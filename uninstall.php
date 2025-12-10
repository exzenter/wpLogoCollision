<?php
/**
 * Uninstall Context-Aware Animation plugin
 *
 * This file is executed when the plugin is deleted through the WordPress admin.
 * It removes all plugin options from the database.
 *
 * @package Context_Aware_Animation
 */

// Exit if not called by WordPress
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// List of all options used by the plugin
$options_to_delete = array(
    // Core settings
    'caa_logo_id',
    'caa_selected_effect',
    'caa_included_elements',
    'caa_excluded_elements',
    'caa_global_offset',
    'caa_debug_mode',
    
    // Global animation settings
    'caa_duration',
    'caa_ease',
    'caa_offset_start',
    'caa_offset_end',
    
    // Effect 1: Scale
    'caa_effect1_scale_down',
    'caa_effect1_origin_x',
    'caa_effect1_origin_y',
    
    // Effect 2: Blur
    'caa_effect2_blur_amount',
    'caa_effect2_blur_scale',
    'caa_effect2_blur_duration',
    
    // Effect 4: Text Split
    'caa_effect4_text_x_range',
    'caa_effect4_text_y_range',
    'caa_effect4_stagger_amount',
    
    // Effect 5: Character Shuffle
    'caa_effect5_shuffle_iterations',
    'caa_effect5_shuffle_duration',
    'caa_effect5_char_delay',
    
    // Effect 6: Rotation
    'caa_effect6_rotation',
    'caa_effect6_x_percent',
    'caa_effect6_origin_x',
    'caa_effect6_origin_y',
    
    // Effect 7: Move Away
    'caa_effect7_move_distance',
    
    // Pro Version: Effect mappings
    'caa_pro_effect_mappings',
    
    // Pro Version: Filtering settings
    'caa_pro_enable_filtering',
    'caa_pro_filter_mode',
    'caa_pro_selected_post_types',
    'caa_pro_include_pages',
    'caa_pro_include_posts',
    'caa_pro_selected_items',
);

// Delete all plugin options
foreach ($options_to_delete as $option) {
    delete_option($option);
}

