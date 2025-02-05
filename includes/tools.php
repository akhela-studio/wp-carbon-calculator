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

        if( !empty($this->options['taxonomies']??[]) ) {

            ?>
            <div class="postbox wpcc-postbox wpcc-postbox-statistics">
                <h2>Terms</h2>
                <div class="inside">
                    <ul class="carbon-calculator-statistics">
                        <?php
                        foreach ($this->options['taxonomies'] ?? [] as $taxonomy) {

                            $all_terms = get_terms(['taxonomy' => $taxonomy, 'fields' => 'ids']);

                            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
                            $result = $wpdb->get_results($wpdb->prepare("SELECT DISTINCT `term_id` from `$wpdb->termmeta` WHERE `meta_key` = 'wpcc' AND `term_id` IN (" . implode(', ', array_fill(0, count($all_terms), '%d')) . ")", $all_terms));

                            $result = array_map(function ($item) {
                                return $item->term_id;
                            }, $result);

                            $terms = array_diff($all_terms, $result);

                            $taxonomy = get_taxonomy($taxonomy);

                            echo '<li>' .
                                '<a href="' . esc_url(admin_url('edit-tags.php?taxonomy=' . $taxonomy->name)) . '" class="dashicons-before dashicons-category"> ' . esc_html($taxonomy->label) . '</a>' .
                                ' : <span>' . esc_html(round(count($result) / count($all_terms) * 100)) . '%</span> ' .
                                (count($result) < count($all_terms) ? '<a class="carbon-calculate carbon-calculator-complete button button-primary" title="' . esc_attr(count($result) . '/' . count($all_terms)) . '" data-type="term" data-nonce="' . esc_attr($nonce) . '" data-completed="' . esc_attr(count($result)) . '" data-total="' . esc_attr(count($all_terms)) . '" data-ids="' . esc_attr(implode(',', $terms)) . '"><span>Complete</span></a>' : '') .
                                (count($result) ? '<a class="carbon-calculate carbon-calculator-reset button button-secondary" data-type="term" data-nonce="' . esc_attr($nonce) . '" data-id="' . esc_attr($taxonomy->name) . '"><span>Reset</span></a>' : '') .
                                '</li>';
                        }
                        ?>
                    </ul>
                </div>
            </div>
            <?php
        }

        if( !empty($this->options['post_types']??[]) ) {

            ?>
            <div class="postbox wpcc-postbox wpcc-postbox-statistics">
                <h2>Posts</h2>
                <div class="inside">
                    <ul class="carbon-calculator-statistics">
                        <?php
                        foreach ($this->options['post_types'] ?? [] as $post_type) {

                            $all_posts = get_posts(['post_type' => $post_type, 'posts_per_page' => -1, 'fields' => 'ids']);

                            if (!count($all_posts))
                                continue;

                            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
                            $result = $wpdb->get_results($wpdb->prepare("SELECT DISTINCT `post_id` from `$wpdb->postmeta` WHERE `meta_key` = 'wpcc' AND `post_id` IN (" . implode(', ', array_fill(0, count($all_posts), '%d')) . ")", $all_posts));

                            $result = array_map(function ($item) {
                                return $item->post_id;
                            }, $result);

                            $posts = array_diff($all_posts, $result);

                            $post_type = get_post_type_object($post_type);

                            echo '<li>' .
                                '<a href="' . esc_url(admin_url('edit.php?post_type=' . $post_type->name)) . '" class="dashicons-before ' . esc_attr($post_type->menu_icon) . '"> ' . esc_html($post_type->label) . '</a>' .
                                ' : <span>' . esc_html(round(count($result) / count($all_posts) * 100)) . '%</span> ' .
                                (count($result) < count($all_posts) ? '<a class="carbon-calculate carbon-calculator-complete button button-primary" title="' . esc_attr(count($result) . '/' . count($all_posts)) . '" data-type="post" data-nonce="' . esc_attr($nonce) . '" data-completed="' . esc_attr(count($result)) . '" data-total="' . esc_attr(count($all_posts)) . '" data-ids="' . esc_attr(implode(',', $posts)) . '"><span>Complete</span></a>' : '') .
                                (count($result) ? '<a class="carbon-calculate carbon-calculator-reset button button-secondary" data-type="post" data-nonce="' . esc_attr($nonce) . '" data-id="' . esc_attr($post_type->name) . '"><span>Reset</span></a>' : '') .
                                '</li>';
                        }
                        ?>
                    </ul>
                </div>
            </div>
            <?php
        }
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
                <?php if( !empty($this->options['post_types']??[]) ) : ?>
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

                                $url = get_post_type_archive_link($post_type->name);
                                ?>
                                <div class="postbox wpcc-postbox">
                                    <h2><a href="<?php echo esc_url($url); ?>" target="_blank"> <?php echo esc_html($post_type->label); ?></a></h2>
                                    <div class="inside">
                                        <?php
                                        $computation = get_option('wpcc_details::'.$post_type->name);
                                        WPCC_Helper::display_calculator_form($computation, 'archive', $post_type->name);
                                        ?>
                                    </div>
                                </div>
                                <?php
                            }
                            ?>
                        </td>
                    </tr>
                <?php endif; ?>
                <tr>
                    <th scope="row">
                        <h2>Other pages</h2>
                    </th>
                    <td class="carbon-calculators">
                        <div class="postbox wpcc-postbox">
                            <h2><a href="<?php echo esc_url(get_search_link()); ?>" target="_blank"> Search</a></h2>
                            <div class="inside">
                                <?php
                                $computation = get_option('wpcc_details::search');
                                WPCC_Helper::display_calculator_form($computation, 'search', '');
                                ?>
                            </div>
                        </div>
                        <div class="postbox wpcc-postbox">
                            <h2><a href="<?php echo esc_url(get_home_url().'/not-found'); ?>" target="_blank"> 404</a></h2>
                            <div class="inside">
                                <?php
                                $computation = get_option('wpcc_details::404');
                                WPCC_Helper::display_calculator_form($computation, '404', '');
                                ?>
                            </div>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
            <?php
        });
    }
}
