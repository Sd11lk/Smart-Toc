<?php
/**
 * Plugin Name: Smart TOC
 * Description: Auto-generates a Table of Contents for your posts
 * Version: 2.0.0
 * Author: Turginator
 * Text Domain: smart-toc
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin version
define('SMART_TOC_VERSION', '1.0.0');

// Plugin activation hook
register_activation_hook(__FILE__, 'smart_toc_activate');
function smart_toc_activate() {
    // Add default settings upon activation
    add_option('smart_toc_settings', array(
        'enable_auto_insert' => 1,
        'min_headings' => 3,
        'position' => 'before_first_heading',
        'toc_title' => 'Table of Contents',
        'display_hierarchy' => 1,
        'heading_levels' => array('h2', 'h3', 'h4'),
        'smooth_scroll' => 1,
        'custom_css' => ''
    ));

    // Create necessary block files on activation
    smart_toc_create_block_js();
}

// Plugin deactivation hook
register_deactivation_hook(__FILE__, 'smart_toc_deactivate');
function smart_toc_deactivate() {
    // Cleanup if needed
}

// Plugin uninstall hook (optional)
register_uninstall_hook(__FILE__, 'smart_toc_uninstall');
function smart_toc_uninstall() {
    delete_option('smart_toc_settings');
}

// Enqueue frontend scripts and styles
add_action('wp_enqueue_scripts', 'smart_toc_enqueue_scripts');
function smart_toc_enqueue_scripts() {
    // Only enqueue on single posts and pages
    if (is_singular()) {
        wp_enqueue_style(
            'smart-toc-style',
            plugin_dir_url(__FILE__) . 'assets/css/smart-toc.css',
            array(),
            SMART_TOC_VERSION
        );

        wp_enqueue_script(
            'smart-toc-script',
            plugin_dir_url(__FILE__) . 'assets/js/smart-toc.js',
            array('jquery'),
            SMART_TOC_VERSION,
            true
        );

        // Pass settings to JavaScript
        $settings = get_option('smart_toc_settings');
        wp_localize_script('smart-toc-script', 'smartTocSettings', array(
            'smoothScroll' => !empty($settings['smooth_scroll']) ? true : false,
            'scrollOffset' => 30 // Offset for fixed headers
        ));
    }
}

/**
 * Automatically add ID attributes to heading tags in the content
 * This runs before TOC insertion to ensure all headings have IDs
 */
add_filter('the_content', 'smart_toc_add_heading_ids', 9);
function smart_toc_add_heading_ids($content) {
    // Only process on single posts and pages in the main query
    if (!is_singular() || !in_the_loop() || !is_main_query()) {
        return $content;
    }

    // Process all heading tags H1-H6
    $pattern = '/<(h[1-6]).*?>(.*?)<\/\1>/i';

    // Find all headings
    preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

    // Track IDs to ensure uniqueness
    $heading_ids = array();

    // Process each heading
    foreach ($matches as $match) {
        $tag = $match[1]; // h1, h2, etc.
        $heading_content = $match[2]; // Heading text
        $full_heading = $match[0]; // The entire heading tag

        // Check if heading already has an ID
        if (strpos($full_heading, ' id=') !== false) {
            continue; // Skip headings that already have an ID
        }

        // Create a unique ID from the heading text
        $text = wp_strip_all_tags($heading_content);
        $id = sanitize_title($text);

        // Make sure ID is unique
        if (isset($heading_ids[$id])) {
            $heading_ids[$id]++;
            $id .= '-' . $heading_ids[$id];
        } else {
            $heading_ids[$id] = 1;
        }

        // Replace the heading with one that has an ID
        $new_heading = '<' . $tag . ' id="' . $id . '">' . $heading_content . '</' . $tag . '>';
        $content = str_replace($full_heading, $new_heading, $content);
    }

    return $content;
}

