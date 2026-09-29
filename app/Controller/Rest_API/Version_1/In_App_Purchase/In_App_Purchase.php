<?php

namespace DirectoristAppToolkit\Controller\Rest_API\Version_1\In_App_Purchase;

use DirectoristAppToolkit\Controller\Rest_API\Version_1\Helper\Rest_Base;
use DirectoristAppToolkit\Helper\Apple_Purchase_Verifier;
use DirectoristAppToolkit\Helper\App_Settings;
use DirectoristAppToolkit\Helper\Google_Play_Credentials;
use DirectoristAppToolkit\Helper\Google_Purchase_Verifier;
use DirectoristAppToolkit\Helper\In_App_Purchase as IAP_Helper;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

class In_App_Purchase extends Rest_Base {
    protected $rest_base = 'in-app-purchases/plans';

    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>[\d]+)/eligibility',
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'check_eligibility' ],
                'permission_callback' => [ $this, 'permissions_check' ],
                'args'                => $this->get_common_args(),
            ]
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>[\d]+)/purchase',
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'purchase' ],
                'permission_callback' => [ $this, 'permissions_check' ],
                'args'                => $this->get_common_args(),
            ]
        );
    }

    public function permissions_check() {
        if ( is_user_logged_in() ) {
            return true;
        }

        return new WP_Error(
            'directorist_app_iap_authentication_required',
            __( 'Authentication is required to purchase a pricing plan.', 'directorist-app-toolkit' ),
            [ 'status' => rest_authorization_required_code() ]
        );
    }

    public function check_eligibility( WP_REST_Request $request ) {
        $context = $this->get_plan_context( $request );

        if ( is_wp_error( $context ) ) {
            return $context;
        }

        $platform = $request->get_param( 'platform' );

        return rest_ensure_response(
            [
                'can_purchase'  => true,
                'plan_id'       => (int) $request['id'],
                'platform'      => $platform,
                'order_type'    => $context['order_type'],
                'product_id'    => $context['product_id'],
                'price'         => (string) $context['expected_amount'],
                'currency'      => strtoupper( (string) $context['currency'] ),
                'account_token' => IAP_Helper::get_account_token( $platform, get_current_user_id() ),
            ]
        );
    }

    public function purchase( WP_REST_Request $request ) {
        $context = $this->get_plan_context( $request );

        if ( is_wp_error( $context ) ) {
            return $context;
        }

        $payment = $request->get_param( 'payment' );
        if ( ! is_array( $payment ) ) {
            return new WP_Error( 'directorist_app_iap_payment_required', __( 'A payment verification object is required.', 'directorist-app-toolkit' ), [ 'status' => 400 ] );
        }

        try {
            $pending = apply_filters( 'directorist_app_toolkit_iap_create_pending_order', null, $context, $request );
        } catch ( \Throwable $exception ) {
            return $this->exception_to_error( $exception );
        }

        if ( is_wp_error( $pending ) ) {
            return $pending;
        }

        if ( ! is_array( $pending ) || empty( $pending['order_id'] ) ) {
            return new WP_Error( 'directorist_app_iap_order_not_created', __( 'The pricing-plan provider could not create a pending order.', 'directorist-app-toolkit' ), [ 'status' => 500 ] );
        }

        $verifier = 'apple' === $request->get_param( 'platform' ) ? new Apple_Purchase_Verifier() : new Google_Purchase_Verifier();

        try {
            $verification = $verifier->verify( $payment, $context, get_current_user_id() );
        } catch ( \Throwable $exception ) {
            $verification = $this->exception_to_error( $exception );
        }

        if ( is_wp_error( $verification ) ) {
            do_action( 'directorist_app_toolkit_iap_fail_order', $pending, $verification, $context, $request );
            return $verification;
        }

        $claim = [
            'user_id'        => get_current_user_id(),
            'plan_id'        => (int) $request['id'],
            'order_type'     => $context['order_type'],
            'order_id'       => (int) $pending['order_id'],
            'transaction_id' => $verification['transaction_id'],
            'created_at'     => current_time( 'mysql', true ),
        ];

        if ( ! IAP_Helper::claim_transaction( $verification['platform'], $verification['transaction_id'], $claim ) ) {
            $error = new WP_Error( 'directorist_app_iap_transaction_already_used', __( 'This store transaction has already been used.', 'directorist-app-toolkit' ), [ 'status' => 409 ] );
            do_action( 'directorist_app_toolkit_iap_fail_order', $pending, $error, $context, $request );
            return $error;
        }

        try {
            $completed = apply_filters( 'directorist_app_toolkit_iap_complete_order', null, $pending, $verification, $context, $request );
        } catch ( \Throwable $exception ) {
            $error = $this->exception_to_error( $exception );
            do_action( 'directorist_app_toolkit_iap_fail_order', $pending, $error, $context, $request );
            return $error;
        }

        if ( is_wp_error( $completed ) ) {
            do_action( 'directorist_app_toolkit_iap_fail_order', $pending, $completed, $context, $request );
            return $completed;
        }

        return rest_ensure_response(
            [
                'order_type' => $context['order_type'],
                'data'       => $completed,
            ]
        );
    }

    private function get_plan_context( WP_REST_Request $request ) {
        try {
            $context = apply_filters(
                'directorist_app_toolkit_iap_plan_context',
                null,
                (int) $request['id'],
                (string) $request->get_param( 'platform' ),
                $request
            );
        } catch ( \Throwable $exception ) {
            return $this->exception_to_error( $exception );
        }

        if ( is_wp_error( $context ) ) {
            return $context;
        }

        $required = [ 'order_type', 'product_id', 'expected_amount', 'currency' ];
        if ( ! is_array( $context ) || array_diff( $required, array_keys( $context ) ) ) {
            return new WP_Error( 'directorist_app_iap_plan_provider_unavailable', __( 'No compatible pricing-plan provider is available for this plan.', 'directorist-app-toolkit' ), [ 'status' => 404 ] );
        }

        if ( '' === trim( (string) $context['product_id'] ) || '' === trim( (string) $context['expected_amount'] ) ) {
            return new WP_Error( 'directorist_app_iap_plan_not_configured', __( 'This plan is not configured for the selected app store.', 'directorist-app-toolkit' ), [ 'status' => 422 ] );
        }

        $amount = IAP_Helper::decimal_to_nanos( $context['expected_amount'] );
        if ( is_wp_error( $amount ) || ! preg_match( '/^[A-Z]{3}$/', strtoupper( (string) $context['currency'] ) ) ) {
            return new WP_Error( 'directorist_app_iap_plan_price_invalid', __( 'This plan has an invalid app-store price or currency configuration.', 'directorist-app-toolkit' ), [ 'status' => 422 ] );
        }

        $configuration = $this->validate_platform_configuration( (string) $request->get_param( 'platform' ) );
        if ( is_wp_error( $configuration ) ) {
            return $configuration;
        }

        return $context;
    }

    private function validate_platform_configuration( $platform ) {
        if ( 'apple' === $platform ) {
            if ( '' === trim( (string) App_Settings::get_setting( 'app_iap_apple_bundle_id', '' ) ) ) {
                return new WP_Error( 'directorist_app_iap_apple_not_configured', __( 'Configure the Apple Bundle ID before accepting Apple purchases.', 'directorist-app-toolkit' ), [ 'status' => 503 ] );
            }

            if ( ! function_exists( 'openssl_verify' ) ) {
                return new WP_Error( 'directorist_app_iap_openssl_unavailable', __( 'The PHP OpenSSL extension is required to verify Apple purchases.', 'directorist-app-toolkit' ), [ 'status' => 503 ] );
            }

            return true;
        }

        if ( '' === trim( (string) App_Settings::get_setting( 'app_iap_google_package_name', '' ) ) ) {
            return new WP_Error( 'directorist_app_iap_google_not_configured', __( 'Configure the Google Package Name before accepting Google Play purchases.', 'directorist-app-toolkit' ), [ 'status' => 503 ] );
        }

        $credentials = Google_Play_Credentials::get();
        if ( is_wp_error( $credentials ) ) {
            $credentials->add_data( [ 'status' => 503 ] );
            return $credentials;
        }

        return true;
    }

    private function get_common_args() {
        return [
            'id'          => [ 'type' => 'integer', 'minimum' => 1, 'required' => true ],
            'platform'    => [
                'type'              => 'string',
                'required'          => true,
                'enum'              => [ 'apple', 'google' ],
                'sanitize_callback' => 'sanitize_key',
            ],
            'listing_id'  => [ 'type' => 'integer', 'minimum' => 0, 'default' => 0 ],
            'is_featured' => [ 'type' => 'boolean', 'default' => false ],
        ];
    }

    private function exception_to_error( \Throwable $exception ) {
        $status = (int) $exception->getCode();
        if ( $status < 400 || $status > 599 ) {
            $status = 400;
        }

        return new WP_Error( 'directorist_app_iap_request_failed', $exception->getMessage(), [ 'status' => $status ] );
    }
}
