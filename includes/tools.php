<?php

class WPCC_Tools{

    private $options;

    public function __construct() {

        if( (in_array($_SERVER['REMOTE_ADDR']??'127.0.0.1', ['127.0.0.1', '::1']) && !WPCC_DEBUG) )
            return;

        $this->options = get_option('wpcc_settings');

        add_action( 'admin_menu', [$this, 'admin_menu'] );
    }

    public function getStatistics(){

        global $wpdb;

        $nonce = wp_create_nonce('carbon-calculator');

        echo '<label><b>Terms</b></label>';
        echo '<ul class="carbon-calculator-statistics">';
        foreach ($this->options['taxonomies']??[] as $taxonomy){

            $all_terms = get_terms(['taxonomy'=>$taxonomy, 'fields'=>'ids']);

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
            $result = $wpdb->get_results($wpdb->prepare("SELECT DISTINCT `term_id` from `$wpdb->termmeta` WHERE `meta_key` = 'wpcc' AND `term_id` IN (".implode( ', ', array_fill( 0, count( $all_terms ), '%d' ) ).")", $all_terms));

            $result = array_map(function ($item){ return $item->term_id; }, $result);

            $terms = array_diff($all_terms, $result);

            $taxonomy = get_taxonomy($taxonomy);

            echo '<li>'.
                '<a href="'.esc_url(admin_url('edit-tags.php?taxonomy='.$taxonomy->name)).'" class="dashicons-before dashicons-category"> '.esc_html($taxonomy->label).'</a>'.
                ' : <span>'.esc_html(round(count($result)/count($all_terms)*100)).'%</span> '.
                (count($result)<count($all_terms)?'<a class="carbon-calculate carbon-calculator-complete" title="'.esc_attr(count($result).'/'.count($all_terms)).'" data-type="term" data-nonce="'.esc_attr($nonce).'" data-completed="'.esc_attr(count($result)).'" data-total="'.esc_attr(count($all_terms)).'" data-ids="'.esc_attr(implode(',', $terms)).'"><span>Complete</span></a>':'').
                (count($result)<count($all_terms) && count($result)?' | ':'').
                (count($result)?'<a class="carbon-calculate carbon-calculator-reset" data-type="term" data-nonce="'.esc_attr($nonce).'" data-id="'.esc_attr($taxonomy->name).'"><span>Reset</span></a>':'').
                '</li>';
        }
        echo '</ul>';
        echo '<br/>';

        echo '<label><b>Posts</b></label>';
        echo '<ul class="carbon-calculator-statistics">';

        foreach ($this->options['post_types']??[] as $post_type){

            $all_posts = get_posts(['post_type'=>$post_type,'posts_per_page'=>-1, 'fields'=>'ids']);

            if( !count($all_posts) )
                continue;

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
            $result = $wpdb->get_results($wpdb->prepare("SELECT DISTINCT `post_id` from `$wpdb->postmeta` WHERE `meta_key` = 'wpcc' AND `post_id` IN (".implode( ', ', array_fill( 0, count( $all_posts ), '%d' ) ).")", $all_posts));

            $result = array_map(function ($item){ return $item->post_id; }, $result);

            $posts = array_diff($all_posts, $result);

            $post_type = get_post_type_object($post_type);

            echo '<li>'.
                '<a href="'.esc_url(admin_url('edit.php?post_type='.$post_type->name)).'" class="dashicons-before '.esc_attr($post_type->menu_icon).'"> '.esc_html($post_type->label).'</a>'.
                ' : <span>'.esc_html(round(count($result)/count($all_posts)*100)).'%</span> '.
                (count($result)<count($all_posts)?'<a class="carbon-calculate carbon-calculator-complete" title="'.esc_attr(count($result).'/'.count($all_posts)).'" data-type="post" data-nonce="'.esc_attr($nonce).'" data-completed="'.esc_attr(count($result)).'" data-total="'.esc_attr(count($all_posts)).'" data-ids="'.esc_attr(implode(',', $posts)).'"><span>Complete</span></a>':'').
                (count($result)<count($all_posts) && count($result)?' | ':'').
                (count($result)?'<a class="carbon-calculate carbon-calculator-reset" data-type="post" data-nonce="'.esc_attr($nonce).'" data-id="'.esc_attr($post_type->name).'"><span>Reset</span></a>':'').
                '</li>';
        }
        echo '</ul>';
    }

    /**
     * Register and add settings
     */
    public function admin_menu()
    {
        add_submenu_page( 'tools.php', 'Carbon calculator','Carbon calculator', 'manage_options', 'carbon-calculator', function(){

            ?>
            <h1>Carbon calculator</h1>
            <table class="form-table carbon-calculator-tools" role="presentation">
                <tbody>
                <tr>
                    <th scope="row">
                        <h2>Statistics</h2>
                    </th>
                    <td>
                        <?php $this->getStatistics(); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <h2>Post types archive</h2>
                    </th>
                    <td class="carbon-calculators">
                        <?php
                        foreach ($this->options['post_types']??[] as $post_type){

                            $post_type = get_post_type_object($post_type);

                            if( !$post_type->has_archive )
                                continue;

                            $computation = get_option('wpcc_details::'.$post_type->name);
                            WPCC_Actions::display_calculator_form($computation, 'archive', $post_type->name);
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <h2>Other pages</h2>
                    </th>
                    <td class="carbon-calculators">
                        <?php
                        $computation = get_option('wpcc_details::search');
                        WPCC_Actions::display_calculator_form($computation, 'search', '');

                        $computation = get_option('wpcc_details::404');
                        WPCC_Actions::display_calculator_form($computation, '404', '');
                        ?>
                    </td>
                </tr>
                </tbody>
            </table>
            <?php
        });
    }
}
