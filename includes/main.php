<?php

class WPCC_MAIN{

    public  $version = WPCC_VERSION;
    private $plugin_dir_path;
    private $plugin_dir_url;
    private $options;

    public function __construct() {

        include WPCC_DIR.'/includes/settings.php';
        new WPCC_Settings();

        include WPCC_DIR.'/includes/tools.php';
        new WPCC_Tools();

        include WPCC_DIR.'/includes/actions.php';
        new WPCC_Actions();

        // Initialize Variables
        $this->plugin_dir_path = plugin_dir_path( WPCC_FILE );
        $this->plugin_dir_url  = plugin_dir_url( WPCC_FILE );
        $this->options  = get_option('wpcc_settings');

        add_action( 'admin_head', [$this, 'admin_head'] );
    }

    public static function getCalculatedCarbon(){

        global $wp_query;

        $queried_object = get_queried_object();

        if( $queried_object instanceof WP_Term )
            return get_term_meta($queried_object->term_id, 'wpcc', true);
        elseif ( $queried_object instanceof WP_Post )
            return get_post_meta($queried_object->ID, 'wpcc', true);
        elseif ( $queried_object instanceof WP_Post_Type )
            return get_option($queried_object->name . '::wpcc');
        elseif ( is_search() )
            return get_option('wpcc::search');
        elseif ( is_404() || ($wp_query->query['name']??'') == '404' )
            return get_option('wpcc::404');

        return false;
    }

    /**
     * add Custom css
     */
    public function admin_head()
    {
        wp_enqueue_script(
            'website-carbon-calculator',
            $this->plugin_dir_url . 'public/script.js', array( 'jquery' ),
            WPCC_VERSION,
            true
        );

        wp_enqueue_style(
            'website-carbon-calculator',
            $this->plugin_dir_url . 'public/style.css', [],
            WPCC_VERSION,
            false
        );

        wp_localize_script(
            'website-carbon-calculator',
            'website_carbon_calculator',
            [ "ajax_url" => admin_url( 'admin-ajax.php' ), 'reference'=>$this->options['reference']??0 ]
        );
    }
}
