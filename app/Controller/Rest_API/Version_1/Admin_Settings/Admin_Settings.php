<?php
/**
 * Rest Admin Settings Controller
 *
 * @package DirectoristAppToolkit\Controller\Rest_API\Version_1
 * @version  1.0.0
 */

namespace DirectoristAppToolkit\Controller\Rest_API\Version_1\Admin_Settings;

defined( 'ABSPATH' ) || exit;

use \WP_REST_Server;

/**
 * Admin Settings class.
 */
class Admin_Settings extends Admin_Settings_Rest_Base {

	/**
	 * Register the routes
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace, '/'. $this->rest_base, 
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_items' ],
					// 'permission_callback' => [ $this, 'get_items_permissions_check' ],
					'args'                => [],
				],
			]
			
		);
	}

	/**
	 * Get Admin Settings
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$settings = get_option('atbdp_option');

		if ( is_array( $settings ) ) {
			return $settings;
		}
	}
}
