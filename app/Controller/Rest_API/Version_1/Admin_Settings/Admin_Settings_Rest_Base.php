<?php
/**
 * Rest Admin Settings Base Controller
 *
 * @package DirectoristAppToolkit\Controller\Rest_API\Version_1
 * @version  1.0.0
 */

namespace DirectoristAppToolkit\Controller\Rest_API\Version_1\Admin_Settings;

defined( 'ABSPATH' ) || exit;

use DirectoristAppToolkit\Controller\Rest_API\Version_1\Helper\Rest_Base;

/**
 * Admin Settings Rest Base class.
 */
abstract class Admin_Settings_Rest_Base extends Rest_Base {

	protected $rest_base = 'admin-settings';

}
