<?php
/*
Plugin Name: Simple Event Plugin
Description: A lightweight plugin to create and display events with advanced features like calendar view, RSVP, custom attributes, social sharing, and status indicators.
Version: 1.9
Author: Your Name
*/

// Prevent direct access to this file
if (!defined('ABSPATH')) {
    exit;
}

// Register Custom Post Type for Events
function sep_register_event_post_type() {
    $labels = array(
        'name' => 'Events',
        'singular_name' => 'Event',
        'menu_name' => 'Events',
        'add_new' => 'Add New Event',
        'add_new_item' => 'Add New Event',
        'edit_item' => 'Edit Event',
        'new_item' => 'New Event',
        'view_item' => 'View Event',
        'search_items' => 'Search Events',
        'not_found' => 'No events found',
        'not_found_in_trash' => 'No events found in Trash',
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'supports' => array('title', 'editor', 'thumbnail'),
        'menu_icon' => 'dashicons-calendar-alt',
        'show_in_rest' => true,
        'taxonomies' => array('sep_event_category'),
        'rewrite' => array('slug' => 'event'),
    );

    register_post_type('sep_event', $args);
}
add_action('init', 'sep_register_event_post_type');

// Register Event Category Taxonomy
function sep_register_event_category() {
    $labels = array(
        'name' => 'Event Categories',
        'singular_name' => 'Event Category',
        'search_items' => 'Search Event Categories',
        'all_items' => 'All Event Categories',
        'edit_item' => 'Edit Event Category',
        'update_item' => 'Update Event Category',
        'add_new_item' => 'Add New Event Category',
        'new_item_name' => 'New Event Category Name',
        'menu_name' => 'Event Categories',
    );

    $args = array(
        'hierarchical' => true,
        'labels' => $labels,
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'event-category'),
    );

    register_taxonomy('sep_event_category', 'sep_event', $args);
}
add_action('init', 'sep_register_event_category');

// Add Meta Boxes for Event Details (Date, Time, Location, Speaker)
function sep_add_event_meta_boxes() {
    add_meta_box(
        'sep_event_details',
        'Event Details',
        'sep_event_details_callback',
        'sep_event',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'sep_add_event_meta_boxes');

function sep_event_details_callback($post) {
    wp_nonce_field('sep_save_event_details', 'sep_event_nonce');
    $event_date = get_post_meta($post->ID, '_sep_event_date', true);
    $event_time = get_post_meta($post->ID, '_sep_event_time', true);
    $event_location = get_post_meta($post->ID, '_sep_event_location', true);
    $event_speaker = get_post_meta($post->ID, '_sep_event_speaker', true);
    ?>
    <p>
        <label for="sep_event_date">Event Date:</label><br>
        <input type="date" id="sep_event_date" name="sep_event_date" value="<?php echo esc_attr($event_date); ?>" />
    </p>
    <p>
        <label for="sep_event_time">Event Time:</label><br>
        <input type="time" id="sep_event_time" name="sep_event_time" value="<?php echo esc_attr($event_time); ?>" />
    </p>
    <p>
        <label for="sep_event_location">Event Location:</label><br>
        <input type="text" id="sep_event_location" name="sep_event_location" value="<?php echo esc_attr($event_location); ?>" style="width: 100%;" />
    </p>
    <p>
        <label for="sep_event_speaker">Speaker (Optional):</label><br>
        <input type="text" id="sep_event_speaker" name="sep_event_speaker" value="<?php echo esc_attr($event_speaker); ?>" style="width: 100%;" />
    </p>
    <?php
}

function sep_save_event_details($post_id) {
    if (!isset($_POST['sep_event_nonce']) || !wp_verify_nonce($_POST['sep_event_nonce'], 'sep_save_event_details')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $fields = array('sep_event_date', 'sep_event_time', 'sep_event_location', 'sep_event_speaker');
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
        }
    }
}
add_action('save_post', 'sep_save_event_details');

