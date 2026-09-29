<?php

namespace DirectoristAppToolkit\Helper;

use Firebase\JWT\JWT;

defined( 'ABSPATH' ) || exit;

class Google_Purchase_Verifier {
    const SCOPE = 'https://www.googleapis.com/auth/androidpublisher';

    public function verify( array $payment, array $context, $user_id ) {
        $purchase_token = isset( $payment['purchase_token'] ) ? trim( (string) $payment['purchase_token'] ) : '';
        $package_name   = trim( (string) App_Settings::get_setting( 'app_iap_google_package_name', '' ) );

        if ( '' === $purchase_token ) {
            return new \WP_Error( 'directorist_app_iap_google_proof_required', __( 'purchase_token is required for a Google Play purchase.', 'directorist-app-toolkit' ), [ 'status' => 400 ] );
        }

        if ( '' === $package_name ) {
            return new \WP_Error( 'directorist_app_iap_google_package_missing', __( 'The Google Play package name is not configured.', 'directorist-app-toolkit' ), [ 'status' => 503 ] );
        }

        $token = $this->get_access_token();
        if ( is_wp_error( $token ) ) {
            return $token;
        }

        $purchase_url = sprintf(
            'https://androidpublisher.googleapis.com/androidpublisher/v3/applications/%s/purchases/productsv2/tokens/%s',
            rawurlencode( $package_name ),
            rawurlencode( $purchase_token )
        );
        $purchase     = $this->get_json( $purchase_url, $token );

        if ( is_wp_error( $purchase ) ) {
            return $purchase;
        }

        $state = (string) ( $purchase['purchaseStateContext']['purchaseState'] ?? '' );
        if ( 'PURCHASED' !== $state ) {
            return new \WP_Error( 'directorist_app_iap_google_not_paid', __( 'The Google Play purchase is not in the purchased state.', 'directorist-app-toolkit' ), [ 'status' => 422 ] );
        }

        $line_items = isset( $purchase['productLineItem'] ) && is_array( $purchase['productLineItem'] ) ? $purchase['productLineItem'] : [];
        $product    = null;
        foreach ( $line_items as $line_item ) {
            if ( hash_equals( (string) $context['product_id'], (string) ( $line_item['productId'] ?? '' ) ) ) {
                $product = $line_item;
                break;
            }
        }

        if ( ! $product ) {
            return new \WP_Error( 'directorist_app_iap_google_product_mismatch', __( 'The Google Play purchase product does not match this plan.', 'directorist-app-toolkit' ), [ 'status' => 422 ] );
        }

        $expected_account = In_App_Purchase::get_account_token( 'google', $user_id );
        if ( empty( $purchase['obfuscatedExternalAccountId'] ) || ! hash_equals( $expected_account, (string) $purchase['obfuscatedExternalAccountId'] ) ) {
            return new \WP_Error( 'directorist_app_iap_google_account_mismatch', __( 'The Google Play purchase is not assigned to the current user.', 'directorist-app-toolkit' ), [ 'status' => 422 ] );
        }

        $is_test   = 'TEST' === (string) ( $purchase['testPurchaseContext']['fopType'] ?? '' );
        $test_mode = ! empty( App_Settings::get_setting( 'app_iap_google_test_mode', false ) );
        if ( $is_test !== $test_mode ) {
            return new \WP_Error( 'directorist_app_iap_google_environment_mismatch', __( 'The Google Play purchase environment does not match Test Mode.', 'directorist-app-toolkit' ), [ 'status' => 422 ] );
        }

        $order_id = trim( (string) ( $purchase['orderId'] ?? '' ) );
        if ( '' === $order_id ) {
            return new \WP_Error( 'directorist_app_iap_google_order_missing', __( 'Google Play did not return an order ID for this purchase.', 'directorist-app-toolkit' ), [ 'status' => 422 ] );
        }

        $order_url = sprintf(
            'https://androidpublisher.googleapis.com/androidpublisher/v3/applications/%s/orders/%s',
            rawurlencode( $package_name ),
            rawurlencode( $order_id )
        );
        $order     = $this->get_json( $order_url, $token );

        if ( is_wp_error( $order ) ) {
            return $order;
        }

        if ( 'PROCESSED' !== (string) ( $order['state'] ?? '' ) ) {
            return new \WP_Error( 'directorist_app_iap_google_order_not_paid', __( 'The Google Play order is not in a paid state.', 'directorist-app-toolkit' ), [ 'status' => 422 ] );
        }

        $order_item = null;
        foreach ( (array) ( $order['lineItems'] ?? [] ) as $line_item ) {
            if ( hash_equals( (string) $context['product_id'], (string) ( $line_item['productId'] ?? '' ) ) ) {
                $order_item = $line_item;
                break;
            }
        }

        if ( ! $order_item || empty( $order_item['total'] ) ) {
            return new \WP_Error( 'directorist_app_iap_google_amount_missing', __( 'Google Play did not return the paid amount for this product.', 'directorist-app-toolkit' ), [ 'status' => 422 ] );
        }

        $paid_nanos = In_App_Purchase::google_money_to_nanos( $order_item['total'] );
        $currency   = strtoupper( (string) ( $order_item['total']['currencyCode'] ?? '' ) );

        if ( is_wp_error( $paid_nanos ) || ! In_App_Purchase::amounts_match( $context['expected_amount'], $paid_nanos ) ) {
            return new \WP_Error( 'directorist_app_iap_google_amount_mismatch', __( 'The amount paid through Google Play does not match the plan price.', 'directorist-app-toolkit' ), [ 'status' => 422 ] );
        }

        if ( ! hash_equals( strtoupper( (string) $context['currency'] ), $currency ) ) {
            return new \WP_Error( 'directorist_app_iap_google_currency_mismatch', __( 'The Google Play order currency does not match the plan currency.', 'directorist-app-toolkit' ), [ 'status' => 422 ] );
        }

        return [
            'platform'       => 'google',
            'transaction_id' => $order_id,
            'amount'         => (string) $context['expected_amount'],
            'currency'       => $currency,
            'environment'    => $is_test ? 'test' : 'production',
            'purchase_hash'  => hash( 'sha256', $purchase_token ),
        ];
    }

