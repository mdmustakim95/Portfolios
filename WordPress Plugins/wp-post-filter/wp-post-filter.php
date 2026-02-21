<?php
/*
Plugin Name: WP Post Filter
Plugin URI: https://wa.me/917255832335?text=Hello%2C%20I%20need%20WordPress%20plugin%20development%20support.%20Please%20contact%20me%20to%20discuss%20the%20requirements.%20Thank%20you
Description: This plugin is created by Md Mustakim to provide an AJAX-powered post filter with search and pagination. It allows dynamic filtering, exclusion of specific posts, and supports hidden filter parameters. Use the following shortcodes to display the post filter on any page: [wp_post_filter], 
[wp_post_filter hidden_author="author" hidden_focus_area="" hidden_publication_type="spotlights"]
Version: 1.0.0
Author: Md Mustakim
Author URI: https://mustakimportfolio.wordpress.com
*/

if (!defined('ABSPATH')) {
    exit;
}

class WP_Post_Filter_Plugin {

    public function __construct() {
        // Internationalization
        add_action('plugins_loaded', array($this, 'load_textdomain'));

        // Register optional taxonomy (manual_type) — harmless if exists
        add_action('init', array($this, 'register_manual_type_taxonomy'));

        // Assets & shortcode
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_shortcode('wp_post_filter', array($this, 'render_shortcode'));

        // AJAX handlers
        add_action('wp_ajax_wppf_filter_posts', array($this, 'ajax_filter_posts'));
        add_action('wp_ajax_nopriv_wppf_filter_posts', array($this, 'ajax_filter_posts'));
    }

