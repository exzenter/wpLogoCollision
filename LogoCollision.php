<?php
/**
 * Plugin Name: Logo Collision
 * Plugin URI: https://exzent.de/logo-collision/
 * Description: Apply context-aware scroll animations to your WordPress header logo when it would collide with scrolling content.
 * Version: 1.1.0
 * Author: wpmitch
 * Author URI: https://exzent.de
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: logo-collision
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CAA_VERSION', '1.1.0');
define('CAA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CAA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('LOGO_COLLISION_PRO', true); // Build script sets to false for Free version

// Max instances: 10 for Pro, 1 for Free
if (defined('LOGO_COLLISION_PRO') && LOGO_COLLISION_PRO) {
    define('CAA_MAX_INSTANCES', 10);
} else {
    define('CAA_MAX_INSTANCES', 1);
}

// PRO_START
// Load Pro-only classes
require_once CAA_PLUGIN_DIR . 'includes/class-license.php';
require_once CAA_PLUGIN_DIR . 'includes/class-updater.php';
// PRO_END

/**
 * Main plugin class
 */
class Context_Aware_Animation {
    
    /**
     * Instance of this class
     */
    private static $instance = null;
    
    /**
     * Get instance of this class
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Plugin activation hook - creates Instance 1 with defaults if no instances exist
     */
    public static function activate() {
        $instances = get_option('caa_instances', array());
        
        // Only create Instance 1 if no instances exist
        if (empty($instances)) {
            $plugin = self::get_instance();
            $default_data = $plugin->get_default_instance_data();
            $default_data['logo_id'] = ''; // Empty by default, user must configure
            
            $instances = array(
                1 => $default_data
            );
            
            update_option('caa_instances', $instances);
        }
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Admin hooks
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_init', array($this, 'maybe_migrate_legacy_settings'));
        add_action('admin_init', array($this, 'ensure_instance_exists'));
        add_action('admin_init', array($this, 'handle_settings_export'));
        add_action('wp_ajax_caa_search_posts', array($this, 'ajax_search_posts'));
        
        // Frontend hooks
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('script_loader_tag', array($this, 'add_module_type'), 10, 3);
    }
    
    // =========================================================================
    // INSTANCE MANAGEMENT METHODS (Pro Version)
    // =========================================================================
    
    /**
     * Get default instance data structure
     */
    public function get_default_instance_data() {
        return array(
            'enabled' => true,
            'logo_id' => '',
            'selected_effect' => '1',
            'included_elements' => '',
            'excluded_elements' => '',
            'global_offset' => '0',
            'debug_mode' => '0',
            // Animation settings with viewport variants (empty = inherit from desktop)
            'duration' => '0.6',
            'duration_tablet' => '',
            'duration_mobile' => '',
            'ease' => 'power4',
            'ease_tablet' => '',
            'ease_mobile' => '',
            'offset_start' => '30',
            'offset_start_tablet' => '',
            'offset_start_mobile' => '',
            'offset_end' => '10',
            'offset_end_tablet' => '',
            'offset_end_mobile' => '',
            // Effect 1: Scale (with viewport variants)
            'effect1_scale_down' => '0',
            'effect1_scale_down_tablet' => '',
            'effect1_scale_down_mobile' => '',
            'effect1_origin_x' => '0',
            'effect1_origin_x_tablet' => '',
            'effect1_origin_x_mobile' => '',
            'effect1_origin_y' => '50',
            'effect1_origin_y_tablet' => '',
            'effect1_origin_y_mobile' => '',
            // Effect 2: Blur (with viewport variants)
            'effect2_blur_amount' => '5',
            'effect2_blur_amount_tablet' => '',
            'effect2_blur_amount_mobile' => '',
            'effect2_blur_scale' => '0.9',
            'effect2_blur_scale_tablet' => '',
            'effect2_blur_scale_mobile' => '',
            'effect2_blur_duration' => '0.2',
            'effect2_blur_duration_tablet' => '',
            'effect2_blur_duration_mobile' => '',
            // Effect 4: Text Split (with viewport variants)
            'effect4_text_x_range' => '50',
            'effect4_text_x_range_tablet' => '',
            'effect4_text_x_range_mobile' => '',
            'effect4_text_y_range' => '40',
            'effect4_text_y_range_tablet' => '',
            'effect4_text_y_range_mobile' => '',
            'effect4_stagger_amount' => '0.03',
            'effect4_stagger_amount_tablet' => '',
            'effect4_stagger_amount_mobile' => '',
            // Effect 5: Character Shuffle (with viewport variants)
            'effect5_shuffle_iterations' => '2',
            'effect5_shuffle_iterations_tablet' => '',
            'effect5_shuffle_iterations_mobile' => '',
            'effect5_shuffle_duration' => '0.03',
            'effect5_shuffle_duration_tablet' => '',
            'effect5_shuffle_duration_mobile' => '',
            'effect5_char_delay' => '0.03',
            'effect5_char_delay_tablet' => '',
            'effect5_char_delay_mobile' => '',
            // Effect 6: Rotation (with viewport variants)
            'effect6_rotation' => '-90',
            'effect6_rotation_tablet' => '',
            'effect6_rotation_mobile' => '',
            'effect6_x_percent' => '-5',
            'effect6_x_percent_tablet' => '',
            'effect6_x_percent_mobile' => '',
            'effect6_origin_x' => '0',
            'effect6_origin_x_tablet' => '',
            'effect6_origin_x_mobile' => '',
            'effect6_origin_y' => '100',
            'effect6_origin_y_tablet' => '',
            'effect6_origin_y_mobile' => '',
            // Effect 7: Move Away (with viewport variants)
            'effect7_move_distance' => '',
            'effect7_move_distance_tablet' => '',
            'effect7_move_distance_mobile' => '',
            // Pro features per instance
            'effect_mappings' => array(),
            'enable_filtering' => '0',
            'filter_mode' => 'include',
            'selected_post_types' => array(),
            'include_pages' => '0',
            'include_posts' => '0',
            'selected_items' => array(),
        );
    }
    
    /**
     * Get all instances
     */
    public function get_all_instances() {
        $instances = get_option('caa_instances', array());
        if (!is_array($instances)) {
            return array();
        }
        return $instances;
    }
    
    /**
     * Get a single logo instance by ID
     */
    public function get_logo_instance($id) {
        $instances = $this->get_all_instances();
        if (isset($instances[$id])) {
            // Merge with defaults to ensure all keys exist
            return array_merge($this->get_default_instance_data(), $instances[$id]);
        }
        return null;
    }
    
    /**
     * Save a single instance
     */
    public function save_instance($id, $data) {
        $instances = $this->get_all_instances();
        
        // Enforce max instances limit (except when updating existing)
        if (!isset($instances[$id]) && count($instances) >= CAA_MAX_INSTANCES) {
            return false;
        }
        
        // Sanitize the instance data
        $instances[$id] = $this->sanitize_instance_data($data);
        
        return update_option('caa_instances', $instances);
    }
    
    /**
     * Delete a single instance
     */
    public function delete_instance($id) {
        $instances = $this->get_all_instances();
        
        if (isset($instances[$id])) {
            unset($instances[$id]);
            return update_option('caa_instances', $instances);
        }
        
        return false;
    }
    