// Auto-insert TOC into content
add_filter('the_content', 'smart_toc_auto_insert', 10);
function smart_toc_auto_insert($content) {
    // Only process on single posts and pages in the main query
    if (!is_singular() || !in_the_loop() || !is_main_query()) {
        return $content;
    }

    // Get post ID
    $post_id = get_the_ID();

    // Check if TOC is disabled for this specific post/page
    $disable_toc = get_post_meta($post_id, '_smart_toc_disable', true);
    if ($disable_toc === '1') {
        return $content;
    }

    $settings = get_option('smart_toc_settings');

    // Check if auto-insert is enabled
    if (empty($settings['enable_auto_insert'])) {
        return $content;
    }

    // Generate TOC HTML
    $toc_html = smart_toc_generate($content);

    // If no TOC or not enough headings, return the original content
    if (!$toc_html) {
        return $content;
    }

    // Determine position to insert TOC
    switch ($settings['position']) {
        case 'top':
            return $toc_html . $content;

        case 'before_first_heading':
            // Find the first heading
            $pattern = '/<h[1-6].*?>/i';
            if (preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                $pos = $matches[0][1];
                return substr_replace($content, $toc_html, $pos, 0);
            } else {
                return $toc_html . $content;
            }

        case 'after_first_paragraph':
            // Find the first paragraph end tag
            $pos = strpos($content, '</p>');
            if ($pos !== false) {
                return substr_replace($content, $toc_html, $pos + 4, 0);
            } else {
                return $toc_html . $content;
            }

        default:
            return $toc_html . $content;
    }
}

// Main TOC generation function
function smart_toc_generate($content) {
    $settings = get_option('smart_toc_settings');

    // Get heading levels to include
    $heading_levels = !empty($settings['heading_levels']) ? $settings['heading_levels'] : array('h2', 'h3', 'h4');

    // Create pattern to match specified headings
    $pattern = '/<(' . implode('|', $heading_levels) . ').*?>(.*?)<\/\1>/i';

    // Find all headings
    preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

    // If minimum headings requirement not met, return empty
    $min_headings = !empty($settings['min_headings']) ? (int) $settings['min_headings'] : 3;
    if (count($matches) < $min_headings) {
        return '';
    }

    // Process headings and build TOC
    $toc_items = array();
    $heading_ids = array();

    foreach ($matches as $i => $match) {
        $tag = strtolower($match[1]); // h2, h3, etc.
        $level = (int) substr($tag, 1); // Get the heading level number
        $full_heading = $match[0]; // The entire heading tag
        $text = wp_strip_all_tags($match[2]); // Get heading text without HTML

        // Check if heading already has an ID
        $id = '';
        if (preg_match('/id=[\'"](.*?)[\'"]/i', $full_heading, $id_match)) {
            $id = $id_match[1];
        } else {
            // Create a unique ID for the heading
            $id = sanitize_title($text);

            // Make sure ID is unique
            if (isset($heading_ids[$id])) {
                $heading_ids[$id]++;
                $id .= '-' . $heading_ids[$id];
            } else {
                $heading_ids[$id] = 1;
            }
        }

        // Add to TOC items
        $toc_items[] = array(
            'level' => $level,
            'text' => $text,
            'id' => $id
        );
    }

    // Build TOC HTML
    $hierarchy = !empty($settings['display_hierarchy']) ? true : false;
    $toc_title = !empty($settings['toc_title']) ? $settings['toc_title'] : 'Table of Contents';

    $toc_html = '<div class="smart-toc-container" id="smart-toc">';
    $toc_html .= '<div class="smart-toc-header">';
    $toc_html .= '<span class="smart-toc-title">' . esc_html($toc_title) . '</span>';
    $toc_html .= '<span class="smart-toc-toggle">[<a href="#" class="smart-toc-toggle-link">hide</a>]</span>';
    $toc_html .= '</div>';

    $toc_html .= '<div class="smart-toc-list-container">';
    $toc_html .= '<ol class="smart-toc-list">';

    $current_level = 2; // Start at H2 level

    foreach ($toc_items as $item) {
        if ($hierarchy) {
            // Create hierarchical list
            if ($item['level'] > $current_level) {
                // Going deeper in the hierarchy
                $toc_html .= '<ol class="smart-toc-sublist">';
                $current_level = $item['level'];
            } elseif ($item['level'] < $current_level) {
                // Going up in the hierarchy
                $diff = $current_level - $item['level'];
                $toc_html .= str_repeat('</ol></li>', $diff);
                $current_level = $item['level'];
            } else {
                // Same level
                $toc_html .= '</li>';
            }

            $toc_html .= '<li class="smart-toc-item smart-toc-level-' . $item['level'] . '">';
            $toc_html .= '<a href="#' . esc_attr($item['id']) . '">' . esc_html($item['text']) . '</a>';
        } else {
            // Create flat list
            $toc_html .= '<li class="smart-toc-item smart-toc-level-' . $item['level'] . '">';
            $toc_html .= '<a href="#' . esc_attr($item['id']) . '">' . esc_html($item['text']) . '</a>';
            $toc_html .= '</li>';
        }
    }

    // Close any remaining tags in hierarchical mode
    if ($hierarchy && $current_level > 2) {
        $diff = $current_level - 2;
        $toc_html .= str_repeat('</li></ol>', $diff);
    }

    $toc_html .= '</ol>';
    $toc_html .= '</div>'; // .smart-toc-list-container
    $toc_html .= '</div>'; // .smart-toc-container

    return $toc_html;
}

