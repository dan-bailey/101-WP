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
            'attributes' => []
        ]);
    }

    /**
     * Enqueue block editor assets
     */
    public static function enqueue_block_editor_assets() {
        wp_enqueue_script(
            'wp-101-blocks-editor',
            WP_101_PLUGIN_URL . 'assets/js/blocks.js',
            ['wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n'],
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
            'activeListData' => self::get_active_list_data()
        ]);
    }

    /**
     * Render the Current Progress block
     */
    public static function render_current_progress_block($attributes) {
        $active_list = WP_101_Post_Type::get_active_list();

        if (!$active_list) {
            return '<div class="wp-101-progress-block wp-101-no-active-list"><p>' .
                   __('No active 101 list found.', '101-wp') . '</p></div>';
        }

        $items = get_post_meta($active_list->ID, '_wp_101_items', true);

        if (!is_array($items) || empty($items)) {
            return '<div class="wp-101-progress-block wp-101-no-items"><p>' .
                   __('No items in the active list yet.', '101-wp') . '</p></div>';
        }

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

        // Enqueue eCharts
        wp_enqueue_script(
            'echarts',
            'https://cdn.jsdelivr.net/npm/echarts@5.4.3/dist/echarts.min.js',
            [],
            '5.4.3',
            true
        );

        wp_enqueue_style(
            'wp-101-blocks-style',
            WP_101_PLUGIN_URL . 'assets/css/blocks.css',
            [],
            WP_101_VERSION
        );

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
                <?php echo esc_html($active_list->post_title); ?>
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
        </div>
        <script>
        (function() {
            function initChart() {
                if (typeof echarts === 'undefined') {
                    setTimeout(initChart, 100);
                    return;
                }

                var chartDom = document.getElementById('<?php echo $chart_id; ?>');
                if (!chartDom) return;

                var myChart = echarts.init(chartDom);
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
                        data: <?php echo json_encode($chart_data); ?>
                    }]
                };

                myChart.setOption(option);

                // Resize on window resize
                window.addEventListener('resize', function() {
                    myChart.resize();
                });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initChart);
            } else {
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
            'title' => $active_list->post_title,
            'totalItems' => is_array($items) ? count($items) : 0,
            'statusCounts' => $status_counts
        ];
    }
}
