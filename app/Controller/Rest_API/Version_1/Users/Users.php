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
class Users {

	protected $namespace = 'directorist-dev-kit/v1';
	protected $rest_base = 'user';

    public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes'] );
    }

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
					'callback'            => [ $this, 'get_user_meta' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'update_user_meta' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
				],
			],
		);
	}

	/**
	 * Get items permissions check
	 */
	public function get_items_permissions_check() {
		return true;
	}

	/**
	 * Get User Meta
	 */
	public function get_user_meta( $request ) {
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
	 * Update User Meta
	 */
	public function update_user_meta( $request ) {
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
