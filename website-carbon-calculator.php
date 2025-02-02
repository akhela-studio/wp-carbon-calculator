<?php
/**
 * Plugin Name: Website Carbon Calculator
 * Description: Effortlessly calculate the CO₂ impact of any page on your website directly from your WordPress admin panel.
 * Version: 1.2.2
 * Author: Akhela
 * License: GPLv3 or later
 * Text Domain : website-carbon-calculator
 * Author URI: https://www.akhela.fr
 * Plugin URI: https://github.com/akhela-studio/wp-carbon-calculator
 */

if ( ! defined( 'ABSPATH' ) )
    exit; // Exit if accessed directly

define('WPCC_VERSION', "1.2.2");

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

    function get_calculated_carbon()
    {
        return WPCC_MAIN::getCalculatedCarbon();
    }
}

// Instantiate.
new WPCC_MAIN();
