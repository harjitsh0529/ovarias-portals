<?php

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Get current profile data
$first_name = get_user_meta($user_id, 'first_name', true) ?: $current_user->first_name;
$last_name = get_user_meta($user_id, 'last_name', true) ?: $current_user->last_name;
$email = $current_user->user_email;

$phone = get_user_meta($user_id, 'phone_number', true);
$country = get_user_meta($user_id, 'country', true);

$dob = get_user_meta($user_id, 'dob', true);
$nationality = get_user_meta($user_id, 'nationality', true);

$height = get_user_meta($user_id, 'height', true);
$weight = get_user_meta($user_id, 'weight', true);

$blood_group = get_user_meta($user_id, 'blood_group', true);
$eye_colour = get_user_meta($user_id, 'eye_colour', true);
$hair_colour = get_user_meta($user_id, 'hair_colour', true);

$education_level = get_user_meta($user_id, 'education_level', true);
$field_of_study = get_user_meta($user_id, 'field_of_study', true);
$occupation = get_user_meta($user_id, 'occupation', true);
$languages_spoken = get_user_meta($user_id, 'languages_spoken', true);

$donation_type = get_user_meta($user_id, 'donation_type', true);
$travel_available = get_user_meta($user_id, 'travel_available', true);
$passport_available = get_user_meta($user_id, 'passport_available', true);
$previous_donations = get_user_meta($user_id, 'previous_donations', true);

$about_me = get_user_meta($user_id, 'about_me', true);
$hobbies = get_user_meta($user_id, 'hobbies', true);
$why_donate = get_user_meta($user_id, 'why_donate', true);

// New variables from Tech Brief
$donor_id = get_user_meta($user_id, 'donor_id', true) ?: 'OVARIAS-' . $user_id;
$availability_status = get_user_meta($user_id, 'availability_status', true) ?: 'Available';
$egg_type = get_user_meta($user_id, 'egg_type', true) ?: 'Fresh';
$num_eggs = get_user_meta($user_id, 'num_eggs', true) ?: '';
$storage_country = get_user_meta($user_id, 'storage_country', true) ?: '';
$num_donations = get_user_meta($user_id, 'num_donations', true) ?: '';

// New PDF fields variables
$ethnic_origin = get_user_meta($user_id, 'ethnic_origin', true);
$race = get_user_meta($user_id, 'race', true);
$ethnicity = get_user_meta($user_id, 'ethnicity', true);
$body_type = get_user_meta($user_id, 'body_type', true);
$face_shape = get_user_meta($user_id, 'face_shape', true);
$nose_shape = get_user_meta($user_id, 'nose_shape', true);
$lips_shape = get_user_meta($user_id, 'lips_shape', true);
$hair_type = get_user_meta($user_id, 'hair_type', true);
$skin_tone = get_user_meta($user_id, 'skin_tone', true);
$freckles = get_user_meta($user_id, 'freckles', true);
$favourite_lessons = get_user_meta($user_id, 'favourite_lessons', true);
$proven_fertility = get_user_meta($user_id, 'proven_fertility', true);
$hearing = get_user_meta($user_id, 'hearing', true);
$vision = get_user_meta($user_id, 'vision', true);
$wearing_glasses = get_user_meta($user_id, 'wearing_glasses', true);
$wearing_lenses = get_user_meta($user_id, 'wearing_lenses', true);
$surgeries = get_user_meta($user_id, 'surgeries', true);
$allergies = get_user_meta($user_id, 'allergies', true);
$dental_history = get_user_meta($user_id, 'dental_history', true);
$twins_history = get_user_meta($user_id, 'twins_history', true);
$alcohol_use = get_user_meta($user_id, 'alcohol_use', true);
$smoking_tobacco = get_user_meta($user_id, 'smoking_tobacco', true);
$vaping = get_user_meta($user_id, 'vaping', true);
$drug_use = get_user_meta($user_id, 'drug_use', true);
$medications = get_user_meta($user_id, 'medications', true);
$decl_anonymous = get_user_meta($user_id, 'decl_anonymous', true);
$decl_genetic_tests = get_user_meta($user_id, 'decl_genetic_tests', true);
$zodiac_sign = get_user_meta($user_id, 'zodiac_sign', true);
$fav_colour = get_user_meta($user_id, 'fav_colour', true);
$fav_dish = get_user_meta($user_id, 'fav_dish', true);
$fav_season = get_user_meta($user_id, 'fav_season', true);
$fav_holiday = get_user_meta($user_id, 'fav_holiday', true);
$fav_sport = get_user_meta($user_id, 'fav_sport', true);
$fav_music = get_user_meta($user_id, 'fav_music', true);
$childhood_dream = get_user_meta($user_id, 'childhood_dream', true);
$fav_author = get_user_meta($user_id, 'fav_author', true);
$fav_movie = get_user_meta($user_id, 'fav_movie', true);
$countries_visited = get_user_meta($user_id, 'countries_visited', true);
$goals_in_life = get_user_meta($user_id, 'goals_in_life', true);
$idols_heroes = get_user_meta($user_id, 'idols_heroes', true);
$personality_words = get_user_meta($user_id, 'personality_words', true);
$strong_side = get_user_meta($user_id, 'strong_side', true);
$weak_side = get_user_meta($user_id, 'weak_side', true);

$medical_history = get_user_meta($user_id, 'medical_history', true) ?: array();

