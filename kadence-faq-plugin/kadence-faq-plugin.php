<?php
/*
Plugin Name: Kadence FAQ Plugin
Description: A plugin to create and display FAQs as an accordion using shortcodes, styled for Kadence theme. To use, create FAQs under the "FAQs" menu in the WordPress admin, optionally assign categories, and add the [kadence_faq] shortcode to any page or post. Use [kadence_faq category="your-category-slug" limit="5"] to filter by category or limit the number of FAQs displayed.
Version: 1.1.1
Author: Miaoulis
License: GPL-2.0+
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Kadence_FAQ_Plugin {
    public function __construct() {
        // Register shortcode
        add_shortcode('kadence_faq', [$this, 'render_faq_shortcode']);
        
        // Enqueue styles and scripts
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        
        // Register custom post type for FAQs
        add_action('init', [$this, 'register_faq_post_type']);
    }
    
    // Register FAQ custom post type
    public function register_faq_post_type() {
        $labels = [
            'name' => 'FAQs',
            'singular_name' => 'FAQ',
            'menu_name' => 'FAQs',
            'name_admin_bar' => 'FAQ',
            'add_new' => 'Add New FAQ',
            'add_new_item' => 'Add New FAQ',
            'edit_item' => 'Edit FAQ',
            'new_item' => 'New FAQ',
            'view_item' => 'View FAQ',
            'all_items' => 'All FAQs',
            'search_items' => 'Search FAQs',
        ];
        
        $args = [
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-editor-help',
            'supports' => ['title', 'editor'],
            'has_archive' => false,
        ];
        
        register_post_type('kadence_faq', $args);
    }
    
    // Enqueue styles and scripts
    public function enqueue_assets() {
        // Enqueue styles
        wp_enqueue_style(
            'kadence-faq-styles',
            plugin_dir_url(__FILE__) . 'css/kadence-faq.css',
            [],
            '1.1.1'
        );
        
        // Enqueue scripts
        wp_enqueue_script(
            'kadence-faq-script',
            plugin_dir_url(__FILE__) . 'js/kadence-faq.js',
            ['jquery'],
            '1.1.1',
            true
        );
    }
    
    // Render FAQ shortcode
    public function render_faq_shortcode($atts) {
        $atts = shortcode_atts([
            'category' => '',
            'limit' => -1,
        ], $atts, 'kadence_faq');
        
        $args = [
            'post_type' => 'kadence_faq',
            'posts_per_page' => intval($atts['limit']),
            'post_status' => 'publish',
        ];
        
        // Add category filter if specified
        if (!empty($atts['category'])) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'kadence_faq_category',
                    'field' => 'slug',
                    'terms' => sanitize_text_field($atts['category']),
                ],
            ];
        }
        
        $faqs = new WP_Query($args);
        
        ob_start();
        ?>
        <div class="kadence-faq-container">
            <?php if ($faqs->have_posts()) : ?>
                <?php while ($faqs->have_posts()) : $faqs->the_post(); ?>
                    <div class="kadence-faq-item">
                        <h3 class="kadence-faq-question" aria-expanded="false">
                            <?php the_title(); ?>
                            <span class="kadence-faq-toggle"></span>
                        </h3>
                        <div class="kadence-faq-answer" style="display: none;">
                            <?php the_content(); ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else : ?>
                <p>No FAQs found.</p>
            <?php endif; ?>
        </div>
        <?php
        wp_reset_postdata();
        
        return ob_get_clean();
    }
}

// Initialize the plugin
new Kadence_FAQ_Plugin();

// Register FAQ category taxonomy
function kadence_faq_register_taxonomy() {
    $labels = [
        'name' => 'FAQ Categories',
        'singular_name' => 'FAQ Category',
        'search_items' => 'Search FAQ Categories',
        'all_items' => 'All FAQ Categories',
        'edit_item' => 'Edit FAQ Category',
        'update_item' => 'Update FAQ Category',
        'add_new_item' => 'Add New FAQ Category',
        'new_item_name' => 'New FAQ Category/minecraft',
        'menu_name' => 'FAQ Categories',
    ];
    
    $args = [
        'hierarchical' => true,
        'labels' => $labels,
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => ['slug' => 'faq-category'],
    ];
    
    register_taxonomy('kadence_faq_category', ['kadence_faq'], $args);
}
add_action('init', 'kadence_faq_register_taxonomy');
?>