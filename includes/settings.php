<?php

class WCCSettings{

    private $options;

    public function __construct() {

        $this->options = get_option('carbon_calculator');

        add_action( 'admin_menu', [$this, 'admin_menu'] );
        add_action( 'admin_init', [$this, 'admin_init'] );
    }

    /**
     * Register and add settings
     */
    public function admin_notices()
    {
        if( in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']) )
            echo '<div class="notice notice-warning is-dismissible"><p>Calculator is not available on localhost</p></div>';
    }

    /**
     * Register and add settings
     */
    public function admin_init()
    {
        global $pagenow;

        if( ($_GET['page']??'') !== 'carbon-calculator-options' && $pagenow != 'options.php' )
            return;

        add_action( 'admin_notices', [$this, 'admin_notices'] );

        register_setting(
            'carbon_calculator', // Option group
            'carbon_calculator', // Option name
            [$this, 'sanitize'] // Sanitize
        );

        $options = get_option('carbon_calculator');
        $options = is_array($options)?$options:[];

        $options = array_merge(['is_green_host'=>false, 'post_types'=>[], 'taxonomies'=>[], 'pagespeed_api_key'=>'', 'reference'=>0.55], $options);

        add_settings_section( 'carbon_calculator', 'Settings', function() use($options){

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
                            <select name="carbon_calculator[post_types][]" multiple style="min-width: 250px">
                                <?php foreach ($post_types as $post_type):?>
                                    <option value="<?=$post_type->name?>" <?=in_array($post_type->name, $options['post_types'])?'selected':''?>><?=$post_type->label?></option>
                                <?php endforeach;?>
                            </select>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Enabled taxonomies</th>
                    <td>
                        <label>
                            <select name="carbon_calculator[taxonomies][]" multiple style="min-width: 250px">
                                <?php foreach ($taxonomies as $taxonomy):?>
                                    <option value="<?=$taxonomy->name?>" <?=in_array($taxonomy->name, $options['taxonomies'])?'selected':''?>><?=$taxonomy->label?></option>
                                <?php endforeach;?>
                            </select>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Green hosting</th>
                    <td>
                        <input type="checkbox" name="carbon_calculator[is_green_host]" value="1" <?=$options['is_green_host']?'checked':''?>>
                        <label for="carbon_calculator[is_green_host]">My host is green</label>
                        <p><em>
                                Please use <a href="https://www.thegreenwebfoundation.org/green-web-check/" target="_blank">thegreenwebfoundation.org</a> if you are not sure
                            </em>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Google Pagespeed Key</th>
                    <td>
                        <input type="text" name="carbon_calculator[pagespeed_api_key]" value="<?=$options['pagespeed_api_key']?>">
                        <p><em>
                                Please read <a href="https://developers.google.com/speed/docs/insights/v5/get-started" target="_blank">Google documentation</a> to generate an API key
                            </em>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Emission reference</th>
                    <td>
                        <input type="number" step="0.01" name="carbon_calculator[reference]" value="<?=$options['reference']?>"> g eq. CO²
                        <p><em>
                                Average website page emission
                            </em>
                        </p>
                    </td>
                </tr>
                </tbody>
            </table>
            <?php
        },'carbon_calculator-admin');
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        ?>
        <div class="wrap">
            <h1><?=__('Carbon calculator', 'carbon-calculator')?></h1>
            <form method="post" action="options.php">
                <?php
                // This prints out all hidden setting fields
                settings_fields( 'carbon_calculator' );
                do_settings_sections( 'carbon_calculator-admin' );
                submit_button(__('Save'));
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
            __('Carbon calculator settings', 'wp-carbon-calculator'),
            __('Carbon calculator', 'wp-carbon-calculator'),
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

new WCCSettings();
