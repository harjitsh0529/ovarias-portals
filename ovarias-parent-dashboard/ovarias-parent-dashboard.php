<?php
/*
Plugin Name: Ovarias Parent Dashboard
Plugin URI: https://ovarias.com
Description: Intended Parent Dashboard with Stripe Payment integration and Zoho CRM sync.
Version: 1.2.6
Author: Ovarias
*/

if (!defined('ABSPATH')) {
    exit;
}

define('OVARIAS_PARENT_VERSION', '1.2.6');
define('OVARIAS_PARENT_PATH', plugin_dir_path(__FILE__));
define('OVARIAS_PARENT_URL', plugin_dir_url(__FILE__));

// Load include files
require_once OVARIAS_PARENT_PATH . 'includes/save-profile.php';
require_once OVARIAS_PARENT_PATH . 'includes/zoho-sync.php';
require_once OVARIAS_PARENT_PATH . 'includes/stripe-checkout.php';

/**
 * Enqueue scripts and styles for the parent dashboard
 */
if (!function_exists('ovarias_parent_enqueue_assets')) {
    function ovarias_parent_enqueue_assets() {
        wp_enqueue_style(
            'ovarias-parent-style',
            OVARIAS_PARENT_URL . 'assets/css/dashboard.css',
            array(),
            OVARIAS_PARENT_VERSION
        );

        wp_enqueue_script(
            'ovarias-parent-js',
            OVARIAS_PARENT_URL . 'assets/js/dashboard.js',
            array('jquery'),
            OVARIAS_PARENT_VERSION,
            true
        );

        // Pass AJAX and localization data to JS
        wp_localize_script('ovarias-parent-js', 'ovariasParentParams', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('ovarias_parent_nonce')
        ));
    }
    add_action('wp_enqueue_scripts', 'ovarias_parent_enqueue_assets');
}

/**
 * Helper to get the custom intended parent login redirect URL
 */
if (!function_exists('ovarias_get_custom_parent_login_url')) {
    function ovarias_get_custom_parent_login_url() {
        $login_url = home_url('/login/intended-parent/');
        return add_query_arg('redirect_to', urlencode(get_permalink()), $login_url);
    }
}

/**
 * Server-side redirect for non-logged in users accessing the parent dashboard
 */
if (!function_exists('ovarias_parent_redirect_non_logged_in')) {
    function ovarias_parent_redirect_non_logged_in() {
        if (is_admin() || (defined('REST_REQUEST') && REST_REQUEST)) {
            return;
        }
        if (is_page() && !is_user_logged_in()) {
            global $post;
            if (isset($post->post_content) && has_shortcode($post->post_content, 'ovarias_parent_dashboard')) {
                wp_safe_redirect(ovarias_get_custom_parent_login_url());
                exit;
            }
        }
    }
    add_action('template_redirect', 'ovarias_parent_redirect_non_logged_in');
}

/**
 * Smart redirect for logged in users with incorrect role accessing the parent dashboard
 */
if (!function_exists('ovarias_parent_redirect_wrong_roles')) {
    function ovarias_parent_redirect_wrong_roles() {
        if (is_admin() || (defined('REST_REQUEST') && REST_REQUEST)) {
            return;
        }
        if (is_page() && is_user_logged_in()) {
            global $post;
            if (isset($post->post_content) && has_shortcode($post->post_content, 'ovarias_parent_dashboard')) {
                $current_user = wp_get_current_user();
                // If they are a donor, redirect them to the donor dashboard page
                if (in_array('um_egg-donor', $current_user->roles) || in_array('egg_donor', $current_user->roles)) {
                    global $wpdb;
                    $donor_page_id = $wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE post_content LIKE '%[ovarias_donor_dashboard]%' AND post_status = 'publish' AND post_type = 'page' LIMIT 1");
                    $donor_url = $donor_page_id ? get_permalink($donor_page_id) : home_url('/donor-dashboard/');
                    wp_safe_redirect($donor_url);
                    exit;
                }
            }
        }
    }
    add_action('template_redirect', 'ovarias_parent_redirect_wrong_roles');
}


