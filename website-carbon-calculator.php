<?php
/**
 * Plugin Name: Website Carbon Calculator
 * Description: Effortlessly calculate the CO₂ impact of any page on your website directly from your WordPress admin panel.
 * Version: 1.2.0
 * Author: Akhela
 * License: GPLv3 or later
 * Text Domain : website-carbon-calculator
 * Author URI: https://www.akhela.fr
 * Plugin URI: https://github.com/akhela-studio/wp-carbon-calculator
 */

if ( ! defined( 'ABSPATH' ) )
    exit; // Exit if accessed directly

define('WCC_VERSION', '1.2.0');

if( !defined('WCC_DEBUG') )
    define('WCC_DEBUG', true);

class WCC{

    public  $version = WCC_VERSION;
    private $plugin_dir_path;
    private $plugin_dir_url;
    private $options;

    public function __construct() {

        include 'includes/settings.php';
        include 'includes/tools.php';
        include 'includes/actions.php';

        // Initialize Variables
        $this->plugin_dir_path = plugin_dir_path( __FILE__ );
        $this->plugin_dir_url  = plugin_dir_url( __FILE__ );
        $this->options  = get_option('carbon_calculator');

        add_action( 'admin_head', [$this, 'admin_head'] );
    }

    /**
     * add Custom css
     */
    public function admin_head()
    {
        wp_enqueue_script(
            'website-carbon-calculator',
            $this->plugin_dir_url . 'public/script.js', array( 'jquery' ),
            WCC_VERSION,
            true
        );

        wp_enqueue_style(
            'website-carbon-calculator',
            $this->plugin_dir_url . 'public/style.css', [],
            WCC_VERSION,
            false
        );

        wp_localize_script(
            'website-carbon-calculator',
            'website_carbon_calculator',
            [ "ajax_url" => admin_url( 'admin-ajax.php' ), 'reference'=>$this->options['reference']??0 ]
        );
    }
}

function get_calculated_carbon(){

    global $wp_query;
    $queried_object = get_queried_object();

    if( $queried_object instanceof WP_Term )
        return get_term_meta($queried_object->term_id, 'calculated_carbon', true);
    elseif ( $queried_object instanceof WP_Post )
        return get_post_meta($queried_object->ID, 'calculated_carbon', true);
    elseif ( $queried_object instanceof WP_Post_Type )
        return get_option($queried_object->name . '::calculated_carbon');
    elseif ( is_search() )
        return get_option('search::calculated_carbon');
    elseif ( is_404() || ($wp_query->query['name']??'') == '404' )
        return get_option('404::calculated_carbon');

    return false;
}

function wcc() {

    global $wcc;

    // Instantiate only once.
    if ( ! isset( $wcc ) )
        $wcc = new WCC();

    return $wcc;
}

if( ( defined('WP_INSTALLING') && WP_INSTALLING ) || !defined('WPINC') )
    return;

// Instantiate.
wcc();
