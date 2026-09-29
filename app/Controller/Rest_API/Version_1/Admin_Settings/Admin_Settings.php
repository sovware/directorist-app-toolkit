<?php
  /**
 * Rest Admin Settings Controller
 *
 * @package DirectoristAppToolkit\Controller\Rest_API\Version_1
 * @version  2.0.0
 */

namespace DirectoristAppToolkit\Controller\Rest_API\Version_1\Admin_Settings;

use DirectoristAppToolkit\Controller\Rest_API\Version_1\Helper\Rest_Base;
use DirectoristAppToolkit\Controller\Licensing\License_Manager;
use DirectoristAppToolkit\Helper\App_Settings as Settings_Helper;

defined( 'ABSPATH' ) || exit;

use \WP_REST_Server;

  /**
 * Admin Settings class.
 */
class Admin_Settings extends Rest_Base {

	protected $rest_base = 'admin-settings';

	protected $read_only_settings = [
		'has_active_license',
		'payment_currency_symbol',
		'listing_currency_symbol',
		'pricing_plan_type',
	];

	protected $legacy_settings = [
		'enable_multi_directory'        => null,
		'radius_search_unit'            => null,
		'admin_email_lists'             => null,
		'privacy_policy'                => null,
		'terms_conditions'              => null,
		'skip_plan_page'                => null,
		'plan_direct_purchase'          => null,
		'payment_currency'              => null,
		'payment_thousand_separator'    => null,
		'payment_decimal_separator'     => null,
		'payment_currency_position'     => null,
		'payment_currency_symbol'       => null,
		'g_currency'                    => 'listing_currency',
		'g_currency_position'           => 'listing_currency_position',
		'listing_currency_symbol'       => null,
	];