// Add shortcode for manual insertion
add_shortcode('smart_toc', 'smart_toc_shortcode');
function smart_toc_shortcode($atts) {
    // Get the content of the current post
    global $post;
    $content = $post->post_content;

    // Generate and return the TOC
    return smart_toc_generate($content);
}

// Add TinyMCE button for shortcode insertion
add_action('admin_init', 'smart_toc_tinymce_init');
function smart_toc_tinymce_init() {
    if (current_user_can('edit_posts') || current_user_can('edit_pages')) {
        add_filter('mce_buttons', 'smart_toc_register_button');
        add_filter('mce_external_plugins', 'smart_toc_add_button');
    }
}

function smart_toc_register_button($buttons) {
    array_push($buttons, 'smart_toc_button');
    return $buttons;
}

function smart_toc_add_button($plugin_array) {
    $plugin_array['smart_toc_button'] = plugin_dir_url(__FILE__) . 'assets/js/tinymce-button.js';
    return $plugin_array;
}

/**
 * Register Smart TOC Gutenberg block
 */
add_action('init', 'smart_toc_register_block');
function smart_toc_register_block() {
    // Skip if Gutenberg is not available
    if (!function_exists('register_block_type')) {
        return;
    }

    // Register block script
    wp_register_script(
        'smart-toc-block',
        plugin_dir_url(__FILE__) . 'assets/js/block.js',
        array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components'),
        SMART_TOC_VERSION,
        true
    );

    // Register block editor styles
    wp_register_style(
        'smart-toc-block-editor',
        plugin_dir_url(__FILE__) . 'assets/css/block-editor.css',
        array('wp-edit-blocks'),
        SMART_TOC_VERSION
    );

    // Register the block
    register_block_type('smart-toc/toc-block', array(
        'editor_script' => 'smart-toc-block',
        'editor_style' => 'smart-toc-block-editor',
        'render_callback' => 'smart_toc_render_block'
    ));
}

/**
 * Render callback for the TOC block
 */
function smart_toc_render_block($attributes, $content) {
    // Get the content of the current post
    global $post;
    $post_content = $post->post_content;

    // Generate TOC
    return smart_toc_generate($post_content);
}

/**
 * Create JS file for Gutenberg block
 */
function smart_toc_create_block_js() {
    // Create directories if they don't exist
    $js_dir = plugin_dir_path(__FILE__) . 'assets/js';
    if (!file_exists($js_dir)) {
        wp_mkdir_p($js_dir);
    }

    // Block JS file path
    $js_file = $js_dir . '/block.js';

    // Only create the file if it doesn't exist
    if (!file_exists($js_file)) {
        $js_content = "
        /* Smart TOC Gutenberg Block */
        (function(blocks, element, components, editor) {
            var el = element.createElement;
            var InspectorControls = editor.InspectorControls;
            var PanelBody = components.PanelBody;
            var ToggleControl = components.ToggleControl;
            var ServerSideRender = wp.serverSideRender;

            blocks.registerBlockType('smart-toc/toc-block', {
                title: 'Smart Table of Contents',
                icon: 'list-view',
                category: 'widgets',

                edit: function(props) {
                    return [
                        el(InspectorControls, {},
                            el(PanelBody, {
                                title: 'Settings',
                                initialOpen: true
                            },
                                el('div', { className: 'smart-toc-block-settings' },
                                    el('p', {}, 'The table of contents will be generated based on the headings in your content.')
                                )
                            )
                        ),
                        el('div', { className: props.className },
                            el('div', { className: 'smart-toc-block-preview' },
                                el('h3', {}, 'Table of Contents'),
                                el('p', {}, 'This block will display a table of contents based on the headings in your content.')
                            )
                        )
                    ];
                },

                save: function() {
                    return null; // Dynamic block, rendered on server
                }
            });
        })(
            window.wp.blocks,
            window.wp.element,
            window.wp.components,
            window.wp.blockEditor
        );";

        // Write the file
        file_put_contents($js_file, $js_content);
    }

    // Block editor CSS file path
    $css_dir = plugin_dir_path(__FILE__) . 'assets/css';
    if (!file_exists($css_dir)) {
        wp_mkdir_p($css_dir);
    }

    $css_file = $css_dir . '/block-editor.css';

    // Only create the file if it doesn't exist
    if (!file_exists($css_file)) {
        $css_content = "
        /* Smart TOC Block Editor Styles */
        .smart-toc-block-preview {
            padding: 16px;
            border: 1px dashed #ccc;
            background-color: #f8f8f8;
        }

        .smart-toc-block-preview h3 {
            margin-top: 0;
        }";

        // Write the file
        file_put_contents($css_file, $css_content);
    }
}

