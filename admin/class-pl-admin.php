<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    PipeLine
 * @subpackage PipeLine/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    PipeLine
 * @subpackage PipeLine/admin
 * @author     Harsh Kurra
 */
class PipeLine_Admin
{

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/pl-constants.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/pl-admin-display.php';

        $this->plugin_name = $plugin_name;
        $this->version = $version;

    }

    /**
     * Setting validation
     * This will get called when user click on save button
     * @since    1.0.0
     * @param      object    $input   user input field's name:value map object
     */
    public function pipe_line_setting_validate($input, $forced_save = false)
    {
        error_log("called_setting_saved");
        error_log(print_r($input, true));

        $api_key = $input[pipe_line_constants\pl_options_api_key];

        $options = get_option(PIPE_LINE_OPTION_NAME);
        $old_api_key = '';
        $old_location_id = '';
        $old_text_widget_error = "0";

        if (isset($options[pipe_line_constants\pl_options_api_key])) {
            $old_api_key = esc_attr($options[pipe_line_constants\pl_options_api_key]);
        }

        if (isset($options[pipe_line_constants\pl_options_location_id])) {
            $old_location_id = esc_attr($options[pipe_line_constants\pl_options_location_id]);
        }

        if (isset($options[pipe_line_constants\pl_options_text_widget_error])) {
            $old_text_widget_error = esc_attr($options[pipe_line_constants\pl_options_text_widget_error]);
        }

        if (!isset($api_key) || (isset($api_key) === true && $api_key === '')) {
            $input[pipe_line_constants\pl_options_text_widget_error] = "1";
            return $input;
        }
        $input[pipe_line_constants\pl_options_text_widget_warning_text] = "";
        $api_key = trim($api_key);

        $enabled_text_widget = 0;
        if (isset($input[pipe_line_constants\pl_options_enable_text_widget])) {
            $enabled_text_widget = esc_attr($input[pipe_line_constants\pl_options_enable_text_widget]);
        }

        if (defined('WP_DEBUG') && true === WP_DEBUG) {
            error_log(print_r($input, true));
        }

        if ($enabled_text_widget == 1 || $forced_save) {
            if (defined('WP_DEBUG') && true === WP_DEBUG) {
                error_log('call API here ');
            }
            //call API here
            $args = array(
                'timeout' => 60,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key,
                ),
            );
            $response = wp_remote_get(PIPE_LINE_BASE_URL . 'v1/locations/me?includeWhiteLabelUrl=true', $args);
            $http_code = wp_remote_retrieve_response_code($response);
            if ($http_code === 200) {
                $body = wp_remote_retrieve_body($response);
                $obj = json_decode($body, false);
                $input[pipe_line_constants\pl_options_location_id] = $obj->id;
                $input[pipe_line_constants\pl_options_text_widget_error] = 0;
                if (isset($obj->settings) && isset($obj->settings->textwidget) && count((array) $obj->settings->textwidget) > 0) {
                    $input[pipe_line_constants\pl_options_text_widget_settings] = json_encode($obj->settings->textwidget, JSON_UNESCAPED_UNICODE);
                } else {
                    error_log('chat widget not configured');
                    $input[pipe_line_constants\pl_options_text_widget_warning_text] = 'Please configure chat widget first!';

                    if (isset($options[pipe_line_constants\pl_options_text_widget_heading])) {
                        $input[pipe_line_constants\pl_options_text_widget_heading] = esc_attr($options[pipe_line_constants\pl_options_text_widget_heading]);
                    }
                    if (isset($options[pipe_line_constants\pl_options_text_widget_sub_heading])) {
                        $input[pipe_line_constants\pl_options_text_widget_sub_heading] = esc_attr($options[pipe_line_constants\pl_options_text_widget_sub_heading]);
                    }
                    if (isset($options[pipe_line_constants\pl_options_text_widget_use_email_filed])) {
                        $input[pipe_line_constants\pl_options_text_widget_use_email_filed] = esc_attr($options[pipe_line_constants\pl_options_text_widget_use_email_filed]);
                    }
                }
                if (isset($obj->whiteLabelUrl)) {
                    $input[pipe_line_constants\pl_options_location_white_label_url] = $obj->whiteLabelUrl;
                } else {
                    $input[pipe_line_constants\pl_options_location_white_label_url] = "";
                }
                if (defined('WP_DEBUG') && true === WP_DEBUG) {
                    error_log(print_r($body, true));
                    error_log(print_r($obj, true));
                }
            } else {
                error_log('Please provide correct API key, error details below');
                error_log(print_r($response, true));
                $input[pipe_line_constants\pl_options_text_widget_error] = "1";
                $input[pipe_line_constants\pl_options_text_widget_error_details] = print_r($response, true);
            }
        } else {
            $input[pipe_line_constants\pl_options_location_id] = $old_location_id;
        }

        return $input;
    }

    /**
     * Filter Post information
     * This will get on each post to get the fields required at front-end
     * @since    1.0.0
     * @param      string    $template_id   postID
     * @param      string    $location_id   Location ID
     */
    public function get_item($template_id, $location_id)
    {
        $post = get_post($template_id);
        $user = get_user_by('id', $post->post_author);
        $date = strtotime($post->post_date);
        $funnel_step_url = get_post_meta($post->ID, "pl_step_url", true);
        $slug = get_post_meta($post->ID, "pl_slug", true);
        $pl_funnel_name = get_post_meta($post->ID, "pl_funnel_name", true);
        $pl_funnel_id = get_post_meta($post->ID, "pl_funnel_id", true);
        $pl_step_id = get_post_meta($post->ID, "pl_step_id", true);
        $pl_step_name = get_post_meta($post->ID, "pl_step_name", true);
        $pl_display_method = get_post_meta($post->ID, "pl_display_method", true);
        $pl_include_tracking_code = get_post_meta($post->ID, "pl_include_tracking_code", true);

        $base_url = get_home_url() . "/";

        $data = [
            'template_id' => $post->ID,
            'title' => $post->post_title,
            'thumbnail' => get_the_post_thumbnail_url($post),
            'date' => $date,
            'human_date' => date_i18n(get_option('date_format'), $date),
            'human_modified_date' => date_i18n(get_option('date_format'), strtotime($post->post_modified)),
            'author' => $user ? $user->display_name : "undefined",
            'status' => $post->post_status,
            'url' => $base_url . $slug,
            "slug" => $slug,
            "funnel_step_url" => $funnel_step_url,
            "pl_funnel_name" => $pl_funnel_name,
            "pl_funnel_id" => $pl_funnel_id,
            "pl_step_id" => $pl_step_id,
            "pl_step_name" => $pl_step_name,
            "location_id" => $location_id,
            "pl_display_method" => $pl_display_method,
            "pl_include_tracking_code" => $pl_include_tracking_code,

        ];

        return $data;
    }

    /**
     * Public API handler
     * rest API call from front-end will end up here, where endpoint query param will the action or remote API path.
     * @since    1.0.0
     */
    public function pl_public_api_proxy($request)
    {

        $options = get_option(PIPE_LINE_OPTION_NAME);
        $params = $request->get_query_params();
        $endpoint = $params[pipe_line_constants\pl_rest_api_endpoint_param];
        $directEndpoint = $params[pipe_line_constants\pl_rest_api_direct_endpoint_param];

        $api_key = "";
        if (isset($options[pipe_line_constants\pl_options_api_key])) {
            $api_key = $options[pipe_line_constants\pl_options_api_key];
            $api_key = trim($api_key);
        }

        if ($request->get_method() == 'POST') {
            error_log("current_user");
            error_log(print_r($request, true));
            error_log(print_r($request->get_body(), true));

            if ($endpoint == "wp_save_options") {
                $body = json_decode($request->get_body());

                if (isset($body->enable_text_widget)) {
                    $options[pipe_line_constants\pl_options_enable_text_widget] = $body->enable_text_widget;
                }
                if (isset($body->api_key)) {
                    $options[pipe_line_constants\pl_options_api_key] = $body->api_key;
                }

                $newOptions = $this->pipe_line_setting_validate($options, true);
                $text_widget_error = "0";
                $error_details = '';
                $warning_msg = "";
                $white_label_url = "";
                $enable_text_widget = "";
                $api_key = "";
                $location_id = "";

                if (isset($newOptions[pipe_line_constants\pl_options_text_widget_error])) {
                    $text_widget_error = esc_attr($newOptions[pipe_line_constants\pl_options_text_widget_error]);
                }
                if (isset($newOptions[pipe_line_constants\pl_options_text_widget_error_details])) {
                    $error_details = esc_attr($newOptions[pipe_line_constants\pl_options_text_widget_error_details]);
                }
                if (isset($newOptions[pipe_line_constants\pl_options_text_widget_warning_text])) {
                    $warning_msg = esc_attr($newOptions[pipe_line_constants\pl_options_text_widget_warning_text]);
                }
                if (isset($newOptions[pipe_line_constants\pl_options_location_white_label_url])) {
                    $white_label_url = (esc_attr($newOptions[pipe_line_constants\pl_options_location_white_label_url]));
                }
                if (isset($newOptions[pipe_line_constants\pl_options_enable_text_widget])) {
                    $enable_text_widget = (esc_attr($newOptions[pipe_line_constants\pl_options_enable_text_widget]));
                }
                if (isset($newOptions[pipe_line_constants\pl_options_api_key])) {
                    $api_key = (esc_attr($newOptions[pipe_line_constants\pl_options_api_key]));
                }
                if (isset($newOptions[pipe_line_constants\pl_options_location_id])) {
                    $location_id = (esc_attr($newOptions[pipe_line_constants\pl_options_location_id]));
                }

                $option_saved = update_option(PIPE_LINE_OPTION_NAME, $newOptions);
                if ($text_widget_error == "1") {
                    return (array(
                        'error' => true,
                        'message' => $error_details,
                    ));
                }
                return (array(
                    'success' => true,
                    'warning_msg' => $warning_msg,
                    'api_key' => $api_key,
                    "enable_text_widget" => $enable_text_widget,
                    "warning_msg" => $warning_msg,
                    "home_url" => get_home_url(),
                    "white_label_url" => $white_label_url,
                    "location_id" => $location_id,
                ));

            }

            if ($endpoint == "wp_insert_post") {
                $body = json_decode($request->get_body());

                if (defined('WP_DEBUG') && true === WP_DEBUG) {
                    error_log("wp_insert_post");
                    error_log(print_r($body, true));

                }

                $pl_slug = $body->pl_slug;
                $pl_step_id = $body->pl_step_id;
                $pl_step_name = $body->pl_step_name;
                $pl_funnel_id = $body->pl_funnel_id;
                $pl_funnel_name = $body->pl_funnel_name;
                $pl_step_url = $body->pl_step_url;
                $template_id = $body->template_id;
                $pl_display_method = $body->pl_display_method;
                $pl_step_meta = null;
                $pl_step_trackingCode = null;
                $pl_funnel_tracking_code = null;

                $pl_include_tracking_code = false;
                if ($body->pl_include_tracking_code == "1") {
                    $pl_include_tracking_code = true;
                }

                if (isset($body->pl_step_meta)) {
                    $pl_step_meta = $body->pl_step_meta;
                }

                if (isset($body->pl_funnel_tracking_code) && $pl_include_tracking_code) {
                    if (defined('WP_DEBUG') && true === WP_DEBUG) {
                        error_log("pl_funnel_tracking_code");
                        error_log(print_r($body->pl_funnel_tracking_code, true));
                        error_log(print_r($body->pl_funnel_tracking_code->headerCode, true));
                        error_log(print_r($body->pl_funnel_tracking_code->footerCode, true));
                    }

                    $pl_funnel_tracking_code = json_encode($body->pl_funnel_tracking_code, JSON_UNESCAPED_UNICODE);

                }

                if (isset($body->pl_step_page_download_url) && $pl_include_tracking_code) {
                    if (defined('WP_DEBUG') && true === WP_DEBUG) {
                        error_log("wp_insert_post_tracking");
                        error_log(print_r($body->pl_include_tracking_code, true));
                        error_log(print_r($body->pl_step_page_download_url, true));
                    }

                    $args = array(
                        'timeout' => 60,
                    );
                    $response_code = wp_remote_get($body->pl_step_page_download_url, $args);
                    $http_tracking_code = wp_remote_retrieve_response_code($response_code);
                    if ($http_tracking_code === 200) {
                        $body_tracking_code = wp_remote_retrieve_body($response_code);

                        $tracking_res = json_decode($body_tracking_code, false);

                        if (isset($tracking_res) && isset($tracking_res->trackingCode)) {
                            $pl_step_trackingCode = base64_encode(json_encode($tracking_res->trackingCode, JSON_UNESCAPED_UNICODE));
                            if (defined('WP_DEBUG') && true === WP_DEBUG) {
                                error_log(print_r($pl_step_trackingCode, true));
                            }
                        }
                    }
                }

                $query_args = array(
                    'meta_key' => 'pl_slug',
                    'meta_value' => $pl_slug,
                    'post_type' => pipe_line_constants\pl_custom_post_type,
                    'compare' => '=',
                );

                $the_posts = get_posts($query_args);
                if (count((array) $the_posts) && $template_id === -1) {
                    return (array(
                        'error' => true,
                        'message' => "slug '" . $pl_slug . "' already exist",
                        "code" => 1009,
                    ));

                }

                $user = wp_get_current_user();
                $user_id = get_current_user_id();
                $user_logged_in = is_user_logged_in();
                error_log("current_user");
                error_log(print_r($user, true));
                error_log(print_r($user_id, true));
                error_log(print_r($user_logged_in, true));

                $post_data = [];
                $post_data['post_title'] = "PipeLine_funnel-" . $pl_funnel_name . "-step-" . $pl_step_name;
                $post_data['post_content'] = "";
                $post_data['post_type'] = pipe_line_constants\pl_custom_post_type;
                $post_data['post_status'] = "publish";
                if ($template_id !== -1) {
                    $post_data['ID'] = $template_id;
                }
                // $post_data['post_author'] = $post_author;

                $post_id = wp_insert_post($post_data);
                if (is_wp_error($post_id) || $post_id === 0) {
                    error_log("fail to save the post");
                    return (array(
                        'error' => true,
                        'message' => "fail to save the post",
                    ));
                }
                if (isset($pl_slug)) {
                    update_post_meta($post_id, "pl_slug", $pl_slug);
                }
                if (isset($pl_step_id)) {
                    update_post_meta($post_id, "pl_step_id", $pl_step_id);
                }
                if (isset($pl_step_name)) {
                    update_post_meta($post_id, "pl_step_name", $pl_step_name);
                }
                if (isset($pl_funnel_id)) {
                    update_post_meta($post_id, "pl_funnel_id", $pl_funnel_id);
                }
                if (isset($pl_funnel_name)) {
                    update_post_meta($post_id, "pl_funnel_name", $pl_funnel_name);
                }
                if (isset($pl_step_url)) {
                    update_post_meta($post_id, "pl_step_url", $pl_step_url);
                }
                if (isset($pl_display_method)) {
                    update_post_meta($post_id, "pl_display_method", $pl_display_method);
                }
                if (isset($pl_step_meta)) {
                    update_post_meta($post_id, "pl_step_meta", json_encode($pl_step_meta, JSON_UNESCAPED_UNICODE));
                }
                if (isset($pl_step_trackingCode)) {
                    update_post_meta($post_id, "pl_step_trackingCode", $pl_step_trackingCode);
                }
                if (isset($pl_include_tracking_code)) {
                    update_post_meta($post_id, "pl_include_tracking_code", $pl_include_tracking_code);
                }
                if (isset($pl_funnel_tracking_code)) {
                    update_post_meta($post_id, "pl_funnel_tracking_code", $pl_funnel_tracking_code);
                }

                return (array(
                    'success' => true,
                ));
            }
            return;
        }

        if ($endpoint == "wp_get_all_posts") {
            $query_args = array(
                'post_type' => pipe_line_constants\pl_custom_post_type,
                'post_status' => 'any',
                'compare' => '=',
                "numberposts" => -1,
            );

            $the_posts = get_posts($query_args);

            $templates = [];
            $location_id = "";
            if (isset($options[pipe_line_constants\pl_options_location_id])) {
                $location_id = esc_attr($options[pipe_line_constants\pl_options_location_id]);
            }
            foreach ($the_posts as $post) {
                $templates[] = $this->get_item($post->ID, $location_id);
            }
            return ($templates);
        }

        if ($endpoint == "wp_get_pl_options") {

            $enabled_text_widget = 0;
            $text_widget_error = "0";
            $error_details = '';
            $warning_msg = "";
            $white_label_url = "";
            $location_id = "";

            if (isset($options[pipe_line_constants\pl_options_text_widget_error])) {
                $text_widget_error = esc_attr($options[pipe_line_constants\pl_options_text_widget_error]);
            }
            if (isset($options[pipe_line_constants\pl_options_text_widget_error_details])) {
                $error_details = esc_attr($options[pipe_line_constants\pl_options_text_widget_error_details]);
            }
            if (isset($options[pipe_line_constants\pl_options_text_widget_warning_text])) {
                $warning_msg = esc_attr($options[pipe_line_constants\pl_options_text_widget_warning_text]);
            }
            if (isset($options[pipe_line_constants\pl_options_enable_text_widget])) {
                $enabled_text_widget = esc_attr($options[pipe_line_constants\pl_options_enable_text_widget]);
            }
            if (isset($options[pipe_line_constants\pl_options_location_white_label_url])) {
                $white_label_url = (esc_attr($options[pipe_line_constants\pl_options_location_white_label_url]));
            }
            if (isset($options[pipe_line_constants\pl_options_location_id])) {
                $location_id = (esc_attr($options[pipe_line_constants\pl_options_location_id]));
            }

            $data = [
                'api_key' => $api_key,
                "enable_text_widget" => $enabled_text_widget,
                "text_widget_error" => $text_widget_error == "1" ? true : false,
                "error_details" => $error_details,
                "warning_msg" => $warning_msg,
                "home_url" => get_home_url(),
                "white_label_url" => $white_label_url,
                "location_id" => $location_id,
            ];

            return $data;
        }

        if ($endpoint == "wp_delete_post") {
            $body = json_decode($params['data']);
            $post_id = $body->post_id;
            $force_delete = $body->force_delete;

            $post_info = wp_delete_post($post_id, $force_delete);
            if (!$post_info) {
                error_log("fail to delete the post");
                return (array(
                    'error' => true,
                    'message' => "fail to delete the post",
                ));

            }
            return $post_info;
        }
        if ($directEndpoint == "true") {
            $args = array(
                'timeout' => 60,
            );
            $response = wp_remote_get($endpoint, $args);
            $http_code = wp_remote_retrieve_response_code($response);
            if ($http_code === 200) {
                $body = wp_remote_retrieve_body($response);
                $obj = json_decode($body, false);
                return $obj;
            } else {
                return (array(
                    'error' => true,
                ));
            }
        }

        //else call Remote API here
        $args = array(
            'timeout' => 60,
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
            ),
        );
        $response = wp_remote_get(PIPE_LINE_BASE_URL . $endpoint, $args);
        $http_code = wp_remote_retrieve_response_code($response);
        if ($http_code === 200) {
            $body = wp_remote_retrieve_body($response);
            $obj = json_decode($body, false);
            return $obj;
        } else {
            return (array(
                'error' => true,
            ));
        }
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles($hook)
    {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/pl-admin.css', array(), $this->version, 'all');

        if ($hook != 'toplevel_page_pl-plugin' && $hook != 'pipeline-so_page_pl-plugin-map-forms-help' ) {
            return;
        }

        // error_log(print_r($_SERVER['REMOTE_ADDR'], true));
        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined PipeLine_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The PipeLine_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        $css_to_load = plugin_dir_url(__FILE__) . '/css/app.min.css';
        $vendor_css_to_load = plugin_dir_url(__FILE__) . '/css/chunk-vendors.min.css';

        wp_enqueue_style('vue_pipe_line_app', $css_to_load, array(), $this->version, 'all');
        wp_enqueue_style('vue_pipe_line_vendor', $vendor_css_to_load, array(), $this->version, 'all');

    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts($hook)
    {
        error_log("enqueue_scripts");

        if ($hook != 'toplevel_page_pl-plugin') {
            return;
        }
        // error_log(print_r($hook, true));
        // error_log(print_r($_SERVER['REMOTE_ADDR'], true));

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in PipeLine_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The PipeLine_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/pl-admin.js', array('jquery'), $this->version, false);
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/repeater.js', array('jquery'), $this->version, false);
        if (in_array($_SERVER['REMOTE_ADDR'], array('10.255.0.2', '::1'))) {
            // DEV Vue dynamic loading
            $js_to_load = plugin_dir_url(__FILE__) . 'js/app.min.js';
            $vendor_js_to_load = plugin_dir_url(__FILE__) . 'js/chunk-vendors.min.js';
            wp_enqueue_script('vue_pipe_line_vendor_js', $vendor_js_to_load, '', mt_rand(11, 1000), true);

        } else {
            $js_to_load = plugin_dir_url(__FILE__) . 'js/app.min.js';
            $vendor_js_to_load = plugin_dir_url(__FILE__) . 'js/chunk-vendors.min.js';
            wp_enqueue_script('vue_pipe_line_vendor_js', $vendor_js_to_load, '', mt_rand(11, 1000), true);
        }

        $options = get_option(PIPE_LINE_OPTION_NAME);
        $enabledTextWidget = 0;
        if (isset($options[pipe_line_constants\pl_options_enable_text_widget])) {
            $enabledTextWidget = esc_attr($options[pipe_line_constants\pl_options_enable_text_widget]);
        }

        wp_localize_script($this->plugin_name, 'pl_admin_settings',
            array(
                'enable_text-widget' => $enabledTextWidget,
                'proxy_url' => rest_url('pl_public_api/v1/proxy'),
                'nonce' => wp_create_nonce('wp_rest'),
            ));

        wp_enqueue_script('vue_pipe_line_js', $js_to_load, '', mt_rand(10, 1000), true);

    }

    /**
     * Top level menu callback function
     */

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function wporg_options_page()
    {
        add_menu_page(
            __('Pipeline.so', 'PipeLine'),
            __('Pipeline.so', 'PipeLine'),
            'manage_options',
            'pl-plugin',
            'pipe_line_render_plugin_settings_page',
            'dashicons-admin-generic',
            '58.5'
        );

        //create submenu settings
	   add_submenu_page(
			'pl-plugin',
			__( 'Integrations', 'PipeLine' ),
			__( 'Integrations', 'PipeLine' ),
			'manage_options',
			'pl-plugin-integrations',
			'pipe_line_render_plugin_integrations_page'
		);
  	$data_innn = unserialize(get_option('pipe_line_render_options'));
  	//~ print_r($data_innn);
  	//~ die();
  	
		if( !empty($data_innn) && in_array('mapforms',$data_innn))
  	   add_submenu_page(
  			'pl-plugin',
  			__( 'Map Forms', 'PipeLine' ),
  			__( 'Map forms', 'PipeLine' ),
  			'manage_options',
  			'pl-plugin-map-forms',
  			'pipe_line_render_plugin_map_forms_page'
  		);
  	   add_submenu_page(
  			'pl-plugin',
  			__( 'Help', 'PipeLine' ),
  			__( 'Help', 'PipeLine' ),
  			'manage_options',
  			'pl-plugin-map-forms-help',
  			'pipe_line_render_plugin_map_forms_help_page'
  		);
    }
    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function register_settings()
    {
        add_settings_section('api_settings_inputs', __('', 'PipeLine'), 'pipe_line_section_text1', 'pipe_line_plugin');

        register_setting(PIPE_LINE_OPTION_NAME, PIPE_LINE_OPTION_NAME, array(
            'sanitize_callback' => array($this, 'pipe_line_setting_validate'),
        ));
    }

    public function admin_rest_api_init()
    {
        register_rest_route('pl_public_api/v1', '/proxy', array(
            // By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
            'methods' => WP_REST_Server::READABLE,
            // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
            'callback' => array($this, 'pl_public_api_proxy'),
            'permission_callback' => '__return_true',
        ));
        register_rest_route('pl_public_api/v1', 'proxy', array(
            // By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
            'methods' => WP_REST_Server::CREATABLE,
            // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
            'callback' => array($this, 'pl_public_api_proxy'),
            'permission_callback' => '__return_true',
        ));

    }

    public function register_custom_post()
    {
        $labels = array(
            'name' => _x('Funnels', 'pipeline funnels'),
            'singular_name' => _x('Funnel', 'post type singular name'),
            'add_new' => _x('Add New', 'Funnel'),
        );
        $args = array(
            'labels' => $labels,
            'description' => 'post your CRM account funnels on wordpress',
            'public' => false,
            'has_archive' => true,
            'supports' => array(''),
            'rewrite' => array('slug' => 'funnels'),
            'register_meta_box_cb' => array($this, "remove_save_box"),
            'hide_post_row_actions' => array('trash'),
        );
        register_post_type(pipe_line_constants\pl_custom_post_type, $args);

    }

    public function get_meta_fields($pl_post_meta)
    {
        $default_meta_fields = '
        <meta  charset="utf-8" />
        <meta
            name="viewport"
            content="width=device-width, user-scalable=no"
        />
        <meta
            name="description"
            content="description"
        />
        <meta  property="og:type" content="website" />
        <meta
            property="twitter:type"
            content="website"
        />
        <meta property="robots" content="noindex" />
        ';
        try {

            if (isset($pl_post_meta)) {

                $pl_post_meta = json_decode($pl_post_meta);
                if (isset($pl_post_meta->title) && $pl_post_meta->title !== '') {
                    $title_meta = '<title>' . $pl_post_meta->title . '</title>
                    <meta property="og:title" content="' . $pl_post_meta->title . '" />
                ';
                    $default_meta_fields .= $title_meta;
                }
                if (isset($pl_post_meta->description) && $pl_post_meta->description !== '') {
                    $description_meta = '<meta property="description" content="' . $pl_post_meta->description . '"/>
                    <meta property="og:description" content="' . $pl_post_meta->description . '" />
                ';
                    $default_meta_fields .= $description_meta;
                }

                if (isset($pl_post_meta->author) && $pl_post_meta->author !== '') {
                    $author_meta = '<meta property="author" content="' . $pl_post_meta->author . '"/>
                    <meta property="og:author" content="' . $pl_post_meta->author . '" />
                ';
                    $default_meta_fields .= $author_meta;
                }

                if (isset($pl_post_meta->imageUrl) && $pl_post_meta->imageUrl !== '') {
                    $imageUrl_meta = '<meta property="image" content="' . $pl_post_meta->imageUrl . '"/>
                    <meta property="og:image" content="' . $pl_post_meta->imageUrl . '" />
                ';
                    $default_meta_fields .= $imageUrl_meta;
                }
                if (isset($pl_post_meta->keywords) && $pl_post_meta->keywords !== '') {
                    $keywords_meta = '<meta property="keywords" content="' . $pl_post_meta->keywords . '"/>
                    <meta property="og:keywords" content="' . $pl_post_meta->keywords . '" />
                ';
                    $default_meta_fields .= $keywords_meta;
                }
                if (isset($pl_post_meta->customMeta) && count((array) $pl_post_meta->customMeta) > 0) {
                    foreach ($pl_post_meta->customMeta as $customMeta) {
                        if (isset($customMeta)) {

                            if (isset($customMeta->name) && $customMeta->name !== '' &&
                                isset($customMeta->content) && $customMeta->content !== '') {
                                $custom_meta = '<meta property="' . $customMeta->name . '" content="' . $customMeta->content . '"/>';
                                $default_meta_fields .= $custom_meta;
                            }

                        }
                    }

                }

            }
        } catch (Exception $e) {
            error_log("failed to parse the post meta");
            error_log(print_r($e, true));

        }
        return $default_meta_fields;

    }

    public function get_tracking_code($pl_post_tracking_code, $is_header, $is_funnel = false)
    {
        if (defined('WP_DEBUG') && true === WP_DEBUG) {
            error_log("get_tracking_code");
            error_log(print_r($pl_post_tracking_code, true));
        }

        $default_tracking_code = ' ';
        try {
            if (isset($pl_post_tracking_code)) {

                if (!$is_funnel) {
                    $pl_post_tracking_code = base64_decode($pl_post_tracking_code);
                }
                $pl_post_tracking_code = json_decode($pl_post_tracking_code);

                if ($is_header && isset($pl_post_tracking_code->headerCode) && $pl_post_tracking_code->headerCode !== '') {
                    $default_tracking_code = ($pl_post_tracking_code->headerCode);
                }
                if (!$is_header && isset($pl_post_tracking_code->footerCode) && $pl_post_tracking_code->footerCode !== '') {
                    $default_tracking_code = $pl_post_tracking_code->footerCode;
                }
                if ($is_funnel) {
                    $default_tracking_code = base64_decode($default_tracking_code);
                }

            }
        } catch (Exception $e) {
            error_log("failed to parse the tracking code");
            error_log(print_r($e, true));

        }
        return $default_tracking_code;

    }

    public function get_page_iframe($funnel_step_url, $pl_post_meta, $pl_step_trackingCode, $pl_funnel_tracking_code)
    {
        $post_meta_fields = $this->get_meta_fields($pl_post_meta);
        $head_tracking_code = $this->get_tracking_code($pl_funnel_tracking_code, true, true);
        $head_tracking_code .= $this->get_tracking_code($pl_step_trackingCode, true);

        $footer_tracking_code = $this->get_tracking_code($pl_funnel_tracking_code, false, true);
        $footer_tracking_code .= $this->get_tracking_code($pl_step_trackingCode, false);

        $widget_url = PIPE_LINE_CDN_BASE_URL . 'loader.js';
        // wp_enqueue_script($this->plugin_name . ".pl_text_widget", PIPE_LINE_CDN_BASE_URL . 'loader.js');
        // wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/pl-public.js', array('jquery'), $this->version, false);
        // wp_localize_script($this->plugin_name, 'pl_public_js',
        //     array(
        //         'text_widget_location_id' => $location_id,
        //         'text_widget_heading' => $heading,
        //         'text_widget_sub_heading' => $sub_heading,
        //         'text_widget_error' => $text_widget_error,
        //         'text_widget_use_email_field' => $use_email_field,
        //         "text_widget_settings" => $chat_widget_settings,
        //     ));

        return '<!DOCTYPE html>
            <head>
            ' . $post_meta_fields . '
            ' . $head_tracking_code . '
                <style>
                    body {
                        margin: 0;            /* Reset default margin */
                    }
                    iframe {
                        display: block;       /* iframes are inline by default */
                        border: none;         /* Reset default border */
                        height: 100vh;        /* Viewport-relative units */
                        width: 100vw;
                    }
                </style>
                <meta name="viewport" content="width=device-width, initial-scale=1">
            </head>
            <body>

                <iframe width="100%" height="100%" src="' . $funnel_step_url . '" frameborder="0" allowfullscreen></iframe>
                 ' . $footer_tracking_code . '
            </body>
        </html>';
    }

    public function process_page_request()
    {

        $full_request_url = (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $request_url_parts = explode("?", $full_request_url);
        $request_url = $request_url_parts[0];
        $base_url = get_home_url() . "/";
        $slug = str_replace($base_url, "", $request_url);
        $slug = rtrim($slug, '/');
        if ($slug != '') {
            $query_args = array(
                'meta_key' => 'pl_slug',
                'meta_value' => $slug,
                'post_type' => pipe_line_constants\pl_custom_post_type,
                'compare' => '=',
            );

            $the_posts = get_posts($query_args);
            $pl_page = current($the_posts);

            if ($pl_page) {
                status_header(200);
                $post_id = $pl_page->ID;
                $funnel_step_url = get_post_meta($post_id, "pl_step_url", true);
                $pl_display_method = get_post_meta($post_id, "pl_display_method", true);
                $pl_post_meta = get_post_meta($post_id, "pl_step_meta", true);
                $pl_step_trackingCode = get_post_meta($post_id, "pl_step_trackingCode", true);
                $pl_funnel_tracking_code = get_post_meta($post_id, "pl_funnel_tracking_code", true);
                $pl_include_tracking_code = get_post_meta($post_id, "pl_include_tracking_code", true);

                if (!$pl_include_tracking_code) {
                    $pl_step_trackingCode = null;
                    $pl_funnel_tracking_code = null;
                }

                if ($pl_display_method == "iframe") {
                   printf( esc_html(_e($this->get_page_iframe($funnel_step_url, $pl_post_meta, $pl_step_trackingCode, $pl_funnel_tracking_code),'PipeLine')));

                } else {
                    wp_redirect($funnel_step_url, 301);
                }
                exit();
            }

        }

    }

}
