<?php

if (!defined('ABSPATH')) {
    exit;
}

function ovarias_save_donor_profile() {

    if (!isset($_POST['ovarias_save_profile'])) {
        return;
    }

    if (!is_user_logged_in()) {
        return;
    }

    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'ovarias_save_profile')) {
        wp_die('Security check failed');
    }

    $user_id = get_current_user_id();

    $fields = array(
        'dob',
        'nationality',
        'height',
        'weight',
        'blood_group',
        'eye_colour',
        'hair_colour',

        'education_level',
        'field_of_study',
        'occupation',
        'languages_spoken',

        'donation_type',
        'travel_available',
        'passport_available',
        'previous_donations',

        'about_me',
        'hobbies',
        'why_donate',

        // New fields from Tech Brief
        'donor_id',
        'availability_status',
        'egg_type',
        'num_eggs',
        'storage_country',
        'num_donations'
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

    // Handle Profile Image Upload
    $upload_error = false;
    if (!empty($_FILES['profile_image']['name'])) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        // Check if it is a valid image file type
        $file_type = wp_check_filetype(basename($_FILES['profile_image']['name']));
        $allowed_types = array('jpg', 'jpeg', 'png', 'gif');

        if (in_array(strtolower($file_type['ext']), $allowed_types)) {
            $attachment_id = media_handle_upload(
                'profile_image',
                0
            );

            if (!is_wp_error($attachment_id)) {
                update_user_meta(
                    $user_id,
                    'profile_image',
                    $attachment_id
                );
            } else {
                $upload_error = true;
                error_log('Ovarias Profile Photo Upload Error: ' . $attachment_id->get_error_message());
            }
        } else {
            $upload_error = true;
            error_log('Ovarias Profile Photo Upload Error: Invalid file type.');
        }
    }

    // Recalculate and update completion metrics
    $completion_pct = ovarias_profile_completion_percentage($user_id);
    update_user_meta(
        $user_id,
        'profile_completed',
        ($completion_pct === 100) ? '1' : '0'
    );

    update_user_meta(
        $user_id,
        'last_dashboard_update',
        current_time('mysql')
    );

    // Sync to Zoho CRM (Bypassed for local database storage)
    $sync_success = true;
    /*
    if (function_exists('ovarias_sync_donor_to_zoho')) {
        $sync_success = ovarias_sync_donor_to_zoho($user_id);
    }
    */

    // Set query params for frontend toast notifications
    $redirect_url = wp_get_referer() ?: home_url('/donor-dashboard/');
    $redirect_url = remove_query_arg(array('profile_updated', 'sync_error', 'upload_error'), $redirect_url);

    if ($upload_error) {
        $redirect_url = add_query_arg('upload_error', '1', $redirect_url);
    }
    
    if (!$sync_success) {
        $redirect_url = add_query_arg('sync_error', '1', $redirect_url);
    } else {
        $redirect_url = add_query_arg('profile_updated', '1', $redirect_url);
    }

    wp_safe_redirect($redirect_url);
    exit;
}

add_action(
    'admin_post_ovarias_save_profile', // We can still run on init or move to a separate handler
    'ovarias_save_donor_profile'
);

// We keep the original init action to maintain compatibility with the form action
add_action(
    'init',
    'ovarias_save_donor_profile'
);
