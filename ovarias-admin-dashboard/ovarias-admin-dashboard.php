<?php
/*
Plugin Name: Ovarias Admin Dashboard
Plugin URI: https://ovarias.com
Description: Standalone administration panel for managing Ovarias parents, donors, and matching inquiries.
Version: 1.2.3
Author: Ovarias
*/

if (!defined('ABSPATH')) {
    exit;
}

define('OVARIAS_ADMIN_VERSION', '1.2.3');
define('OVARIAS_ADMIN_PATH', plugin_dir_path(__FILE__));
define('OVARIAS_ADMIN_URL', plugin_dir_url(__FILE__));

// Load includes
require_once OVARIAS_ADMIN_PATH . 'includes/ajax-handlers.php';

/**
 * Enqueue scripts and styles for Ovarias Admin page
 */
function ovarias_admin_enqueue_assets($hook) {
    // Only load on our custom admin menu page
    if ($hook !== 'toplevel_page_ovarias-admin') {
        return;
    }

    wp_enqueue_style(
        'ovarias-admin-style',
        OVARIAS_ADMIN_URL . 'assets/css/admin.css',
        array(),
        OVARIAS_ADMIN_VERSION
    );

    wp_enqueue_script(
        'ovarias-admin-js',
        OVARIAS_ADMIN_URL . 'assets/js/admin.js',
        array('jquery'),
        OVARIAS_ADMIN_VERSION,
        true
    );

    wp_localize_script('ovarias-admin-js', 'ovariasAdminParams', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('ovarias_admin_nonce')
    ));
}
add_action('admin_enqueue_scripts', 'ovarias_admin_enqueue_assets');

/**
 * Register Ovarias Admin menu page in WP sidebar
 */
function ovarias_admin_register_menu_page() {
    add_menu_page(
        'Ovarias Admin',           // Page title
        'Ovarias Admin',           // Menu title
        'manage_options',          // Capability required (Admins only)
        'ovarias-admin',           // Menu slug
        'ovarias_admin_render_page_callback', // Rendering function
        'dashicons-groups',        // Icon
        25                         // Position
    );
}
add_action('admin_menu', 'ovarias_admin_register_menu_page');

/**
 * Render the admin dashboard layout template
 */
function ovarias_admin_render_page_callback() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    include OVARIAS_ADMIN_PATH . 'includes/admin-view.php';
}

/**
 * Render Frontend Shortcode [ovarias_admin_dashboard] for mobile/tablet administrative access
 */
function ovarias_admin_dashboard_shortcode() {
    // Enqueue styles and scripts manually so they apply to both the login page and console
    wp_enqueue_style('ovarias-admin-style', OVARIAS_ADMIN_URL . 'assets/css/admin.css', array(), OVARIAS_ADMIN_VERSION);
    wp_enqueue_script('ovarias-admin-js', OVARIAS_ADMIN_URL . 'assets/js/admin.js', array('jquery'), OVARIAS_ADMIN_VERSION, true);
    wp_localize_script('ovarias-admin-js', 'ovariasAdminParams', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('ovarias_admin_nonce')
    ));

    // Check if user is logged in
    if (!is_user_logged_in()) {
        ob_start();
        ?>
        <div class="ovarias-public-inquiry-card" style="max-width: 420px; margin: 40px auto;">
            <h3 class="form-title">Ovarias Admin Login</h3>
            <p class="form-subtitle" style="margin-bottom: 20px;">Enter your administrator credentials below</p>
            
            <form name="loginform" id="loginform" action="<?php echo esc_url(site_url('wp-login.php', 'login_post')); ?>" method="post">
                <div class="form-row">
                    <label class="form-label" for="user_login" style="font-weight: 600; font-size: 13px;">Admin Username / Email</label>
                    <input type="text" name="log" id="user_login" class="form-input" value="" size="20" required style="width: 100%; box-sizing: border-box; height: 42px; padding: 10px; margin-top: 5px;">
                </div>
                
                <div class="form-row" style="margin-top: 15px;">
                    <label class="form-label" for="user_pass" style="font-weight: 600; font-size: 13px;">Password</label>
                    <input type="password" name="pwd" id="user_pass" class="form-input" value="" size="20" required style="width: 100%; box-sizing: border-box; height: 42px; padding: 10px; margin-top: 5px;">
                </div>
                
                <div class="form-row" style="margin-top: 15px; display: flex; align-items: center; gap: 8px;">
                    <input name="rememberme" type="checkbox" id="rememberme" value="forever">
                    <label for="rememberme" style="font-size: 13px; color: #555A4E; cursor: pointer; font-weight: 500;">Remember Me</label>
                </div>
                
                <input type="hidden" name="redirect_to" value="<?php echo esc_url(home_url(wp_unslash($_SERVER['REQUEST_URI']))); ?>">
                
                <button type="submit" name="wp-submit" id="wp-submit" class="action-btn" style="width: 100%; padding: 12px; font-size: 14px; font-weight: bold; margin-top: 25px; height: 45px; cursor: pointer; border-radius: 6px;">Log In</button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    // Check if current user is an administrator
    if (!current_user_can('manage_options')) {
        return '<div style="max-width: 600px; margin: 40px auto; padding: 25px; background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; border-radius: 6px; font-family: sans-serif; text-align: center; font-weight: 500;">Access denied. Only administrators can access the administrative console.</div>';
    }

    ob_start();
    include OVARIAS_ADMIN_PATH . 'includes/admin-view.php';
    return ob_get_clean();
}
add_shortcode('ovarias_admin_dashboard', 'ovarias_admin_dashboard_shortcode');

