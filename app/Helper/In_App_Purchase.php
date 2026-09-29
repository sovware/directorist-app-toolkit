<?php

namespace DirectoristAppToolkit\Helper;

defined( 'ABSPATH' ) || exit;

class In_App_Purchase {
    const APPLE_ACCOUNT_META = '_directorist_app_iap_apple_account_token';

    public static function get_account_token( $platform, $user_id ) {
        if ( 'apple' === $platform ) {
            $token = get_user_meta( $user_id, self::APPLE_ACCOUNT_META, true );

            if ( ! is_string( $token ) || ! preg_match( '/^[a-f0-9-]{36}$/i', $token ) ) {
                $token = wp_generate_uuid4();
                update_user_meta( $user_id, self::APPLE_ACCOUNT_META, $token );
            }

            return strtolower( $token );
        }

        return hash_hmac( 'sha256', 'directorist-app-iap-user:' . (int) $user_id, wp_salt( 'auth' ) );
    }

    public static function decimal_to_nanos( $amount ) {
        $amount = trim( (string) $amount );

        if ( ! preg_match( '/^(\d+)(?:\.(\d{1,9}))?$/', $amount, $matches ) ) {
            return new \WP_Error( 'directorist_app_iap_invalid_amount', __( 'The configured plan price is invalid.', 'directorist-app-toolkit' ) );
        }

        $fraction = isset( $matches[2] ) ? str_pad( $matches[2], 9, '0' ) : '000000000';

        return ( (int) $matches[1] * 1000000000 ) + (int) $fraction;
    }

    public static function google_money_to_nanos( $money ) {
        if ( ! is_array( $money ) ) {
            return new \WP_Error( 'directorist_app_iap_google_amount_missing', __( 'Google Play did not return the paid amount.', 'directorist-app-toolkit' ) );
        }

        $units = isset( $money['units'] ) ? (int) $money['units'] : 0;
        $nanos = isset( $money['nanos'] ) ? (int) $money['nanos'] : 0;

        return ( $units * 1000000000 ) + $nanos;
    }

    public static function amounts_match( $expected, $actual ) {
        $expected_nanos = self::decimal_to_nanos( $expected );

        return ! is_wp_error( $expected_nanos ) && (int) $expected_nanos === (int) $actual;
    }

    public static function claim_transaction( $platform, $transaction_id, array $record ) {
        $key = 'directorist_app_iap_tx_' . hash( 'sha256', $platform . '|' . $transaction_id );

        return add_option( $key, $record, '', false );
    }
}
