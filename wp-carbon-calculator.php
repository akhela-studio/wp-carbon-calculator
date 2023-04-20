<?php
/**
 * Plugin Name: Website carbon calculator
 * Description: Estimate your web page carbon footprint
 * Version: 1.0.0
 * Author: Akhela
 * Author URI: http://www.akhela.fr
 */

if ( ! defined( 'ABSPATH' ) )
    exit; // Exit if accessed directly

require __DIR__ . '/vendor/autoload.php';

define('WCC_VERSION', '1.1.0');

if( !defined('WCC_DEBUG') )
    define('WCC_DEBUG', false);

class WCC{

    public  $version = WCC_VERSION;
    private $plugin_dir_path;
    private $plugin_dir_url;
    private $options;

    public function __construct() {

        include 'includes/settings.php';
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
            'wp-carbon-calculator',
            $this->plugin_dir_url . 'public/script.js', array( 'jquery' ),
            WCC_VERSION,
            true
        );

        wp_enqueue_style(
            'wp-carbon-calculator',
            $this->plugin_dir_url . 'public/style.css', [],
            WCC_VERSION,
            false
        );

        wp_localize_script(
            'wp-carbon-calculator',
            'wp_carbon_calculator',
            [ "ajax_url" => admin_url( 'admin-ajax.php' ), 'reference'=>$this->options['reference']??0 ]
        );
    }
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
