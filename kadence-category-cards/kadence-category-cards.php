<?php
/*
Plugin Name: Kadence Category Cards
Description: Enhances the Kadence theme blog page by allowing posts to be displayed as grouped category cards.
Version: 1.0
Author: Miaoulis N
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add Customizer settings
function kcc_customize_register($wp_customize) {
    // Add section
    $wp_customize->add_section('kcc_display_options', array(
        'title'    => __('Kadence Blog Display', 'kadence-category-cards'),
        'priority' => 30,
    ));

    // Add setting for display mode
    $wp_customize->add_setting('kcc_display_mode', array(
        'default'           => 'default',
        'sanitize_callback' => 'sanitize_key',
    ));

    // Add control for display mode
    $wp_customize->add_control('kcc_display_mode_control', array(
        'label'    => __('Blog Display Mode', 'kadence-category-cards'),
        'section'  => 'kcc_display_options',
        'settings' => 'kcc_display_mode',
        'type'     => 'select',
        'choices'  => array(
            'default'       => __('Default (All Posts)', 'kadence-category-cards'),
            'category_cards' => __('Category Cards', 'kadence-category-cards'),
        ),
    ));
}
add_action('customize_register', 'kcc_customize_register');

// Enqueue styles for category cards
function kcc_enqueue_styles() {
    if (is_home() && get_theme_mod('kcc_display_mode', 'default') === 'category_cards') {
        wp_enqueue_style('kcc-styles', plugins_url('style.css', __FILE__));
    }
}
add_action('wp_enqueue_scripts', 'kcc_enqueue_styles');

// Override blog page output for category cards
function kcc_override_blog_output() {
    if (is_home() && get_theme_mod('kcc_display_mode', 'default') === 'category_cards') {
        // Start output buffering
        ob_start();
        ?>
        <div class="kcc-category-cards">
            <?php
            // Get all categories with at least one post
            $categories = get_categories(array(
                'hide_empty' => true,
            ));

            foreach ($categories as $category) {
                ?>
                <div class="kcc-category-card">
                    <h2 class="kcc-category-title"><?php echo esc_html($category->name); ?></h2>
                    <div class="kcc-posts-list">
                        <?php
                        $posts_query = new WP_Query(array(
                            'cat'            => $category->term_id,
                            'posts_per_page' => -1, // Show all posts in category
                        ));

                        if ($posts_query->have_posts()) {
                            while ($posts_query->have_posts()) {
                                $posts_query->the_post();
                                ?>
                                <div class="kcc-post-item">
                                    <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                                    <div class="kcc-post-excerpt"><?php the_excerpt(); ?></div>
                                </div>
                                <?php
                            }
                            wp_reset_postdata();
                        } else {
                            echo '<p>' . __('No posts found in this category.', 'kadence-category-cards') . '</p>';
                        }
                        ?>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>
        <?php
        // Get buffered content and clean buffer
        $custom_output = ob_get_clean();

        // Replace Kadence's default loop
        add_filter('kadence_loop_content', function() use ($custom_output) {
            echo $custom_output;
            return false; // Prevent default loop from running
        });
    }
}
add_action('wp', 'kcc_override_blog_output');

// Load text domain for translations
function kcc_load_textdomain() {
    load_plugin_textdomain('kadence-category-cards', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'kcc_load_textdomain');