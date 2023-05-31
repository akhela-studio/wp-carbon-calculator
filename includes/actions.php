<?php

use Akhela\WebsiteCarbonCalculator\WebsiteCarbonCalculator;

class WCCActions{

    private $options;

    public function __construct()
    {
        add_action( 'password_protected_is_active', [$this, 'password_protected_is_active'] );

        if( in_array($_SERVER['REMOTE_ADDR']??'127.0.0.1', ['127.0.0.1', '::1']) && !WCC_DEBUG )
            return;

        $this->options = get_option('carbon_calculator');

        add_action( '_wp_put_post_revision', [$this, 'post_revision_updated'] );
        add_action( 'add_meta_boxes', [$this, 'add_meta_boxes'] );

        foreach ($this->options['taxonomies']??[] as $taxonomy){

            add_action($taxonomy.'_term_edit_form_top', [$this, 'term_edit_form_tag'], 10, 2);

            add_filter( "manage_edit-{$taxonomy}_columns", [ $this, 'manage_posts_columns' ] );
            add_filter( "manage_{$taxonomy}_custom_column", [ $this, 'manage_terms_custom_column' ], 10, 3 );
        }

        foreach ($this->options['post_types']??[] as $post_type){

            add_filter( "manage_{$post_type}_posts_columns", [ $this, 'manage_posts_columns' ] );
            add_action( "manage_{$post_type}_posts_custom_column", [ $this, 'manage_posts_custom_column' ], 10, 2 );
        }

        add_action( 'wp_ajax_carbon_calculate', [$this, 'carbon_calculate'] );
        add_action( 'wp_ajax_get_calculated_carbon', [$this, 'get_calculated_carbon'] );
    }

    /**
     * @param $revision_id
     */
    public function post_revision_updated($revision_id) {

        $post = wp_get_post_revision($revision_id);

        delete_post_meta($post->post_parent, 'calculated_carbon_details');
        delete_post_meta($post->post_parent, 'calculated_carbon');
    }

    /**
     * @return array
     */
    public function manage_posts_columns($columns) {

        $columns['wcc'] = '<span class="wcc-icon dashicons-before dashicons-admin-site" title="Estimated carbon emissions"/>';

        return $columns;
    }

    /**
     * @return void
     */
    public function manage_posts_custom_column($column_name, $item_id) {

        if( $column_name == 'wcc'){

            $computation = get_post_meta($item_id,'calculated_carbon_details', true);

            if( $computation )
                echo '<a class="wcc-badge wcc-badge--'.$computation['colorCode'].'" title="'.round(($computation['co2PerPageview']??0),2).' g eq. CO²"/>';
            else
                echo '<a class="wcc-badge wcc-badge--grey"/>';
        }
    }

    /**
     * @return void
     */
    public function manage_terms_custom_column($string, $column_name, $item_id) {

        if( $column_name == 'wcc'){

            $computation = get_term_meta($item_id,'calculated_carbon_details', true);

            if( $computation )
                echo '<a class="wcc-badge wcc-badge--'.$computation['colorCode'].'" title="'.round(($computation['co2PerPageview']??0),2).' g eq. CO²"/>';
            else
                echo '<a class="wcc-badge wcc-badge--grey"/>';
        }
    }

    /**
     * @return string
     */public function password_protected_is_active($bool) {

        if ( strpos($_SERVER['HTTP_USER_AGENT']??'', 'Lighthouse') !== false  || in_array($_SERVER['REMOTE_ADDR']??'127.0.0.1', ['127.0.0.1', '::1']) ) {
            $bool = false;
        }

        return $bool;
    }

    /**
     * @return void
     */
    public function add_meta_boxes() {

        foreach ($this->options['post_types']??[] as $post_type){

            add_meta_box(
                'wpc',
                __( 'Carbon calculator', 'wcc' ),
                [$this, 'add_meta_box'],
                $post_type,
                'side',
                'high'
            );
        }
    }

    /**
     * @param $size
     * @param $precision
     * @return string
     */
    private function humanFilesize($size, $precision = 2) {

        $units = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
        $step = 1024;
        $i = 0;
        while (($size / $step) > 0.9) {
            $size = $size / $step;
            $i++;
        }
        return round($size, $precision).$units[$i];
    }

