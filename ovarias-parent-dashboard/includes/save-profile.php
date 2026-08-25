<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('ovarias_save_parent_profile')) {
    function ovarias_save_parent_profile() {
        if (!isset($_POST['ovarias_save_parent'])) {
            return;
        }

        if (!is_user_logged_in()) {
            return;
        }

        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'ovarias_save_parent')) {
            wp_die('Security check failed');
        }

        $user_id = get_current_user_id();

        $fields = array(
            'first_name',
            'last_name',
            'phone_number',
            'country',
            'parent_preferences',
            'parent_notes'
        );

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_user_meta(
                    $user_id,
                    $field,
                    sanitize_textarea_field($_POST[$field])
                );
            }
        }

        // Set last updated timestamp
        update_user_meta($user_id, 'last_parent_dashboard_update', current_time('mysql'));

        // Sync to Zoho CRM (Bypassed for local database storage)
        $sync_success = true;
        /*
        if (function_exists('ovarias_sync_parent_to_zoho')) {
            $sync_success = ovarias_sync_parent_to_zoho($user_id);
        }
        */

        $redirect_url = wp_get_referer() ?: home_url('/intended-parent-dashboard/');
        $redirect_url = remove_query_arg(array('profile_updated', 'sync_error'), $redirect_url);

        if (!$sync_success) {
            $redirect_url = add_query_arg('sync_error', '1', $redirect_url);
        } else {
            $redirect_url = add_query_arg('profile_updated', '1', $redirect_url);
        }

        wp_safe_redirect($redirect_url);
        exit;
    }
    
    // Hook to both init and admin_post to catch all form submissions
    add_action('init', 'ovarias_save_parent_profile');
    add_action('admin_post_ovarias_save_parent', 'ovarias_save_parent_profile');
}
