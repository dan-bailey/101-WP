<?php
/**
 * Register 101 List custom post type
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_101_Post_Type {

    /**
     * Initialize the class
     */
    public static function init() {
        add_action('init', [__CLASS__, 'register_post_type']);
    }

    /**
     * Register the 101 List post type
     */
    public static function register_post_type() {
        $labels = [
            'name'                  => _x('101 Lists', 'Post type general name', '101-wp'),
            'singular_name'         => _x('101 List', 'Post type singular name', '101-wp'),
            'menu_name'             => _x('101 Lists', 'Admin Menu text', '101-wp'),
            'name_admin_bar'        => _x('101 List', 'Add New on Toolbar', '101-wp'),
            'add_new'               => __('Add New', '101-wp'),
            'add_new_item'          => __('Add New 101 List', '101-wp'),
            'new_item'              => __('New 101 List', '101-wp'),
            'edit_item'             => __('Edit 101 List', '101-wp'),
            'view_item'             => __('View 101 List', '101-wp'),
            'all_items'             => __('All 101 Lists', '101-wp'),
            'search_items'          => __('Search 101 Lists', '101-wp'),
            'parent_item_colon'     => __('Parent 101 Lists:', '101-wp'),
            'not_found'             => __('No 101 lists found.', '101-wp'),
            'not_found_in_trash'    => __('No 101 lists found in Trash.', '101-wp'),
            'featured_image'        => _x('Cover Image', 'Overrides the "Featured Image" phrase', '101-wp'),
            'set_featured_image'    => _x('Set cover image', 'Overrides the "Set featured image" phrase', '101-wp'),
            'remove_featured_image' => _x('Remove cover image', 'Overrides the "Remove featured image" phrase', '101-wp'),
            'use_featured_image'    => _x('Use as cover image', 'Overrides the "Use as featured image" phrase', '101-wp'),
            'archives'              => _x('101 List archives', 'The post type archive label', '101-wp'),
            'insert_into_item'      => _x('Insert into 101 list', 'Overrides the "Insert into post" phrase', '101-wp'),
            'uploaded_to_this_item' => _x('Uploaded to this 101 list', 'Overrides the "Uploaded to this post" phrase', '101-wp'),
            'filter_items_list'     => _x('Filter 101 lists list', 'Screen reader text for the filter links', '101-wp'),
            'items_list_navigation' => _x('101 Lists list navigation', 'Screen reader text for the pagination', '101-wp'),
            'items_list'            => _x('101 Lists list', 'Screen reader text for the items list', '101-wp'),
        ];

        $args = [
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => ['slug' => '101-list'],
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => 20,
            'menu_icon'          => 'dashicons-list-view',
            'supports'           => ['title', 'editor', 'thumbnail'],
            'show_in_rest'       => true,
        ];

        register_post_type('wp_101_list', $args);
    }

    /**
     * Get the active 101 list
     *
     * @return WP_Post|null The active list post object or null
     */
    public static function get_active_list() {
        $args = [
            'post_type'      => 'wp_101_list',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'meta_query'     => [
                [
                    'key'     => '_wp_101_status',
                    'value'   => 'active',
                    'compare' => '='
                ]
            ]
        ];

        $query = new WP_Query($args);

        if ($query->have_posts()) {
            return $query->posts[0];
        }

        return null;
    }

    /**
     * Calculate end date (1001 days from publication)
     *
     * @param string $start_date The publication date
     * @return string The end date
     */
    public static function calculate_end_date($start_date) {
        $start = new DateTime($start_date);
        $start->modify('+1001 days');
        return $start->format('Y-m-d H:i:s');
    }

    /**
     * Check if a list is complete
     *
     * @param int $post_id The post ID
     * @return bool
     */
    public static function is_list_complete($post_id) {
        $status = get_post_meta($post_id, '_wp_101_status', true);
        return $status === 'complete';
    }
}
