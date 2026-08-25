<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Log errors to WordPress option for easy administrator debugging
 */
function ovarias_log_zoho_error($code, $response_body) {
    update_option('ovarias_last_zoho_error', array(
        'timestamp' => current_time('mysql'),
        'code' => $code,
        'response' => $response_body
    ));
}

/**
 * Fetch Zoho Access Token from transient or generate a new one using refresh token
 */
function ovarias_get_zoho_access_token() {
    $cached_token = get_transient('ovarias_zoho_access_token');
    if ($cached_token) {
        return $cached_token;
    }

    $client_id = '1000.KXZWBAQPQ2I74RPBZDUNQTJ6HSWMUD';
    $client_secret = 'a80385852b2d01945e7313387730fd4ce665c51b5a';
    $refresh_token = '1000.692206426ae87a7dbff9678be454eef3.a2eb0eae8bdf41f7872459a8eda1edbb';

    $response = wp_remote_post(
        'https://accounts.zoho.eu/oauth/v2/token',
        array(
            'body' => array(
                'refresh_token' => $refresh_token,
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'grant_type' => 'refresh_token'
            ),
            'timeout' => 30
        )
    );

    if (is_wp_error($response)) {
        $msg = $response->get_error_message();
        error_log('Ovarias Zoho Token Error: ' . $msg);
        ovarias_log_zoho_error('TOKEN_HTTP_ERROR', $msg);
        return false;
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);
    
    if (isset($body['access_token'])) {
        // Cache the token for 55 minutes (3300 seconds)
        set_transient('ovarias_zoho_access_token', $body['access_token'], 3300);
        return $body['access_token'];
    }

    error_log('Ovarias Zoho Token Response Error: ' . print_r($body, true));
    ovarias_log_zoho_error('TOKEN_API_ERROR_' . $code, $body);
    return false;
}

/**
 * Fetch the donor record from Zoho CRM and cache the latest status in WordPress
 */
function ovarias_fetch_donor_from_zoho($user_id) {
    $access_token = ovarias_get_zoho_access_token();
    if (!$access_token) {
        return false;
    }

    $zoho_record_id = get_user_meta($user_id, 'zoho_record_id', true);
    $record = null;

    if ($zoho_record_id) {
        $url = 'https://www.zohoapis.eu/crm/v6/Donors/' . $zoho_record_id;
        $response = wp_remote_get(
            $url,
            array(
                'headers' => array(
                    'Authorization' => 'Zoho-oauthtoken ' . $access_token,
                ),
                'timeout' => 30
            )
        );

        if (!is_wp_error($response)) {
            $code = wp_remote_retrieve_response_code($response);
            if ($code === 200) {
                $body = json_decode(wp_remote_retrieve_body($response), true);
                if (!empty($body['data'][0])) {
                    $record = $body['data'][0];
                }
            } elseif ($code === 404) {
                // Stale cached ID, clear it
                delete_user_meta($user_id, 'zoho_record_id');
                $zoho_record_id = null;
            } else {
                $body = json_decode(wp_remote_retrieve_body($response), true);
                ovarias_log_zoho_error('FETCH_RECORD_ERROR_' . $code, $body);
            }
        } else {
            ovarias_log_zoho_error('FETCH_RECORD_HTTP_ERROR', $response->get_error_message());
        }
    }

    // If not found by cached ID, search by WordPress_User_ID
    if (!$zoho_record_id) {
        $search_url = 'https://www.zohoapis.eu/crm/v6/Donors/search?criteria=(' . rawurlencode('WordPress_User_ID:equals:' . $user_id) . ')';
        $response = wp_remote_get(
            $search_url,
            array(
                'headers' => array(
                    'Authorization' => 'Zoho-oauthtoken ' . $access_token,
                ),
                'timeout' => 30
            )
        );

        if (!is_wp_error($response)) {
            $code = wp_remote_retrieve_response_code($response);
            if ($code === 200) {
                $body = json_decode(wp_remote_retrieve_body($response), true);
                if (!empty($body['data'][0])) {
                    $record = $body['data'][0];
                    $zoho_record_id = $record['id'];
                    update_user_meta($user_id, 'zoho_record_id', $zoho_record_id);
                }
            } elseif ($code !== 204) { // 204 means no records found, which is fine
                $body = json_decode(wp_remote_retrieve_body($response), true);
                ovarias_log_zoho_error('SEARCH_RECORD_ERROR_' . $code, $body);
            }
        } else {
            ovarias_log_zoho_error('SEARCH_RECORD_HTTP_ERROR', $response->get_error_message());
        }
    }

    if ($record) {
        if (!empty($record['Donor_Status'])) {
            update_user_meta($user_id, 'donor_status', $record['Donor_Status']);
        }
        return $record;
    }

    return false;
}

/**
 * Synchronize WordPress user donor profile fields to Zoho CRM
 */
