<?php

namespace DirectoristAppToolkit\Controller\Rest_API;

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
            Version_1\Init::class,
        ];
    }
}
