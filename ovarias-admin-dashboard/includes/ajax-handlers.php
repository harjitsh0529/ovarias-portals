<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * AJAX Handler: Toggle Parent Premium Access Status
 */
function ovarias_admin_ajax_toggle_premium() {
    check_ajax_referer('ovarias_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized action.'));
    }

    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $current_status = isset($_POST['current_status']) ? sanitize_text_field($_POST['current_status']) : '0';

    if (!$user_id) {
        wp_send_json_error(array('message' => 'Invalid user ID.'));
    }

    // Toggle premium state
    $new_status = ($current_status === '1') ? '0' : '1';
    update_user_meta($user_id, 'is_premium_parent', $new_status);

    if ($new_status === '1') {
        update_user_meta($user_id, 'ovarias_payment_date', current_time('mysql'));
    } else {
        delete_user_meta($user_id, 'ovarias_payment_date');
    }

    wp_send_json_success(array(
        'message' => 'Access status toggled successfully.',
        'new_status' => $new_status
    ));
}
add_action('wp_ajax_ovarias_admin_toggle_premium', 'ovarias_admin_ajax_toggle_premium');

/**
 * AJAX Handler: Update Donor Profile Status & Categories
 */
function ovarias_admin_ajax_update_donor() {
    check_ajax_referer('ovarias_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized action.'));
    }

    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $donor_id = isset($_POST['donor_id']) ? sanitize_text_field($_POST['donor_id']) : '';
    $availability = isset($_POST['availability']) ? sanitize_text_field($_POST['availability']) : 'Available';
    $egg_type = isset($_POST['egg_type']) ? sanitize_text_field($_POST['egg_type']) : 'Fresh';
    $num_eggs = isset($_POST['num_eggs']) ? (int)$_POST['num_eggs'] : 0;
    $storage_country = isset($_POST['storage_country']) ? sanitize_text_field($_POST['storage_country']) : '';

    if (!$user_id) {
        wp_send_json_error(array('message' => 'Invalid user ID.'));
    }

    // Save metadata
    update_user_meta($user_id, 'donor_id', $donor_id);
    update_user_meta($user_id, 'availability_status', $availability);
    update_user_meta($user_id, 'egg_type', $egg_type);
    
    if ($egg_type === 'Frozen' || $egg_type === 'Both') {
        update_user_meta($user_id, 'num_eggs', $num_eggs);
        update_user_meta($user_id, 'storage_country', $storage_country);
    } else {
        delete_user_meta($user_id, 'num_eggs');
        delete_user_meta($user_id, 'storage_country');
    }

    wp_send_json_success(array('message' => 'Donor details saved successfully.'));
}
add_action('wp_ajax_ovarias_admin_update_donor', 'ovarias_admin_ajax_update_donor');

/**
 * AJAX Handler: Update Client Info Inquiry Status
 */
function ovarias_admin_ajax_update_inquiry() {
    check_ajax_referer('ovarias_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized action.'));
    }

    $parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : 0;
    $inquiry_id = isset($_POST['inquiry_id']) ? sanitize_text_field($_POST['inquiry_id']) : '';
    $new_status = isset($_POST['new_status']) ? sanitize_text_field($_POST['new_status']) : 'New';

    if (!$parent_id || empty($inquiry_id)) {
        wp_send_json_error(array('message' => 'Invalid request identifiers.'));
    }

    $requests = get_user_meta($parent_id, 'ovarias_info_requests', true) ?: array();
    $updated = false;

    foreach ($requests as &$req) {
        if ($req['id'] === $inquiry_id) {
            $req['status'] = $new_status;
            $updated = true;
            break;
        }
    }

    if ($updated) {
        update_user_meta($parent_id, 'ovarias_info_requests', $requests);
        wp_send_json_success(array('message' => 'Inquiry status updated.'));
    } else {
        wp_send_json_error(array('message' => 'Inquiry record not found.'));
    }
}
add_action('wp_ajax_ovarias_admin_update_inquiry', 'ovarias_admin_ajax_update_inquiry');

/**
 * AJAX Handler: Create a New Parent or Donor User
 */
