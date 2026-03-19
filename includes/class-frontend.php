<?php
/**
 * Frontend display for 101 Lists
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_101_Frontend {

    /**
     * Initialize the class
     */
    public static function init() {
        add_filter('the_content', [__CLASS__, 'display_list_content']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_frontend_scripts']);
    }

    /**
     * Display list content
     */
    public static function display_list_content($content) {
        if (!is_singular('wp_101_list')) {
            return $content;
        }

        global $post;

        $items = get_post_meta($post->ID, '_wp_101_items', true);

        if (!is_array($items) || empty($items)) {
            return $content;
        }

        // Organize items by category
        $items_by_category = self::organize_items_by_category($items);

        // Build HTML
        $html = '<div class="wp-101-list">';

        // Display original content first
        if (!empty($content)) {
            $html .= '<div class="wp-101-intro">' . $content . '</div>';
        }

        // Display items by category
        foreach ($items_by_category as $category => $category_items) {
            $html .= '<div class="wp-101-category">';
            $html .= '<h2 class="wp-101-category-title">' . esc_html($category) . '</h2>';
            $html .= '<div class="wp-101-items">';

            foreach ($category_items as $item) {
                $html .= self::render_item($item);
            }

            $html .= '</div>'; // .wp-101-items
            $html .= '</div>'; // .wp-101-category
        }

        $html .= '</div>'; // .wp-101-list

        return $html;
    }

    /**
     * Organize items by category and status
     */
    private static function organize_items_by_category($items) {
        $organized = [];

        // First, group by category
        foreach ($items as $item) {
            // Get category name from term ID
            $category_name = __('Uncategorized', '101-wp');
            if (!empty($item['category'])) {
                $term = get_term($item['category'], 'wp_101_item_category');
                if ($term && !is_wp_error($term)) {
                    $category_name = $term->name;
                }
            }

            if (!isset($organized[$category_name])) {
                $organized[$category_name] = [
                    'underway' => [],
                    'not_started' => [],
                    'complete' => [],
                    'failed' => []
                ];
            }

            $status = $item['status'];
            $organized[$category_name][$status][] = $item;
        }

        // Now flatten each category with proper ordering
        $result = [];
        foreach ($organized as $category => $statuses) {
            $result[$category] = array_merge(
                $statuses['underway'],
                $statuses['not_started'],
                $statuses['complete'],
                $statuses['failed']
            );
        }

        return $result;
    }

    /**
     * Render a single item
     */
    private static function render_item($item) {
        $status_icons = [
            'not_started' => '◻️',
            'underway' => '🔁',
            'complete' => '✅',
            'failed' => '❌'
        ];

        $status_icon = isset($status_icons[$item['status']]) ? $status_icons[$item['status']] : '';
        $status_class = 'wp-101-status-' . esc_attr($item['status']);

        $html = '<div class="wp-101-item ' . $status_class . '">';

        // Title with status icon
        $html .= '<div class="wp-101-item-header">';
        $html .= '<span class="wp-101-status-icon">' . $status_icon . '</span>';
        $html .= '<h3 class="wp-101-item-title">' . esc_html($item['title']) . '</h3>';

        // Progress indicator
        if ($item['tracking_mode'] === 'simple' && $item['target_count'] > 1) {
            $html .= '<span class="wp-101-progress">';
            $html .= sprintf('(%d/%d)', $item['current_count'], $item['target_count']);
            $html .= '</span>';
        } elseif ($item['tracking_mode'] === 'detailed' && !empty($item['sub_items'])) {
            $completed_count = count(array_filter($item['sub_items'], function($sub) {
                return $sub['completed'];
            }));
            $html .= '<span class="wp-101-progress">';
            $html .= sprintf('(%d/%d)', $completed_count, count($item['sub_items']));
            $html .= '</span>';
        }

        $html .= '</div>'; // .wp-101-item-header

        // Content
        if (!empty($item['content'])) {
            $html .= '<div class="wp-101-item-content">';
            $html .= wpautop(wp_kses_post($item['content']));
            $html .= '</div>';
        }

        // Detailed sub-items list
        if ($item['tracking_mode'] === 'detailed' && !empty($item['sub_items'])) {
            $html .= '<ul class="wp-101-sub-items">';
            foreach ($item['sub_items'] as $sub_item) {
                $sub_class = $sub_item['completed'] ? 'completed' : 'incomplete';
                $sub_icon = $sub_item['completed'] ? '✅' : '◻️';

                $html .= '<li class="wp-101-sub-item ' . $sub_class . '">';
                $html .= '<span class="wp-101-sub-icon">' . $sub_icon . '</span>';
                $html .= '<span class="wp-101-sub-title">' . esc_html($sub_item['title']) . '</span>';

                if ($sub_item['completed'] && !empty($sub_item['date'])) {
                    $html .= '<span class="wp-101-sub-date">';
                    $html .= date_i18n(get_option('date_format'), strtotime($sub_item['date']));
                    $html .= '</span>';
                }

                $html .= '</li>';
            }
            $html .= '</ul>';
        }

        // Completion date
        if ($item['status'] === 'complete' && !empty($item['completion_date'])) {
            $html .= '<div class="wp-101-completion-date">';
            $html .= sprintf(
                __('Completed: %s', '101-wp'),
                date_i18n(get_option('date_format'), strtotime($item['completion_date']))
            );
            $html .= '</div>';
        }

        $html .= '</div>'; // .wp-101-item

        return $html;
    }

    /**
     * Enqueue frontend scripts
     */
    public static function enqueue_frontend_scripts() {
        if (is_singular('wp_101_list')) {
            wp_enqueue_style(
                'wp-101-frontend',
                WP_101_PLUGIN_URL . 'assets/css/frontend.css',
                [],
                WP_101_VERSION
            );
        }
    }
}