    /**
     * Get all enabled instances
     */
    public function get_enabled_instances() {
        $instances = $this->get_all_instances();
        $enabled = array();
        
        foreach ($instances as $id => $instance) {
            $instance = array_merge($this->get_default_instance_data(), $instance);
            if (!empty($instance['enabled'])) {
                $enabled[$id] = $instance;
            }
        }
        
        return $enabled;
    }
    
    /**
     * Get the next available instance ID
     */
    public function get_next_instance_id() {
        $instances = $this->get_all_instances();
        $max_id = 0;
        
        foreach (array_keys($instances) as $id) {
            if (is_numeric($id) && intval($id) > $max_id) {
                $max_id = intval($id);
            }
        }
        
        return $max_id + 1;
    }
    
    /**
     * Check if migration is needed and perform it
     */
    public function maybe_migrate_legacy_settings() {
        // Check if instances already exist
        $instances = $this->get_all_instances();
        if (!empty($instances)) {
            return; // Already migrated or has instances
        }
        
        // Check if legacy settings exist
        $legacy_logo_id = get_option('caa_logo_id', '');
        if (empty($legacy_logo_id)) {
            return; // No legacy settings to migrate
        }
        
        // Migrate legacy settings to Instance 1
        $this->migrate_legacy_settings_to_instances();
    }
    
    /**
     * Migrate legacy settings to the new instances structure
     */
    private function migrate_legacy_settings_to_instances() {
        $instance_data = array(
            'enabled' => true,
            'logo_id' => get_option('caa_logo_id', ''),
            'selected_effect' => get_option('caa_selected_effect', '1'),
            'included_elements' => get_option('caa_included_elements', ''),
            'excluded_elements' => get_option('caa_excluded_elements', ''),
            'global_offset' => get_option('caa_global_offset', '0'),
            'debug_mode' => get_option('caa_debug_mode', '0'),
            'duration' => get_option('caa_duration', '0.6'),
            'ease' => get_option('caa_ease', 'power4'),
            'offset_start' => get_option('caa_offset_start', '30'),
            'offset_end' => get_option('caa_offset_end', '10'),
            // Effect settings
            'effect1_scale_down' => get_option('caa_effect1_scale_down', '0'),
            'effect1_origin_x' => get_option('caa_effect1_origin_x', '0'),
            'effect1_origin_y' => get_option('caa_effect1_origin_y', '50'),
            'effect2_blur_amount' => get_option('caa_effect2_blur_amount', '5'),
            'effect2_blur_scale' => get_option('caa_effect2_blur_scale', '0.9'),
            'effect2_blur_duration' => get_option('caa_effect2_blur_duration', '0.2'),
            'effect4_text_x_range' => get_option('caa_effect4_text_x_range', '50'),
            'effect4_text_y_range' => get_option('caa_effect4_text_y_range', '40'),
            'effect4_stagger_amount' => get_option('caa_effect4_stagger_amount', '0.03'),
            'effect5_shuffle_iterations' => get_option('caa_effect5_shuffle_iterations', '2'),
            'effect5_shuffle_duration' => get_option('caa_effect5_shuffle_duration', '0.03'),
            'effect5_char_delay' => get_option('caa_effect5_char_delay', '0.03'),
            'effect6_rotation' => get_option('caa_effect6_rotation', '-90'),
            'effect6_x_percent' => get_option('caa_effect6_x_percent', '-5'),
            'effect6_origin_x' => get_option('caa_effect6_origin_x', '0'),
            'effect6_origin_y' => get_option('caa_effect6_origin_y', '100'),
            'effect7_move_distance' => get_option('caa_effect7_move_distance', ''),
            // Pro features
            'effect_mappings' => get_option('caa_pro_effect_mappings', array()),
            'enable_filtering' => get_option('caa_pro_enable_filtering', '0'),
            'filter_mode' => get_option('caa_pro_filter_mode', 'include'),
            'selected_post_types' => get_option('caa_pro_selected_post_types', array()),
            'include_pages' => get_option('caa_pro_include_pages', '0'),
            'include_posts' => get_option('caa_pro_include_posts', '0'),
            'selected_items' => get_option('caa_pro_selected_items', array()),
        );
        
        // Save as Instance 1
        $instances = array(
            1 => $instance_data
        );
        
        update_option('caa_instances', $instances);
        
        // Set a flag to show migration notice
        set_transient('caa_migration_notice', true, 60);
    }
    
    /**
     * Ensure at least Instance 1 exists (for existing installations)
     */
    public function ensure_instance_exists() {
        $instances = $this->get_all_instances();
        
        // Create Instance 1 if no instances exist
        if (empty($instances)) {
            $default_data = $this->get_default_instance_data();
            $default_data['logo_id'] = ''; // Empty by default
            
            $instances = array(
                1 => $default_data
            );
            
            update_option('caa_instances', $instances);
        }
    }
    
