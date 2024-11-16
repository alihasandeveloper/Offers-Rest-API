<?php


class custom_offer_post
{
    public $post_name;
    public $single_post_name;

    public function __construct($single_name)
    {
        $this->post_name = $single_name . 's';
        $this->single_post_name = $single_name;
        add_action('init', array($this, 'custom_offer'));
    }

    public function custom_offer()
    {
        register_post_type('offers', array(
            'labels' => array(
                'name' => $this->post_name,
                'singular_name' => $this->single_post_name,
                'add_new' => 'Add New',
                'add_new_item' => 'Add New ' . $this->single_post_name,
                'edit' => 'Edit',
                'edit_item' => 'Edit ' . $this->single_post_name,
                'new_item' => 'New ' . $this->single_post_name,
                'view' => 'View',
                'view_item' => 'View ' . $this->single_post_name,
                'not_found' => 'Sorry, No ' . $this->post_name . ' Found',
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor'),
            'menu_icon' => 'dashicons-smiley',
            'menu_position' => 21,
            'show_in_menu' => true,
        ));
    }
}

$offer_post = new custom_offer_post('Offer');


function custom_offer_api()
{
    register_rest_route('wl/v1', 'offers', array(
        'methods' => 'GET',
        'callback' => 'wl_offers',
    ));
}

function wl_offers()
{
    $args = array(
        'post_type' => 'offers',
        'post_status' => 'publish',
    );

    $query = new WP_Query($args);
    $data = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();

            $data[] = array(
                'id' => $post_id,
                'title' => get_the_title(),
                'content' => get_the_content(),
                'assign_plugin' => get_post_meta($post_id, 'assign_plugin', true),
            );
        }
    }

    wp_reset_postdata();
    return rest_ensure_response($data);
}

add_action('rest_api_init', 'custom_offer_api');

function check_offers_and_display_admin_notice()
{
    $hardCodeSlug = 'top-table-of-contents';
    $api_url = 'http://custom-post.test/wp-json/wl/v1/offers';
    $response = wp_remote_get($api_url);

    if (is_wp_error($response)) {
        return;
    }

    $body = wp_remote_retrieve_body($response);
    $offers = json_decode($body, true);

    foreach ($offers as $offer) {
        $post_id = $offer['id'];
        $assign_plugn = get_post_meta($post_id, 'assign_plugin', true);

        if ($assign_plugn === $hardCodeSlug) {
            $post_title = get_the_title($post_id);
            $post_content = get_post_field('post_content', $post_id);

            add_action('admin_notices', function () use ($post_title, $post_content) {
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p><strong>Title:</strong> ' . esc_html($post_title) . '</p>';
                echo '<p><strong>Content:</strong> ' . esc_html(wp_trim_words($post_content, 20)) . '</p>';
                echo '</div>';
            });
        }
    }
}

add_action('admin_init', 'check_offers_and_display_admin_notice');

