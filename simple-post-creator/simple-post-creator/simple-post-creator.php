<?php
/*
 * Plugin Name: Simple Post Creator
 * Description: A user-friendly interface to create WordPress posts, designed for Blogspot users transitioning to WordPress.
 * Version: 1.2
 * Author: Miaoulis N
 * License: GPL2
 * Text Domain: simple-post-creator
 * Domain Path: /languages
 */

class Simple_Post_Creator {
    public function __construct() {
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('admin_menu', array($this, 'add_menu_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_post_save_simple_post', array($this, 'save_post'));
    }

    public function load_textdomain() {
        load_plugin_textdomain('simple-post-creator', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function add_menu_page() {
        add_menu_page(
            __('Simple Post Creator', 'simple-post-creator'),
            __('Simple Post Creator', 'simple-post-creator'),
            'edit_posts',
            'simple-post-creator',
            array($this, 'render_page'),
            'dashicons-edit',
            6
        );
        add_submenu_page(
            'simple-post-creator',
            __('Add New Post', 'simple-post-creator'),
            __('Add New Post', 'simple-post-creator'),
            'edit_posts',
            'simple-post-creator',
            array($this, 'render_page')
        );
        add_submenu_page(
            'simple-post-creator',
            __('Edit Posts', 'simple-post-creator'),
            __('Edit Posts', 'simple-post-creator'),
            'edit_posts',
            'simple-post-creator-edit',
            array($this, 'render_edit_page')
        );
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'toplevel_page_simple-post-creator' && $hook !== 'simple-post-creator_page_simple-post-creator-edit') {
            return;
        }
        wp_enqueue_media();
        wp_enqueue_script('simple-post-script', plugin_dir_url(__FILE__) . 'simple-post.js', array('jquery'), '1.2', true);
        wp_enqueue_style('simple-post-style', plugin_dir_url(__FILE__) . 'simple-post.css');
    }

    public function render_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Create a New Post', 'simple-post-creator'); ?></h1>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data">
                <?php wp_nonce_field('simple_post_creator_nonce', 'simple_post_nonce'); ?>
                <input type="hidden" name="action" value="save_simple_post">

                <label for="post_title"><?php _e('Title', 'simple-post-creator'); ?></label>
                <input type="text" name="post_title" id="post_title" class="widefat" required>

                <label for="post_content"><?php _e('Content', 'simple-post-creator'); ?></label>
                <?php
                wp_editor('', 'post_content', array(
                    'textarea_name' => 'post_content',
                    'media_buttons' => true,
                    'teeny' => false,
                    'quicktags' => true,
                ));
                ?>

                <label for="post_category"><?php _e('Category', 'simple-post-creator'); ?></label>
                <?php
                wp_dropdown_categories(array(
                    'name' => 'post_category',
                    'id' => 'post_category',
                    'class' => 'widefat',
                    'hide_empty' => 0,
                    'selected' => get_option('default_category'),
                ));
                ?>

                <label for="post_tag"><?php _e('Tag', 'simple-post-creator'); ?></label>
                <input type="text" name="post_tag" id="post_tag" class="widefat" placeholder="<?php _e('Enter one tag', 'simple-post-creator'); ?>">

                <label for="featured_image"><?php _e('Featured Image', 'simple-post-creator'); ?></label>
                <input type="button" id="upload_image_button" class="button" value="<?php _e('Upload Image', 'simple-post-creator'); ?>">
                <input type="hidden" name="featured_image" id="featured_image" value="">
                <div id="image_preview"></div>

                <input type="submit" class="button button-primary" value="<?php _e('Publish Post', 'simple-post-creator'); ?>">
            </form>
        </div>
        <?php
    }

    public function render_edit_page() {
        if (isset($_GET['post_id']) && is_numeric($_GET['post_id'])) {
            $this->render_edit_form($_GET['post_id']);
        } else {
            $this->render_posts_list();
        }
    }

    private function render_posts_list() {
        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

        $args = array(
            'posts_per_page' => 20,
            'paged' => $paged,
            'post_status' => array('publish', 'draft'),
            'post_type' => 'post',
        );

        if (!empty($search)) {
            $args['s'] = $search;
        }

        $query = new WP_Query($args);
        ?>
        <div class="wrap">
            <h1><?php _e('Edit Posts', 'simple-post-creator'); ?></h1>
            
            <!-- Search Form -->
            <form method="get" action="">
                <input type="hidden" name="page" value="simple-post-creator-edit">
                <p class="search-box">
                    <label class="screen-reader-text" for="post-search-input"><?php _e('Search Posts', 'simple-post-creator'); ?>:</label>
                    <input type="search" id="post-search-input" name="s" value="<?php echo esc_attr($search); ?>">
                    <input type="submit" id="search-submit" class="button" value="<?php _e('Search Posts', 'simple-post-creator'); ?>">
                </p>
            </form>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Title', 'simple-post-creator'); ?></th>
                        <th><?php _e('Category', 'simple-post-creator'); ?></th>
                        <th><?php _e('Tag', 'simple-post-creator'); ?></th>
                        <th><?php _e('Actions', 'simple-post-creator'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($query->have_posts()) : while ($query->have_posts()) : $query->the_post(); ?>
                        <tr>
                            <td><?php the_title(); ?></td>
                            <td><?php echo get_the_category_list(', '); ?></td>
                            <td><?php echo get_the_tag_list('', ', '); ?></td>
                            <td><a href="?page=simple-post-creator-edit&post_id=<?php the_ID(); ?>" class="button"><?php _e('Edit', 'simple-post-creator'); ?></a></td>
                        </tr>
                    <?php endwhile; else : ?>
                        <tr>
                            <td colspan="4"><?php _e('No posts found.', 'simple-post-creator'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php
                    $total_pages = $query->max_num_pages;
                    $big = 999999999; // Arbitrary number for pagination links
                    echo paginate_links(array(
                        'base' => str_replace($big, '%#%', esc_url(add_query_arg('paged', $big))),
                        'format' => '?paged=%#%',
                        'current' => $paged,
                        'total' => $total_pages,
                        'prev_text' => __('« Previous', 'simple-post-creator'),
                        'next_text' => __('Next »', 'simple-post-creator'),
                    ));
                    wp_reset_postdata();
                    ?>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_edit_form($post_id) {
        $post = get_post($post_id);
        $category = wp_get_post_categories($post_id, array('fields' => 'ids'))[0] ?? get_option('default_category');
        $tags = wp_get_post_tags($post_id, array('fields' => 'names'));
        $featured_image = get_post_thumbnail_id($post_id);
        ?>
        <div class="wrap">
            <h1><?php _e('Edit Post', 'simple-post-creator'); ?></h1>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data">
                <?php wp_nonce_field('simple_post_creator_nonce', 'simple_post_nonce'); ?>
                <input type="hidden" name="action" value="save_simple_post">
                <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">

                <label for="post_title"><?php _e('Title', 'simple-post-creator'); ?></label>
                <input type="text" name="post_title" id="post_title" class="widefat" value="<?php echo esc_attr($post->post_title); ?>" required>

                <label for="post_content"><?php _e('Content', 'simple-post-creator'); ?></label>
                <?php wp_editor($post->post_content, 'post_content', array('textarea_name' => 'post_content', 'media_buttons' => true)); ?>

                <label for="post_category"><?php _e('Category', 'simple-post-creator'); ?></label>
                <?php wp_dropdown_categories(array('name' => 'post_category', 'id' => 'post_category', 'class' => 'widefat', 'hide_empty' => 0, 'selected' => $category)); ?>

                <label for="post_tag"><?php _e('Tag', 'simple-post-creator'); ?></label>
                <input type="text" name="post_tag" id="post_tag" class="widefat" value="<?php echo esc_attr($tags[0] ?? ''); ?>">

                <label for="featured_image"><?php _e('Featured Image', 'simple-post-creator'); ?></label>
                <input type="button" id="upload_image_button" class="button" value="<?php _e('Upload Image', 'simple-post-creator'); ?>">
                <input type="hidden" name="featured_image" id="featured_image" value="<?php echo $featured_image; ?>">
                <div id="image_preview">
                    <?php if ($featured_image) : ?>
                        <img src="<?php echo wp_get_attachment_url($featured_image); ?>" style="max-width:200px; margin-top:10px;">
                    <?php endif; ?>
                </div>

                <input type="submit" class="button button-primary" value="<?php _e('Update Post', 'simple-post-creator'); ?>">
            </form>
        </div>
        <?php
    }

    public function save_post() {
        if (!isset($_POST['simple_post_nonce']) || !wp_verify_nonce($_POST['simple_post_nonce'], 'simple_post_creator_nonce')) {
            wp_die('Security check failed');
        }
        if (!current_user_can('edit_posts')) {
            wp_die('You do not have permission to create posts');
        }

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $post_title = sanitize_text_field($_POST['post_title']);
        $post_content = wp_kses_post($_POST['post_content']);
        $post_category = intval($_POST['post_category']);
        $post_tag = sanitize_text_field($_POST['post_tag']);
        $featured_image = intval($_POST['featured_image']);

        $post_data = array(
            'post_title'   => $post_title,
            'post_content' => $post_content,
            'post_status'  => 'publish',
            'post_type'    => 'post',
            'post_category' => array($post_category),
        );
        if ($post_id) {
            $post_data['ID'] = $post_id;
            wp_update_post($post_data);
        } else {
            $post_id = wp_insert_post($post_data);
        }

        if ($post_id && !empty($post_tag)) {
            wp_set_post_tags($post_id, $post_tag, true);
        }
        if ($post_id && $featured_image) {
            set_post_thumbnail($post_id, $featured_image);
        }

        wp_redirect(admin_url('edit.php'));
        exit;
    }
}

new Simple_Post_Creator();

add_action('admin_footer', function() {
    if (get_current_screen()->id !== 'toplevel_page_simple-post-creator' && get_current_screen()->id !== 'simple-post-creator_page_simple-post-creator-edit') {
        return;
    }
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            var mediaUploader;
            $('#upload_image_button').click(function(e) {
                e.preventDefault();
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }
                mediaUploader = wp.media({
                    title: '<?php _e('Choose Featured Image', 'simple-post-creator'); ?>',
                    button: { text: '<?php _e('Set Featured Image', 'simple-post-creator'); ?>' },
                    multiple: false
                });
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#featured_image').val(attachment.id);
                    $('#image_preview').html('<img src="' + attachment.url + '" style="max-width:200px; margin-top:10px;">');
                });
                mediaUploader.open();
            });
        });
    </script>
    <?php
});

add_action('admin_head', function() {
    if (get_current_screen()->id !== 'toplevel_page_simple-post-creator' && get_current_screen()->id !== 'simple-post-creator_page_simple-post-creator-edit') {
        return;
    }
    ?>
    <style type="text/css">
        .wrap { max-width: 900px; margin: 20px auto; background: #fff; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 5px; }
        h1 { color: #23282d; font-size: 24px; margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        .widefat { width: 100%; padding: 10px; margin-bottom: 20px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .button { padding: 8px 16px; font-size: 14px; }
        .button-primary { background: #0073aa; border-color: #006799; }
        .button-primary:hover { background: #006799; }
        #image_preview { margin-bottom: 20px; }
        #image_preview img { border: 1px solid #ddd; padding: 5px; background: #f7f7f7; }
        .wp-list-table th { background: #f9f9f9; padding: 12px; }
        .wp-list-table td { padding: 12px; vertical-align: middle; }
        .search-box { float: right; margin-bottom: 20px; }
        .tablenav.bottom { margin-top: 20px; }
    </style>
    <?php
});