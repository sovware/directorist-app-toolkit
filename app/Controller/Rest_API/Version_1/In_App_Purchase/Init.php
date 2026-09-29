<?php

namespace DirectoristAppToolkit\Controller\Rest_API\Version_1\In_App_Purchase;

use DirectoristAppToolkit\Helper\Traits as HelperTraits;

defined( 'ABSPATH' ) || exit;

class Init {
    use HelperTraits\Rest_Route_Registrar;

    public function __construct() {
        $this->register_rest_routes( [ In_App_Purchase::class ] );
    }
}