    /**
     * @param $size
     * @param $precision
     * @return string
     */
    private function humanTime($size, $precision = 2) {

        $units = array('ms','s');
        $step = 1000;
        $i = 0;
        while (($size / $step) > 0.9) {
            $size = $size / $step;
            $i++;
        }
        return round($size, $precision).$units[$i];
    }


    /**
     * Compute carbon
     */
    public function carbon_calculate()
    {
        $url = false;
        $id = $_POST['id']??'';
        $type = $_POST['type']??false;
        $reference = floatval($this->options['reference']);

        ignore_user_abort(true);

        if( $type == 'post' )
            $url = get_permalink(intval($id));
        elseif( $type == 'term' )
            $url = get_term_link(intval($id));
        elseif( $type == 'archive' )
            $url = get_post_type_archive_link($id);
        elseif( $type == 'search' )
            $url = get_search_link($id);
        elseif( $type == '404' )
            $url = '404';

        if( !$url || is_wp_error($url) ){

            wp_send_json('Url not found', 404);
            return;
        }

        if( $this->get_meta($type, $id, 'calculating_carbon') ){

            wp_send_json('Computation is in progress, please wait and reload the page', 500);
            return;
        }

        $this->save_meta($type, $id, 'calculating_carbon', true);

        $base_url = is_multisite() ? network_home_url() : get_home_url();
        $url = rtrim($base_url, '/').wp_make_link_relative($url);

        if( in_array($_SERVER['REMOTE_ADDR']??'127.0.0.1', ['127.0.0.1', '::1']) && WCC_DEBUG )
            $url = 'https://www.websitecarbon.com';

        //ensure generated cached version
        wp_remote_get($url);

        $websiteCarbonCalculator = new WebsiteCarbonCalculator($this->options['pagespeed_api_key']);

        try {

            $computation = $websiteCarbonCalculator->calculateByURL($url, ['isGreenHost' => $this->options['is_green_host']??false]);
            $co2 = $computation['co2PerPageview'];

            unset($computation['url'], $computation['isGreenHost']);

            $computation['bytesTransferred'] = $this->humanFilesize($computation['bytesTransferred']);
            $computation['firstMeaningfulPaint'] = $this->humanTime($computation['firstMeaningfulPaint']);
            $computation['interactive'] = $this->humanTime($computation['interactive']);
            $computation['bootupTime'] = $this->humanTime($computation['bootupTime']);
            $computation['serverResponseTime'] = $this->humanTime($computation['serverResponseTime']);
            $computation['mainthreadWork'] = $this->humanTime($computation['mainthreadWork']);
            $computation['energy'] = round($computation['energy']*1000, 2).'Wh';
            $computation['colorCode'] = $this->getColorCode($co2, $reference);

            $this->save_meta($type, $id, 'calculated_carbon_details', $computation);
            $this->save_meta($type, $id, 'calculated_carbon', $co2);

            $this->delete_meta($type, $id, 'calculating_carbon');

            wp_send_json($computation);

        } catch (Throwable $t) {

            $this->delete_meta($type, $id, 'calculating_carbon');

            wp_send_json($t->getMessage(), 500);
        }
    }

    /**
     * Compute carbon
     */
    public function get_calculated_carbon()
    {
        $id = $_POST['id']??'';
        $type = $_POST['type']??false;

        if( $computation = $this->get_meta($type, $id, 'calculated_carbon_details') )
            wp_send_json($computation);
        else
            wp_send_json(['co2PerPageview'=>0, 'colorCode'=>'grey']);

    }

    /**
     * @param $type
     * @param $id
     * @param $key
     * @param $value
     * @return void
     */
    public function save_meta($type, $id, $key, $value){

        if( $type == 'search' || $type == '404' )
            update_option($type.'::'.$key, $value);
        if( $type == 'archive' )
            update_option($id.'::'.$key, $value);
        elseif( $type == 'post' )
            update_post_meta($id, $key, $value);
        elseif( $type == 'term' )
            update_term_meta($id, $key, $value);
    }

