<?php
class WPCC_Dashboard{

    private $options;

    public function __construct()
    {
        if( !is_admin() || (in_array($_SERVER['REMOTE_ADDR']??'127.0.0.1', ['127.0.0.1', '::1']) && !WPCC_DEBUG) )
            return;

        $this->options = get_option('wpcc_settings');

        add_action( 'wp_dashboard_setup', [$this, 'add_dashboard_widgets'] );
    }

    public function add_dashboard_widgets(){

        wp_add_dashboard_widget( 'wpcc_performance', 'Website Performance Snapshot', [$this, 'dashboard_widget_callback'] );
    }

    public function dashboard_widget_callback(){

        global $wpdb;

        $performance_score = [
            'desktop' => [],
            'mobile' => [],
        ];
        $co2PerPageview = 0;
        $total_items = $computed_items = 0;

        foreach ($this->options['post_types']??[] as $post_type){

            $results = $wpdb->get_results(
                $wpdb->prepare("SELECT p.ID as post_id, pm.meta_value FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE pm.meta_key = 'wpcc_details' AND p.post_type = %s", $post_type)
            );

            foreach ($results as $result){

                $computed = maybe_unserialize($result->meta_value);

                $performance_score['desktop'][] = $computed['details']['desktop']['performanceScore']??($computed['performanceScore']??null);
                $performance_score['mobile'][] = $computed['details']['mobile']['performanceScore']??null;

                $co2PerPageview += $computed['co2PerPageview'];
            }

            $computed_items += count($results);
            $total_posts = wp_count_posts( $post_type );

            $total_items += $total_posts->publish;
        }

        foreach ($this->options['taxonomies']??[] as $taxonomy){

            $results = $wpdb->get_results(
                $wpdb->prepare(" SELECT t.term_id, tm.meta_value FROM {$wpdb->terms} t INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id INNER JOIN {$wpdb->termmeta} tm ON t.term_id = tm.term_id WHERE tt.taxonomy = %s AND tm.meta_key = 'wpcc_details'", $taxonomy)
            );

            foreach ($results as $result){

                $computed = maybe_unserialize($result->meta_value);

                $performance_score['desktop'][] = $computed['details']['desktop']['performanceScore']??($computed['performanceScore']??null);
                $performance_score['mobile'][] = $computed['details']['mobile']['performanceScore']??null;

                $co2PerPageview += $computed['co2PerPageview'];
            }

            $computed_items += count($results);
            $total_items += wp_count_terms(['taxonomy' => $taxonomy, 'hide_empty' => true]);
        }

        $performance_score['desktop'] = array_filter($performance_score['desktop']);
        $performance_score['mobile'] = array_filter($performance_score['mobile']);

        $performance_score['desktop'] = count($performance_score['desktop'])?array_sum($performance_score['desktop'])/count($performance_score['desktop']):0;
        $performance_score['mobile'] = count($performance_score['mobile']) ? array_sum($performance_score['mobile']) / count($performance_score['mobile']):0;

        if( $performance_score['mobile'] )
            $performance_score['general'] = ($performance_score['desktop']+$performance_score['mobile'])/2;
        else
            $performance_score['general'] = $performance_score['desktop'];

        $co2_reference = floatval($this->options['reference']??0.55);

        ?>
        <div class="wpcc-widget">
            <?php if ($total_items): ?>
                <div class="wpcc-widget__item">
                    <span class="wpcc-widget__progress wpcc-widget__progress--<?php echo  esc_attr(WPCC_Helper::get_color($computed_items/$total_items)); ?>" style="--progress:<?php echo esc_attr($computed_items/$total_items); ?>">
                        <span><?php echo esc_html($computed_items); ?>/<?php echo esc_html($total_items); ?></span>
                    </span>
                    Computed items
                </div>
            <?php endif; ?>
            <div class="wpcc-widget__item">
                <span title="Desktop: <?php echo esc_attr(round($performance_score['desktop']*100));?>, Mobile: <?php echo esc_attr(round($performance_score['mobile']*100));?>" class="wpcc-widget__progress wpcc-widget__progress--<?php echo  esc_attr(WPCC_Helper::get_color($performance_score['general'])); ?>" style="--progress:<?php echo esc_attr($performance_score['general']); ?>">
                    <span><?php echo esc_html(round($performance_score['general']*100)); ?></span>
                </span>
                Performance
            </div>
            <?php if ($co2PerPageview): ?>
                <div class="wpcc-widget__item">
                    <span title="Average emission per page view" class="wpcc-widget__progress wpcc-widget__progress--<?php echo  esc_attr(WPCC_Helper::get_color($co2_reference/($co2PerPageview/$computed_items))); ?>" style="--progress:<?php echo esc_attr(($co2PerPageview/$computed_items)/$co2_reference); ?>">
                        <span><?php echo esc_html(round($co2PerPageview/$computed_items*100)/100); ?></span>
                    </span>
                    g eq. CO² / page
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}
