<?php

namespace DirectoristAppToolkit\Helper\Traits;

trait Service_Registrar {

/**
 * Register Serivces
 * 
 * @param array $services
 * @return void
 */
private function register_serivces( $services = [] ) {

    if (  ! is_array( $services ) ) {
        return;
    }

    foreach ( $services as $service ) {
        if ( ! class_exists( $service ) ) {
            continue;
        }

        new $service();
    }
}
}