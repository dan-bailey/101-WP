jQuery(document).ready(function($) {
    let itemIndex = $('.wp-101-item').length;

    // Toggle accordion
    $(document).on('click', '.wp-101-toggle-item', function(e) {
        e.preventDefault();
        const $item = $(this).closest('.wp-101-item');
        const $content = $item.find('.wp-101-item-content');

        $content.slideToggle(200);
        $(this).toggleClass('dashicons-arrow-down-alt2 dashicons-arrow-up-alt2');
    });

    // Add new item
    $('.wp-101-add-item').on('click', function(e) {
        e.preventDefault();

        const currentCount = $('.wp-101-item').length;
        if (currentCount >= 101) {
            alert('Maximum of 101 items reached.');
            return;
        }

        const template = $('#wp-101-item-template').html();
        const newItem = template.replace(/\{\{INDEX\}\}/g, itemIndex);

        $('#wp-101-items-container').append(newItem);

        // Open the newly added item's accordion
        const $newItem = $('#wp-101-items-container .wp-101-item').last();
        $newItem.find('.wp-101-item-content').show();
        $newItem.find('.wp-101-toggle-item').removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');

        itemIndex++;

        updateItemCount();
        reindexItems();
    });

    // Bulk add items - show modal
    $('.wp-101-bulk-add-items').on('click', function(e) {
        e.preventDefault();
        $('#wp-101-bulk-add-modal').fadeIn(200);
    });

    // Bulk add items - cancel
    $('.wp-101-bulk-add-cancel').on('click', function(e) {
        e.preventDefault();
        $('#wp-101-bulk-add-modal').fadeOut(200);
    });

    // Bulk add items - confirm
    $('.wp-101-bulk-add-confirm').on('click', function(e) {
        e.preventDefault();

        const category = $('#wp-101-bulk-category').val();
        const count = parseInt($('#wp-101-bulk-count').val(), 10);
        const currentCount = $('.wp-101-item').length;

        if (!count || count < 1) {
            alert('Please enter a valid number of items.');
            return;
        }

        if (currentCount + count > 101) {
            alert('Adding ' + count + ' items would exceed the maximum of 101 items. You can add ' + (101 - currentCount) + ' more items.');
            return;
        }

        const template = $('#wp-101-item-template').html();

        // Add multiple items
        for (let i = 0; i < count; i++) {
            const newItem = template.replace(/\{\{INDEX\}\}/g, itemIndex);
            const $newItem = $(newItem);

            // Set the category if selected
            if (category) {
                $newItem.find('select[name*="[category]"]').val(category);
                // Update the category badge
                const categoryText = $('#wp-101-bulk-category option:selected').text();
                $newItem.find('.wp-101-item-category-badge').text(categoryText);
                $newItem.attr('data-category-id', category);
            }

            $('#wp-101-items-container').append($newItem);
            itemIndex++;
        }

        updateItemCount();
        reindexItems();

        // Close modal and reset
        $('#wp-101-bulk-add-modal').fadeOut(200);
        $('#wp-101-bulk-count').val(5);
        $('#wp-101-bulk-category').val('');
    });

    // Close modal on background click
    $('#wp-101-bulk-add-modal').on('click', function(e) {
        if ($(e.target).is('#wp-101-bulk-add-modal')) {
            $(this).fadeOut(200);
        }
    });

    // Remove item
    $(document).on('click', '.wp-101-remove-item', function(e) {
        e.preventDefault();

        if (confirm('Are you sure you want to remove this item?')) {
            $(this).closest('.wp-101-item').remove();
            updateItemCount();
            reindexItems();
        }
    });

    // Update item title in header
    $(document).on('input', '.wp-101-item-title-input', function() {
        const title = $(this).val() || 'New Item';
        $(this).closest('.wp-101-item').find('.wp-101-item-title-preview').text(title);
    });

    // Update status badge
    $(document).on('change', '.wp-101-item-status', function() {
        const status = $(this).val();
        const $badge = $(this).closest('.wp-101-item').find('.wp-101-item-status-badge');

        $badge.removeClass('wp-101-status-not_started wp-101-status-underway wp-101-status-complete wp-101-status-failed');
        $badge.addClass('wp-101-status-' + status);
        $badge.text($(this).find('option:selected').text());
    });

    // Update category badge
    $(document).on('change', 'select[name*="[category]"]', function() {
        const categoryId = $(this).val();
        const categoryText = $(this).find('option:selected').text();
        const $item = $(this).closest('.wp-101-item');
        const $badge = $item.find('.wp-101-item-category-badge');

        $item.attr('data-category-id', categoryId);
        $badge.text(categoryText);
    });

    // Toggle tracking mode
    $(document).on('change', '.wp-101-tracking-mode', function() {
        const mode = $(this).val();
        const $item = $(this).closest('.wp-101-item');

        if (mode === 'single') {
            $item.find('.wp-101-simple-mode').hide();
            $item.find('.wp-101-detailed-mode').hide();
        } else if (mode === 'simple') {
            $item.find('.wp-101-simple-mode').show();
            $item.find('.wp-101-detailed-mode').hide();
            // Sync visible inputs to hidden inputs when switching to simple mode
            const currentCount = $item.find('.wp-101-current-count-visible').val();
            const targetCount = $item.find('.wp-101-target-count-visible').val();
            $item.find('.wp-101-current-count-hidden').val(currentCount);
            $item.find('.wp-101-target-count-hidden').val(targetCount);
        } else {
            $item.find('.wp-101-simple-mode').hide();
            $item.find('.wp-101-detailed-mode').show();
        }
    });

    // Sync visible count inputs with hidden inputs
    $(document).on('input change', '.wp-101-current-count-visible', function() {
        console.log('DEBUG: current_count changed to', $(this).val());
        const $item = $(this).closest('.wp-101-item');
        $item.find('.wp-101-current-count-hidden').val($(this).val());
        console.log('DEBUG: hidden current_count updated to', $item.find('.wp-101-current-count-hidden').val());

        // Auto-complete if current equals target
        const currentCount = parseInt($(this).val());
        const targetCount = parseInt($item.find('.wp-101-target-count-visible').val());
        if (currentCount >= targetCount && targetCount > 0) {
            $item.find('.wp-101-item-status').val('complete').trigger('change');
        }
    });

    $(document).on('input change', '.wp-101-target-count-visible', function() {
        console.log('DEBUG: target_count changed to', $(this).val());
        const $item = $(this).closest('.wp-101-item');
        $item.find('.wp-101-target-count-hidden').val($(this).val());
        console.log('DEBUG: hidden target_count updated to', $item.find('.wp-101-target-count-hidden').val());
        // Update max attribute on current_count field
        $item.find('.wp-101-current-count-visible').attr('max', $(this).val());
    });

    // Add sub-item
    $(document).on('click', '.wp-101-add-sub-item', function(e) {
        e.preventDefault();

        const $subItems = $(this).prev('.wp-101-sub-items');
        const itemIndex = $subItems.data('item-index');
        const subIndex = $subItems.find('.wp-101-sub-item').length;

        const subItemHtml = `
            <div class="wp-101-sub-item">
                <input type="checkbox"
                       name="wp_101_items[${itemIndex}][sub_items][${subIndex}][completed]"
                       value="1" />
                <input type="text"
                       name="wp_101_items[${itemIndex}][sub_items][${subIndex}][title]"
                       placeholder="Sub-item title"
                       class="widefat" />
                <input type="hidden"
                       name="wp_101_items[${itemIndex}][sub_items][${subIndex}][date]"
                       value="" />
                <button type="button" class="button button-small wp-101-remove-sub-item">×</button>
            </div>
        `;

        $subItems.append(subItemHtml);
        updateTargetCount($subItems);
    });

    // Remove sub-item
    $(document).on('click', '.wp-101-remove-sub-item', function(e) {
        e.preventDefault();

        const $subItems = $(this).closest('.wp-101-sub-items');
        $(this).closest('.wp-101-sub-item').remove();
        updateTargetCount($subItems);
        reindexSubItems($subItems);
    });

    // Update target count based on sub-items (for detailed mode)
    function updateTargetCount($subItems) {
        const count = $subItems.find('.wp-101-sub-item').length;
        const $item = $subItems.closest('.wp-101-item');
        $item.find('.wp-101-target-count-hidden').val(count);
        // Also update current count based on completed sub-items
        const completedCount = $subItems.find('input[type="checkbox"]:checked').length;
        $item.find('.wp-101-current-count-hidden').val(completedCount);

        // Auto-complete if all sub-items are checked
        if (count > 0 && completedCount >= count) {
            $item.find('.wp-101-item-status').val('complete').trigger('change');
        }
    }

    // Handle sub-item checkbox changes
    $(document).on('change', '.wp-101-sub-item input[type="checkbox"]', function() {
        const $subItems = $(this).closest('.wp-101-sub-items');
        updateTargetCount($subItems);
    });

    // Reindex items
    function reindexItems() {
        $('.wp-101-item').each(function(index) {
            $(this).attr('data-index', index);
            $(this).find('.wp-101-item-number').text('#' + (index + 1));

            // Update all field names
            $(this).find('input, select, textarea').each(function() {
                const name = $(this).attr('name');
                if (name) {
                    const newName = name.replace(/wp_101_items\[\d+\]/, 'wp_101_items[' + index + ']');
                    $(this).attr('name', newName);
                }
            });

            // Reindex sub-items
            const $subItems = $(this).find('.wp-101-sub-items');
            if ($subItems.length) {
                $subItems.attr('data-item-index', index);
                reindexSubItems($subItems);
            }
        });
    }

    // Reindex sub-items
    function reindexSubItems($subItems) {
        const itemIndex = $subItems.data('item-index');

        $subItems.find('.wp-101-sub-item').each(function(subIndex) {
            $(this).find('input').each(function() {
                const name = $(this).attr('name');
                if (name) {
                    const newName = name.replace(
                        /wp_101_items\[\d+\]\[sub_items\]\[\d+\]/,
                        'wp_101_items[' + itemIndex + '][sub_items][' + subIndex + ']'
                    );
                    $(this).attr('name', newName);
                }
            });
        });
    }

    // Update item count
    function updateItemCount() {
        const count = $('.wp-101-item').length;
        $('.wp-101-item-count').html('Items: <strong>' + count + ' / 101</strong>');

        if (count >= 101) {
            $('.wp-101-add-item').prop('disabled', true);
        } else {
            $('.wp-101-add-item').prop('disabled', false);
        }
    }

    // Sort items
    $('#wp-101-sort-by').on('change', function() {
        const sortBy = $(this).val();
        const $container = $('#wp-101-items-container');
        const $items = $container.find('.wp-101-item').get();

        $items.sort(function(a, b) {
            const $a = $(a);
            const $b = $(b);

            switch(sortBy) {
                case 'title':
                    const titleA = $a.find('.wp-101-item-title-preview').text().toLowerCase();
                    const titleB = $b.find('.wp-101-item-title-preview').text().toLowerCase();
                    return titleA.localeCompare(titleB);

                case 'category':
                    const catA = $a.find('.wp-101-item-category-badge').text().toLowerCase();
                    const catB = $b.find('.wp-101-item-category-badge').text().toLowerCase();
                    return catA.localeCompare(catB);

                case 'status':
                    const statusOrder = {
                        'underway': 0,
                        'not_started': 1,
                        'complete': 2,
                        'failed': 3
                    };
                    const statusA = $a.find('.wp-101-item-status-badge').attr('class').match(/wp-101-status-(\w+)/)[1];
                    const statusB = $b.find('.wp-101-item-status-badge').attr('class').match(/wp-101-status-(\w+)/)[1];
                    return (statusOrder[statusA] || 999) - (statusOrder[statusB] || 999);

                case 'order':
                default:
                    return parseInt($a.attr('data-index')) - parseInt($b.attr('data-index'));
            }
        });

        $.each($items, function(idx, item) {
            $container.append(item);
        });

        // Reindex after sorting
        reindexItems();
    });

    // Filter items by category
    $('#wp-101-filter-category').on('change', function() {
        const filterCategory = $(this).val();

        $('.wp-101-item').each(function() {
            const $item = $(this);
            const itemCategoryId = $item.attr('data-category-id');

            if (!filterCategory) {
                // Show all items
                $item.show();
            } else if (filterCategory === '0') {
                // Show only uncategorized items
                if (!itemCategoryId || itemCategoryId === '0' || itemCategoryId === '') {
                    $item.show();
                } else {
                    $item.hide();
                }
            } else {
                // Show items matching the selected category
                if (itemCategoryId === filterCategory) {
                    $item.show();
                } else {
                    $item.hide();
                }
            }
        });
    });

    // Initialize - don't reindex on load since PHP renders with correct indexes
    // Items are only reindexed when user adds/removes items

    // Before form submission, sync all simple mode visible inputs to hidden inputs
    $('form#post').on('submit', function() {
        $('.wp-101-item').each(function() {
            const $item = $(this);
            const mode = $item.find('.wp-101-tracking-mode').val();

            if (mode === 'simple') {
                const currentCount = $item.find('.wp-101-current-count-visible').val();
                const targetCount = $item.find('.wp-101-target-count-visible').val();
                $item.find('.wp-101-current-count-hidden').val(currentCount);
                $item.find('.wp-101-target-count-hidden').val(targetCount);
                console.log('SUBMIT: Synced simple mode counts - current:', currentCount, 'target:', targetCount);
            }
        });
    });
});
