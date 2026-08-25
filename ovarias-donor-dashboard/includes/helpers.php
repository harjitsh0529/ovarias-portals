<?php

if (!defined('ABSPATH')) {
    exit;
}

function ovarias_profile_completion_percentage($user_id) {

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
        'about_me',
        'hobbies',
        'why_donate',
        'donor_id',
        'availability_status',
        'egg_type',
        'num_donations'
    );

    $egg_type = get_user_meta($user_id, 'egg_type', true);
    if ($egg_type === 'Frozen') {
        $fields[] = 'num_eggs';
        $fields[] = 'storage_country';
    }

    $completed = 0;

    foreach ($fields as $field) {

        if (!empty(get_user_meta($user_id, $field, true))) {

            $completed++;

        }

    }

    return round(
        ($completed / count($fields)) * 100
    );

}
