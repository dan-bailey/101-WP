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
            'allLists' => self::get_all_lists()
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
