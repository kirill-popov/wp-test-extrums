<?php

namespace ExtrumsTest\Classes;

use Exception;

if (!defined('ABSPATH')) {
	die();
}

class ExtrumsTestPlugin {
    private $DIR_PATH;
    private $FILE_PATH;
    private $DIR_URL;
    private $actions = [];
    private $filters = [];
    private $styles = [];
    private $scripts = [];

    public function __construct() {
        $this->DIR_PATH = DIR_PATH;
        $this->FILE_PATH = plugin_dir_path(__FILE__);
        $this->DIR_URL = DIR_URL;
        $this->setup_actions();

        $this->init_styles();
        $this->init_scripts();
    }

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
            'data' => [],
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
                    $wp_post = get_post($post->ID);
                    $wp_post->meta_title = $post->meta_title;
                    $wp_post->meta_desc = $post->meta_desc;
                    $response['data'][] = PostResponce::make($wp_post);
                }

                ob_start();
                require_once $this->DIR_PATH . '/partials/replace_form.php';
                $response['replace_form'] = ob_get_clean();
            }

        } catch(Exception $e) {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
        }

        wp_send_json($response);
    }

    private function get_posts_by_keyword($string='', $field='') {
        $results = [];
        if (!empty($string)) {
            global $wpdb;

            switch ($field) {
                case '':
                    $where = '(p.post_title REGEXP %s OR p.post_content REGEXP %s OR pm_title.meta_value REGEXP %s OR pm_desc.meta_value REGEXP %s)';
                    $values = [
                        '\\b' . $string . '\\b', // full word match
                        '\\b' . $string . '\\b', // full word match
                        '\\b' . $string . '\\b', // full word match
                        '\\b' . $string . '\\b', // full word match
                    ];
                    break;

                case 'title':
                    $where = 'p.post_title REGEXP %s';
                    $values = [
                        '\\b' . $string . '\\b', // full word match
                    ];
                    break;

                case 'content':
                    $where = 'p.post_content REGEXP %s';
                    $values = [
                        '\\b' . $string . '\\b', // full word match
                    ];
                    break;

                case 'meta_title':
                    $where = 'pm_title.meta_value REGEXP %s';
                    $values = [
                        '\\b' . $string . '\\b', // full word match
                    ];
                    break;

                case 'meta_desc':
                    $where = 'pm_desc.meta_value REGEXP %s';
                    $values = [
                        '\\b' . $string . '\\b', // full word match
                    ];
                    break;

                default:
                    throw new Exception("Wrong field value.");
            }

            $query = "
                SELECT p.*, pm_title.meta_value as meta_title, pm_desc.meta_value as meta_desc
                FROM $wpdb->posts p
                LEFT JOIN $wpdb->postmeta pm_title on pm_title.post_id = p.ID AND pm_title.meta_key='_yoast_wpseo_title'
                LEFT JOIN $wpdb->postmeta pm_desc on pm_desc.post_id = p.ID AND pm_desc.meta_key='_yoast_wpseo_metadesc'
                WHERE
                    p.post_type='post' AND p.post_status='publish'
                    AND " . $where;

            $prepared_query = $wpdb->prepare($query, $values);
            $results = $wpdb->get_results(
                $prepared_query
            );
        }
        return $results;
    }

    private function replace_form_submit() {
        $response = [
            'success' => true,
            'message' => '',
            'data' => [],
        ];

        try {
            check_admin_referer('replace_form_submit_action', '_extrums_replace_nonce');

            if (empty($_POST['_extrums_replace_nonce'])
            || !wp_verify_nonce($_POST['_extrums_replace_nonce'], 'replace_form_submit_action')) {
                throw new Exception("Wrong nonce.");
            }

            if (empty($_POST['field'])) {
                throw new Exception("Empty search field.");
            }

            if (empty($_POST['find'])) {
                throw new Exception("Empty search string.");
            }

            $posts = $this->get_posts_by_keyword($_POST['find'], $_POST['field']);
            $update_post = false;
            $update_post_meta = false;

            switch ($_POST['field']) {
                case 'title':
                    $field = 'post_title';
                    $update_post = true;
                    break;

                case 'content':
                    $field = 'post_content';
                    $update_post = true;
                    break;

                case 'meta_title':
                    $field = '_yoast_wpseo_title';
                    $update_post_meta = true;
                    break;

                case 'meta_desc':
                    $field = '_yoast_wpseo_metadesc';
                    $update_post_meta = true;
                    break;

                default:
                    throw new Exception("Wrong field value.");
            }

            if (!empty($posts)) {
                foreach ($posts as $post) {
                    if ($update_post) {
                        $upd_post = [
                            'ID' => $post->ID,
                            $field => preg_replace('/\b' . $_POST['find'] . '\b/', $_POST['replace'], $post->$field),
                        ];
                        wp_update_post($upd_post);

                    } else if ($update_post_meta) {
                        $post_meta_field = $_POST['field'];
                        $value = preg_replace('/\b' . $_POST['find'] . '\b/', $_POST['replace'], $post->$post_meta_field);

                        update_post_meta($post->ID, $field, $value);
                    }
                }
            }

            $posts = $this->get_posts_by_keyword($_POST['replace'], $_POST['field']);
            if (!empty($posts)) {
                foreach ($posts as $post) {
                    $wp_post = get_post($post->ID);
                    $wp_post->meta_title = $post->meta_title;
                    $wp_post->meta_desc = $post->meta_desc;
                    $response['data'][] = PostResponce::make($wp_post);
                }
            }

        } catch(Exception $e) {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
        }

        wp_send_json($response);
    }
}