function ovarias_admin_ajax_create_user() {
    check_ajax_referer('ovarias_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized action.'));
    }

    $username = isset($_POST['username']) ? sanitize_user($_POST['username']) : '';
    $email = !empty($username) ? $username . '@ovarias.temp' : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
    $last_name = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
    $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : ''; // 'parent' or 'donor'

    if (empty($username) || empty($password) || empty($type)) {
        wp_send_json_error(array('message' => 'All mandatory fields (username, password, type) must be filled.'));
    }

    if (username_exists($username)) {
        wp_send_json_error(array('message' => 'This username already exists.'));
    }

    // Create the WordPress User
    $user_id = wp_create_user($username, $password, $email);
    if (is_wp_error($user_id)) {
        wp_send_json_error(array('message' => $user_id->get_error_message()));
    }

    // Save Names
    wp_update_user(array(
        'ID' => $user_id,
        'first_name' => $first_name,
        'last_name' => $last_name
    ));

    // Assign custom roles and Ultimate Member meta roles
    $u = new WP_User($user_id);
    if ($type === 'donor') {
        $u->set_role('um_egg-donor');
        update_user_meta($user_id, 'role', 'um_egg-donor');
        update_user_meta($user_id, 'community_role', 'um_egg-donor');
        
        // Save donor profile meta fields
        $donor_meta_fields = array(
            'donor_id', 'dob', 'nationality', 'blood_group', 'height', 'weight',
            'eye_colour', 'hair_colour', 'education_level', 'field_of_study',
            'occupation', 'languages_spoken', 'availability_status', 'egg_type',
            'num_eggs', 'storage_country', 'about_me', 'hobbies', 'why_donate',
            
            // New PDF Fields
            'ethnic_origin', 'race', 'ethnicity', 'body_type',
            'face_shape', 'nose_shape', 'lips_shape', 'hair_type',
            'skin_tone', 'freckles', 'favourite_lessons',
            'proven_fertility', 'hearing', 'vision', 'wearing_glasses', 'wearing_lenses',
            'surgeries', 'allergies', 'dental_history', 'twins_history',
            'alcohol_use', 'smoking_tobacco', 'vaping', 'drug_use', 'medications',
            'decl_anonymous', 'decl_genetic_tests', 'zodiac_sign',
            'fav_colour', 'fav_dish', 'fav_season', 'fav_holiday', 'fav_sport', 'fav_music',
            'childhood_dream', 'fav_author', 'fav_movie', 'countries_visited',
            'goals_in_life', 'idols_heroes', 'personality_words', 'strong_side', 'weak_side'
        );
        foreach ($donor_meta_fields as $field) {
            if (isset($_POST[$field])) {
                update_user_meta($user_id, $field, sanitize_textarea_field($_POST[$field]));
            }
        }

        // Save Medical & Family History
        if (isset($_POST['medical_history']) && is_array($_POST['medical_history'])) {
            $med_history = array();
            foreach ($_POST['medical_history'] as $key => $val) {
                $med_history[sanitize_key($key)] = sanitize_text_field($val);
            }
            update_user_meta($user_id, 'medical_history', $med_history);
        } else {
            update_user_meta($user_id, 'medical_history', array());
        }

        // Handle Profile Image Upload (Single)
        if (!empty($_FILES['profile_image']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $attachment_id = media_handle_upload('profile_image', 0);
            if (!is_wp_error($attachment_id)) {
                update_user_meta($user_id, 'profile_image', $attachment_id);
            }
        }

        // Handle Gallery Uploads (Multiple)
        if (!empty($_FILES['donor_gallery']['name'][0])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $files = $_FILES['donor_gallery'];
            $uploaded_attachments = array();

            foreach ($files['name'] as $key => $value) {
                if ($files['name'][$key]) {
                    $file = array(
                        'name'     => $files['name'][$key],
                        'type'     => $files['type'][$key],
                        'tmp_name' => $files['tmp_name'][$key],
                        'error'    => $files['error'][$key],
                        'size'     => $files['size'][$key]
                    );

                    $temp_key = "temp_admin_upload_" . $key;
                    $_FILES[$temp_key] = $file;

                    $file_type = wp_check_filetype(basename($file['name']));
                    $allowed_types = array('jpg', 'jpeg', 'png', 'gif');

                    if (in_array(strtolower($file_type['ext']), $allowed_types)) {
                        $attachment_id = media_handle_upload($temp_key, 0);

                        if (!is_wp_error($attachment_id)) {
                            $uploaded_attachments[] = $attachment_id;
                        }
                    }
                    unset($_FILES[$temp_key]);
                }
            }
            if (!empty($uploaded_attachments)) {
                update_user_meta($user_id, 'profile_images_gallery', $uploaded_attachments);
            }
        }
    } else {
        $u->set_role('um_intended_parent');
        update_user_meta($user_id, 'role', 'um_intended_parent');
        update_user_meta($user_id, 'community_role', 'um_intended_parent');
        
        // Save parent profile meta fields
        $parent_meta_fields = array(
            'country', 'parent_preferences', 'parent_notes'
        );
        foreach ($parent_meta_fields as $field) {
            if (isset($_POST[$field])) {
                update_user_meta($user_id, $field, sanitize_textarea_field($_POST[$field]));
            }
        }
    }

    wp_send_json_success(array('message' => 'User created successfully.'));
}
add_action('wp_ajax_ovarias_admin_create_user', 'ovarias_admin_ajax_create_user');

