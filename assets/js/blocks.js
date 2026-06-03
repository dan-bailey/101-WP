(function(blocks, element, editor, components, i18n) {
    var el = element.createElement;
    var registerBlockType = blocks.registerBlockType;
    var InspectorControls = editor.InspectorControls;
    var PanelBody = components.PanelBody;
    var Placeholder = components.Placeholder;
    var SelectControl = components.SelectControl;
    var TextControl = components.TextControl;
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
        attributes: {
            listId: {
                type: 'number',
                default: 0
            },
            customTitle: {
                type: 'string',
                default: ''
            }
        },

        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            var allLists = wp101Data && wp101Data.allLists ? wp101Data.allLists : [];

            if (!allLists || allLists.length === 0) {
                return el(
                    Placeholder,
                    {
                        icon: 'chart-pie',
                        label: __('101 Current Progress', '101-wp'),
                        instructions: __('No published 101 lists found. Create and publish a 101 list to use this block.', '101-wp')
                    }
                );
            }

            // Build dropdown options
            var listOptions = [
                { value: 0, label: __('Active List (Auto)', '101-wp') }
            ].concat(allLists);

            // Get data for preview
            var data = wp101Data && wp101Data.activeListData;
            var hasActiveList = wp101Data && wp101Data.hasActiveList;

            // Determine display title
            var displayTitle = attributes.customTitle || (data ? data.title : '');

            var percentage = data && data.totalItems > 0
                ? Math.round((data.statusCounts.complete / data.totalItems) * 100)
                : 0;

            return [
                el(InspectorControls, { key: 'inspector' },
                    el(PanelBody, { title: __('Block Settings', '101-wp'), initialOpen: true },
                        el(SelectControl, {
                            label: __('Select 101 List', '101-wp'),
                            value: attributes.listId,
                            options: listOptions,
                            onChange: function(value) {
                                setAttributes({ listId: parseInt(value) });
                            }
                        }),
                        el(TextControl, {
                            label: __('Custom Title (optional)', '101-wp'),
                            value: attributes.customTitle,
                            onChange: function(value) {
                                setAttributes({ customTitle: value });
                            },
                            help: __('Leave empty to use the list title', '101-wp')
                        })
                    )
                ),
                el('div', {
                    key: 'block',
                    className: 'wp-101-progress-block wp-101-editor-preview'
                },
                    el('h3', { className: 'wp-101-progress-title' }, displayTitle),
                    el('div', { className: 'wp-101-chart-placeholder' },
                        el('div', { className: 'wp-101-chart-icon' }, '📊'),
                        el('p', {}, __('Chart will display on the frontend', '101-wp'))
                    ),
                    el('div', { className: 'wp-101-progress-stats' },
                        el('div', { className: 'wp-101-stat' },
                            el('span', { className: 'wp-101-stat-label' }, __('Total Items:', '101-wp')),
                            el('span', { className: 'wp-101-stat-value' }, data ? data.totalItems : 0)
                        ),
                        el('div', { className: 'wp-101-stat' },
                            el('span', { className: 'wp-101-stat-label' }, __('Complete:', '101-wp')),
                            el('span', { className: 'wp-101-stat-value' }, data ? data.statusCounts.complete : 0)
                        ),
                        el('div', { className: 'wp-101-stat' },
                            el('span', { className: 'wp-101-stat-label' }, __('Progress:', '101-wp')),
                            el('span', { className: 'wp-101-stat-value' }, percentage + '%')
                        )
                    ),
                    el('div', { className: 'wp-101-status-breakdown' },
                        el('div', { className: 'wp-101-status-item' },
                            el('span', { className: 'wp-101-status-dot', style: { backgroundColor: '#e5e7eb' } }),
                            el('span', {}, __('Not Started:', '101-wp') + ' ' + (data ? data.statusCounts.not_started : 0))
                        ),
                        el('div', { className: 'wp-101-status-item' },
                            el('span', { className: 'wp-101-status-dot', style: { backgroundColor: '#3b82f6' } }),
                            el('span', {}, __('Underway:', '101-wp') + ' ' + (data ? data.statusCounts.underway : 0))
                        ),
                        el('div', { className: 'wp-101-status-item' },
                            el('span', { className: 'wp-101-status-dot', style: { backgroundColor: '#10b981' } }),
                            el('span', {}, __('Complete:', '101-wp') + ' ' + (data ? data.statusCounts.complete : 0))
                        ),
                        el('div', { className: 'wp-101-status-item' },
                            el('span', { className: 'wp-101-status-dot', style: { backgroundColor: '#ef4444' } }),
                            el('span', {}, __('Failed:', '101-wp') + ' ' + (data ? data.statusCounts.failed : 0))
                        )
                    ),
                    el('div', { className: 'wp-101-view-list-button-wrapper' },
                        el('span', { className: 'wp-101-view-list-button' }, __('View List', '101-wp'))
                    )
                )
            ];
        },

        save: function() {
            // Dynamic block, rendered server-side
            return null;
        }
    });

    // Register Timeframe Report Block
    registerBlockType('wp-101/timeframe-report', {
        title: __('101 Timeframe Report', '101-wp'),
        description: __('Generate a snapshot report for a specific timeframe of the 101 Things list', '101-wp'),
        icon: 'analytics',
        category: 'widgets',
        keywords: [__('101', '101-wp'), __('report', '101-wp'), __('timeframe', '101-wp')],
        supports: {
            html: false,
            align: ['wide', 'full']
        },
        attributes: {
            listId: {
                type: 'number',
                default: 0
            },
            startDate: {
                type: 'string',
                default: ''
            },
            endDate: {
                type: 'string',
                default: ''
            },
            reportContent: {
                type: 'string',
                default: ''
            },
            isGenerated: {
                type: 'boolean',
                default: false
            }
        },

        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            var allLists = wp101Data && wp101Data.allLists ? wp101Data.allLists : [];

            if (!allLists || allLists.length === 0) {
                return el(
                    Placeholder,
                    {
                        icon: 'analytics',
                        label: __('101 Timeframe Report', '101-wp'),
                        instructions: __('No published 101 lists found. Create and publish a 101 list to use this block.', '101-wp')
                    }
                );
            }

            // Build dropdown options
            var listOptions = [
                { value: 0, label: __('Select a 101 List', '101-wp') }
            ].concat(allLists);

            // Function to generate report
            function generateReport() {
                if (!attributes.listId || !attributes.startDate || !attributes.endDate) {
                    alert(__('Please select a list and enter both start and end dates.', '101-wp'));
                    return;
                }

                // Make AJAX call to generate report
                var data = new FormData();
                data.append('action', 'wp_101_generate_timeframe_report');
                data.append('nonce', wp101Data.nonce);
                data.append('listId', attributes.listId);
                data.append('startDate', attributes.startDate);
                data.append('endDate', attributes.endDate);

                fetch(wp101Data.ajaxUrl, {
                    method: 'POST',
                    body: data
                })
                .then(function(response) {
                    return response.json();
                })
                .then(function(result) {
                    if (result.success) {
                        setAttributes({
                            reportContent: result.data.html,
                            isGenerated: true
                        });
                    } else {
                        alert(__('Error generating report: ', '101-wp') + (result.data.message || 'Unknown error'));
                    }
                })
                .catch(function(error) {
                    alert(__('Error generating report: ', '101-wp') + error.message);
                });
            }

            // If report is generated, show it with edit controls
            if (attributes.isGenerated && attributes.reportContent) {
                return [
                    el(InspectorControls, { key: 'inspector' },
                        el(PanelBody, { title: __('Report Settings', '101-wp'), initialOpen: true },
                            el(SelectControl, {
                                label: __('Select 101 List', '101-wp'),
                                value: attributes.listId,
                                options: listOptions,
                                onChange: function(value) {
                                    setAttributes({
                                        listId: parseInt(value),
                                        isGenerated: false,
                                        reportContent: ''
                                    });
                                }
                            }),
                            el(TextControl, {
                                label: __('Start Date', '101-wp'),
                                type: 'date',
                                value: attributes.startDate,
                                onChange: function(value) {
                                    setAttributes({
                                        startDate: value,
                                        isGenerated: false,
                                        reportContent: ''
                                    });
                                }
                            }),
                            el(TextControl, {
                                label: __('End Date', '101-wp'),
                                type: 'date',
                                value: attributes.endDate,
                                onChange: function(value) {
                                    setAttributes({
                                        endDate: value,
                                        isGenerated: false,
                                        reportContent: ''
                                    });
                                }
                            }),
                            el(components.Button, {
                                isPrimary: true,
                                onClick: generateReport
                            }, __('Regenerate Report', '101-wp'))
                        )
                    ),
                    el('div', {
                        key: 'block',
                        className: 'wp-101-timeframe-report',
                        dangerouslySetInnerHTML: { __html: attributes.reportContent }
                    })
                ];
            }

            // Show configuration UI
            return [
                el(InspectorControls, { key: 'inspector' },
                    el(PanelBody, { title: __('Report Settings', '101-wp'), initialOpen: true },
                        el(SelectControl, {
                            label: __('Select 101 List', '101-wp'),
                            value: attributes.listId,
                            options: listOptions,
                            onChange: function(value) {
                                setAttributes({ listId: parseInt(value) });
                            }
                        }),
                        el(TextControl, {
                            label: __('Start Date', '101-wp'),
                            type: 'date',
                            value: attributes.startDate,
                            onChange: function(value) {
                                setAttributes({ startDate: value });
                            }
                        }),
                        el(TextControl, {
                            label: __('End Date', '101-wp'),
                            type: 'date',
                            value: attributes.endDate,
                            onChange: function(value) {
                                setAttributes({ endDate: value });
                            }
                        }),
                        el(components.Button, {
                            isPrimary: true,
                            onClick: generateReport
                        }, __('Generate Report', '101-wp'))
                    )
                ),
                el(
                    Placeholder,
                    {
                        key: 'placeholder',
                        icon: 'analytics',
                        label: __('101 Timeframe Report', '101-wp'),
                        instructions: __('Configure the report settings in the sidebar and click "Generate Report".', '101-wp')
                    },
                    el('div', { style: { marginTop: '20px' } },
                        el(SelectControl, {
                            label: __('Select 101 List', '101-wp'),
                            value: attributes.listId,
                            options: listOptions,
                            onChange: function(value) {
                                setAttributes({ listId: parseInt(value) });
                            }
                        }),
                        el(TextControl, {
                            label: __('Start Date', '101-wp'),
                            type: 'date',
                            value: attributes.startDate,
                            onChange: function(value) {
                                setAttributes({ startDate: value });
                            }
                        }),
                        el(TextControl, {
                            label: __('End Date', '101-wp'),
                            type: 'date',
                            value: attributes.endDate,
                            onChange: function(value) {
                                setAttributes({ endDate: value });
                            }
                        }),
                        el(components.Button, {
                            isPrimary: true,
                            onClick: generateReport,
                            style: { marginTop: '10px' }
                        }, __('Generate Report', '101-wp'))
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
