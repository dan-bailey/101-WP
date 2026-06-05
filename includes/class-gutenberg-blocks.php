<?php
/**
 * Gutenberg blocks for 101-WP
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_101_Gutenberg_Blocks {

    /**
     * Initialize the class
     */
    public static function init() {
        add_action('init', [__CLASS__, 'register_blocks']);
        add_action('enqueue_block_editor_assets', [__CLASS__, 'enqueue_block_editor_assets']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_frontend_assets']);
        add_action('wp_ajax_wp_101_generate_timeframe_report', [__CLASS__, 'ajax_generate_timeframe_report']);
    }

    /**
     * Register blocks
     */
    public static function register_blocks() {
        // Register the Current Progress block
        register_block_type('wp-101/current-progress', [
            'editor_script' => 'wp-101-blocks-editor',
            'editor_style' => 'wp-101-blocks-editor-style',
            'style' => 'wp-101-blocks-style',
            'render_callback' => [__CLASS__, 'render_current_progress_block'],
            'attributes' => [
                'listId' => [
                    'type' => 'number',
                    'default' => 0
                ],
                'customTitle' => [
                    'type' => 'string',
                    'default' => ''
                ]
            ]
        ]);

        // Register the Timeframe Report block
        register_block_type('wp-101/timeframe-report', [
            'editor_script' => 'wp-101-blocks-editor',
            'editor_style' => 'wp-101-blocks-editor-style',
            'style' => 'wp-101-blocks-style',
            'render_callback' => [__CLASS__, 'render_timeframe_report_block'],
            'attributes' => [
                'listId' => [
                    'type' => 'number',
                    'default' => 0
                ],
                'startDate' => [
                    'type' => 'string',
                    'default' => ''
                ],
                'endDate' => [
                    'type' => 'string',
                    'default' => ''
                ],
                'reportContent' => [
                    'type' => 'string',
                    'default' => ''
                ],
                'isGenerated' => [
                    'type' => 'boolean',
                    'default' => false
                ]
            ]
        ]);
    }

    /**
     * Enqueue block editor assets
     */
    public static function enqueue_block_editor_assets() {
        wp_enqueue_script(
            'wp-101-blocks-editor',
            WP_101_PLUGIN_URL . 'assets/js/blocks.js',
            ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n'],
            WP_101_VERSION,
            true
        );

        wp_enqueue_style(
            'wp-101-blocks-editor-style',
            WP_101_PLUGIN_URL . 'assets/css/blocks-editor.css',
            ['wp-edit-blocks'],
            WP_101_VERSION
        );

        // Pass data to JavaScript
        wp_localize_script('wp-101-blocks-editor', 'wp101Data', [
            'hasActiveList' => self::has_active_list(),
            'activeListData' => self::get_active_list_data(),
            'allLists' => self::get_all_lists(),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_101_timeframe_report')
        ]);
    }

    /**
     * Enqueue frontend assets
     */
    public static function enqueue_frontend_assets() {
        // Always enqueue eCharts - it's needed for the progress block
        wp_enqueue_script(
            'echarts',
            'https://cdn.jsdelivr.net/npm/echarts@6.0.0/dist/echarts.min.js',
            [],
            '6.0.0',
            true
        );

        wp_enqueue_style(
            'wp-101-blocks-style',
            WP_101_PLUGIN_URL . 'assets/css/blocks.css',
            [],
            WP_101_VERSION
        );
    }

    /**
     * Render the Current Progress block
     */
    public static function render_current_progress_block($attributes) {
        // Get the list to display
        $list_id = isset($attributes['listId']) ? intval($attributes['listId']) : 0;

        if ($list_id > 0) {
            $list = get_post($list_id);
            if (!$list || $list->post_type !== 'wp_101_list') {
                $list = null;
            }
        } else {
            // Default to active list if no specific list selected
            $list = WP_101_Post_Type::get_active_list();
        }

        if (!$list) {
            return '<div class="wp-101-progress-block wp-101-no-active-list"><p>' .
                   __('No 101 list found.', '101-wp') . '</p></div>';
        }

        $items = get_post_meta($list->ID, '_wp_101_items', true);

        if (!is_array($items) || empty($items)) {
            return '<div class="wp-101-progress-block wp-101-no-items"><p>' .
                   __('No items in the list yet.', '101-wp') . '</p></div>';
        }

        // Determine the title to display
        $custom_title = isset($attributes['customTitle']) ? trim($attributes['customTitle']) : '';
        $display_title = !empty($custom_title) ? $custom_title : $list->post_title;

        // Count items by status
        $status_counts = [
            'not_started' => 0,
            'underway' => 0,
            'complete' => 0,
            'failed' => 0
        ];

        foreach ($items as $item) {
            if (isset($status_counts[$item['status']])) {
                $status_counts[$item['status']]++;
            }
        }

        // Generate unique ID for this chart
        $chart_id = 'wp-101-chart-' . uniqid();

        // Build the chart initialization script
        $chart_data = [
            [
                'value' => $status_counts['not_started'],
                'name' => __('Not Started', '101-wp'),
                'itemStyle' => ['color' => '#e5e7eb']
            ],
            [
                'value' => $status_counts['underway'],
                'name' => __('Underway', '101-wp'),
                'itemStyle' => ['color' => '#3b82f6']
            ],
            [
                'value' => $status_counts['complete'],
                'name' => __('Complete', '101-wp'),
                'itemStyle' => ['color' => '#10b981']
            ],
            [
                'value' => $status_counts['failed'],
                'name' => __('Failed', '101-wp'),
                'itemStyle' => ['color' => '#ef4444']
            ]
        ];

        // Build HTML
        ob_start();
        ?>
        <div class="wp-101-progress-block">
            <h3 class="wp-101-progress-title">
                <?php echo esc_html($display_title); ?>
            </h3>
            <div id="<?php echo esc_attr($chart_id); ?>" class="wp-101-chart"></div>
            <div class="wp-101-progress-stats">
                <div class="wp-101-stat">
                    <span class="wp-101-stat-label"><?php _e('Total Items:', '101-wp'); ?></span>
                    <span class="wp-101-stat-value"><?php echo count($items); ?></span>
                </div>
                <div class="wp-101-stat">
                    <span class="wp-101-stat-label"><?php _e('Complete:', '101-wp'); ?></span>
                    <span class="wp-101-stat-value"><?php echo $status_counts['complete']; ?></span>
                </div>
                <div class="wp-101-stat">
                    <span class="wp-101-stat-label"><?php _e('Progress:', '101-wp'); ?></span>
                    <span class="wp-101-stat-value">
                        <?php
                        $percentage = count($items) > 0 ? round(($status_counts['complete'] / count($items)) * 100) : 0;
                        echo $percentage . '%';
                        ?>
                    </span>
                </div>
            </div>
            <div class="wp-101-view-list-button-wrapper">
                <a href="<?php echo esc_url(get_permalink($list->ID)); ?>" class="wp-101-view-list-button">
                    <?php _e('View List', '101-wp'); ?>
                </a>
            </div>
        </div>
        <script>
        (function() {
            console.log('[WP-101] Initializing chart script for <?php echo $chart_id; ?>');

            var attempts = 0;
            var maxAttempts = 50; // 5 seconds max

            function initChart() {
                attempts++;
                console.log('[WP-101] Chart init attempt #' + attempts);

                if (typeof echarts === 'undefined') {
                    console.log('[WP-101] eCharts not loaded yet, waiting...');
                    if (attempts < maxAttempts) {
                        setTimeout(initChart, 100);
                    } else {
                        console.error('[WP-101] eCharts failed to load after ' + maxAttempts + ' attempts (5 seconds)');
                    }
                    return;
                }

                console.log('[WP-101] eCharts loaded successfully! Version:', echarts.version);

                var chartDom = document.getElementById('<?php echo $chart_id; ?>');
                if (!chartDom) {
                    console.error('[WP-101] Chart container not found: <?php echo $chart_id; ?>');
                    return;
                }

                console.log('[WP-101] Chart container found:', chartDom);

                var myChart = echarts.init(chartDom);
                console.log('[WP-101] eCharts instance created');

                var option = {
                    tooltip: {
                        trigger: 'item',
                        formatter: '{b}: {c} ({d}%)'
                    },
                    legend: {
                        orient: 'horizontal',
                        bottom: 0
                    },
                    series: [{
                        name: '<?php _e('Status', '101-wp'); ?>',
                        type: 'pie',
                        radius: ['40%', '70%'],
                        avoidLabelOverlap: false,
                        label: {
                            show: false
                        },
                        emphasis: {
                            label: {
                                show: true,
                                fontSize: 16,
                                fontWeight: 'bold'
                            }
                        },
                        labelLine: {
                            show: false
                        },
                        data: <?php echo wp_json_encode($chart_data); ?>
                    }]
                };

                myChart.setOption(option);
                console.log('[WP-101] Chart options set successfully with data:', <?php echo wp_json_encode($chart_data); ?>);

                // Resize on window resize
                window.addEventListener('resize', function() {
                    myChart.resize();
                    console.log('[WP-101] Chart resized');
                });

                console.log('[WP-101] Chart initialization complete!');
            }

            if (document.readyState === 'loading') {
                console.log('[WP-101] Document still loading, waiting for DOMContentLoaded');
                document.addEventListener('DOMContentLoaded', initChart);
            } else {
                console.log('[WP-101] Document already loaded, initializing immediately');
                initChart();
            }
        })();
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Render the Timeframe Report block
     */
    public static function render_timeframe_report_block($attributes) {
        // If report content exists, just return it
        if (!empty($attributes['reportContent'])) {
            return '<div class="wp-101-timeframe-report">' . $attributes['reportContent'] . '</div>';
        }

        // Otherwise, show a placeholder
        return '<div class="wp-101-timeframe-report wp-101-no-report"><p>' .
               __('Configure and generate the timeframe report in the editor.', '101-wp') . '</p></div>';
    }

    /**
     * Generate timeframe report content
     * This is called from JavaScript during block configuration
     */
    public static function generate_timeframe_report($list_id, $start_date, $end_date) {
        $list = get_post($list_id);
        if (!$list || $list->post_type !== 'wp_101_list') {
            return '';
        }

        $items = get_post_meta($list->ID, '_wp_101_items', true);
        if (!is_array($items) || empty($items)) {
            return '';
        }

        // Filter items by completion date within timeframe
        $start = strtotime($start_date);
        $end = strtotime($end_date);

        $timeframe_completed = [];
        $timeframe_in_progress = [];
        $timeframe_failed = [];

        $total_completed = 0;
        $total_in_progress = 0;
        $total_failed = 0;
        $total_not_started = 0;

        foreach ($items as $item) {
            // Count totals (only top-level items, no subtasks)
            switch ($item['status']) {
                case 'complete':
                    $total_completed++;
                    break;
                case 'underway':
                    $total_in_progress++;
                    break;
                case 'failed':
                    $total_failed++;
                    break;
                case 'not_started':
                    $total_not_started++;
                    break;
            }

            // Check if item falls within timeframe
            if (!empty($item['completion_date'])) {
                $completion_time = strtotime($item['completion_date']);
                if ($completion_time >= $start && $completion_time <= $end) {
                    if ($item['status'] === 'complete') {
                        $timeframe_completed[] = $item;
                    } elseif ($item['status'] === 'failed') {
                        $timeframe_failed[] = $item;
                    }
                }
            }

            // Also include in-progress items that were updated in timeframe
            if ($item['status'] === 'underway') {
                // For now, include all underway items
                // TODO: Add logic to check if item was updated in timeframe
                $timeframe_in_progress[] = $item;
            }
        }

        $total_items = count($items);
        $total_completed_pct = $total_items > 0 ? round(($total_completed / $total_items) * 100) : 0;
        $total_in_progress_pct = $total_items > 0 ? round(($total_in_progress / $total_items) * 100) : 0;
        $total_failed_pct = $total_items > 0 ? round(($total_failed / $total_items) * 100) : 0;

        $tf_completed_count = count($timeframe_completed);
        $tf_in_progress_count = count($timeframe_in_progress);
        $tf_failed_count = count($timeframe_failed);

        $tf_completed_pct = $total_items > 0 ? round(($tf_completed_count / $total_items) * 100) : 0;
        $tf_in_progress_pct = $total_items > 0 ? round(($tf_in_progress_count / $total_items) * 100) : 0;
        $tf_failed_pct = $total_items > 0 ? round(($tf_failed_count / $total_items) * 100) : 0;

        // Build the report HTML
        $html = '';

        // Overview Section
        $html .= '<div class="wp-101-report-section wp-101-report-overview">';
        $html .= '<h3>' . __('Overall Progress', '101-wp') . '</h3>';
        $html .= '<p>';
        $html .= '<strong>' . __('Total Items:', '101-wp') . '</strong> ' . $total_items . '<br>';
        $html .= '<strong>' . __('Total Completed Items:', '101-wp') . '</strong> ' . $total_completed . ' (' . $total_completed_pct . '%)<br>';
        $html .= '<strong>' . __('Total In-Progress Items:', '101-wp') . '</strong> ' . $total_in_progress . ' (' . $total_in_progress_pct . '%)<br>';
        $html .= '<strong>' . __('Total Failed Items:', '101-wp') . '</strong> ' . $total_failed . ' (' . $total_failed_pct . '%)';
        $html .= '</p>';
        $html .= '</div>';

        // Timeframe Section
        $html .= '<div class="wp-101-report-section wp-101-report-timeframe">';
        $html .= '<h3 contenteditable="true">' . __('Timeframe', '101-wp') . '</h3>';
        $html .= '<p>';
        $html .= '<strong>' . date_i18n('F j, Y', $start) . '</strong> ' . __('to', '101-wp') . ' <strong>' . date_i18n('F j, Y', $end) . '</strong><br>';
        $html .= '<strong>' . __('Timeframe Completed Items:', '101-wp') . '</strong> ' . $tf_completed_count . ' (' . $tf_completed_pct . '%)<br>';
        $html .= '<strong>' . __('Timeframe In-Progress Items:', '101-wp') . '</strong> ' . $tf_in_progress_count . ' (' . $tf_in_progress_pct . '%)<br>';
        $html .= '<strong>' . __('Timeframe Failed Items:', '101-wp') . '</strong> ' . $tf_failed_count . ' (' . $tf_failed_pct . '%)';
        $html .= '</p>';
        $html .= '</div>';

        // Completed Tasks
        if (!empty($timeframe_completed)) {
            $html .= '<div class="wp-101-report-section wp-101-report-completed">';
            $html .= '<h3 class="wp-101-category-title" contenteditable="true">' . __('Completed Tasks', '101-wp') . '</h3>';
            foreach ($timeframe_completed as $item) {
                $html .= '<div class="wp-101-report-item" contenteditable="true">';
                $html .= '<p>✅ ' . esc_html($item['title']) . '</p>';

                // Add progress counter for Simple Count tasks
                if (isset($item['tracking_mode']) && $item['tracking_mode'] === 'count') {
                    $current = isset($item['current_count']) ? intval($item['current_count']) : 0;
                    $target = isset($item['target_count']) ? intval($item['target_count']) : 1;
                    $html .= ' (' . $current . '/' . $target . ')';
                }
                // Add count info if it's a detailed list task
                elseif (isset($item['subtasks']) && is_array($item['subtasks'])) {
                    $subtask_count = count($item['subtasks']);
                    if ($subtask_count > 0) {
                        $html .= ' (' . $subtask_count . ' items)';
                    }
                }

                $html .= '</p>';
                $html .= '<p>' . __('Space here for user editable text to talk about the task completion.', '101-wp') . '</p>';
                $html .= '</div>';
            }
            $html .= '</div>';
        }

        // In-Progress Tasks
        if (!empty($timeframe_in_progress)) {
            $html .= '<div class="wp-101-report-section wp-101-report-in-progress">';
            $html .= '<h3 class="wp-101-category-title" contenteditable="true">' . __('In-Progress Tasks', '101-wp') . '</h3>';
            foreach ($timeframe_in_progress as $item) {
                $html .= '<div class="wp-101-report-item" contenteditable="true">';
                $html .= '<p>🔄 ' . esc_html($item['title']) . '</p>';

                // Add progress counter for Simple Count tasks
                if (isset($item['tracking_mode']) && $item['tracking_mode'] === 'count') {
                    $current = isset($item['current_count']) ? intval($item['current_count']) : 0;
                    $target = isset($item['target_count']) ? intval($item['target_count']) : 1;
                    $html .= ' (' . $current . '/' . $target . ')';
                }
                // Add count info if it's a detailed list task
                elseif (isset($item['subtasks']) && is_array($item['subtasks'])) {
                    $completed = 0;
                    foreach ($item['subtasks'] as $subtask) {
                        if ($subtask['completed']) {
                            $completed++;
                        }
                    }
                    $total = count($item['subtasks']);
                    $html .= ' (' . $completed . '/' . $total . ')';
                }

                $html .= '</p>';
                $html .= '<p>' . __('Space here for user editable text to talk about the task in-progress.', '101-wp') . '</p>';
                $html .= '</div>';
            }
            $html .= '</div>';
        }

        // Failed Tasks
        if (!empty($timeframe_failed)) {
            $html .= '<div class="wp-101-report-section wp-101-report-failed">';
            $html .= '<h3 class="wp-101-category-title" contenteditable="true">' . __('Failed Tasks', '101-wp') . '</h3>';
            foreach ($timeframe_failed as $item) {
                $html .= '<div class="wp-101-report-item" contenteditable="true">';
                $html .= '<p>❌ ' . esc_html($item['title']) . '</p>';

                // Add progress counter for Simple Count tasks
                if (isset($item['tracking_mode']) && $item['tracking_mode'] === 'count') {
                    $current = isset($item['current_count']) ? intval($item['current_count']) : 0;
                    $target = isset($item['target_count']) ? intval($item['target_count']) : 1;
                    $html .= ' (' . $current . '/' . $target . ')';
                }

                $html .= '</p>';
                $html .= '<p>' . __('Space here for user editable text to talk about the failed task.', '101-wp') . '</p>';
                $html .= '</div>';
            }
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * Check if there's an active list
     */
    private static function has_active_list() {
        return WP_101_Post_Type::get_active_list() !== null;
    }

    /**
     * Get active list data for editor
     */
    private static function get_active_list_data() {
        $active_list = WP_101_Post_Type::get_active_list();

        if (!$active_list) {
            return null;
        }

        $items = get_post_meta($active_list->ID, '_wp_101_items', true);

        $status_counts = [
            'not_started' => 0,
            'underway' => 0,
            'complete' => 0,
            'failed' => 0
        ];

        if (is_array($items)) {
            foreach ($items as $item) {
                if (isset($status_counts[$item['status']])) {
                    $status_counts[$item['status']]++;
                }
            }
        }

        return [
            'title' => esc_js($active_list->post_title),
            'totalItems' => is_array($items) ? count($items) : 0,
            'statusCounts' => $status_counts
        ];
    }

    /**
     * Get all published 101 lists for dropdown
     */
    private static function get_all_lists() {
        $lists = get_posts([
            'post_type' => 'wp_101_list',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC'
        ]);

        $formatted_lists = [];
        foreach ($lists as $list) {
            $formatted_lists[] = [
                'value' => $list->ID,
                'label' => esc_js($list->post_title)
            ];
        }

        return $formatted_lists;
    }

    /**
     * AJAX handler for generating timeframe report
     */
    public static function ajax_generate_timeframe_report() {
        check_ajax_referer('wp_101_timeframe_report', 'nonce');

        $list_id = isset($_POST['listId']) ? intval($_POST['listId']) : 0;
        $start_date = isset($_POST['startDate']) ? sanitize_text_field($_POST['startDate']) : '';
        $end_date = isset($_POST['endDate']) ? sanitize_text_field($_POST['endDate']) : '';

        if (!$list_id || !$start_date || !$end_date) {
            wp_send_json_error(['message' => 'Missing required parameters']);
            return;
        }

        $html = self::generate_timeframe_report($list_id, $start_date, $end_date);

        wp_send_json_success(['html' => $html]);
    }

    /**
     * Add integrity attribute to eCharts CDN script
     */
    public static function add_echarts_integrity($tag, $handle) {
        if ($handle === 'echarts') {
            // Add SRI hash and crossorigin attribute for CDN security
            $tag = str_replace(
                ' src=',
                ' integrity="sha384-ovnTTVxK2au429glRSILIHFiONHAF1PSmucWRQc/Z9nH2O0NrUcbf9yRqpK7K8LE" crossorigin="anonymous" src=',
                $tag
            );
        }
        return $tag;
    }
}
