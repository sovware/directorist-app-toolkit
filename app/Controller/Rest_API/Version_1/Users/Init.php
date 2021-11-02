<?php

namespace DirectoristAppToolkit\Controller\Rest_API\Version_1\Users;

defined( 'ABSPATH' ) || exit;

use DirectoristAppToolkit\Helper\Traits as HelperTraits;


class Init {

    use HelperTraits\Service_Registrar;

    public function __construct() {
        $this->register_serivces( $this->get_controllers() );
    }

    /**
     * Get the controllers
     * 
     * @return array a list of controllers
     * 
     */
    public static function get_controllers() {
        return [
            Users::class,
        ];
    }
}
