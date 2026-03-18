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
        itemIndex++;

        updateItemCount();
        reindexItems();
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

    // Toggle tracking mode
    $(document).on('change', '.wp-101-tracking-mode', function() {
        const mode = $(this).val();
        const $item = $(this).closest('.wp-101-item');

        if (mode === 'simple') {
            $item.find('.wp-101-simple-mode').show();
            $item.find('.wp-101-detailed-mode').hide();
        } else {
            $item.find('.wp-101-simple-mode').hide();
            $item.find('.wp-101-detailed-mode').show();
        }
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

    // Update target count based on sub-items
    function updateTargetCount($subItems) {
        const count = $subItems.find('.wp-101-sub-item').length;
        $subItems.siblings('.wp-101-target-count').val(count);
    }

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

    // Initialize
    reindexItems();
});