    private function get_access_token() {
        $credentials = Google_Play_Credentials::get();
        if ( is_wp_error( $credentials ) ) {
            return $credentials;
        }

        $cache_key = 'directorist_google_oauth_' . md5( $credentials['client_email'] . ( $credentials['private_key_id'] ?? '' ) );
        $cached    = get_transient( $cache_key );
        if ( is_string( $cached ) && '' !== $cached ) {
            return $cached;
        }

        $now       = time();
        $assertion = JWT::encode(
            [
                'iss'   => $credentials['client_email'],
                'scope' => self::SCOPE,
                'aud'   => $credentials['token_uri'],
                'iat'   => $now,
                'exp'   => $now + 3600,
            ],
            $credentials['private_key'],
            'RS256'
        );

        $response = wp_remote_post(
            $credentials['token_uri'],
            [
                'timeout' => 20,
                'body'    => [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion'  => $assertion,
                ],
            ]
        );

        if ( is_wp_error( $response ) ) {
            return new \WP_Error( 'directorist_app_iap_google_auth_failed', $response->get_error_message(), [ 'status' => 502 ] );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( 200 !== wp_remote_retrieve_response_code( $response ) || empty( $body['access_token'] ) ) {
            return new \WP_Error( 'directorist_app_iap_google_auth_failed', __( 'Google rejected the configured service-account credentials.', 'directorist-app-toolkit' ), [ 'status' => 502 ] );
        }

        set_transient( $cache_key, $body['access_token'], max( 60, (int) ( $body['expires_in'] ?? 3600 ) - 120 ) );
        return $body['access_token'];
    }

    private function get_json( $url, $access_token ) {
        $response = wp_remote_get(
            $url,
            [
                'timeout' => 20,
                'headers' => [ 'Authorization' => 'Bearer ' . $access_token ],
            ]
        );

        if ( is_wp_error( $response ) ) {
            return new \WP_Error( 'directorist_app_iap_google_request_failed', $response->get_error_message(), [ 'status' => 502 ] );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( 200 !== wp_remote_retrieve_response_code( $response ) || ! is_array( $body ) ) {
            return new \WP_Error( 'directorist_app_iap_google_request_failed', __( 'Google Play could not verify this purchase.', 'directorist-app-toolkit' ), [ 'status' => 422 ] );
        }

        return $body;
    }
}
