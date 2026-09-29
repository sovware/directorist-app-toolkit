<?php

namespace DirectoristAppToolkit\Helper;

defined( 'ABSPATH' ) || exit;

class Apple_Purchase_Verifier {
    public function verify( array $payment, array $context, $user_id ) {
        $jws = isset( $payment['signed_transaction_info'] ) ? trim( (string) $payment['signed_transaction_info'] ) : '';

        if ( '' === $jws ) {
            return new \WP_Error( 'directorist_app_iap_apple_proof_required', __( 'signed_transaction_info is required for an Apple purchase.', 'directorist-app-toolkit' ), [ 'status' => 400 ] );
        }

        $decoded = $this->decode_and_verify( $jws );

        if ( is_wp_error( $decoded ) ) {
            return $decoded;
        }

        $bundle_id   = (string) App_Settings::get_setting( 'app_iap_apple_bundle_id', '' );
        $environment = ! empty( App_Settings::get_setting( 'app_iap_apple_test_mode', false ) ) ? 'Sandbox' : 'Production';
        $account     = In_App_Purchase::get_account_token( 'apple', $user_id );
        $price_nanos = isset( $decoded['price'] ) ? (int) $decoded['price'] * 1000000 : -1;

        $checks = [
            [ ! empty( $bundle_id ) && hash_equals( $bundle_id, (string) ( $decoded['bundleId'] ?? '' ) ), __( 'The Apple transaction bundle ID does not match this app.', 'directorist-app-toolkit' ) ],
            [ hash_equals( (string) $context['product_id'], (string) ( $decoded['productId'] ?? '' ) ), __( 'The Apple transaction product does not match this plan.', 'directorist-app-toolkit' ) ],
            [ hash_equals( $environment, (string) ( $decoded['environment'] ?? '' ) ), __( 'The Apple transaction environment does not match Test Mode.', 'directorist-app-toolkit' ) ],
            [ hash_equals( $account, strtolower( (string) ( $decoded['appAccountToken'] ?? '' ) ) ), __( 'The Apple transaction is not assigned to the current user.', 'directorist-app-toolkit' ) ],
            [ empty( $decoded['revocationDate'] ), __( 'The Apple transaction has been revoked.', 'directorist-app-toolkit' ) ],
            [ In_App_Purchase::amounts_match( $context['expected_amount'], $price_nanos ), __( 'The amount paid through Apple does not match the plan price.', 'directorist-app-toolkit' ) ],
            [ hash_equals( strtoupper( (string) $context['currency'] ), strtoupper( (string) ( $decoded['currency'] ?? '' ) ) ), __( 'The Apple transaction currency does not match the plan currency.', 'directorist-app-toolkit' ) ],
        ];

        foreach ( $checks as $check ) {
            if ( ! $check[0] ) {
                return new \WP_Error( 'directorist_app_iap_apple_verification_failed', $check[1], [ 'status' => 422 ] );
            }
        }

        if ( empty( $decoded['transactionId'] ) ) {
            return new \WP_Error( 'directorist_app_iap_apple_transaction_missing', __( 'The Apple transaction ID is missing.', 'directorist-app-toolkit' ), [ 'status' => 422 ] );
        }

        return [
            'platform'       => 'apple',
            'transaction_id' => (string) $decoded['transactionId'],
            'amount'         => (string) $context['expected_amount'],
            'currency'       => strtoupper( (string) $decoded['currency'] ),
            'environment'    => $environment,
        ];
    }

