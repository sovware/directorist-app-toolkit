<?php
  /**
 * Rest Admin Settings Controller
 *
 * @package DirectoristAppToolkit\Controller\Rest_API\Version_1
 * @version  1.0.0
 */

namespace DirectoristAppToolkit\Controller\Rest_API\Version_1\Admin_Settings;

use DirectoristAppToolkit\Controller\Rest_API\Version_1\Helper\Rest_Base;

defined( 'ABSPATH' ) || exit;

use \WP_REST_Server;

  /**
 * Admin Settings class.
 */
class Admin_Settings extends Rest_Base {

	protected $rest_base = 'admin-settings';

	protected $publishable_setting_keys = [
		'enable_multi_directory',
	];

	  /**
	 * Register the routes
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace, '/'. $this->rest_base, 
			[
				[
					'methods'  => WP_REST_Server::READABLE,
					'callback' => [ $this, 'get_items' ],
					  // 'permission_callback' => [ $this, 'get_items_permissions_check' ],
					'args' => [],
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
		$_raw_settings = get_option('atbdp_option');
		$settings      = [];

		if ( ! is_array( $_raw_settings ) ) {
			return rest_ensure_response( $settings );
		}

		foreach ( $this->publishable_setting_keys as $setting_key ) {
			if ( isset( $_raw_settings[ $setting_key ] ) ) {
				$settings[ $setting_key ] = $_raw_settings[ $setting_key ];
			}
		}

		return rest_ensure_response( $settings );
	}
}
