<?php

namespace DirectoristAppToolkit\Controller\Licensing;

use DirectoristAppToolkit\Helper\Traits as HelperTraits;

defined( 'ABSPATH' ) || exit;

class Init {

    use HelperTraits\Service_Registrar;

    public function __construct() {
        $this->register_serivces( $this->get_controllers() );
    }

    /**
     * Get licensing services.
     *
     * @return array
     */
    public static function get_controllers() {
        return [
            License_Manager::class,
        ];
    }
}
