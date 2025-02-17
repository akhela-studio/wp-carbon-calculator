<?php

class WPCC_Migration{

    public function __construct() {

        add_action( 'admin_notices', [$this, 'admin_notices'] );

        add_action('admin_init', function (){

            $page = sanitize_text_field(wp_unslash($_GET['page']??''));
            $nonce = sanitize_text_field(wp_unslash($_GET['nonce']??''));
            $migrate_from = sanitize_text_field(wp_unslash($_GET['migrate']??''));

            if( $page == 'carbon-calculator-options' && isset($_GET['migrate']) && wp_verify_nonce($nonce, 'migrate') ){

                if( $migrate_from == '1.0' )
                    $this->migrateFromV1();

                wp_redirect(admin_url('options-general.php?page=carbon-calculator-options'));
                exit;
            }
        });
    }

    /**
     * Register and add settings
     */
    public function admin_notices()
    {
        $options_v1 = get_option('carbon_calculator');
        $options_v2 = get_option('wpcc_settings');

        if( $options_v1 && !$options_v2 ){

            $nonce = wp_create_nonce('migrate');
            echo '<div class="notice notice-warning is-dismissible"><p><b>Website Carbon Calulator</b> : <a href="'.esc_url(admin_url('options-general.php?page=carbon-calculator-options&migrate=1.0&nonce='.esc_attr($nonce))).'">please migrate your settings.</a></p></div>';
        }
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
