<?php

namespace DirectoristAppToolkit\Helper\Traits;

trait Rest_Route_Registrar {

/**
 * Register Rest Routes
 * 
 * @param array $controllers
 * @return void
 */
private function register_rest_routes( $controllers = [] ) {

    if (  ! is_array( $controllers ) ) {
        return;
    }

    foreach ( $controllers as $controller ) {
        if ( ! class_exists( $controller ) ) {
            continue;
        }

        $controller_class = new $controller();

        if ( method_exists( $controller_class, 'register_routes' ) ) {
            // add_action( 'rest_api_init', [ $controller_class, 'register_routes'] );
        }
    }
}
}