    public function load_textdomain() {
        load_plugin_textdomain('wppf', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function register_manual_type_taxonomy() {
        $labels = array('name' => __('Manual Types', 'wppf'), 'singular_name' => __('Manual Type', 'wppf'));
        $args = array(
            'hierarchical' => true,
            'labels' => $labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'rewrite' => array('slug' => 'manual-type'),
        );
        register_taxonomy('manual_type', array('post'), $args);
    }

    public function enqueue_assets() {
        // Register assets but only enqueue on shortcode render
        wp_register_style('wppf-style', plugin_dir_url(__FILE__) . 'assets/wppf-style.css', array(), '1.5.0');
        wp_register_script('wppf-script', plugin_dir_url(__FILE__) . 'assets/wppf-script.js', array('jquery'), '1.5.0', true);

        wp_localize_script('wppf-script', 'wppf_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('wppf_nonce'),
        ));
    }

    /**
     * Render the shortcode UI.
     *
     * Attributes:
     * - posts_per_page (int)
     * - hidden_author (string; login/display_name)
     * - hidden_author_id (int)
     * - hidden_focus_area (tag slug)
     * - hidden_publication_type (category slug)
     * - hide_filters (0|1) — hide the visible filter UI entirely
     */
    public function render_shortcode($atts) {
        $atts = shortcode_atts(array(
            'posts_per_page' => 8,
            'hidden_author' => '',
            'hidden_author_id' => '',
            'hidden_focus_area' => '',
            'hidden_publication_type' => '',
            'hide_filters' => 0,
        ), $atts, 'wp_post_filter');

        $ppp = intval($atts['posts_per_page']);
        $ppp = max(1, min(50, $ppp));

        // sanitize hidden values
        $hidden_author_raw = sanitize_text_field($atts['hidden_author']);
        $hidden_author_id = $atts['hidden_author_id'] !== '' ? intval($atts['hidden_author_id']) : 0;
        $hidden_focus_area = sanitize_text_field($atts['hidden_focus_area']);
        $hidden_publication_type = sanitize_text_field($atts['hidden_publication_type']);
        $hide_filters = intval($atts['hide_filters']) ? true : false;

        // resolve hidden author to ID if possible
        $hidden_author_resolved_id = $this->resolve_author_to_id($hidden_author_raw, $hidden_author_id);

        // prepare lists (no expensive caching here — optional improvement: transients)
        $tags = get_tags(array('hide_empty' => false));
        $cats = get_categories(array('hide_empty' => false));
        $authors = get_users(array('who' => 'authors', 'orderby' => 'display_name'));

        // Enqueue assets now that shortcode is present
        wp_enqueue_style('wppf-style');
        wp_enqueue_script('wppf-script');

        ob_start();
        ?>
        <div class="wppf-wrapper">
            <?php if (!$hide_filters): ?>
            <form id="wppf-filter-form"
                  class="wppf-filter-form"
                  data-posts-per-page="<?php echo esc_attr($ppp); ?>"
                  <?php if ($hidden_author_resolved_id) printf('data-hidden-author-id="%d" ', esc_attr($hidden_author_resolved_id)); ?>
                  <?php if (!empty($hidden_focus_area)) printf('data-hidden-focus-area="%s" ', esc_attr($hidden_focus_area)); ?>
                  <?php if (!empty($hidden_publication_type)) printf('data-hidden-publication-type="%s" ', esc_attr($hidden_publication_type)); ?>>

                <div class="wppf-filter-row">
                    <div class="wppf-filter-col">
                        <label class="wppf-label" for="wppf-focus-area"><?php esc_html_e('By Focus Area', 'wppf'); ?></label>
                        <div class="wppf-select-wrapper">
                            <select id="wppf-focus-area" name="focus_area" class="wppf-select" aria-label="<?php echo esc_attr__('By Focus Area','wppf'); ?>">
                                <option value=""><?php echo esc_html__('By Focus Area', 'wppf'); ?></option>
                                <?php foreach ($tags as $t): 
                                    if (!empty($hidden_focus_area) && $t->slug === $hidden_focus_area) continue;
                                ?>
                                    <option value="<?php echo esc_attr($t->slug); ?>"><?php echo esc_html($t->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="wppf-filter-col">
                        <label class="wppf-label" for="wppf-publication-type"><?php esc_html_e('Publications', 'wppf'); ?></label>
                        <div class="wppf-select-wrapper">
                            <select id="wppf-publication-type" name="publication_type" class="wppf-select" aria-label="<?php echo esc_attr__('Publications','wppf'); ?>">
                                <option value=""><?php echo esc_html__('By Publication Type', 'wppf'); ?></option>
                                <?php foreach ($cats as $c):
                                    if (!empty($hidden_publication_type) && $c->slug === $hidden_publication_type) continue;
                                ?>
                                    <option value="<?php echo esc_attr($c->slug); ?>"><?php echo esc_html($c->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="wppf-filter-col">
                        <label class="wppf-label" for="wppf-post-author"><?php esc_html_e('By Author', 'wppf'); ?></label>
                        <div class="wppf-select-wrapper">
                            <select id="wppf-post-author" name="post_author" class="wppf-select" aria-label="<?php echo esc_attr__('By Author','wppf'); ?>">
                                <option value=""><?php echo esc_html__('By Author', 'wppf'); ?></option>
                                <?php foreach ($authors as $a):
                                    if (!empty($hidden_author_resolved_id) && intval($a->ID) === intval($hidden_author_resolved_id)) continue;
                                ?>
                                    <option value="<?php echo intval($a->ID); ?>"><?php echo esc_html($a->display_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="wppf-search-col">
                        <label class="wppf-label" for="wppf-search"><?php esc_html_e('Search', 'wppf'); ?></label>
                        <input type="search" id="wppf-search" name="s" class="wppf-search" placeholder="<?php echo esc_attr__('Search', 'wppf'); ?>" aria-label="<?php echo esc_attr__('Search','wppf'); ?>">
                    </div>
                </div>
            </form>
            <?php else: ?> 
                <!-- if filters hidden, still output the form with data attributes so JS/AJAX can read them -->
                <form id="wppf-filter-form"
                      class="wppf-filter-form"
                      data-posts-per-page="<?php echo esc_attr($ppp); ?>"
                      <?php if ($hidden_author_resolved_id) printf('data-hidden-author-id="%d" ', esc_attr($hidden_author_resolved_id)); ?>
                      <?php if (!empty($hidden_focus_area)) printf('data-hidden-focus-area="%s" ', esc_attr($hidden_focus_area)); ?>
                      <?php if (!empty($hidden_publication_type)) printf('data-hidden-publication-type="%s" ', esc_attr($hidden_publication_type)); ?>>
                </form>
            <?php endif; ?>

            <div id="wppf-results" class="wppf-results" aria-live="polite"></div>
            <div id="wppf-pagination" class="wppf-pagination" role="navigation" aria-label="<?php echo esc_attr__('Pagination','wppf'); ?>"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX handler — returns JSON with 'html', 'found', 'total_pages', 'paged', 'posts_per_page'
     */
    public function ajax_filter_posts() {
        check_ajax_referer('wppf_nonce', 'nonce');

        $posts_per_page = isset($_POST['posts_per_page']) ? intval($_POST['posts_per_page']) : 6;
        $posts_per_page = max(1, min(50, $posts_per_page));
        $paged = isset($_POST['paged']) ? max(1, intval($_POST['paged'])) : 1;

        $args = array(
            'post_type' => 'post',
            'posts_per_page' => $posts_per_page,
            'paged' => $paged,
            'post_status' => 'publish',
        );
        
        // visible author (from dropdown)
        if (!empty($_POST['post_author'])) {
            $args['author__in'] = array(intval($_POST['post_author']));
        }
        
        // excluded author (from shortcode)
        if (!empty($_POST['hidden_author_id'])) {
            $args['author__not_in'] = array(intval($_POST['hidden_author_id']));
        }
        
        /* -------------------------
         * TAX QUERY (IN + NOT IN)
         * ------------------------- */
        
        $tax_query = array('relation' => 'AND');
        
        // visible tag filter
        if (!empty($_POST['focus_area'])) {
            $tax_query[] = array(
                'taxonomy' => 'post_tag',
                'field'    => 'slug',
                'terms'    => sanitize_text_field($_POST['focus_area']),
                'operator' => 'IN',
            );
        }
        
        // visible category filter
        if (!empty($_POST['publication_type'])) {
            $tax_query[] = array(
                'taxonomy' => 'category',
                'field'    => 'slug',
                'terms'    => sanitize_text_field($_POST['publication_type']),
                'operator' => 'IN',
            );
        }
        
        // ❌ EXCLUDE tag from shortcode
        if (!empty($_POST['hidden_focus_area'])) {
            $tax_query[] = array(
                'taxonomy' => 'post_tag',
                'field'    => 'slug',
                'terms'    => sanitize_text_field($_POST['hidden_focus_area']),
                'operator' => 'NOT IN',
            );
        }
        
        // ❌ EXCLUDE category from shortcode
        if (!empty($_POST['hidden_publication_type'])) {
            $tax_query[] = array(
                'taxonomy' => 'category',
                'field'    => 'slug',
                'terms'    => sanitize_text_field($_POST['hidden_publication_type']),
                'operator' => 'NOT IN',
            );
        }
        
        // apply only if something exists
        if (count($tax_query) > 1) {
            $args['tax_query'] = $tax_query;
        }



        // search
        if (isset($_POST['s']) && $_POST['s'] !== '') {
            $args['s'] = sanitize_text_field($_POST['s']);
        }

        // visible filters (if user selected them)
        $tax_query = array('relation' => 'AND');

        // visible focus area (tag)
        if (!empty($_POST['focus_area'])) {
            $tax_query[] = array(
                'taxonomy' => 'post_tag',
                'field'    => 'slug',
                'terms'    => sanitize_text_field($_POST['focus_area']),
                'operator' => 'IN',
            );
        }
        
        // visible publication type (category)
        if (!empty($_POST['publication_type'])) {
            $tax_query[] = array(
                'taxonomy' => 'category',
                'field'    => 'slug',
                'terms'    => sanitize_text_field($_POST['publication_type']),
                'operator' => 'IN',
            );
        }
        
        // hidden EXCLUDED focus area (tag)
        if (!empty($_POST['hidden_focus_area'])) {
            $tax_query[] = array(
                'taxonomy' => 'post_tag',
                'field'    => 'slug',
                'terms'    => sanitize_text_field($_POST['hidden_focus_area']),
                'operator' => 'NOT IN',
            );
        }
        
        // hidden EXCLUDED publication type (category)
        if (!empty($_POST['hidden_publication_type'])) {
            $tax_query[] = array(
                'taxonomy' => 'category',
                'field'    => 'slug',
                'terms'    => sanitize_text_field($_POST['hidden_publication_type']),
                'operator' => 'NOT IN',
            );
        }
        
        // apply tax query ONLY if needed
        if (count($tax_query) > 1) {
            $args['tax_query'] = $tax_query;
        }

        
        // EXCLUDE posts based on shortcode hidden attributes
        
        // exclude author
        if (!empty($_POST['hidden_author_id'])) {
            $args['author__not_in'] = array(intval($_POST['hidden_author_id']));
        }
        
        // exclude focus area (tag)
        if (!empty($_POST['hidden_focus_area'])) {
            $tag = get_term_by('slug', sanitize_text_field($_POST['hidden_focus_area']), 'post_tag');
            if ($tag) {
                $args['tag__not_in'] = array(intval($tag->term_id));
            }
        }
        
        // exclude publication type (category)
        if (!empty($_POST['hidden_publication_type'])) {
            $cat = get_term_by('slug', sanitize_text_field($_POST['hidden_publication_type']), 'category');
            if ($cat) {
                $args['category__not_in'] = array(intval($cat->term_id));
            }
        }


        // allow modification
        $args = apply_filters('wppf_query_args', $args);

        $q = new WP_Query($args);

        ob_start();
        if ($q->have_posts()) {
            echo '<div class="wppf-grid">';
            while ($q->have_posts()) {
                $q->the_post();

                $thumb = has_post_thumbnail() ? get_the_post_thumbnail_url(get_the_ID(), 'large') : plugin_dir_url(__FILE__) . 'assets/placeholder.png';
                $title = get_the_title();
                $permalink = get_permalink();
                $date = get_the_date();
                $excerpt = wp_trim_words(get_the_excerpt() ? get_the_excerpt() : get_the_content(), 20, '...');

                ?>
                <article class="wppf-card" id="post-<?php the_ID(); ?>">
                    <div class="wppf-card-media">
                        <a href="<?php echo esc_url($permalink); ?>">
                            <img src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr($title); ?>">
                        </a>
                    </div>
                    <div class="wppf-card-body">
                        <h2 class="wppf-card-title"><a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($title); ?></a></h2>
                        <div class="wppf-card-date"><?php echo esc_html($date); ?></div>
                        <div class="wppf-card-excerpt"><?php echo esc_html($excerpt); ?></div>
                        <a class="wppf-readmore" href="<?php echo esc_url($permalink); ?>"><?php echo esc_html__('Read more...', 'wppf'); ?></a>
                    </div>
                </article>
                <?php
            }
            echo '</div>';

            $found = intval($q->found_posts);
            $total_pages = intval($q->max_num_pages);

            // include a small helper element (backwards compat) but we also return structured fields
            if ($found > $posts_per_page && $total_pages > 1) {
                printf('<div class="wppf-pages" data-total="%d" data-current="%d" data-found="%d"></div>', $total_pages, $paged, $found);
            }
        } else {
            echo '<div class="wppf-no-results">' . esc_html__('No posts found.', 'wppf') . '</div>';
            $found = 0;
            $total_pages = 0;
        }
        wp_reset_postdata();

        $html = ob_get_clean();

        wp_send_json_success(array(
            'html' => $html,
            'found' => isset($found) ? $found : 0,
            'total_pages' => isset($total_pages) ? $total_pages : 0,
            'paged' => $paged,
            'posts_per_page' => $posts_per_page,
        ));
    }

    /**
     * Resolve author string or ID to a user ID.
     * Accepts author login/display_name or numeric ID.
     */
    protected function resolve_author_to_id($author_raw, $author_id = 0) {
        $author_id = intval($author_id);
        if ($author_id > 0) {
            return $author_id;
        }
        $author_raw = trim($author_raw);
        if ($author_raw === '') {
            return 0;
        }

        // try common lookups
        $user = false;
        // by login (user_nicename)
        $user = get_user_by('login', $author_raw);
        if (!$user) $user = get_user_by('slug', $author_raw);
        if (!$user) $user = get_user_by('email', $author_raw);
        if (!$user) {
            // search display_name (may return multiple — take first)
            $users = get_users(array(
                'search' => $author_raw,
                'search_columns' => array('display_name'),
                'number' => 1,
            ));
            if (!empty($users)) $user = $users[0];
        }

        if ($user) return intval($user->ID);

        return 0;
    }

} // end class

new WP_Post_Filter_Plugin();