// Add settings page
add_action('admin_menu', 'smart_toc_admin_menu');
function smart_toc_admin_menu() {
    add_options_page(
        'Smart TOC Settings',
        'Smart TOC',
        'manage_options',
        'smart-toc-settings',
        'smart_toc_settings_page'
    );
}

// Register settings
add_action('admin_init', 'smart_toc_register_settings');
function smart_toc_register_settings() {
    register_setting('smart_toc_settings_group', 'smart_toc_settings', 'smart_toc_sanitize_settings');
}

// Sanitize settings
function smart_toc_sanitize_settings($input) {
    $sanitized = array();

    $sanitized['enable_auto_insert'] = isset($input['enable_auto_insert']) ? 1 : 0;
    $sanitized['min_headings'] = isset($input['min_headings']) ? intval($input['min_headings']) : 3;
    $sanitized['position'] = isset($input['position']) ? sanitize_text_field($input['position']) : 'before_first_heading';
    $sanitized['toc_title'] = isset($input['toc_title']) ? sanitize_text_field($input['toc_title']) : 'Table of Contents';
    $sanitized['display_hierarchy'] = isset($input['display_hierarchy']) ? 1 : 0;
    $sanitized['smooth_scroll'] = isset($input['smooth_scroll']) ? 1 : 0;

    // Heading levels
    $sanitized['heading_levels'] = array();
    $valid_heading_levels = array('h1', 'h2', 'h3', 'h4', 'h5', 'h6');

    if (isset($input['heading_levels']) && is_array($input['heading_levels'])) {
        foreach ($input['heading_levels'] as $level) {
            if (in_array($level, $valid_heading_levels)) {
                $sanitized['heading_levels'][] = $level;
            }
        }
    }

    // If no heading levels selected, default to h2, h3, h4
    if (empty($sanitized['heading_levels'])) {
        $sanitized['heading_levels'] = array('h2', 'h3', 'h4');
    }

    // Custom CSS (strip dangerous content)
    $sanitized['custom_css'] = isset($input['custom_css']) ? wp_strip_all_tags($input['custom_css']) : '';

    return $sanitized;
}

