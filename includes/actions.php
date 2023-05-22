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

        add_action( 'add_meta_boxes', [$this, 'add_meta_boxes'] );

        foreach ($this->options['taxonomies']??[] as $taxonomy){
            add_action($taxonomy.'_term_edit_form_top', [$this, 'term_edit_form_tag'], 10, 2);
        }

        foreach ($this->options['post_types']??[] as $post_type){
            add_filter( "manage_{$post_type}_posts_columns", [ $this, 'manage_posts_columns' ] );
            add_action( "manage_{$post_type}_posts_custom_column", [ $this, 'manage_posts_custom_column' ], 10, 2 );
        }

        add_action( 'wp_ajax_carbon_calculate', [$this, 'carbon_calculate'] );
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
     * @return string
     */public function password_protected_is_active($bool) {

        if ( strpos($_SERVER['HTTP_USER_AGENT']??'', 'Lighthouse') !== false  || in_array($_SERVER['REMOTE_ADDR']??'127.0.0.1', ['127.0.0.1', '::1']) ) {
            $bool = false;
        }

        return $bool;
    }

    /**
     * @return string
     */
    public function add_meta_boxes() {

        foreach ($this->options['post_types'] as $post_type){

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
        $id = intval($_POST['id']??0);
        $type = $_POST['type']??false;
        $reference = floatval($this->options['reference']);

        ignore_user_abort(true);

        if( $type == 'post' )
            $url = get_permalink($id);
        elseif( $type == 'term' )
            $url = get_term_link($id);

        if( !$url || is_wp_error($url) ){

            wp_send_json('Url not found', 404);
            return;
        }

        $url = get_home_url().wp_make_link_relative($url);

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

            if( $type == 'post' ){

                update_post_meta($id, 'calculated_carbon', $co2);
                update_post_meta($id, 'calculated_carbon_details', $computation);
            }
            elseif( $type == 'term' ){

                update_term_meta($id, 'calculated_carbon', $co2);
                update_term_meta($id, 'calculated_carbon_details', $computation);
            }

            wp_send_json($computation);

        } catch (Throwable $t) {

            wp_send_json($t->getMessage(), 500);
        }
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
    public function display_calculator_form($computation, $type, $id){

        $reference = floatval($this->options['reference']);
        ?>
        <div id="carbon-calculator" class="carbon-calculator carbon-calculator--<?=$computation['colorCode']??'grey'?>">

            <?php if( $type == 'term'): ?>
                <label>Carbon Calculator</label>
            <?php endif; ?>
            <div id="carbon-calculator-progressbar">
                <div id="carbon-calculator-progress" style="width: <?=(($computation['co2PerPageview']??0)/$reference*100)?>%"></div>
                <div id="carbon-calculator-progressinfo">
                    <?php if($computation):?>
                        <?=round(($computation['co2PerPageview']??0),2)?> /
                    <?php endif; ?>
                    <?=$reference?> g eq. CO²
                </div>
            </div>
            <span id="carbon-calculator-title">Footprint :</span>
            <span id="carbon-calculator-display" title="per page view">
                <?php if($computation):?>
                    <?=round($computation['co2PerPageview'],2)?>g eq. CO²
                <?php endif; ?>
            </span>
            <a id="carbon-calculate" data-type="<?=$type?>" data-id="<?=$id?>" role="button" title="Estimated computation time : 15s">
                <span>Estimate</span>
            </a>
            <div id="carbon-calculator-details">
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
