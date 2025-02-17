<?php

class WPCC_Helper{

    /**
     * @return false|mixed|null
     */
    public static function get_calculated_carbon(){

        global $wp_query;

        $queried_object = get_queried_object();

        if( $queried_object instanceof WP_Term )
            return get_term_meta($queried_object->term_id, 'wpcc', true);
        elseif ( $queried_object instanceof WP_Post )
            return get_post_meta($queried_object->ID, 'wpcc', true);
        elseif ( $queried_object instanceof WP_Post_Type )
            return get_option('wpcc::'.$queried_object->name);
        elseif ( is_search() )
            return get_option('wpcc::search');
        elseif ( is_404() || ($wp_query->query['name']??'') == '404' )
            return get_option('wpcc::404');

        return false;
    }

    /**
     * @param $score
     * @return string
     */
    public static function get_color($score){

        return $score < 0.5 ? "red" : ( $score > 0.9 ? 'green': 'orange');
    }

    /**
     * @param $co2PerPageview
     * @param $reference
     * @return string
     */
    public static function get_color_code($co2PerPageview, $reference){

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

        $options = get_option('wpcc_settings');

        $reference = floatval($options['reference']??0.55);
        $strategies = $options['strategy']??['desktop'];
        $current_screen = get_current_screen();
        $url = '';

        if( !$current_screen )
            return;

        $is_block_editor = $current_screen->is_block_editor();

        if( $type == '404') {

            $url = get_home_url() . '/404';
        }
        elseif( $type == 'search') {

            $url = get_search_link();
        }
        elseif( $type == 'post') {

            $url = get_permalink($id);
        }
        elseif( $type == 'term') {

            $url = get_term_link($id);
        }
        elseif( $type == 'archive'){

            $post_type = get_post_type_object($id);
            $url = get_post_type_archive_link($post_type->name);
        }

        if( is_wp_error($url) ){

            echo esc_html($url->get_error_message());
            return;
        }

        ?>
        <div class="carbon-calculator carbon-calculator--<?php echo esc_attr($computation['colorCode']??'grey'); ?>">
            <div class="carbon-calculator-indicators">
                <div class="carbon-calculator-data carbon-calculator-data--carbon">
                    <?php
                    if( $computation )
                        echo '<a title="Emission per page view" class="wpcc-badge wpcc-badge--'.esc_attr($computation['colorCode']).'"></a>Hit emission : <b>'.esc_attr(round(($computation['co2PerPageview']??0),2)).' g eq. CO²</b>';
                    else
                        echo '<a class="wpcc-badge wpcc-badge--grey"></a>Hit emission : <b>N/A</b>';
                    ?>
                </div>
                <?php if( in_array('desktop', $strategies )): ?>
                    <div class="carbon-calculator-data carbon-calculator-data--performance carbon-calculator-data--desktop">
                        <?php
                        $score = $computation['details']['desktop']['performanceScore']??$computation['performanceScore']??false;
                        if( $score )
                            echo '<a title="View on Google Page Speed" href="'.esc_url('https://pagespeed.web.dev/analysis?url='.esc_url($url).'&form_factor=desktop').'" target="_blank" class="dashicons-before dashicons-desktop wpcc-performance wpcc-performance--'.esc_attr(self::get_color($score)).'" style="--progress:'.esc_attr($score).'"></a> Performance score : <b>'.esc_html(round($score*100)).'</b>';
                        else
                            echo '<a title="View on Google Page Speed" href="'.esc_url('https://pagespeed.web.dev/analysis?url='.esc_url($url).'&form_factor=desktop').'" target="_blank" class="dashicons-before dashicons-desktop wpcc-performance wpcc-performance--grey"></a>Performance score : <b>N/A</b>';
                        ?>
                    </div>
                <?php endif; ?>
                <?php if( in_array('mobile', $strategies )): ?>
                    <div class="carbon-calculator-data carbon-calculator-data--performance carbon-calculator-data--mobile">
                        <?php
                        $score = $computation['details']['mobile']['performanceScore']??false;
                        if( $score )
                            echo '<a title="View on Google Page Speed" href="'.esc_url('https://pagespeed.web.dev/analysis?url='.esc_url($url).'&form_factor=mobile').'" target="_blank" class="dashicons-before dashicons-smartphone wpcc-performance wpcc-performance--'.esc_attr(self::get_color($score)).'" style="--progress:'.esc_attr($score).'"></a> Performance score : <b>'.esc_html(round($score*100)).'</b>';
                        else
                            echo '<a title="View on Google Page Speed" href="'.esc_url('https://pagespeed.web.dev/analysis?url='.esc_url($url).'&form_factor=mobile').'" target="_blank" class="dashicons-before dashicons-smartphone wpcc-performance wpcc-performance--grey"></a>Performance score : <b>N/A</b>';
                        ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="carbon-calculator-progressbar">
                <div class="carbon-calculator-progress" style="width: <?php echo esc_attr((($computation['co2PerPageview']??0)/$reference*100)); ?>%"></div>
                <div class="carbon-calculator-progressinfo">
                    <?php if($computation):?>
                        <?php echo esc_html(round(($computation['co2PerPageview']??0),2)); ?> /
                    <?php endif; ?>
                    <?php echo esc_html($reference); ?> g eq. CO²
                </div>
            </div>
            <span class="carbon-calculator-display" title="View computation details">Details</span>
            <button class="carbon-calculate carbon-calculate-estimate <?php echo esc_attr($is_block_editor?'components-button is-secondary':'button button-secondary'); ?>" data-type="<?php echo esc_attr($type); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('carbon-calculator'));?>" data-id="<?php echo esc_attr($id); ?>" role="button" title="Estimated computation time : <?php echo esc_attr(count($strategies)*15); ?>s">
                <span>Estimate</span>
                <span>Loading…</span>
            </button>
            <div class="carbon-calculator-details" style="display: none">
                <?php if($computation):?>
                    <?php foreach ($computation['details'] as $strategy=>$values) :?>
                        <div class="carbon-calculator-strategy">
                            <h3><?php echo esc_html($strategy); ?></h3>
                            <div class="carbon-calculator-strategy-details">
                                <?php foreach ($values as $key=>$value) :?>
                                    <?php if( $key == 'performanceScore' || $key == 'co2PerPageview'):?>
                                        <span><?php echo esc_html($key); ?><b><?php echo esc_html(round($value,2)); ?></b></span>
                                    <?php else: ?>
                                        <span><?php echo esc_html($key); ?><b><?php echo esc_html($value); ?></b></span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}
