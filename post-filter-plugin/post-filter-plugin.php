<?php
/*
Plugin Name: Post Filter Plugin
Description: A responsive WordPress plugin to filter posts by category, date, author, tag, and custom text. Optimized for Kadence theme.
Version: 3.3
Author: Miaoulis N.
License: GPL2
Text Domain: post-filter-plugin
Domain Path: /languages
*/

// Load plugin text domain for translations
function pfp_load_textdomain() {
    load_plugin_textdomain('post-filter-plugin', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'pfp_load_textdomain');

// Enqueue styles and scripts
function pfp_enqueue_assets() {
    $dependencies = (wp_style_is('kadence-global', 'enqueued')) ? array('kadence-global') : array();
    wp_enqueue_style('pfp-style', plugins_url('css/style.css', __FILE__), $dependencies, '3.3', 'all');
    wp_enqueue_script('pfp-script', plugins_url('script.js', __FILE__), array('jquery'), '1.0', true);
}
add_action('wp_enqueue_scripts', 'pfp_enqueue_assets', 20);

// Function to render the filter form
function pfp_render_filter_form($is_sidebar = false) {
    $options = get_option('pfp_settings');
    $enable_filters = isset($options['enable_filters']) ? $options['enable_filters'] : array(
        'category' => 1,
        'date' => 1,
        'quick_date' => 1,
        'author' => 1,
        'tag' => 1,
        'search' => 1,
    );
    $filter_settings = isset($options['filter_settings']) ? $options['filter_settings'] : array(
        'category' => array('order' => 1, 'visible' => 1),
        'date' => array('order' => 2, 'visible' => 1),
        'quick_date' => array('order' => 3, 'visible' => 1),
        'author' => array('order' => 4, 'visible' => 1),
        'tag' => array('order' => 5, 'visible' => 1),
        'search' => array('order' => 6, 'visible' => 1),
    );

    // Debug: Log the filter settings used for rendering
    error_log('PFP Render Filter Settings: ' . print_r($filter_settings, true));

    // Sort filters by order
    uasort($filter_settings, function($a, $b) {
        return $a['order'] - $b['order'];
    });

    ob_start();
    ?>
    <div class="pfp-filter-container<?php echo $is_sidebar ? ' pfp-sidebar' : ''; ?>">
        <form method="GET" class="pfp-filter-form" action="<?php echo esc_url(get_permalink(get_option('page_for_posts'))); ?>">
            <div class="pfp-filter-row">
                <?php
                foreach ($filter_settings as $filter => $settings) {
                    // Skip if filter is disabled or not visible
                    if (!isset($enable_filters[$filter]) || !$enable_filters[$filter] || !isset($settings['visible']) || !$settings['visible']) {
                        continue;
                    }

                    switch ($filter) {
                        case 'category':
                            ?>
                            <!-- Category Filter -->
                            <div class="pfp-filter-item">
                                <label for="pfp_category"><?php _e('Category:', 'post-filter-plugin'); ?></label>
                                <?php
                                wp_dropdown_categories(array(
                                    'show_option_all' => __('All Categories', 'post-filter-plugin'),
                                    'name' => 'pfp_category',
                                    'id' => 'pfp_category',
                                    'selected' => isset($_GET['pfp_category']) ? intval($_GET['pfp_category']) : 0,
                                ));
                                ?>
                            </div>
                            <?php
                            break;

                        case 'date':
                            ?>
                            <!-- Month Filter -->
                            <div class="pfp-filter-item">
                                <label for="pfp_month"><?php _e('Month:', 'post-filter-plugin'); ?></label>
                                <select name="pfp_month" id="pfp_month">
                                    <option value=""><?php _e('All Months', 'post-filter-plugin'); ?></option>
                                    <?php
                                    $months = array(
                                        '01' => __('January', 'post-filter-plugin'),
                                        '02' => __('February', 'post-filter-plugin'),
                                        '03' => __('March', 'post-filter-plugin'),
                                        '04' => __('April', 'post-filter-plugin'),
                                        '05' => __('May', 'post-filter-plugin'),
                                        '06' => __('June', 'post-filter-plugin'),
                                        '07' => __('July', 'post-filter-plugin'),
                                        '08' => __('August', 'post-filter-plugin'),
                                        '09' => __('September', 'post-filter-plugin'),
                                        '10' => __('October', 'post-filter-plugin'),
                                        '11' => __('November', 'post-filter-plugin'),
                                        '12' => __('December', 'post-filter-plugin'),
                                    );
                                    foreach ($months as $value => $label) {
                                        echo '<option value="' . esc_attr($value) . '" ' . selected(isset($_GET['pfp_month']) && $_GET['pfp_month'] == $value, true, false) . '>' . esc_html($label) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>

                            <!-- Year/Week Filter -->
                            <div class="pfp-filter-item">
                                <label for="pfp_year_week"><?php _e('Year/Week:', 'post-filter-plugin'); ?></label>
                                <select name="pfp_year_week" id="pfp_year_week">
                                    <option value=""><?php _e('All Years', 'post-filter-plugin'); ?></option>
                                    <option value="current_week" <?php selected(isset($_GET['pfp_year_week']) && $_GET['pfp_year_week'] == 'current_week'); ?>><?php _e('Current Week', 'post-filter-plugin'); ?></option>
                                    <?php
                                    $current_year = date('Y');
                                    for ($year = $current_year; $year >= $current_year - 10; $year--) {
                                        echo '<option value="' . esc_attr($year) . '" ' . selected(isset($_GET['pfp_year_week']) && $_GET['pfp_year_week'] == $year, true, false) . '>' . esc_html($year) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <?php
                            break;

                        case 'quick_date':
                            ?>
                            <!-- Quick Date Filters -->
                            <div class="pfp-filter-item">
                                <label><?php _e('Quick Filters:', 'post-filter-plugin'); ?></label>
                                <select name="pfp_quick_date" id="pfp_quick_date">
                                    <option value=""><?php _e('Select', 'post-filter-plugin'); ?></option>
                                    <option value="last_week" <?php selected(isset($_GET['pfp_quick_date']) && $_GET['pfp_quick_date'] == 'last_week'); ?>><?php _e('Last Week', 'post-filter-plugin'); ?></option>
                                    <option value="last_month" <?php selected(isset($_GET['pfp_quick_date']) && $_GET['pfp_quick_date'] == 'last_month'); ?>><?php _e('Last Month', 'post-filter-plugin'); ?></option>
                                    <option value="last_year" <?php selected(isset($_GET['pfp_quick_date']) && $_GET['pfp_quick_date'] == 'last_year'); ?>><?php _e('Last Year', 'post-filter-plugin'); ?></option>
                                </select>
                            </div>
                            <?php
                            break;

                        case 'author':
                            ?>
                            <!-- Author Filter -->
                            <div class="pfp-filter-item">
                                <label for="pfp_author"><?php _e('Author:', 'post-filter-plugin'); ?></label>
                                <?php
                                wp_dropdown_users(array(
                                    'show_option_all' => __('All Authors', 'post-filter-plugin'),
                                    'name' => 'pfp_author',
                                    'id' => 'pfp_author',
                                    'selected' => isset($_GET['pfp_author']) ? intval($_GET['pfp_author']) : 0,
                                ));
                                ?>
                            </div>
                            <?php
                            break;

                        case 'tag':
                            ?>
                            <!-- Tag Filter -->
                            <div class="pfp-filter-item">
                                <label for="pfp_tag"><?php _e('Tag:', 'post-filter-plugin'); ?></label>
                                <input type="text" name="pfp_tag" id="pfp_tag" value="<?php echo esc_attr(isset($_GET['pfp_tag']) ? $_GET['pfp_tag'] : ''); ?>" placeholder="<?php esc_attr_e('Enter tag', 'post-filter-plugin'); ?>">
                            </div>
                            <?php
                            break;

                        case 'search':
                            ?>
                            <!-- Custom Text Filter -->
                            <div class="pfp-filter-item">
                                <label for="pfp_search"><?php _e('Search Text:', 'post-filter-plugin'); ?></label>
                                <input type="text" name="pfp_search" id="pfp_search" value="<?php echo esc_attr(isset($_GET['pfp_search']) ? $_GET['pfp_search'] : ''); ?>" placeholder="<?php esc_attr_e('Search in post', 'post-filter-plugin'); ?>">
                            </div>
                            <?php
                            break;
                    }
                }
                ?>

                <!-- Submit Button (always displayed) -->
                <div class="pfp-filter-item pfp-submit-wrapper">
                    <button type="submit" class="pfp-submit"><?php _e('Filter', 'post-filter-plugin'); ?></button>
                </div>

                <!-- Preserve other query parameters (e.g., paged) -->
                <?php
                foreach ($_GET as $key => $value) {
                    if (!in_array($key, ['pfp_category', 'pfp_month', 'pfp_year_week', 'pfp_quick_date', 'pfp_author', 'pfp_tag', 'pfp_search']) && !empty($value)) {
                        echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
                    }
                }
                ?>
            </div>
        </form>
    </div>
    <?php
    return ob_get_clean();
}

// Inject filter form (only if display mode is horizontal)
function pfp_inject_filter_form() {
    $options = get_option('pfp_settings');
    $display_mode = isset($options['display_mode']) ? $options['display_mode'] : 'horizontal';

    if ($display_mode === 'horizontal' && is_home() && !is_front_page()) {
        echo pfp_render_filter_form();
    }
}
add_action('kadence_before_main_content', 'pfp_inject_filter_form');

// Shortcode for sidebar filters
function pfp_filters_shortcode() {
    return pfp_render_filter_form(true);
}
add_shortcode('pfp_filters', 'pfp_filters_shortcode');

// Modify main query based on filter inputs
function pfp_modify_main_query($query) {
    if (!is_admin() && $query->is_main_query() && is_home() && !is_front_page()) {
        // Category Filter
        if (!empty($_GET['pfp_category']) && $_GET['pfp_category'] > 0) {
            $query->set('cat', intval($_GET['pfp_category']));
        }

        // Month and Year/Week Filter
        $date_query = array();
        if (!empty($_GET['pfp_month']) || !empty($_GET['pfp_year_week'])) {
            if (!empty($_GET['pfp_month'])) {
                $date_query['month'] = intval($_GET['pfp_month']);
            }
            if (!empty($_GET['pfp_year_week'])) {
                if ($_GET['pfp_year_week'] === 'current_week') {
                    $current_week_start = date('Y-m-d', strtotime('monday this week'));
                    $current_week_end = date('Y-m-d', strtotime('sunday this week'));
                    $date_query['after'] = $current_week_start;
                    $date_query['before'] = $current_week_end;
                    $date_query['inclusive'] = true;
                } else {
                    $date_query['year'] = intval($_GET['pfp_year_week']);
                }
            }
            if (!empty($date_query)) {
                $query->set('date_query', array($date_query));
            }
        }

        // Quick Date Filters
        if (!empty($_GET['pfp_quick_date'])) {
            $date_query = array();
            switch ($_GET['pfp_quick_date']) {
                case 'last_week':
                    $date_query['after'] = '1 week ago';
                    break;
                case 'last_month':
                    $date_query['after'] = '1 month ago';
                    break;
                case 'last_year':
                    $date_query['after'] = '1 year ago';
                    break;
            }
            $query->set('date_query', array($date_query));
        }

        // Author Filter
        if (!empty($_GET['pfp_author']) && $_GET['pfp_author'] > 0) {
            $query->set('author', intval($_GET['pfp_author']));
        }

        // Tag Filter
        if (!empty($_GET['pfp_tag'])) {
            $query->set('tag', sanitize_text_field($_GET['pfp_tag']));
        }

        // Search Text Filter
        if (!empty($_GET['pfp_search'])) {
            $query->set('s', sanitize_text_field($_GET['pfp_search']));
        }
    }
}
add_action('pre_get_posts', 'pfp_modify_main_query');

// Shortcode support (optional)
function pfp_post_filter_shortcode($atts) {
    ob_start();
    echo pfp_render_filter_form();
    ?>
    <div class="pfp-results">
        <?php
        $args = array(
            'post_type' => 'post',
            'posts_per_page' => 10,
            'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
        );

        if (!empty($_GET['pfp_category']) && $_GET['pfp_category'] > 0) {
            $args['cat'] = intval($_GET['pfp_category']);
        }

        $date_query = array();
        if (!empty($_GET['pfp_month']) || !empty($_GET['pfp_year_week'])) {
            if (!empty($_GET['pfp_month'])) {
                $date_query['month'] = intval($_GET['pfp_month']);
            }
            if (!empty($_GET['pfp_year_week'])) {
                if ($_GET['pfp_year_week'] === 'current_week') {
                    $current_week_start = date('Y-m-d', strtotime('monday this week'));
                    $current_week_end = date('Y-m-d', strtotime('sunday this week'));
                    $date_query['after'] = $current_week_start;
                    $date_query['before'] = $current_week_end;
                    $date_query['inclusive'] = true;
                } else {
                    $date_query['year'] = intval($_GET['pfp_year_week']);
                }
            }
            if (!empty($date_query)) {
                $args['date_query'] = array($date_query);
            }
        }

        if (!empty($_GET['pfp_quick_date'])) {
            $date_query = array();
            switch ($_GET['pfp_quick_date']) {
                case 'last_week':
                    $date_query['after'] = '1 week ago';
                    break;
                case 'last_month':
                    $date_query['after'] = '1 month ago';
                    break;
                case 'last_year':
                    $date_query['after'] = '1 year ago';
                    break;
            }
            $args['date_query'] = array($date_query);
        }

        if (!empty($_GET['pfp_author']) && $_GET['pfp_author'] > 0) {
            $args['author'] = intval($_GET['pfp_author']);
        }

        if (!empty($_GET['pfp_tag'])) {
            $args['tag'] = sanitize_text_field($_GET['pfp_tag']);
        }

        if (!empty($_GET['pfp_search'])) {
            $args['s'] = sanitize_text_field($_GET['pfp_search']);
        }

        $query = new WP_Query($args);

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                ?>
                <div class="pfp-post">
                    <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                    <div class="pfp-meta">
                        <span><?php printf(__('By %s', 'post-filter-plugin'), get_the_author()); ?></span> | <span><?php the_date(); ?></span>
                    </div>
                    <div class="pfp-excerpt"><?php the_excerpt(); ?></div>
                </div>
                <?php
            }
            echo '<div class="pfp-pagination">';
            echo paginate_links(array(
                'total' => $query->max_num_pages,
                'current' => max(1, get_query_var('paged')),
            ));
            echo '</div>';
        } else {
            echo '<p>' . __('No posts found.', 'post-filter-plugin') . '</p>';
        }
        wp_reset_postdata();
        ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('post_filter', 'pfp_post_filter_shortcode');

// Fallback: Add critical styles inline if the stylesheet fails to load
function pfp_add_inline_styles() {
    if (is_home() && !is_front_page()) {
        $inline_styles = "
            .pfp-filter-container {
                max-width: 1200px;
                margin: 20px auto;
                padding: 0 20px;
                position: relative;
                z-index: 10;
                width: 100%;
                box-sizing: border-box;
            }
            .pfp-filter-form {
                background: #f9f9f9;
                padding: 15px;
                border-radius: 5px;
                margin-bottom: 20px;
                display: flex;
                align-items: flex-end;
                width: 100%;
                box-sizing: border-box;
            }
            .pfp-filter-row {
                display: flex;
                flex-direction: row;
                flex-wrap: nowrap;
                gap: 10px;
                align-items: flex-end;
                width: 100%;
                overflow-x: auto;
                scrollbar-width: thin;
                min-height: 60px;
                transition: all 0.3s ease;
            }
            .pfp-filter-row::-webkit-scrollbar {
                height: 8px;
            }
            .pfp-filter-row::-webkit-scrollbar-thumb {
                background: #0073aa;
                border-radius: 4px;
            }
            .pfp-filter-item {
                flex: 0 0 auto;
                display: flex;
                flex-direction: column;
                min-width: 120px;
                max-width: 150px;
                transition: all 0.3s ease;
            }
            .pfp-filter-item label {
                margin-bottom: 5px;
                font-weight: bold;
                font-size: 12px;
            }
            .pfp-filter-item input,
            .pfp-filter-item select {
                padding: 6px;
                border: 1px solid #ddd;
                border-radius: 4px;
                width: 100%;
                box-sizing: border-box;
                font-size: 12px;
            }
            .pfp-submit-wrapper {
                flex: 0 0 auto;
                min-width: auto;
            }
            .pfp-submit {
                padding: 6px 20px;
                background: #0073aa;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 12px;
                transition: background 0.3s ease;
            }
            .pfp-submit:hover {
                background: #005177;
            }
            .pfp-filter-container.pfp-sidebar {
                max-width: none;
                margin: 0;
                padding: 0;
            }
            .pfp-filter-container.pfp-sidebar .pfp-filter-form {
                background: none;
                padding: 0;
                border-radius: 0;
                margin-bottom: 0;
            }
            .pfp-filter-container.pfp-sidebar .pfp-filter-row {
                flex-direction: column;
                flex-wrap: wrap;
                overflow-x: visible;
                align-items: stretch;
                gap: 15px;
                min-height: auto;
            }
            .pfp-filter-container.pfp-sidebar .pfp-filter-item {
                min-width: 100%;
                max-width: none;
                margin: 0;
            }
            .pfp-filter-container.pfp-sidebar .pfp-filter-item input,
            .pfp-filter-container.pfp-sidebar .pfp-filter-item select {
                font-size: 14px;
            }
            .pfp-filter-container.pfp-sidebar .pfp-submit-wrapper {
                display: flex;
                justify-content: center;
            }
            .pfp-filter-container.pfp-sidebar .pfp-submit {
                padding: 8px 30px;
                font-size: 14px;
            }
            @media (max-width: 1024px) and (min-width: 769px) {
                .pfp-filter-container:not(.pfp-sidebar) .pfp-filter-form .pfp-filter-row {
                    display: grid !important;
                    grid-template-columns: repeat(2, 1fr) !important;
                    gap: 15px !important;
                    overflow-x: visible !important;
                    align-items: stretch !important;
                    min-height: auto !important;
                }
                .pfp-filter-container:not(.pfp-sidebar) .pfp-filter-form .pfp-filter-row .pfp-filter-item {
                    min-width: 100% !important;
                    max-width: none !important;
                    margin: 0 !important;
                }
                .pfp-filter-container:not(.pfp-sidebar) .pfp-filter-form .pfp-filter-row .pfp-filter-item input,
                .pfp-filter-container:not(.pfp-sidebar) .pfp-filter-form .pfp-filter-row .pfp-filter-item select {
                    font-size: 14px !important;
                }
                .pfp-filter-container:not(.pfp-sidebar) .pfp-filter-form .pfp-filter-row .pfp-submit-wrapper {
                    grid-column: 1 / -1 !important;
                    display: flex !important;
                    justify-content: center !important;
                }
                .pfp-filter-container:not(.pfp-sidebar) .pfp-filter-form .pfp-filter-row .pfp-submit-wrapper .pfp-submit {
                    padding: 8px 30px !important;
                    font-size: 14px !important;
                }
            }
            @media (max-width: 768px) {
                .pfp-filter-container:not(.pfp-sidebar) .pfp-filter-form .pfp-filter-row {
                    display: flex !important;
                    flex-direction: column !important;
                    flex-wrap: wrap !important;
                    overflow-x: visible !important;
                    align-items: stretch !important;
                    gap: 15px !important;
                    padding: 10px !important;
                }
                .pfp-filter-container:not(.pfp-sidebar) .pfp-filter-form .pfp-filter-row .pfp-filter-item {
                    min-width: 100% !important;
                    max-width: none !important;
                    margin: 0 !important;
                }
                .pfp-filter-container:not(.pfp-sidebar) .pfp-filter-form .pfp-filter-row .pfp-filter-item input,
                .pfp-filter-container:not(.pfp-sidebar) .pfp-filter-form .pfp-filter-row .pfp-filter-item select {
                    font-size: 14px !important;
                }
                .pfp-filter-container:not(.pfp-sidebar) .pfp-filter-form .pfp-filter-row .pfp-submit-wrapper {
                    display: flex !important;
                    justify-content: center !important;
                }
                .pfp-filter-container:not(.pfp-sidebar) .pfp-filter-form .pfp-filter-row .pfp-submit-wrapper .pfp-submit {
                    padding: 8px 30px !important;
                    font-size: 14px !important;
                }
            }
        ";
        wp_add_inline_style('pfp-style', $inline_styles);
    }
}
add_action('wp_enqueue_scripts', 'pfp_add_inline_styles', 30);

// Ensure the viewport meta tag is present for proper responsive behavior
function pfp_add_viewport_meta_tag() {
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
}
add_action('wp_head', 'pfp_add_viewport_meta_tag', 1);

// Add settings page
function pfp_register_settings() {
    // Register settings
    register_setting('pfp_settings_group', 'pfp_settings', array('sanitize_callback' => 'pfp_sanitize_settings'));

    // Add settings section
    add_settings_section(
        'pfp_main_section',
        __('Post Filter Plugin Settings', 'post-filter-plugin'),
        'pfp_settings_section_callback',
        'pfp-settings'
    );

    // Add settings fields
    add_settings_field(
        'pfp_enable_filters',
        __('Enable/Disable Filters', 'post-filter-plugin'),
        'pfp_enable_filters_callback',
        'pfp-settings',
        'pfp_main_section'
    );

    add_settings_field(
        'pfp_display_mode',
        __('Display Mode', 'post-filter-plugin'),
        'pfp_display_mode_callback',
        'pfp-settings',
        'pfp_main_section'
    );

    add_settings_field(
        'pfp_filter_order',
        __('Filter Order and Visibility', 'post-filter-plugin'),
        'pfp_filter_order_callback',
        'pfp-settings',
        'pfp_main_section'
    );
}
add_action('admin_init', 'pfp_register_settings');

// Add menu item for settings page
function pfp_add_settings_page() {
    add_options_page(
        __('Post Filter Plugin Settings', 'post-filter-plugin'),
        __('Post Filter Settings', 'post-filter-plugin'),
        'manage_options',
        'pfp-settings',
        'pfp_settings_page_callback'
    );
}
add_action('admin_menu', 'pfp_add_settings_page');

// Settings section callback
function pfp_settings_section_callback() {
    echo '<p>' . __('Configure the settings for the Post Filter Plugin.', 'post-filter-plugin') . '</p>';
}

// Enable/Disable Filters callback
function pfp_enable_filters_callback() {
    $options = get_option('pfp_settings');
    $filters = array(
        'category' => __('Category Filter', 'post-filter-plugin'),
        'date' => __('Date Filtering (Month & Year/Week)', 'post-filter-plugin'),
        'quick_date' => __('Quick Date Filters', 'post-filter-plugin'),
        'author' => __('Author Filter', 'post-filter-plugin'),
        'tag' => __('Tag Filter', 'post-filter-plugin'),
        'search' => __('Search Text Filter', 'post-filter-plugin'),
    );

    foreach ($filters as $key => $label) {
        $checked = isset($options['enable_filters'][$key]) ? $options['enable_filters'][$key] : 1; // Enabled by default
        echo '<label><input type="checkbox" name="pfp_settings[enable_filters][' . esc_attr($key) . ']" value="1" ' . checked(1, $checked, false) . '> ' . esc_html($label) . '</label><br>';
    }
}

// Display Mode callback
function pfp_display_mode_callback() {
    $options = get_option('pfp_settings');
    $display_mode = isset($options['display_mode']) ? $options['display_mode'] : 'horizontal';
    ?>
    <select name="pfp_settings[display_mode]">
        <option value="horizontal" <?php selected($display_mode, 'horizontal'); ?>><?php _e('Horizontal (Under Hero Section)', 'post-filter-plugin'); ?></option>
        <option value="sidebar" <?php selected($display_mode, 'sidebar'); ?>><?php _e('Sidebar (Use Shortcode)', 'post-filter-plugin'); ?></option>
    </select>
    <p class="description"><?php _e('Choose how to display the filters. If "Sidebar" is selected, use the shortcode <code>[pfp_filters]</code> in a sidebar widget.', 'post-filter-plugin'); ?></p>
    <?php
}

// Filter Order and Visibility callback
function pfp_filter_order_callback() {
    $options = get_option('pfp_settings');
    $filter_settings = isset($options['filter_settings']) ? $options['filter_settings'] : array(
        'category' => array('order' => 1, 'visible' => 1),
        'date' => array('order' => 2, 'visible' => 1),
        'quick_date' => array('order' => 3, 'visible' => 1),
        'author' => array('order' => 4, 'visible' => 1),
        'tag' => array('order' => 5, 'visible' => 1),
        'search' => array('order' => 6, 'visible' => 1),
    );

    $available_filters = array(
        'category' => __('Category Filter', 'post-filter-plugin'),
        'date' => __('Date Filtering (Month & Year/Week)', 'post-filter-plugin'),
        'quick_date' => __('Quick Date Filters', 'post-filter-plugin'),
        'author' => __('Author Filter', 'post-filter-plugin'),
        'tag' => __('Tag Filter', 'post-filter-plugin'),
        'search' => __('Search Text Filter', 'post-filter-plugin'),
    );

    // Sort filters by order for display
    uasort($filter_settings, function($a, $b) {
        return $a['order'] - $b['order'];
    });

    echo '<p>' . __('Drag and drop to reorder filters. Uncheck to hide a filter.', 'post-filter-plugin') . '</p>';
    echo '<ul id="pfp-filter-order" style="list-style: none; padding: 0;">';
    $order_index = 1;
    foreach ($filter_settings as $filter => $settings) {
        if (!isset($available_filters[$filter])) continue;
        // Fix: Use the saved visible value directly, default to 0 if not set
        $visible = isset($settings['visible']) ? (int) $settings['visible'] : 0;
        echo '<li style="padding: 5px; background: #f1f1f1; margin-bottom: 5px; cursor: move;">';
        echo '<input type="checkbox" name="pfp_settings[filter_settings][' . esc_attr($filter) . '][visible]" value="1" ' . checked(1, $visible, false) . '> ';
        echo esc_html($available_filters[$filter]);
        echo '<input type="hidden" name="pfp_settings[filter_settings][' . esc_attr($filter) . '][order]" value="' . esc_attr($order_index) . '">';
        echo '</li>';
        $order_index++;
    }
    echo '</ul>';

    // Add jQuery UI Sortable for drag-and-drop
    wp_enqueue_script('jquery-ui-sortable');
    ?>
    <script>
        jQuery(document).ready(function($) {
            $('#pfp-filter-order').sortable({
                update: function(event, ui) {
                    // Update hidden order inputs when reordered
                    $('#pfp-filter-order li').each(function(index) {
                        $(this).find('input[name$="[order]"]').val(index + 1);
                    });
                }
            });
        });
    </script>
    <?php
}

// Sanitize settings
function pfp_sanitize_settings($input) {
    // Debug: Log the raw input
    error_log('PFP Raw Input: ' . print_r($input, true));

    $sanitized = array();
    $available_filters = array('category', 'date', 'quick_date', 'author', 'tag', 'search');

    // Sanitize enable_filters
    $sanitized['enable_filters'] = array();
    foreach ($available_filters as $filter) {
        $sanitized['enable_filters'][$filter] = (isset($input['enable_filters'][$filter]) && $input['enable_filters'][$filter] == 1) ? 1 : 0;
    }

    // Sanitize display_mode
    $sanitized['display_mode'] = isset($input['display_mode']) && in_array($input['display_mode'], array('horizontal', 'sidebar')) ? $input['display_mode'] : 'horizontal';

    // Sanitize filter_settings (order and visibility)
    $sanitized['filter_settings'] = array();
    foreach ($available_filters as $filter) {
        // Ensure the filter exists in the input, otherwise use defaults
        $order = isset($input['filter_settings'][$filter]['order']) ? intval($input['filter_settings'][$filter]['order']) : 999;
        // Fix: Explicitly set visible to 0 if not set in input
        $visible = isset($input['filter_settings'][$filter]['visible']) && $input['filter_settings'][$filter]['visible'] == 1 ? 1 : 0;
        $sanitized['filter_settings'][$filter] = array(
            'order' => $order,
            'visible' => $visible,
        );
    }

    // Debug: Log the sanitized settings
    error_log('PFP Sanitized Settings: ' . print_r($sanitized, true));

    return $sanitized;
}

// Settings page callback
function pfp_settings_page_callback() {
    ?>
    <div class="wrap">
        <h1><?php _e('Post Filter Plugin Settings', 'post-filter-plugin'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('pfp_settings_group');
            do_settings_sections('pfp-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}
?>