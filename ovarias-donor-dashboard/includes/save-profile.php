<?php

if (!defined('ABSPATH')) {
    exit;
}

function ovarias_save_donor_profile() {
    if (is_user_logged_in() && isset($_GET['delete_profile_photo'])) {
        $user_id = get_current_user_id();
        $primary_id = get_user_meta($user_id, 'profile_image', true);
        if ($primary_id) {
            update_user_meta($user_id, 'profile_image', '');
            wp_delete_attachment($primary_id, true);
        }
        $redirect_url = remove_query_arg('delete_profile_photo');
        wp_safe_redirect($redirect_url);
        exit;
    }

    if (is_user_logged_in() && isset($_GET['delete_gallery_image'])) {
        $user_id = get_current_user_id();
        $img_id = (int)$_GET['delete_gallery_image'];
        $gallery = get_user_meta($user_id, 'profile_images_gallery', true) ?: array();
        
        if (($key = array_search($img_id, $gallery)) !== false) {
            unset($gallery[$key]);
            $gallery = array_values($gallery);
            update_user_meta($user_id, 'profile_images_gallery', $gallery);
            
            // Physically delete attachment from media library
            wp_delete_attachment($img_id, true);
        }
        
        $redirect_url = remove_query_arg('delete_gallery_image');
        wp_safe_redirect($redirect_url);
        exit;
    }

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

    // Handle Primary Profile Image Upload (Single)
    $upload_error = false;
    if (!empty($_FILES['profile_image']['name'])) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $file_type = wp_check_filetype(basename($_FILES['profile_image']['name']));
        $allowed_types = array('jpg', 'jpeg', 'png', 'gif');

        if (in_array(strtolower($file_type['ext']), $allowed_types)) {
            $attachment_id = media_handle_upload('profile_image', 0);

            if (!is_wp_error($attachment_id)) {
                // Delete previous profile picture from media library if one exists
                $old_avatar = get_user_meta($user_id, 'profile_image', true);
                if ($old_avatar) {
                    wp_delete_attachment($old_avatar, true);
                }
                update_user_meta($user_id, 'profile_image', $attachment_id);
            } else {
                $upload_error = true;
                error_log('Ovarias Profile Photo Upload Error: ' . $attachment_id->get_error_message());
            }
        } else {
            $upload_error = true;
            error_log('Ovarias Profile Photo Upload Error: Invalid file type.');
        }
    }

    // Handle Additional Photos Gallery Upload (Multiple)
    if (!empty($_FILES['donor_gallery']['name'][0])) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $files = $_FILES['donor_gallery'];
        $uploaded_attachments = get_user_meta($user_id, 'profile_images_gallery', true) ?: array();

        foreach ($files['name'] as $key => $value) {
            if ($files['name'][$key]) {
                $file = array(
                    'name'     => $files['name'][$key],
                    'type'     => $files['type'][$key],
                    'tmp_name' => $files['tmp_name'][$key],
                    'error'    => $files['error'][$key],
                    'size'     => $files['size'][$key]
                );

                $temp_key = "temp_upload_file_" . $key;
                $_FILES[$temp_key] = $file;

                $file_type = wp_check_filetype(basename($file['name']));
                $allowed_types = array('jpg', 'jpeg', 'png', 'gif');

                if (in_array(strtolower($file_type['ext']), $allowed_types)) {
                    $attachment_id = media_handle_upload($temp_key, 0);

                    if (!is_wp_error($attachment_id)) {
                        $uploaded_attachments[] = $attachment_id;
                    } else {
                        $upload_error = true;
                        error_log('Ovarias Gallery Upload Error: ' . $attachment_id->get_error_message());
                    }
                } else {
                    $upload_error = true;
                    error_log('Ovarias Gallery Upload Error: Invalid file type.');
                }
                unset($_FILES[$temp_key]);
            }
        }
        update_user_meta($user_id, 'profile_images_gallery', $uploaded_attachments);
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
