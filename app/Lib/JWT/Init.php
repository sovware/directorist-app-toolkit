<?php

namespace DirectoristAppToolkit\Lib\JWT;

use DirectoristAppToolkit\Lib\JWT\Controller\Jwt_Auth;

/**
 * The plugin bootstrap file.
 *
 * Description:       Extends the WP REST API using JSON Web Tokens Authentication as an authentication method.
 * Version:           1.2.6
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}


/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */

Class Init {
    public function __construct() {
        $plugin = new Jwt_Auth();
        $plugin->run();
    }
}
