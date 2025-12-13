/**
 * Admin JavaScript for Context-Aware Animation plugin
 * Handles accordion functionality for effect settings and tab navigation
 */
(function ($) {
    'use strict';

    $(document).ready(function () {
        // =====================
        // Tab Navigation
        // =====================

        // Handle tab clicks
        $('.caa-tabs .nav-tab').on('click', function (e) {
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
        $('.caa-sub-tabs .nav-tab').on('click', function (e) {
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

            // Check if it's a sub-tab (pro-general, pro-mappings or pro-filtering)
            if (hashValue === 'pro-general' || hashValue === 'pro-mappings' || hashValue === 'pro-filtering' || hashValue === 'pro-version') {
                // Activate the pro-version main tab first
                $('.caa-tabs .nav-tab').removeClass('nav-tab-active');
                $('.caa-tabs .nav-tab[data-tab="pro-version"]').addClass('nav-tab-active');
                $('.caa-tab-content').removeClass('caa-tab-active');
                $('#pro-version').addClass('caa-tab-active');

                // Then activate the sub-tab
                var subtab = hashValue === 'pro-version' ? 'pro-general' : hashValue;
                setTimeout(function () {
                    $('.caa-sub-tabs .nav-tab').removeClass('nav-tab-active');
                    $('.caa-sub-tabs .nav-tab[data-subtab="' + subtab + '"]').addClass('nav-tab-active');
                    $('.caa-sub-tab-content').removeClass('caa-sub-tab-active');
                    $('#' + subtab).addClass('caa-sub-tab-active');
                }, 10);
            }
            // Check if it's a main tab
            else if ($('#' + hashValue).hasClass('caa-tab-content')) {
                $('.caa-tabs .nav-tab').removeClass('nav-tab-active');
                $('.caa-tabs .nav-tab[data-tab="' + hashValue + '"]').addClass('nav-tab-active');
                $('.caa-tab-content').removeClass('caa-tab-active');
                $('#' + hashValue).addClass('caa-tab-active');
            }
        }

        // =====================
        // Instance Selector (Pro Version)
        // =====================

        // Handle instance selector dropdown change
        $('#caa-instance-select').on('change', function () {
            var instanceId = $(this).val();
            var currentUrl = window.location.href;

            // Build new URL with instance_id parameter
            var url = new URL(currentUrl);
            url.searchParams.set('instance_id', instanceId);

            // Preserve the hash (current sub-tab)
            var currentHash = window.location.hash || '#pro-general';
            url.hash = currentHash;

            // Redirect to new URL
            window.location.href = url.toString();
        });

        // =====================
        // Effect Selection (General Settings Tab)
        // =====================

        // Handle effect radio button changes (horizontal button row)
        $('.caa-effect-radio').on('change', function () {
            var selectedEffect = $(this).val();
            var $container = $(this).closest('td');

            // Update button active states (new horizontal button row)
            $container.find('.caa-effect-btn').removeClass('caa-effect-btn-active');
            $(this).closest('.caa-effect-btn').addClass('caa-effect-btn-active');

            // Handle new settings panel UI
            var $settingsPanel = $container.find('.caa-effect-settings-panel');
            if ($settingsPanel.length) {
                $settingsPanel.find('.caa-effect-settings-content').slideUp(150);
                $settingsPanel.find('.caa-effect-settings-content[data-effect="' + selectedEffect + '"]').slideDown(150);
            }

            // Handle old card-based UI (for Pro tab)
            var $fieldset = $(this).closest('fieldset');
            if ($fieldset.hasClass('caa-effect-fieldset')) {
                $fieldset.find('.caa-effect-card').removeClass('caa-effect-active');
                $(this).closest('.caa-effect-card').addClass('caa-effect-active');
                $fieldset.find('.caa-effect-card-body').slideUp(200);
                $(this).closest('.caa-effect-card').find('.caa-effect-card-body').slideDown(200);
            } else if (!$settingsPanel.length) {
                // Handle old accordion-style UI
                $('.caa-effect-accordion').slideUp(200);
                $('.caa-effect-accordion[data-effect="' + selectedEffect + '"]').slideDown(200);
            }
        });

        // Initialize: show active effect's settings
        var selectedEffect = $('.caa-effect-radio:checked').val();
        if (selectedEffect) {
            // New horizontal button style
            var $activeBtn = $('.caa-effect-btn input[value="' + selectedEffect + '"]').closest('.caa-effect-btn');
            $activeBtn.addClass('caa-effect-btn-active');
            $('.caa-effect-settings-content[data-effect="' + selectedEffect + '"]').show();

            // Old card style (Pro tab)
            var $activeCard = $('.caa-effect-card[data-effect="' + selectedEffect + '"]');
            if ($activeCard.length) {
                $activeCard.addClass('caa-effect-active');
                $activeCard.find('.caa-effect-card-body').show();
            }
            // Old accordion style
            $('.caa-effect-accordion[data-effect="' + selectedEffect + '"]').show();
        }

        // =====================
        // Pro Version Mappings
        // =====================

        var mappingIndex = $('#caa-mappings-list .caa-mapping-row-wrapper').length;

        // Add new mapping row
        $('#caa-add-mapping').on('click', function () {
            var newRow = createMappingRow(mappingIndex);
            var $newRow = $(newRow);
            $('#caa-mappings-list').append($newRow);
            $newRow.slideDown(200);
            mappingIndex++;
        });

        // Remove mapping row
        $(document).on('click', '.caa-remove-mapping', function () {
            var $wrapper = $(this).closest('.caa-mapping-row-wrapper');

            // If this is the last row, clear it instead of removing
            if ($('#caa-mappings-list .caa-mapping-row-wrapper').length === 1) {
                $wrapper.find('.caa-mapping-selector').val('');
                $wrapper.find('.caa-mapping-effect-select').val('1').trigger('change');
                $wrapper.find('.caa-override-checkbox').prop('checked', false).trigger('change');
            } else {
                $wrapper.fadeOut(200, function () {
                    $(this).remove();
                });
            }
        });

        // Override checkbox - now only toggles the enabled state (does NOT control panel visibility)
        // The visual feedback (gear icon color) is handled by CSS
        $(document).on('change', '.caa-override-checkbox', function () {
            // No panel sliding - override just enables/disables the settings values
            // Panel visibility is controlled by the chevron toggle button
        });

        // Handle panel toggle button (chevron) - controls settings panel visibility
        $(document).on('click', '.caa-panel-toggle', function () {
            var $button = $(this);
            var $wrapper = $button.closest('.caa-mapping-row-wrapper');
            var $panel = $wrapper.find('.caa-mapping-settings-panel');

            if ($button.hasClass('caa-expanded')) {
                // Collapse
                $panel.slideUp(200);
                $button.removeClass('caa-expanded');
            } else {
                // Expand
                $panel.slideDown(200);
                $button.addClass('caa-expanded');
            }
        });

        // Handle effect dropdown change - reset settings but keep panel state
        $(document).on('change', '.caa-mapping-effect-select', function () {
            var $wrapper = $(this).closest('.caa-mapping-row-wrapper');
            var $panel = $wrapper.find('.caa-mapping-settings-panel');
            var selectedEffect = $(this).val();

            // Update the panel's data-effect attribute
            $panel.attr('data-effect', selectedEffect);

            // Hide all effect-specific settings
            $panel.find('.caa-effect-settings').hide();

            // Show the settings for the selected effect
            $panel.find('.caa-effect-settings-' + selectedEffect).show();

            // Reset all settings to defaults
            resetMappingSettings($wrapper, selectedEffect);
        });

        // Handle collapsible section chevron toggle
        $(document).on('click', '.caa-collapsible-header', function () {
            var $header = $(this);
            var $section = $header.closest('.caa-collapsible-section');
            var $body = $header.next('.caa-collapsible-body');
            var isExpanded = $header.attr('data-expanded') === 'true';

            if (isExpanded) {
                // Collapse
                $body.slideUp(200);
                $header.attr('data-expanded', 'false');
                $section.addClass('caa-collapsed');
            } else {
                // Expand
                $body.slideDown(200);
                $header.attr('data-expanded', 'true');
                $section.removeClass('caa-collapsed');
            }
        });

        // Reset mapping settings to defaults
        function resetMappingSettings($wrapper, effectNumber) {
            // Reset global animation settings
            $wrapper.find('input[name*="[settings][duration]"]').val('0.6');
            $wrapper.find('select[name*="[settings][ease]"]').val('power4');
            $wrapper.find('input[name*="[settings][offset_start]"]').val('30');
            $wrapper.find('input[name*="[settings][offset_end]"]').val('10');

            // Reset Effect 1 settings
            $wrapper.find('input[name*="[settings][effect1_scale_down]"]').val('0');
            $wrapper.find('input[name*="[settings][effect1_origin_x]"]').val('0');
            $wrapper.find('input[name*="[settings][effect1_origin_y]"]').val('50');

            // Reset Effect 2 settings
            $wrapper.find('input[name*="[settings][effect2_blur_amount]"]').val('5');
            $wrapper.find('input[name*="[settings][effect2_blur_scale]"]').val('0.9');
            $wrapper.find('input[name*="[settings][effect2_blur_duration]"]').val('0.2');

            // Reset Effect 4 settings
            $wrapper.find('input[name*="[settings][effect4_text_x_range]"]').val('50');
            $wrapper.find('input[name*="[settings][effect4_text_y_range]"]').val('40');
            $wrapper.find('input[name*="[settings][effect4_stagger_amount]"]').val('0.03');

            // Reset Effect 5 settings
            $wrapper.find('input[name*="[settings][effect5_shuffle_iterations]"]').val('2');
            $wrapper.find('input[name*="[settings][effect5_shuffle_duration]"]').val('0.03');
            $wrapper.find('input[name*="[settings][effect5_char_delay]"]').val('0.03');

            // Reset Effect 6 settings
            $wrapper.find('input[name*="[settings][effect6_rotation]"]').val('-90');
            $wrapper.find('input[name*="[settings][effect6_x_percent]"]').val('-5');
            $wrapper.find('input[name*="[settings][effect6_origin_x]"]').val('0');
            $wrapper.find('input[name*="[settings][effect6_origin_y]"]').val('100');

            // Reset Effect 7 settings
            $wrapper.find('input[name*="[settings][effect7_move_distance]"]').val('');
        }

        // Create a new mapping row HTML
        function createMappingRow(index) {
            return '<div class="caa-mapping-row-wrapper" style="display: none;">' +
                '<div class="caa-mapping-row">' +
                '<div class="caa-mapping-col-selector">' +
                '<input type="text" name="caa_mappings[' + index + '][selector]" value="" class="regular-text caa-mapping-selector" placeholder="#element-id or .class-name" />' +
                '</div>' +
                '<div class="caa-mapping-col-effect">' +
                '<select name="caa_mappings[' + index + '][effect]" class="caa-mapping-effect-select">' +
                '<option value="1">Effect 1: Scale</option>' +
                '<option value="2">Effect 2: Blur</option>' +
                '<option value="3">Effect 3: Slide Text</option>' +
                '<option value="4">Effect 4: Text Split</option>' +
                '<option value="5">Effect 5: Character Shuffle</option>' +
                '<option value="6">Effect 6: Rotation</option>' +
                '<option value="7">Effect 7: Move Away</option>' +
                '</select>' +
                '</div>' +
                '<div class="caa-mapping-col-override">' +
                '<label class="caa-override-checkbox-label" title="Enable custom settings override">' +
                '<input type="checkbox" name="caa_mappings[' + index + '][override_enabled]" value="1" class="caa-override-checkbox" />' +
                '<span class="dashicons dashicons-admin-generic"></span>' +
                '</label>' +
                '</div>' +
                '<div class="caa-mapping-col-expand">' +
                '<button type="button" class="caa-panel-toggle" title="Expand/Collapse settings">' +
                '<span class="dashicons dashicons-arrow-down-alt2 caa-chevron"></span>' +
                '</button>' +
                '</div>' +
                '<div class="caa-mapping-col-actions">' +
                '<button type="button" class="button caa-remove-mapping" title="Remove Mapping">' +
                '<span class="dashicons dashicons-trash"></span>' +
                '</button>' +
                '</div>' +
                '</div>' +
                '<div class="caa-mapping-settings-panel" data-effect="1">' +
                '<div class="caa-mapping-settings-content">' +
                // Animation Settings Collapsible Section
                '<div class="caa-collapsible-section">' +
                '<div class="caa-collapsible-header caa-animation-header" data-expanded="true">' +
                '<span class="dashicons dashicons-arrow-down-alt2 caa-chevron"></span>' +
                '<h4>Animation Settings</h4>' +
                '</div>' +
                '<div class="caa-collapsible-body caa-animation-body">' +
                '<div class="caa-settings-grid">' +
                '<div class="caa-setting-field">' +
                '<label>Duration</label>' +
                '<input type="number" name="caa_mappings[' + index + '][settings][duration]" value="0.6" min="0.1" max="10" step="0.1" class="small-text" />' +
                '<span>s</span>' +
                '</div>' +
                '<div class="caa-setting-field">' +
                '<label>Ease</label>' +
                '<select name="caa_mappings[' + index + '][settings][ease]">' +
                '<option value="power1">Power 1</option>' +
                '<option value="power2">Power 2</option>' +
                '<option value="power3">Power 3</option>' +
                '<option value="power4" selected>Power 4</option>' +
                '<option value="expo">Expo</option>' +
                '<option value="sine">Sine</option>' +
                '<option value="back">Back</option>' +
                '<option value="elastic">Elastic</option>' +
                '<option value="bounce">Bounce</option>' +
                '<option value="none">None</option>' +
                '</select>' +
                '</div>' +
                '<div class="caa-setting-field">' +
                '<label>Start Offset</label>' +
                '<input type="number" name="caa_mappings[' + index + '][settings][offset_start]" value="30" step="1" class="small-text" />' +
                '<span>px</span>' +
                '</div>' +
                '<div class="caa-setting-field">' +
                '<label>End Offset</label>' +
                '<input type="number" name="caa_mappings[' + index + '][settings][offset_end]" value="10" step="1" class="small-text" />' +
                '<span>px</span>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '</div>' +
                // Effect Settings Collapsible Section
                '<div class="caa-collapsible-section caa-effect-section">' +
                '<div class="caa-collapsible-header caa-effect-header" data-expanded="true">' +
                '<span class="dashicons dashicons-arrow-down-alt2 caa-chevron"></span>' +
                '<h4>Effect Settings</h4>' +
                '</div>' +
                '<div class="caa-collapsible-body caa-effect-body">' +
                '<div class="caa-effect-settings caa-effect-settings-1" style="display: block;">' +
                '<div class="caa-settings-grid">' +
                '<div class="caa-setting-field">' +
                '<label>Scale Down</label>' +
                '<input type="number" name="caa_mappings[' + index + '][settings][effect1_scale_down]" value="0" min="0" max="1" step="0.1" class="small-text" />' +
                '</div>' +
                '<div class="caa-setting-field">' +
                '<label>Origin X</label>' +
                '<input type="number" name="caa_mappings[' + index + '][settings][effect1_origin_x]" value="0" min="0" max="500" step="5" class="small-text" />' +
                '<span>%</span>' +
                '</div>' +
                '<div class="caa-setting-field">' +
                '<label>Origin Y</label>' +
                '<input type="number" name="caa_mappings[' + index + '][settings][effect1_origin_y]" value="50" min="0" max="500" step="5" class="small-text" />' +
                '<span>%</span>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '<div class="caa-effect-settings caa-effect-settings-2">' +
                '<div class="caa-settings-grid">' +
                '<div class="caa-setting-field">' +
                '<label>Blur Amount</label>' +
                '<input type="number" name="caa_mappings[' + index + '][settings][effect2_blur_amount]" value="5" min="0" max="100" step="0.5" class="small-text" />' +
                '<span>px</span>' +
                '</div>' +
                '<div class="caa-setting-field">' +
                '<label>Scale</label>' +
                '<input type="number" name="caa_mappings[' + index + '][settings][effect2_blur_scale]" value="0.9" min="0.5" max="1" step="0.05" class="small-text" />' +
                '</div>' +
                '<div class="caa-setting-field">' +
                '<label>Duration</label>' +
                '<input type="number" name="caa_mappings[' + index + '][settings][effect2_blur_duration]" value="0.2" min="0.1" max="5" step="0.1" class="small-text" />' +
                '<span>s</span>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '<div class="caa-effect-settings caa-effect-settings-3">' +
                '<p class="description">This effect uses only the animation settings above.</p>' +
                '</div>' +
                '<div class="caa-effect-settings caa-effect-settings-4">' +
                '<div class="caa-settings-grid">' +
                '<div class="caa-setting-field">' +
                '<label>X Range</label>' +
                '<input type="number" name="caa_mappings[' + index + '][settings][effect4_text_x_range]" value="50" min="0" max="1000" step="5" class="small-text" />' +
                '<span>px</span>' +
                '</div>' +
                '<div class="caa-setting-field">' +
                '<label>Y Range</label>' +
                '<input type="number" name="caa_mappings[' + index + '][settings][effect4_text_y_range]" value="40" min="0" max="1000" step="5" class="small-text" />' +
                '<span>px</span>' +
                '</div>' +
                '<div class="caa-setting-field">' +
                '<label>Stagger</label>' +
                '<input type="number" name="caa_mappings[' + index + '][settings][effect4_stagger_amount]" value="0.03" min="0" max="2.5" step="0.01" class="small-text" />' +
                '<span>s</span>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '<div class="caa-effect-settings caa-effect-settings-5">' +
                '<div class="caa-settings-grid">' +
                '<div class="caa-setting-field">' +
                '<label>Iterations</label>' +
                '<input type="number" name="caa_mappings[' + index + '][settings][effect5_shuffle_iterations]" value="2" min="1" max="50" step="1" class="small-text" />' +
                '</div>' +
                '<div class="caa-setting-field">' +
                '<label>Shuffle Duration</label>' +
                '<input type="number" name="caa_mappings[' + index + '][settings][effect5_shuffle_duration]" value="0.03" min="0.01" max="0.5" step="0.01" class="small-text" />' +
                '<span>s</span>' +
                '</div>' +
                '<div class="caa-setting-field">' +
                '<label>Char Delay</label>' +
                '<input type="number" name="caa_mappings[' + index + '][settings][effect5_char_delay]" value="0.03" min="0" max="1.0" step="0.01" class="small-text" />' +
                '<span>s</span>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '<div class="caa-effect-settings caa-effect-settings-6">' +
                '<div class="caa-settings-grid">' +
                '<div class="caa-setting-field">' +
                '<label>Rotation</label>' +
                '<input type="number" name="caa_mappings[' + index + '][settings][effect6_rotation]" value="-90" min="-180" max="900" step="5" class="small-text" />' +
                '<span>°</span>' +
                '</div>' +
                '<div class="caa-setting-field">' +
                '<label>X Percent</label>' +
                '<input type="number" name="caa_mappings[' + index + '][settings][effect6_x_percent]" value="-5" min="-50" max="250" step="1" class="small-text" />' +
                '<span>%</span>' +
                '</div>' +
                '<div class="caa-setting-field">' +
                '<label>Origin X</label>' +
                '<input type="number" name="caa_mappings[' + index + '][settings][effect6_origin_x]" value="0" min="0" max="500" step="5" class="small-text" />' +
                '<span>%</span>' +
                '</div>' +
                '<div class="caa-setting-field">' +
                '<label>Origin Y</label>' +
                '<input type="number" name="caa_mappings[' + index + '][settings][effect6_origin_y]" value="100" min="0" max="500" step="5" class="small-text" />' +
                '<span>%</span>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '<div class="caa-effect-settings caa-effect-settings-7">' +
                '<div class="caa-settings-grid">' +
                '<div class="caa-setting-field caa-setting-field-wide">' +
                '<label>Move Distance</label>' +
                '<input type="text" name="caa_mappings[' + index + '][settings][effect7_move_distance]" value="" class="regular-text" placeholder="auto (e.g., 100px or 50%)" />' +
                '</div>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '</div>';
        }

        // =====================
        // Page Filtering
        // =====================

        var selectedItems = typeof caaAdmin !== 'undefined' ? caaAdmin.selectedItems : [];

        // Show/hide filtering options based on enable checkbox (per-instance)
        $('#caa_instance_enable_filtering').on('change', function () {
            if ($(this).is(':checked')) {
                $('#caa-filtering-options').slideDown(200);
            } else {
                $('#caa-filtering-options').slideUp(200);
            }
        });

        // Update labels based on filter mode
        function updateFilterLabels() {
            var mode = $('input[name="caa_instance_filter_mode"]:checked').val();
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
        $('input[name="caa_instance_filter_mode"]').on('change', function () {
            updateFilterLabels();
        });

        // Initialize labels
        updateFilterLabels();

        // Get list of already selected item IDs
        function getSelectedItemIds() {
            var ids = [];
            $('#caa-selected-items input[type="hidden"]').each(function () {
                ids.push(parseInt($(this).val(), 10));
            });
            return ids;
        }

        // Autocomplete for post/page search
        if ($('#caa-items-search').length && typeof caaAdmin !== 'undefined') {
            $('#caa-items-search').autocomplete({
                source: function (request, response) {
                    $.ajax({
                        url: caaAdmin.ajaxUrl,
                        dataType: 'json',
                        data: {
                            action: 'caa_search_posts',
                            term: request.term,
                            nonce: caaAdmin.nonce
                        },
                        success: function (data) {
                            if (data.success && data.data) {
                                // Filter out already selected items
                                var selectedIds = getSelectedItemIds();
                                var filtered = data.data.filter(function (item) {
                                    return selectedIds.indexOf(parseInt(item.id, 10)) === -1;
                                });
                                response(filtered);
                            } else {
                                response([]);
                            }
                        },
                        error: function () {
                            response([]);
                        }
                    });
                },
                minLength: 2,
                select: function (event, ui) {
                    event.preventDefault();
                    addSelectedItem(ui.item.id, ui.item.label);
                    $(this).val('');
                    return false;
                },
                focus: function (event, ui) {
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

            var mode = $('input[name="caa_instance_filter_mode"]:checked').val();
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
                    'name': 'caa_instance_selected_items[]',
                    'value': id
                })
            );

            $('#caa-selected-items').append(tag);
        }

        // Remove selected item
        $(document).on('click', '.caa-remove-item', function () {
            $(this).closest('.caa-item-tag').fadeOut(200, function () {
                $(this).remove();
            });
        });

        // =====================
        // Instance Editor
        // =====================

        // Handle instance effect radio button changes
        $('.caa-instance-effect-radio').on('change', function () {
            var selectedEffect = $(this).val();

            // Hide all instance effect accordions
            $('.caa-instance-effect-accordion').slideUp(200);

            // Show the accordion for the selected effect
            $('.caa-instance-effect-accordion[data-effect="' + selectedEffect + '"]').slideDown(200);
        });

        // Initialize: show accordion for currently selected instance effect
        var selectedInstanceEffect = $('.caa-instance-effect-radio:checked').val();
        if (selectedInstanceEffect) {
            $('.caa-instance-effect-accordion[data-effect="' + selectedInstanceEffect + '"]').show();
        }

        // Handle instance filtering toggle
        $('#caa_instance_enable_filtering').on('change', function () {
            if ($(this).is(':checked')) {
                $('#caa-instance-filtering-options').slideDown(200);
            } else {
                $('#caa-instance-filtering-options').slideUp(200);
            }
        });

        // =====================
        // Viewport Switcher (Pro Feature)
        // =====================

        // Handle viewport mode switching
        $('input[name="caa_viewport_mode"]').on('change', function () {
            var viewport = $(this).val();
            var $form = $(this).closest('form');
            var $switcher = $(this).closest('.caa-viewport-switcher');

            // Update active button class
            $switcher.find('.caa-viewport-btn').removeClass('caa-viewport-active');
            $(this).closest('.caa-viewport-btn').addClass('caa-viewport-active');

            // Remove all viewport classes from form
            $form.removeClass('caa-viewport-active-desktop caa-viewport-active-tablet caa-viewport-active-mobile');

            // Add current viewport class (default is desktop, which shows desktop inputs)
            if (viewport !== 'desktop') {
                $form.addClass('caa-viewport-active-' + viewport);
            }
        });

        // Update override indicators when input values change
        function updateViewportOverrideIndicators() {
            $('.caa-viewport-field').each(function () {
                var $field = $(this);
                var $tabletInput = $field.find('.caa-viewport-tablet');
                var $mobileInput = $field.find('.caa-viewport-mobile');
                var $indicator = $field.find('.caa-has-override');

                // Check if either tablet or mobile has a value
                var hasTabletValue = $tabletInput.length && $tabletInput.val() !== '';
                var hasMobileValue = $mobileInput.length && $mobileInput.val() !== '';

                if (hasTabletValue || hasMobileValue) {
                    $indicator.addClass('visible');
                } else {
                    $indicator.removeClass('visible');
                }
            });
        }

        // Update indicators on input change
        $(document).on('input change', '.caa-viewport-tablet, .caa-viewport-mobile', function () {
            updateViewportOverrideIndicators();
        });

        // Initialize viewport switcher state
        updateViewportOverrideIndicators();

    });
})(jQuery);