    /**
     * Sanitize instance data
     */
    public function sanitize_instance_data($data) {
        $defaults = $this->get_default_instance_data();
        $sanitized = array();
        
        // Boolean/checkbox fields
        $sanitized['enabled'] = !empty($data['enabled']);
        $sanitized['debug_mode'] = isset($data['debug_mode']) ? sanitize_text_field($data['debug_mode']) : '0';
        $sanitized['enable_filtering'] = isset($data['enable_filtering']) ? sanitize_text_field($data['enable_filtering']) : '0';
        $sanitized['include_pages'] = isset($data['include_pages']) ? sanitize_text_field($data['include_pages']) : '0';
        $sanitized['include_posts'] = isset($data['include_posts']) ? sanitize_text_field($data['include_posts']) : '0';
        
        // Text fields
        $sanitized['logo_id'] = isset($data['logo_id']) ? sanitize_text_field($data['logo_id']) : '';
        $sanitized['included_elements'] = isset($data['included_elements']) ? sanitize_textarea_field($data['included_elements']) : '';
        $sanitized['excluded_elements'] = isset($data['excluded_elements']) ? sanitize_textarea_field($data['excluded_elements']) : '';
        
        // Effect selection
        $sanitized['selected_effect'] = isset($data['selected_effect']) ? $this->sanitize_effect($data['selected_effect']) : '1';
        
        // Offset fields (with viewport variants)
        $sanitized['global_offset'] = isset($data['global_offset']) ? $this->sanitize_offset($data['global_offset']) : '0';
        $sanitized['offset_start'] = isset($data['offset_start']) ? $this->sanitize_offset($data['offset_start']) : '30';
        $sanitized['offset_start_tablet'] = isset($data['offset_start_tablet']) ? $this->sanitize_viewport_value($data['offset_start_tablet'], 'offset') : '';
        $sanitized['offset_start_mobile'] = isset($data['offset_start_mobile']) ? $this->sanitize_viewport_value($data['offset_start_mobile'], 'offset') : '';
        $sanitized['offset_end'] = isset($data['offset_end']) ? $this->sanitize_offset($data['offset_end']) : '10';
        $sanitized['offset_end_tablet'] = isset($data['offset_end_tablet']) ? $this->sanitize_viewport_value($data['offset_end_tablet'], 'offset') : '';
        $sanitized['offset_end_mobile'] = isset($data['offset_end_mobile']) ? $this->sanitize_viewport_value($data['offset_end_mobile'], 'offset') : '';
        
        // Float fields (with viewport variants)
        $sanitized['duration'] = isset($data['duration']) ? $this->sanitize_float($data['duration']) : '0.6';
        $sanitized['duration_tablet'] = isset($data['duration_tablet']) ? $this->sanitize_viewport_value($data['duration_tablet'], 'float') : '';
        $sanitized['duration_mobile'] = isset($data['duration_mobile']) ? $this->sanitize_viewport_value($data['duration_mobile'], 'float') : '';
        
        // Ease (with viewport variants)
        $sanitized['ease'] = isset($data['ease']) ? $this->sanitize_ease($data['ease']) : 'power4';
        $sanitized['ease_tablet'] = isset($data['ease_tablet']) ? $this->sanitize_viewport_value($data['ease_tablet'], 'ease') : '';
        $sanitized['ease_mobile'] = isset($data['ease_mobile']) ? $this->sanitize_viewport_value($data['ease_mobile'], 'ease') : '';
        
        // Filter mode
        $sanitized['filter_mode'] = isset($data['filter_mode']) ? $this->sanitize_filter_mode($data['filter_mode']) : 'include';
        
        // Effect 1 settings (with viewport variants)
        $sanitized['effect1_scale_down'] = isset($data['effect1_scale_down']) ? $this->sanitize_float($data['effect1_scale_down']) : '0';
        $sanitized['effect1_scale_down_tablet'] = isset($data['effect1_scale_down_tablet']) ? $this->sanitize_viewport_value($data['effect1_scale_down_tablet'], 'float') : '';
        $sanitized['effect1_scale_down_mobile'] = isset($data['effect1_scale_down_mobile']) ? $this->sanitize_viewport_value($data['effect1_scale_down_mobile'], 'float') : '';
        $sanitized['effect1_origin_x'] = isset($data['effect1_origin_x']) ? $this->sanitize_percent($data['effect1_origin_x']) : '0';
        $sanitized['effect1_origin_x_tablet'] = isset($data['effect1_origin_x_tablet']) ? $this->sanitize_viewport_value($data['effect1_origin_x_tablet'], 'percent') : '';
        $sanitized['effect1_origin_x_mobile'] = isset($data['effect1_origin_x_mobile']) ? $this->sanitize_viewport_value($data['effect1_origin_x_mobile'], 'percent') : '';
        $sanitized['effect1_origin_y'] = isset($data['effect1_origin_y']) ? $this->sanitize_percent($data['effect1_origin_y']) : '50';
        $sanitized['effect1_origin_y_tablet'] = isset($data['effect1_origin_y_tablet']) ? $this->sanitize_viewport_value($data['effect1_origin_y_tablet'], 'percent') : '';
        $sanitized['effect1_origin_y_mobile'] = isset($data['effect1_origin_y_mobile']) ? $this->sanitize_viewport_value($data['effect1_origin_y_mobile'], 'percent') : '';
        
        // Effect 2 settings (with viewport variants)
        $sanitized['effect2_blur_amount'] = isset($data['effect2_blur_amount']) ? $this->sanitize_float($data['effect2_blur_amount']) : '5';
        $sanitized['effect2_blur_amount_tablet'] = isset($data['effect2_blur_amount_tablet']) ? $this->sanitize_viewport_value($data['effect2_blur_amount_tablet'], 'float') : '';
        $sanitized['effect2_blur_amount_mobile'] = isset($data['effect2_blur_amount_mobile']) ? $this->sanitize_viewport_value($data['effect2_blur_amount_mobile'], 'float') : '';
        $sanitized['effect2_blur_scale'] = isset($data['effect2_blur_scale']) ? $this->sanitize_float($data['effect2_blur_scale']) : '0.9';
        $sanitized['effect2_blur_scale_tablet'] = isset($data['effect2_blur_scale_tablet']) ? $this->sanitize_viewport_value($data['effect2_blur_scale_tablet'], 'float') : '';
        $sanitized['effect2_blur_scale_mobile'] = isset($data['effect2_blur_scale_mobile']) ? $this->sanitize_viewport_value($data['effect2_blur_scale_mobile'], 'float') : '';
        $sanitized['effect2_blur_duration'] = isset($data['effect2_blur_duration']) ? $this->sanitize_float($data['effect2_blur_duration']) : '0.2';
        $sanitized['effect2_blur_duration_tablet'] = isset($data['effect2_blur_duration_tablet']) ? $this->sanitize_viewport_value($data['effect2_blur_duration_tablet'], 'float') : '';
        $sanitized['effect2_blur_duration_mobile'] = isset($data['effect2_blur_duration_mobile']) ? $this->sanitize_viewport_value($data['effect2_blur_duration_mobile'], 'float') : '';
        
        // Effect 4 settings (with viewport variants)
        $sanitized['effect4_text_x_range'] = isset($data['effect4_text_x_range']) ? $this->sanitize_offset($data['effect4_text_x_range']) : '50';
        $sanitized['effect4_text_x_range_tablet'] = isset($data['effect4_text_x_range_tablet']) ? $this->sanitize_viewport_value($data['effect4_text_x_range_tablet'], 'offset') : '';
        $sanitized['effect4_text_x_range_mobile'] = isset($data['effect4_text_x_range_mobile']) ? $this->sanitize_viewport_value($data['effect4_text_x_range_mobile'], 'offset') : '';
        $sanitized['effect4_text_y_range'] = isset($data['effect4_text_y_range']) ? $this->sanitize_offset($data['effect4_text_y_range']) : '40';
        $sanitized['effect4_text_y_range_tablet'] = isset($data['effect4_text_y_range_tablet']) ? $this->sanitize_viewport_value($data['effect4_text_y_range_tablet'], 'offset') : '';
        $sanitized['effect4_text_y_range_mobile'] = isset($data['effect4_text_y_range_mobile']) ? $this->sanitize_viewport_value($data['effect4_text_y_range_mobile'], 'offset') : '';
        $sanitized['effect4_stagger_amount'] = isset($data['effect4_stagger_amount']) ? $this->sanitize_float($data['effect4_stagger_amount']) : '0.03';
        $sanitized['effect4_stagger_amount_tablet'] = isset($data['effect4_stagger_amount_tablet']) ? $this->sanitize_viewport_value($data['effect4_stagger_amount_tablet'], 'float') : '';
        $sanitized['effect4_stagger_amount_mobile'] = isset($data['effect4_stagger_amount_mobile']) ? $this->sanitize_viewport_value($data['effect4_stagger_amount_mobile'], 'float') : '';
        
        // Effect 5 settings (with viewport variants)
        $sanitized['effect5_shuffle_iterations'] = isset($data['effect5_shuffle_iterations']) ? $this->sanitize_offset($data['effect5_shuffle_iterations']) : '2';
        $sanitized['effect5_shuffle_iterations_tablet'] = isset($data['effect5_shuffle_iterations_tablet']) ? $this->sanitize_viewport_value($data['effect5_shuffle_iterations_tablet'], 'offset') : '';
        $sanitized['effect5_shuffle_iterations_mobile'] = isset($data['effect5_shuffle_iterations_mobile']) ? $this->sanitize_viewport_value($data['effect5_shuffle_iterations_mobile'], 'offset') : '';
        $sanitized['effect5_shuffle_duration'] = isset($data['effect5_shuffle_duration']) ? $this->sanitize_float($data['effect5_shuffle_duration']) : '0.03';
        $sanitized['effect5_shuffle_duration_tablet'] = isset($data['effect5_shuffle_duration_tablet']) ? $this->sanitize_viewport_value($data['effect5_shuffle_duration_tablet'], 'float') : '';
        $sanitized['effect5_shuffle_duration_mobile'] = isset($data['effect5_shuffle_duration_mobile']) ? $this->sanitize_viewport_value($data['effect5_shuffle_duration_mobile'], 'float') : '';
        $sanitized['effect5_char_delay'] = isset($data['effect5_char_delay']) ? $this->sanitize_float($data['effect5_char_delay']) : '0.03';
        $sanitized['effect5_char_delay_tablet'] = isset($data['effect5_char_delay_tablet']) ? $this->sanitize_viewport_value($data['effect5_char_delay_tablet'], 'float') : '';
        $sanitized['effect5_char_delay_mobile'] = isset($data['effect5_char_delay_mobile']) ? $this->sanitize_viewport_value($data['effect5_char_delay_mobile'], 'float') : '';
        
        // Effect 6 settings (with viewport variants)
        $sanitized['effect6_rotation'] = isset($data['effect6_rotation']) ? $this->sanitize_offset($data['effect6_rotation']) : '-90';
        $sanitized['effect6_rotation_tablet'] = isset($data['effect6_rotation_tablet']) ? $this->sanitize_viewport_value($data['effect6_rotation_tablet'], 'offset') : '';
        $sanitized['effect6_rotation_mobile'] = isset($data['effect6_rotation_mobile']) ? $this->sanitize_viewport_value($data['effect6_rotation_mobile'], 'offset') : '';
        $sanitized['effect6_x_percent'] = isset($data['effect6_x_percent']) ? $this->sanitize_offset($data['effect6_x_percent']) : '-5';
        $sanitized['effect6_x_percent_tablet'] = isset($data['effect6_x_percent_tablet']) ? $this->sanitize_viewport_value($data['effect6_x_percent_tablet'], 'offset') : '';
        $sanitized['effect6_x_percent_mobile'] = isset($data['effect6_x_percent_mobile']) ? $this->sanitize_viewport_value($data['effect6_x_percent_mobile'], 'offset') : '';
        $sanitized['effect6_origin_x'] = isset($data['effect6_origin_x']) ? $this->sanitize_percent($data['effect6_origin_x']) : '0';
        $sanitized['effect6_origin_x_tablet'] = isset($data['effect6_origin_x_tablet']) ? $this->sanitize_viewport_value($data['effect6_origin_x_tablet'], 'percent') : '';
        $sanitized['effect6_origin_x_mobile'] = isset($data['effect6_origin_x_mobile']) ? $this->sanitize_viewport_value($data['effect6_origin_x_mobile'], 'percent') : '';
        $sanitized['effect6_origin_y'] = isset($data['effect6_origin_y']) ? $this->sanitize_percent($data['effect6_origin_y']) : '100';
        $sanitized['effect6_origin_y_tablet'] = isset($data['effect6_origin_y_tablet']) ? $this->sanitize_viewport_value($data['effect6_origin_y_tablet'], 'percent') : '';
        $sanitized['effect6_origin_y_mobile'] = isset($data['effect6_origin_y_mobile']) ? $this->sanitize_viewport_value($data['effect6_origin_y_mobile'], 'percent') : '';
        
        // Effect 7 settings (with viewport variants)
        $sanitized['effect7_move_distance'] = isset($data['effect7_move_distance']) ? $this->sanitize_move_away($data['effect7_move_distance']) : '';
        $sanitized['effect7_move_distance_tablet'] = isset($data['effect7_move_distance_tablet']) ? $this->sanitize_viewport_value($data['effect7_move_distance_tablet'], 'move_away') : '';
        $sanitized['effect7_move_distance_mobile'] = isset($data['effect7_move_distance_mobile']) ? $this->sanitize_viewport_value($data['effect7_move_distance_mobile'], 'move_away') : '';
        
        // Array fields
        $sanitized['effect_mappings'] = isset($data['effect_mappings']) ? $this->sanitize_effect_mappings($data['effect_mappings']) : array();
        $sanitized['selected_post_types'] = isset($data['selected_post_types']) ? $this->sanitize_post_types($data['selected_post_types']) : array();
        $sanitized['selected_items'] = isset($data['selected_items']) ? $this->sanitize_post_ids($data['selected_items']) : array();
        
        return $sanitized;
    }
    
