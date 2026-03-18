(function(blocks, element, editor, components, i18n) {
    var el = element.createElement;
    var registerBlockType = blocks.registerBlockType;
    var InspectorControls = editor.InspectorControls;
    var PanelBody = components.PanelBody;
    var Placeholder = components.Placeholder;
    var __ = i18n.__;

    registerBlockType('wp-101/current-progress', {
        title: __('101 Current Progress', '101-wp'),
        description: __('Display progress chart for the active 101 Things list', '101-wp'),
        icon: 'chart-pie',
        category: 'widgets',
        keywords: [__('101', '101-wp'), __('progress', '101-wp'), __('chart', '101-wp')],
        supports: {
            html: false,
            align: ['wide', 'full']
        },

        edit: function(props) {
            var hasActiveList = wp101Data && wp101Data.hasActiveList;
            var data = wp101Data && wp101Data.activeListData;

            if (!hasActiveList || !data) {
                return el(
                    Placeholder,
                    {
                        icon: 'chart-pie',
                        label: __('101 Current Progress', '101-wp'),
                        instructions: __('No active 101 list found. Create and publish a 101 list to use this block.', '101-wp')
                    }
                );
            }

            var percentage = data.totalItems > 0
                ? Math.round((data.statusCounts.complete / data.totalItems) * 100)
                : 0;

            return [
                el(InspectorControls, { key: 'inspector' },
                    el(PanelBody, { title: __('Block Settings', '101-wp'), initialOpen: true },
                        el('p', {}, __('This block automatically displays the current active 101 list.', '101-wp'))
                    )
                ),
                el('div', {
                    key: 'block',
                    className: 'wp-101-progress-block wp-101-editor-preview'
                },
                    el('h3', { className: 'wp-101-progress-title' }, data.title),
                    el('div', { className: 'wp-101-chart-placeholder' },
                        el('div', { className: 'wp-101-chart-icon' }, '📊'),
                        el('p', {}, __('Chart will display on the frontend', '101-wp'))
                    ),
                    el('div', { className: 'wp-101-progress-stats' },
                        el('div', { className: 'wp-101-stat' },
                            el('span', { className: 'wp-101-stat-label' }, __('Total Items:', '101-wp')),
                            el('span', { className: 'wp-101-stat-value' }, data.totalItems)
                        ),
                        el('div', { className: 'wp-101-stat' },
                            el('span', { className: 'wp-101-stat-label' }, __('Complete:', '101-wp')),
                            el('span', { className: 'wp-101-stat-value' }, data.statusCounts.complete)
                        ),
                        el('div', { className: 'wp-101-stat' },
                            el('span', { className: 'wp-101-stat-label' }, __('Progress:', '101-wp')),
                            el('span', { className: 'wp-101-stat-value' }, percentage + '%')
                        )
                    ),
                    el('div', { className: 'wp-101-status-breakdown' },
                        el('div', { className: 'wp-101-status-item' },
                            el('span', { className: 'wp-101-status-dot', style: { backgroundColor: '#e5e7eb' } }),
                            el('span', {}, __('Not Started:', '101-wp') + ' ' + data.statusCounts.not_started)
                        ),
                        el('div', { className: 'wp-101-status-item' },
                            el('span', { className: 'wp-101-status-dot', style: { backgroundColor: '#3b82f6' } }),
                            el('span', {}, __('Underway:', '101-wp') + ' ' + data.statusCounts.underway)
                        ),
                        el('div', { className: 'wp-101-status-item' },
                            el('span', { className: 'wp-101-status-dot', style: { backgroundColor: '#10b981' } }),
                            el('span', {}, __('Complete:', '101-wp') + ' ' + data.statusCounts.complete)
                        ),
                        el('div', { className: 'wp-101-status-item' },
                            el('span', { className: 'wp-101-status-dot', style: { backgroundColor: '#ef4444' } }),
                            el('span', {}, __('Failed:', '101-wp') + ' ' + data.statusCounts.failed)
                        )
                    )
                )
            ];
        },

        save: function() {
            // Dynamic block, rendered server-side
            return null;
        }
    });
})(
    window.wp.blocks,
    window.wp.element,
    window.wp.blockEditor || window.wp.editor,
    window.wp.components,
    window.wp.i18n
);