	/**
	 * Get all settings that should be returned by the admin settings API.
	 *
	 * This keeps the API aligned with the admin settings schema automatically.
	 *
	 * @return array
	 */
	protected function get_available_settings() {
		$settings = [];

		foreach ( Settings_Helper::get_tabs() as $tab ) {
			if ( empty( $tab['fields'] ) || ! is_array( $tab['fields'] ) ) {
				continue;
			}

			foreach ( $tab['fields'] as $field_key => $field ) {
				if ( Settings_Helper::is_section_field( $field ) ) {
					continue;
				}

				$settings[ $field_key ] = null;
			}
		}

		return array_merge( $settings, $this->legacy_settings );
	}

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
					'permission_callback' => '__return_true',
					'args' => [],
				],
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update_items' ],
					'permission_callback' => [ $this, 'update_items_permissions_check' ],
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
		return rest_ensure_response( $this->prepare_settings_response() );
	}

	/**
	 * Check whether the current user may update App Toolkit settings.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|\WP_Error
	 */
	public function update_items_permissions_check( $request ) {
		$capability = (string) apply_filters( 'directorist_app_toolkit_settings_capability', 'manage_options' );

		if ( current_user_can( $capability ) ) {
			return true;
		}

		return new \WP_Error(
			'directorist_app_toolkit_rest_cannot_update_settings',
			__( 'Sorry, you are not allowed to update app settings.', 'directorist-app-toolkit' ),
			[ 'status' => rest_authorization_required_code() ]
		);
	}

	/**
	 * Update a partial flat map of settings.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function update_items( $request ) {
		$payload = $request->get_json_params();

		if ( ! is_array( $payload ) ) {
			$payload = $request->get_body_params();
		}

		if ( ! is_array( $payload ) || empty( $payload ) ) {
			return new \WP_Error(
				'directorist_app_toolkit_rest_settings_payload_invalid',
				__( 'A non-empty settings object is required.', 'directorist-app-toolkit' ),
				[ 'status' => 400 ]
			);
		}

		$writable_settings = $this->get_writable_settings();
		$invalid_keys      = array_values( array_diff( array_keys( $payload ), array_keys( $writable_settings ) ) );

		if ( ! empty( $invalid_keys ) ) {
			return new \WP_Error(
				'directorist_app_toolkit_rest_settings_keys_invalid',
				__( 'One or more settings are unknown or read-only.', 'directorist-app-toolkit' ),
				[
					'status' => 400,
					'keys'   => $invalid_keys,
				]
			);
		}

		$app_options       = [];
		$legacy_options    = get_option( 'atbdp_option', [] );
		$has_legacy_update = false;
		$legacy_options    = is_array( $legacy_options ) ? $legacy_options : [];

		foreach ( Settings_Helper::get_tabs() as $tab_key => $tab ) {
			$tab_values = Settings_Helper::get_tab_values( $tab_key );
			$has_update = false;

			foreach ( $tab['fields'] as $field_key => $field ) {
				if ( ! array_key_exists( $field_key, $payload ) || Settings_Helper::is_section_field( $field ) ) {
					continue;
				}

				$value = $payload[ $field_key ];

				if ( isset( $field['type'] ) && 'json' === $field['type'] && ( is_array( $value ) || is_object( $value ) ) ) {
					$value = wp_json_encode( $value );
				}

				$tab_values[ $field_key ] = $value;
				$has_update               = true;
			}

			if ( ! $has_update ) {
				continue;
			}

			$errors = Settings_Helper::validate_tab_values( $tab_key, $tab_values );

			if ( ! empty( $errors ) ) {
				return new \WP_Error(
					'directorist_app_toolkit_rest_settings_validation_failed',
					reset( $errors ),
					[
						'status' => 400,
						'errors' => $errors,
					]
				);
			}

			$app_options[ $tab['option_key'] ] = Settings_Helper::sanitize_tab_values( $tab_key, $tab_values );
		}

		foreach ( $writable_settings as $rest_key => $setting ) {
			if ( 'legacy' !== $setting['source'] || ! array_key_exists( $rest_key, $payload ) ) {
				continue;
			}

			$legacy_options[ $setting['storage_key'] ] = $this->sanitize_legacy_value( $payload[ $rest_key ] );
			$has_legacy_update                         = true;
		}

		foreach ( $app_options as $option_key => $values ) {
			update_option( $option_key, $values, false );
		}

		if ( $has_legacy_update ) {
			update_option( 'atbdp_option', $legacy_options );
		}

		return rest_ensure_response( $this->prepare_settings_response() );
	}

	/**
	 * Prepare the complete public settings response.
	 *
	 * @return array
	 */
	protected function prepare_settings_response() {
		$_raw_settings = get_option('atbdp_option');
		$settings      = [];

		if ( ! is_array( $_raw_settings ) ) {
			$_raw_settings = [];
		}

		foreach ( $this->get_available_settings() as $setting_key => $rest_key ) {
			$rest_key = is_null( $rest_key ) ? $setting_key : $rest_key;

			if ( Settings_Helper::has_field( $setting_key ) ) {
				$settings[ $rest_key ] = Settings_Helper::get_rest_setting( $setting_key );
			} elseif ( isset( $_raw_settings[ $setting_key ] ) ) {
				$settings[ $rest_key ] = $_raw_settings[ $setting_key ];
			} else {
				$settings[ $rest_key ] = null;
			}
		}

		if ( ! empty( $settings['payment_currency'] ) && function_exists( 'atbdp_currency_symbol' ) ) {
			$settings['payment_currency_symbol'] = html_entity_decode( atbdp_currency_symbol( $settings['payment_currency'] ) );
		}

		if ( ! empty( $settings['listing_currency'] ) && function_exists( 'atbdp_currency_symbol' ) ) {
			$settings['listing_currency_symbol'] = html_entity_decode( atbdp_currency_symbol( $settings['listing_currency'] ) );
		}

		$settings['has_active_license'] = License_Manager::has_active_license();
		$settings['pricing_plan_type']  = $this->get_pricing_plan_type();

		return $settings;
	}

	/**
	 * Get the active pricing-plan provider used by the app.
	 *
	 * @return string
	 */
	protected function get_pricing_plan_type() {
		if ( class_exists( 'DWPP_Pricing_Plans' ) ) {
			return 'woocommerce';
		}

		if ( class_exists( 'DirectoristPricingPlan' ) || class_exists( 'ATBDP_Pricing_Plans' ) ) {
			return 'directorist';
		}

		return 'none';
	}

	/**
	 * Build the flat REST key map for writable settings.
	 *
	 * @return array
	 */
	protected function get_writable_settings() {
		$settings = [];

		foreach ( Settings_Helper::get_tabs() as $tab ) {
			foreach ( $tab['fields'] as $field_key => $field ) {
				if ( Settings_Helper::is_section_field( $field ) ) {
					continue;
				}

				$settings[ $field_key ] = [
					'source'      => 'app',
					'storage_key' => $field_key,
				];
			}
		}

		foreach ( $this->legacy_settings as $storage_key => $rest_key ) {
			$rest_key = is_null( $rest_key ) ? $storage_key : $rest_key;

			if ( in_array( $rest_key, $this->read_only_settings, true ) ) {
				continue;
			}

			$settings[ $rest_key ] = [
				'source'      => 'legacy',
				'storage_key' => $storage_key,
			];
		}

		return $settings;
	}

	/**
	 * Sanitize legacy Directorist values without changing scalar types.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return mixed
	 */
	protected function sanitize_legacy_value( $value ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $key => $item ) {
				$value[ $key ] = $this->sanitize_legacy_value( $item );
			}

			return $value;
		}

		if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) || null === $value ) {
			return $value;
		}

		return sanitize_text_field( (string) $value );
	}
}