// Handle RSVP Submission with Toggle
function sep_handle_rsvp() {
    if (isset($_POST['sep_rsvp_submit']) && isset($_POST['event_id']) && isset($_POST['sep_rsvp_nonce']) && wp_verify_nonce($_POST['sep_rsvp_nonce'], 'sep_rsvp_action')) {
        $event_id = absint($_POST['event_id']);
        $user_id = get_current_user_id();
        $rsvp_list = get_post_meta($event_id, '_sep_rsvp_list', true) ?: array();

        if (in_array($user_id, $rsvp_list)) {
            $rsvp_list = array_diff($rsvp_list, array($user_id));
        } else {
            $rsvp_list[] = $user_id;
        }

        update_post_meta($event_id, '_sep_rsvp_list', array_unique($rsvp_list));
        update_post_meta($event_id, '_sep_rsvp_count', count($rsvp_list));
        wp_redirect(add_query_arg('rsvp_updated', 'true', wp_get_referer()));
        exit;
    }
}
add_action('init', 'sep_handle_rsvp');

// Shortcode to Display Events
function sep_display_events_shortcode($atts) {
    $atts = shortcode_atts(array(
        'category' => '',
        'event_id' => '',
        'view' => 'carousel',
    ), $atts, 'display_events');

    $args = array(
        'post_type' => 'sep_event',
        'post_status' => 'publish',
        'meta_key' => '_sep_event_date',
        'orderby' => 'meta_value',
    );

    $display_mode = get_option('sep_display_mode', 'latest'); // Default to latest
    $max_cards = absint(get_option('sep_max_cards', 3)); // Default to 3

    if ($display_mode === 'latest') {
        $args['order'] = 'DESC';
        $args['posts_per_page'] = $max_cards;
    } elseif ($display_mode === 'oldest') {
        $args['order'] = 'ASC';
        $args['posts_per_page'] = $max_cards;
    } elseif ($display_mode === 'quarter') {
        $current_month = date('n'); // 1-12
        $current_year = date('Y');
        $quarter_start = ceil($current_month / 3) * 3 - 2; // Start month of current quarter
        $quarter_end = $quarter_start + 2; // End month of current quarter
        $start_date = "$current_year-" . str_pad($quarter_start, 2, '0', STR_PAD_LEFT) . "-01";
        $end_date = date('Y-m-t', strtotime("$current_year-" . str_pad($quarter_end, 2, '0', STR_PAD_LEFT) . "-01"));

        $args['meta_query'] = array(
            array(
                'key' => '_sep_event_date',
                'value' => array($start_date, $end_date),
                'compare' => 'BETWEEN',
                'type' => 'DATE',
            ),
        );
        $args['order'] = 'ASC';
        $args['posts_per_page'] = $max_cards;
    }

    if (!empty($atts['category'])) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'sep_event_category',
                'field' => 'slug',
                'terms' => sanitize_text_field($atts['category']),
            ),
        );
    }

    if (!empty($atts['event_id'])) {
        $args['post__in'] = array(absint($atts['event_id']));
        $args['posts_per_page'] = 1;
    }

    $events = new WP_Query($args);
    $show_category = get_option('sep_show_category', 'yes');
    $show_speaker = get_option('sep_show_speaker', 'yes');
    $show_description = get_option('sep_show_description', 'no');
    $current_time = current_time('timestamp');
    $user_id = get_current_user_id();

    if ($atts['view'] === 'calendar') {
        $output = '<div class="sep-events-calendar">';
        if ($events->have_posts()) {
            $output .= '<table>';
            $output .= '<thead><tr><th>Date</th><th>Event</th><th>Status</th></tr></thead><tbody>';
            while ($events->have_posts()) {
                $events->the_post();
                $event_date = get_post_meta(get_the_ID(), '_sep_event_date', true);
                $event_time = get_post_meta(get_the_ID(), '_sep_event_time', true);
                $event_datetime = strtotime("$event_date $event_time");
                $status = ($event_datetime > $current_time) ? 'Upcoming' : (($event_datetime <= $current_time && $event_datetime + 3600 >= $current_time) ? 'Live' : 'Past');

                $output .= '<tr>';
                $output .= '<td>' . esc_html($event_date) . ' ' . esc_html($event_time) . '</td>';
                $output .= '<td><a href="' . esc_url(get_permalink()) . '">' . get_the_title() . '</a></td>';
                $output .= '<td>' . esc_html($status) . '</td>';
                $output .= '</tr>';
            }
            $output .= '</tbody></table>';
        } else {
            $output .= '<p>No events found.</p>';
        }
        $output .= '</div>';
    } else {
        $output = '<div class="sep-events-list sep-carousel">';
        if ($events->have_posts()) {
            $output .= '<div class="sep-carousel-inner">';
            $count = 0;
            while ($events->have_posts() && $count < $max_cards) {
                $events->the_post();
                $count++;
                $event_date = get_post_meta(get_the_ID(), '_sep_event_date', true);
                $event_time = get_post_meta(get_the_ID(), '_sep_event_time', true);
                $event_location = get_post_meta(get_the_ID(), '_sep_event_location', true);
                $event_speaker = get_post_meta(get_the_ID(), '_sep_event_speaker', true);
                $thumbnail = get_the_post_thumbnail(get_the_ID(), 'medium', array('class' => 'sep-event-image'));
                $categories = get_the_terms(get_the_ID(), 'sep_event_category');
                $category_names = $categories ? wp_list_pluck($categories, 'name') : array();
                $permalink = get_permalink();
                $rsvp_list = get_post_meta(get_the_ID(), '_sep_rsvp_list', true) ?: array();
                $rsvp_count = count($rsvp_list);
                $has_rsvped = in_array($user_id, $rsvp_list);
                $event_datetime = strtotime("$event_date $event_time");
                $status = ($event_datetime > $current_time) ? 'Upcoming' : (($event_datetime <= $current_time && $event_datetime + 3600 >= $current_time) ? 'Live' : 'Past');

                $output .= '<a href="' . esc_url($permalink) . '" class="sep-event-link">';
                $output .= '<div class="sep-event">';
                if ($thumbnail) {
                    $output .= $thumbnail;
                }
                $output .= '<div class="sep-event-datetime-box">' . esc_html($event_date) . ' ' . esc_html($event_time) . '</div>';
                $output .= '<div class="sep-event-content">';
                $output .= '<h2>' . get_the_title() . '</h2>';
                $output .= '<p><strong>Location:</strong> ' . esc_html($event_location) . '</p>';
                $output .= '<p><strong>Status:</strong> <span class="sep-event-status sep-status-' . strtolower($status) . '">' . esc_html($status) . '</span></p>';
                $output .= '<p><strong>Attendees:</strong> ' . esc_html($rsvp_count) . '</p>';
                if ($show_category === 'yes' && !empty($category_names)) {
                    $output .= '<p><strong>Category:</strong> ' . esc_html(implode(', ', $category_names)) . '</p>';
                }
                if ($show_speaker === 'yes' && !empty($event_speaker)) {
                    $output .= '<p><strong>Speaker:</strong> ' . esc_html($event_speaker) . '</p>';
                }
                $output .= '<div class="sep-action-buttons">';
                $output .= '<form method="post" class="sep-rsvp-form">';
                $output .= wp_nonce_field('sep_rsvp_action', 'sep_rsvp_nonce', true, false);
                $output .= '<input type="hidden" name="event_id" value="' . get_the_ID() . '">';
                $output .= '<button type="submit" name="sep_rsvp_submit" class="' . ($has_rsvped ? 'sep-rsvp-cancel' : '') . '">' . ($has_rsvped ? 'Cancel RSVP' : 'RSVP') . '</button>';
                $output .= '</form>';
                $output .= '<div class="sep-social-buttons">';
                $output .= '<a href="https://twitter.com/intent/tweet?url=' . urlencode($permalink) . '&text=' . urlencode(get_the_title()) . '" target="_blank" class="sep-social-twitter"><i class="fab fa-twitter"></i></a>';
                $output .= '<a href="https://www.facebook.com/sharer/sharer.php?u=' . urlencode($permalink) . '" target="_blank" class="sep-social-facebook"><i class="fab fa-facebook-f"></i></a>';
                $output .= '</div>';
                $output .= '</div>';
                if ($show_description === 'yes') {
                    $output .= '<div class="sep-event-description">' . get_the_content() . '</div>';
                }
                $output .= '</div>';
                $output .= '</div>';
                $output .= '</a>';
            }
            $output .= '</div>';
        } else {
            $output .= '<p>No events found.</p>';
        }
        $output .= '</div>';
    }

    wp_reset_postdata();
    return $output;
}
add_shortcode('display_events', 'sep_display_events_shortcode');