/**
 * Intended Parent Dashboard shortcode handler
 */
if (!function_exists('ovarias_parent_dashboard_shortcode')) {
    function ovarias_parent_dashboard_shortcode() {
        if (!is_user_logged_in()) {
            // Javascript redirect fallback in case template_redirect is bypassed
            return '<script>window.location.href = "' . esc_url(ovarias_get_custom_parent_login_url()) . '";</script>';
        }

        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;
        
        // Merge WordPress roles with Ultimate Member metadata roles
        $user_roles = (array) $current_user->roles;
        
        $um_role = get_user_meta($user_id, 'role', true);
        if ($um_role) {
            $user_roles[] = $um_role;
        }
        
        $um_community_role = get_user_meta($user_id, 'community_role', true);
        if ($um_community_role) {
            $user_roles[] = $um_community_role;
        }

        // Check for Intended Parent role using fuzzy matching
        $has_access = false;
        foreach ($user_roles as $role) {
            $role_lower = strtolower($role);
            if (strpos($role_lower, 'parent') !== false || strpos($role_lower, 'admin') !== false) {
                $has_access = true;
                break;
            }
        }

        if (!$has_access) {
            $roles_str = implode(', ', array_unique($user_roles));
            return '<div class="ovarias-message">Access denied. Only Intended Parents can access this dashboard. (Debug: Your active roles are: ' . esc_html($roles_str) . ')</div>';
        }

        ob_start();
        include OVARIAS_PARENT_PATH . 'includes/dashboard-form.php';
        return ob_get_clean();
    }
    add_shortcode('ovarias_parent_dashboard', 'ovarias_parent_dashboard_shortcode');
}

/**
 * Redirect Intended Parents to their dashboard after login
 */
if (!function_exists('ovarias_parent_login_redirect')) {
    function ovarias_parent_login_redirect($redirect_to, $request, $user) {
        if (isset($user->ID)) {
            $user_id = $user->ID;
            $user_roles = (array) $user->roles;
            
            $um_role = get_user_meta($user_id, 'role', true);
            if ($um_role) {
                $user_roles[] = $um_role;
            }
            
            $um_community_role = get_user_meta($user_id, 'community_role', true);
            if ($um_community_role) {
                $user_roles[] = $um_community_role;
            }
            
            $is_parent = false;
            foreach ($user_roles as $role) {
                if (strpos(strtolower($role), 'parent') !== false) {
                    $is_parent = true;
                    break;
                }
            }
            
            if ($is_parent) {
                return home_url('/intended-parent-dashboard/');
            }
        }
        return $redirect_to;
    }
    add_filter('login_redirect', 'ovarias_parent_login_redirect', 30, 3);
}

/**
 * Register settings page for Stripe Keys in WordPress Admin
 */
if (!function_exists('ovarias_parent_register_settings')) {
    function ovarias_parent_register_settings() {
        register_setting('ovarias_parent_settings_group', 'ovarias_stripe_secret_key');
        register_setting('ovarias_parent_settings_group', 'ovarias_stripe_price_amount');
        register_setting('ovarias_parent_settings_group', 'ovarias_stripe_currency');
    }
    add_action('admin_init', 'ovarias_parent_register_settings');
}

if (!function_exists('ovarias_parent_register_settings_page')) {
    function ovarias_parent_register_settings_page() {
        add_options_page(
            'Ovarias Stripe Settings',
            'Ovarias Stripe',
            'manage_options',
            'ovarias-stripe-settings',
            'ovarias_parent_settings_page_html'
        );
    }
    add_action('admin_menu', 'ovarias_parent_register_settings_page');
}