    private function decode_and_verify( $jws ) {
        $parts = explode( '.', $jws );

        if ( 3 !== count( $parts ) ) {
            return $this->error( __( 'The Apple signed transaction is malformed.', 'directorist-app-toolkit' ) );
        }

        $header    = json_decode( $this->base64url_decode( $parts[0] ), true );
        $payload   = json_decode( $this->base64url_decode( $parts[1] ), true );
        $signature = $this->base64url_decode( $parts[2] );

        if ( ! is_array( $header ) || ! is_array( $payload ) || 'ES256' !== ( $header['alg'] ?? '' ) || empty( $header['x5c'] ) || ! is_array( $header['x5c'] ) ) {
            return $this->error( __( 'The Apple signed transaction header is invalid.', 'directorist-app-toolkit' ) );
        }

        $certificates = [];
        foreach ( $header['x5c'] as $certificate ) {
            $certificates[] = "-----BEGIN CERTIFICATE-----\n" . chunk_split( preg_replace( '/\s+/', '', (string) $certificate ), 64, "\n" ) . "-----END CERTIFICATE-----\n";
        }

        if ( 3 !== count( $certificates ) ) {
            return $this->error( __( 'The Apple signing certificate chain has an invalid length.', 'directorist-app-toolkit' ) );
        }

        $root_path = DIRECTORIST_APP_TOOLKIT_PATH . 'assets/certificates/AppleRootCA-G3.pem';
        $root      = is_readable( $root_path ) ? file_get_contents( $root_path ) : false; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

        if ( ! $root ) {
            return $this->error( __( 'The trusted Apple root certificate is unavailable.', 'directorist-app-toolkit' ) );
        }

        $now = time();
        foreach ( $certificates as $index => $certificate ) {
            $parsed = openssl_x509_parse( $certificate );
            if ( ! is_array( $parsed ) || $now < (int) ( $parsed['validFrom_time_t'] ?? 0 ) || $now > (int) ( $parsed['validTo_time_t'] ?? 0 ) ) {
                return $this->error( __( 'The Apple signing certificate is invalid or expired.', 'directorist-app-toolkit' ) );
            }

            $issuer = isset( $certificates[ $index + 1 ] ) ? $certificates[ $index + 1 ] : $root;
            if ( 1 !== openssl_x509_verify( $certificate, openssl_pkey_get_public( $issuer ) ) ) {
                return $this->error( __( 'The Apple signing certificate chain is not trusted.', 'directorist-app-toolkit' ) );
            }
        }

        $leaf_extensions         = (array) ( openssl_x509_parse( $certificates[0] )['extensions'] ?? [] );
        $intermediate_extensions = (array) ( openssl_x509_parse( $certificates[1] )['extensions'] ?? [] );

        if ( ! array_key_exists( '1.2.840.113635.100.6.11.1', $leaf_extensions )
            || ! array_key_exists( '1.2.840.113635.100.6.2.1', $intermediate_extensions )
            || ! hash_equals( (string) openssl_x509_fingerprint( $root, 'sha256' ), (string) openssl_x509_fingerprint( $certificates[2], 'sha256' ) )
        ) {
            return $this->error( __( 'The Apple signing certificate chain is not an App Store receipt-signing chain.', 'directorist-app-toolkit' ) );
        }

        $leaf_key = openssl_pkey_get_public( $certificates[0] );
        $der_sig  = $this->ecdsa_raw_to_der( $signature );

        if ( ! $leaf_key || ! $der_sig || 1 !== openssl_verify( $parts[0] . '.' . $parts[1], $der_sig, $leaf_key, OPENSSL_ALGO_SHA256 ) ) {
            return $this->error( __( 'The Apple transaction signature is invalid.', 'directorist-app-toolkit' ) );
        }

        return $payload;
    }

    private function base64url_decode( $value ) {
        $value = strtr( $value, '-_', '+/' );
        $value = str_pad( $value, strlen( $value ) + ( 4 - strlen( $value ) % 4 ) % 4, '=' );
        return (string) base64_decode( $value, true );
    }

    private function ecdsa_raw_to_der( $signature ) {
        if ( 64 !== strlen( $signature ) ) {
            return '';
        }

        $encode = static function( $integer ) {
            $integer = ltrim( $integer, "\x00" );
            $integer = '' === $integer ? "\x00" : $integer;
            if ( ord( $integer[0] ) > 0x7f ) {
                $integer = "\x00" . $integer;
            }
            return "\x02" . chr( strlen( $integer ) ) . $integer;
        };

        $body = $encode( substr( $signature, 0, 32 ) ) . $encode( substr( $signature, 32 ) );
        return "\x30" . chr( strlen( $body ) ) . $body;
    }

    private function error( $message ) {
        return new \WP_Error( 'directorist_app_iap_apple_verification_failed', $message, [ 'status' => 422 ] );
    }
}
