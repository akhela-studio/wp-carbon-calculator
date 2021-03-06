<?php

use Akhela\WebsiteCarbonCalculator\WebsiteCarbonCalculator;

class WCCActions{

    private $options;

    public function __construct()
    {
        if( in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']) && !WCC_DEBUG )
            return;

        $this->options = get_option('carbon_calculator');

        add_action( 'post_submitbox_misc_actions', [$this, 'post_submitbox_misc_actions']);

        foreach ($this->options['taxonomies'] as $taxonomy)
            add_action($taxonomy.'_term_edit_form_top', [$this, 'term_edit_form_tag'], 10, 2);

        add_action( 'wp_ajax_carbon_calculate', [$this, 'carbon_calculate'] );
    }


    /**
     * Compute carbon
     */
    public function carbon_calculate()
    {
        $url = false;
        $id = intval($_POST['id']??0);
        $type = $_POST['type']??false;

        ignore_user_abort(true);

        if( $type == 'post' )
            $url = get_permalink($id);
        elseif( $type == 'term' )
            $url = get_term_link($id);

        if( !$url || is_wp_error($url) ){

            wp_send_json('Url not found', 404);
            return;
        }

        $url = 'https://www.yahoo.com';

        //ensure generated cached version
        wp_remote_get($url);

        $websiteCarbonCalculator = new WebsiteCarbonCalculator($this->options['pagespeed_api_key']);

        try {

            $computation = $websiteCarbonCalculator->calculateByURL($url, ['isGreenHost' => false]);

            unset($computation['url'], $computation['isGreenHost']);

            if( $type == 'post' ){

                update_post_meta($id, 'calculated_carbon', $computation['calculated_carbon']);
                update_post_meta($id, '_calculated_carbon', $computation);
            }
            elseif( $type == 'term' ){

                update_term_meta($id, 'calculated_carbon', $computation['calculated_carbon']);
                update_term_meta($id, '_calculated_carbon', $computation);
            }

            wp_send_json($computation);

        } catch (Throwable $t) {

            wp_send_json($t->getMessage(), $t->getCode());
        }
    }


    /**
     * @param WP_Post $post
     * @return void
     */
    public function post_submitbox_misc_actions($post){

        if( !in_array($post->post_type, $this->options['post_types']) )
            return;

        $computation = get_post_meta($post->ID,'_calculated_carbon', true);
        ?>
        <div class="misc-pub-section misc-pub-carbon-calculator">
            <?php $this->display_calculator_form($computation, 'post', $post->ID) ?>
        </div>
        <?php
    }


    /**
     * @param WP_Term $tag
     * @return void
     */
    public function term_edit_form_tag($tag, $taxonomy){

        $computation = get_term_meta($tag->term_id,'_calculated_carbon', true);
        $this->display_calculator_form($computation, 'term', $tag->term_id);
    }

    /**
     * @param $computation
     * @param $type
     * @param $id
     * @return void
     */
    public function display_calculator_form($computation, $type, $id){

        $color_code = 'grey';

        if( $computation ){
            $color_code = 'orange';

            if( $computation['co2PerPageview'] < $this->options['reference']/2 )
                $color_code = 'green';
            elseif( $computation['co2PerPageview'] > $this->options['reference']*2 )
                $color_code = 'red';
        }
        ?>
        <div id="carbon-calculator" class="carbon-calculator carbon-calculator--<?=$color_code?>">
            <span id="carbon-calculator-title">Footprint :</span>
            <span id="carbon-calculator-display" title="per page view">
                <?php if($computation):?>
                    <?=round($computation['co2PerPageview'],2)?>g eq. CO??
                <?php endif; ?>
            </span>
            <a id="carbon-calculate" data-type="<?=$type?>" data-id="<?=$id?>" role="button">
                <span>Calculer</span>
            </a>
            <div id="carbon-calculator-details">
                <span>
                    <?php if($computation):?>
                        <?php foreach ($computation as $key=>$value) :?>
                            <span><?=$key?> : <b><?=$value?></b></span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </span>
            </div>
        </div>
        <?php
    }
}

new WCCActions();