/**
 * AJAX Handler: Delete a Parent or Donor User permanently
 */
function ovarias_admin_ajax_delete_user() {
    check_ajax_referer('ovarias_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized action.'));
    }

    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

    if (!$user_id) {
        wp_send_json_error(array('message' => 'Invalid user ID.'));
    }

    // Load WordPress User delete core function (not available on frontend by default)
    if (!function_exists('wp_delete_user')) {
        require_once ABSPATH . 'wp-admin/includes/user.php';
    }

    // Delete user
    if (wp_delete_user($user_id)) {
        wp_send_json_success(array('message' => 'User deleted successfully.'));
    } else {
        wp_send_json_error(array('message' => 'Failed to delete user from database.'));
    }
}
add_action('wp_ajax_ovarias_admin_delete_user', 'ovarias_admin_ajax_delete_user');

/**
 * AJAX Handler: Submit Public General Inquiry
 */
function ovarias_public_ajax_submit_inquiry() {
    // Check security nonce (from localized parameter)
    check_ajax_referer('ovarias_admin_nonce', 'nonce');

    // Honeypot anti-spam check
    if (!empty($_POST['ovarias_hp'])) {
        wp_send_json_error(array('message' => 'Spam block triggered.'));
    }

    $type = isset($_POST['inquiry_type']) ? sanitize_text_field($_POST['inquiry_type']) : 'General inquiry';
    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
    $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
    $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';

    if (empty($email)) {
        wp_send_json_error(array('message' => 'Email address is required.'));
    }

    // Save inquiry to global option database
    $inquiries = get_option('ovarias_general_inquiries', array());
    $inq_id = uniqid('INQ_');
    $pay_date = current_time('mysql');
    
    $new_inquiry = array(
        'id' => $inq_id,
        'type' => $type,
        'email' => $email,
        'name' => $name,
        'phone' => $phone,
        'message' => $message,
        'status' => 'New',
        'date' => $pay_date
    );
    
    $inquiries[] = $new_inquiry;
    update_option('ovarias_general_inquiries', $inquiries);

    // Prepare email headers
    $site_name = get_bloginfo('name') ?: 'Ovarias';
    $admin_email = 'harjit.singh@bharatlogic.in';
    $from_email = 'no-reply@' . preg_replace('#^www\.#', '', strtolower($_SERVER['SERVER_NAME'] ?? 'ovarias.com'));

    $headers_admin = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $site_name . ' <' . $from_email . '>',
        'Reply-To: ' . ($name ? $name . ' ' : '') . '<' . $email . '>'
    );

    // Beautiful HTML email body for Admin
    $admin_subject = "New " . esc_html($type) . " received - " . $site_name;
    $admin_body = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #ffffff; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden;">';
    $admin_body .= '<div style="background: #555A4E; padding: 20px 24px; text-align: center; color: #ffffff;">';
    $admin_body .= '<h2 style="margin: 0; font-size: 20px; font-weight: bold; letter-spacing: 0.5px;">New Website Inquiry</h2>';
    $admin_body .= '<p style="margin: 6px 0 0 0; font-size: 13px; opacity: 0.9;">' . esc_html($type) . '</p>';
    $admin_body .= '</div>';
    $admin_body .= '<div style="padding: 24px;">';
    $admin_body .= '<table style="width: 100%; border-collapse: collapse; font-size: 14px; color: #333333;">';
    $admin_body .= '<tr style="border-bottom: 1px solid #f0f0f0;"><td style="padding: 10px 0; font-weight: bold; width: 30%; color: #666;">Inquiry Type:</td><td style="padding: 10px 0; font-weight: 600; color: #2e7d32;">' . esc_html($type) . '</td></tr>';
    $admin_body .= '<tr style="border-bottom: 1px solid #f0f0f0;"><td style="padding: 10px 0; font-weight: bold; color: #666;">Full Name:</td><td style="padding: 10px 0;">' . esc_html($name ?: 'Not provided') . '</td></tr>';
    $admin_body .= '<tr style="border-bottom: 1px solid #f0f0f0;"><td style="padding: 10px 0; font-weight: bold; color: #666;">Email:</td><td style="padding: 10px 0;"><a href="mailto:' . esc_attr($email) . '" style="color: #2e7d32; text-decoration: none; font-weight: bold;">' . esc_html($email) . '</a></td></tr>';
    $admin_body .= '<tr style="border-bottom: 1px solid #f0f0f0;"><td style="padding: 10px 0; font-weight: bold; color: #666;">Phone:</td><td style="padding: 10px 0;">' . esc_html($phone ?: 'Not provided') . '</td></tr>';
    $admin_body .= '<tr style="border-bottom: 1px solid #f0f0f0;"><td style="padding: 10px 0; font-weight: bold; color: #666;">Date:</td><td style="padding: 10px 0;">' . esc_html($pay_date) . '</td></tr>';
    $admin_body .= '</table>';
    $admin_body .= '<div style="margin-top: 20px; padding: 16px; background: #fafbf9; border-radius: 6px; border-left: 4px solid #555A4E;">';
    $admin_body .= '<p style="margin: 0 0 6px 0; font-weight: bold; font-size: 13px; color: #555A4E;">Message / Details:</p>';
    $admin_body .= '<p style="margin: 0; font-size: 14px; line-height: 1.6; color: #444; white-space: pre-wrap;">' . esc_html($message ?: 'No message text provided.') . '</p>';
    $admin_body .= '</div>';
    $admin_body .= '<div style="margin-top: 24px; text-align: center;">';
    $admin_body .= '<a href="mailto:' . esc_attr($email) . '" style="display: inline-block; background: #555A4E; color: #ffffff; padding: 10px 22px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 13px;">Reply to ' . esc_html($name ?: $email) . '</a>';
    $admin_body .= '</div>';
    $admin_body .= '</div>';
    $admin_body .= '<div style="background: #f7f7f7; padding: 12px 24px; text-align: center; font-size: 12px; color: #999;">This inquiry was recorded in your Ovarias Admin Dashboard.</div>';
    $admin_body .= '</div>';

    // Dispatch Admin Notification
    wp_mail($admin_email, $admin_subject, $admin_body, $headers_admin);

    // Send confirmation receipt to visitor
    if (!empty($email)) {
        $headers_visitor = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $site_name . ' <' . $from_email . '>'
        );

        $visitor_subject = "We have received your inquiry - " . $site_name;
        $visitor_body = '<div style="font-family: Arial, sans-serif; max-width: 550px; margin: 0 auto; background: #ffffff; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden;">';
        $visitor_body .= '<div style="background: #555A4E; padding: 20px 24px; text-align: center; color: #ffffff;">';
        $visitor_body .= '<h2 style="margin: 0; font-size: 20px; font-weight: bold;">Thank You for Contacting Us</h2>';
        $visitor_body .= '</div>';
        $visitor_body .= '<div style="padding: 24px; font-size: 14px; color: #444; line-height: 1.6;">';
        $visitor_body .= '<p>Hi <strong>' . esc_html($name ?: 'there') . '</strong>,</p>';
        $visitor_body .= '<p>Thank you for reaching out to <strong>' . esc_html($site_name) . '</strong>. We have received your inquiry regarding <em>"' . esc_html($type) . '"</em>.</p>';
        $visitor_body .= '<p>Our coordination team is currently reviewing your details and will get in touch with you shortly.</p>';
        $visitor_body .= '<div style="margin: 20px 0; padding: 14px; background: #fbfcf9; border-left: 3px solid #7E8372; font-size: 13px;">';
        $visitor_body .= '<strong>Your Inquiry Summary:</strong><br>';
        $visitor_body .= 'Type: ' . esc_html($type) . '<br>';
        if ($phone) {
            $visitor_body .= 'Phone: ' . esc_html($phone) . '<br>';
        }
        $visitor_body .= 'Date: ' . esc_html($pay_date) . '';
        $visitor_body .= '</div>';
        $visitor_body .= '<p style="margin-bottom: 0;">Warm regards,<br><strong>' . esc_html($site_name) . ' Coordination Team</strong></p>';
        $visitor_body .= '</div>';
        $visitor_body .= '</div>';

        wp_mail($email, $visitor_subject, $visitor_body, $headers_visitor);
    }

    wp_send_json_success(array('message' => 'Thank you! Your inquiry was submitted successfully.'));
}
// Allow public submissions (no privileges required)
add_action('wp_ajax_ovarias_public_submit_inquiry', 'ovarias_public_ajax_submit_inquiry');
add_action('wp_ajax_nopriv_ovarias_public_submit_inquiry', 'ovarias_public_ajax_submit_inquiry');