    /**
     * @param $type
     * @param $id
     * @param $key
     * @return void
     */
    public function delete_meta($type, $id, $key){

        if( $type == 'search' || $type == '404' )
            delete_option($type.'::'.$key);
        if( $type == 'archive' )
            delete_option($id.'::'.$key);
        elseif( $type == 'post' )
            delete_post_meta($id, $key);
        elseif( $type == 'term' )
            delete_term_meta($id, $key);
    }

    /**
     * @param $type
     * @param $id
     * @param $key
     * @return string|bool
     */
    public function get_meta($type, $id, $key){

        if( $type == 'search' || $type == '404' )
            return get_option($type.'::'.$key, false);
        if( $type == 'archive' )
            return get_option($id.'::'.$key, false);
        elseif( $type == 'post' )
            return get_post_meta($id, $key, true);
        elseif( $type == 'term' )
            return get_term_meta($id, $key, true);

        return false;
    }


    /**
     * @return void
     */
    public function add_meta_box(){

        $post = get_post();

        $computation = get_post_meta($post->ID,'calculated_carbon_details', true);
        $this->display_calculator_form($computation, 'post', $post->ID);
    }


    /**
     * @param WP_Term $tag
     * @return void
     */
    public function term_edit_form_tag($tag, $taxonomy){

        $computation = get_term_meta($tag->term_id,'calculated_carbon_details', true);

        $this->display_calculator_form($computation, 'term', $tag->term_id);
    }

    /**
     * @param $co2PerPageview
     * @param $reference
     * @return string
     */
    public function getColorCode($co2PerPageview, $reference){

        if( !$co2PerPageview )
            return 'grey';

        $color_code = 'orange';

        if( $co2PerPageview <= $reference/2 )
            $color_code = 'green';
        elseif( $co2PerPageview >= $reference )
            $color_code = 'red';

        return $color_code;
    }

    /**
     * @param $computation
     * @param $type
     * @param $id
     * @return void
     */
    public static function display_calculator_form($computation, $type, $id){

        $options = get_option('carbon_calculator');
        $reference = floatval($options['reference']??0.55);
        ?>
        <div class="carbon-calculator carbon-calculator--<?=$computation['colorCode']??'grey'?>">

            <?php if( $type == '404'): ?>
                <label><a href="<?=get_home_url().'/404'?>" target="_blank">404</a></label>
            <?php elseif( $type == 'search'): ?>
                <label><a href="<?=get_search_link()?>" target="_blank">Search</a></label>
            <?php elseif( $type == 'term'): ?>
                <label>Carbon Calculator</label>
            <?php elseif( $type == 'archive'):
                $post_type = get_post_type_object($id);
                ?>
                <label><a href="<?=get_post_type_archive_link($post_type->name)?>" target="_blank"><?=$post_type->label?></a></label>
            <?php endif; ?>
            <div class="carbon-calculator-progressbar">
                <div class="carbon-calculator-progress" style="width: <?=(($computation['co2PerPageview']??0)/$reference*100)?>%"></div>
                <div class="carbon-calculator-progressinfo">
                    <?php if($computation):?>
                        <?=round(($computation['co2PerPageview']??0),2)?> /
                    <?php endif; ?>
                    <?=$reference?> g eq. CO²
                </div>
            </div>
            <span class="carbon-calculator-title">Footprint :</span>
            <span class="carbon-calculator-display" title="per page view">
                <?php if($computation):?>
                    <?=round($computation['co2PerPageview'],2)?>g eq. CO²
                <?php endif; ?>
            </span>
            <a class="carbon-calculate carbon-calculate-estimate" data-type="<?=$type?>" data-id="<?=$id?>" role="button" title="Estimated computation time : 15s">
                <span>Estimate</span>
            </a>
            <div class="carbon-calculator-details">
                <span>
                    <?php if($computation):?>
                        <?php foreach ($computation as $key=>$value) :?>
                            <?php if( $key != 'co2PerPageview'):?>
                                <span><?=$key?> : <b><?=$value?></b></span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </span>
            </div>
        </div>
        <?php
    }
}

new WCCActions();