    /**
     * Sanitize viewport-specific value (allows empty string for inheritance)
     */
    public function sanitize_viewport_value($value, $type = 'text') {
        // Empty string means inherit from desktop - preserve it
        if ($value === '' || $value === null) {
            return '';
        }
        
        // Apply appropriate sanitization based on type
        switch ($type) {
            case 'float':
                return $this->sanitize_float($value);
            case 'offset':
                return $this->sanitize_offset($value);
            case 'percent':
                return $this->sanitize_percent($value);
            case 'ease':
                return $this->sanitize_ease($value);
            case 'move_away':
                return $this->sanitize_move_away($value);
            default:
                return sanitize_text_field($value);
        }
    }
    
    /**
     * Get instance display name (uses logo ID or fallback)
     */
    public function get_instance_name($id, $instance = null) {
        if ($instance === null) {
            $instance = $this->get_logo_instance($id);
        }
        
        if ($instance && !empty($instance['logo_id'])) {
            return $instance['logo_id'];
        }
        
        /* translators: %d: Instance ID */
        return sprintf(__('Instance %d', 'logo-collision'), $id);
    }
    
    /**
     * Add type="module" to ES6 module scripts
     */
    public function add_module_type($tag, $handle, $src) {
        $module_handles = array('caa-utils', 'caa-text-splitter', 'caa-frontend');
        if (in_array($handle, $module_handles)) {
            // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- This is a filter modifying already-enqueued scripts
            $tag = '<script type="module" src="' . esc_url($src) . '"></script>';
        }
        return $tag;
    }
    
    /**
     * Add settings page to WordPress admin
     */
    public function add_settings_page() {
        add_options_page(
            __('Logo Collision Settings', 'logo-collision'),
            __('Logo Collision', 'logo-collision'),
            'manage_options',
            'logo-collision',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Handle settings export - runs early via admin_init to send headers before any output
     */
    public function handle_settings_export() {
        // Check if this is an export request
        if (!isset($_POST['caa_export_settings'])) {
            return;
        }
        
        // Verify nonce and capabilities
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated -- Nonce verification
        if (!wp_verify_nonce(isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '', 'caa_export_settings_nonce')) {
            return;
        }
        
        // List of all options to export
        $options_to_export = array(
            // Core settings
            'caa_logo_id',
            'caa_selected_effect',
            'caa_included_elements',
            'caa_excluded_elements',
            'caa_global_offset',
            'caa_debug_mode',
            
            // Mobile settings
            'caa_disable_mobile',
            'caa_mobile_breakpoint',
            
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
            
            // Instances (Pro feature but structure exists in both)
            'caa_instances',
        );
        
        // Collect all settings
        $export_data = array(
            'plugin' => 'logo-collision',
            'version' => CAA_VERSION,
            'exported_at' => gmdate('c'),
            'is_pro' => defined('LOGO_COLLISION_PRO') && LOGO_COLLISION_PRO,
            'settings' => array(),
        );
        
        foreach ($options_to_export as $option_name) {
            $value = get_option($option_name);
            if ($value !== false) {
                $export_data['settings'][$option_name] = $value;
            }
        }
        
        // Send JSON file download
        $filename = 'logo-collision-settings-' . gmdate('Y-m-d') . '.json';
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON export, headers already set
        echo wp_json_encode($export_data, JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('caa_settings_group', 'caa_logo_id', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));
        
        register_setting('caa_settings_group', 'caa_selected_effect', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_effect'),
            'default' => '1'
        ));
        
        register_setting('caa_settings_group', 'caa_included_elements', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default' => ''
        ));
        
        register_setting('caa_settings_group', 'caa_excluded_elements', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default' => ''
        ));
        
