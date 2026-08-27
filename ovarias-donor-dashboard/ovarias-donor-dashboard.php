<?php
/*
Plugin Name: Ovarias Donor Dashboard
Plugin URI: https://ovarias.com
Description: Donor Dashboard with Zoho CRM Integration
Version: 1.1.9
Author: Ovarias
*/

if (!defined('ABSPATH')) {
    exit;
}

define('OVARIAS_DONOR_VERSION', '1.1.9');
define('OVARIAS_DONOR_PATH', plugin_dir_path(__FILE__));
define('OVARIAS_PARENT_VERSION_COMPAT', '1.0.1'); // Keep compat constants if any
define('OVARIAS_DONOR_URL', plugin_dir_url(__FILE__));

require_once OVARIAS_DONOR_PATH . 'includes/helpers.php';
require_once OVARIAS_DONOR_PATH . 'includes/save-profile.php';
require_once OVARIAS_DONOR_PATH . 'includes/zoho-sync.php';

function ovarias_donor_enqueue_assets() {

    wp_enqueue_style(
        'ovarias-donor-style',
        OVARIAS_DONOR_URL . 'assets/css/dashboard.css',
        array(),
        OVARIAS_DONOR_VERSION
    );

    wp_enqueue_script(
        'ovarias-donor-js',
        OVARIAS_DONOR_URL . 'assets/js/dashboard.js',
        array('jquery'),
        OVARIAS_DONOR_VERSION,
        true
    );
}

add_action(
    'wp_enqueue_scripts',
    'ovarias_donor_enqueue_assets'
);

/**
 * Helper to get the custom donor login redirect URL
 */
function ovarias_get_custom_donor_login_url() {
    $login_url = home_url('/login/donor/');
    return add_query_arg('redirect_to', urlencode(get_permalink()), $login_url);
}

/**
 * Server-side redirect for non-logged in users accessing the donor dashboard
 */
function ovarias_donor_redirect_non_logged_in() {
    if (is_admin() || (defined('REST_REQUEST') && REST_REQUEST)) {
        return;
    }
    if (is_page() && !is_user_logged_in()) {
        global $post;
        if (isset($post->post_content) && has_shortcode($post->post_content, 'ovarias_donor_dashboard')) {
            wp_safe_redirect(ovarias_get_custom_donor_login_url());
            exit;
        }
    }
}
add_action('template_redirect', 'ovarias_donor_redirect_non_logged_in');

/**
 * Smart redirect for logged in users with incorrect role accessing the donor dashboard
 */
if (!function_exists('ovarias_donor_redirect_wrong_roles')) {
    function ovarias_donor_redirect_wrong_roles() {
        if (is_admin() || (defined('REST_REQUEST') && REST_REQUEST)) {
            return;
        }
        if (is_page() && is_user_logged_in()) {
            global $post;
            if (isset($post->post_content) && has_shortcode($post->post_content, 'ovarias_donor_dashboard')) {
                $current_user = wp_get_current_user();
                // If they are a parent, redirect them to the parent dashboard page
                if (in_array('um_intended-parent', $current_user->roles) || in_array('intended_parent', $current_user->roles)) {
                    global $wpdb;
                    $parent_page_id = $wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE post_content LIKE '%[ovarias_parent_dashboard]%' AND post_status = 'publish' AND post_type = 'page' LIMIT 1");
                    $parent_url = $parent_page_id ? get_permalink($parent_page_id) : home_url('/intended-parent-dashboard/');
                    wp_safe_redirect($parent_url);
                    exit;
                }
            }
        }
    }
    add_action('template_redirect', 'ovarias_donor_redirect_wrong_roles');
}


function ovarias_donor_dashboard_shortcode() {

    if (!is_user_logged_in()) {
        // Javascript redirect fallback in case template_redirect is bypassed
        return '<script>window.location.href = "' . esc_url(ovarias_get_custom_donor_login_url()) . '";</script>';
    }

    $current_user = wp_get_current_user();

    if (!in_array('um_egg-donor', (array) $current_user->roles) && !in_array('egg_donor', (array) $current_user->roles) && !in_array('administrator', (array) $current_user->roles)) {

        return '
        <div class="ovarias-message">
            Access denied.
        </div>';
    }

    ob_start();

    include OVARIAS_DONOR_PATH . 'includes/dashboard-form.php';

    return ob_get_clean();
}

