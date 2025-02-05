<?php

class WPCC_Migration{

    public function __construct() {

        add_action( 'admin_notices', [$this, 'admin_notices'] );

        if( is_admin() ){

            if( sanitize_text_field(wp_unslash($_GET['page']??'')) == 'carbon-calculator-options' && sanitize_text_field(wp_unslash($_GET['migrate']??'')) == 'v1' && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['nonce']??'')), 'migrate') )
                $this->migrateFromV1();
        }
    }

    /**
     * Register and add settings
     */
    public function admin_notices()
    {
        $options_v1 = get_option('carbon_calculator');
        $options_v2 = get_option('wpcc_settings');

        if( $options_v1 && !$options_v2 )
            echo '<div class="notice notice-warning is-dismissible"><p>Website Carbon Calulator : please migrate your settings from <a href="'.esc_url(admin_url('options-general.php?page=carbon-calculator-options&migrate=v1&nonce'.wp_create_nonce('migrate'))).'">v1</a></p></div>';
    }

    public function migrateFromV1() {

        $options_v1 = get_option('carbon_calculator');
        $options_v2 = get_option('wpcc_settings');

        if( $options_v1 && !$options_v2 ){

            update_option('wpcc_settings', $options_v1);
            delete_option('carbon_calculator');

            global $wpdb;
            $wpdb->update($wpdb->postmeta, ['meta_key'=>'wpcc_details'], ['meta_key'=>'calculated_carbon_details']);
            $wpdb->update($wpdb->postmeta, ['meta_key'=>'wpcc'], ['meta_key'=>'calculated_carbon']);

            $wpdb->update($wpdb->termmeta, ['meta_key'=>'wpcc_details'], ['meta_key'=>'calculated_carbon_details']);
            $wpdb->update($wpdb->termmeta, ['meta_key'=>'wpcc'], ['meta_key'=>'calculated_carbon']);
        }
    }
}
