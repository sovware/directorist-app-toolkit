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
			$user         = $this->validate_token( $token );        // Validate token and get user
			$redirect_url = home_url( '/' );

			if ( $user ) {
				$checkout_page_id = (int) get_directorist_option( 'checkout_page' );
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
		// Set your secret key (must be the same as used in Flutter)
		$secret_key = defined('JWT_AUTH_SECRET_KEY') ? JWT_AUTH_SECRET_KEY : false;
	
		try {
			// Decode the JWT token
			$decoded = JWT::decode( $token, $secret_key, array( 'HS256' ) );

			// Extract user data from the token
			$user_id = $decoded->data->user->id;
	
			// Get the user by ID
			$user = get_user_by( 'ID', $user_id );
	
			if ( $user ) {
				// Authentication successful
				return $user;
			} else {
				// User not found
				return false;
			}
		} catch (\Exception $e) {
			// Token is invalid
			return false;
		}
	}
}
