<?php
/*
Plugin Name: Church Donations Manager
Description: A plugin to manage and display church donation posts with categories, shortcodes, and styling.
Version: 1.7
Author: Miaoulis N
Text Domain: church-donations
Domain Path: /languages
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Register Custom Post Type
function cdm_register_donation_post_type() {
    $labels = array(
        'name' => __('Donations', 'church-donations'),
        'singular_name' => __('Donation', 'church-donations'),
        'menu_name' => __('Donations', 'church-donations'),
        'add_new' => __('Add New', 'church-donations'),
        'add_new_item' => __('Add New Donation', 'church-donations'),
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'supports' => array('title', 'editor', 'thumbnail'), // Supports block editor
        'menu_icon' => 'dashicons-heart',
        'rewrite' => array('slug' => 'donations'),
        'show_in_rest' => true, // Ensures Gutenberg block editor compatibility
    );

    register_post_type('donation', $args);
}
add_action('init', 'cdm_register_donation_post_type');

// Register Taxonomy for Categories
function cdm_register_donation_categories() {
    $labels = array(
        'name' => __('Donation Categories', 'church-donations'),
        'singular_name' => __('Donation Category', 'church-donations'),
    );

    $args = array(
        'labels' => $labels,
        'hierarchical' => true,
        'show_admin_column' => true,
        'show_in_rest' => true, // For Gutenberg compatibility
    );

    register_taxonomy('donation_category', 'donation', $args);
}
add_action('init', 'cdm_register_donation_categories');

// Force Gutenberg for Donation Post Type
function cdm_force_gutenberg_for_donations($use_block_editor, $post_type) {
    if ($post_type === 'donation') {
        return true; // Force Gutenberg for 'donation' post type
    }
    return $use_block_editor;
}
add_filter('use_block_editor_for_post_type', 'cdm_force_gutenberg_for_donations', 10, 2);

// Add Meta Field for Urgency
function cdm_add_urgency_meta_box() {
    add_meta_box(
        'cdm_urgency_meta',
        __('Urgency', 'church-donations'),
        'cdm_urgency_meta_box_callback',
        'donation',
        'side'
    );
}
add_action('add_meta_boxes', 'cdm_add_urgency_meta_box');

function cdm_urgency_meta_box_callback($post) {
    $urgency = get_post_meta($post->ID, '_cdm_urgency', true);
    ?>
    <label><input type="checkbox" name="cdm_urgency" value="1" <?php checked($urgency, 1); ?>> <?php _e('Mark as Urgent', 'church-donations'); ?></label>
    <?php
}

function cdm_save_urgency_meta($post_id) {
    if (isset($_POST['cdm_urgency'])) {
        update_post_meta($post_id, '_cdm_urgency', 1);
    } else {
        delete_post_meta($post_id, '_cdm_urgency');
    }
}
add_action('save_post', 'cdm_save_urgency_meta');

// Shortcode for Displaying Donations
function cdm_donations_shortcode($atts) {
    $atts = shortcode_atts(array(
        'category' => '',
        'limit' => 3,
        'urgent' => false,
    ), $atts, 'church_donations');

    $args = array(
        'post_type' => 'donation',
        'posts_per_page' => $atts['limit'],
    );

    if ($atts['category']) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'donation_category',
                'field' => 'slug',
                'terms' => $atts['category'],
            ),
        );
    }

    if ($atts['urgent']) {
        $args['meta_query'] = array(
            array(
                'key' => '_cdm_urgency',
                'value' => 1,
            ),
        );
    }

    $query = new WP_Query($args);
    $output = '<div class="cdm-donations row">';

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $permalink = get_permalink();
            $is_urgent = get_post_meta(get_the_ID(), '_cdm_urgency', true);
            $card_class = $is_urgent ? 'card h-100 urgent' : 'card h-100'; // Add 'urgent' class if applicable

            $output .= '<div class="col-md-4 mb-4">';
            $output .= '<a href="' . esc_url($permalink) . '" class="card-link">';
            $output .= '<div class="' . $card_class . '">';
            
            // Featured Image
            if (has_post_thumbnail()) {
                $output .= '<img src="' . get_the_post_thumbnail_url(get_the_ID(), 'medium') . '" class="card-img-top" alt="' . esc_attr(get_the_title()) . '">';
            }

            // Urgent Label
            if ($is_urgent) {
                $output .= '<div class="urgent-label">' . __('Urgent', 'church-donations') . '</div>';
            }

            $output .= '<div class="card-body">';
            $output .= '<h5 class="card-title">' . get_the_title() . '</h5>';
            $output .= '<div class="card-text">' . get_the_content() . '</div>';
            $output .= '</div>';
            $output .= '<div class="card-footer">';
            $output .= '<div class="cdm-share">' . cdm_get_share_buttons() . '</div>';
            $output .= '</div>';
            $output .= '</div>';
            $output .= '</a>';
            $output .= '</div>';
        }
    } else {
        $output .= '<p>' . __('No donations found.', 'church-donations') . '</p>';
    }

    $output .= '</div>';
    wp_reset_postdata();
    return $output;
}
add_shortcode('church_donations', 'cdm_donations_shortcode');

// Social Sharing Buttons with Icons
function cdm_get_share_buttons() {
    $url = get_permalink();
    $title = get_the_title();
    return '<a href="https://twitter.com/share?url=' . urlencode($url) . '&text=' . urlencode($title) . '" target="_blank" class="share-icon"><i class="fab fa-twitter"></i></a>' .
           '<a href="https://www.facebook.com/sharer/sharer.php?u=' . urlencode($url) . '" target="_blank" class="share-icon"><i class="fab fa-facebook-f"></i></a>';
}

// Enqueue Styles and Scripts
function cdm_enqueue_styles() {
    // Enqueue Bootstrap CSS
    wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css', array(), '5.3.0');
    
    // Enqueue Font Awesome for icons
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css', array(), '5.15.4');
    
    // Enqueue custom styles
    wp_enqueue_style('cdm-styles', plugins_url('css/style.css', __FILE__));
    
    // Enqueue Bootstrap JS (for any Bootstrap components that need it)
    wp_enqueue_script('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js', array('jquery'), '5.3.0', true);
}
add_action('wp_enqueue_scripts', 'cdm_enqueue_styles');

// Enqueue Admin Styles for Settings Page
function cdm_enqueue_admin_styles($hook) {
    if ($hook !== 'donation_page_cdm-settings') {
        return;
    }
    wp_enqueue_style('cdm-admin-styles', plugins_url('css/admin-style.css', __FILE__));
}
add_action('admin_enqueue_scripts', 'cdm_enqueue_admin_styles');

// Load Text Domain for Translations
function cdm_load_textdomain() {
    load_plugin_textdomain('church-donations', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}
add_action('plugins_loaded', 'cdm_load_textdomain');

// Settings Page under Donations Menu
function cdm_register_settings() {
    add_submenu_page(
        'edit.php?post_type=donation', // Parent slug (Donations menu)
        __('Settings', 'church-donations'), // Page title
        __('Settings', 'church-donations'), // Menu title
        'manage_options',
        'cdm-settings',
        'cdm_settings_page'
    );

    register_setting('cdm_settings_group', 'cdm_text_color');
    register_setting('cdm_settings_group', 'cdm_background_color');
    register_setting('cdm_settings_group', 'cdm_title_color');
}
add_action('admin_menu', 'cdm_register_settings');

function cdm_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php _e('Settings', 'church-donations'); ?></h1>

        <!-- Styling Settings -->
        <form method="post" action="options.php">
            <?php settings_fields('cdm_settings_group'); ?>
            <h2><?php _e('Styling Options', 'church-donations'); ?></h2>
            <table class="form-table">
                <tr>
                    <th><label><?php _e('Card Title Color', 'church-donations'); ?></label></th>
                    <td><input type="color" name="cdm_title_color" value="<?php echo esc_attr(get_option('cdm_title_color', '#0066cc')); ?>" /></td>
                </tr>
                <tr>
                    <th><label><?php _e('Text Color', 'church-donations'); ?></label></th>
                    <td><input type="color" name="cdm_text_color" value="<?php echo esc_attr(get_option('cdm_text_color', '#333333')); ?>" /></td>
                </tr>
                <tr>
                    <th><label><?php _e('Background Color', 'church-donations'); ?></label></th>
                    <td><input type="color" name="cdm_background_color" value="<?php echo esc_attr(get_option('cdm_background_color', '#f9f9f9')); ?>" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>

        <!-- Shortcodes Section -->
        <h2><?php _e('Available Shortcodes', 'church-donations'); ?></h2>
        <div class="cdm-shortcodes">
            <p><?php _e('Use the following shortcodes to display donation posts on your pages or posts.', 'church-donations'); ?></p>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Shortcode', 'church-donations'); ?></th>
                        <th><?php _e('Description', 'church-donations'); ?></th>
                        <th><?php _e('Example', 'church-donations'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>[church_donations]</code></td>
                        <td><?php _e('Displays the 3 latest donation posts.', 'church-donations'); ?></td>
                        <td><code>[church_donations]</code></td>
                    </tr>
                    <tr>
                        <td><code>[church_donations category="slug"]</code></td>
                        <td><?php _e('Displays donation posts from a specific category (replace "slug" with the category slug, e.g., "blood").', 'church-donations'); ?></td>
                        <td><code>[church_donations category="blood" limit="5"]</code></td>
                    </tr>
                    <tr>
                        <td><code>[church_donations urgent="true"]</code></td>
                        <td><?php _e('Displays only urgent donation posts.', 'church-donations'); ?></td>
                        <td><code>[church_donations urgent="true" limit="3"]</code></td>
                    </tr>
                    <tr>
                        <td><code>[church_donations limit="number"]</code></td>
                        <td><?php _e('Controls the number of posts to display (default is 3).', 'church-donations'); ?></td>
                        <td><code>[church_donations limit="10"]</code></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

function cdm_add_custom_styles() {
    $text_color = get_option('cdm_text_color', '#333333');
    $title_color = get_option('cdm_title_color', '#0066cc');
    $background_color = get_option('cdm_background_color', '#f9f9f9');

    $custom_css = "
        .cdm-donations .card {
            background-color: {$background_color};
        }
        .cdm-donations .card-body {
            color: {$text_color};
        }
        .cdm-donations .card-title {
            color: {$title_color};
        }
    ";
    wp_add_inline_style('cdm-styles', $custom_css);
}
add_action('wp_enqueue_scripts', 'cdm_add_custom_styles');