// Recalculate completion percentage
$completion_pct = ovarias_profile_completion_percentage($user_id);

// Get current Zoho status
$donor_status = get_user_meta($user_id, 'donor_status', true) ?: 'New Registration';

// Profile avatar handling
$avatar_id = get_user_meta($user_id, 'profile_image', true);
$avatar_url = $avatar_id ? wp_get_attachment_url($avatar_id) : '';

?>

<div class="ovarias-dashboard">

    <!-- Notification Toasts / Alerts -->
    <?php if (isset($_GET['profile_updated'])): ?>
        <div class="ovarias-alert ovarias-alert-success">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            <span>Your profile has been successfully saved and synced to Zoho CRM.</span>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['sync_error'])): ?>
        <div class="ovarias-alert ovarias-alert-warning">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
            <span>Profile saved locally, but there was an issue syncing with Zoho CRM. We will try again on your next save.</span>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['upload_error'])): ?>
        <div class="ovarias-alert ovarias-alert-danger">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
            <span>Unable to upload photo. Please verify it is a valid image (JPG, PNG, or GIF).</span>
        </div>
    <?php endif; ?>

    <!-- Admin Debugging Box -->
    <?php 
    $last_error = get_option('ovarias_last_zoho_error'); 
    if ($last_error): 
    ?>
        <div class="ovarias-alert ovarias-alert-danger" style="display: block; text-align: left; padding: 25px; border-radius: 12px; margin-bottom: 30px;">
            <h4 style="margin: 0 0 10px 0; color: #C96464; font-size: 16px; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                Zoho CRM Sync Debugger
            </h4>
            <p style="margin: 0 0 8px 0; font-size: 14px;"><strong>Failed Sync Timestamp:</strong> <?php echo esc_html($last_error['timestamp']); ?></p>
            <p style="margin: 0 0 8px 0; font-size: 14px;"><strong>Error Code/Status:</strong> <?php echo esc_html($last_error['code']); ?></p>
            <div style="background: rgba(0,0,0,0.05); padding: 12px; border-radius: 6px; font-family: monospace; font-size: 12px; overflow-x: auto; white-space: pre-wrap; margin-top: 10px; color: #333;">
                <?php echo esc_html(print_r($last_error['response'], true)); ?>
            </div>
            <p style="margin: 10px 0 0 0; font-size: 12px; opacity: 0.8; font-style: italic;">Temporary notice: This box is currently visible to help us diagnose the integration.</p>
        </div>
    <?php endif; ?>

    <!-- Premium Dashboard Header -->
    <div class="ovarias-dashboard-header">
        <div class="ovarias-header-user">
            <div class="ovarias-avatar-wrapper">
                <?php if ($avatar_url): ?>
                    <img src="<?php echo esc_url($avatar_url); ?>" alt="Profile Photo" class="ovarias-user-avatar" id="avatar-preview">
                <?php else: ?>
                    <div class="ovarias-user-avatar-placeholder" id="avatar-preview-placeholder">
                        <span><?php echo esc_html(strtoupper(substr($first_name ?: $current_user->user_login, 0, 1))); ?></span>
                    </div>
                <?php endif; ?>
            </div>
            <div class="ovarias-user-info">
                <h2>Welcome, <?php echo esc_html($first_name ?: $current_user->user_login); ?></h2>
                <div style="margin-top: 15px; display: flex; gap: 10px;">
                    <button type="button" class="ovarias-submit-btn" id="btn-toggle-edit" style="padding: 10px 20px; font-size: 13px; border-radius: 6px; display: inline-flex; align-items: center; gap: 8px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="14 2 18 6 7 17 3 17 3 13 14 2"></polygon></svg>
                        <span>Edit Profile</span>
                    </button>
                    <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="ovarias-submit-btn" style="background: #a0a595; color: #fff; text-decoration: none; padding: 10px 20px; font-size: 13px; border-radius: 6px; display: inline-flex; align-items: center; gap: 8px; line-height: 1.2;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="ovarias-header-status">
            <div class="ovarias-status-badge">
                <span class="ovarias-status-label">Status</span>
                <span class="ovarias-status-value"><?php echo esc_html($donor_status); ?></span>
            </div>
            <div class="ovarias-progress-container">
                <div class="ovarias-progress-label">
                    <span>Profile Completion</span>
                    <strong><?php echo (int)$completion_pct; ?>%</strong>
                </div>
                <div class="ovarias-progress-bar-bg">
                    <div class="ovarias-progress-bar-fill" style="width: <?php echo (int)$completion_pct; ?>%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Profile Edit Form -->
    <form method="post" enctype="multipart/form-data" class="ovarias-form">
        
        <?php wp_nonce_field('ovarias_save_profile'); ?>

        <div class="ovarias-form-grid">
            
            <!-- Section: Personal Information -->
            <div class="ovarias-form-section">
                <h3>Personal Information</h3>
                
                <div class="ovarias-input-row">
                    <div class="ovarias-input-group">
                        <label for="donor_id">Donor ID</label>
                        <input type="text" id="donor_id" name="donor_id" value="<?php echo esc_attr($donor_id); ?>" placeholder="e.g. OVARIAS-1002">
                    </div>
                    <div class="ovarias-input-group">
                        <label for="availability_status">Availability Status</label>
                        <select id="availability_status" name="availability_status">
                            <option value="Available" <?php selected($availability_status, 'Available'); ?>>Available</option>
                            <option value="Reserved" <?php selected($availability_status, 'Reserved'); ?>>Reserved</option>
                            <option value="Temporarily Unavailable" <?php selected($availability_status, 'Temporarily Unavailable'); ?>>Temporarily Unavailable</option>
                            <option value="Not Available" <?php selected($availability_status, 'Not Available'); ?>>Not Available</option>
                        </select>
                    </div>
                </div>

                <div class="ovarias-input-group">
                    <label for="dob">Date of Birth</label>
                    <input type="date" id="dob" name="dob" value="<?php echo esc_attr($dob); ?>">
                </div>

                <div class="ovarias-input-group">
                    <label for="nationality">Nationality</label>
                    <input type="text" id="nationality" name="nationality" value="<?php echo esc_attr($nationality); ?>" placeholder="e.g. British">
                </div>

                <div class="ovarias-input-row">
                    <div class="ovarias-input-group">
                        <label for="height">Height (cm)</label>
                        <input type="text" id="height" name="height" value="<?php echo esc_attr($height); ?>" placeholder="e.g. 172 cm">
                    </div>
                    
                    <div class="ovarias-input-group">
                        <label for="weight">Weight (kg)</label>
                        <input type="text" id="weight" name="weight" value="<?php echo esc_attr($weight); ?>" placeholder="e.g. 62 kg">
                    </div>
                </div>

                <div class="ovarias-input-group">
                    <label for="blood_group">Blood Group</label>
                    <select id="blood_group" name="blood_group">
                        <?php 
                        $blood_options = array('Unknown', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-');
                        foreach ($blood_options as $opt) {
                            $selected = ($blood_group === $opt) ? 'selected' : '';
                            echo '<option value="' . esc_attr($opt) . '" ' . $selected . '>' . esc_html($opt) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="ovarias-input-row">
                    <div class="ovarias-input-group">
                        <label for="eye_colour">Eye Colour</label>
                        <select id="eye_colour" name="eye_colour">
                            <?php 
                            $eye_options = array('Other', 'Brown', 'Blue', 'Black', 'Green', 'Gray', 'Hazel');
                            foreach ($eye_options as $opt) {
                                $selected = ($eye_colour === $opt) ? 'selected' : '';
                                echo '<option value="' . esc_attr($opt) . '" ' . $selected . '>' . esc_html($opt) . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="ovarias-input-group">
                        <label for="hair_colour">Hair Colour</label>
                        <select id="hair_colour" name="hair_colour">
                            <?php 
                            $hair_options = array('Other', 'Black', 'Brown', 'Blonde', 'Red', 'Gray');
                            foreach ($hair_options as $opt) {
                                $selected = ($hair_colour === $opt) ? 'selected' : '';
                                echo '<option value="' . esc_attr($opt) . '" ' . $selected . '>' . esc_html($opt) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="ovarias-input-row">
                    <div class="ovarias-input-group">
                        <label for="ethnic_origin">Ethnic Origin</label>
                        <input type="text" id="ethnic_origin" name="ethnic_origin" value="<?php echo esc_attr($ethnic_origin); ?>" placeholder="e.g. European">
                    </div>
                    <div class="ovarias-input-group">
                        <label for="race">Race</label>
                        <input type="text" id="race" name="race" value="<?php echo esc_attr($race); ?>" placeholder="e.g. Caucasian">
                    </div>
                </div>
                <div class="ovarias-input-row">
                    <div class="ovarias-input-group">
                        <label for="ethnicity">Ethnicity</label>
                        <input type="text" id="ethnicity" name="ethnicity" value="<?php echo esc_attr($ethnicity); ?>" placeholder="e.g. Ukrainian">
                    </div>
                    <div class="ovarias-input-group">
                        <label for="body_type">Body Type</label>
                        <input type="text" id="body_type" name="body_type" value="<?php echo esc_attr($body_type); ?>" placeholder="e.g. Middle, Slim">
                    </div>
                </div>
            </div>

            <!-- Section: Facial, Hair & Skin Features -->
            <div class="ovarias-form-section">
                <h3>Facial, Hair & Skin Features</h3>
                <div class="ovarias-input-row">
                    <div class="ovarias-input-group">
                        <label for="face_shape">Face Shape</label>
                        <input type="text" id="face_shape" name="face_shape" value="<?php echo esc_attr($face_shape); ?>" placeholder="e.g. Oval">
                    </div>
                    <div class="ovarias-input-group">
                        <label for="nose_shape">Nose Shape / Size</label>
                        <input type="text" id="nose_shape" name="nose_shape" value="<?php echo esc_attr($nose_shape); ?>" placeholder="e.g. Normal Small Straight">
                    </div>
                </div>
                <div class="ovarias-input-row">
                    <div class="ovarias-input-group">
                        <label for="lips_shape">Lips Shape</label>
                        <input type="text" id="lips_shape" name="lips_shape" value="<?php echo esc_attr($lips_shape); ?>" placeholder="e.g. Thin Normal">
                    </div>
                    <div class="ovarias-input-group">
                        <label for="hair_type">Hair Type</label>
                        <input type="text" id="hair_type" name="hair_type" value="<?php echo esc_attr($hair_type); ?>" placeholder="e.g. Straight, Wavy">
                    </div>
                </div>
                <div class="ovarias-input-row">
                    <div class="ovarias-input-group">
                        <label for="skin_tone">Skin Tone</label>
                        <input type="text" id="skin_tone" name="skin_tone" value="<?php echo esc_attr($skin_tone); ?>" placeholder="e.g. Tan, Fair">
                    </div>
                    <div class="ovarias-input-group">
                        <label for="freckles">Freckles</label>
                        <input type="text" id="freckles" name="freckles" value="<?php echo esc_attr($freckles); ?>" placeholder="e.g. Few, None">
                    </div>
                </div>
            </div>

            <!-- Section: Education & Occupation -->
            <div class="ovarias-form-section">
                <h3>Education & Occupation</h3>

                <div class="ovarias-input-group">
                    <label for="education_level">Education Level</label>
                    <select id="education_level" name="education_level">
                        <?php 
                        $edu_options = array('Other', 'High School', 'College', "Bachelor's Degree", "Master's Degree", 'Doctorate');
                        foreach ($edu_options as $opt) {
                            $selected = ($education_level === $opt) ? 'selected' : '';
                            echo '<option value="' . esc_attr($opt) . '" ' . $selected . '>' . esc_html($opt) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="ovarias-input-group">
                    <label for="field_of_study">Field of Study</label>
                    <input type="text" id="field_of_study" name="field_of_study" value="<?php echo esc_attr($field_of_study); ?>" placeholder="e.g. Biology, Arts">
                </div>

                <div class="ovarias-input-group">
                    <label for="occupation">Occupation</label>
                    <input type="text" id="occupation" name="occupation" value="<?php echo esc_attr($occupation); ?>" placeholder="e.g. Student, Graphic Designer">
                </div>

                <div class="ovarias-input-group">
                    <label for="languages_spoken">Languages Spoken</label>
                    <textarea id="languages_spoken" name="languages_spoken" placeholder="e.g. English, Spanish (conversational)"><?php echo esc_textarea($languages_spoken); ?></textarea>
                </div>

                <div class="ovarias-input-group">
                    <label for="favourite_lessons">Favourite Lessons / Subjects</label>
                    <input type="text" id="favourite_lessons" name="favourite_lessons" value="<?php echo esc_attr($favourite_lessons); ?>" placeholder="e.g. Nature studies, Biology">
                </div>
            </div>

            <!-- Section: Donation Details -->
            <div class="ovarias-form-section">
                <h3>Donation Preferences</h3>

                <div class="ovarias-input-group">
                    <label for="donation_type">Donation Type</label>
                    <select id="donation_type" name="donation_type">
                        <?php 
                        $donation_options = array('Anonymous Donor', 'First Time Donor', 'Repeat Donor', 'Known Donor');
                        foreach ($donation_options as $opt) {
                            $selected = ($donation_type === $opt) ? 'selected' : '';
                            echo '<option value="' . esc_attr($opt) . '" ' . $selected . '>' . esc_html($opt) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="ovarias-input-row">
                    <div class="ovarias-input-group">
                        <label for="travel_available">Willing to Travel</label>
                        <select id="travel_available" name="travel_available">
                            <option value="Yes" <?php selected($travel_available, 'Yes'); ?>>Yes</option>
                            <option value="No" <?php selected($travel_available, 'No'); ?>>No</option>
                        </select>
                    </div>

                    <div class="ovarias-input-group">
                        <label for="passport_available">Valid Passport Available</label>
                        <select id="passport_available" name="passport_available">
                            <option value="Yes" <?php selected($passport_available, 'Yes'); ?>>Yes</option>
                            <option value="No" <?php selected($passport_available, 'No'); ?>>No</option>
                        </select>
                    </div>
                </div>

                <div class="ovarias-input-row">
                    <div class="ovarias-input-group">
                        <label for="num_donations">Number of Previous Donations</label>
                        <input type="number" id="num_donations" name="num_donations" min="0" value="<?php echo esc_attr($num_donations); ?>" placeholder="e.g. 2">
                    </div>
                    
                    <div class="ovarias-input-group">
                        <label for="egg_type">Egg Type Category</label>
                        <select id="egg_type" name="egg_type">
                            <option value="Fresh" <?php selected($egg_type, 'Fresh'); ?>>Fresh Egg Donor</option>
                            <option value="Frozen" <?php selected($egg_type, 'Frozen'); ?>>Frozen Egg Donor</option>
                            <option value="Both" <?php selected($egg_type, 'Both'); ?>>Both</option>
                        </select>
                    </div>
                </div>

                <!-- Conditional Frozen Donor Fields -->
                <div id="frozen-donor-fields" class="ovarias-input-row" style="<?php echo ($egg_type === 'Frozen' || $egg_type === 'Both') ? 'display: flex;' : 'display: none;'; ?>">
                    <div class="ovarias-input-group">
                        <label for="num_eggs">Number of Eggs Available</label>
                        <input type="number" id="num_eggs" name="num_eggs" min="0" value="<?php echo esc_attr($num_eggs); ?>" placeholder="e.g. 12">
                    </div>
                    <div class="ovarias-input-group">
                        <label for="storage_country">Storage Country</label>
                        <input type="text" id="storage_country" name="storage_country" value="<?php echo esc_attr($storage_country); ?>" placeholder="e.g. Spain">
                    </div>
                </div>
            </div>

            <!-- Section: Health, Lifestyle & Declarations -->
            <div class="ovarias-form-section">
                <h3>Health, Lifestyle & Declarations</h3>
                
                <div class="ovarias-input-row">
                    <div class="ovarias-input-group">
                        <label for="proven_fertility">Proven Fertility</label>
                        <select id="proven_fertility" name="proven_fertility">
                            <option value="">Select</option>
                            <option value="Yes" <?php selected($proven_fertility, 'Yes'); ?>>Yes</option>
                            <option value="No" <?php selected($proven_fertility, 'No'); ?>>No</option>
                        </select>
                    </div>
                    <div class="ovarias-input-group">
                        <label for="hearing">Hearing</label>
                        <input type="text" id="hearing" name="hearing" value="<?php echo esc_attr($hearing); ?>" placeholder="e.g. Great, Normal">
                    </div>
                </div>

                <div class="ovarias-input-row">
                    <div class="ovarias-input-group">
                        <label for="vision">Vision</label>
                        <input type="text" id="vision" name="vision" value="<?php echo esc_attr($vision); ?>" placeholder="e.g. Great, Normal">
                    </div>
                    <div class="ovarias-input-group">
                        <label for="wearing_glasses">Inclination to Wearing Glasses</label>
                        <select id="wearing_glasses" name="wearing_glasses">
                            <option value="">Select</option>
                            <option value="Yes" <?php selected($wearing_glasses, 'Yes'); ?>>Yes</option>
                            <option value="No" <?php selected($wearing_glasses, 'No'); ?>>No</option>
                        </select>
                    </div>
                </div>

                <div class="ovarias-input-row">
                    <div class="ovarias-input-group">
                        <label for="wearing_lenses">Wearing Contact Lenses</label>
                        <select id="wearing_lenses" name="wearing_lenses">
                            <option value="">Select</option>
                            <option value="Yes" <?php selected($wearing_lenses, 'Yes'); ?>>Yes</option>
                            <option value="No" <?php selected($wearing_lenses, 'No'); ?>>No</option>
                        </select>
                    </div>
                    <div class="ovarias-input-group">
                        <label for="surgeries">History of Surgeries</label>
                        <input type="text" id="surgeries" name="surgeries" value="<?php echo esc_attr($surgeries); ?>" placeholder="e.g. No, Appendix surgery (2018)">
                    </div>
                </div>

                <div class="ovarias-input-row">
                    <div class="ovarias-input-group">
                        <label for="allergies">Allergies</label>
                        <input type="text" id="allergies" name="allergies" value="<?php echo esc_attr($allergies); ?>" placeholder="e.g. No, Peanuts">
                    </div>
                    <div class="ovarias-input-group">
                        <label for="dental_history">Dental / Orthodontic History</label>
                        <input type="text" id="dental_history" name="dental_history" value="<?php echo esc_attr($dental_history); ?>" placeholder="e.g. No, Braces in childhood">
                    </div>
                </div>

                <div class="ovarias-input-row">
                    <div class="ovarias-input-group">
                        <label for="twins_history">Twins Family History</label>
                        <input type="text" id="twins_history" name="twins_history" value="<?php echo esc_attr($twins_history); ?>" placeholder="e.g. No, Twins on maternal side">
                    </div>
                    <div class="ovarias-input-group">
                        <label for="alcohol_use">Alcohol Use</label>
                        <select id="alcohol_use" name="alcohol_use">
                            <option value="">Select</option>
                            <option value="No" <?php selected($alcohol_use, 'No'); ?>>No</option>
                            <option value="Socially" <?php selected($alcohol_use, 'Socially'); ?>>Socially</option>
                            <option value="Yes" <?php selected($alcohol_use, 'Yes'); ?>>Yes</option>
                        </select>
                    </div>
                </div>

                <div class="ovarias-input-row">
                    <div class="ovarias-input-group">
                        <label for="smoking_tobacco">Smoking (Tobacco)</label>
                        <select id="smoking_tobacco" name="smoking_tobacco">
                            <option value="">Select</option>
                            <option value="No" <?php selected($smoking_tobacco, 'No'); ?>>No</option>
                            <option value="Yes" <?php selected($smoking_tobacco, 'Yes'); ?>>Yes</option>
                        </select>
                    </div>
                    <div class="ovarias-input-group">
                        <label for="vaping">Vaping (Nicotine/THC)</label>
                        <select id="vaping" name="vaping">
                            <option value="">Select</option>
                            <option value="No" <?php selected($vaping, 'No'); ?>>No</option>
                            <option value="Yes" <?php selected($vaping, 'Yes'); ?>>Yes</option>
                        </select>
                    </div>
                </div>

                <div class="ovarias-input-row">
                    <div class="ovarias-input-group">
                        <label for="drug_use">Recreational Drug Use</label>
                        <select id="drug_use" name="drug_use">
                            <option value="">Select</option>
                            <option value="No" <?php selected($drug_use, 'No'); ?>>No</option>
                            <option value="Yes" <?php selected($drug_use, 'Yes'); ?>>Yes</option>
                        </select>
                    </div>
                    <div class="ovarias-input-group">
                        <label for="medications">Medications</label>
                        <input type="text" id="medications" name="medications" value="<?php echo esc_attr($medications); ?>" placeholder="e.g. No, Vitamin C">
                    </div>
                </div>

                <div class="ovarias-input-row">
                    <div class="ovarias-input-group">
                        <label for="decl_anonymous">Do you understand you’re contributing genetic material while remaining anonymous?</label>
                        <select id="decl_anonymous" name="decl_anonymous">
                            <option value="">Select</option>
                            <option value="YES" <?php selected($decl_anonymous, 'YES'); ?>>YES</option>
                            <option value="NO" <?php selected($decl_anonymous, 'NO'); ?>>NO</option>
                        </select>
                    </div>
                    <div class="ovarias-input-group">
                        <label for="decl_genetic_tests">Are you open to taking a genetic test?</label>
                        <select id="decl_genetic_tests" name="decl_genetic_tests">
                            <option value="">Select</option>
                            <option value="YES" <?php selected($decl_genetic_tests, 'YES'); ?>>YES</option>
                            <option value="NO" <?php selected($decl_genetic_tests, 'NO'); ?>>NO</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Section: Profile Photo & Upload -->
            <div class="ovarias-form-section" style="grid-column: 1 / -1; display: grid; grid-template-columns: 1fr 1fr; gap: 30px; border-bottom: 1px solid var(--ovarias-border); padding-bottom: 30px;">
                <!-- Column 1: Primary Profile Picture -->
                <div style="border-right: 1px solid var(--ovarias-border); padding-right: 30px;">
                    <h3>Primary Profile Picture</h3>
                    <div class="ovarias-photo-upload-container" id="profile-photo-upload-container" style="height: auto; min-height: auto;">
                        <?php if ($avatar_url): ?>
                            <div class="ovarias-uploaded-image-preview" style="text-align: center;">
                                <img src="<?php echo esc_url($avatar_url); ?>" alt="Uploaded Profile Photo" id="profile-image-preview-element" style="max-width: 100%; max-height: 250px; border-radius: var(--ovarias-radius-sm); border: 1px solid var(--ovarias-border); object-fit: cover; display: block; margin: 0 auto 15px auto;">
                                
                                <div style="display: flex; justify-content: center; gap: 10px; margin-top: 15px;">
                                    <button type="button" class="ovarias-submit-btn ovarias-btn-change-photo" id="btn-trigger-upload" style="padding: 10px 18px; font-size: 13px; border-radius: 6px; display: none;">
                                        Change Photo
                                    </button>
                                    <a href="<?php echo esc_url(add_query_arg('delete_profile_photo', '1')); ?>" class="ovarias-submit-btn btn-delete-profile-photo" style="background: #c62828; color: #fff; text-decoration: none; display: none; align-items: center; justify-content: center; height: 38px; padding: 0 20px; font-size: 13px; border-radius: 6px; font-weight: bold; margin: 0;">
                                        Delete Photo
                                    </a>
                                </div>
                                <!-- Kept hidden inside the preview container -->
                                <input type="file" id="profile_image" name="profile_image" accept="image/*" style="display: none;">
                            </div>
                        <?php else: ?>
                            <div class="ovarias-file-dropzone" id="dropzone-area-profile" style="display: none; margin-top: 15px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                                <p>Drag your profile photo here or click to browse</p>
                                <span class="ovarias-file-formats">JPEG, PNG or GIF (max. 5MB)</span>
                                <input type="file" id="profile_image" name="profile_image" accept="image/*">
                            </div>
                            <div class="ovarias-no-photo-notice" id="no-photo-notice-profile" style="color: var(--ovarias-text); text-align: center; padding: 40px 20px; font-style: italic; border: 1px dashed var(--ovarias-border); border-radius: var(--ovarias-radius-md); background: var(--ovarias-bg-soft);">
                                No profile photo uploaded. Click "Edit Profile" to add one.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Column 2: Additional Photos (Gallery) -->
                <div>
                    <h3>Additional Photos (Gallery)</h3>
                    
                    <!-- Display Current Gallery Images -->
                    <?php 
                    $gallery_ids = get_user_meta($user_id, 'profile_images_gallery', true) ?: array();
                    ?>

                    <div class="ovarias-photo-gallery-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 15px; margin-bottom: 20px;">
                        <?php foreach ($gallery_ids as $attachment_id): 
                            $img_url = wp_get_attachment_image_url($attachment_id, 'large');
                            if ($img_url): ?>
                                <div class="ovarias-gallery-item-card" style="position: relative; border: 1px solid var(--ovarias-border); border-radius: var(--ovarias-radius-sm); overflow: hidden; background: #fff; padding: 8px; text-align: center;">
                                    <img src="<?php echo esc_url($img_url); ?>" style="width: 100%; height: 130px; object-fit: cover; border-radius: 4px; display: block; margin-bottom: 8px;">
                                    <!-- Delete Button (via query param redirection) -->
                                    <a href="<?php echo esc_url(add_query_arg('delete_gallery_image', $attachment_id)); ?>" class="ovarias-submit-btn" style="background: #c62828; color: #fff; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; width: 100%; box-sizing: border-box; height: 30px; font-size: 11px; border-radius: var(--ovarias-radius-sm); font-weight: bold; margin: 0; padding: 0;">
                                        Delete
                                    </a>
                                </div>
                            <?php endif; 
                        endforeach; ?>
                    </div>

                    <div class="ovarias-photo-upload-container" id="gallery-photo-upload-container" style="height: auto; min-height: auto;">
                        <div class="ovarias-file-dropzone" id="dropzone-area-gallery" style="display: none;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                            <p>Drag additional photos here or click to browse</p>
                            <span class="ovarias-file-formats">JPEG, PNG or GIF (max. 5MB per file)</span>
                            <input type="file" id="donor_gallery" name="donor_gallery[]" accept="image/*" multiple>
                        </div>
                        <?php if (empty($gallery_ids)): ?>
                            <div class="ovarias-no-photo-notice" id="no-photo-notice-gallery" style="color: var(--ovarias-text); text-align: center; padding: 40px 20px; font-style: italic; border: 1px dashed var(--ovarias-border); border-radius: var(--ovarias-radius-md); background: var(--ovarias-bg-soft);">
                                No gallery photos uploaded yet. Click "Edit Profile" to add them.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>

        <!-- Section: Text Areas / About Me -->
        <div class="ovarias-full-width-section">
            <h3>More About You</h3>

            <div class="ovarias-input-group">
                <label for="about_me">About Me</label>
                <textarea id="about_me" name="about_me" placeholder="Describe your personality, values, and life philosophy..."><?php echo esc_textarea($about_me); ?></textarea>
            </div>

            <div class="ovarias-input-group">
                <label style="font-weight: bold; margin-bottom: 10px; display: block; color: var(--ovarias-text-dark);">Preferences & Additional Details</label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div>
                        <label for="zodiac_sign" style="font-size: 13px; font-weight: normal; color: #555;">Sign of the Zodiac</label>
                        <input type="text" id="zodiac_sign" name="zodiac_sign" value="<?php echo esc_attr($zodiac_sign); ?>" placeholder="e.g. Capricorn">
                    </div>
                    <div>
                        <label for="fav_colour" style="font-size: 13px; font-weight: normal; color: #555;">Favourite Colour</label>
                        <input type="text" id="fav_colour" name="fav_colour" value="<?php echo esc_attr($fav_colour); ?>" placeholder="e.g. Amber Gold">
                    </div>
                    <div>
                        <label for="fav_dish" style="font-size: 13px; font-weight: normal; color: #555;">Favourite Dish</label>
                        <input type="text" id="fav_dish" name="fav_dish" value="<?php echo esc_attr($fav_dish); ?>" placeholder="e.g. Varenyky">
                    </div>
                    <div>
                        <label for="fav_season" style="font-size: 13px; font-weight: normal; color: #555;">Favourite Season</label>
                        <input type="text" id="fav_season" name="fav_season" value="<?php echo esc_attr($fav_season); ?>" placeholder="e.g. Late Summer">
                    </div>
                    <div>
                        <label for="fav_holiday" style="font-size: 13px; font-weight: normal; color: #555;">Favourite Holiday</label>
                        <input type="text" id="fav_holiday" name="fav_holiday" value="<?php echo esc_attr($fav_holiday); ?>" placeholder="e.g. Christmas Eve">
                    </div>
                    <div>
                        <label for="fav_sport" style="font-size: 13px; font-weight: normal; color: #555;">Favourite Kind of Sport</label>
                        <input type="text" id="fav_sport" name="fav_sport" value="<?php echo esc_attr($fav_sport); ?>" placeholder="e.g. Rock climbing">
                    </div>
                    <div>
                        <label for="fav_music" style="font-size: 13px; font-weight: normal; color: #555;">Favourite Musical Style</label>
                        <input type="text" id="fav_music" name="fav_music" value="<?php echo esc_attr($fav_music); ?>" placeholder="e.g. Synthwave">
                    </div>
                    <div>
                        <label for="childhood_dream" style="font-size: 13px; font-weight: normal; color: #555;">Childhood Dream</label>
                        <input type="text" id="childhood_dream" name="childhood_dream" value="<?php echo esc_attr($childhood_dream); ?>" placeholder="e.g. To score soundtracks">
                    </div>
                    <div>
                        <label for="fav_author" style="font-size: 13px; font-weight: normal; color: #555;">Favourite Author / Book</label>
                        <input type="text" id="fav_author" name="fav_author" value="<?php echo esc_attr($fav_author); ?>" placeholder="e.g. Dune by Frank Herbert">
                    </div>
                    <div>
                        <label for="fav_movie" style="font-size: 13px; font-weight: normal; color: #555;">Favourite Movie</label>
                        <input type="text" id="fav_movie" name="fav_movie" value="<?php echo esc_attr($fav_movie); ?>" placeholder="e.g. Atmospheric sci-fi">
                    </div>
                    <div>
                        <label for="countries_visited" style="font-size: 13px; font-weight: normal; color: #555;">Countries Visited</label>
                        <input type="text" id="countries_visited" name="countries_visited" value="<?php echo esc_attr($countries_visited); ?>" placeholder="e.g. Austria, Norway">
                    </div>
                    <div>
                        <label for="goals_in_life" style="font-size: 13px; font-weight: normal; color: #555;">Goals in Life</label>
                        <input type="text" id="goals_in_life" name="goals_in_life" value="<?php echo esc_attr($goals_in_life); ?>" placeholder="e.g. To open an independent audio studio">
                    </div>
                    <div>
                        <label for="idols_heroes" style="font-size: 13px; font-weight: normal; color: #555;">Idols / Heroes</label>
                        <input type="text" id="idols_heroes" name="idols_heroes" value="<?php echo esc_attr($idols_heroes); ?>" placeholder="e.g. Electronic music composers">
                    </div>
                    <div>
                        <label for="personality_words" style="font-size: 13px; font-weight: normal; color: #555;">Words Describing Personality</label>
                        <input type="text" id="personality_words" name="personality_words" value="<?php echo esc_attr($personality_words); ?>" placeholder="e.g. Analytical, calm, curious">
                    </div>
                    <div>
                        <label for="strong_side" style="font-size: 13px; font-weight: normal; color: #555;">Strong Side</label>
                        <input type="text" id="strong_side" name="strong_side" value="<?php echo esc_attr($strong_side); ?>" placeholder="e.g. Deep focus">
                    </div>
                    <div>
                        <label for="weak_side" style="font-size: 13px; font-weight: normal; color: #555;">Weak Side</label>
                        <input type="text" id="weak_side" name="weak_side" value="<?php echo esc_attr($weak_side); ?>" placeholder="e.g. Getting overly lost">
                    </div>
                </div>
            </div>

            <div class="ovarias-input-group">
                <label for="hobbies">Hobbies & Interests</label>
                <textarea id="hobbies" name="hobbies" placeholder="What are your favorite activities, sports, reading, etc.?"><?php echo esc_textarea($hobbies); ?></textarea>
            </div>

            <div class="ovarias-input-group">
                <label for="why_donate">Why do you want to donate?</label>
                <textarea id="why_donate" name="why_donate" placeholder="Share your motivation for becoming an egg donor..."><?php echo esc_textarea($why_donate); ?></textarea>
            </div>
        </div>

        <!-- Section: Medical & Family History -->
        <div class="ovarias-full-width-section" style="margin-top: 30px; border-top: 1px solid var(--ovarias-border); padding-top: 30px;">
            <h3>Donor Medical & Family History</h3>
            <p style="margin-bottom: 20px; font-size: 13px; color: #666; font-style: italic;">Please check the box next to any condition if you or anyone in your biological family has a history of it. Leave unchecked for "No".</p>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 15px; margin-bottom: 20px;">
                <?php
                $conditions = array(
                    'heart_disease_under40' => 'Heart Disease (under 40)',
                    'heart_attack_under40' => 'Heart Attack (under 40)',
                    'high_blood_pressure' => 'High Blood Pressure',
                    'other_heart_conditions' => 'Other Heart Conditions',
                    'hemophilia' => 'Hemophilia',
                    'sickle_cell_anemia' => 'Sickle Cell Anemia',
                    'lymphoma' => 'Lymphoma',
                    'other_blood_disorders' => 'Other Blood Disorders',
                    'emphysema_copd' => 'Emphysema / COPD',
                    'lung_cancer' => 'Lung Cancer',
                    'environmental_allergies' => 'Environmental Allergies',
                    'neurofibromatosis' => 'Neurofibromatosis',
                    'skin_cancer_melanoma' => 'Skin Cancer / Melanoma',
                    'eczema' => 'Eczema',
                    'pigmentation_disorders' => 'Pigmentation Disorders',
                    'rectal_cancer' => 'Colo-Rectal Cancer',
                    'crohns_disease' => "Crohn's Disease",
                    'cystic_fibrosis' => 'Cystic Fibrosis',
                    'liver_disease' => 'Liver Disease',
                    'schizophrenia' => 'Schizophrenia',
                    'bipolar_disorder' => 'Bipolar Disorder',
                    'depression' => 'Depression',
                    'suicide' => 'Suicide',
                    'congenital_heart_malformation' => 'Congenital Heart Malformation',
                    'arthritis' => 'Arthritis',
                    'congenital_spine_malformation' => 'Congenital Spine Malformation',
                    'dwarfism' => 'Dwarfism',
                    'muscular_dystrophy' => 'Muscular Dystrophy',
                    'osteoporosis' => 'Osteoporosis',
                    'congenital_blindness' => 'Congenital Blindness',
                    'cataracts_under50' => 'Cataracts (under 50)',
                    'dyslexia' => 'Dyslexia',
                    'retinoblastoma' => 'Retinoblastoma',
                    'glaucoma' => 'Glaucoma',
                    'congenital_deafness' => 'Congenital Deafness',
                    'cleft_lip_palate' => 'Cleft Lip / Palate',
                    'club_foot' => 'Club Foot',
                    'turners_syndrome' => "Turner's Syndrome",
                    'klinefelters_syndrome' => "Klinefelter's Syndrome",
                    'fragile_x_syndrome' => 'Fragile X Syndrome',
                    'other_disorders' => 'Other Disorders'
                );

                foreach ($conditions as $key => $label) {
                    $checked = !empty($medical_history[$key]) && $medical_history[$key] === 'Yes' ? 'checked' : '';
                    ?>
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: normal; cursor: pointer; color: #555A4E; background: #fafbf9; padding: 10px; border-radius: 6px; border: 1px solid #eef0eb;">
                        <input type="checkbox" name="medical_history[<?php echo esc_attr($key); ?>]" value="Yes" <?php echo $checked; ?> style="width: auto; margin: 0; cursor: pointer;">
                        <span><?php echo esc_html($label); ?></span>
                    </label>
                    <?php
                }
                ?>
            </div>
        </div>

        <!-- Submit Panel -->
        <div class="ovarias-form-submit">
            <button type="submit" name="ovarias_save_profile" class="ovarias-submit-btn">
                <span>Save Profile Information</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
            </button>
        </div>

    </form>
</div>
