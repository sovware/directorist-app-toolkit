<?php

namespace DirectoristAppToolkit\Controller\Licensing;

defined( 'ABSPATH' ) || exit;

/**
 * Activate and track the site entitlement supplied by Directorist.
 */
class License_Manager {

    const OPTION_NAME = 'directorist_app_toolkit_license_state';

    /**
     * Directorist customer licensing endpoint.
     *
     * @var string
     */
    protected $licensing_endpoint = 'https://directorist.com/wp-json/directorist/v1/licencing';

    /**
     * License data captured from a successful Directorist customer login.
     *
     * @var array|null
     */
    protected $pending_license_data;

    /**
     * Whether the shutdown callback has been registered.
     *
     * @var bool
     */
    protected $activation_scheduled = false;

    public function __construct() {
        add_filter( 'http_response', [ $this, 'capture_license_response' ], 10, 3 );
    }

    /**
     * Capture successful Themes & Extensions authentication responses.
     *
     * @param array|\WP_Error $response HTTP response.
     * @param array           $args     HTTP request arguments.
     * @param string          $url      Request URL.
     *
     * @return array|\WP_Error
     */
    public function capture_license_response( $response, $args, $url ) {
        if ( 0 !== strpos( (string) $url, $this->licensing_endpoint ) || is_wp_error( $response ) ) {
            return $response;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( ! is_array( $body ) || empty( $body['success'] ) || ! isset( $body['license_data'] ) || ! is_array( $body['license_data'] ) ) {
            return $response;
        }

        $this->pending_license_data = $body['license_data'];

        if ( ! $this->activation_scheduled ) {
            $this->activation_scheduled = true;
            add_action( 'shutdown', [ $this, 'activate_pending_license' ] );
        }

        return $response;
    }

    /**
     * Activate the first entitlement accepted by the Directorist EDD API.
     *
     * @return void
     */
    public function activate_pending_license() {
        if ( ! is_array( $this->pending_license_data ) ) {
            return;
        }

        $products = [
            'plugin' => isset( $this->pending_license_data['plugins'] ) && is_array( $this->pending_license_data['plugins'] ) ? $this->pending_license_data['plugins'] : [],
            'theme'  => isset( $this->pending_license_data['themes'] ) && is_array( $this->pending_license_data['themes'] ) ? $this->pending_license_data['themes'] : [],
        ];

        foreach ( $products as $product_type => $items ) {
            foreach ( $items as $item ) {
                if ( ! $this->is_activatable_item( $item ) ) {
                    continue;
                }

                $activation = $this->activate_license( $item );

                if ( empty( $activation['success'] ) ) {
                    continue;
                }

                $response   = isset( $activation['response'] ) && is_array( $activation['response'] ) ? $activation['response'] : [];
                $expires_at = $this->normalize_expiration( isset( $response['expires'] ) ? $response['expires'] : '' );

                if ( $expires_at < 0 || ( $expires_at > 0 && $expires_at <= time() ) ) {
                    continue;
                }

                update_option(
                    self::OPTION_NAME,
                    [
                        'active'       => true,
                        'item_id'      => absint( $item['item_id'] ),
                        'product_type' => $product_type,
                        'product_key'  => $this->get_product_key( $item ),
                        'activated_at' => time(),
                        'expires_at'   => $expires_at,
                    ],
                    false
                );

                return;
            }
        }

        update_option(
            self::OPTION_NAME,
            [
                'active'       => false,
                'checked_at'   => time(),
                'expires_at'   => 0,
            ],
            false
        );
    }

    /**
     * Determine whether the saved site entitlement is currently active.
     *
     * @return bool
     */
    public static function has_active_license() {
        $state = get_option( self::OPTION_NAME, [] );

        if ( ! is_array( $state ) || empty( $state['active'] ) ) {
            return false;
        }

        $expires_at = isset( $state['expires_at'] ) ? absint( $state['expires_at'] ) : 0;

        return 0 === $expires_at || $expires_at > time();
    }

    /**
     * Call Directorist's EDD activation helper.
     *
     * @param array $item License item.
     *
     * @return array
     */
    protected function activate_license( $item ) {
        if ( ! class_exists( '\\ATBDP_Extensions' ) || ! is_callable( [ '\\ATBDP_Extensions', 'remote_activate_license' ] ) ) {
            return [ 'success' => false ];
        }

        $activation = \ATBDP_Extensions::remote_activate_license( $item );

        return is_array( $activation ) ? $activation : [ 'success' => false ];
    }

    /**
     * Check the minimum EDD fields before attempting an activation.
     *
     * @param mixed $item License item.
     *
     * @return bool
     */
    protected function is_activatable_item( $item ) {
        return is_array( $item ) && ! empty( $item['item_id'] ) && ! empty( $item['license'] ) && empty( $item['skip_licencing'] );
    }

    /**
     * Normalize the EDD expiration value to a UTC timestamp.
     *
     * Zero represents a lifetime or unspecified expiration on a valid response;
     * negative one represents an invalid expiration value.
     *
     * @param mixed $expiration EDD expiration value.
     *
     * @return int
     */
    protected function normalize_expiration( $expiration ) {
        $expiration = trim( (string) $expiration );

        if ( '' === $expiration || 'lifetime' === strtolower( $expiration ) ) {
            return 0;
        }

        try {
            $date = new \DateTimeImmutable( $expiration, new \DateTimeZone( 'UTC' ) );
        } catch ( \Exception $exception ) {
            return -1;
        }

        return $date->getTimestamp();
    }

    /**
     * Build a non-sensitive product reference for diagnostics.
     *
     * @param array $item License item.
     *
     * @return string
     */
    protected function get_product_key( $item ) {
        if ( empty( $item['permalink'] ) ) {
            return '';
        }

        $path = wp_parse_url( $item['permalink'], PHP_URL_PATH );

        return sanitize_key( basename( untrailingslashit( (string) $path ) ) );
    }
}
