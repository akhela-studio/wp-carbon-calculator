<?php

class WPCC_Settings{

    private $options;

    public function __construct() {

        $this->options = get_option('wpcc_settings');

        add_action( 'admin_menu', [$this, 'admin_menu'] );
        add_action( 'admin_init', [$this, 'admin_init'] );
    }

    /**
     * Register and add settings
     */
    public function admin_notices()
    {
        if( in_array($_SERVER['REMOTE_ADDR']??'', ['127.0.0.1', '::1']) )
            echo '<div class="notice notice-warning is-dismissible"><p>Calculator is not available on localhost</p></div>';
    }

    /**
     * Register and add settings
     */
    public function admin_init()
    {
        global $plugin_page, $pagenow;

        if( $plugin_page !== 'carbon-calculator-options' && $pagenow != 'options.php' )
            return;

        add_action( 'admin_notices', [$this, 'admin_notices'] );

        register_setting(
            'wpcc_settings', // Option group
            'wpcc_settings', // Option name
            [$this, 'sanitize'] // Sanitize
        );

        $options = get_option('wpcc_settings');
        $options = is_array($options)?$options:[];

        $options = array_merge(['is_green_host'=>false, 'post_types'=>[], 'taxonomies'=>[], 'pagespeed_api_key'=>'', 'reference'=>0.55], $options);

        add_settings_section( 'wpcc_settings_section', 'Settings', function() use($options){

            $post_types = get_post_types( ['public'=>true], 'objects');
            unset($post_types['attachment']);

            $taxonomies = get_taxonomies( ['public'=>true], 'objects');
            ?>
            <table class="form-table" role="presentation">
                <tbody>
                <tr>
                    <th scope="row">Enabled post types</th>
                    <td>
                        <label>
                            <select name="wpcc_settings[post_types][]" multiple style="min-width: 250px">
                                <?php foreach ($post_types as $post_type):?>
                                    <option value="<?php echo esc_attr($post_type->name); ?>" <?php echo esc_attr(in_array($post_type->name, $options['post_types'])?'selected':''); ?>><?php echo esc_html($post_type->label); ?></option>
                                <?php endforeach;?>
                            </select>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Enabled taxonomies</th>
                    <td>
                        <label>
                            <select name="wpcc_settings[taxonomies][]" multiple style="min-width: 250px">
                                <?php foreach ($taxonomies as $taxonomy):?>
                                    <option value="<?php echo esc_attr($taxonomy->name); ?>" <?php echo esc_attr(in_array($taxonomy->name, $options['taxonomies'])?'selected':''); ?>><?php echo esc_html($taxonomy->label); ?></option>
                                <?php endforeach;?>
                            </select>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Green hosting</th>
                    <td>
                        <input type="checkbox" name="wpcc_settings[is_green_host]" value="1" <?php echo esc_attr($options['is_green_host']?'checked':''); ?>>
                        <label for="wpcc_settings[is_green_host]">My host is green</label>
                        <p><em>
                                Please use <a href="https://www.thegreenwebfoundation.org/green-web-check/" target="_blank">thegreenwebfoundation.org</a> if you are not sure
                            </em>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Google Pagespeed Key</th>
                    <td>
                        <input type="text" name="wpcc_settings[pagespeed_api_key]" value="<?php echo esc_attr($options['pagespeed_api_key']); ?>">
                        <p><em>
                                Please read <a href="https://developers.google.com/speed/docs/insights/v5/get-started" target="_blank">Google documentation</a> to generate an API key
                            </em>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Emission reference</th>
                    <td>
                        <input type="number" step="0.01" name="wpcc_settings[reference]" value="<?php echo esc_attr($options['reference']); ?>"> g eq. CO²
                        <p><em>
                                Average website page emission
                            </em>
                        </p>
                    </td>
                </tr>
                </tbody>
            </table>
            <?php
        },'wpcc_settings-admin');
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(__('Carbon calculator', 'website-carbon-calculator')); ?></h1>
            <form method="post" action="options.php">
                <?php
                // This prints out all hidden setting fields
                settings_fields( 'wpcc_settings' );
                do_settings_sections( 'wpcc_settings-admin' );
                submit_button(__('Save', 'website-carbon-calculator'));
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Add options page
     */
    public function admin_menu()
    {
        // This page will be under "Settings"
        add_options_page(
            __('Carbon calculator settings', 'website-carbon-calculator'),
            __('Carbon calculator', 'website-carbon-calculator'),
            'manage_options',
            'carbon-calculator-options',
            [$this, 'create_admin_page']
        );
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     * @return array
     */
    public function sanitize( $input )
    {
        $new_input = array();

        $new_input['pagespeed_api_key'] = sanitize_text_field( $input['pagespeed_api_key']??'' );
        $new_input['is_green_host'] = boolval( $input['is_green_host']??false );
        $new_input['post_types'] = $input['post_types']??[];
        $new_input['taxonomies'] = $input['taxonomies']??[];
        $new_input['reference'] = floatval($input['reference']??0);

        return $new_input;
    }
}
