<?php
/**
 * Directorist App Toolkit
 *
 * @package           DirectoristAppToolkit
 * @author            wpWax
 * @copyright         2021 wpWax
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Directorist App Toolkit
 * Plugin URI:        https://github.com/syedgalib/directorist-app-toolkit
 * Description:       Manage your Directorist mobile app with this extension
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            wpWax
 * Author URI:        https://wpwax.com/
 * Text Domain:       directorist-app-toolkit
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Update URI:        https://github.com/syedgalib/directorist-app-toolkit
 */

if ( ! class_exists( 'DirectoristAppToolkit' ) ) {
    include dirname( __FILE__ ) . '/app.php';
}