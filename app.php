<?php

include dirname( __FILE__ ) . '/vendor/autoload.php';

use DirectoristAppToolkit\Helper\Traits;
use DirectoristAppToolkit\Lib;
use DirectoristAppToolkit\Controller;

final class DirectoristAppToolkit {
    use Traits\Service_Registrar;

    public static $instance;

    /**
     * The Constructor
     * 
     * Making it private so that the class can't
     * be instantiate with new keyword
     * 
     */
    private function __construct() {
        $this->register_serivces( $this->get_controllers() );
    }

    /**
     * Get the instance of the class 
     * 
     * It insures that the class doesn't instantiate twice 
     * to maintain the Singleton pattern.
     * 
     */
    public static function get_instance() {
        if (  is_null( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Get the controllers
     * 
     * @return array a list of controllers
     * 
     */
    public static function get_controllers() {
        return [
            // Libraries
            Lib\JWT\Init::class,

            // Controllers
            Controller\Notification\Init::class,
            Controller\Rest_API\Init::class,
            Controller\Admin_Settings\Init::class,
        ];
    }

    /**
     * Throw error on object clone.
     *
     * The whole idea of the singleton design pattern is that there is a single
     * object therefore, we don't want the object to be cloned.
     *
     * @return void
     * @since 1.0
     * @access protected
     */
    public function __clone() {
        // Cloning instances of the class is forbidden.
        _doing_it_wrong( __FUNCTION__, __('Cheatin&#8217; huh?', 'directorist-app-toolkit'), '1.0' );
    }

    /**
     * Disable unserializing of the class.
     *
     * @return void
     * @since 1.0
     * @access protected
     */
    public function __wakeup() {
        // Unserializing instances of the class is forbidden.
        _doing_it_wrong( __FUNCTION__, __('Cheatin&#8217; huh?', 'directorist-app-toolkit'), '1.0' );
    }
}

function DirectoristAppToolkit() {
    return DirectoristAppToolkit::get_instance();
}

DirectoristAppToolkit();