/**
 * Render Frontend Shortcode [ovarias_general_inquiry_form] for public contact/inquiry page
 */
function ovarias_general_inquiry_form_shortcode() {
    wp_enqueue_style('ovarias-admin-style', OVARIAS_ADMIN_URL . 'assets/css/admin.css', array(), OVARIAS_ADMIN_VERSION);
    wp_enqueue_script('ovarias-admin-js', OVARIAS_ADMIN_URL . 'assets/js/admin.js', array('jquery'), OVARIAS_ADMIN_VERSION, true);
    wp_localize_script('ovarias-admin-js', 'ovariasAdminParams', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('ovarias_admin_nonce')
    ));

    ob_start();
    ?>
    <div class="ovarias-public-inquiry-card">
        <h3 class="form-title">Send Us an Inquiry</h3>
        <p class="form-subtitle">Choose your inquiry type below, and our coordination team will assist you.</p>
        
        <form id="ovarias-public-inquiry-form">
            <div class="form-row">
                <label class="form-label">Inquiry Type <span class="required-star">*</span></label>
                <select id="inq-type" class="form-input" required style="width: 100%; height: 42px;">
                    <option value="I want to become an egg donor">I want to become an egg donor</option>
                    <option value="I want to become a sperm donor">I want to become a sperm donor</option>
                    <option value="General inquiry">General inquiry</option>
                </select>
            </div>
            
            <div class="form-row" style="margin-top: 15px;">
                <label class="form-label">Email Address <span class="required-star">*</span></label>
                <input type="email" id="inq-email" class="form-input" placeholder="example@domain.com" required style="width: 100%; box-sizing: border-box; height: 42px;">
            </div>
            
            <div class="form-row" style="margin-top: 15px;">
                <label class="form-label">Your Name</label>
                <input type="text" id="inq-name" class="form-input" placeholder="Enter your full name" style="width: 100%; box-sizing: border-box; height: 42px;">
            </div>
            
            <div class="form-row" style="margin-top: 15px;">
                <label class="form-label">Phone Number</label>
                <input type="tel" id="inq-phone" class="form-input" placeholder="+1 (555) 000-0000" style="width: 100%; box-sizing: border-box; height: 42px;">
            </div>
            
            <div class="form-row" style="margin-top: 15px;">
                <label class="form-label">Message / Details</label>
                <textarea id="inq-message" class="form-input" rows="5" placeholder="Write your message here..." style="width: 100%; box-sizing: border-box; resize: vertical; padding: 12px;"></textarea>
            </div>
            
            <!-- Honey-pot anti-spam field -->
            <input type="text" id="inq-hp" style="display:none !important;" autocomplete="off">
            
            <button type="submit" class="action-btn" id="btn-submit-inquiry" style="width: 100%; padding: 12px; font-size: 14px; font-weight: bold; margin-top: 20px; height: 45px; cursor: pointer; border-radius: 6px;">Submit Inquiry</button>
            <div id="inq-form-response" style="margin-top: 15px; text-align: center; font-size: 14px; display: none;"></div>
        </form>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('ovarias_general_inquiry_form', 'ovarias_general_inquiry_form_shortcode');