        register_setting('caa_settings_group', 'caa_global_offset', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_offset'),
            'default' => '0'
        ));
        
        register_setting('caa_settings_group', 'caa_debug_mode', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '0'
        ));
        
        // Mobile disable settings
        register_setting('caa_settings_group', 'caa_disable_mobile', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '0'
        ));
        
        // Viewport breakpoints (global settings)
        register_setting('caa_settings_group', 'caa_tablet_breakpoint', array(
            'type' => 'string',
            'sanitize_callback' => 'absint',
            'default' => '782'
        ));
        
        register_setting('caa_settings_group', 'caa_mobile_breakpoint', array(
            'type' => 'string',
            'sanitize_callback' => 'absint',
            'default' => '600'
        ));
        
        // Global animation settings
        register_setting('caa_settings_group', 'caa_duration', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_float'),
            'default' => '0.6'
        ));
        
        register_setting('caa_settings_group', 'caa_ease', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_ease'),
            'default' => 'power4'
        ));
        
        register_setting('caa_settings_group', 'caa_offset_start', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_offset'),
            'default' => '30'
        ));
        
        register_setting('caa_settings_group', 'caa_offset_end', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_offset'),
            'default' => '10'
        ));
        
        // Effect 1: Scale settings
        register_setting('caa_settings_group', 'caa_effect1_scale_down', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_float'),
            'default' => '0'
        ));
        
        register_setting('caa_settings_group', 'caa_effect1_origin_x', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_percent'),
            'default' => '0'
        ));
        
        register_setting('caa_settings_group', 'caa_effect1_origin_y', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_percent'),
            'default' => '50'
        ));
        
        // Effect 2: Blur settings
        register_setting('caa_settings_group', 'caa_effect2_blur_amount', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_float'),
            'default' => '5'
        ));
        
        register_setting('caa_settings_group', 'caa_effect2_blur_scale', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_float'),
            'default' => '0.9'
        ));
        
        register_setting('caa_settings_group', 'caa_effect2_blur_duration', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_float'),
            'default' => '0.2'
        ));
        
        // Effect 4: Text Split settings
        register_setting('caa_settings_group', 'caa_effect4_text_x_range', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_offset'),
            'default' => '50'
        ));
        
        register_setting('caa_settings_group', 'caa_effect4_text_y_range', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_offset'),
            'default' => '40'
        ));
        
        register_setting('caa_settings_group', 'caa_effect4_stagger_amount', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_float'),
            'default' => '0.03'
        ));
        
        // Effect 5: Character Shuffle settings
        register_setting('caa_settings_group', 'caa_effect5_shuffle_iterations', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_offset'),
            'default' => '2'
        ));
        
        register_setting('caa_settings_group', 'caa_effect5_shuffle_duration', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_float'),
            'default' => '0.03'
        ));
        
        register_setting('caa_settings_group', 'caa_effect5_char_delay', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_float'),
            'default' => '0.03'
        ));
        
        // Effect 6: Rotation settings
        register_setting('caa_settings_group', 'caa_effect6_rotation', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_offset'),
            'default' => '-90'
        ));
        
        register_setting('caa_settings_group', 'caa_effect6_x_percent', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_offset'),
            'default' => '-5'
        ));
        
        register_setting('caa_settings_group', 'caa_effect6_origin_x', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_percent'),
            'default' => '0'
        ));
        
        register_setting('caa_settings_group', 'caa_effect6_origin_y', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_percent'),
            'default' => '100'
        ));
        
        // Effect 7: Move Away settings
        register_setting('caa_settings_group', 'caa_effect7_move_distance', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_move_away'),
            'default' => ''
        ));
        
        // Pro Version: Effect mappings
        register_setting('caa_settings_group', 'caa_pro_effect_mappings', array(
            'type' => 'array',
            'sanitize_callback' => array($this, 'sanitize_effect_mappings'),
            'default' => array()
        ));
        
        // Pro Version: Filtering settings
        register_setting('caa_settings_group', 'caa_pro_enable_filtering', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '0'
        ));
        
        register_setting('caa_settings_group', 'caa_pro_filter_mode', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_filter_mode'),
            'default' => 'include'
        ));
        
        register_setting('caa_settings_group', 'caa_pro_selected_post_types', array(
            'type' => 'array',
            'sanitize_callback' => array($this, 'sanitize_post_types'),
            'default' => array()
        ));
        
        register_setting('caa_settings_group', 'caa_pro_include_pages', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '0'
        ));
        
        register_setting('caa_settings_group', 'caa_pro_include_posts', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '0'
        ));
        
        register_setting('caa_settings_group', 'caa_pro_selected_items', array(
            'type' => 'array',
            'sanitize_callback' => array($this, 'sanitize_post_ids'),
            'default' => array()
        ));
        
        // Pro Version: Instances (multiple logos/settings)
        register_setting('caa_settings_group', 'caa_instances', array(
            'type' => 'array',
            'sanitize_callback' => array($this, 'sanitize_instances'),
            'default' => array()
        ));
    }
    
    /**
     * Sanitize all instances
     */
    public function sanitize_instances($value) {
        if (!is_array($value)) {
            return array();
        }
        
        $sanitized = array();
        $count = 0;
        
        foreach ($value as $id => $instance_data) {
            if ($count >= CAA_MAX_INSTANCES) {
                break;
            }
            
            $sanitized[$id] = $this->sanitize_instance_data($instance_data);
            $count++;
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize effect selection
     */
    public function sanitize_effect($value) {
        $valid_effects = array('1', '2', '3', '4', '5', '6', '7');
        return in_array($value, $valid_effects) ? $value : '1';
    }
    
    /**
     * Sanitize offset value
     */
    public function sanitize_offset($value) {
        // Allow positive and negative integers
        $value = trim($value);
        if ($value === '' || $value === null) {
            return '0';
        }
        // Convert to integer and back to string to ensure it's a valid number
        $int_value = intval($value);
        return (string)$int_value;
    }
    
    /**
     * Sanitize float value
     */
    public function sanitize_float($value) {
        $value = trim($value);
        if ($value === '' || $value === null) {
            return '0';
        }
        $float_value = floatval($value);
        return (string)$float_value;
    }
    
    /**
     * Sanitize percentage value (0-100)
     */
    public function sanitize_percent($value) {
        $value = trim($value);
        if ($value === '' || $value === null) {
            return '0';
        }
        $int_value = intval($value);
        // Clamp between 0 and 100
        $int_value = max(0, min(100, $int_value));
        return (string)$int_value;
    }
    
    /**
     * Sanitize easing type
     */
    public function sanitize_ease($value) {
        $valid_eases = array('power1', 'power2', 'power3', 'power4', 'expo', 'sine', 'back', 'elastic', 'bounce', 'none');
        return in_array($value, $valid_eases) ? $value : 'power4';
    }
    
    /**
     * Sanitize move away distance (supports px or %)
     */
    public function sanitize_move_away($value) {
        $value = trim($value);
        if ($value === '' || $value === null) {
            return '';
        }
        // Check if it ends with % or px
        if (preg_match('/^([+-]?\d+(?:\.\d+)?)(px|%)$/i', $value, $matches)) {
            $number = floatval($matches[1]);
            $unit = strtolower($matches[2]);
            return (string) $number . $unit;
        }
        // If no unit, assume px
        $number = floatval($value);
        return (string) $number . 'px';
    }
    
    /**
     * Sanitize effect mappings array
     */
    public function sanitize_effect_mappings($value) {
        if (!is_array($value)) {
            return array();
        }
        
        $sanitized = array();
        foreach ($value as $mapping) {
            if (isset($mapping['selector']) && isset($mapping['effect'])) {
                $selector = sanitize_text_field($mapping['selector']);
                $effect = $this->sanitize_effect($mapping['effect']);
                $override_enabled = isset($mapping['override_enabled']) && $mapping['override_enabled'];
                
                if (!empty($selector)) {
                    $mapping_data = array(
                        'selector' => $selector,
                        'effect' => $effect,
                        'override_enabled' => $override_enabled
                    );
                    
                    // Only save settings if override is enabled
                    if ($override_enabled && isset($mapping['settings']) && is_array($mapping['settings'])) {
                        $settings = $mapping['settings'];
                        $mapping_data['settings'] = array(
                            // Global animation settings
                            'duration' => isset($settings['duration']) ? $this->sanitize_float($settings['duration']) : '0.6',
                            'ease' => isset($settings['ease']) ? $this->sanitize_ease($settings['ease']) : 'power4',
                            'offset_start' => isset($settings['offset_start']) ? $this->sanitize_offset($settings['offset_start']) : '30',
                            'offset_end' => isset($settings['offset_end']) ? $this->sanitize_offset($settings['offset_end']) : '10',
                        );
                        
                        // Effect-specific settings based on selected effect
                        switch ($effect) {
                            case '1': // Scale
                                $mapping_data['settings']['effect1_scale_down'] = isset($settings['effect1_scale_down']) ? $this->sanitize_float($settings['effect1_scale_down']) : '0';
                                $mapping_data['settings']['effect1_origin_x'] = isset($settings['effect1_origin_x']) ? $this->sanitize_percent($settings['effect1_origin_x']) : '0';
                                $mapping_data['settings']['effect1_origin_y'] = isset($settings['effect1_origin_y']) ? $this->sanitize_percent($settings['effect1_origin_y']) : '50';
                                break;
                            case '2': // Blur
                                $mapping_data['settings']['effect2_blur_amount'] = isset($settings['effect2_blur_amount']) ? $this->sanitize_float($settings['effect2_blur_amount']) : '5';
                                $mapping_data['settings']['effect2_blur_scale'] = isset($settings['effect2_blur_scale']) ? $this->sanitize_float($settings['effect2_blur_scale']) : '0.9';
                                $mapping_data['settings']['effect2_blur_duration'] = isset($settings['effect2_blur_duration']) ? $this->sanitize_float($settings['effect2_blur_duration']) : '0.2';
                                break;
                            case '4': // Text Split
                                $mapping_data['settings']['effect4_text_x_range'] = isset($settings['effect4_text_x_range']) ? $this->sanitize_offset($settings['effect4_text_x_range']) : '50';
                                $mapping_data['settings']['effect4_text_y_range'] = isset($settings['effect4_text_y_range']) ? $this->sanitize_offset($settings['effect4_text_y_range']) : '40';
                                $mapping_data['settings']['effect4_stagger_amount'] = isset($settings['effect4_stagger_amount']) ? $this->sanitize_float($settings['effect4_stagger_amount']) : '0.03';
                                break;
                            case '5': // Character Shuffle
                                $mapping_data['settings']['effect5_shuffle_iterations'] = isset($settings['effect5_shuffle_iterations']) ? $this->sanitize_offset($settings['effect5_shuffle_iterations']) : '2';
                                $mapping_data['settings']['effect5_shuffle_duration'] = isset($settings['effect5_shuffle_duration']) ? $this->sanitize_float($settings['effect5_shuffle_duration']) : '0.03';
                                $mapping_data['settings']['effect5_char_delay'] = isset($settings['effect5_char_delay']) ? $this->sanitize_float($settings['effect5_char_delay']) : '0.03';
                                break;
                            case '6': // Rotation
                                $mapping_data['settings']['effect6_rotation'] = isset($settings['effect6_rotation']) ? $this->sanitize_offset($settings['effect6_rotation']) : '-90';
                                $mapping_data['settings']['effect6_x_percent'] = isset($settings['effect6_x_percent']) ? $this->sanitize_offset($settings['effect6_x_percent']) : '-5';
                                $mapping_data['settings']['effect6_origin_x'] = isset($settings['effect6_origin_x']) ? $this->sanitize_percent($settings['effect6_origin_x']) : '0';
                                $mapping_data['settings']['effect6_origin_y'] = isset($settings['effect6_origin_y']) ? $this->sanitize_percent($settings['effect6_origin_y']) : '100';
                                break;
                            case '7': // Move Away
                                $mapping_data['settings']['effect7_move_distance'] = isset($settings['effect7_move_distance']) ? $this->sanitize_move_away($settings['effect7_move_distance']) : '';
                                break;
                            // Effect 3 (Slide Text) uses only global settings
                        }
                    }
                    
                    $sanitized[] = $mapping_data;
                }
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize filter mode
     */
    public function sanitize_filter_mode($value) {
        $valid_modes = array('include', 'exclude');
        return in_array($value, $valid_modes, true) ? $value : 'include';
    }
    
    /**
     * Sanitize post types array
     */
    public function sanitize_post_types($value) {
        if (!is_array($value)) {
            return array();
        }
        
        $valid_post_types = array_keys(get_post_types(array('public' => true), 'names'));
        $sanitized = array();
        
        foreach ($value as $post_type) {
            $post_type = sanitize_text_field($post_type);
            if (in_array($post_type, $valid_post_types, true)) {
                $sanitized[] = $post_type;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize post IDs array
     */
    public function sanitize_post_ids($value) {
        if (!is_array($value)) {
            return array();
        }
        
        $sanitized = array();
        foreach ($value as $id) {
            $id = absint($id);
            if ($id > 0) {
                $sanitized[] = $id;
            }
        }
        
        return array_unique($sanitized);
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        require_once CAA_PLUGIN_DIR . 'admin/settings-page.php';
    }
    
    /**
     * Check if a specific instance should load on current page
     */
    private function should_load_instance($instance_data) {
        $enable_filtering = isset($instance_data['enable_filtering']) ? $instance_data['enable_filtering'] : '0';
        
        // If filtering is disabled, run everywhere (default behavior)
        if ($enable_filtering !== '1') {
            return true;
        }
        
        $filter_mode = isset($instance_data['filter_mode']) ? $instance_data['filter_mode'] : 'include';
        $selected_post_types = isset($instance_data['selected_post_types']) ? $instance_data['selected_post_types'] : array();
        $include_pages = isset($instance_data['include_pages']) ? $instance_data['include_pages'] : '0';
        $include_posts = isset($instance_data['include_posts']) ? $instance_data['include_posts'] : '0';
        $selected_items = isset($instance_data['selected_items']) ? $instance_data['selected_items'] : array();
        
        $current_post_id = 0;
        $current_post_type = '';
        $matches_rule = false;
        
        // Get current post information if on a singular page
        if (is_singular()) {
            global $post;
            if ($post) {
                $current_post_id = $post->ID;
                $current_post_type = $post->post_type;
            }
        }
        
        // Check if current page matches any rule
        // Check individual items first (most specific)
        if (!empty($selected_items) && $current_post_id > 0 && in_array($current_post_id, $selected_items, true)) {
            $matches_rule = true;
        }
        // Check post type
        elseif (!empty($current_post_type) && in_array($current_post_type, $selected_post_types, true)) {
            $matches_rule = true;
        }
        // Check pages checkbox
        elseif ($include_pages === '1' && is_page()) {
            $matches_rule = true;
        }
        // Check posts checkbox
        elseif ($include_posts === '1' && is_single() && get_post_type() === 'post') {
            $matches_rule = true;
        }
        
        // Apply filter mode logic
        if ($filter_mode === 'include') {
            // Include mode: only load if a rule matched
            return $matches_rule;
        } else {
            // Exclude mode: load everywhere except where rules match
            return !$matches_rule;
        }
    }
    
    /**
     * Legacy method for backward compatibility
     * Check if plugin should load on current page (uses legacy single-instance settings)
     */
    private function should_load_plugin() {
        // Check if we have instances
        $instances = $this->get_enabled_instances();
        if (!empty($instances)) {
            // At least one instance should load
            foreach ($instances as $instance) {
                if ($this->should_load_instance($instance)) {
                    return true;
                }
            }
            return false;
        }
        
        // Fallback to legacy behavior
        $enable_filtering = get_option('caa_pro_enable_filtering', '0');
        
        if ($enable_filtering !== '1') {
            return true;
        }
        
        // ... legacy filtering logic ...
        return true;
    }
    
    /**
     * AJAX handler for searching posts/pages
     */
    public function ajax_search_posts() {
        check_ajax_referer('caa_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'logo-collision')));
            return;
        }
        
        $search_term = isset($_GET['term']) ? sanitize_text_field(wp_unslash($_GET['term'])) : '';
        
        if (empty($search_term)) {
            wp_send_json_success(array());
            return;
        }
        
        $args = array(
            'post_type' => array('post', 'page'),
            'post_status' => 'publish',
            'posts_per_page' => 20,
            's' => $search_term,
            'orderby' => 'relevance',
            'order' => 'DESC'
        );
        
        $query = new WP_Query($args);
        $results = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $results[] = array(
                    'id' => get_the_ID(),
                    'label' => get_the_title() . ' (' . get_post_type() . ' #' . get_the_ID() . ')',
                    'value' => get_the_ID()
                );
            }
            wp_reset_postdata();
        }
        
        wp_send_json_success($results);
    }
    
    /**
     * Build the settings array for a single instance (for JavaScript)
     */
    private function build_settings_array_for_instance($instance_id, $instance_data) {
        $defaults = $this->get_default_instance_data();
        $instance = array_merge($defaults, $instance_data);
        
        // Get Pro Version effect mappings and convert keys to camelCase for JS
        $effect_mappings_raw = isset($instance['effect_mappings']) ? $instance['effect_mappings'] : array();
        $effect_mappings = array();
        foreach ($effect_mappings_raw as $mapping) {
            $js_mapping = array(
                'selector' => isset($mapping['selector']) ? $mapping['selector'] : '',
                'effect' => isset($mapping['effect']) ? $mapping['effect'] : '1',
                'overrideEnabled' => isset($mapping['override_enabled']) && $mapping['override_enabled']
            );
            
            // Convert settings to camelCase if override is enabled
            if ($js_mapping['overrideEnabled'] && isset($mapping['settings']) && is_array($mapping['settings'])) {
                $settings = $mapping['settings'];
                $js_mapping['settings'] = array(
                    'duration' => isset($settings['duration']) ? $settings['duration'] : '0.6',
                    'ease' => isset($settings['ease']) ? $settings['ease'] : 'power4',
                    'offsetStart' => isset($settings['offset_start']) ? $settings['offset_start'] : '30',
                    'offsetEnd' => isset($settings['offset_end']) ? $settings['offset_end'] : '10',
                );
                
                $effect = $js_mapping['effect'];
                switch ($effect) {
                    case '1':
                        $js_mapping['settings']['effect1ScaleDown'] = isset($settings['effect1_scale_down']) ? $settings['effect1_scale_down'] : '0';
                        $js_mapping['settings']['effect1OriginX'] = isset($settings['effect1_origin_x']) ? $settings['effect1_origin_x'] : '0';
                        $js_mapping['settings']['effect1OriginY'] = isset($settings['effect1_origin_y']) ? $settings['effect1_origin_y'] : '50';
                        break;
                    case '2':
                        $js_mapping['settings']['effect2BlurAmount'] = isset($settings['effect2_blur_amount']) ? $settings['effect2_blur_amount'] : '5';
                        $js_mapping['settings']['effect2BlurScale'] = isset($settings['effect2_blur_scale']) ? $settings['effect2_blur_scale'] : '0.9';
                        $js_mapping['settings']['effect2BlurDuration'] = isset($settings['effect2_blur_duration']) ? $settings['effect2_blur_duration'] : '0.2';
                        break;
                    case '4':
                        $js_mapping['settings']['effect4TextXRange'] = isset($settings['effect4_text_x_range']) ? $settings['effect4_text_x_range'] : '50';
                        $js_mapping['settings']['effect4TextYRange'] = isset($settings['effect4_text_y_range']) ? $settings['effect4_text_y_range'] : '40';
                        $js_mapping['settings']['effect4StaggerAmount'] = isset($settings['effect4_stagger_amount']) ? $settings['effect4_stagger_amount'] : '0.03';
                        break;
                    case '5':
                        $js_mapping['settings']['effect5ShuffleIterations'] = isset($settings['effect5_shuffle_iterations']) ? $settings['effect5_shuffle_iterations'] : '2';
                        $js_mapping['settings']['effect5ShuffleDuration'] = isset($settings['effect5_shuffle_duration']) ? $settings['effect5_shuffle_duration'] : '0.03';
                        $js_mapping['settings']['effect5CharDelay'] = isset($settings['effect5_char_delay']) ? $settings['effect5_char_delay'] : '0.03';
                        break;
                    case '6':
                        $js_mapping['settings']['effect6Rotation'] = isset($settings['effect6_rotation']) ? $settings['effect6_rotation'] : '-90';
                        $js_mapping['settings']['effect6XPercent'] = isset($settings['effect6_x_percent']) ? $settings['effect6_x_percent'] : '-5';
                        $js_mapping['settings']['effect6OriginX'] = isset($settings['effect6_origin_x']) ? $settings['effect6_origin_x'] : '0';
                        $js_mapping['settings']['effect6OriginY'] = isset($settings['effect6_origin_y']) ? $settings['effect6_origin_y'] : '100';
                        break;
                    case '7':
                        $js_mapping['settings']['effect7MoveDistance'] = isset($settings['effect7_move_distance']) ? $settings['effect7_move_distance'] : '';
                        break;
                }
            }
            
            $effect_mappings[] = $js_mapping;
        }
        
        // Build settings array for this instance
        $settings_array = array(
            'instanceId' => $instance_id,
            'logoId' => $instance['logo_id'],
            'selectedEffect' => $instance['selected_effect'],
            'includedElements' => $instance['included_elements'],
            'excludedElements' => $instance['excluded_elements'],
            'globalOffset' => $instance['global_offset'],
            'debugMode' => $instance['debug_mode'],
            'duration' => $instance['duration'],
            'ease' => $instance['ease'],
            'offsetStart' => $instance['offset_start'],
            'offsetEnd' => $instance['offset_end'],
            'effectMappings' => $effect_mappings,
            // Effect settings
            'effect1ScaleDown' => $instance['effect1_scale_down'],
            'effect1OriginX' => $instance['effect1_origin_x'],
            'effect1OriginY' => $instance['effect1_origin_y'],
            'effect2BlurAmount' => $instance['effect2_blur_amount'],
            'effect2BlurScale' => $instance['effect2_blur_scale'],
            'effect2BlurDuration' => $instance['effect2_blur_duration'],
            'effect4TextXRange' => $instance['effect4_text_x_range'],
            'effect4TextYRange' => $instance['effect4_text_y_range'],
            'effect4StaggerAmount' => $instance['effect4_stagger_amount'],
            'effect5ShuffleIterations' => $instance['effect5_shuffle_iterations'],
            'effect5ShuffleDuration' => $instance['effect5_shuffle_duration'],
            'effect5CharDelay' => $instance['effect5_char_delay'],
            'effect6Rotation' => $instance['effect6_rotation'],
            'effect6XPercent' => $instance['effect6_x_percent'],
            'effect6OriginX' => $instance['effect6_origin_x'],
            'effect6OriginY' => $instance['effect6_origin_y'],
            'effect7MoveDistance' => $instance['effect7_move_distance'],
        );
        
        return $settings_array;
    }
    
    /**
     * Build settings for all enabled instances that should load on current page
     */
    private function build_all_instances_settings() {
        $instances = $this->get_enabled_instances();
        $instances_settings = array();
        
        foreach ($instances as $id => $instance) {
            // Check if this instance should load on current page
            if ($this->should_load_instance($instance)) {
                // Only include instances with a logo ID
                if (!empty($instance['logo_id'])) {
                    $instances_settings[] = $this->build_settings_array_for_instance($id, $instance);
                }
            }
        }
        
        return $instances_settings;
    }
    
    /**
     * Build the settings array for JavaScript (legacy support + multi-instance)
     */
    private function build_settings_array() {
        // Get global mobile settings (shared across all instances)
        $disable_mobile = get_option('caa_disable_mobile', '0');
        
        // Get viewport breakpoints (global settings)
        $tablet_breakpoint = get_option('caa_tablet_breakpoint', '782');
        $mobile_breakpoint = get_option('caa_mobile_breakpoint', '600');
        
        // Get all enabled instances' settings
        $instances_settings = $this->build_all_instances_settings();
        
        // Return new structure with instances array
        return array(
            'disableMobile' => $disable_mobile,
            'tabletBreakpoint' => $tablet_breakpoint,
            'mobileBreakpoint' => $mobile_breakpoint,
            'instances' => $instances_settings,
        );
    }
    
    /**
     * Check if text splitting effects are needed (across all enabled instances)
     */
    private function needs_text_splitting() {
        $instances = $this->get_enabled_instances();
        $all_effects_used = array();
        
        foreach ($instances as $instance) {
            // Check if this instance should load on current page
            if (!$this->should_load_instance($instance)) {
                continue;
            }
            
            // Add instance's selected effect
            if (!empty($instance['selected_effect'])) {
                $all_effects_used[] = $instance['selected_effect'];
            }
            
            // Add effects from mappings
            $effect_mappings = isset($instance['effect_mappings']) ? $instance['effect_mappings'] : array();
            foreach ($effect_mappings as $mapping) {
                if (!empty($mapping['effect'])) {
                    $all_effects_used[] = $mapping['effect'];
                }
            }
        }
        
        return count(array_intersect($all_effects_used, array('4', '5'))) > 0;
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        // Only enqueue on frontend
        if (is_admin()) {
            return;
        }
        
        // Get enabled instances that should load on current page
        $instances = $this->get_enabled_instances();
        $has_valid_instance = false;
        
        foreach ($instances as $instance) {
            // Check if this instance should load on current page
            if ($this->should_load_instance($instance) && !empty($instance['logo_id'])) {
                $has_valid_instance = true;
                break;
            }
        }
        
        // Don't enqueue if no valid instances
        if (!$has_valid_instance) {
            return;
        }
        
        // Enqueue scripts
        $this->enqueue_scripts_standard();
    }
    
    /**
     * Standard script enqueue without viewport check
     */
    private function enqueue_scripts_standard() {
        $needs_text_splitting = $this->needs_text_splitting();
        
        // Enqueue GSAP from local assets
        wp_enqueue_script(
            'gsap',
            CAA_PLUGIN_URL . 'assets/js/gsap.min.js',
            array(),
            '3.12.5',
            true
        );
        
        wp_enqueue_script(
            'gsap-scrolltrigger',
            CAA_PLUGIN_URL . 'assets/js/ScrollTrigger.min.js',
            array('gsap'),
            '3.12.5',
            true
        );
        
        // Only enqueue SplitType and textSplitter for effects 4 and 5
        $frontend_dependencies = array('gsap', 'gsap-scrolltrigger');
        
        if ($needs_text_splitting) {
            // Enqueue SplitType from local assets - load in HEAD to ensure it's available before modules
            wp_enqueue_script(
                'split-type',
                CAA_PLUGIN_URL . 'assets/js/splittype.js',
                array(),
                '0.3.4',
                false // Load in <head>, not footer
            );
            
            // Ensure SplitType script has no defer/async (some themes add these)
            add_filter('script_loader_tag', function($tag, $handle) {
                if ($handle === 'split-type') {
                    $tag = preg_replace('/\s+(defer|async)(=[\'"][^\'"]*[\'"])?/i', '', $tag);
                }
                return $tag;
            }, 99, 2);
            
            // Enqueue utility scripts
            wp_enqueue_script(
                'caa-utils',
                CAA_PLUGIN_URL . 'assets/js/utils.js',
                array(),
                CAA_VERSION,
                true
            );
            
            wp_enqueue_script(
                'caa-text-splitter',
                CAA_PLUGIN_URL . 'assets/js/textSplitter.js',
                array('split-type', 'caa-utils'),
                CAA_VERSION,
                true
            );
            
            $frontend_dependencies[] = 'split-type';
            $frontend_dependencies[] = 'caa-text-splitter';
        }
        
        // Enqueue main frontend script
        wp_enqueue_script(
            'caa-frontend',
            CAA_PLUGIN_URL . 'assets/js/frontend.js',
            $frontend_dependencies,
            CAA_VERSION,
            true
        );
        
        // Pass settings to JavaScript
        wp_localize_script('caa-frontend', 'caaSettings', $this->build_settings_array());
        
        // Enqueue frontend CSS
        wp_enqueue_style(
            'caa-frontend',
            CAA_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            CAA_VERSION
        );
    }
}

// Register activation hook
register_activation_hook(__FILE__, array('Context_Aware_Animation', 'activate'));

// Initialize the plugin
Context_Aware_Animation::get_instance();