if (!function_exists('ovarias_parent_settings_page_html')) {
    function ovarias_parent_settings_page_html() {
        ?>
        <div class="wrap">
            <h1>Ovarias Stripe Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('ovarias_parent_settings_group'); ?>
                <?php do_settings_sections('ovarias_parent_settings_group'); ?>
                <table class="form-table">
                    <tr valign="top">
                    <th scope="row">Stripe Secret Key</th>
                    <td><input type="text" name="ovarias_stripe_secret_key" value="<?php echo esc_attr(get_option('ovarias_stripe_secret_key')); ?>" style="width: 450px;" placeholder="sk_test_..." /></td>
                    </tr>
                    <tr valign="top">
                    <th scope="row">Price Amount ($)</th>
                    <td><input type="number" step="0.01" name="ovarias_stripe_price_amount" value="<?php echo esc_attr(get_option('ovarias_stripe_price_amount', '199.00')); ?>" /></td>
                    </tr>
                    <tr valign="top">
                    <th scope="row">Currency</th>
                    <td><input type="text" name="ovarias_stripe_currency" value="<?php echo esc_attr(get_option('ovarias_stripe_currency', 'usd')); ?>" /></td>
                     </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}

/**
 * Add custom payment and membership columns to WordPress Admin Users list page
 */
if (!function_exists('ovarias_parent_add_user_columns')) {
    function ovarias_parent_add_user_columns($columns) {
        $columns['ovarias_payment_status'] = 'Payment Status';
        $columns['ovarias_payment_date'] = 'Payment Date';
        $columns['ovarias_payment_amount'] = 'Amount Paid';
        $columns['ovarias_payment_txn_id'] = 'Txn Reference ID';
        $columns['ovarias_premium_status'] = 'Access Status';
        return $columns;
    }
    add_filter('manage_users_columns', 'ovarias_parent_add_user_columns');
}

/**
 * Render custom columns content on WP Admin Users list page
 */
if (!function_exists('ovarias_parent_render_user_column_content')) {
    function ovarias_parent_render_user_column_content($output, $column_name, $user_id) {
        $user = get_userdata($user_id);
        if (!$user) {
            return $output;
        }
        
        // Check if the user is a parent or administrator
        $user_roles = (array) $user->roles;
        $um_role = get_user_meta($user_id, 'role', true);
        if ($um_role) {
            $user_roles[] = $um_role;
        }
        
        $is_parent = false;
        foreach ($user_roles as $role) {
            if (strpos(strtolower($role), 'parent') !== false) {
                $is_parent = true;
                break;
            }
        }
        
        if (!$is_parent) {
            return $output;
        }

        switch ($column_name) {
            case 'ovarias_payment_status':
                $is_premium = get_user_meta($user_id, 'is_premium_parent', true);
                return $is_premium ? '<span style="color: #2e7d32; font-weight: bold;">Paid</span>' : '<span style="color: #c62828;">Unpaid / Pending</span>';
            
            case 'ovarias_payment_date':
                $pay_date = get_user_meta($user_id, 'ovarias_payment_date', true);
                return $pay_date ? esc_html(date('Y-m-d H:i', strtotime($pay_date))) : '—';
                
            case 'ovarias_payment_amount':
                $is_premium = get_user_meta($user_id, 'is_premium_parent', true);
                return $is_premium ? '$199.00' : '—';
                
            case 'ovarias_payment_txn_id':
                $is_premium = get_user_meta($user_id, 'is_premium_parent', true);
                if ($is_premium) {
                    $pay_date = get_user_meta($user_id, 'ovarias_payment_date', true) ?: '';
                    return 'TXN_' . substr(md5($user_id . $pay_date), 0, 10);
                }
                return '—';
                
            case 'ovarias_premium_status':
                $is_premium = get_user_meta($user_id, 'is_premium_parent', true);
                return $is_premium ? '<span style="background: #e8f5e9; color: #2e7d32; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase;">Premium Active</span>' : '<span style="background: #ffebee; color: #c62828; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase;">Restricted</span>';
                
            default:
                return $output;
        }
    }
    add_filter('manage_custom_column', 'ovarias_parent_render_user_column_content', 10, 3);
}

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


