<?php
/**
 * Plugin Name: Logo Collision
 * Plugin URI: https://wordpress.org/plugins/logo-collision/
 * Description: Apply context-aware scroll animations to your WordPress header logo when it would collide with scrolling content.
 * Version: 1.0.0
 * Author: wpmitch
 * Author URI: https://profiles.wordpress.org/wpmitch/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: logo-collision
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CAA_VERSION', '1.0.0');
define('CAA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CAA_PLUGIN_URL', plugin_dir_url(__FILE__));

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
        add_action('wp_ajax_caa_search_posts', array($this, 'ajax_search_posts'));
        
        // Frontend hooks
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('script_loader_tag', array($this, 'add_module_type'), 10, 3);
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
        
        register_setting('caa_settings_group', 'caa_mobile_breakpoint', array(
            'type' => 'string',
            'sanitize_callback' => 'absint',
            'default' => '768'
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
     * Check if plugin should load on current page
     */
    private function should_load_plugin() {
        $enable_filtering = get_option('caa_pro_enable_filtering', '0');
        
        // If filtering is disabled, run everywhere (default behavior)
        if ($enable_filtering !== '1') {
            return true;
        }
        
        $filter_mode = get_option('caa_pro_filter_mode', 'include');
        $selected_post_types = get_option('caa_pro_selected_post_types', array());
        $include_pages = get_option('caa_pro_include_pages', '0');
        $include_posts = get_option('caa_pro_include_posts', '0');
        $selected_items = get_option('caa_pro_selected_items', array());
        
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
     * Build the settings array for JavaScript
     */
    private function build_settings_array() {
        $logo_id = get_option('caa_logo_id', '');
        $selected_effect = get_option('caa_selected_effect', '1');
        $included_elements = get_option('caa_included_elements', '');
        $excluded_elements = get_option('caa_excluded_elements', '');
        $global_offset = get_option('caa_global_offset', '0');
        $debug_mode = get_option('caa_debug_mode', '0');
        
        // Get global animation settings
        $duration = get_option('caa_duration', '0.6');
        $ease = get_option('caa_ease', 'power4');
        $offset_start = get_option('caa_offset_start', '30');
        $offset_end = get_option('caa_offset_end', '10');
        
        // Get Pro Version effect mappings and convert keys to camelCase for JS
        $effect_mappings_raw = get_option('caa_pro_effect_mappings', array());
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
                    // Global animation settings
                    'duration' => isset($settings['duration']) ? $settings['duration'] : '0.6',
                    'ease' => isset($settings['ease']) ? $settings['ease'] : 'power4',
                    'offsetStart' => isset($settings['offset_start']) ? $settings['offset_start'] : '30',
                    'offsetEnd' => isset($settings['offset_end']) ? $settings['offset_end'] : '10',
                );
                
                // Effect-specific settings (camelCase)
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
        
        // Get mobile disable settings
        $disable_mobile = get_option('caa_disable_mobile', '0');
        $mobile_breakpoint = get_option('caa_mobile_breakpoint', '768');
        
        // Build settings array
        $settings_array = array(
            'logoId' => $logo_id,
            'selectedEffect' => $selected_effect,
            'includedElements' => $included_elements,
            'excludedElements' => $excluded_elements,
            'globalOffset' => $global_offset,
            'debugMode' => $debug_mode,
            'disableMobile' => $disable_mobile,
            'mobileBreakpoint' => $mobile_breakpoint,
            'duration' => $duration,
            'ease' => $ease,
            'offsetStart' => $offset_start,
            'offsetEnd' => $offset_end,
            'effectMappings' => $effect_mappings
        );
        
        // Determine which effects are needed
        $needed_effects = array($selected_effect);
        foreach ($effect_mappings as $mapping) {
            if (!empty($mapping['effect']) && !in_array($mapping['effect'], $needed_effects)) {
                $needed_effects[] = $mapping['effect'];
            }
        }
        
        // Add all effect settings that are needed
        foreach ($needed_effects as $effect) {
            switch ($effect) {
                case '1':
                    if (!isset($settings_array['effect1ScaleDown'])) {
                        $settings_array['effect1ScaleDown'] = get_option('caa_effect1_scale_down', '0');
                        $settings_array['effect1OriginX'] = get_option('caa_effect1_origin_x', '0');
                        $settings_array['effect1OriginY'] = get_option('caa_effect1_origin_y', '50');
                    }
                    break;
                case '2':
                    if (!isset($settings_array['effect2BlurAmount'])) {
                        $settings_array['effect2BlurAmount'] = get_option('caa_effect2_blur_amount', '5');
                        $settings_array['effect2BlurScale'] = get_option('caa_effect2_blur_scale', '0.9');
                        $settings_array['effect2BlurDuration'] = get_option('caa_effect2_blur_duration', '0.2');
                    }
                    break;
                case '4':
                    if (!isset($settings_array['effect4TextXRange'])) {
                        $settings_array['effect4TextXRange'] = get_option('caa_effect4_text_x_range', '50');
                        $settings_array['effect4TextYRange'] = get_option('caa_effect4_text_y_range', '40');
                        $settings_array['effect4StaggerAmount'] = get_option('caa_effect4_stagger_amount', '0.03');
                    }
                    break;
                case '5':
                    if (!isset($settings_array['effect5ShuffleIterations'])) {
                        $settings_array['effect5ShuffleIterations'] = get_option('caa_effect5_shuffle_iterations', '2');
                        $settings_array['effect5ShuffleDuration'] = get_option('caa_effect5_shuffle_duration', '0.03');
                        $settings_array['effect5CharDelay'] = get_option('caa_effect5_char_delay', '0.03');
                    }
                    break;
                case '6':
                    if (!isset($settings_array['effect6Rotation'])) {
                        $settings_array['effect6Rotation'] = get_option('caa_effect6_rotation', '-90');
                        $settings_array['effect6XPercent'] = get_option('caa_effect6_x_percent', '-5');
                        $settings_array['effect6OriginX'] = get_option('caa_effect6_origin_x', '0');
                        $settings_array['effect6OriginY'] = get_option('caa_effect6_origin_y', '100');
                    }
                    break;
                case '7':
                    if (!isset($settings_array['effect7MoveDistance'])) {
                        $settings_array['effect7MoveDistance'] = get_option('caa_effect7_move_distance', '');
                    }
                    break;
            }
        }
        
        return $settings_array;
    }
    
    /**
     * Check if text splitting effects are needed
     */
    private function needs_text_splitting() {
        $selected_effect = get_option('caa_selected_effect', '1');
        $effect_mappings = get_option('caa_pro_effect_mappings', array());
        $all_effects_used = array($selected_effect);
        foreach ($effect_mappings as $mapping) {
            if (!empty($mapping['effect'])) {
                $all_effects_used[] = $mapping['effect'];
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
        
        // Check if plugin should load based on filtering settings
        if (!$this->should_load_plugin()) {
            return;
        }
        
        // Don't enqueue if no logo ID is set
        $logo_id = get_option('caa_logo_id', '');
        if (empty($logo_id)) {
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

// Initialize the plugin
Context_Aware_Animation::get_instance();

