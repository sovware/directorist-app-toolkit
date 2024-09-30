<?php

namespace DirectoristAppToolkit\Controller\iFrame_Login;

use \Firebase\JWT\JWT;

class LoginController {

	public function __construct() {
		add_action( 'init', array( $this, 'authenticate' ) );
	}

	public function authenticate() {
		if ( isset( $_GET['_dapp_token'] ) ) {
			$token        = sanitize_text_field( $_GET['_dapp_token'] );
			$user         = $this->validate_token( $token );
			$redirect_url = home_url( '/' );

			if ( $user ) {
				$checkout_page_id  = (int) get_directorist_option( 'checkout_page' );
				$dashboard_page_id = (int) get_directorist_option( 'user_dashboard' );

				if ( $checkout_page_id && ( $url = get_the_permalink( $checkout_page_id ) ) ) {
					$redirect_url = $url;
				} elseif ( $dashboard_page_id && ( $url = get_the_permalink( $dashboard_page_id ) ) ) {
					$redirect_url = $url;
				}

				wp_set_auth_cookie( $user->ID, true );
				wp_safe_redirect( $redirect_url );

				exit;
			} else {
				wp_safe_redirect( $redirect_url );

				exit;
			}
		}
	}

	protected function validate_token( $token ) {
		$secret_key = defined( 'JWT_AUTH_SECRET_KEY' ) ? JWT_AUTH_SECRET_KEY : false;
	
		try {
			$decoded = JWT::decode( $token, $secret_key, array( 'HS256' ) );
			$user_id = $decoded->data->user->id;
			$user    = get_user_by( 'ID', $user_id );
	
			if ( $user ) {
				return $user;
			} else {
				return false;
			}
		} catch ( \Exception $e ) {
			return false;
		}
	}
}
