<?php

class WCCTools{

    private $options;

    public function __construct() {

        if( (in_array($_SERVER['REMOTE_ADDR']??'127.0.0.1', ['127.0.0.1', '::1']) && !WCC_DEBUG) )
            return;

        $this->options = get_option('carbon_calculator');

        add_action( 'admin_menu', [$this, 'admin_menu'] );
    }

    public function getStatistics(){

        global $wpdb;

        echo '<label><b>Terms</b></label>';
        echo '<ul class="carbon-calculator-statistics">';
        foreach ($this->options['taxonomies']??[] as $taxonomy){

            $all_terms = get_terms(['taxonomy'=>$taxonomy, 'fields'=>'ids']);
            $result = $wpdb->get_results("SELECT DISTINCT `term_id` from `$wpdb->termmeta` WHERE `meta_key` = 'calculated_carbon' AND `term_id` IN (".implode(',', $all_terms).")");
            $result = array_map(function ($item){ return $item->term_id; }, $result);

            $terms = array_diff($all_terms, $result);

            $taxonomy = get_taxonomy($taxonomy);

            echo '<li>'.
                '<a href="'.admin_url('edit-tags.php?taxonomy='.$taxonomy->name).'" class="dashicons-before dashicons-category"> '.$taxonomy->label.'</a>'.
                ' : <span>'.(round(count($result)/count($all_terms)*100)).'%</span> '.
                (count($result)<count($all_terms)?'<a class="carbon-calculate carbon-calculator-complete" title="'.count($result).'/'.count($all_terms).'" data-type="term" data-completed="'.count($result).'" data-total="'.count($all_terms).'" data-ids="'.implode(',', $terms).'"><span>Complete</span></a>':'').
                (count($result)<count($all_terms) && count($result)?' | ':'').
                (count($result)?'<a class="carbon-calculate carbon-calculator-reset" data-type="term" data-id="'.$taxonomy->name.'"><span>Reset</span></a>':'').
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

            $result = $wpdb->get_results("SELECT DISTINCT `post_id` from `$wpdb->postmeta` WHERE `meta_key` = 'calculated_carbon' AND `post_id` IN (".implode(',', $all_posts).")");
            $result = array_map(function ($item){ return $item->post_id; }, $result);

            $posts = array_diff($all_posts, $result);

            $post_type = get_post_type_object($post_type);

            echo '<li>'.
                '<a href="'.admin_url('edit.php?post_type='.$post_type->name).'" class="dashicons-before '.$post_type->menu_icon.'"> '.$post_type->label.'</a>'.
                ' : <span>'.(round(count($result)/count($all_posts)*100)).'%</span> '.
                (count($result)<count($all_posts)?'<a class="carbon-calculate carbon-calculator-complete" title="'.count($result).'/'.count($all_posts).'" data-type="post" data-completed="'.count($result).'" data-total="'.count($all_posts).'" data-ids="'.implode(',', $posts).'"><span>Complete</span></a>':'').
                (count($result)<count($all_posts) && count($result)?' | ':'').
                (count($result)?'<a class="carbon-calculate carbon-calculator-reset" data-type="post" data-id="'.$post_type->name.'"><span>Reset</span></a>':'').
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

                            $computation = get_option($post_type->name . '::calculated_carbon_details');
                            WCCActions::display_calculator_form($computation, 'archive', $post_type->name);
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
                        $computation = get_option('search::calculated_carbon_details');
                        WCCActions::display_calculator_form($computation, 'search', '');

                        $computation = get_option('404::calculated_carbon_details');
                        WCCActions::display_calculator_form($computation, '404', '');
                        ?>
                    </td>
                </tr>
                </tbody>
            </table>
            <?php
        });
    }
}

new WCCTools();