function ovarias_sync_donor_to_zoho($user_id) {
    $access_token = ovarias_get_zoho_access_token();
    if (!$access_token) {
        return false;
    }

    $user = get_userdata($user_id);
    if (!$user) {
        return false;
    }

    $profile_image_id = get_user_meta($user_id, 'profile_image', true);
    $profile_image_url = '';
    if ($profile_image_id) {
        $profile_image_url = wp_get_attachment_url($profile_image_id);
    }

    // Try to get existing record details first
    $existing_record = ovarias_fetch_donor_from_zoho($user_id);
    $zoho_record_id = get_user_meta($user_id, 'zoho_record_id', true);
    $current_status = $existing_record ? ($existing_record['Donor_Status'] ?? '') : '';

    $completion_pct = (int)ovarias_profile_completion_percentage($user_id);
    
    // Determine target Donor_Status
    $status_to_send = 'New Registration';
    if ($current_status) {
        // Retain existing advanced status unless it was Incomplete and is now complete
        if ($current_status === 'Profile Incomplete' && $completion_pct === 100) {
            $status_to_send = 'Profile Completed';
        } else {
            $status_to_send = $current_status;
        }
    } else {
        // If not created yet in Zoho
        $status_to_send = ($completion_pct === 100) ? 'Profile Completed' : 'Profile Incomplete';
    }

    // Format last updated date
    $last_update_mysql = get_user_meta($user_id, 'last_dashboard_update', true);
    $last_update_iso = $last_update_mysql ? wp_date('c', strtotime($last_update_mysql)) : wp_date('c');

    $data = array(
        'Name' => trim((get_user_meta($user_id, 'first_name', true) ?: $user->first_name) . ' ' . (get_user_meta($user_id, 'last_name', true) ?: $user->last_name)) ?: $user->user_login,
        'WordPress_User_ID' => (string)$user_id,
        'First_Name' => get_user_meta($user_id, 'first_name', true) ?: $user->first_name,
        'Last_Name' => get_user_meta($user_id, 'last_name', true) ?: $user->last_name,
        'Email' => $user->user_email,
        'Phone_Number' => get_user_meta($user_id, 'phone_number', true),
        'Country' => get_user_meta($user_id, 'country', true),
        
        'Date_of_Birth' => get_user_meta($user_id, 'dob', true) ?: null,
        'Nationality' => get_user_meta($user_id, 'nationality', true),
        'Height' => get_user_meta($user_id, 'height', true),
        'Weight' => get_user_meta($user_id, 'weight', true),
        'Blood_Group1' => get_user_meta($user_id, 'blood_group', true) ?: 'Unknown',
        'Eye_Colour' => get_user_meta($user_id, 'eye_colour', true) ?: 'Other',
        'Hair_Colour' => get_user_meta($user_id, 'hair_colour', true) ?: 'Other',
        
        'Level_of_Education' => get_user_meta($user_id, 'education_level', true) ?: 'Other',
        'Field_Of_Study' => get_user_meta($user_id, 'field_of_study', true),
        'Occupation' => get_user_meta($user_id, 'occupation', true),
        'Languages_Spoken' => get_user_meta($user_id, 'languages_spoken', true),
        
        'Donation_Type' => get_user_meta($user_id, 'donation_type', true) ?: 'Anonymous Donor',
        'Travel_Available' => (get_user_meta($user_id, 'travel_available', true) == 'Yes'),
        'Passport_Available' => (get_user_meta($user_id, 'passport_available', true) == 'Yes'),
        'Previous_Donations' => (get_user_meta($user_id, 'previous_donations', true) == 'Yes'),
        
        'About_Me' => get_user_meta($user_id, 'about_me', true),
        'Hobbies' => get_user_meta($user_id, 'hobbies', true),
        'Why_Donate' => get_user_meta($user_id, 'why_donate', true),
        
        'Profile_Completed' => ($completion_pct === 100),
        'Profile_Completion_Percentage' => $completion_pct,
        'Last_Dashboard_Update' => $last_update_iso,
        'Donor_Status' => $status_to_send
    );

    if ($profile_image_url) {
        $data['Record_Image'] = $profile_image_url;
    }

    if ($zoho_record_id) {
        // Update existing record (PUT)
        $url = 'https://www.zohoapis.eu/crm/v6/Donors/' . $zoho_record_id;
        $args = array(
            'method' => 'PUT',
            'headers' => array(
                'Authorization' => 'Zoho-oauthtoken ' . $access_token,
                'Content-Type' => 'application/json'
            ),
            'body' => wp_json_encode(array('data' => array($data))),
            'timeout' => 60
        );
    } else {
        // Create new record (POST)
        $url = 'https://www.zohoapis.eu/crm/v6/Donors';
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Authorization' => 'Zoho-oauthtoken ' . $access_token,
                'Content-Type' => 'application/json'
            ),
            'body' => wp_json_encode(array('data' => array($data))),
            'timeout' => 60
        );
    }

    $response = wp_remote_request($url, $args);

    if (is_wp_error($response)) {
        $msg = $response->get_error_message();
        error_log('Ovarias Zoho Sync Error: ' . $msg);
        ovarias_log_zoho_error('SYNC_HTTP_ERROR', $msg);
        return false;
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);

    if ($code === 200 || $code === 201) {
        // Clear any previous errors on success
        delete_option('ovarias_last_zoho_error');

        // Check if there was a data-level warning or error in the 200 OK body
        if (!empty($body['data'][0]['status']) && $body['data'][0]['status'] === 'error') {
            ovarias_log_zoho_error('SYNC_DATA_LEVEL_ERROR', $body);
            return false;
        }

        if (!empty($body['data'][0]['details']['id'])) {
            $new_zoho_id = $body['data'][0]['details']['id'];
            update_user_meta($user_id, 'zoho_record_id', $new_zoho_id);
        }
        // Cache the status we just sent
        update_user_meta($user_id, 'donor_status', $status_to_send);
        return true;
    }

    error_log('Ovarias Zoho Sync API Failure (' . $code . '): ' . print_r($body, true));
    ovarias_log_zoho_error('SYNC_API_ERROR_' . $code, $body);
    return false;
}