// Settings page callback
function smart_toc_settings_page() {
    // Get current settings
    $settings = get_option('smart_toc_settings');
    ?>
    <div class="wrap">
        <h1>Smart TOC Settings</h1>

        <form method="post" action="options.php">
            <?php settings_fields('smart_toc_settings_group'); ?>

            <table class="form-table">
                <tr>
                    <th scope="row">Auto-Insert TOC</th>
                    <td>
                        <label>
                            <input type="checkbox" name="smart_toc_settings[enable_auto_insert]" value="1" <?php checked(isset($settings['enable_auto_insert']) ? $settings['enable_auto_insert'] : 0); ?> />
                            Automatically insert Table of Contents in posts
                        </label>
                    </td>
                </tr>

                <tr>
                    <th scope="row">Minimum Headings</th>
                    <td>
                        <input type="number" min="1" max="50" name="smart_toc_settings[min_headings]" value="<?php echo esc_attr(isset($settings['min_headings']) ? $settings['min_headings'] : 3); ?>" />
                        <p class="description">Minimum number of headings required to show TOC</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">TOC Position</th>
                    <td>
                        <select name="smart_toc_settings[position]">
                            <option value="top" <?php selected(isset($settings['position']) ? $settings['position'] : '', 'top'); ?>>At the top of content</option>
                            <option value="before_first_heading" <?php selected(isset($settings['position']) ? $settings['position'] : '', 'before_first_heading'); ?>>Before first heading</option>
                            <option value="after_first_paragraph" <?php selected(isset($settings['position']) ? $settings['position'] : '', 'after_first_paragraph'); ?>>After first paragraph</option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row">TOC Title</th>
                    <td>
                        <input type="text" name="smart_toc_settings[toc_title]" value="<?php echo esc_attr(isset($settings['toc_title']) ? $settings['toc_title'] : 'Table of Contents'); ?>" class="regular-text" />
                    </td>
                </tr>

                <tr>
                    <th scope="row">Display Style</th>
                    <td>
                        <label>
                            <input type="checkbox" name="smart_toc_settings[display_hierarchy]" value="1" <?php checked(isset($settings['display_hierarchy']) ? $settings['display_hierarchy'] : 0); ?> />
                            Show hierarchical structure (nested lists)
                        </label>
                    </td>
                </tr>

                <tr>
                    <th scope="row">Heading Levels</th>
                    <td>
                        <?php
                        $heading_levels = isset($settings['heading_levels']) ? $settings['heading_levels'] : array('h2', 'h3', 'h4');
                        for ($i = 1; $i <= 6; $i++) {
                            $level = 'h' . $i;
                            ?>
                            <label>
                                <input type="checkbox" name="smart_toc_settings[heading_levels][]" value="<?php echo $level; ?>" <?php checked(in_array($level, $heading_levels)); ?> />
                                H<?php echo $i; ?>
                            </label>
                            <br>
                            <?php
                        }
                        ?>
                    </td>
                </tr>

                <tr>
                    <th scope="row">Smooth Scrolling</th>
                    <td>
                        <label>
                            <input type="checkbox" name="smart_toc_settings[smooth_scroll]" value="1" <?php checked(isset($settings['smooth_scroll']) ? $settings['smooth_scroll'] : 0); ?> />
                            Enable smooth scrolling to headings
                        </label>
                    </td>
                </tr>

                <tr>
                    <th scope="row">Custom CSS</th>
                    <td>
                        <textarea name="smart_toc_settings[custom_css]" rows="8" cols="50" class="large-text code"><?php echo esc_textarea(isset($settings['custom_css']) ? $settings['custom_css'] : ''); ?></textarea>
                        <p class="description">Add custom CSS to style your Table of Contents</p>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Add meta box for post-specific settings
add_action('add_meta_boxes', 'smart_toc_add_meta_box');
function smart_toc_add_meta_box() {
    $post_types = array('post', 'page');

    foreach ($post_types as $post_type) {
        add_meta_box(
            'smart_toc_meta_box',
            'Smart TOC Settings',
            'smart_toc_meta_box_callback',
            $post_type,
            'side',
            'default'
        );
    }
}

// Meta box callback
function smart_toc_meta_box_callback($post) {
    wp_nonce_field('smart_toc_meta_box', 'smart_toc_meta_box_nonce');

    $disable_toc = get_post_meta($post->ID, '_smart_toc_disable', true);
    ?>
    <p>
        <label>
            <input type="checkbox" name="smart_toc_disable" value="1" <?php checked($disable_toc, '1'); ?> />
            Disable Table of Contents for this <?php echo get_post_type($post); ?>
        </label>
    </p>
    <p class="description">
        You can also manually insert the TOC using the shortcode: <code>[smart_toc]</code>
        or the Gutenberg block "Smart Table of Contents"
    </p>
    <?php
}

// Save meta box data
add_action('save_post', 'smart_toc_save_meta_box_data');
function smart_toc_save_meta_box_data($post_id) {
    // Check if our nonce is set
    if (!isset($_POST['smart_toc_meta_box_nonce'])) {
        return;
    }

    // Verify the nonce
    if (!wp_verify_nonce($_POST['smart_toc_meta_box_nonce'], 'smart_toc_meta_box')) {
        return;
    }

    // If this is an autosave, we don't want to do anything
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Check the user's permissions
    if (isset($_POST['post_type']) && 'page' == $_POST['post_type']) {
        if (!current_user_can('edit_page', $post_id)) {
            return;
        }
    } else {
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }

    // Update the meta field
    if (isset($_POST['smart_toc_disable'])) {
        update_post_meta($post_id, '_smart_toc_disable', '1');
    } else {
        delete_post_meta($post_id, '_smart_toc_disable');
    }
}

// Add plugin action links
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'smart_toc_plugin_action_links');
function smart_toc_plugin_action_links($links) {
    $settings_link = '<a href="options-general.php?page=smart-toc-settings">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}
