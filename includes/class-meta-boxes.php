<?php
/**
 * Meta boxes for 101 List items
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_101_Meta_Boxes {

    /**
     * Initialize the class
     */
    public static function init() {
        add_action('add_meta_boxes', [__CLASS__, 'add_meta_boxes']);
        add_action('save_post_wp_101_list', [__CLASS__, 'save_meta_boxes'], 10, 2);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin_scripts']);
    }

    /**
     * Add meta boxes
     */
    public static function add_meta_boxes() {
        add_meta_box(
            'wp_101_items',
            __('101 Items', '101-wp'),
            [__CLASS__, 'render_items_meta_box'],
            'wp_101_list',
            'normal',
            'high'
        );

        add_meta_box(
            'wp_101_info',
            __('List Information', '101-wp'),
            [__CLASS__, 'render_info_meta_box'],
            'wp_101_list',
            'side',
            'default'
        );
    }

    /**
     * Render the items meta box
     */
    public static function render_items_meta_box($post) {
        wp_nonce_field('wp_101_save_items', 'wp_101_items_nonce');

        $items = get_post_meta($post->ID, '_wp_101_items', true);
        $is_complete = WP_101_Post_Type::is_list_complete($post->ID);

        if (!is_array($items)) {
            $items = [];
        }

        ?>
        <div id="wp-101-items-wrapper" <?php echo $is_complete ? 'class="wp-101-disabled"' : ''; ?>>
            <?php if ($is_complete): ?>
                <div class="notice notice-warning inline">
                    <p><?php _e('This list is complete and cannot be modified.', '101-wp'); ?></p>
                </div>
            <?php endif; ?>

            <div id="wp-101-items-container">
                <?php
                if (!empty($items)) {
                    foreach ($items as $index => $item) {
                        self::render_item_accordion($index, $item, $is_complete);
                    }
                }
                ?>
            </div>

            <?php if (!$is_complete): ?>
                <div class="wp-101-add-item-wrapper">
                    <button type="button" class="button button-secondary wp-101-add-item">
                        <?php _e('+ Add Item', '101-wp'); ?>
                    </button>
                    <span class="wp-101-item-count">
                        <?php
                        printf(
                            __('Items: <strong>%d / 101</strong>', '101-wp'),
                            count($items)
                        );
                        ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>

        <script type="text/template" id="wp-101-item-template">
            <?php self::render_item_accordion('{{INDEX}}', [], false); ?>
        </script>
        <?php
    }

    /**
     * Render a single item accordion
     */
    private static function render_item_accordion($index, $item = [], $disabled = false) {
        $defaults = [
            'title' => '',
            'status' => 'not_started',
            'category' => '',
            'content' => '',
            'tracking_mode' => 'simple',
            'target_count' => 1,
            'current_count' => 0,
            'sub_items' => [],
            'completion_date' => ''
        ];

        $item = wp_parse_args($item, $defaults);
        $disabled_attr = $disabled ? 'disabled' : '';
        ?>
        <div class="wp-101-item" data-index="<?php echo esc_attr($index); ?>">
            <div class="wp-101-item-header">
                <span class="wp-101-item-number">#<?php echo esc_html($index === '{{INDEX}}' ? '' : $index + 1); ?></span>
                <span class="wp-101-item-title-preview"><?php echo esc_html($item['title'] ?: __('New Item', '101-wp')); ?></span>
                <span class="wp-101-item-status-badge wp-101-status-<?php echo esc_attr($item['status']); ?>">
                    <?php echo esc_html(self::get_status_label($item['status'])); ?>
                </span>
                <?php if (!$disabled): ?>
                    <button type="button" class="wp-101-toggle-item dashicons dashicons-arrow-down-alt2"></button>
                    <button type="button" class="wp-101-remove-item dashicons dashicons-trash" title="<?php esc_attr_e('Remove Item', '101-wp'); ?>"></button>
                <?php endif; ?>
            </div>

            <div class="wp-101-item-content" style="display: none;">
                <table class="form-table">
                    <tr>
                        <th><label><?php _e('Title', '101-wp'); ?></label></th>
                        <td>
                            <input type="text"
                                   name="wp_101_items[<?php echo esc_attr($index); ?>][title]"
                                   value="<?php echo esc_attr($item['title']); ?>"
                                   class="widefat wp-101-item-title-input"
                                   <?php echo $disabled_attr; ?>
                                   required />
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Status', '101-wp'); ?></label></th>
                        <td>
                            <select name="wp_101_items[<?php echo esc_attr($index); ?>][status]"
                                    class="wp-101-item-status"
                                    <?php echo $disabled_attr; ?>>
                                <option value="not_started" <?php selected($item['status'], 'not_started'); ?>>◻️ <?php _e('Not Started', '101-wp'); ?></option>
                                <option value="underway" <?php selected($item['status'], 'underway'); ?>>🔁 <?php _e('Underway', '101-wp'); ?></option>
                                <option value="complete" <?php selected($item['status'], 'complete'); ?>>✅ <?php _e('Complete', '101-wp'); ?></option>
                                <option value="failed" <?php selected($item['status'], 'failed'); ?>>❌ <?php _e('Failed', '101-wp'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Category', '101-wp'); ?></label></th>
                        <td>
                            <input type="text"
                                   name="wp_101_items[<?php echo esc_attr($index); ?>][category]"
                                   value="<?php echo esc_attr($item['category']); ?>"
                                   class="widefat"
                                   <?php echo $disabled_attr; ?> />
                            <p class="description"><?php _e('User-defined category for organizing items', '101-wp'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Tracking Mode', '101-wp'); ?></label></th>
                        <td>
                            <select name="wp_101_items[<?php echo esc_attr($index); ?>][tracking_mode]"
                                    class="wp-101-tracking-mode"
                                    <?php echo $disabled_attr; ?>>
                                <option value="simple" <?php selected($item['tracking_mode'], 'simple'); ?>><?php _e('Simple Count', '101-wp'); ?></option>
                                <option value="detailed" <?php selected($item['tracking_mode'], 'detailed'); ?>><?php _e('Detailed List', '101-wp'); ?></option>
                            </select>
                            <p class="description"><?php _e('Simple: track by count only. Detailed: list individual sub-items.', '101-wp'); ?></p>
                        </td>
                    </tr>
                    <tr class="wp-101-simple-mode" <?php echo $item['tracking_mode'] === 'detailed' ? 'style="display:none;"' : ''; ?>>
                        <th><label><?php _e('Progress', '101-wp'); ?></label></th>
                        <td>
                            <input type="number"
                                   name="wp_101_items[<?php echo esc_attr($index); ?>][current_count]"
                                   value="<?php echo esc_attr($item['current_count']); ?>"
                                   min="0"
                                   max="<?php echo esc_attr($item['target_count']); ?>"
                                   <?php echo $disabled_attr; ?> />
                            /
                            <input type="number"
                                   name="wp_101_items[<?php echo esc_attr($index); ?>][target_count]"
                                   value="<?php echo esc_attr($item['target_count']); ?>"
                                   min="1"
                                   <?php echo $disabled_attr; ?> />
                        </td>
                    </tr>
                    <tr class="wp-101-detailed-mode" <?php echo $item['tracking_mode'] === 'simple' ? 'style="display:none;"' : ''; ?>>
                        <th><label><?php _e('Sub-Items', '101-wp'); ?></label></th>
                        <td>
                            <div class="wp-101-sub-items" data-item-index="<?php echo esc_attr($index); ?>">
                                <?php
                                if (!empty($item['sub_items'])) {
                                    foreach ($item['sub_items'] as $sub_index => $sub_item) {
                                        self::render_sub_item($index, $sub_index, $sub_item, $disabled);
                                    }
                                }
                                ?>
                            </div>
                            <?php if (!$disabled): ?>
                                <button type="button" class="button button-small wp-101-add-sub-item">
                                    <?php _e('+ Add Sub-Item', '101-wp'); ?>
                                </button>
                            <?php endif; ?>
                            <input type="hidden"
                                   name="wp_101_items[<?php echo esc_attr($index); ?>][target_count]"
                                   value="<?php echo esc_attr($item['target_count']); ?>"
                                   class="wp-101-target-count" />
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Content', '101-wp'); ?></label></th>
                        <td>
                            <textarea name="wp_101_items[<?php echo esc_attr($index); ?>][content]"
                                      rows="4"
                                      class="widefat"
                                      <?php echo $disabled_attr; ?>><?php echo esc_textarea($item['content']); ?></textarea>
                        </td>
                    </tr>
                    <?php if (!empty($item['completion_date'])): ?>
                    <tr>
                        <th><label><?php _e('Completion Date', '101-wp'); ?></label></th>
                        <td>
                            <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($item['completion_date']))); ?>
                            <input type="hidden"
                                   name="wp_101_items[<?php echo esc_attr($index); ?>][completion_date]"
                                   value="<?php echo esc_attr($item['completion_date']); ?>" />
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * Render a sub-item
     */
    private static function render_sub_item($item_index, $sub_index, $sub_item = [], $disabled = false) {
        $defaults = [
            'title' => '',
            'completed' => false,
            'date' => ''
        ];

        $sub_item = wp_parse_args($sub_item, $defaults);
        $disabled_attr = $disabled ? 'disabled' : '';
        ?>
        <div class="wp-101-sub-item">
            <input type="checkbox"
                   name="wp_101_items[<?php echo esc_attr($item_index); ?>][sub_items][<?php echo esc_attr($sub_index); ?>][completed]"
                   value="1"
                   <?php checked($sub_item['completed'], true); ?>
                   <?php echo $disabled_attr; ?> />
            <input type="text"
                   name="wp_101_items[<?php echo esc_attr($item_index); ?>][sub_items][<?php echo esc_attr($sub_index); ?>][title]"
                   value="<?php echo esc_attr($sub_item['title']); ?>"
                   placeholder="<?php esc_attr_e('Sub-item title', '101-wp'); ?>"
                   class="widefat"
                   <?php echo $disabled_attr; ?> />
            <?php if (!empty($sub_item['date'])): ?>
                <span class="wp-101-sub-item-date"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($sub_item['date']))); ?></span>
            <?php endif; ?>
            <input type="hidden"
                   name="wp_101_items[<?php echo esc_attr($item_index); ?>][sub_items][<?php echo esc_attr($sub_index); ?>][date]"
                   value="<?php echo esc_attr($sub_item['date']); ?>" />
            <?php if (!$disabled): ?>
                <button type="button" class="button button-small wp-101-remove-sub-item">×</button>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render the info meta box
     */
    public static function render_info_meta_box($post) {
        $start_date = $post->post_date;
        $end_date = WP_101_Post_Type::calculate_end_date($start_date);
        $status = get_post_meta($post->ID, '_wp_101_status', true);
        $items = get_post_meta($post->ID, '_wp_101_items', true);

        if (!$status) {
            $status = 'active';
        }

        $completed_count = 0;
        if (is_array($items)) {
            foreach ($items as $item) {
                if ($item['status'] === 'complete') {
                    $completed_count++;
                }
            }
        }

        ?>
        <div class="wp-101-info">
            <p>
                <strong><?php _e('Start Date:', '101-wp'); ?></strong><br>
                <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($start_date))); ?>
            </p>
            <p>
                <strong><?php _e('End Date:', '101-wp'); ?></strong><br>
                <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($end_date))); ?>
                (<?php echo esc_html(self::get_days_remaining($end_date)); ?>)
            </p>
            <p>
                <strong><?php _e('Status:', '101-wp'); ?></strong><br>
                <span class="wp-101-list-status-<?php echo esc_attr($status); ?>">
                    <?php echo esc_html(ucfirst($status)); ?>
                </span>
            </p>
            <p>
                <strong><?php _e('Progress:', '101-wp'); ?></strong><br>
                <?php printf(__('%d of %d items completed', '101-wp'), $completed_count, count($items ?: [])); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Get days remaining
     */
    private static function get_days_remaining($end_date) {
        $now = new DateTime();
        $end = new DateTime($end_date);
        $diff = $now->diff($end);

        if ($diff->invert) {
            return __('Ended', '101-wp');
        }

        return sprintf(_n('%d day remaining', '%d days remaining', $diff->days, '101-wp'), $diff->days);
    }

    /**
     * Get status label
     */
    private static function get_status_label($status) {
        $labels = [
            'not_started' => __('Not Started', '101-wp'),
            'underway' => __('Underway', '101-wp'),
            'complete' => __('Complete', '101-wp'),
            'failed' => __('Failed', '101-wp')
        ];

        return isset($labels[$status]) ? $labels[$status] : $status;
    }

    /**
     * Save meta boxes
     */
    public static function save_meta_boxes($post_id, $post) {
        // Check nonce
        if (!isset($_POST['wp_101_items_nonce']) || !wp_verify_nonce($_POST['wp_101_items_nonce'], 'wp_101_save_items')) {
            return;
        }

        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Don't save if list is complete
        if (WP_101_Post_Type::is_list_complete($post_id)) {
            return;
        }

        // Save items
        if (isset($_POST['wp_101_items'])) {
            $items = [];

            foreach ($_POST['wp_101_items'] as $index => $item) {
                // Sanitize item data
                $sanitized_item = [
                    'title' => sanitize_text_field($item['title']),
                    'status' => sanitize_text_field($item['status']),
                    'category' => sanitize_text_field($item['category']),
                    'content' => wp_kses_post($item['content']),
                    'tracking_mode' => sanitize_text_field($item['tracking_mode']),
                    'target_count' => intval($item['target_count']),
                    'current_count' => isset($item['current_count']) ? intval($item['current_count']) : 0,
                    'sub_items' => [],
                    'completion_date' => isset($item['completion_date']) ? sanitize_text_field($item['completion_date']) : ''
                ];

                // Handle status change to complete
                if ($sanitized_item['status'] === 'complete' && empty($sanitized_item['completion_date'])) {
                    $sanitized_item['completion_date'] = current_time('mysql');
                }

                // Handle sub-items
                if (isset($item['sub_items']) && is_array($item['sub_items'])) {
                    foreach ($item['sub_items'] as $sub_index => $sub_item) {
                        $completed = isset($sub_item['completed']) && $sub_item['completed'] === '1';
                        $date = isset($sub_item['date']) ? sanitize_text_field($sub_item['date']) : '';

                        // Set date when first completed
                        if ($completed && empty($date)) {
                            $date = current_time('mysql');
                        }

                        $sanitized_item['sub_items'][] = [
                            'title' => sanitize_text_field($sub_item['title']),
                            'completed' => $completed,
                            'date' => $date
                        ];
                    }

                    // Update target count based on sub-items count
                    $sanitized_item['target_count'] = count($sanitized_item['sub_items']);
                }

                $items[] = $sanitized_item;
            }

            update_post_meta($post_id, '_wp_101_items', $items);

            // Update list status
            self::update_list_status($post_id, $items, $post->post_date);
        }
    }

    /**
     * Update list status
     */
    private static function update_list_status($post_id, $items, $start_date) {
        $end_date = WP_101_Post_Type::calculate_end_date($start_date);
        $now = current_time('mysql');

        // Check if past end date
        if ($now > $end_date) {
            update_post_meta($post_id, '_wp_101_status', 'complete');
            return;
        }

        // Check if all items are complete
        $all_complete = true;
        foreach ($items as $item) {
            if ($item['status'] !== 'complete') {
                $all_complete = false;
                break;
            }
        }

        if ($all_complete && count($items) > 0) {
            update_post_meta($post_id, '_wp_101_status', 'complete');
        } else {
            update_post_meta($post_id, '_wp_101_status', 'active');
        }
    }

    /**
     * Enqueue admin scripts
     */
    public static function enqueue_admin_scripts($hook) {
        global $post_type;

        if (('post.php' === $hook || 'post-new.php' === $hook) && 'wp_101_list' === $post_type) {
            wp_enqueue_style(
                'wp-101-admin',
                WP_101_PLUGIN_URL . 'assets/css/admin.css',
                [],
                WP_101_VERSION
            );

            wp_enqueue_script(
                'wp-101-admin',
                WP_101_PLUGIN_URL . 'assets/js/admin.js',
                ['jquery'],
                WP_101_VERSION,
                true
            );
        }
    }
}