add_shortcode(
    'ovarias_donor_dashboard',
    'ovarias_donor_dashboard_shortcode'
);

/**
 * Enforce role separation during login validation (bypasses cross-login actions)
 */
if (!function_exists('ovarias_restrict_login_by_role')) {
    function ovarias_restrict_login_by_role($user, $username, $password) {
        if (is_wp_error($user) || empty($user) || !($user instanceof WP_User)) {
            return $user;
        }

        // Always allow administrators to login anywhere
        if (in_array('administrator', $user->roles)) {
            return $user;
        }

        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        $referer = wp_get_referer() ?: '';

        $is_donor_page = (strpos($request_uri, 'donor') !== false || strpos($referer, 'donor') !== false);
        $is_parent_page = (strpos($request_uri, 'parent') !== false || strpos($referer, 'parent') !== false);

        // Get user roles dynamically
        $is_donor_user = false;
        $is_parent_user = false;
        foreach ($user->roles as $role) {
            $role_lower = strtolower($role);
            if (strpos($role_lower, 'donor') !== false) {
                $is_donor_user = true;
            }
            if (strpos($role_lower, 'parent') !== false) {
                $is_parent_user = true;
            }
        }

        // Block donors logging in on parent pages
        if ($is_parent_page && $is_donor_user) {
            return new WP_Error(
                'role_mismatch',
                '<strong>Error</strong>: Invalid login credentials for this portal.'
            );
        }

        // Block parents logging in on donor pages
        if ($is_donor_page && $is_parent_user) {
            return new WP_Error(
                'role_mismatch',
                '<strong>Error</strong>: Invalid login credentials for this portal.'
            );
        }

        return $user;
    }
    add_filter('authenticate', 'ovarias_restrict_login_by_role', 99, 3);
}

/**
 * Ultimate Member Login Validation to enforce role-separation on forms
 */
if (!function_exists('ovarias_um_login_validation')) {
    function ovarias_um_login_validation($args) {
        if (is_admin() || (defined('REST_REQUEST') && REST_REQUEST)) {
            return;
        }

        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        $referer = wp_get_referer() ?: '';

        $is_donor_page = (strpos($request_uri, 'donor') !== false || strpos($referer, 'donor') !== false);
        $is_parent_page = (strpos($request_uri, 'parent') !== false || strpos($referer, 'parent') !== false);

        if (!$is_donor_page && !$is_parent_page) {
            return;
        }

        $username = isset($args['username']) ? sanitize_text_field($args['username']) : '';
        if (empty($username)) {
            return;
        }

        $user = null;
        if (is_email($username)) {
            $user = get_user_by('email', $username);
        } else {
            $user = get_user_by('login', $username);
        }

        if (!$user || !($user instanceof WP_User)) {
            return;
        }

        // Allow administrators
        if (in_array('administrator', $user->roles)) {
            return;
        }

        // Get user roles dynamically
        $is_donor_user = false;
        $is_parent_user = false;
        foreach ($user->roles as $role) {
            $role_lower = strtolower($role);
            if (strpos($role_lower, 'donor') !== false) {
                $is_donor_user = true;
            }
            if (strpos($role_lower, 'parent') !== false) {
                $is_parent_user = true;
            }
        }

        if (function_exists('UM')) {
            if ($is_parent_page && $is_donor_user) {
                UM()->form()->add_error('username', 'Invalid login credentials for this portal.');
            }
            if ($is_donor_page && $is_parent_user) {
                UM()->form()->add_error('username', 'Invalid login credentials for this portal.');
            }
        }
    }
    add_action('um_submit_form_errors_hook_login', 'ovarias_um_login_validation', 10, 1);
}


