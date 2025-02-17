<?php

use Akhela\WebsiteCarbonCalculator\WebsiteCarbonCalculator;

class WPCC_Actions{

    private $options;

    public function __construct()
    {
        add_action( 'password_protected_is_active', [$this, 'password_protected_is_active'] );
        add_action( '_wp_put_post_revision', [$this, 'post_revision_updated'] );
        add_filter( 'posts_results', [$this, 'preview_access'], 10, 2 );

        if( !is_admin() )
            return;

        $this->options = get_option('wpcc_settings');

        if( empty($this->options['pagespeed_api_key']??'') || (in_array($_SERVER['REMOTE_ADDR']??'127.0.0.1', ['127.0.0.1', '::1']) && !WPCC_DEBUG) )
            return;

        add_action( 'add_meta_boxes', [$this, 'add_meta_boxes'] );

        foreach ($this->options['taxonomies']??[] as $taxonomy){

            add_action($taxonomy.'_edit_form', [$this, 'term_edit_form_tag'], 10, 2);

            add_filter( "manage_edit-{$taxonomy}_columns", [ $this, 'manage_columns' ] );
            add_filter( "manage_{$taxonomy}_custom_column", [ $this, 'manage_terms_custom_column' ], 10, 3 );
        }

        foreach ($this->options['post_types']??[] as $post_type){

            add_filter( "manage_{$post_type}_posts_columns", [ $this, 'manage_columns' ] );
            add_action( "manage_{$post_type}_posts_custom_column", [ $this, 'manage_posts_custom_column' ], 10, 2 );
        }

        add_action( 'wp_ajax_carbon_calculate', [$this, 'carbon_calculate'] );
        add_action( 'wp_ajax_reset_carbon_calculation', [$this, 'reset_carbon_calculation'] );
        add_action( 'wp_ajax_get_calculated_carbon', [$this, 'get_calculated_carbon'] );
    }

    /**
     * @param $posts
     * @return mixed
     */
    public function preview_access($posts ) {

        if ( !count($posts) )
            return $posts;

        $post = $posts[0];

        if( is_main_query() && in_array($post->post_status, ['draft', 'pending', 'future']) && $this->allowAccess() )
            $post->post_status = 'publish';

        return $posts;
    }

    /**
     * @param $revision_id
     */
    public function post_revision_updated($revision_id) {

        $post = wp_get_post_revision($revision_id);

        if( get_post_meta($post->post_parent, 'wpcc', true) ){

            delete_post_meta($post->post_parent, 'wpcc_details');
            delete_post_meta($post->post_parent, 'wpcc');

            update_post_meta($post->post_parent, 'wpcc_pending', true);

        }
    }

    /**
     * @return array
     */
    public function manage_columns($columns) {

        $columns_option = $this->options['columns']??[];

        if( in_array('performance', $columns_option ) ){

            $strategies = $this->options['strategy']??['desktop'];

            if( empty($strategies) )
                $strategies = ['desktop'];

            foreach ($strategies as $strategy)
                $columns['wpcc_performance_'.$strategy] = '<span class="wpcc-icon dashicons-before dashicons-'.esc_attr($strategy=='desktop'?'desktop':'smartphone').'" title="Performance score"></span><b>'.ucfirst($strategy).' Performance score</b>';
        }

        if( in_array('computed_carbon', $columns_option ) || !isset($this->options['columns']) )
            $columns['wpcc_computed'] = '<span class="wpcc-icon dashicons-before dashicons-admin-site" title="Estimated carbon emissions"></span><b>Estimated carbon emissions</b>';

        return $columns;
    }

    /**
     * @return void
     */
    public function manage_posts_custom_column($column_name, $item_id) {

        if( str_starts_with($column_name, 'wpcc_' ) ){

            $computation = get_post_meta($item_id,'wpcc_details', true);

            $this->displayIndicators($column_name, $computation);
        }
    }

    /**
     * @return void
     */
    public function manage_terms_custom_column($string, $column_name, $item_id) {

        if( str_starts_with($column_name, 'wpcc_' ) ){

            $computation = get_term_meta($item_id,'wpcc_details', true);

            $this->displayIndicators($column_name, $computation);
        }
    }

