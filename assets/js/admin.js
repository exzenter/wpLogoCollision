/**
 * Admin JavaScript for Context-Aware Animation plugin
 * Handles accordion functionality for effect settings and tab navigation
 */
(function($) {
    'use strict';
    
    $(document).ready(function() {
        // =====================
        // Tab Navigation
        // =====================
        
        // Handle tab clicks
        $('.caa-tabs .nav-tab').on('click', function(e) {
            e.preventDefault();
            
            var tabId = $(this).data('tab');
            
            // Update active tab
            $('.caa-tabs .nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            
            // Show corresponding content
            $('.caa-tab-content').removeClass('caa-tab-active');
            $('#' + tabId).addClass('caa-tab-active');
            
            // Update URL hash without scrolling
            if (history.pushState) {
                history.pushState(null, null, '#' + tabId);
            }
        });
        
        // =====================
        // Sub-tab Navigation (within Pro Version tab)
        // =====================
        
        // Handle sub-tab clicks
        $('.caa-sub-tabs .nav-tab').on('click', function(e) {
            e.preventDefault();
            
            var subtabId = $(this).data('subtab');
            
            // Update active sub-tab
            $('.caa-sub-tabs .nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            
            // Show corresponding sub-tab content
            $('.caa-sub-tab-content').removeClass('caa-sub-tab-active');
            $('#' + subtabId).addClass('caa-sub-tab-active');
            
            // Update URL hash without scrolling
            if (history.pushState) {
                history.pushState(null, null, '#' + subtabId);
            }
        });
        
        // Check for hash on page load - handle both main tabs and sub-tabs
        var hash = window.location.hash;
        if (hash) {
            var hashValue = hash.substring(1);
            
            // Check if it's a sub-tab (pro-mappings or pro-filtering)
            if (hashValue === 'pro-mappings' || hashValue === 'pro-filtering') {
                // Activate the pro-version main tab first
                $('.caa-tabs .nav-tab').removeClass('nav-tab-active');
                $('.caa-tabs .nav-tab[data-tab="pro-version"]').addClass('nav-tab-active');
                $('.caa-tab-content').removeClass('caa-tab-active');
                $('#pro-version').addClass('caa-tab-active');
                
                // Then activate the sub-tab
                setTimeout(function() {
                    $('.caa-sub-tabs .nav-tab').removeClass('nav-tab-active');
                    $('.caa-sub-tabs .nav-tab[data-subtab="' + hashValue + '"]').addClass('nav-tab-active');
                    $('.caa-sub-tab-content').removeClass('caa-sub-tab-active');
                    $('#' + hashValue).addClass('caa-sub-tab-active');
                }, 10);
            }
            // Check if it's a main tab
            else if ($('#' + hashValue).hasClass('caa-tab-content')) {
                $('.caa-tabs .nav-tab').removeClass('nav-tab-active');
                $('.caa-tabs .nav-tab[data-tab="' + hashValue + '"]').addClass('nav-tab-active');
                $('.caa-tab-content').removeClass('caa-tab-active');
                $('#' + hashValue).addClass('caa-tab-active');
                
                // If it's the pro-version tab, activate default sub-tab
                if (hashValue === 'pro-version') {
                    setTimeout(function() {
                        $('.caa-sub-tabs .nav-tab[data-subtab="pro-mappings"]').trigger('click');
                    }, 10);
                }
            }
        }
        
        // =====================
        // Effect Accordions
        // =====================
        
        // Handle effect radio button changes
        $('.caa-effect-radio').on('change', function() {
            var selectedEffect = $(this).val();
            
            // Hide all accordions
            $('.caa-effect-accordion').slideUp(200);
            
            // Show the accordion for the selected effect
            $('.caa-effect-accordion[data-effect="' + selectedEffect + '"]').slideDown(200);
        });
        
        // Initialize: show accordion for currently selected effect
        var selectedEffect = $('.caa-effect-radio:checked').val();
        if (selectedEffect) {
            $('.caa-effect-accordion[data-effect="' + selectedEffect + '"]').show();
        }
        
        // =====================
        // Pro Version Mappings
        // =====================
        
        var mappingIndex = $('#caa-mappings-list .caa-mapping-row').length;
        
        // Add new mapping row
        $('#caa-add-mapping').on('click', function() {
            var newRow = createMappingRow(mappingIndex);
            var $newRow = $(newRow);
            $('#caa-mappings-list').append($newRow);
            $newRow.slideDown(200);
            mappingIndex++;
        });
        
        // Remove mapping row
        $(document).on('click', '.caa-remove-mapping', function() {
            var $row = $(this).closest('.caa-mapping-row');
            
            // If this is the last row, clear it instead of removing
            if ($('#caa-mappings-list .caa-mapping-row').length === 1) {
                $row.find('input[type="text"]').val('');
                $row.find('select').val('1');
            } else {
                $row.fadeOut(200, function() {
                    $(this).remove();
                });
            }
        });
        
        // Create a new mapping row HTML
        function createMappingRow(index) {
            return '<div class="caa-mapping-row" style="display: none;">' +
                '<div class="caa-mapping-col-selector">' +
                    '<input type="text" name="caa_mappings[' + index + '][selector]" value="" class="regular-text" placeholder="#element-id or .class-name" />' +
                '</div>' +
                '<div class="caa-mapping-col-effect">' +
                    '<select name="caa_mappings[' + index + '][effect]">' +
                        '<option value="1">Effect 1: Scale</option>' +
                        '<option value="2">Effect 2: Blur</option>' +
                        '<option value="3">Effect 3: Slide Text</option>' +
                        '<option value="4">Effect 4: Text Split</option>' +
                        '<option value="5">Effect 5: Character Shuffle</option>' +
                        '<option value="6">Effect 6: Rotation</option>' +
                        '<option value="7">Effect 7: Move Away</option>' +
                    '</select>' +
                '</div>' +
                '<div class="caa-mapping-col-actions">' +
                    '<button type="button" class="button caa-remove-mapping" title="Remove Mapping">' +
                        '<span class="dashicons dashicons-trash"></span>' +
                    '</button>' +
                '</div>' +
            '</div>';
        }
        
        // =====================
        // Page Filtering
        // =====================
        
        var selectedItems = typeof caaAdmin !== 'undefined' ? caaAdmin.selectedItems : [];
        
        // Show/hide filtering options based on enable checkbox
        $('#caa_pro_enable_filtering').on('change', function() {
            if ($(this).is(':checked')) {
                $('#caa-filtering-options').slideDown(200);
            } else {
                $('#caa-filtering-options').slideUp(200);
            }
        });
        
        // Update labels based on filter mode
        function updateFilterLabels() {
            var mode = $('input[name="caa_pro_filter_mode"]:checked').val();
            var isInclude = mode === 'include';
            
            // Update post types label
            $('#caa-post-types-label').text(isInclude ? 'Include Post Types' : 'Exclude Post Types');
            $('#caa-post-types-desc').text(isInclude 
                ? 'Select post types to include.' 
                : 'Select post types to exclude.');
            
            // Update pages label
            $('#caa-pages-label').text(isInclude ? 'Include Pages' : 'Exclude Pages');
            $('#caa-pages-text').text(isInclude ? 'Include all pages' : 'Exclude all pages');
            
            // Update posts label
            $('#caa-posts-label').text(isInclude ? 'Include Posts' : 'Exclude Posts');
            $('#caa-posts-text').text(isInclude ? 'Include all posts' : 'Exclude all posts');
            
            // Update items label
            $('#caa-items-label').text(isInclude ? 'Include Individual Items' : 'Exclude Individual Items');
            $('#caa-items-desc').text(isInclude 
                ? 'Search and select individual posts or pages to include.' 
                : 'Search and select individual posts or pages to exclude.');
        }
        
        // Update labels when mode changes
        $('input[name="caa_pro_filter_mode"]').on('change', function() {
            updateFilterLabels();
        });
        
        // Initialize labels
        updateFilterLabels();
        
        // Get list of already selected item IDs
        function getSelectedItemIds() {
            var ids = [];
            $('#caa-selected-items input[type="hidden"]').each(function() {
                ids.push(parseInt($(this).val(), 10));
            });
            return ids;
        }
        
        // Autocomplete for post/page search
        if ($('#caa-items-search').length && typeof caaAdmin !== 'undefined') {
            $('#caa-items-search').autocomplete({
                source: function(request, response) {
                    $.ajax({
                        url: caaAdmin.ajaxUrl,
                        dataType: 'json',
                        data: {
                            action: 'caa_search_posts',
                            term: request.term,
                            nonce: caaAdmin.nonce
                        },
                        success: function(data) {
                            if (data.success && data.data) {
                                // Filter out already selected items
                                var selectedIds = getSelectedItemIds();
                                var filtered = data.data.filter(function(item) {
                                    return selectedIds.indexOf(parseInt(item.id, 10)) === -1;
                                });
                                response(filtered);
                            } else {
                                response([]);
                            }
                        },
                        error: function() {
                            response([]);
                        }
                    });
                },
                minLength: 2,
                select: function(event, ui) {
                    event.preventDefault();
                    addSelectedItem(ui.item.id, ui.item.label);
                    $(this).val('');
                    return false;
                },
                focus: function(event, ui) {
                    event.preventDefault();
                    return false;
                }
            });
        }
        
        // Add selected item
        function addSelectedItem(id, label) {
            // Check if already selected
            if ($('#caa-selected-items input[value="' + id + '"]').length > 0) {
                return;
            }
            
            var mode = $('input[name="caa_pro_filter_mode"]:checked').val();
            var isInclude = mode === 'include';
            var removeText = isInclude ? 'Remove from includes' : 'Remove from excludes';
            
            var tag = $('<span>', {
                'class': 'caa-item-tag',
                'data-id': id,
                'style': 'display: inline-block; background: #2271b1; color: #fff; padding: 4px 8px; margin: 4px 4px 4px 0; border-radius: 3px;'
            }).append(
                $('<span>').text(label),
                ' ',
                $('<span>', {
                    'class': 'caa-remove-item',
                    'style': 'cursor: pointer; margin-left: 5px; font-weight: bold;',
                    'title': removeText
                }).text('×'),
                $('<input>', {
                    'type': 'hidden',
                    'name': 'caa_pro_selected_items[]',
                    'value': id
                })
            );
            
            $('#caa-selected-items').append(tag);
        }
        
        // Remove selected item
        $(document).on('click', '.caa-remove-item', function() {
            $(this).closest('.caa-item-tag').fadeOut(200, function() {
                $(this).remove();
            });
        });
        
    });
})(jQuery);

