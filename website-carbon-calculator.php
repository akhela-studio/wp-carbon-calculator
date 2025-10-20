<?php
/**
 * Plugin Name: Website Carbon Calculator
 * Description: Effortlessly calculate the CO₂ impact and performance of any page on your website directly from your WordPress admin panel.
 * Version: 1.3.7
 * Author: Sustainable Web Dev
 * License: GPLv3 or later
 * Text Domain: website-carbon-calculator
 * Author URI: https://sustainablewebdev.org
 * Plugin URI: https://github.com/sustainablewebdev/wp-carbon-calculator
 */

if ( ! defined( 'ABSPATH' ) )
    exit; // Exit if accessed directly

define('WPCC_VERSION', "1.3.7");

if( !defined('WPCC_DEBUG') )
    define('WPCC_DEBUG', false);

if ( ! defined( 'WPCC_FILE' ) )
    define( 'WPCC_FILE', __FILE__ );

if ( ! defined( 'WPCC_DIR' ) )
    define( 'WPCC_DIR', dirname(__FILE__) );


include WPCC_DIR.'/includes/main.php';

/**
 * Get calculated carbon based on $wp_query
 * @return false|mixed|null
 */
if( !function_exists('get_calculated_carbon') ){

    function get_calculated_carbon() { return WPCC_Helper::get_calculated_carbon(); }
}

// Instantiate.
add_action('plugins_loaded', function () {
    new WPCC_MAIN();
});