    /**
     * @param $column_name
     * @param $computation
     * @return void
     */
    private function displayIndicators($column_name, $computation) {

        if( $column_name == 'wpcc_computed'){

            if( $computation )
                echo '<a class="wpcc-badge wpcc-badge--'.esc_attr($computation['colorCode']).'" title="'.esc_attr(round(($computation['co2PerPageview']??0),2)).' g eq. CO²"></a>';
            else
                echo '<a class="wpcc-badge wpcc-badge--grey"></a>';
        }
        elseif( $column_name == 'wpcc_performance_desktop' or $column_name == 'wpcc_performance_mobile'){

            $type = str_replace('wpcc_performance_', '', $column_name);

            $score = $computation['details'][$type]['performanceScore']??($type=='desktop'?$computation['performanceScore']??0:0);

            if( $score )
                echo '<a class="wpcc-performance wpcc-performance--'.esc_attr(WPCC_Helper::get_color($score)).'" style="--progress:'.esc_attr($score).'">'.esc_html(round($score*100)).'</a>';
            else
                echo '<a class="wpcc-performance wpcc-performance--grey"></a>';
        }
    }

    /**
     * @return string
     */
    public function password_protected_is_active($bool) {

        if ( $this->allowAccess() )
            return false;

        return $bool;
    }


    private function allowAccess(){

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return wp_hash('carbon_calculate') == sanitize_text_field(wp_unslash($_GET['hash']??'')) || in_array($_SERVER['REMOTE_ADDR']??'127.0.0.1', ['127.0.0.1', '::1']);
    }

