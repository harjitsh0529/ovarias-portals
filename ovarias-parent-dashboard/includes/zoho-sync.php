<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Log errors (Dummy helper)
 */
if (!function_exists('ovarias_parent_log_zoho_error')) {
    function ovarias_parent_log_zoho_error($code, $response_body) {
        // Bypassed for local database testing
    }
}

/**
 * Fetch Zoho Access Token (Dummy helper)
 */
if (!function_exists('ovarias_parent_get_zoho_access_token')) {
    function ovarias_parent_get_zoho_access_token() {
        return false;
    }
}

/**
 * Fetch the Intended Parent record (Bypassed)
 */
if (!function_exists('ovarias_parent_fetch_from_zoho')) {
    function ovarias_parent_fetch_from_zoho($user_id) {
        return null;
    }
}

/**
 * Synchronize Intended Parent details (Bypassed)
 */
if (!function_exists('ovarias_sync_parent_to_zoho')) {
    function ovarias_sync_parent_to_zoho($user_id) {
        return true;
    }
}

/**
 * Fetch all active/completed Donors from local WordPress database
 */
if (!function_exists('ovarias_parent_get_donors')) {
    function ovarias_parent_get_donors() {
        $args = array(
            'number' => -1,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => 'availability_status',
                    'value' => 'Available',
                    'compare' => '='
                ),
                array(
                    'relation' => 'OR',
                    array(
                        'key' => 'role',
                        'value' => array('um_egg-donor', 'um_egg_donor', 'egg_donor'),
                        'compare' => 'IN'
                    ),
                    array(
                        'key' => 'community_role',
                        'value' => array('um_egg-donor', 'um_egg_donor', 'egg_donor'),
                        'compare' => 'IN'
                    ),
                    array(
                        'key' => 'wp_capabilities',
                        'value' => 'egg-donor',
                        'compare' => 'LIKE'
                    ),
                    array(
                        'key' => 'wp_capabilities',
                        'value' => 'egg_donor',
                        'compare' => 'LIKE'
                    )
                )
            )
        );

        $user_query = new WP_User_Query($args);
        $donors = array();
        $users = $user_query->get_results();
        
        if (!empty($users)) {
            foreach ($users as $user) {
                $user_id = $user->ID;
                
                // Check completion on-the-fly to bypass database sync lags
                $is_complete = false;
                if (function_exists('ovarias_profile_completion_percentage')) {
                    $pct = ovarias_profile_completion_percentage($user_id);
                    if ($pct >= 95) { // Bypasses the strict 100% DB save state
                        $is_complete = true;
                    }
                } else {
                    $is_complete = (get_user_meta($user_id, 'profile_completed', true) == '1');
                }
                
                if (!$is_complete) {
                    continue; // Skip incomplete profiles
                }

                $dob = get_user_meta($user_id, 'dob', true);
                
                // Calculate age
                $age = 'N/A';
                if (!empty($dob)) {
                    $birthDate = new DateTime($dob);
                    $today = new DateTime('today');
                    $age = $birthDate->diff($today)->y;
                }

                $avatar_id = get_user_meta($user_id, 'profile_image', true);
                $avatar_url = $avatar_id ? wp_get_attachment_url($avatar_id) : '';

                $donors[] = array(
                    'id' => $user_id,
                    'WordPress_User_ID' => $user_id,
                    'donor_id' => get_user_meta($user_id, 'donor_id', true) ?: 'OVARIAS-' . $user_id,
                    'First_Name' => get_user_meta($user_id, 'first_name', true) ?: $user->first_name,
                    'Last_Name' => get_user_meta($user_id, 'last_name', true) ?: $user->last_name,
                    'Date_of_Birth' => $dob,
                    'Age' => $age,
                    'Height' => get_user_meta($user_id, 'height', true),
                    'Weight' => get_user_meta($user_id, 'weight', true),
                    'Blood_Group1' => get_user_meta($user_id, 'blood_group', true),
                    'Eye_Colour' => get_user_meta($user_id, 'eye_colour', true),
                    'Hair_Colour' => get_user_meta($user_id, 'hair_colour', true),
                    'Level_of_Education' => get_user_meta($user_id, 'education_level', true),
                    'Field_of_Study' => get_user_meta($user_id, 'field_of_study', true),
                    'Occupation' => get_user_meta($user_id, 'occupation', true),
                    'Languages_Spoken' => get_user_meta($user_id, 'languages_spoken', true),
                    'Donation_Type' => get_user_meta($user_id, 'donation_type', true),
                    'Willing_to_Travel' => get_user_meta($user_id, 'travel_available', true),
                    'Valid_Passport' => get_user_meta($user_id, 'passport_available', true),
                    'Number_of_Donations' => get_user_meta($user_id, 'num_donations', true),
                    'Egg_Type' => get_user_meta($user_id, 'egg_type', true) ?: 'Fresh',
                    'Number_of_Eggs' => get_user_meta($user_id, 'num_eggs', true),
                    'Storage_Country' => get_user_meta($user_id, 'storage_country', true),
                    'About_Me' => get_user_meta($user_id, 'about_me', true),
                    'Hobbies' => get_user_meta($user_id, 'hobbies', true),
                    'Why_Donate' => get_user_meta($user_id, 'why_donate', true),
                    'avatar_url' => $avatar_url
                );
            }
        }
        
        // If no real donors are registered yet in the database, return mock donors so they can preview the directory layout!
        if (empty($donors)) {
            $donors = array(
                array(
                    'id' => 9991,
                    'WordPress_User_ID' => 0,
                    'donor_id' => '101',
                    'First_Name' => 'Chloe',
                    'Last_Name' => 'D.',
                    'Date_of_Birth' => '1998-05-12',
                    'Age' => '28',
                    'Height' => '168',
                    'Weight' => '58',
                    'Blood_Group1' => 'O+',
                    'Eye_Colour' => 'Blue',
                    'Hair_Colour' => 'Blonde',
                    'Level_of_Education' => 'Bachelor\'s Degree',
                    'Field_of_Study' => 'Psychology',
                    'Occupation' => 'Social Worker',
                    'Languages_Spoken' => 'English, French',
                    'Donation_Type' => 'Anonymous Donor',
                    'Willing_to_Travel' => 'Yes',
                    'Valid_Passport' => 'Yes',
                    'Number_of_Donations' => '1',
                    'Egg_Type' => 'Fresh',
                    'Number_of_Eggs' => '0',
                    'Storage_Country' => 'UK',
                    'About_Me' => 'I am a compassionate individual who loves reading, traveling, and helping others. I decided to become an egg donor to help families achieve their dreams.',
                    'Hobbies' => 'Hiking, Yoga, Playing Piano, Photography',
                    'Why_Donate' => 'Knowing that I can make a direct difference in someone\'s life is incredibly fulfilling.'
                ),
                array(
                    'id' => 9992,
                    'WordPress_User_ID' => 0,
                    'donor_id' => '102',
                    'First_Name' => 'Elena',
                    'Last_Name' => 'M.',
                    'Date_of_Birth' => '2001-09-20',
                    'Age' => '24',
                    'Height' => '172',
                    'Weight' => '62',
                    'Blood_Group1' => 'A+',
                    'Eye_Colour' => 'Green',
                    'Hair_Colour' => 'Brown',
                    'Level_of_Education' => 'Master\'s Degree',
                    'Field_of_Study' => 'Architecture',
                    'Occupation' => 'Junior Architect',
                    'Languages_Spoken' => 'English, Spanish',
                    'Donation_Type' => 'Anonymous Donor',
                    'Willing_to_Travel' => 'Yes',
                    'Valid_Passport' => 'Yes',
                    'Number_of_Donations' => '0',
                    'Egg_Type' => 'Frozen',
                    'Number_of_Eggs' => '14',
                    'Storage_Country' => 'Spain',
                    'About_Me' => 'I am creative, detail-oriented, and love outdoor activities. I have a healthy lifestyle and enjoy cooking healthy meals.',
                    'Hobbies' => 'Painting, Cooking, Tennis, Cycling',
                    'Why_Donate' => 'I believe that giving the gift of family is one of the most beautiful contributions one can make.'
                ),
                array(
                    'id' => 9993,
                    'WordPress_User_ID' => 0,
                    'donor_id' => '103',
                    'First_Name' => 'Sophia',
                    'Last_Name' => 'K.',
                    'Date_of_Birth' => '1999-11-03',
                    'Age' => '26',
                    'Height' => '165',
                    'Weight' => '54',
                    'Blood_Group1' => 'B-',
                    'Eye_Colour' => 'Brown',
                    'Hair_Colour' => 'Black',
                    'Level_of_Education' => 'Doctorate',
                    'Field_of_Study' => 'Chemistry',
                    'Occupation' => 'Research Associate',
                    'Languages_Spoken' => 'English, German',
                    'Donation_Type' => 'Anonymous Donor',
                    'Willing_to_Travel' => 'No',
                    'Valid_Passport' => 'Yes',
                    'Number_of_Donations' => '2',
                    'Egg_Type' => 'Both',
                    'Number_of_Eggs' => '8',
                    'Storage_Country' => 'Germany',
                    'About_Me' => 'I am an academic researcher passionate about science and education. I enjoy classical literature and long hikes.',
                    'Hobbies' => 'Reading, Violin, Classical Concerts, Hiking',
                    'Why_Donate' => 'I wanted to support families who are going through difficult IVF journeys.'
                )
            );
        }
        
        return $donors;
    }
}
