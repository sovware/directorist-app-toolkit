<?php
/**
 * Rest Listings Controller
 *
 * @package Directorist\Rest_Api
 * @version  1.0.0
 */

namespace DirectoristAppToolkit\Controller\Rest_API\Version_1\Admin_Settings;

defined( 'ABSPATH' ) || exit;

use \WP_REST_Server;

/**
 * Admin Settings class.
 */
class Admin_Settings {

	protected $namespace = 'directorist-dev-kit/v1';
	protected $rest_base = 'admin-settings';

    public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes'] );
    }

	/**
	 * Register the routes
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace, '/'. $this->rest_base,
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_settings' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => [],
			),
		);
	}

	/**
	 * Get items permissions check
	 */
	public function get_items_permissions_check() {
		return true;
	}

	/**
	 * Get Settings
	 */
	public function get_settings() {
		$settings = get_option('atbdp_option');

		if ( is_array( $settings ) ) {
			return $settings;
		}
	}
}
