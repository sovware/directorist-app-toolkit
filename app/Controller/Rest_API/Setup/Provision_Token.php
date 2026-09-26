<?php

namespace DirectoristAppToolkit\Controller\Rest_API\Setup;

use DirectoristAppToolkit\Helper\Provision;
use Firebase\JWT\JWT;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

class Provision_Token {
    const REST_NAMESPACE = 'directorist-app-toolkit/v1';
    const REST_BASE      = 'provision/token';
    const TOKEN_AUDIENCE = 'directorist-app-toolkit-provision';

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
        add_filter( 'determine_current_user', [ $this, 'determine_current_user' ], 20 );
    }

    /**
     * Register the provision-token endpoint.
     *
     * @return void
     */
    public function register_routes() {
        register_rest_route(
            self::REST_NAMESPACE,
            '/' . self::REST_BASE,
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'create_token' ],
                'permission_callback' => '__return_true',
                'args'                => [
                    'provision_key' => [
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => static function ( $value ) {
                            return is_string( $value ) ? $value : '';
                        },
                    ],
                ],
            ]
        );
    }

    /**
     * Exchange the configured provision key for an administrator JWT.
     *
     * @param WP_REST_Request $request REST request.
     *
     * @return array|WP_Error
     */
    public function create_token( WP_REST_Request $request ) {
        $status = Provision::get_status();

        if ( ! $status['configured'] ) {
            return new WP_Error(
                'directorist_app_provision_not_configured',
                __( 'Administrator provisioning is not configured correctly.', 'directorist-app-toolkit' ),
                [ 'status' => 503 ]
            );
        }

        $provided_key   = $request->get_param( 'provision_key' );
        $configured_key = Provision::get_key();

        if ( ! is_string( $provided_key ) || ! hash_equals( $configured_key, $provided_key ) ) {
            return new WP_Error(
                'directorist_app_provision_key_invalid',
                __( 'The provision key is invalid.', 'directorist-app-toolkit' ),
                [ 'status' => 403 ]
            );
        }

        $user       = Provision::get_user();
        $issued_at  = time();
        $not_before = (int) apply_filters( 'directorist_app_toolkit_provision_token_not_before', $issued_at, $user );
        $expires_at = (int) apply_filters( 'directorist_app_toolkit_provision_token_expiration', $issued_at + ( DAY_IN_SECONDS * 7 ), $issued_at, $user );
        $payload    = [
            'iss' => get_bloginfo( 'url' ),
            'aud' => self::TOKEN_AUDIENCE,
            'iat' => $issued_at,
            'nbf' => $not_before,
            'exp' => $expires_at,
            'data' => [
                'user' => [
                    'id' => (int) $user->ID,
                ],
                'provision_key_hash' => hash( 'sha256', $configured_key ),
            ],
        ];

        $payload = apply_filters( 'directorist_app_toolkit_provision_token_before_sign', $payload, $user );
        $token   = JWT::encode( $payload, $this->get_signing_key() );
        $data    = [
            'token'             => $token,
            'token_type'        => 'Bearer',
            'expires_in'        => max( 0, $expires_at - $issued_at ),
            'expires_at'        => $expires_at,
            'user_id'           => (int) $user->ID,
            'user_email'        => $user->user_email,
            'user_nicename'     => $user->user_nicename,
            'user_display_name' => $user->display_name,
        ];

        return apply_filters( 'directorist_app_toolkit_provision_token_before_dispatch', $data, $user );
    }

    /**
     * Authenticate provisioned JWTs for WordPress REST requests.
     *
     * @param int|false $user_id Previously authenticated user ID.
     *
     * @return int|false
     */
    public function determine_current_user( $user_id ) {
        if ( $user_id || ! $this->is_rest_request() ) {
            return $user_id;
        }

        $token = $this->get_bearer_token();

        if ( '' === $token ) {
            return $user_id;
        }

        try {
            $payload = JWT::decode( $token, $this->get_signing_key(), [ 'HS256' ] );
        } catch ( \Exception $exception ) {
            return $user_id;
        }

        if (
            empty( $payload->aud ) ||
            self::TOKEN_AUDIENCE !== $payload->aud ||
            empty( $payload->iss ) ||
            get_bloginfo( 'url' ) !== $payload->iss ||
            empty( $payload->data->user->id ) ||
            empty( $payload->data->provision_key_hash )
        ) {
            return $user_id;
        }

        $status = Provision::get_status();
        $user   = Provision::get_user();

        if (
            ! $status['configured'] ||
            ! $user ||
            (int) $user->ID !== (int) $payload->data->user->id ||
            ! hash_equals( hash( 'sha256', Provision::get_key() ), (string) $payload->data->provision_key_hash )
        ) {
            return $user_id;
        }

        return (int) $user->ID;
    }

    /**
     * Get the server-side JWT signing key.
     *
     * @return string
     */
    protected function get_signing_key() {
        return (string) apply_filters( 'directorist_app_toolkit_provision_jwt_signing_key', wp_salt( 'auth' ) );
    }

    /**
     * Get a bearer token from the request headers.
     *
     * @return string
     */
    protected function get_bearer_token() {
        $authorization = isset( $_SERVER['HTTP_AUTHORIZATION'] ) ? (string) $_SERVER['HTTP_AUTHORIZATION'] : '';

        if ( '' === $authorization && isset( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) ) {
            $authorization = (string) $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        return 1 === preg_match( '/^Bearer\s+(\S+)$/i', $authorization, $matches ) ? $matches[1] : '';
    }

    /**
     * Determine whether WordPress is handling a REST request.
     *
     * @return bool
     */
    protected function is_rest_request() {
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            return true;
        }

        if ( empty( $_SERVER['REQUEST_URI'] ) ) {
            return false;
        }

        return false !== strpos( (string) $_SERVER['REQUEST_URI'], '/' . rest_get_url_prefix() . '/' );
    }
}
