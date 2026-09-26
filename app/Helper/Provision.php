<?php

namespace DirectoristAppToolkit\Helper;

defined( 'ABSPATH' ) || exit;

class Provision {
    const KEY_CONSTANT      = 'DIRECTORIST_APP_PROVISION_KEY';
    const USERNAME_CONSTANT = 'DIRECTORIST_APP_PROVISION_USERNAME';

    /**
     * Get the current provision configuration status.
     *
     * @return array
     */
    public static function get_status() {
        $key_status      = self::get_key_status();
        $username_status = self::get_username_status();

        return [
            'configured' => $key_status['valid'] && $username_status['valid'],
            'items'      => [
                'provision_key' => $key_status,
                'username'      => $username_status,
            ],
        ];
    }

    /**
     * Get the configured provision key when it is valid.
     *
     * @return string
     */
    public static function get_key() {
        $status = self::get_key_status();

        return $status['valid'] ? $status['value'] : '';
    }

    /**
     * Get the configured administrator when the username is valid.
     *
     * @return \WP_User|null
     */
    public static function get_user() {
        $status = self::get_username_status();

        return $status['valid'] ? $status['user'] : null;
    }

    /**
     * Generate a random example provision key.
     *
     * @return string
     */
    public static function generate_example_key() {
        try {
            return bin2hex( random_bytes( 32 ) );
        } catch ( \Exception $exception ) {
            return wp_generate_password( 64, false, false );
        }
    }

    /**
     * Get the username of the first administrator.
     *
     * @return string
     */
    public static function get_default_username() {
        $users = get_users(
            [
                'role'    => 'administrator',
                'orderby' => 'ID',
                'order'   => 'ASC',
                'number'  => 1,
                'fields'  => [ 'user_login' ],
            ]
        );

        return ! empty( $users[0]->user_login ) ? (string) $users[0]->user_login : '';
    }

    /**
     * Validate the provision-key constant.
     *
     * @return array
     */
    protected static function get_key_status() {
        $defined = defined( self::KEY_CONSTANT );
        $value   = $defined ? constant( self::KEY_CONSTANT ) : '';
        $error   = '';

        if ( ! $defined ) {
            $error = sprintf(
                /* translators: %s: PHP constant name */
                __( '%s is not defined.', 'directorist-app-toolkit' ),
                self::KEY_CONSTANT
            );
        } elseif ( ! is_string( $value ) ) {
            $error = sprintf(
                /* translators: %s: PHP constant name */
                __( '%s must be a string.', 'directorist-app-toolkit' ),
                self::KEY_CONSTANT
            );
        } elseif ( 64 !== strlen( $value ) ) {
            $error = sprintf(
                /* translators: %s: PHP constant name */
                __( '%s must contain exactly 64 characters.', 'directorist-app-toolkit' ),
                self::KEY_CONSTANT
            );
        } elseif ( preg_match( '/\s/', $value ) ) {
            $error = sprintf(
                /* translators: %s: PHP constant name */
                __( '%s must not contain spaces or other whitespace.', 'directorist-app-toolkit' ),
                self::KEY_CONSTANT
            );
        }

        return [
            'constant' => self::KEY_CONSTANT,
            'defined'  => $defined,
            'valid'    => '' === $error,
            'value'    => is_scalar( $value ) ? (string) $value : '',
            'error'    => $error,
        ];
    }

    /**
     * Validate the provision-username constant.
     *
     * @return array
     */
    protected static function get_username_status() {
        $defined = defined( self::USERNAME_CONSTANT );
        $value   = $defined ? constant( self::USERNAME_CONSTANT ) : '';
        $error   = '';
        $user    = null;

        if ( ! $defined ) {
            $error = sprintf(
                /* translators: %s: PHP constant name */
                __( '%s is not defined.', 'directorist-app-toolkit' ),
                self::USERNAME_CONSTANT
            );
        } elseif ( ! is_string( $value ) || '' === trim( $value ) ) {
            $error = sprintf(
                /* translators: %s: PHP constant name */
                __( '%s must contain a WordPress username.', 'directorist-app-toolkit' ),
                self::USERNAME_CONSTANT
            );
        } else {
            $user = get_user_by( 'login', $value );

            if ( ! $user ) {
                $error = sprintf(
                    /* translators: %s: configured WordPress username */
                    __( 'The configured user “%s” does not exist.', 'directorist-app-toolkit' ),
                    $value
                );
            } elseif ( ! in_array( 'administrator', (array) $user->roles, true ) ) {
                $error = sprintf(
                    /* translators: %s: configured WordPress username */
                    __( 'The configured user “%s” does not have the administrator role.', 'directorist-app-toolkit' ),
                    $value
                );
            }
        }

        return [
            'constant' => self::USERNAME_CONSTANT,
            'defined'  => $defined,
            'valid'    => '' === $error,
            'value'    => is_scalar( $value ) ? (string) $value : '',
            'error'    => $error,
            'user'     => $user,
        ];
    }
}
