(function(blocks, element, editor, components, i18n, blockEditor) {
    var el = element.createElement;
    var registerBlockType = blocks.registerBlockType;
    var InspectorControls = editor.InspectorControls;
    var PanelBody = components.PanelBody;
    var Placeholder = components.Placeholder;
    var SelectControl = components.SelectControl;
    var TextControl = components.TextControl;
    var __ = i18n.__;
    var InnerBlocks = blockEditor.InnerBlocks;

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
            reportData: {
                type: 'object',
                default: {}
            },
            isGenerated: {
                type: 'boolean',
                default: false
            }
        },

        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            var clientId = props.clientId;
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

            // Function to create block template from report data
            function createBlocksFromData(data) {
                var blocks = [];

                // Overview Section
                var overviewGroup = wp.blocks.createBlock('core/group', {
                    className: 'wp-101-report-section wp-101-report-overview'
                }, [
                    wp.blocks.createBlock('core/heading', {
                        level: 3,
                        content: __('Overall Progress', '101-wp')
                    }),
                    wp.blocks.createBlock('core/paragraph', {
                        content: '<strong>' + __('Total Items:', '101-wp') + '</strong> ' + data.overview.totalItems + '<br>' +
                            '<strong>' + __('Total Completed Items:', '101-wp') + '</strong> ' + data.overview.totalCompleted + ' (' + data.overview.totalCompletedPct + '%)<br>' +
                            '<strong>' + __('Total In-Progress Items:', '101-wp') + '</strong> ' + data.overview.totalInProgress + ' (' + data.overview.totalInProgressPct + '%)<br>' +
                            '<strong>' + __('Total Failed Items:', '101-wp') + '</strong> ' + data.overview.totalFailed + ' (' + data.overview.totalFailedPct + '%)'
                    })
                ]);
                blocks.push(overviewGroup);

                // Timeframe Section
                var timeframeGroup = wp.blocks.createBlock('core/group', {
                    className: 'wp-101-report-section wp-101-report-timeframe'
                }, [
                    wp.blocks.createBlock('core/heading', {
                        level: 3,
                        content: __('Timeframe', '101-wp')
                    }),
                    wp.blocks.createBlock('core/paragraph', {
                        content: '<strong>' + data.timeframe.startDate + '</strong> ' + __('to', '101-wp') + ' <strong>' + data.timeframe.endDate + '</strong><br>' +
                            '<strong>' + __('Timeframe Completed Items:', '101-wp') + '</strong> ' + data.timeframe.completedCount + ' (' + data.timeframe.completedPct + '%)<br>' +
                            '<strong>' + __('Timeframe In-Progress Items:', '101-wp') + '</strong> ' + data.timeframe.inProgressCount + ' (' + data.timeframe.inProgressPct + '%)<br>' +
                            '<strong>' + __('Timeframe Failed Items:', '101-wp') + '</strong> ' + data.timeframe.failedCount + ' (' + data.timeframe.failedPct + '%)'
                    })
                ]);
                blocks.push(timeframeGroup);

                // Completed Tasks
                if (data.completed && data.completed.length > 0) {
                    var completedInnerBlocks = [
                        wp.blocks.createBlock('core/heading', {
                            level: 3,
                            className: 'wp-101-category-title',
                            content: __('Completed Tasks', '101-wp')
                        })
                    ];

                    data.completed.forEach(function(item) {
                        var itemTitle = '✅ ' + item.title;

                        // Add progress counter for Simple Count tasks
                        if (item.tracking_mode === 'count') {
                            var current = item.current_count || 0;
                            var target = item.target_count || 1;
                            itemTitle += ' (' + current + '/' + target + ')';
                        }
                        // Add count info if it's a detailed list task
                        else if (item.subtasks && item.subtasks.length > 0) {
                            itemTitle += ' (' + item.subtasks.length + ' items)';
                        }

                        var itemBlocks = [
                            wp.blocks.createBlock('core/paragraph', {
                                content: itemTitle,
                                lock: { move: false, remove: false }
                            }),
                            wp.blocks.createBlock('core/paragraph', {
                                content: __('Add notes about this completed task here.', '101-wp'),
                                placeholder: __('Add notes about this completed task here.', '101-wp')
                            })
                        ];

                        // Add completion date if available
                        if (item.completion_date) {
                            var dateObj = new Date(item.completion_date);
                            var dateStr = dateObj.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
                            itemBlocks.push(wp.blocks.createBlock('core/paragraph', {
                                className: 'wp-101-report-date',
                                content: 'Completed: ' + dateStr,
                                lock: { move: false, remove: false }
                            }));
                        }

                        completedInnerBlocks.push(wp.blocks.createBlock('core/group', {
                            className: 'wp-101-report-item'
                        }, itemBlocks));
                    });

                    blocks.push(wp.blocks.createBlock('core/group', {
                        className: 'wp-101-report-section wp-101-report-completed'
                    }, completedInnerBlocks));
                }

                // In-Progress Tasks
                if (data.inProgress && data.inProgress.length > 0) {
                    var inProgressInnerBlocks = [
                        wp.blocks.createBlock('core/heading', {
                            level: 3,
                            className: 'wp-101-category-title',
                            content: __('In-Progress Tasks', '101-wp')
                        })
                    ];

                    data.inProgress.forEach(function(item) {
                        var itemTitle = '🔄 ' + item.title;

                        // Add progress counter for Simple Count tasks
                        if (item.tracking_mode === 'count') {
                            var current = item.current_count || 0;
                            var target = item.target_count || 1;
                            itemTitle += ' (' + current + '/' + target + ')';
                        }
                        // Add count info if it's a detailed list task
                        else if (item.subtasks && item.subtasks.length > 0) {
                            var completed = 0;
                            item.subtasks.forEach(function(subtask) {
                                if (subtask.completed) completed++;
                            });
                            itemTitle += ' (' + completed + '/' + item.subtasks.length + ')';
                        }

                        var itemBlocks = [
                            wp.blocks.createBlock('core/paragraph', {
                                content: itemTitle,
                                lock: { move: false, remove: false }
                            }),
                            wp.blocks.createBlock('core/paragraph', {
                                content: __('Add notes about this in-progress task here.', '101-wp'),
                                placeholder: __('Add notes about this in-progress task here.', '101-wp')
                            })
                        ];

                        // Add start date if available
                        if (item.start_date) {
                            var dateObj = new Date(item.start_date);
                            var dateStr = dateObj.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
                            itemBlocks.push(wp.blocks.createBlock('core/paragraph', {
                                className: 'wp-101-report-date',
                                content: 'Started: ' + dateStr,
                                lock: { move: false, remove: false }
                            }));
                        }

                        inProgressInnerBlocks.push(wp.blocks.createBlock('core/group', {
                            className: 'wp-101-report-item'
                        }, itemBlocks));
                    });

                    blocks.push(wp.blocks.createBlock('core/group', {
                        className: 'wp-101-report-section wp-101-report-in-progress'
                    }, inProgressInnerBlocks));
                }

                // Failed Tasks
                if (data.failed && data.failed.length > 0) {
                    var failedInnerBlocks = [
                        wp.blocks.createBlock('core/heading', {
                            level: 3,
                            className: 'wp-101-category-title',
                            content: __('Failed Tasks', '101-wp')
                        })
                    ];

                    data.failed.forEach(function(item) {
                        var itemBlocks = [
                            wp.blocks.createBlock('core/paragraph', {
                                content: '❌ ' + item.title,
                                lock: { move: false, remove: false }
                            }),
                            wp.blocks.createBlock('core/paragraph', {
                                content: __('Add notes about this failed task here.', '101-wp'),
                                placeholder: __('Add notes about this failed task here.', '101-wp')
                            })
                        ];

                        // Add fail date if available
                        if (item.fail_date) {
                            var dateObj = new Date(item.fail_date);
                            var dateStr = dateObj.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
                            itemBlocks.push(wp.blocks.createBlock('core/paragraph', {
                                className: 'wp-101-report-date',
                                content: 'Failed: ' + dateStr,
                                lock: { move: false, remove: false }
                            }));
                        }

                        failedInnerBlocks.push(wp.blocks.createBlock('core/group', {
                            className: 'wp-101-report-item'
                        }, itemBlocks));
                    });

                    blocks.push(wp.blocks.createBlock('core/group', {
                        className: 'wp-101-report-section wp-101-report-failed'
                    }, failedInnerBlocks));
                }

                return blocks;
            }

            // Function to generate report
            function generateReport() {
                console.log('[WP-101 Report] Generate button clicked');

                if (!attributes.listId || !attributes.startDate || !attributes.endDate) {
                    alert(__('Please select a list and enter both start and end dates.', '101-wp'));
                    return;
                }

                // Make AJAX call to generate report
                var formData = new FormData();
                formData.append('action', 'wp_101_generate_timeframe_report');
                formData.append('nonce', wp101Data.nonce);
                formData.append('listId', attributes.listId);
                formData.append('startDate', attributes.startDate);
                formData.append('endDate', attributes.endDate);

                fetch(wp101Data.ajaxUrl, {
                    method: 'POST',
                    body: formData
                })
                .then(function(response) {
                    return response.json();
                })
                .then(function(result) {
                    console.log('[WP-101 Report] Result:', result);
                    if (result.success && result.data && result.data.data) {
                        // Create blocks from the data
                        var newBlocks = createBlocksFromData(result.data.data);

                        // Insert the blocks
                        wp.data.dispatch('core/block-editor').replaceInnerBlocks(clientId, newBlocks);

                        // Update attributes
                        setAttributes({
                            reportData: result.data.data,
                            isGenerated: true
                        });

                        console.log('[WP-101 Report] Report generated successfully');
                    } else {
                        var errorMsg = 'Unknown error';
                        if (result.data && result.data.message) {
                            errorMsg = result.data.message;
                        }
                        console.error('[WP-101 Report] Error:', errorMsg);
                        alert(__('Error generating report: ', '101-wp') + errorMsg);
                    }
                })
                .catch(function(error) {
                    console.error('[WP-101 Report] Caught error:', error);
                    alert(__('Error generating report: ', '101-wp') + error.message);
                });
            }

            // If report is generated, show InnerBlocks with edit controls
            if (attributes.isGenerated) {
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
                                        reportData: {}
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
                                        reportData: {}
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
                                        reportData: {}
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
                        className: 'wp-101-timeframe-report'
                    },
                        el(InnerBlocks, {
                            templateLock: false
                        })
                    )
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

        save: function(props) {
            return el(InnerBlocks.Content);
        }
    });
})(
    window.wp.blocks,
    window.wp.element,
    window.wp.blockEditor || window.wp.editor,
    window.wp.components,
    window.wp.i18n,
    window.wp.blockEditor
);