// Enqueue Styles and Scripts
function sep_enqueue_styles() {
    wp_enqueue_style('sep-styles', plugins_url('css/style.css', __FILE__));
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css');
    $carousel_js = "
        document.addEventListener('DOMContentLoaded', function() {
            const carousels = document.querySelectorAll('.sep-carousel');
            carousels.forEach(carousel => {
                const inner = carousel.querySelector('.sep-carousel-inner');
                let scrollAmount = 0;
                const scrollStep = 320;
                setInterval(() => {
                    scrollAmount += scrollStep;
                    if (scrollAmount >= inner.scrollWidth - inner.clientWidth) {
                        scrollAmount = 0;
                    }
                    inner.scrollTo({ left: scrollAmount, behavior: 'smooth' });
                }, 3000);
            });
        });
    ";
    wp_add_inline_script('jquery', $carousel_js);
}
add_action('wp_enqueue_scripts', 'sep_enqueue_styles');

// Apply Custom Colors to Frontend
function sep_enqueue_custom_styles() {
    $primary_color = get_option('sep_primary_color', '#0073aa');
    $secondary_color = get_option('sep_secondary_color', '#f5f5f5');
    $text_color = get_option('sep_text_color', '#333');
    $accent_color = get_option('sep_accent_color', '#00aaff');

    $custom_css = "
        :root {
            --primary-color: {$primary_color};
            --secondary-color: {$secondary_color};
            --text-color: {$text_color};
            --accent-color: {$accent_color};
        }
    ";
    wp_add_inline_style('sep-styles', $custom_css);
}
add_action('wp_enqueue_scripts', 'sep_enqueue_custom_styles');

