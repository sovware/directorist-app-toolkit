<?php
/**
 * Rest Listings Controller
 *
 * @package DirectoristAppToolkit\Controller\Rest_API\Version_1\Users\User_Rest_Base
 * @version  1.0.0
 */

namespace DirectoristAppToolkit\Controller\Rest_API\Version_1\Users;

defined( 'ABSPATH' ) || exit;

use DirectoristAppToolkit\Controller\Rest_API\Version_1\Helper\Rest_Base;

/**
 * User Rest Base class.
 */
abstract class User_Rest_Base extends Rest_Base {

	protected $rest_base = 'users';

}