    /**
     * @return void
     */
    public function add_meta_boxes() {

        foreach ($this->options['post_types']??[] as $post_type){

            add_meta_box(
                'wpcc_calculator',
                __( 'Page Performance', 'website-carbon-calculator' ),
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

        $nonce = sanitize_text_field(wp_unslash($_POST['nonce']??''));

        if( !wp_verify_nonce($nonce, 'carbon-calculator') )
            wp_send_json(['in_progress'=>false, 'error'=>'Nonce not valid'], 500);

        $id = sanitize_text_field(wp_unslash($_POST['id']??''));
        $type = sanitize_text_field(wp_unslash($_POST['type']??false));
        $reference = floatval($this->options['reference']);

        set_time_limit(120);
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
            $url = '/404';

        if( !$url || is_wp_error($url) ){

            wp_send_json(['in_progress'=>false, 'error'=>'Url not found'], 500);
            return;
        }

        if( $time = $this->get_meta($type, $id, 'wpcc_calculating') ){

            if( $time+120 > time() ){
                
                wp_send_json(['in_progress'=>true], 500);
                return;
            }
        }

        $this->save_meta($type, $id, 'wpcc_calculating', time());

        $base_url = is_multisite() ? network_home_url() : get_home_url();
        $url = rtrim($base_url, '/').wp_make_link_relative($url);

        if( in_array($_SERVER['REMOTE_ADDR']??'127.0.0.1', ['127.0.0.1', '::1']) && WPCC_DEBUG )
            $url = 'https://www.websitecarbon.com';

        //ensure generated cached version
        $response = wp_remote_get($url);

        if( is_wp_error($response) ){

            wp_send_json(['in_progress'=>false, 'error'=>$response->get_error_message()], 500);
            return;
        }

        //load library
        if( !class_exists('Akhela\WebsiteCarbonCalculator\WebsiteCarbonCalculator') )
            require __DIR__ . '/../vendor/autoload.php';

        $websiteCarbonCalculator = new WebsiteCarbonCalculator($this->options['pagespeed_api_key']);

        try {

            $url = add_query_arg('hash', wp_hash('carbon_calculate'), $url);
            $strategies = $this->options['strategy']??['desktop'];

            $co2 = 0;
            $performance_score = 0;

            $data = [
                'details'=>[
                    'desktop'=>false,
                    'mobile'=>false
                ]
            ];

            foreach($strategies as $strategy){

                $computation = $websiteCarbonCalculator->calculateByURL($url, [
                        'strategy'=>$strategy,
                        'isGreenHost' => $this->options['is_green_host']??false]
                );

                $co2 += $computation['co2PerPageview'];
                $performance_score += $computation['performanceScore'];

                $data['details'][$strategy]['co2PerPageview'] = $computation['co2PerPageview'];
                $data['details'][$strategy]['energy'] = round($computation['energy']*1000, 2).'Wh';
                $data['details'][$strategy]['performanceScore'] = $computation['performanceScore'];
                $data['details'][$strategy]['bytesTransferred'] = $this->humanFilesize($computation['bytesTransferred']);
                $data['details'][$strategy]['firstContentfulPaint'] = $this->humanTime($computation['firstContentfulPaint']);
                $data['details'][$strategy]['largestContentfulPaint'] = $this->humanTime($computation['largestContentfulPaint']);
                $data['details'][$strategy]['interactive'] = $this->humanTime($computation['interactive']);
                $data['details'][$strategy]['bootupTime'] = $this->humanTime($computation['bootupTime']);
                $data['details'][$strategy]['serverResponseTime'] = $this->humanTime($computation['serverResponseTime']);
                $data['details'][$strategy]['mainthreadWork'] = $this->humanTime($computation['mainthreadWork']);
            }

            $data['co2PerPageview'] = $co2/count($strategies);
            $data['performanceScore'] = $performance_score/count($strategies);
            $data['colorCode'] = WPCC_Helper::get_color_code( $data['co2PerPageview'], $reference);

            $this->save_meta($type, $id, 'wpcc_details', $data);
            $this->save_meta($type, $id, 'wpcc', $data['co2PerPageview']);

            $this->delete_meta($type, $id, 'wpcc_calculating');

            wp_send_json($data);

        } catch (Throwable $t) {

            $this->delete_meta($type, $id, 'wpcc_calculating');

            wp_send_json(['in_progress'=>false, 'error'=>$t->getMessage()], 500);
        }
    }


    /**
     * Compute carbon
     */
    public function reset_carbon_calculation()
    {
        $nonce = sanitize_text_field(wp_unslash($_POST['nonce']??''));

        if( !wp_verify_nonce($nonce, 'carbon-calculator') )
            wp_send_json(['in_progress'=>false, 'error'=>'Nonce not valid'], 500);

        global $wpdb;

        $result = false;
        $id = sanitize_text_field(wp_unslash($_POST['id']??''));
        $type = sanitize_text_field(wp_unslash($_POST['type']??false));

        if( $type == 'post' ){

            $all_posts = get_posts(['post_type'=>$id,'posts_per_page'=>-1, 'fields'=>'ids']);
            $args = array_merge($all_posts, ['wpcc%']);

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
            $result = $wpdb->query(
                    $wpdb->remove_placeholder_escape(
                            $wpdb->prepare("DELETE FROM $wpdb->postmeta WHERE `post_id` IN (".implode( ', ', array_fill( 0, count( $all_posts ), '%d' ) ).") AND `meta_key` LIKE %s", $args)
                    )
            );

            wp_cache_delete_multiple( $all_posts, 'post_meta' );
        }
        elseif( $type == 'term' ){

            $all_terms = get_terms(['taxonomy'=>$id, 'fields'=>'ids']);
            $args = array_merge($all_terms, ['wpcc%']);

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
            $result = $wpdb->query(
                    $wpdb->remove_placeholder_escape(
                            $wpdb->prepare("DELETE FROM $wpdb->termmeta WHERE `term_id` IN (".implode( ', ', array_fill( 0, count( $all_terms ), '%d' ) ).") AND `meta_key` LIKE %s", $args)
                    )
            );

            wp_cache_delete_multiple( $all_terms, 'term_meta' );
        }

        wp_send_json($result);
    }

    /**
     * Compute carbon
     */
    public function get_calculated_carbon()
    {
        $nonce = sanitize_text_field(wp_unslash($_POST['nonce']??''));

        if( !wp_verify_nonce($nonce, 'carbon-calculator') )
            wp_send_json(['in_progress'=>false, 'error'=>'Nonce not valid'], 500);

        $id = sanitize_text_field(wp_unslash($_POST['id']??''));
        $type = sanitize_text_field(wp_unslash($_POST['type']??false));

        if( $time = $this->get_meta($type, $id, 'wpcc_calculating') ){

            if( $time+120 < time() )
                $this->delete_meta($type, $id, 'wpcc_calculating');

            wp_send_json(['in_progress'=>true], 500);
        }
        elseif( $computation = $this->get_meta($type, $id, 'wpcc_details') ){

            wp_send_json($computation);
        }
        elseif( $this->get_meta($type, $id, 'wpcc_pending') ){

            $this->carbon_calculate();
        }
        else{

            wp_send_json([
                'co2PerPageview'=>0,
                'colorCode'=>'grey',
                'performanceScore'=>0,
                'details'=>[]
            ]);
        }
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
            update_option($key.'::'.$type, $value);
        if( $type == 'archive' )
            update_option($key.'::'.$id, $value);
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
            delete_option($key.'::'.$type);
        if( $type == 'archive' )
            delete_option($key.'::'.$id);
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
            return get_option($key.'::'.$type, false);
        if( $type == 'archive' )
            return get_option($key.'::'.$id, false);
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

        $computation = get_post_meta($post->ID, 'wpcc_details', true);
        WPCC_Helper::display_calculator_form($computation, 'post', $post->ID);
    }


    /**
     * @param WP_Term $tag
     * @return void
     */
    public function term_edit_form_tag($tag, $taxonomy){

        $computation = get_term_meta($tag->term_id, 'wpcc_details', true);

        echo '<div class="postbox wpcc-postbox"><h2>Page Performance</h2><div class="inside">';
        WPCC_Helper::display_calculator_form($computation, 'term', $tag->term_id);
        echo '</div></div>';
    }
}
