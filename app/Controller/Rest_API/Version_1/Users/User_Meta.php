<?php
/**
 * Rest Listings Controller
 *
 * @package Directorist\Rest_Api
 * @version  1.0.0
 */

namespace DirectoristAppToolkit\Controller\Rest_API\Version_1\Users;

defined( 'ABSPATH' ) || exit;

use \WP_REST_Server;

/**
 * Admin Settings class.
 */
class User_Meta extends User_Rest_Base {

	/**
	 * Register the routes
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace, '/'. $this->rest_base . '/(?P<id>[\d]+)/metas', [
				'args' => [
					'id' => [
						'description' => __( 'Unique identifier for the resource.', 'directorist-app-toolkit' ),
						'type'        => 'integer',
					],
				],
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_items' ],
					// 'permission_callback' => [ $this, 'get_items_permissions_check' ],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_item' ],
					// 'permission_callback' => [ $this, 'get_items_permissions_check' ],
				],
			],
		);
	}

	/**
	 * Get user metas
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$status = [];
		$status['success'] = false;
		$status['message'] = '';

		$user_id = ( isset( $request['id'] ) ) ? $request['id'] : 0;
		$meta_key = ( isset( $request['meta_key'] ) ) ? $request['meta_key'] : '';

		$user = get_userdata( $user_id );

		if ( false == $user ) {
			$status['message'] = 'No user found';
			return $status;
		}

		$user_metas = get_user_meta( $user_id, $meta_key, true );

		return $user_metas;
	}


	/**
	 * Update user meta
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_item( $request ) {
		$status = [];
		$status['success'] = false;
		$status['message'] = '';

		$user_id = ( isset( $request['id'] ) ) ? $request['id'] : 0;
		$user_metas = ( isset( $request['metas'] ) && is_array( $request['metas'] ) ) ? $request['metas'] : [];

		$user = get_userdata( $user_id );

		if ( false == $user ) {
			$status['message'] = 'No user found';
			return $status;
		}

		if ( empty( $user_metas ) ) {
			$status['message'] = 'Nothing to update';
			return $status;
		}

		foreach ( $user_metas as $key => $value ) {
			update_user_meta(  $user_id, $key, $value );
		}

		$updated_user_metas = get_user_meta( $user_id );

		return $updated_user_metas;
	}
}
