<?php

namespace DirectoristAppToolkit\Helper;

defined( 'ABSPATH' ) || exit;

class Google_Play_Credentials {
    const PATH_CONSTANT = 'DIRECTORIST_APP_GOOGLE_PLAY_CREDENTIALS_FILE';

    /**
     * Return validated service-account credentials.
     *
     * @return array|\WP_Error
     */
    public static function get() {
        if ( ! defined( self::PATH_CONSTANT ) || '' === trim( (string) constant( self::PATH_CONSTANT ) ) ) {
            return new \WP_Error(
                'directorist_app_google_credentials_not_configured',
                __( 'The Google Play service-account JSON file path is not configured.', 'directorist-app-toolkit' )
            );
        }

        $path = (string) constant( self::PATH_CONSTANT );

        if ( ! is_file( $path ) || ! is_readable( $path ) ) {
            return new \WP_Error(
                'directorist_app_google_credentials_unreadable',
                __( 'The configured Google Play service-account JSON file does not exist or is not readable.', 'directorist-app-toolkit' )
            );
        }

        $contents = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        $data     = json_decode( (string) $contents, true );

        if ( ! is_array( $data ) || JSON_ERROR_NONE !== json_last_error() ) {
            return new \WP_Error(
                'directorist_app_google_credentials_invalid_json',
                __( 'The configured Google Play service-account file does not contain valid JSON.', 'directorist-app-toolkit' )
            );
        }

        $required = [ 'client_email', 'private_key', 'token_uri' ];

        foreach ( $required as $key ) {
            if ( empty( $data[ $key ] ) || ! is_string( $data[ $key ] ) ) {
                return new \WP_Error(
                    'directorist_app_google_credentials_invalid',
                    sprintf(
                        /* translators: %s: missing service-account field */
                        __( 'The Google Play service-account JSON is missing the required “%s” value.', 'directorist-app-toolkit' ),
                        $key
                    )
                );
            }
        }

        if ( isset( $data['type'] ) && 'service_account' !== $data['type'] ) {
            return new \WP_Error(
                'directorist_app_google_credentials_wrong_type',
                __( 'The configured Google credential must be a service-account key.', 'directorist-app-toolkit' )
            );
        }

        if ( ! wp_http_validate_url( $data['token_uri'] ) || 'https' !== wp_parse_url( $data['token_uri'], PHP_URL_SCHEME ) ) {
            return new \WP_Error(
                'directorist_app_google_credentials_invalid_token_uri',
                __( 'The Google service-account token URI must be a valid HTTPS URL.', 'directorist-app-toolkit' )
            );
        }

        return $data;
    }

    /**
     * Return a safe status object for the settings UI.
     *
     * @return array
     */
    public static function get_status() {
        $credentials = self::get();

        if ( is_wp_error( $credentials ) ) {
            return [
                'valid'        => false,
                'message'      => $credentials->get_error_message(),
                'client_email' => '',
            ];
        }

        return [
            'valid'        => true,
            'message'      => __( 'Configured and valid', 'directorist-app-toolkit' ),
            'client_email' => sanitize_email( $credentials['client_email'] ),
        ];
    }
}
