<?php
/**
 * Initialize Users Routes
 *
 * @package DirectoristAppToolkit\Controller\Rest_API\Version_1
 * @version  1.0.0
 */

namespace DirectoristAppToolkit\Controller\Rest_API\Version_1\Users;

defined( 'ABSPATH' ) || exit;

use DirectoristAppToolkit\Helper\Traits as HelperTraits;


class Init {

    use HelperTraits\Rest_Route_Registrar;

    public function __construct() {
        $this->register_rest_routes( $this->get_controllers() );
    }

    /**
     * Get the controllers
     * 
     * @return array a list of controllers
     * 
     */
    public static function get_controllers() {
        return [
            User_Meta::class,
        ];
    }
}
