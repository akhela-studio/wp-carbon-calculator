<?php

class WPCC_MAIN{

    public  $version = WPCC_VERSION;
    private $plugin_dir_path;
    private $plugin_dir_url;
    private $options;

    public function __construct() {

        $this->loadAll();

        // Initialize Variables
        $this->plugin_dir_path = plugin_dir_path( WPCC_FILE );
        $this->plugin_dir_url  = plugin_dir_url( WPCC_FILE );
        $this->options  = get_option('wpcc_settings');

        add_action( 'admin_head', [$this, 'admin_head'] );
    }

    public function loadAll(){

        include WPCC_DIR.'/includes/settings.php';
        new WPCC_Settings();

        include WPCC_DIR.'/includes/tools.php';
        new WPCC_Tools();

        include WPCC_DIR.'/includes/actions.php';
        new WPCC_Actions();

        include WPCC_DIR.'/includes/dashboard.php';
        new WPCC_Dashboard();

        include WPCC_DIR.'/includes/migration.php';
        new WPCC_Migration();

        include WPCC_DIR.'/includes/helper.php';
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