// Add Settings Menu
function sep_settings_menu() {
    add_options_page(
        'Simple Event Plugin Settings',
        'Event Settings',
        'manage_options',
        'sep-settings',
        'sep_settings_page'
    );
}
add_action('admin_menu', 'sep_settings_menu');

// Register Settings
function sep_register_settings() {
    register_setting('sep_settings_group', 'sep_primary_color');
    register_setting('sep_settings_group', 'sep_secondary_color');
    register_setting('sep_settings_group', 'sep_text_color');
    register_setting('sep_settings_group', 'sep_accent_color');
    register_setting('sep_settings_group', 'sep_show_category');
    register_setting('sep_settings_group', 'sep_show_speaker');
    register_setting('sep_settings_group', 'sep_show_description');
    register_setting('sep_settings_group', 'sep_max_cards', array('sanitize_callback' => 'absint'));
    register_setting('sep_settings_group', 'sep_display_mode');
}
add_action('admin_init', 'sep_register_settings');

// Settings Page Callback
function sep_settings_page() {
    ?>
    <div class="wrap">
        <h1>Simple Event Plugin Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('sep_settings_group'); ?>
            <?php do_settings_sections('sep_settings_group'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="sep_primary_color">Primary Color (Titles, Accents)</label></th>
                    <td><input type="color" name="sep_primary_color" value="<?php echo esc_attr(get_option('sep_primary_color', '#0073aa')); ?>" /></td>
                </tr>
                <tr>
                    <th><label for="sep_secondary_color">Secondary Color (Card Background)</label></th>
                    <td><input type="color" name="sep_secondary_color" value="<?php echo esc_attr(get_option('sep_secondary_color', '#f5f5f5')); ?>" /></td>
                </tr>
                <tr>
                    <th><label for="sep_text_color">Text Color</label></th>
                    <td><input type="color" name="sep_text_color" value="<?php echo esc_attr(get_option('sep_text_color', '#333')); ?>" /></td>
                </tr>
                <tr>
                    <th><label for="sep_accent_color">Accent Color (Hover)</label></th>
                    <td><input type="color" name="sep_accent_color" value="<?php echo esc_attr(get_option('sep_accent_color', '#00aaff')); ?>" /></td>
                </tr>
                <tr>
                    <th><label for="sep_show_category">Show Event Category</label></th>
                    <td>
                        <select name="sep_show_category" id="sep_show_category">
                            <option value="yes" <?php selected(get_option('sep_show_category', 'yes'), 'yes'); ?>>Yes</option>
                            <option value="no" <?php selected(get_option('sep_show_category', 'yes'), 'no'); ?>>No</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="sep_show_speaker">Show Event Speaker</label></th>
                    <td>
                        <select name="sep_show_speaker" id="sep_show_speaker">
                            <option value="yes" <?php selected(get_option('sep_show_speaker', 'yes'), 'yes'); ?>>Yes</option>
                            <option value="no" <?php selected(get_option('sep_show_speaker', 'yes'), 'no'); ?>>No</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="sep_show_description">Show Event Description</label></th>
                    <td>
                        <select name="sep_show_description" id="sep_show_description">
                            <option value="yes" <?php selected(get_option('sep_show_description', 'no'), 'yes'); ?>>Yes</option>
                            <option value="no" <?php selected(get_option('sep_show_description', 'no'), 'no'); ?>>No</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="sep_max_cards">Max Number of Cards (Desktop)</label></th>
                    <td>
                        <input type="number" name="sep_max_cards" id="sep_max_cards" value="<?php echo esc_attr(get_option('sep_max_cards', 3)); ?>" min="1" step="1" />
                        <p class="description">Set the maximum number of event cards to display on desktop. Tablet will show up to 2, and mobile up to 1.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="sep_display_mode">Display Mode</label></th>
                    <td>
                        <select name="sep_display_mode" id="sep_display_mode">
                            <option value="latest" <?php selected(get_option('sep_display_mode', 'latest'), 'latest'); ?>>Max 3 Latest</option>
                            <option value="oldest" <?php selected(get_option('sep_display_mode', 'latest'), 'oldest'); ?>>Max 3 Oldest</option>
                            <option value="quarter" <?php selected(get_option('sep_display_mode', 'latest'), 'quarter'); ?>>Current Quarter</option>
                        </select>
                        <p class="description">Choose which events to display: latest, oldest, or those in the current quarter.</p>
                    </td>
                </tr>
            </table>
            <h2>Shortcode Reference</h2>
            <p>Use the following shortcodes to display events:</p>
            <ul>
                <li><code>[display_events]</code> - Display events in a carousel based on display mode.</li>
                <li><code>[display_events category="slug"]</code> - Filter by category.</li>
                <li><code>[display_events event_id="123"]</code> - Display a single event by ID.</li>
                <li><code>[display_events view="calendar"]</code> - Display all events in a calendar view.</li>
            </ul>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Activation Hook to Flush Rewrite Rules
function sep_activate() {
    sep_register_event_post_type();
    sep_register_event_category();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'sep_activate');

// Deactivation Hook
function sep_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'sep_deactivate');