/**
 * AJAX Handler: Update General Inquiry Status
 */
function ovarias_admin_ajax_update_general_inquiry() {
    check_ajax_referer('ovarias_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized action.'));
    }

    $inq_id = isset($_POST['inquiry_id']) ? sanitize_text_field($_POST['inquiry_id']) : '';
    $new_status = isset($_POST['new_status']) ? sanitize_text_field($_POST['new_status']) : 'New';

    if (empty($inq_id)) {
        wp_send_json_error(array('message' => 'Invalid inquiry ID.'));
    }

    $inquiries = get_option('ovarias_general_inquiries', array());
    $updated = false;

    foreach ($inquiries as &$inq) {
        if ($inq['id'] === $inq_id) {
            $inq['status'] = $new_status;
            $updated = true;
            break;
        }
    }

    if ($updated) {
        update_option('ovarias_general_inquiries', $inquiries);
        wp_send_json_success(array('message' => 'Inquiry status updated.'));
    } else {
        wp_send_json_error(array('message' => 'Inquiry record not found.'));
    }
}
add_action('wp_ajax_ovarias_admin_update_general_inquiry', 'ovarias_admin_ajax_update_general_inquiry');

/**
 * AJAX Handler: Delete General Inquiry
 */
function ovarias_admin_ajax_delete_general_inquiry() {
    check_ajax_referer('ovarias_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized action.'));
    }

    $inq_id = isset($_POST['inquiry_id']) ? sanitize_text_field($_POST['inquiry_id']) : '';

    if (empty($inq_id)) {
        wp_send_json_error(array('message' => 'Invalid inquiry ID.'));
    }

    $inquiries = get_option('ovarias_general_inquiries', array());
    $filtered = array();
    $found = false;

    foreach ($inquiries as $inq) {
        if ($inq['id'] === $inq_id) {
            $found = true;
        } else {
            $filtered[] = $inq;
        }
    }

    if ($found) {
        update_option('ovarias_general_inquiries', $filtered);
        wp_send_json_success(array('message' => 'Inquiry deleted.'));
    } else {
        wp_send_json_error(array('message' => 'Inquiry record not found.'));
    }
}
add_action('wp_ajax_ovarias_admin_delete_general_inquiry', 'ovarias_admin_ajax_delete_general_inquiry');

/**
 * AJAX Handler: Delete Match Inquiry
 */
function ovarias_admin_ajax_delete_inquiry() {
    check_ajax_referer('ovarias_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized action.'));
    }

    $parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : 0;
    $inquiry_id = isset($_POST['inquiry_id']) ? sanitize_text_field($_POST['inquiry_id']) : '';

    if (!$parent_id || empty($inquiry_id)) {
        wp_send_json_error(array('message' => 'Invalid request identifiers.'));
    }

    $requests = get_user_meta($parent_id, 'ovarias_info_requests', true) ?: array();
    $updated = false;
    $new_requests = array();

    foreach ($requests as $req) {
        if ($req['id'] === $inquiry_id) {
            $updated = true;
            continue; // Skip the matching inquiry to delete it
        }
        $new_requests[] = $req;
    }

    if ($updated) {
        update_user_meta($parent_id, 'ovarias_info_requests', $new_requests);
        wp_send_json_success(array('message' => 'Match inquiry deleted successfully.'));
    } else {
        wp_send_json_error(array('message' => 'Inquiry record not found.'));
    }
}
add_action('wp_ajax_ovarias_admin_delete_inquiry', 'ovarias_admin_ajax_delete_inquiry');


