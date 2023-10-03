<?php

namespace Extrums;

use Exception;

class ExtrumsTestPlugin {
    private $DIR_PATH;
    private $FILE_PATH;
    private $DIR_URL;
    private $actions = [];
    private $filters = [];
    private $styles = [];
    private $scripts = [];

    public function __construct() {
        $this->DIR_PATH = plugin_dir_path(__DIR__);
        $this->FILE_PATH = plugin_dir_path(__FILE__);
        $this->DIR_URL = plugin_dir_url(__DIR__);
        $this->setup_actions();

        // $this->add_filter('wpseo_metadesc', [$this,'prefix_filter_description_example']);

        $this->init_styles();
        $this->init_scripts();
    }

    // function prefix_filter_description_example( $description ) {
    //     error_log('1111');
    //     if ( is_single( 6 ) ) {
    //         error_log('222');
    //       $description = 'My custom custom meta description';
    //     }
    //     return $description;
    //   }

    private function setup_actions() {
        register_activation_hook($this->FILE_PATH, array($this, 'activate'));
        register_deactivation_hook($this->FILE_PATH, array($this, 'deactivate'));

        $this->add_menu_pages();
        $this->add_styles();
        $this->add_scripts();

        $this->add_ajax_handler('search_form_submit', function() {
            $this->search_form_submit();
        });
        $this->add_ajax_handler('replace_form_submit', function() {
            $this->replace_form_submit();
        });
    }

    public static function activate() {
        //Activation code in here
    }

    public static function deactivate() {
        //Deactivation code in here
    }

    private function add_menu_pages() {
        $this->add_action('admin_menu', [$this, 'extrums_options_page']);
    }

    private function add_styles() {
        $this->add_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css');
    }

    private function add_scripts() {
        $this->add_script('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js', array('jquery'));
        $this->add_script('extrums_admin_script', $this->DIR_URL . 'js/admin_script.js', array('jquery', 'bootstrap'));
    }

    public function extrums_options_page() {
        add_menu_page(
            $this->get_page_title(),
            $this->get_menu_title(),
            'manage_options',
            'extrums-test-plugin',
            [$this, 'extrums_test_plugin_page_content'],
            '',
            20
        );
    }

    private function get_page_title() {
        return 'Extrums Test Plugin';
    }

    private function get_menu_title() {
        return 'Extrums Test Plugin';
    }

    public function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
        $this->actions = $this->add($this->actions, $hook, $callback, $priority, $accepted_args);
    }

    public function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {
        $this->filters = $this->add($this->filters, $hook, $callback, $priority, $accepted_args);
    }

    private function add($hooks, $hook, $callback, $priority, $accepted_args) {
        $hooks[] = array(
            'hook'          => $hook,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args
        );

        return $hooks;
    }

    public function run() {
        foreach ($this->filters as $hook) {
            add_filter($hook['hook'], $hook['callback'], $hook['priority'], $hook['accepted_args']);
        }

        foreach ($this->actions as $hook) {
            add_action($hook['hook'], $hook['callback'], $hook['priority'], $hook['accepted_args']);
        }
    }

    private function init_styles(): void
    {
        $this->add_action('admin_enqueue_scripts', [$this, 'load_styles']);
    }

    public function load_styles(): void
    {
        foreach ($this->styles as $style) {
            wp_enqueue_style($style['name'], $style['file'], $style['deps'], $style['ver'], $style['media']);
        }
    }

    public function add_style(string $name, string $file, array $deps=[], string|bool|null $ver = false, string $media = 'all'): ExtrumsTestPlugin
    {
        $this->styles[] = [
            'name'  => $name,
            'file'  => $file,
            'deps'  => $deps,
            'ver'   => $ver,
            'media' => $media
        ];

        return $this;
    }


    private function init_scripts(): void
    {
        $this->add_action('admin_enqueue_scripts', [$this, 'load_scripts']);
    }

    public function load_scripts(): void
    {
        foreach ($this->scripts as $script) {
            wp_enqueue_script($script['name'], $script['file'], $script['deps'], $script['ver'], $script['args']);
        }
    }

    public function add_script(string $name, string $file, array $deps=[], string|bool|null $ver = false, array $args=[]): ExtrumsTestPlugin
    {
        $this->scripts[] = [
            'name'  => $name,
            'file'  => $file,
            'deps'  => $deps,
            'ver'   => $ver,
            'args'  => $args
        ];

        return $this;
    }

    private function add_ajax_handler(
        string $name,
        callable $callable,
        bool $authenticated_only=true,
    ): ExtrumsTestPlugin
    {
        $name = sanitize_title(trim($name));
        if (!empty($name)) {
            $this->add_action('wp_ajax_' . $name, $callable);
            if (!$authenticated_only) {
                $this->add_action('wp_ajax_nopriv_' . $name, $callable);
            }
        }

        return $this;
    }

    public function extrums_test_plugin_page_content() {
        $args = [
            'title' => $this->get_page_title()
        ];
        require_once $this->DIR_PATH . '/partials/search_form.php';
    }

    private function search_form_submit() {
        $response = [
            'success' => true,
            'message' => '',
            'data' => []
        ];

        try {
            check_admin_referer('search_form_submit_action', '_extrums_search_nonce');

            if (empty($_POST['_extrums_search_nonce'])
            || !wp_verify_nonce($_POST['_extrums_search_nonce'], 'search_form_submit_action')) {
                throw new Exception("Wrong nonce.");
            }

            if (empty($_POST['search_string'])) {
                throw new Exception("Empty search string.");
            }

            $posts = $this->get_posts_by_keyword($_POST['search_string']);
            if (!empty($posts)) {
                foreach ($posts as $post) {
                    $response['data'][] = [
                        'id' => $post->ID,
                        'title' => $post->post_title,
                        'content' => $post->post_content,
                    ];
                }
            }

            // error_log(print_r($posts, true));
        } catch(Exception $e) {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
        }

        wp_send_json($response);
    }

    private function get_posts_by_keyword($string='') {
        $results = [];
        if (!empty($string)) {
            global $wpdb;
            $query = "
                SELECT p.* FROM $wpdb->posts p
                LEFT JOIN $wpdb->postmeta pm on pm.post_id = p.ID
                WHERE
                    p.post_type='post' AND p.post_status='publish'
                    AND (
                        p.post_title LIKE %s OR p.post_title LIKE %s OR p.post_title LIKE %s
                        OR p.post_content LIKE %s OR p.post_content LIKE %s OR p.post_content LIKE %s

                    )
            ";
            // OR (pm.meta_key = '_yoast_wpseo_metadesc' AND pm.meta_value like %s)
            $string = $wpdb->esc_like(trim($string));
            $prepared_query = $wpdb->prepare($query, [
                $string . ' %',
                '% ' . $string . ' %',
                '% ' . $string,
                $string . ' %',
                '% ' . $string . ' %',
                '% ' . $string,
            ]);
            // error_log($prepared_query);
            $results = $wpdb->get_results(
               $prepared_query
            );
        }
        return $results;
    }

    private function replace_form_submit() {
        error_log(print_r($_POST, true));
    }
}