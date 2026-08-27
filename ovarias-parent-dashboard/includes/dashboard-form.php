<?php

if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Get current parent profile details
$first_name = get_user_meta($user_id, 'first_name', true) ?: $current_user->first_name;
$last_name = get_user_meta($user_id, 'last_name', true) ?: $current_user->last_name;
$email = $current_user->user_email;
$phone = get_user_meta($user_id, 'phone_number', true);
$country = get_user_meta($user_id, 'country', true);
$preferences = get_user_meta($user_id, 'parent_preferences', true);
$notes = get_user_meta($user_id, 'parent_notes', true);

// Get already requested donor IDs to prevent duplicate requests
$info_requests = get_user_meta($user_id, 'ovarias_info_requests', true) ?: array();
$requested_donor_ids = array();
foreach ($info_requests as $req) {
    if (isset($req['donor_id'])) {
        $requested_donor_ids[] = strtolower(trim($req['donor_id']));
    }
}

// Access checks
$is_premium = get_user_meta($user_id, 'is_premium_parent', true) ? true : false;
$payment_date = get_user_meta($user_id, 'ovarias_payment_date', true);

// 12-Month Validity Check
$days_remaining = 0;
if ($is_premium) {
    if (empty($payment_date)) {
        $payment_date = current_time('mysql');
        update_user_meta($user_id, 'ovarias_payment_date', $payment_date);
    }
    $pay_time = strtotime($payment_date);
    $expiry_time = $pay_time + (365 * 24 * 60 * 60); // 365 days
    $current_time = current_time('timestamp');
    if ($current_time > $expiry_time) {
        // Membership expired
        $is_premium = false;
        update_user_meta($user_id, 'is_premium_parent', '0');
    } else {
        $days_remaining = ceil(($expiry_time - $current_time) / (24 * 60 * 60));
    }
}

// ----------------------------------------------------
// POST Action: Toggle Favorites (Local WP Database)
// ----------------------------------------------------
if (isset($_POST['ovarias_toggle_favorite'])) {
    $fav_id = (int)$_POST['favorite_donor_id'];
    $favorites = get_user_meta($user_id, 'ovarias_favorite_donors', true) ?: array();
    
    if (in_array($fav_id, $favorites)) {
        $favorites = array_diff($favorites, array($fav_id));
    } else {
        $favorites[] = $fav_id;
    }
    update_user_meta($user_id, 'ovarias_favorite_donors', $favorites);
    
    wp_safe_redirect(add_query_arg('tab', sanitize_text_field($_POST['current_tab']), remove_query_arg(array('profile_updated', 'payment_success'))));
    exit;
}

// ----------------------------------------------------
// POST Action: Submit Information Request (Local WP Database)
// ----------------------------------------------------
if (isset($_POST['ovarias_submit_info_request'])) {
    $donor_id_val = sanitize_text_field($_POST['request_donor_id']);
    $message_val = sanitize_textarea_field($_POST['request_message']);
    
    $requests = get_user_meta($user_id, 'ovarias_info_requests', true) ?: array();
    $new_req = array(
        'id' => uniqid('REQ_'),
        'donor_id' => $donor_id_val,
        'date' => current_time('mysql'),
        'message' => $message_val,
        'status' => 'New'
    );
    $requests[] = $new_req;
    update_user_meta($user_id, 'ovarias_info_requests', $requests);
    
    // Send email to admin (simulated transactional notification)
    $admin_email = get_option('admin_email');
    $subject = "New Egg Donor Information Request - " . $donor_id_val;
    $body_text = "Client Name: " . $first_name . " " . $last_name . "\r\n";
    $body_text .= "Client Email: " . $email . "\r\n";
    $body_text .= "Donor ID: " . $donor_id_val . "\r\n";
    $body_text .= "Date/Time: " . $new_req['date'] . "\r\n";
    $body_text .= "Message:\r\n" . $message_val . "\r\n";
    wp_mail($admin_email, $subject, $body_text);
    
    wp_safe_redirect(add_query_arg('tab', 'requests', remove_query_arg(array('profile_updated', 'payment_success'))));
    exit;
}

// Determine active tab
$active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'account';
$valid_tabs = array('account', 'fresh', 'frozen', 'favorites', 'requests', 'profile');
if (!in_array($active_tab, $valid_tabs)) {
    $active_tab = 'account';
}

// Fetch local donors list
$all_donors = function_exists('ovarias_parent_get_donors') ? ovarias_parent_get_donors() : array();
?>

