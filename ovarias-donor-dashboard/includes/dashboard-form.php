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

            <!-- Section: Profile Photo & Upload -->
            <div class="ovarias-form-section" style="grid-column: 1 / -1; display: grid; grid-template-columns: 1fr 1fr; gap: 30px; border-bottom: 1px solid var(--ovarias-border); padding-bottom: 30px;">
                <!-- Column 1: Primary Profile Picture -->
                <div style="border-right: 1px solid var(--ovarias-border); padding-right: 30px;">
                    <h3>Primary Profile Picture</h3>
                    <div class="ovarias-photo-upload-container" id="profile-photo-upload-container">
                        <?php if ($avatar_url): ?>
                            <div class="ovarias-uploaded-image-preview" style="text-align: center;">
                                <img src="<?php echo esc_url($avatar_url); ?>" alt="Uploaded Profile Photo" id="profile-image-preview-element" style="max-width: 100%; max-height: 250px; border-radius: var(--ovarias-radius-sm); border: 1px solid var(--ovarias-border); object-fit: cover; display: block; margin: 0 auto 15px auto;">
                                <a href="<?php echo esc_url(add_query_arg('delete_profile_photo', '1')); ?>" class="ovarias-submit-btn" style="background: #c62828; color: #fff; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; height: 36px; padding: 0 20px; font-size: 12px; border-radius: var(--ovarias-radius-sm); font-weight: bold; margin: 0;">
                                    Delete Profile Photo
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <div class="ovarias-file-dropzone" id="dropzone-area-profile" style="display: none; margin-top: 15px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                            <p>Drag your profile photo here or click to browse</p>
                            <span class="ovarias-file-formats">JPEG, PNG or GIF (max. 5MB)</span>
                            <input type="file" id="profile_image" name="profile_image" accept="image/*">
                        </div>
                        <?php if (empty($avatar_url)): ?>
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

                    <div class="ovarias-photo-upload-container" id="gallery-photo-upload-container">
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
                <label for="hobbies">Hobbies & Interests</label>
                <textarea id="hobbies" name="hobbies" placeholder="What are your favorite activities, sports, reading, etc.?"><?php echo esc_textarea($hobbies); ?></textarea>
            </div>

            <div class="ovarias-input-group">
                <label for="why_donate">Why do you want to donate?</label>
                <textarea id="why_donate" name="why_donate" placeholder="Share your motivation for becoming an egg donor..."><?php echo esc_textarea($why_donate); ?></textarea>
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