<div class="ovarias-parent-dashboard">

    <!-- Notification Toasts / Alerts -->
    <?php if (isset($_GET['profile_updated'])): ?>
        <div class="ovarias-alert ovarias-alert-success">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            <span>Profile saved successfully.</span>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['payment_success'])): ?>
        <div class="ovarias-alert ovarias-alert-success ovarias-alert-payment">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            <div>
                <strong>Payment Successful (Test Mode)!</strong>
                <span>Your 12-month premium membership is now active. You have full access to browse the databases.</span>
            </div>
        </div>
    <?php endif; ?>
    <?php if (current_user_can('administrator')): ?>
        <div class="ovarias-alert" style="background: #fff; border: 1px solid var(--border-color); border-left: 5px solid #2b6cb0; padding: 20px; display: block; margin-bottom: 30px;">
            <strong style="color: #2b6cb0; font-size: 16px; display: block; margin-bottom: 10px;">[Admin Debug Panel - Donor Accounts Audit]</strong>
            <p style="margin: 0 0 10px 0; font-size: 14px;">The parent dashboard only displays donors with <code>profile_completed == 1</code> (100% complete) and <code>availability_status == Available</code>. Below are the actual values of all registered donors on your site:</p>
            <ul style="margin: 0; padding-left: 20px; line-height: 1.8; font-size: 13px;">
                <?php 
                $debug_donors = get_users(array(
                    'number' => -1,
                    'meta_query' => array(
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
                ));
                if (empty($debug_donors)) {
                    echo '<li>No users found with role <code>um_egg-donor</code> or <code>egg_donor</code>.</li>';
                } else {
                    foreach ($debug_donors as $d_user) {
                        $p_comp = get_user_meta($d_user->ID, 'profile_completed', true);
                        $a_stat = get_user_meta($d_user->ID, 'availability_status', true);
                        $e_type = get_user_meta($d_user->ID, 'egg_type', true) ?: 'Not set';
                        $d_id = get_user_meta($d_user->ID, 'donor_id', true) ?: 'Not set';
                        
                        // Define list of required fields for completion check
                        $required_fields = array(
                            'dob' => 'Date of Birth',
                            'nationality' => 'Nationality',
                            'height' => 'Height',
                            'weight' => 'Weight',
                            'blood_group' => 'Blood Group',
                            'eye_colour' => 'Eye Colour',
                            'hair_colour' => 'Hair Colour',
                            'education_level' => 'Education Level',
                            'field_of_study' => 'Field of Study',
                            'occupation' => 'Occupation',
                            'languages_spoken' => 'Languages Spoken',
                            'donation_type' => 'Donation Type',
                            'travel_available' => 'Willing to Travel',
                            'passport_available' => 'Valid Passport',
                            'about_me' => 'About Me',
                            'hobbies' => 'Hobbies',
                            'why_donate' => 'Why Donate',
                            'donor_id' => 'Donor ID',
                            'availability_status' => 'Availability Status',
                            'egg_type' => 'Egg Type Category',
                            'num_donations' => 'Number of Donations'
                        );
                        if ($e_type === 'Frozen' || $e_type === 'Both') {
                            $required_fields['num_eggs'] = 'Number of Eggs';
                            $required_fields['storage_country'] = 'Storage Country';
                        }
                        
                        $missing = array();
                        foreach ($required_fields as $key => $label) {
                            $val = get_user_meta($d_user->ID, $key, true);
                            if (empty($val) && $val !== '0') {
                                $missing[] = $label;
                            }
                        }

                        echo '<li style="margin-bottom: 12px; border-bottom: 1px dashed var(--border-color); padding-bottom: 12px;">';
                        echo '<strong>Username:</strong> <code>' . esc_html($d_user->user_login) . '</code> | ';
                        echo '<strong>Donor ID:</strong> <code>' . esc_html($d_id) . '</code> | ';
                        echo '<strong>Completion Flag:</strong> <code style="background: ' . ($p_comp == '1' ? '#d4edda' : '#f8d7da') . '; color: ' . ($p_comp == '1' ? '#155724' : '#721c24') . ';">' . esc_html($p_comp === '' ? 'Empty' : $p_comp) . '</code> (Must be <code>1</code>) | ';
                        echo '<strong>Availability Status:</strong> <code style="background: ' . ($a_stat == 'Available' ? '#d4edda' : '#f8d7da') . '; color: ' . ($a_stat == 'Available' ? '#155724' : '#721c24') . ';">' . esc_html($a_stat === '' ? 'Empty' : $a_stat) . '</code> (Must be <code>Available</code>) | ';
                        echo '<strong>Egg Type:</strong> <code>' . esc_html($e_type) . '</code><br>';
                        if (!empty($missing)) {
                            echo '<span style="color: #721c24; font-size: 12px; font-weight: bold;">⚠️ Missing fields for 100% Completion: </span><span style="color: #c53030; font-size: 12px;">' . esc_html(implode(', ', $missing)) . '</span>';
                        } else {
                            echo '<span style="color: #155724; font-size: 12px; font-weight: bold;">✔ Profile is 100% Complete!</span>';
                        }
                        echo '</li>';
                    }
                }
                ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Dashboard Tabs Header -->
    <div class="ovarias-parent-tabs-nav">
        <a class="ovarias-tab-btn <?php echo ($active_tab === 'account') ? 'active' : ''; ?>" href="<?php echo esc_url(add_query_arg('tab', 'account')); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            My Account
        </a>
        <a class="ovarias-tab-btn <?php echo ($active_tab === 'fresh') ? 'active' : ''; ?>" href="<?php echo esc_url(add_query_arg('tab', 'fresh')); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path></svg>
            Fresh Eggs
            <?php if (!$is_premium): ?><span class="ovarias-badge-lock"><svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg></span><?php endif; ?>
        </a>
        <a class="ovarias-tab-btn <?php echo ($active_tab === 'frozen') ? 'active' : ''; ?>" href="<?php echo esc_url(add_query_arg('tab', 'frozen')); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
            Frozen Eggs
            <?php if (!$is_premium): ?><span class="ovarias-badge-lock"><svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg></span><?php endif; ?>
        </a>
        <a class="ovarias-tab-btn <?php echo ($active_tab === 'favorites') ? 'active' : ''; ?>" href="<?php echo esc_url(add_query_arg('tab', 'favorites')); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
            Favorites
            <?php if (!$is_premium): ?><span class="ovarias-badge-lock"><svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg></span><?php endif; ?>
        </a>
        <a class="ovarias-tab-btn <?php echo ($active_tab === 'requests') ? 'active' : ''; ?>" href="<?php echo esc_url(add_query_arg('tab', 'requests')); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
            Info Requests
            <?php if (!$is_premium): ?><span class="ovarias-badge-lock"><svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg></span><?php endif; ?>
        </a>
        <a class="ovarias-tab-btn <?php echo ($active_tab === 'profile') ? 'active' : ''; ?>" href="<?php echo esc_url(add_query_arg('tab', 'profile')); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
            Account Details
        </a>
        <a class="ovarias-tab-btn logout-link-btn" href="<?php echo esc_url(wp_logout_url(home_url())); ?>" style="text-decoration: none; color: #b66;">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"></path></svg>
            Logout
        </a>
    </div>

    <!-- -------------------------------------------------- -->
    <!-- TAB: My Account -->
    <!-- -------------------------------------------------- -->
    <?php if ($active_tab === 'account'): ?>
        <div class="ovarias-tab-content active">
            <div class="ovarias-parent-header-card">
                <div class="ovarias-parent-header-title">
                    <?php if (!empty($first_name)): ?>
                        <h2>Welcome to Ovarias, <?php echo esc_html($first_name); ?>!</h2>
                    <?php else: ?>
                        <h2>Welcome to Ovarias!</h2>
                    <?php endif; ?>
                    <p>Access your egg donor databases, manage favorites, and request details below.</p>
                </div>
                <div class="ovarias-parent-header-status">
                    <span class="ovarias-status-badge <?php echo $is_premium ? 'status-premium' : 'status-standard'; ?>">
                        <?php echo $is_premium ? 'Premium Active' : 'Access Restricted'; ?>
                    </span>
                </div>
            </div>

            <div class="ovarias-account-summary-grid">
                <div class="ovarias-summary-card">
                    <h3 style="margin-top:0; color: var(--text-title);">Membership Status</h3>
                    <?php if ($is_premium): ?>
                        <div class="ovarias-status-text-row text-success">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <span>Active 12-Month Premium Account</span>
                        </div>
                        <p style="margin-bottom: 0; font-size: 14px; color: var(--text-body);">You have <strong><?php echo $days_remaining; ?> days</strong> of full access remaining.</p>
                    <?php else: ?>
                        <div class="ovarias-status-text-row text-danger">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                            <span>No Active Membership</span>
                        </div>
                        <p style="font-size: 14px; color: var(--text-muted); margin-bottom: 20px;">Purchase a 12-month pass to browse our Fresh and Frozen donor grids.</p>
                        <a href="<?php echo esc_url(add_query_arg('tab', 'fresh')); ?>" class="ovarias-btn ovarias-btn-submit" style="display:inline-block; text-decoration:none; text-align:center; padding: 12px 25px; font-size: 13px; border-radius: 6px;">Upgrade Account</a>
                    <?php endif; ?>
                </div>

                <div class="ovarias-summary-card">
                    <h3 style="margin-top:0; color: var(--text-title);">Quick Links</h3>
                    <ul>
                        <li><a href="<?php echo esc_url(add_query_arg('tab', 'fresh')); ?>">Browse Fresh Donors</a></li>
                        <li><a href="<?php echo esc_url(add_query_arg('tab', 'frozen')); ?>">Browse Frozen Donors</a></li>
                        <li><a href="<?php echo esc_url(add_query_arg('tab', 'favorites')); ?>">View Saved Donors</a></li>
                        <li><a href="<?php echo esc_url(add_query_arg('tab', 'requests')); ?>">View Submitted Inquiries</a></li>
                    </ul>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- -------------------------------------------------- -->
    <!-- TAB: Fresh Egg Donors -->
    <!-- -------------------------------------------------- -->
    <?php if ($active_tab === 'fresh'): ?>
        <div class="ovarias-tab-content active">
            <?php if (!$is_premium): ?>
                <?php include OVARIAS_PARENT_PATH . 'includes/stripe-checkout.php'; // Renders paywall card ?>
                <div class="ovarias-paywall-card">
                    <h2>Unlock Fresh Egg Donors List</h2>
                    <p>Get instant access to complete profiles of available fresh egg donors for 12 months.</p>
                    <div class="ovarias-price-box">
                        <span class="currency">$</span>
                        <span class="amount">199</span>
                        <span class="period">12-Month Access</span>
                    </div>
                    <form action="" method="POST" class="ovarias-checkout-form">
                        <?php wp_nonce_field('ovarias_checkout_nonce'); ?>
                        <input type="hidden" name="ovarias_buy_premium" value="1">
                        <button type="submit" class="ovarias-btn ovarias-btn-checkout">Unlock Database Now (Dummy Checkout)</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="ovarias-directory-header">
                    <h2>Fresh Egg Donors</h2>
                    <p>Browse our list of approved donors available for active/fresh donor cycles.</p>
                </div>

                <?php 
                $fresh_donors = array_filter($all_donors, function($d) {
                    return $d['Egg_Type'] === 'Fresh' || $d['Egg_Type'] === 'Both';
                });
                render_donors_grid($fresh_donors, $active_tab, $user_id); 
                ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- -------------------------------------------------- -->
    <!-- TAB: Frozen Egg Donors -->
    <!-- -------------------------------------------------- -->
    <?php if ($active_tab === 'frozen'): ?>
        <div class="ovarias-tab-content active">
            <?php if (!$is_premium): ?>
                <div class="ovarias-paywall-card">
                    <h2>Unlock Frozen Egg Donors List</h2>
                    <p>Unlock our catalog of immediately available, retrieved, and frozen donor eggs.</p>
                    <div class="ovarias-price-box">
                        <span class="currency">$</span>
                        <span class="amount">199</span>
                        <span class="period">12-Month Access</span>
                    </div>
                    <form action="" method="POST" class="ovarias-checkout-form">
                        <?php wp_nonce_field('ovarias_checkout_nonce'); ?>
                        <input type="hidden" name="ovarias_buy_premium" value="1">
                        <button type="submit" class="ovarias-btn ovarias-btn-checkout">Unlock Database Now (Dummy Checkout)</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="ovarias-directory-header">
                    <h2>Frozen Egg Donors</h2>
                    <p>Browse available donor eggs ready for shipping or direct clinic transfer.</p>
                </div>

                <?php 
                $frozen_donors = array_filter($all_donors, function($d) {
                    return $d['Egg_Type'] === 'Frozen' || $d['Egg_Type'] === 'Both';
                });
                render_donors_grid($frozen_donors, $active_tab, $user_id); 
                ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- -------------------------------------------------- -->
    <!-- TAB: Favorites -->
    <!-- -------------------------------------------------- -->
    <?php if ($active_tab === 'favorites'): ?>
        <div class="ovarias-tab-content active">
            <?php if (!$is_premium): ?>
                <div class="ovarias-paywall-card">
                    <h2>Unlock Your Saved Favorites</h2>
                    <p>Keep a shortlist of profiles you are interested in matching with.</p>
                    <div class="ovarias-price-box">
                        <span class="currency">$</span>
                        <span class="amount">199</span>
                        <span class="period">12-Month Access</span>
                    </div>
                    <form action="" method="POST" class="ovarias-checkout-form">
                        <?php wp_nonce_field('ovarias_checkout_nonce'); ?>
                        <input type="hidden" name="ovarias_buy_premium" value="1">
                        <button type="submit" class="ovarias-btn ovarias-btn-checkout">Unlock Database Now (Dummy Checkout)</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="ovarias-directory-header">
                    <h2>My Favorite Donors</h2>
                    <p>Your saved shortlist of potential egg donors.</p>
                </div>

                <?php 
                $fav_ids = get_user_meta($user_id, 'ovarias_favorite_donors', true) ?: array();
                $fav_donors = array_filter($all_donors, function($d) use ($fav_ids) {
                    return in_array($d['id'], $fav_ids);
                });
                render_donors_grid($fav_donors, $active_tab, $user_id); 
                ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- -------------------------------------------------- -->
    <!-- TAB: Info Requests Log -->
    <!-- -------------------------------------------------- -->
    <?php if ($active_tab === 'requests'): ?>
        <div class="ovarias-tab-content active">
            <?php if (!$is_premium): ?>
                <div class="ovarias-paywall-card">
                    <h2>Unlock Information Request Panel</h2>
                    <p>Ask our matching coordinators for detailed portfolios and logs on specific donors.</p>
                    <div class="ovarias-price-box">
                        <span class="currency">$</span>
                        <span class="amount">199</span>
                        <span class="period">12-Month Access</span>
                    </div>
                    <form action="" method="POST" class="ovarias-checkout-form">
                        <?php wp_nonce_field('ovarias_checkout_nonce'); ?>
                        <input type="hidden" name="ovarias_buy_premium" value="1">
                        <button type="submit" class="ovarias-btn ovarias-btn-checkout">Unlock Database Now (Dummy Checkout)</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="ovarias-directory-header">
                    <h2>Requests for More Information</h2>
                    <p>Below is a log of all donor profile inquiries you have submitted to our team.</p>
                </div>

                <?php 
                $user_requests = get_user_meta($user_id, 'ovarias_info_requests', true) ?: array();
                if (empty($user_requests)):
                ?>
                    <div class="ovarias-empty-directory" style="text-align: center; padding: 50px;">
                        <h3>No requests submitted yet</h3>
                        <p>When you click "Request More Info" on a donor's profile, it will show up here.</p>
                    </div>
                <?php else: ?>
                    <table class="ovarias-requests-table" style="width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 15px;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--border-color); text-align: left; background: var(--primary-light);">
                                <th style="padding: 15px;">Request ID</th>
                                <th style="padding: 15px;">Donor ID</th>
                                <th style="padding: 15px;">Date Submitted</th>
                                <th style="padding: 15px;">Status</th>
                                <th style="padding: 15px;">Message Snippet</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_reverse($user_requests) as $req): ?>
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: 15px; font-family: monospace; font-weight: bold;"><?php echo esc_html($req['id']); ?></td>
                                    <td style="padding: 15px; font-weight: bold; color: var(--primary);"><?php echo esc_html($req['donor_id']); ?></td>
                                    <td style="padding: 15px;"><?php echo esc_html(date('M d, Y H:i', strtotime($req['date']))); ?></td>
                                    <td style="padding: 15px;">
                                        <span class="ovarias-req-status badge-<?php echo strtolower(str_replace(' ', '-', $req['status'])); ?>" style="padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 600; text-transform: uppercase; background: #eee; color: #444;">
                                            <?php echo esc_html($req['status']); ?>
                                        </span>
                                    </td>
                                    <td style="padding: 15px; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <?php echo esc_html($req['message']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <style>
                        .badge-new { background: #E2ECF7 !important; color: #1E4E8C !important; }
                        .badge-in-progress { background: #FFF4E2 !important; color: #8A5A1E !important; }
                        .badge-responded { background: #E2F7E2 !important; color: #1E5C1E !important; }
                        .badge-closed { background: #eee !important; color: #666 !important; }
                    </style>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- -------------------------------------------------- -->
    <!-- TAB: Account Details -->
    <!-- -------------------------------------------------- -->
    <?php if ($active_tab === 'profile'): ?>
        <div class="ovarias-tab-content active" id="tab-profile">
            <div class="ovarias-parent-header-card">
                <div class="ovarias-parent-header-title">
                    <h2>Account Details</h2>
                    <p>Update your personal information and egg donor matching criteria below.</p>
                </div>
            </div>

            <form action="" method="POST" class="ovarias-parent-form">
                <?php wp_nonce_field('ovarias_save_parent'); ?>
                <input type="hidden" name="ovarias_save_parent" value="1">

                <div class="ovarias-form-grid">
                    <!-- Row 1 -->
                    <div class="ovarias-form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" value="<?php echo esc_attr($first_name); ?>" required>
                    </div>
                    <div class="ovarias-form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" value="<?php echo esc_attr($last_name); ?>" required>
                    </div>

                    <!-- Hidden fields to preserve credentials on save -->
                    <input type="hidden" name="phone_number" value="<?php echo esc_attr($phone); ?>">

                    <!-- Row 3 -->
                    <div class="ovarias-form-group full-width">
                        <label for="country">Country of Residence</label>
                        <input type="text" id="country" name="country" value="<?php echo esc_attr($country); ?>" required>
                    </div>

                    <!-- Textareas -->
                    <div class="ovarias-form-group full-width">
                        <label for="parent_preferences">What are you looking for in a donor? (Height, eyes, ethnicity, characteristics)</label>
                        <textarea id="parent_preferences" name="parent_preferences" rows="4" placeholder="e.g. Height 170cm+, Blonde hair, Blue eyes..."><?php echo esc_textarea($preferences); ?></textarea>
                    </div>

                    <div class="ovarias-form-group full-width">
                        <label for="parent_notes">Additional Information / Family Notes</label>
                        <textarea id="parent_notes" name="parent_notes" rows="4" placeholder="e.g. Any details about your clinic, family details..."><?php echo esc_textarea($notes); ?></textarea>
                    </div>
                </div>

                <div class="ovarias-form-actions">
                    <button type="submit" class="ovarias-btn ovarias-btn-submit">
                        Save Preferences Details
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>

</div>

<!-- Modal Dialog for Details (Active Directory users only) -->
<div class="ovarias-parent-modal" id="donor-detail-modal">
    <div class="ovarias-modal-overlay"></div>
    <div class="ovarias-modal-container">
        <button class="ovarias-modal-close">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
        <div class="ovarias-modal-content">
            <div class="ovarias-modal-header-layout" style="display: flex; flex-direction: column; gap: 15px; margin-bottom: 20px; align-items: center; width: 100%;">
                <div class="ovarias-modal-image-wrapper" style="width: 100%; max-height: 450px; overflow: hidden; border-radius: 8px; border: 1px solid var(--border-color); background: #fcfcfc; display: flex; align-items: center; justify-content: center; position: relative;">
                    <img src="" alt="Donor Photo" class="ovarias-modal-avatar" id="modal-avatar" style="max-height: 450px; max-width: 100%; width: auto; height: auto; object-fit: contain; border-radius: 0; border: none; margin: 0 auto; display: block;">
                </div>
                <!-- Gallery Thumbnails Container -->
                <div class="ovarias-modal-gallery" id="modal-gallery" style="display: flex; gap: 8px; overflow-x: auto; padding: 5px 0; margin-top: 5px; justify-content: center; width: 100%;"></div>
                
                <div class="ovarias-modal-header-info" style="text-align: center; width: 100%;">
                    <h2 id="modal-name" style="margin-top: 0; margin-bottom: 8px;"></h2>
                    <div class="ovarias-modal-badges" style="justify-content: center;">
                        <span class="ovarias-modal-badge">Age: <strong id="modal-age"></strong></span>
                        <span class="ovarias-modal-badge">Blood: <strong id="modal-blood"></strong></span>
                    </div>
                </div>
            </div>
            
            <div class="ovarias-modal-details-grid">
                <div class="ovarias-modal-group">
                    <label>Donor ID</label>
                    <span id="modal-donor-id" style="font-weight: bold; color: var(--primary);"></span>
                </div>
                <div class="ovarias-modal-group">
                    <label>Nationality</label>
                    <span id="modal-nationality"></span>
                </div>
                <div class="ovarias-modal-group">
                    <label>Education</label>
                    <span id="modal-education"></span>
                </div>
                <div class="ovarias-modal-group">
                    <label>Height</label>
                    <span id="modal-height"></span>
                </div>
                <div class="ovarias-modal-group">
                    <label>Weight</label>
                    <span id="modal-weight"></span>
                </div>
                <div class="ovarias-modal-group">
                    <label>Hair Colour</label>
                    <span id="modal-hair"></span>
                </div>
                <div class="ovarias-modal-group">
                    <label>Eye Colour</label>
                    <span id="modal-eyes"></span>
                </div>
                <div class="ovarias-modal-group">
                    <label>Previous Donations</label>
                    <span id="modal-num-donations"></span>
                </div>
                <div class="ovarias-modal-group" id="modal-egg-type-container">
                    <label>Egg Category</label>
                    <span id="modal-egg-type"></span>
                </div>
                <div class="ovarias-modal-group" id="modal-num-eggs-container">
                    <label>Eggs Available</label>
                    <span id="modal-num-eggs"></span>
                </div>
                <div class="ovarias-modal-group" id="modal-storage-country-container">
                    <label>Storage Location</label>
                    <span id="modal-storage-country"></span>
                </div>
            </div>

            <!-- Operational Details / PDF Downloads and Inquiry -->
            <div class="ovarias-modal-action-bar" style="display: flex; gap: 15px; margin: 20px 0; padding: 15px; background: var(--accent-light); border-radius: 8px;">
                <button type="button" class="ovarias-btn" id="modal-btn-download-pdf" style="background: #EAECE6; color: var(--text-title); display: flex; align-items: center; gap: 8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 3v12"/></svg>
                    Download Profile PDF
                </button>
                <button type="button" class="ovarias-btn ovarias-btn-submit" id="modal-btn-toggle-info-form">
                    Request More Info
                </button>
            </div>

            <!-- Toggleable Information Request Form -->
            <div id="modal-info-request-form" style="display: none; padding: 20px; background: #fff; border: 1px solid var(--border-color); border-radius: 8px; margin-bottom: 20px;">
                <h4 style="margin-top:0;">Request More Information</h4>
                <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 12px;">Type your detailed matching inquiries (e.g. hobbies, childhood photos, specific medical inquiries) to send to Ovarias coordinators.</p>
                <form action="" method="POST">
                    <input type="hidden" name="request_donor_id" id="form-request-donor-id" value="">
                    <textarea name="request_message" rows="4" style="width:100%; margin-bottom:12px; font-family:inherit; padding:10px; border-radius:4px; border:1px solid var(--border-color);" placeholder="I would like to receive more details about this donor..." required></textarea>
                    <button type="submit" name="ovarias_submit_info_request" class="ovarias-btn ovarias-btn-submit">Send Inquiry to Admin</button>
                </form>
            </div>

            <div class="ovarias-modal-sections">
                <div class="ovarias-modal-section">
                    <h3>About Me</h3>
                    <p id="modal-about"></p>
                </div>
                <div class="ovarias-modal-section">
                    <h3>Hobbies</h3>
                    <p id="modal-hobbies"></p>
                </div>
                <div class="ovarias-modal-section">
                    <h3>Why I want to Donate</h3>
                    <p id="modal-why"></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// ----------------------------------------------------
// HELPER: Renders the Anonymized Donors Grid Layout
// ----------------------------------------------------
function render_donors_grid($donors, $current_tab, $user_id) {
    if (empty($donors)):
    ?>
        <div class="ovarias-empty-directory">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <h3>No donors found</h3>
            <p>No active egg donor profiles match this category. Please check again later.</p>
        </div>
    <?php else: ?>
        
        <!-- Search & Filter Options -->
        <div class="ovarias-directory-filters" style="display: flex; gap: 15px; margin-bottom: 25px; flex-wrap: wrap;">
            <input type="text" id="filter-search" placeholder="Search by Donor ID or Ethnicity..." style="flex: 1; padding: 12px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); font-family: inherit;">
            <select id="filter-blood" style="padding: 12px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); font-family: inherit;">
                <option value="">All Blood Groups</option>
                <option value="A+">A+</option>
                <option value="A-">A-</option>
                <option value="B+">B+</option>
                <option value="B-">B-</option>
                <option value="AB+">AB+</option>
                <option value="AB-">AB-</option>
                <option value="O+">O+</option>
                <option value="O-">O-</option>
            </select>
            <select id="filter-education" style="padding: 12px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); font-family: inherit;">
                <option value="">All Education Levels</option>
                <option value="High School">High School</option>
                <option value="College">College</option>
                <option value="Bachelor's Degree">Bachelor's Degree</option>
                <option value="Master's Degree">Master's Degree</option>
                <option value="Doctorate">Doctorate</option>
            </select>
        </div>

        <div class="ovarias-donors-grid">
            <?php foreach ($donors as $donor): 
                $wp_uid = (int)($donor['WordPress_User_ID'] ?? 0);
                $avatar_id = $wp_uid ? get_user_meta($wp_uid, 'profile_image', true) : 0;
                $avatar_url = $avatar_id ? wp_get_attachment_url($avatar_id) : '';
                
                $donor_unique_id = esc_html($donor['donor_id']);
                $name = "Donor " . $donor_unique_id;
                $age = esc_html($donor['Age']);
                $nationality = esc_html($donor['First_Name'] ?? 'N/A'); // Using First_Name field for Nationality or keep as is
                $blood_group = esc_html($donor['Blood_Group1'] ?? 'N/A');
                $education = esc_html($donor['Level_of_Education'] ?? 'N/A');
                $height = esc_html($donor['Height'] ?? 'N/A');
                $weight = esc_html($donor['Weight'] ?? 'N/A');
                
                $favorites = get_user_meta($user_id, 'ovarias_favorite_donors', true) ?: array();
                $is_favorited = in_array($donor['id'], $favorites);

                $gallery_ids = $wp_uid ? (get_user_meta($wp_uid, 'profile_images_gallery', true) ?: array()) : array();
                if (!empty($avatar_id) && empty($gallery_ids)) {
                    $gallery_ids = array($avatar_id);
                }
                $gallery_urls = array();
                foreach ($gallery_ids as $att_id) {
                    $url = wp_get_attachment_url($att_id);
                    if ($url) {
                        $gallery_urls[] = $url;
                    }
                }
                if (empty($gallery_urls) && $avatar_url) {
                    $gallery_urls[] = $avatar_url;
                }

                $detail_data = array(
                    'name' => $name,
                    'donor_id' => $donor_unique_id,
                    'age' => $age,
                    'nationality' => esc_html($donor['Nationality'] ?? 'N/A'),
                    'blood_group' => $blood_group,
                    'education' => $education,
                    'height' => $height,
                    'weight' => $weight,
                    'hair' => esc_html($donor['Hair_Colour'] ?? 'N/A'),
                    'eyes' => esc_html($donor['Eye_Colour'] ?? 'N/A'),
                    'num_donations' => esc_html($donor['Number_of_Donations'] ?? '0'),
                    'egg_type' => esc_html($donor['Egg_Type'] ?? 'Fresh'),
                    'num_eggs' => esc_html($donor['Number_of_Eggs'] ?? '0'),
                    'storage_country' => esc_html($donor['Storage_Country'] ?? 'N/A'),
                    'occupation' => esc_html($donor['Occupation'] ?? 'N/A'),
                    'hobbies' => esc_html($donor['Hobbies'] ?? 'N/A'),
                    'about_me' => esc_html($donor['About_Me'] ?? 'N/A'),
                    'why_donate' => esc_html($donor['Why_Donate'] ?? 'N/A'),
                    'avatar' => $avatar_url ?: OVARIAS_PARENT_URL . 'assets/images/placeholder.png',
                    'gallery' => $gallery_urls
                );
            ?>
                <div class="ovarias-donor-card" data-donor-id="<?php echo esc_attr(strtolower($donor_unique_id)); ?>" data-blood="<?php echo esc_attr($blood_group); ?>" data-education="<?php echo esc_attr($education); ?>">
                    <div class="ovarias-donor-card-image" style="position: relative;">
                        <?php if ($avatar_url): ?>
                            <img src="<?php echo esc_url($avatar_url); ?>" alt="Donor Photo" style="width: 100%; height: 200px; object-fit: cover;">
                        <?php else: ?>
                            <div class="ovarias-donor-avatar-placeholder" style="width: 100%; height: 200px; background: #eee; display: flex; align-items: center; justify-content: center;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Favorites Icon Toggle -->
                        <form method="POST" style="position: absolute; top: 12px; right: 12px; z-index: 5;">
                            <input type="hidden" name="favorite_donor_id" value="<?php echo esc_attr($donor['id']); ?>">
                            <input type="hidden" name="current_tab" value="<?php echo esc_attr($current_tab); ?>">
                            <button type="submit" name="ovarias_toggle_favorite" style="background: rgba(255,255,255,0.85); border: none; padding: 8px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="<?php echo $is_favorited ? '#e74c3c' : 'none'; ?>" stroke="<?php echo $is_favorited ? '#e74c3c' : '#444'; ?>" stroke-width="2.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                            </button>
                        </form>
                    </div>
                    <div class="ovarias-donor-card-body" style="padding: 20px;">
                        <h3 style="margin-top: 0; margin-bottom: 10px; color: var(--text-title);"><?php echo $name; ?></h3>
                        <div class="ovarias-donor-card-meta" style="font-size: 13px; color: var(--text-muted); margin-bottom: 15px; display: flex; justify-content: space-between;">
                            <span>Age: <strong><?php echo $age; ?></strong></span>
                            <span>Blood: <strong><?php echo $blood_group; ?></strong></span>
                        </div>
                        <div class="ovarias-donor-card-specs" style="font-size: 14px; line-height: 1.6; border-top: 1px solid var(--border-color); padding-top: 15px; margin-bottom: 20px;">
                            <p style="margin: 0 0 6px 0;">Education: <strong><?php echo $education; ?></strong></p>
                            <p style="margin: 0 0 6px 0;">Height: <strong><?php echo $height; ?> cm</strong></p>
                            <?php if ($donor['Egg_Type'] === 'Frozen' || $donor['Egg_Type'] === 'Both'): ?>
                                <p style="margin: 0; color: var(--primary);">Eggs Available: <strong><?php echo esc_html($donor['Number_of_Eggs']); ?> (<?php echo esc_html($donor['Storage_Country']); ?>)</strong></p>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="ovarias-btn ovarias-btn-view-details" data-donor="<?php echo esc_attr(wp_json_encode($detail_data)); ?>" style="width: 100%; border: 1px solid var(--border-color); background: none; color: var(--text-title); padding: 10px; font-weight: 600; cursor: pointer; border-radius: var(--radius-sm); transition: var(--transition);">
                            View Full Details
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php
}
?>

<script>
var ovarias_requested_donor_ids = <?php echo wp_json_encode($requested_donor_ids); ?>;

jQuery(document).ready(function($) {
    // Dynamic grid search filtering
    $('#filter-search, #filter-blood, #filter-education').on('keyup change', function() {
        var query = $('#filter-search').val().toLowerCase();
        var blood = $('#filter-blood').val();
        var edu = $('#filter-education').val();

        $('.ovarias-donor-card').each(function() {
            var cardId = $(this).attr('data-donor-id') || '';
            var cardBlood = $(this).attr('data-blood') || '';
            var cardEdu = $(this).attr('data-education') || '';

            var matchSearch = cardId.indexOf(query) > -1;
            var matchBlood = blood === '' || cardBlood === blood;
            var matchEdu = edu === '' || cardEdu === edu;

            if (matchSearch && matchBlood && matchEdu) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    // Request More Info panel toggle
    $('#modal-btn-toggle-info-form').on('click', function() {
        $('#modal-info-request-form').slideToggle();
    });

    // Client-side print function for PDF watermark mockup
    $('#modal-btn-download-pdf').on('click', function(e) {
        e.preventDefault();
        var donorDataStr = $('.ovarias-btn-view-details').first().attr('data-donor'); // Placeholder getter
        var activeModalData = $('#donor-detail-modal').data('active-donor');
        if (activeModalData) {
            printDonorProfile(activeModalData);
        }
    });

    // Intercept View Details button to save details data to modal state
    $('.ovarias-parent-dashboard').on('click', '.ovarias-btn-view-details', function() {
        var donorDataStr = $(this).attr('data-donor');
        if (!donorDataStr) return;
        try {
            var donor = JSON.parse(donorDataStr);
            $('#donor-detail-modal').data('active-donor', donor);
            
            // Populating additional fields in modal
            $('#modal-donor-id').text(donor.donor_id);
            $('#modal-num-donations').text(donor.num_donations);
            $('#form-request-donor-id').val(donor.donor_id);
            
            if (donor.egg_type === 'Frozen' || donor.egg_type === 'Both') {
                $('#modal-egg-type').text(donor.egg_type + ' Eggs');
                $('#modal-num-eggs').text(donor.num_eggs);
                $('#modal-storage-country').text(donor.storage_country);
                $('#modal-egg-type-container, #modal-num-eggs-container, #modal-storage-country-container').show();
            } else {
                $('#modal-egg-type-container, #modal-num-eggs-container, #modal-storage-country-container').hide();
            }

            // Check if this donor has already been requested
            var donorIdLower = donor.donor_id.toLowerCase().trim();
            if (typeof ovarias_requested_donor_ids !== 'undefined' && ovarias_requested_donor_ids.includes(donorIdLower)) {
                $('#modal-btn-toggle-info-form')
                    .html('<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px; vertical-align: middle; display: inline-block;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg> Info Requested')
                    .prop('disabled', true)
                    .css({'background-color': '#a0a595', 'color': '#ffffff', 'cursor': 'not-allowed', 'opacity': '0.9'});
            } else {
                $('#modal-btn-toggle-info-form')
                    .html('Request More Info')
                    .prop('disabled', false)
                    .css({'background-color': '', 'color': '', 'cursor': 'pointer', 'opacity': ''});
            }

            // Reset request form
            $('#modal-info-request-form').hide();
            $('#modal-info-request-form textarea').val('');
        } catch(err) {
            console.error('Error opening details modal:', err);
        }
    });

    function printDonorProfile(donor) {
        var printWindow = window.open('', '_blank');
        var html = '<html><head><title>Donor Profile - ' + donor.donor_id + '</title>';
        html += '<style>';
        html += 'body { font-family: sans-serif; color: #333; padding: 40px; position: relative; }';
        html += '.watermark { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-30deg); font-size: 110px; color: rgba(200, 205, 186, 0.15); font-weight: bold; z-index: -1; white-space: nowrap; pointer-events: none; letter-spacing: 5px; }';
        html += '.header { border-bottom: 3px solid #7E8372; padding-bottom: 20px; margin-bottom: 30px; display: flex; align-items: center; }';
        html += '.header img { width: 110px; height: 110px; border-radius: 50%; margin-right: 25px; object-fit: cover; border: 3px solid #C8CDBA; }';
        html += 'h1 { color: #2C2E27; margin: 0; font-size: 28px; }';
        html += '.grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }';
        html += '.item { border-bottom: 1px solid #eee; padding-bottom: 10px; }';
        html += '.item label { font-weight: bold; color: #7E8372; display: block; font-size: 11px; text-transform: uppercase; margin-bottom: 4px; }';
        html += '.section { margin-bottom: 25px; }';
        html += '.section h3 { border-bottom: 1px solid #7E8372; color: #2C2E27; padding-bottom: 5px; font-size: 18px; margin-bottom: 10px; }';
        html += 'p { line-height: 1.6; margin: 0; font-size: 14px; color: #444; }';
        html += '</style></head><body>';
        html += '<div class="watermark">OVARIAS EGG BANK</div>';
        html += '<div class="header">';
        if (donor.avatar && donor.avatar.indexOf('placeholder.png') === -1) {
            html += '<img src="' + donor.avatar + '">';
        }
        html += '<div><h1>Donor ID: ' + donor.donor_id + '</h1>';
        html += '<p>Age: ' + donor.age + ' | Blood Group: ' + donor.blood_group + '</p></div></div>';
        html += '<div class="grid">';
        html += '<div class="item"><label>Nationality</label><span>' + donor.nationality + '</span></div>';
        html += '<div class="item"><label>Education</label><span>' + donor.education + '</span></div>';
        html += '<div class="item"><label>Height</label><span>' + donor.height + ' cm</span></div>';
        html += '<div class="item"><label>Weight</label><span>' + donor.weight + ' kg</span></div>';
        html += '<div class="item"><label>Hair Colour</label><span>' + donor.hair + '</span></div>';
        html += '<div class="item"><label>Eye Colour</label><span>' + donor.eyes + '</span></div>';
        html += '<div class="item"><label>Occupation</label><span>' + donor.occupation + '</span></div>';
        html += '<div class="item"><label>Previous Donations</label><span>' + donor.num_donations + '</span></div>';
        if (donor.egg_type === 'Frozen' || donor.egg_type === 'Both') {
            html += '<div class="item"><label>Eggs Available</label><span>' + donor.num_eggs + '</span></div>';
            html += '<div class="item"><label>Storage Country</label><span>' + donor.storage_country + '</span></div>';
        }
        html += '</div>';
        html += '<div class="section"><h3>About Me</h3><p>' + donor.about_me + '</p></div>';
        html += '<div class="section"><h3>Hobbies</h3><p>' + donor.hobbies + '</p></div>';
        html += '<div class="section"><h3>Why I want to Donate</h3><p>' + donor.why_donate + '</p></div>';
        html += '</body></html>';
        
        printWindow.document.write(html);
        printWindow.document.close();
        printWindow.focus();
        setTimeout(function() {
            printWindow.print();
            printWindow.close();
        }, 600);
    }
});
</script>
