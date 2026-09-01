<?php

if (!defined('ABSPATH')) {
    exit;
}

// Determine active tab
$active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'overview';
$valid_tabs = array('overview', 'parents', 'donors', 'inquiries', 'general-inquiries');
if (!in_array($active_tab, $valid_tabs)) {
    $active_tab = 'overview';
}

// Fetch all parents using our metadata filter
$parents = get_users(array(
    'number' => -1,
    'meta_query' => array(
        'relation' => 'OR',
        array(
            'key' => 'role',
            'value' => array('um_intended-parent', 'um_intended_parent', 'intended_parent'),
            'compare' => 'IN'
        ),
        array(
            'key' => 'community_role',
            'value' => array('um_intended-parent', 'um_intended_parent', 'intended_parent'),
            'compare' => 'IN'
        ),
        array(
            'key' => 'wp_capabilities',
            'value' => 'intended-parent',
            'compare' => 'LIKE'
        ),
        array(
            'key' => 'wp_capabilities',
            'value' => 'intended_parent',
            'compare' => 'LIKE'
        )
    )
));

// Fetch all donors using our metadata filter
$donors = get_users(array(
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

// Fetch client match inquiries across all parents
$all_match_inquiries = array();
foreach ($parents as $parent) {
    $parent_reqs = get_user_meta($parent->ID, 'ovarias_info_requests', true) ?: array();
    if (!empty($parent_reqs)) {
        foreach ($parent_reqs as $req) {
            $all_match_inquiries[] = array(
                'parent_id' => $parent->ID,
                'parent_name' => (get_user_meta($parent->ID, 'first_name', true) ?: $parent->first_name) . ' ' . (get_user_meta($parent->ID, 'last_name', true) ?: $parent->last_name),
                'parent_email' => $parent->user_email,
                'id' => $req['id'],
                'donor_id' => $req['donor_id'],
                'date' => $req['date'],
                'message' => $req['message'],
                'status' => $req['status']
            );
        }
    }
}

// Sort match inquiries by date (newest first)
usort($all_match_inquiries, function($a, $b) {
    return strcmp($b['date'], $a['date']);
});

// Fetch general public inquiries (Section 10 / 14)
$general_inquiries = get_option('ovarias_general_inquiries', array());
// Sort general inquiries by date (newest first)
usort($general_inquiries, function($a, $b) {
    return strcmp($b['date'], $a['date']);
});

// Calculate statistics
$total_parents = count($parents);
$premium_parents = 0;
foreach ($parents as $parent) {
    if (get_user_meta($parent->ID, 'is_premium_parent', true) == '1') {
        $premium_parents++;
    }
}

$total_donors = count($donors);
$fresh_donors_count = 0;
$frozen_donors_count = 0;
foreach ($donors as $donor) {
    $type = get_user_meta($donor->ID, 'egg_type', true) ?: 'Fresh';
    if ($type === 'Fresh') {
        $fresh_donors_count++;
    } elseif ($type === 'Frozen') {
        $frozen_donors_count++;
    } else {
        $fresh_donors_count++;
        $frozen_donors_count++;
    }
}

// Counts of pending inquiries
$pending_match_inquiries = 0;
foreach ($all_match_inquiries as $inq) {
    if ($inq['status'] === 'New' || $inq['status'] === 'In Progress') {
        $pending_match_inquiries++;
    }
}

$pending_general_inquiries = 0;
foreach ($general_inquiries as $inq) {
    if ($inq['status'] === 'New' || $inq['status'] === 'In Progress') {
        $pending_general_inquiries++;
    }
}

// ----------------------------------------------------
// PAGINATION SETTINGS & SLICING
// ----------------------------------------------------
$items_per_page = 10;

// Parents Pagination
$p_page = isset($_GET['p_page']) ? max(1, (int)$_GET['p_page']) : 1;
$parents_sliced = array_slice($parents, ($p_page - 1) * $items_per_page, $items_per_page);

// Donors Pagination
$d_page = isset($_GET['d_page']) ? max(1, (int)$_GET['d_page']) : 1;
$donors_sliced = array_slice($donors, ($d_page - 1) * $items_per_page, $items_per_page);

// Match Inquiries Pagination
$mi_page = isset($_GET['mi_page']) ? max(1, (int)$_GET['mi_page']) : 1;
$match_inquiries_sliced = array_slice($all_match_inquiries, ($mi_page - 1) * $items_per_page, $items_per_page);

// General Inquiries Pagination
$gi_page = isset($_GET['gi_page']) ? max(1, (int)$_GET['gi_page']) : 1;
$general_inquiries_sliced = array_slice($general_inquiries, ($gi_page - 1) * $items_per_page, $items_per_page);

/**
 * Pagination Render Helper
 */
function ovarias_admin_render_pagination($total_items, $items_per_page, $current_page, $page_arg, $tab_name) {
    $total_pages = ceil($total_items / $items_per_page);
    if ($total_pages <= 1) {
        return;
    }
    
    echo '<div class="ovarias-admin-pagination" style="display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 25px; padding-top: 15px; border-top: 1px solid var(--border-color);">';
    
    // Previous Link
    if ($current_page > 1) {
        $prev_url = add_query_arg(array('tab' => $tab_name, $page_arg => $current_page - 1));
        echo '<a href="' . esc_url($prev_url) . '" class="action-btn" style="background: #FAFBF9; color: var(--primary); border: 1px solid var(--border-color); padding: 6px 12px; text-decoration: none;">&laquo; Prev</a>';
    }
    
    // Page Numbers
    for ($i = 1; $i <= $total_pages; $i++) {
        $page_url = add_query_arg(array('tab' => $tab_name, $page_arg => $i));
        $is_active = ($i === $current_page);
        $bg_color = $is_active ? 'var(--primary)' : '#FAFBF9';
        $text_color = $is_active ? '#fff' : 'var(--text-dark)';
        $border_color = $is_active ? 'var(--primary)' : 'var(--border-color)';
        echo '<a href="' . esc_url($page_url) . '" class="action-btn" style="background: ' . $bg_color . '; color: ' . $text_color . '; border: 1px solid ' . $border_color . '; padding: 6px 12px; text-decoration: none; font-weight: bold;">' . $i . '</a>';
    }
    
    // Next Link
    if ($current_page < $total_pages) {
        $next_url = add_query_arg(array('tab' => $tab_name, $page_arg => $current_page + 1));
        echo '<a href="' . esc_url($next_url) . '" class="action-btn" style="background: #FAFBF9; color: var(--primary); border: 1px solid var(--border-color); padding: 6px 12px; text-decoration: none;">Next &raquo;</a>';
    }
    
    echo '</div>';
}
?>

<div class="ovarias-admin-dashboard-wrapper">
    <!-- Header -->
    <div class="ovarias-admin-header">
        <div class="ovarias-admin-logo-layout">
            <h2>Ovarias Administrative Console</h2>
            <p>Standalone local business database dashboard manager</p>
        </div>
        <span class="ovarias-admin-version-tag">Version <?php echo OVARIAS_ADMIN_VERSION; ?></span>
    </div>

    <!-- Navigation Tabs -->
    <div class="ovarias-admin-tabs-nav">
        <button class="ovarias-admin-tab-btn <?php echo $active_tab === 'overview' ? 'active' : ''; ?>" data-tab="overview">Overview</button>
        <button class="ovarias-admin-tab-btn <?php echo $active_tab === 'parents' ? 'active' : ''; ?>" data-tab="parents">Intended Parents (<span class="count-parents"><?php echo $total_parents; ?></span>)</button>
        <button class="ovarias-admin-tab-btn <?php echo $active_tab === 'donors' ? 'active' : ''; ?>" data-tab="donors">Egg Donors (<span class="count-donors"><?php echo $total_donors; ?></span>)</button>
        <button class="ovarias-admin-tab-btn <?php echo $active_tab === 'inquiries' ? 'active' : ''; ?>" data-tab="inquiries">Match Inquiries (<span class="count-inquiries"><?php echo count($all_match_inquiries); ?></span>)</button>
        <button class="ovarias-admin-tab-btn <?php echo $active_tab === 'general-inquiries' ? 'active' : ''; ?>" data-tab="general-inquiries">General Inquiries (<span class="count-general-inquiries"><?php echo count($general_inquiries); ?></span>)</button>
    </div>

    <!-- -------------------------------------------------- -->
    <!-- TAB: Overview Metrics -->
    <!-- -------------------------------------------------- -->
    <div class="ovarias-admin-tab-content <?php echo $active_tab === 'overview' ? 'active' : ''; ?>" id="tab-overview">
        <div class="ovarias-admin-metrics-grid">
            <div class="ovarias-metric-card border-blue">
                <span class="label">Total Intended Parents</span>
                <span class="value val-parents"><?php echo $total_parents; ?></span>
                <span class="subtext"><?php echo $premium_parents; ?> Paid Premium Access</span>
            </div>
            <div class="ovarias-metric-card border-green">
                <span class="label">Total Registered Donors</span>
                <span class="value val-donors"><?php echo $total_donors; ?></span>
                <span class="subtext"><?php echo $fresh_donors_count; ?> Fresh / <?php echo $frozen_donors_count; ?> Frozen</span>
            </div>
            <div class="ovarias-metric-card border-orange">
                <span class="label">Open Match Inquiries</span>
                <span class="value val-pending-match"><?php echo $pending_match_inquiries; ?></span>
                <span class="subtext">Awaiting donor match updates</span>
            </div>
            <div class="ovarias-metric-card border-red">
                <span class="label">Open General Inquiries</span>
                <span class="value val-pending-general"><?php echo $pending_general_inquiries; ?></span>
                <span class="subtext">From public contact forms</span>
            </div>
        </div>

        <div class="ovarias-admin-quick-note" style="margin-top: 30px; background: #fff; border: 1px solid #e2e5df; padding: 25px; border-radius: 8px;">
            <h3 style="margin-top: 0; color: #2C2E27;">Local Sandbox Manager Mode</h3>
            <p style="margin: 0; font-size: 14px; line-height: 1.6; color: #555A4E;">
                This portal bypasses Zoho CRM synchronization and Stripe merchant gateways for offline sandboxing. Toggle client premium memberships instantly or update donor egg stocks using the control tables below. All updates apply live to the local WordPress database.
            </p>
        </div>
    </div>

    <!-- -------------------------------------------------- -->
    <!-- TAB: Manage Intended Parents -->
    <!-- -------------------------------------------------- -->
    <div class="ovarias-admin-tab-content <?php echo $active_tab === 'parents' ? 'active' : ''; ?>" id="tab-parents">
        <div class="ovarias-admin-table-container">
            <div class="table-header-toolbar">
                <button class="action-btn btn-open-modal" data-modal-type="parent" style="background: #2e7d32;">+ Add New Client</button>
                <input type="text" class="search-filter-input" id="search-parents" placeholder="Search parents by name or email...">
            </div>
            <table class="ovarias-admin-table" id="parents-table">
                <thead>
                    <tr>
                        <th>Client Details</th>
                        <th>Status</th>
                        <th>Amount Paid</th>
                        <th>Txn Ref ID</th>
                        <th>Payment Date</th>
                        <th>Premium Expiration</th>
                        <th style="text-align: right;">Manual Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($parents_sliced)): ?>
                        <tr>
                            <td colspan="7" class="empty-row">No intended parent accounts registered.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($parents_sliced as $p): 
                            $p_id = $p->ID;
                            $f_name = get_user_meta($p_id, 'first_name', true) ?: $p->first_name;
                            $l_name = get_user_meta($p_id, 'last_name', true) ?: $p->last_name;
                            $is_prem = get_user_meta($p_id, 'is_premium_parent', true) == '1';
                            $pay_date = get_user_meta($p_id, 'ovarias_payment_date', true);
                            
                            $days_rem = '—';
                            if ($is_prem && !empty($pay_date)) {
                                $expiry = strtotime($pay_date) + (365 * 24 * 60 * 60);
                                $curr = current_time('timestamp');
                                $days_rem = $curr > $expiry ? 'Expired' : ceil(($expiry - $curr) / (24 * 60 * 60)) . ' days remaining';
                            }
                        ?>
                            <tr class="parent-row">
                                <td>
                                    <strong><?php echo esc_html($f_name . ' ' . $l_name); ?></strong>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $is_prem ? 'active' : 'inactive'; ?>">
                                        <?php echo $is_prem ? 'Paid Access' : 'Restricted'; ?>
                                    </span>
                                </td>
                                <td><?php echo $is_prem ? '$199.00' : '—'; ?></td>
                                <td>
                                    <?php 
                                    if ($is_prem) {
                                        echo 'TXN_' . substr(md5($p_id . $pay_date), 0, 10);
                                    } else {
                                        echo '—';
                                    }
                                    ?>
                                </td>
                                <td><?php echo $pay_date ? date('Y-m-d H:i', strtotime($pay_date)) : '—'; ?></td>
                                <td><?php echo esc_html($days_rem); ?></td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <button class="action-btn btn-toggle-premium" data-user-id="<?php echo $p_id; ?>" data-status="<?php echo $is_prem ? '1' : '0'; ?>" style="margin-right: 5px;">
                                        <?php echo $is_prem ? 'Revoke Access' : 'Grant Access'; ?>
                                    </button>
                                    <button class="action-btn btn-delete-user" data-user-id="<?php echo $p_id; ?>" style="background: #c62828;">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <?php ovarias_admin_render_pagination($total_parents, $items_per_page, $p_page, 'p_page', 'parents'); ?>
        </div>
    </div>

    <!-- -------------------------------------------------- -->
    <!-- TAB: Manage Egg Donors -->
    <!-- -------------------------------------------------- -->
    <div class="ovarias-admin-tab-content <?php echo $active_tab === 'donors' ? 'active' : ''; ?>" id="tab-donors">
        <div class="ovarias-admin-table-container">
            <div class="table-header-toolbar">
                <button class="action-btn btn-open-modal" data-modal-type="donor" style="background: #2e7d32;">+ Add New Donor</button>
                <input type="text" class="search-filter-input" id="search-donors" placeholder="Search donors by ID or characteristics...">
            </div>
            <table class="ovarias-admin-table" id="donors-table">
                <thead>
                    <tr>
                        <th>Donor Account</th>
                        <th>Unique ID</th>
                        <th>Availability Status</th>
                        <th>Egg Category</th>
                        <th>Frozen Stock Details</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($donors_sliced)): ?>
                        <tr>
                            <td colspan="6" class="empty-row">No egg donor accounts registered in the database.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($donors_sliced as $d): 
                            $d_id = $d->ID;
                            $f_name = get_user_meta($d_id, 'first_name', true) ?: $d->first_name;
                            $l_name = get_user_meta($d_id, 'last_name', true) ?: $d->last_name;
                            
                            $donor_unique_id = get_user_meta($d_id, 'donor_id', true) ?: 'OVARIAS-' . $d_id;
                            $availability = get_user_meta($d_id, 'availability_status', true) ?: 'Available';
                            $egg_type = get_user_meta($d_id, 'egg_type', true) ?: 'Fresh';
                            
                            $num_eggs = get_user_meta($d_id, 'num_eggs', true) ?: '0';
                            $storage_country = get_user_meta($d_id, 'storage_country', true) ?: 'N/A';
                            
                            $pct = '0%';
                            if (function_exists('ovarias_profile_completion_percentage')) {
                                $pct = ovarias_profile_completion_percentage($d_id) . '%';
                            }
                        ?>
                            <tr class="donor-row" data-user-id="<?php echo $d_id; ?>">
                                <td>
                                    <?php 
                                    $gallery_ids = get_user_meta($d_id, 'profile_images_gallery', true) ?: array();
                                    $full_urls = array();
                                    foreach ($gallery_ids as $att_id) {
                                        $url = wp_get_attachment_url($att_id);
                                        if ($url) {
                                            $full_urls[] = $url;
                                        }
                                    }
                                    $avatar_id = get_user_meta($d_id, 'profile_image', true);
                                    $avatar_url = $avatar_id ? wp_get_attachment_url($avatar_id) : '';
                                    if (empty($full_urls) && $avatar_url) {
                                        $full_urls[] = $avatar_url;
                                    }

                                    $dob = get_user_meta($d_id, 'dob', true);
                                    $age = 'N/A';
                                    if ($dob) {
                                        $dob_date = date_create($dob);
                                        if ($dob_date) {
                                            $age = date_diff($dob_date, date_create('today'))->y;
                                        }
                                    }

                                    $detail_data = array(
                                        'user_id' => $d_id,
                                        'first_name' => $f_name,
                                        'last_name' => $l_name,
                                        'name' => trim($f_name . ' ' . $l_name) ?: ($donor_unique_id ?: ('Donor #' . $d_id)),
                                        'donor_id' => $donor_unique_id,
                                        'dob' => $dob,
                                        'age' => $age,
                                        'nationality' => get_user_meta($d_id, 'nationality', true) ?: '',
                                        'blood_group' => get_user_meta($d_id, 'blood_group', true) ?: '',
                                        'education' => get_user_meta($d_id, 'education_level', true) ?: '',
                                        'study' => get_user_meta($d_id, 'field_of_study', true) ?: '',
                                        'height' => get_user_meta($d_id, 'height', true) ?: '',
                                        'weight' => get_user_meta($d_id, 'weight', true) ?: '',
                                        'hair' => get_user_meta($d_id, 'hair_colour', true) ?: '',
                                        'eyes' => get_user_meta($d_id, 'eye_colour', true) ?: '',
                                        'num_donations' => get_user_meta($d_id, 'num_donations', true) ?: '0',
                                        'egg_type' => get_user_meta($d_id, 'egg_type', true) ?: 'Fresh',
                                        'num_eggs' => get_user_meta($d_id, 'num_eggs', true) ?: '0',
                                        'storage_country' => get_user_meta($d_id, 'storage_country', true) ?: '',
                                        'occupation' => get_user_meta($d_id, 'occupation', true) ?: '',
                                        'languages' => get_user_meta($d_id, 'languages_spoken', true) ?: '',
                                        'availability' => $availability,
                                        'hobbies' => get_user_meta($d_id, 'hobbies', true) ?: '',
                                        'about_me' => get_user_meta($d_id, 'about_me', true) ?: '',
                                        'why_donate' => get_user_meta($d_id, 'why_donate', true) ?: '',
                                        'avatar' => $avatar_url ?: (OVARIAS_ADMIN_URL . 'assets/css/placeholder.png'),
                                        'gallery' => $full_urls,
                                        
                                        // New PDF fields
                                        'ethnic_origin' => get_user_meta($d_id, 'ethnic_origin', true) ?: '',
                                        'race' => get_user_meta($d_id, 'race', true) ?: '',
                                        'ethnicity' => get_user_meta($d_id, 'ethnicity', true) ?: '',
                                        'body_type' => get_user_meta($d_id, 'body_type', true) ?: '',
                                        'face_shape' => get_user_meta($d_id, 'face_shape', true) ?: '',
                                        'nose_shape' => get_user_meta($d_id, 'nose_shape', true) ?: '',
                                        'lips_shape' => get_user_meta($d_id, 'lips_shape', true) ?: '',
                                        'hair_type' => get_user_meta($d_id, 'hair_type', true) ?: '',
                                        'skin_tone' => get_user_meta($d_id, 'skin_tone', true) ?: '',
                                        'freckles' => get_user_meta($d_id, 'freckles', true) ?: '',
                                        'favourite_lessons' => get_user_meta($d_id, 'favourite_lessons', true) ?: '',
                                        'proven_fertility' => get_user_meta($d_id, 'proven_fertility', true) ?: '',
                                        'hearing' => get_user_meta($d_id, 'hearing', true) ?: '',
                                        'vision' => get_user_meta($d_id, 'vision', true) ?: '',
                                        'wearing_glasses' => get_user_meta($d_id, 'wearing_glasses', true) ?: '',
                                        'wearing_lenses' => get_user_meta($d_id, 'wearing_lenses', true) ?: '',
                                        'surgeries' => get_user_meta($d_id, 'surgeries', true) ?: '',
                                        'allergies' => get_user_meta($d_id, 'allergies', true) ?: '',
                                        'dental_history' => get_user_meta($d_id, 'dental_history', true) ?: '',
                                        'twins_history' => get_user_meta($d_id, 'twins_history', true) ?: '',
                                        'alcohol_use' => get_user_meta($d_id, 'alcohol_use', true) ?: '',
                                        'smoking_tobacco' => get_user_meta($d_id, 'smoking_tobacco', true) ?: '',
                                        'vaping' => get_user_meta($d_id, 'vaping', true) ?: '',
                                        'drug_use' => get_user_meta($d_id, 'drug_use', true) ?: '',
                                        'medications' => get_user_meta($d_id, 'medications', true) ?: '',
                                        'decl_anonymous' => get_user_meta($d_id, 'decl_anonymous', true) ?: '',
                                        'decl_genetic_tests' => get_user_meta($d_id, 'decl_genetic_tests', true) ?: '',
                                        'zodiac_sign' => get_user_meta($d_id, 'zodiac_sign', true) ?: '',
                                        'fav_colour' => get_user_meta($d_id, 'fav_colour', true) ?: '',
                                        'fav_dish' => get_user_meta($d_id, 'fav_dish', true) ?: '',
                                        'fav_season' => get_user_meta($d_id, 'fav_season', true) ?: '',
                                        'fav_holiday' => get_user_meta($d_id, 'fav_holiday', true) ?: '',
                                        'fav_sport' => get_user_meta($d_id, 'fav_sport', true) ?: '',
                                        'fav_music' => get_user_meta($d_id, 'fav_music', true) ?: '',
                                        'childhood_dream' => get_user_meta($d_id, 'childhood_dream', true) ?: '',
                                        'fav_author' => get_user_meta($d_id, 'fav_author', true) ?: '',
                                        'fav_movie' => get_user_meta($d_id, 'fav_movie', true) ?: '',
                                        'countries_visited' => get_user_meta($d_id, 'countries_visited', true) ?: '',
                                        'goals_in_life' => get_user_meta($d_id, 'goals_in_life', true) ?: '',
                                        'idols_heroes' => get_user_meta($d_id, 'idols_heroes', true) ?: '',
                                        'personality_words' => get_user_meta($d_id, 'personality_words', true) ?: '',
                                        'strong_side' => get_user_meta($d_id, 'strong_side', true) ?: '',
                                        'weak_side' => get_user_meta($d_id, 'weak_side', true) ?: '',
                                        'medical_history' => get_user_meta($d_id, 'medical_history', true) ?: array()
                                    );
                                    ?>
                                    <strong class="btn-view-admin-donor-profile" style="color: #7E8372; cursor: pointer; text-decoration: underline;" data-donor="<?php echo esc_attr(wp_json_encode($detail_data)); ?>">
                                        <?php echo esc_html(trim($f_name . ' ' . $l_name) ?: ($donor_unique_id ?: ('Donor #' . $d_id))); ?>
                                    </strong><br>
                                    <span style="font-size: 11px; color: #8A9181;">Completion: <strong><?php echo $pct; ?></strong></span>
                                    <br><span class="btn-view-admin-donor-profile" style="font-size: 11px; color: #2e7d32; cursor: pointer; font-weight: bold; display: inline-block; margin-top: 4px;" data-donor="<?php echo esc_attr(wp_json_encode($detail_data)); ?>">View Profile</span>
                                </td>
                                <td>
                                    <input type="text" class="table-inline-input donor-id-val" value="<?php echo esc_attr($donor_unique_id); ?>" style="width: 100px;">
                                </td>
                                <td>
                                    <select class="table-inline-select donor-avail-val">
                                        <option value="Available" <?php selected($availability, 'Available'); ?>>Available</option>
                                        <option value="Reserved" <?php selected($availability, 'Reserved'); ?>>Reserved</option>
                                        <option value="Temporarily Unavailable" <?php selected($availability, 'Temporarily Unavailable'); ?>>Temporarily Unavailable</option>
                                        <option value="Not Available" <?php selected($availability, 'Not Available'); ?>>Not Available</option>
                                    </select>
                                </td>
                                <td>
                                    <select class="table-inline-select donor-egg-type-val">
                                        <option value="Fresh" <?php selected($egg_type, 'Fresh'); ?>>Fresh Egg Donor</option>
                                        <option value="Frozen" <?php selected($egg_type, 'Frozen'); ?>>Frozen Egg Donor</option>
                                        <option value="Both" <?php selected($egg_type, 'Both'); ?>>Both Category</option>
                                    </select>
                                </td>
                                <td>
                                    <div class="frozen-stock-edit-wrapper" style="<?php echo ($egg_type === 'Frozen' || $egg_type === 'Both') ? 'display: flex;' : 'display: none;'; ?>; align-items: center; gap: 8px; white-space: nowrap;">
                                        <span>Eggs:</span>
                                        <input type="number" class="table-inline-input donor-eggs-val" value="<?php echo esc_attr($num_eggs); ?>" style="width: 55px; margin: 0; padding: 6px;">
                                        <span>Loc:</span>
                                        <input type="text" class="table-inline-input donor-country-val" value="<?php echo esc_attr($storage_country); ?>" style="width: 80px; margin: 0; padding: 6px;">
                                    </div>
                                    <span class="stock-n-a" style="<?php echo ($egg_type === 'Fresh') ? 'display: inline;' : 'display: none;'; ?>; color: #aaa;">—</span>
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <button class="action-btn btn-save-donor" style="margin-right: 5px;">Save</button>
                                    <button class="action-btn btn-delete-user" data-user-id="<?php echo $d_id; ?>" style="background: #c62828;">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <?php ovarias_admin_render_pagination($total_donors, $items_per_page, $d_page, 'd_page', 'donors'); ?>
        </div>
    </div>

    <!-- -------------------------------------------------- -->
    <!-- TAB: Match Inquiries -->
    <!-- -------------------------------------------------- -->
    <div class="ovarias-admin-tab-content <?php echo $active_tab === 'inquiries' ? 'active' : ''; ?>" id="tab-inquiries">
        <div class="ovarias-admin-table-container">
            <table class="ovarias-admin-table">
                <thead>
                    <tr>
                        <th>Parent Client</th>
                        <th>Target Donor ID</th>
                        <th>Message / Request specifications</th>
                        <th>Date Submitted</th>
                        <th>Inquiry Status</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($match_inquiries_sliced)): ?>
                        <tr>
                            <td colspan="6" class="empty-row">No matching inquiries submitted by clients yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($match_inquiries_sliced as $inq): ?>
                            <tr class="inquiry-row">
                                <td>
                                    <strong><?php echo esc_html($inq['parent_name']); ?></strong>
                                </td>
                                <td><strong><?php echo esc_html($inq['donor_id']); ?></strong></td>
                                <td style="max-width: 300px; white-space: normal; line-height: 1.4; font-size: 13px;">
                                    <?php echo esc_html($inq['message']); ?>
                                </td>
                                <td><?php echo esc_html(date('Y-m-d H:i', strtotime($inq['date']))); ?></td>
                                <td>
                                    <select class="table-inline-select inquiry-status-val">
                                        <option value="New" <?php selected($inq['status'], 'New'); ?>>New</option>
                                        <option value="In Progress" <?php selected($inq['status'], 'In Progress'); ?>>In Progress</option>
                                        <option value="Responded" <?php selected($inq['status'], 'Responded'); ?>>Responded</option>
                                        <option value="Closed" <?php selected($inq['status'], 'Closed'); ?>>Closed</option>
                                    </select>
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <button class="action-btn btn-save-inquiry" data-parent-id="<?php echo $inq['parent_id']; ?>" data-inquiry-id="<?php echo esc_attr($inq['id']); ?>">
                                        Update Status
                                    </button>
                                    <button class="action-btn btn-delete-inquiry" data-parent-id="<?php echo $inq['parent_id']; ?>" data-inquiry-id="<?php echo esc_attr($inq['id']); ?>" style="background: #c62828; margin-left: 5px;">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <?php ovarias_admin_render_pagination(count($all_match_inquiries), $items_per_page, $mi_page, 'mi_page', 'inquiries'); ?>
        </div>
    </div>

    <!-- -------------------------------------------------- -->
    <!-- TAB: General Public Inquiries -->
    <!-- -------------------------------------------------- -->
    <div class="ovarias-admin-tab-content <?php echo $active_tab === 'general-inquiries' ? 'active' : ''; ?>" id="tab-general-inquiries">
        <div class="ovarias-admin-table-container">
            <table class="ovarias-admin-table" id="general-inquiries-table">
                <thead>
                    <tr>
                        <th>Sender Details</th>
                        <th>Inquiry Type</th>
                        <th>Message</th>
                        <th>Date Submitted</th>
                        <th>Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($general_inquiries_sliced)): ?>
                        <tr>
                            <td colspan="6" class="empty-row">No general public inquiries registered in the database.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($general_inquiries_sliced as $g_inq): 
                            $g_id = $g_inq['id'];
                            $g_type = $g_inq['type'];
                            $g_email = $g_inq['email'];
                            $g_name = isset($g_inq['name']) ? $g_inq['name'] : 'Not set';
                            $g_phone = isset($g_inq['phone']) ? $g_inq['phone'] : 'Not set';
                            $g_msg = isset($g_inq['message']) ? $g_inq['message'] : '';
                            $g_status = isset($g_inq['status']) ? $g_inq['status'] : 'New';
                            $g_date = isset($g_inq['date']) ? $g_inq['date'] : '';
                        ?>
                            <tr class="general-inquiry-row">
                                <td>
                                    <strong><?php echo esc_html($g_name); ?></strong><br>
                                    <a href="mailto:<?php echo esc_attr($g_email); ?>?subject=Re:%20Ovarias%20Inquiry%20-%20<?php echo esc_attr(rawurlencode($g_type)); ?>" class="muted-email" style="text-decoration: underline; color: var(--primary); font-weight: bold;"><?php echo esc_html($g_email); ?></a><br>
                                    <span style="font-size: 11px; color: #8A9181;">Phone: <?php echo esc_html($g_phone); ?></span>
                                </td>
                                <td>
                                    <span class="status-badge" style="background: #e2e8f0; color: #4a5568;">
                                        <?php echo esc_html($g_type); ?>
                                    </span>
                                </td>
                                <td style="max-width: 300px; white-space: normal; line-height: 1.4; font-size: 13px;">
                                    <?php echo esc_html($g_msg); ?>
                                </td>
                                <td><?php echo $g_date ? esc_html(date('Y-m-d H:i', strtotime($g_date))) : '—'; ?></td>
                                <td>
                                    <select class="table-inline-select general-inq-status-val">
                                        <option value="New" <?php selected($g_status, 'New'); ?>>New</option>
                                        <option value="In Progress" <?php selected($g_status, 'In Progress'); ?>>In Progress</option>
                                        <option value="Completed/Closed" <?php selected($g_status, 'Completed/Closed'); ?>>Completed/Closed</option>
                                    </select>
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <button class="action-btn btn-save-general-inquiry" data-inquiry-id="<?php echo esc_attr($g_id); ?>" style="margin-right: 5px;">Update</button>
                                    <button class="action-btn btn-delete-general-inquiry" data-inquiry-id="<?php echo esc_attr($g_id); ?>" style="background: #c62828;">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <?php ovarias_admin_render_pagination(count($general_inquiries), $items_per_page, $gi_page, 'gi_page', 'general-inquiries'); ?>
        </div>
    </div>
</div>

<div id="ovarias-create-user-modal" class="ovarias-admin-modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 9999; font-family: sans-serif;">
    <div class="ovarias-admin-modal-box" style="background: #fff; border-radius: 8px; max-width: 520px; width: 90%; padding: 30px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); position: relative; box-sizing: border-box;">
        <h3 id="modal-title" style="margin-top: 0; color: #7E8372; font-size: 18px; margin-bottom: 20px;">Add New Account</h3>
        <span class="btn-close-modal-x" style="position: absolute; top: 15px; right: 20px; font-size: 24px; color: #aaa; cursor: pointer; line-height: 1;">&times;</span>
        <form id="ovarias-create-user-form">
            <input type="hidden" id="new-user-type" name="type" value="donor">
            
            <div style="max-height: 55vh; overflow-y: auto; padding-right: 12px; margin-bottom: 20px;">
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">First Name</label>
                    <input type="text" id="new-first-name" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Last Name</label>
                    <input type="text" id="new-last-name" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Username</label>
                    <input type="text" id="new-username" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Account Password</label>
                    <input type="password" id="new-password" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                </div>
                
                <!-- Donor Specific Profile Fields -->
                <div id="donor-specific-fields" style="display: none;">
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Unique Donor ID</label>
                        <input type="text" id="new-donor-id" class="table-inline-input" style="width: 100%; box-sizing: border-box;" placeholder="e.g. OVARIAS-24">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Date of Birth</label>
                        <input type="date" id="new-donor-dob" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Nationality</label>
                        <input type="text" id="new-donor-nationality" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Blood Group</label>
                        <select id="new-donor-blood" class="table-inline-select" style="width: 100%; box-sizing: border-box;">
                            <option value="">Select Group</option>
                            <option value="A+">A+</option>
                            <option value="A-">A-</option>
                            <option value="B+">B+</option>
                            <option value="B-">B-</option>
                            <option value="AB+">AB+</option>
                            <option value="AB-">AB-</option>
                            <option value="O+">O+</option>
                            <option value="O-">O-</option>
                        </select>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Height (cm)</label>
                        <input type="number" id="new-donor-height" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Weight (kg)</label>
                        <input type="number" id="new-donor-weight" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Eye Colour</label>
                        <input type="text" id="new-donor-eyes" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Hair Colour</label>
                        <input type="text" id="new-donor-hair" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Level of Education</label>
                        <input type="text" id="new-donor-education" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Field of Study</label>
                        <input type="text" id="new-donor-study" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Occupation</label>
                        <input type="text" id="new-donor-occupation" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Languages Spoken</label>
                        <input type="text" id="new-donor-languages" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Availability Status</label>
                        <select id="new-donor-avail" class="table-inline-select" style="width: 100%; box-sizing: border-box;">
                            <option value="Available">Available</option>
                            <option value="Reserved">Reserved</option>
                            <option value="Temporarily Unavailable">Temporarily Unavailable</option>
                            <option value="Not Available">Not Available</option>
                        </select>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Egg Category</label>
                        <select id="new-donor-egg-type" class="table-inline-select" style="width: 100%; box-sizing: border-box;">
                            <option value="Fresh">Fresh Egg Donor</option>
                            <option value="Frozen">Frozen Egg Donor</option>
                            <option value="Both">Both Category</option>
                        </select>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Number of Eggs</label>
                        <input type="number" id="new-donor-num-eggs" class="table-inline-input" style="width: 100%; box-sizing: border-box;" value="0">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Storage Country / Location</label>
                        <input type="text" id="new-donor-storage" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">About Me</label>
                        <textarea id="new-donor-about" class="table-inline-input" style="width: 100%; box-sizing: border-box; min-height: 80px;"></textarea>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Hobbies & Interests</label>
                        <textarea id="new-donor-hobbies" class="table-inline-input" style="width: 100%; box-sizing: border-box; min-height: 80px;"></textarea>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Why I Want to Donate</label>
                        <textarea id="new-donor-why" class="table-inline-input" style="width: 100%; box-sizing: border-box; min-height: 80px;"></textarea>
                    </div>

                    <!-- Basic Origin Details -->
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Ethnic Origin</label>
                        <input type="text" id="new-donor-ethnic-origin" class="table-inline-input" style="width: 100%; box-sizing: border-box;" placeholder="e.g. European">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Race</label>
                        <input type="text" id="new-donor-race" class="table-inline-input" style="width: 100%; box-sizing: border-box;" placeholder="e.g. Caucasian">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Ethnicity</label>
                        <input type="text" id="new-donor-ethnicity" class="table-inline-input" style="width: 100%; box-sizing: border-box;" placeholder="e.g. Ukrainian">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Body Type</label>
                        <input type="text" id="new-donor-body-type" class="table-inline-input" style="width: 100%; box-sizing: border-box;" placeholder="e.g. Middle, Slim">
                    </div>

                    <!-- Physical Features Details -->
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Face Shape</label>
                        <input type="text" id="new-donor-face-shape" class="table-inline-input" style="width: 100%; box-sizing: border-box;" placeholder="e.g. Oval">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Nose Shape / Size</label>
                        <input type="text" id="new-donor-nose-shape" class="table-inline-input" style="width: 100%; box-sizing: border-box;" placeholder="e.g. Normal Small Straight">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Lips Shape</label>
                        <input type="text" id="new-donor-lips-shape" class="table-inline-input" style="width: 100%; box-sizing: border-box;" placeholder="e.g. Thin Normal">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Hair Type</label>
                        <input type="text" id="new-donor-hair-type" class="table-inline-input" style="width: 100%; box-sizing: border-box;" placeholder="e.g. Straight, Curly">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Skin Tone</label>
                        <input type="text" id="new-donor-skin-tone" class="table-inline-input" style="width: 100%; box-sizing: border-box;" placeholder="e.g. Tan, Fair">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Freckles</label>
                        <input type="text" id="new-donor-freckles" class="table-inline-input" style="width: 100%; box-sizing: border-box;" placeholder="e.g. Few, None">
                    </div>

                    <!-- Education Detail -->
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Favourite Lessons</label>
                        <input type="text" id="new-donor-favourite-lessons" class="table-inline-input" style="width: 100%; box-sizing: border-box;" placeholder="e.g. Nature studies">
                    </div>

                    <!-- Health & Lifestyle Details -->
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Proven Fertility</label>
                        <select id="new-donor-proven-fertility" class="table-inline-select" style="width: 100%; box-sizing: border-box;">
                            <option value="">Select</option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Hearing</label>
                        <input type="text" id="new-donor-hearing" class="table-inline-input" style="width: 100%; box-sizing: border-box;" placeholder="e.g. Great">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Vision</label>
                        <input type="text" id="new-donor-vision" class="table-inline-input" style="width: 100%; box-sizing: border-box;" placeholder="e.g. Great">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Inclination to Wearing Glasses</label>
                        <select id="new-donor-wearing-glasses" class="table-inline-select" style="width: 100%; box-sizing: border-box;">
                            <option value="">Select</option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Wearing Contact Lenses</label>
                        <select id="new-donor-wearing-lenses" class="table-inline-select" style="width: 100%; box-sizing: border-box;">
                            <option value="">Select</option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">History of Surgeries</label>
                        <input type="text" id="new-donor-surgeries" class="table-inline-input" style="width: 100%; box-sizing: border-box;" placeholder="e.g. No">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Allergies</label>
                        <input type="text" id="new-donor-allergies" class="table-inline-input" style="width: 100%; box-sizing: border-box;" placeholder="e.g. No">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Dental / Orthodontic History</label>
                        <input type="text" id="new-donor-dental-history" class="table-inline-input" style="width: 100%; box-sizing: border-box;" placeholder="e.g. No">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Twins Family History</label>
                        <input type="text" id="new-donor-twins-history" class="table-inline-input" style="width: 100%; box-sizing: border-box;" placeholder="e.g. No">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Alcohol Use</label>
                        <select id="new-donor-alcohol-use" class="table-inline-select" style="width: 100%; box-sizing: border-box;">
                            <option value="">Select</option>
                            <option value="No">No</option>
                            <option value="Socially">Socially</option>
                            <option value="Yes">Yes</option>
                        </select>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Smoking (Tobacco)</label>
                        <select id="new-donor-smoking-tobacco" class="table-inline-select" style="width: 100%; box-sizing: border-box;">
                            <option value="">Select</option>
                            <option value="No">No</option>
                            <option value="Yes">Yes</option>
                        </select>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Vaping (Nicotine/THC)</label>
                        <select id="new-donor-vaping" class="table-inline-select" style="width: 100%; box-sizing: border-box;">
                            <option value="">Select</option>
                            <option value="No">No</option>
                            <option value="Yes">Yes</option>
                        </select>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Recreational Drug Use</label>
                        <select id="new-donor-drug-use" class="table-inline-select" style="width: 100%; box-sizing: border-box;">
                            <option value="">Select</option>
                            <option value="No">No</option>
                            <option value="Yes">Yes</option>
                        </select>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Medications</label>
                        <input type="text" id="new-donor-medications" class="table-inline-input" style="width: 100%; box-sizing: border-box;" placeholder="e.g. No">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Anonymity Confirmation Agreement</label>
                        <select id="new-donor-decl-anonymous" class="table-inline-select" style="width: 100%; box-sizing: border-box;">
                            <option value="">Select</option>
                            <option value="YES">YES</option>
                            <option value="NO">NO</option>
                        </select>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Open to Genetic Tests</label>
                        <select id="new-donor-decl-genetic-tests" class="table-inline-select" style="width: 100%; box-sizing: border-box;">
                            <option value="">Select</option>
                            <option value="YES">YES</option>
                            <option value="NO">NO</option>
                        </select>
                    </div>

                    <!-- Preferences & Personality Details -->
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Sign of the Zodiac</label>
                        <input type="text" id="new-donor-zodiac-sign" class="table-inline-input" style="width: 100%; box-sizing: border-box;" placeholder="e.g. Capricorn">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Favourite Colour</label>
                        <input type="text" id="new-donor-fav-colour" class="table-inline-input" style="width: 100%; box-sizing: border-box;" placeholder="e.g. Amber Gold">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Favourite Dish</label>
                        <input type="text" id="new-donor-fav-dish" class="table-inline-input" style="width: 100%; box-sizing: border-box;" placeholder="e.g. Varenyky">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Favourite Season</label>
                        <input type="text" id="new-donor-fav-season" class="table-inline-input" style="width: 100%; box-sizing: border-box;" placeholder="e.g. Late Summer">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Favourite Holiday</label>
                        <input type="text" id="new-donor-fav-holiday" class="table-inline-input" style="width: 100%; box-sizing: border-box;" placeholder="e.g. Christmas Eve">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Favourite Kind of Sport</label>
                        <input type="text" id="new-donor-fav-sport" class="table-inline-input" style="width: 100%; box-sizing: border-box;" placeholder="e.g. Rock climbing">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Favourite Musical Style</label>
                        <input type="text" id="new-donor-fav-music" class="table-inline-input" style="width: 100%; box-sizing: border-box;" placeholder="e.g. Synthwave">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Childhood Dream</label>
                        <input type="text" id="new-donor-childhood-dream" class="table-inline-input" style="width: 100%; box-sizing: border-box;" placeholder="e.g. To score soundtracks">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Favourite Author / Book</label>
                        <input type="text" id="new-donor-fav-author" class="table-inline-input" style="width: 100%; box-sizing: border-box;" placeholder="e.g. Dune">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Favourite Movie</label>
                        <input type="text" id="new-donor-fav-movie" class="table-inline-input" style="width: 100%; box-sizing: border-box;" placeholder="e.g. Atmospheric sci-fi">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Countries Visited</label>
                        <input type="text" id="new-donor-countries-visited" class="table-inline-input" style="width: 100%; box-sizing: border-box;" placeholder="e.g. Austria, Norway">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Goals in Life</label>
                        <input type="text" id="new-donor-goals-in-life" class="table-inline-input" style="width: 100%; box-sizing: border-box;" placeholder="e.g. Open audio studio">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Idols / Heroes</label>
                        <input type="text" id="new-donor-idols-heroes" class="table-inline-input" style="width: 100%; box-sizing: border-box;" placeholder="e.g. Pioneering composers">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Words Describing Personality</label>
                        <input type="text" id="new-donor-personality-words" class="table-inline-input" style="width: 100%; box-sizing: border-box;" placeholder="e.g. Analytical, calm, curious">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Strong Side</label>
                        <input type="text" id="new-donor-strong-side" class="table-inline-input" style="width: 100%; box-sizing: border-box;" placeholder="e.g. Deep focus">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Weak Side</label>
                        <input type="text" id="new-donor-weak-side" class="table-inline-input" style="width: 100%; box-sizing: border-box;" placeholder="e.g. Getting overly lost">
                    </div>

                    <!-- Medical & Family History Checkbox Grid -->
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 10px; color: #555A4E;">Donor Medical & Family History Checklist</label>
                        <div style="max-height: 250px; overflow-y: auto; background: #fafbf9; padding: 10px; border-radius: 6px; border: 1px solid #c2c7bd; display: grid; grid-template-columns: 1fr; gap: 8px;">
                            <?php
                            $admin_conditions = array(
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
                            foreach ($admin_conditions as $key => $label) {
                                ?>
                                <label style="display: flex; align-items: center; gap: 8px; font-size: 11px; font-weight: normal; cursor: pointer; color: #555A4E;">
                                    <input type="checkbox" class="new-donor-med-checkbox" data-key="<?php echo esc_attr($key); ?>" value="Yes" style="width: auto; margin: 0; cursor: pointer;">
                                    <span><?php echo esc_html($label); ?></span>
                                </label>
                                <?php
                            }
                            ?>
                        </div>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Primary Profile Picture</label>
                        <input type="file" id="new-donor-profile-image" name="profile_image" accept="image/*" style="width: 100%; box-sizing: border-box; font-size: 12px;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Additional Gallery Photos (Multiple)</label>
                        <input type="file" id="new-donor-gallery" name="donor_gallery[]" accept="image/*" multiple style="width: 100%; box-sizing: border-box; font-size: 12px;">
                    </div>
                </div>
                
                <!-- Intended Parent Specific Profile Fields -->
                <div id="parent-specific-fields" style="display: none;">
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Country</label>
                        <input type="text" id="new-parent-country" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Preferences & Requirements</label>
                        <textarea id="new-parent-preferences" class="table-inline-input" style="width: 100%; box-sizing: border-box; min-height: 80px;"></textarea>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Internal Notes / History</label>
                        <textarea id="new-parent-notes" class="table-inline-input" style="width: 100%; box-sizing: border-box; min-height: 80px;"></textarea>
                    </div>
                </div>
            </div>
            
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="action-btn btn-close-modal-cancel" style="background: #ccc; color: #333;">Cancel</button>
                <button type="submit" class="action-btn" style="background: #2e7d32;">Create User</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Dialog for Details (Donor Profile View for Admin) -->
<div class="ovarias-parent-modal" id="donor-detail-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 99999; font-family: sans-serif;">
    <div class="ovarias-modal-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"></div>
    <div class="ovarias-modal-container" style="position: relative; background: #fff; width: 92%; max-width: 650px; max-height: 85vh; border-radius: 12px; overflow-y: auto; z-index: 10; padding: 40px; box-shadow: 0 15px 35px rgba(0,0,0,0.15); border: 1px solid var(--border-color); box-sizing: border-box;">

        <!-- View Mode Panel -->
        <div id="donor-modal-view-panel">
            <div class="ovarias-modal-content">
            <div class="ovarias-modal-header-layout" style="display: flex; flex-direction: column; gap: 15px; margin-bottom: 20px; align-items: center; width: 100%; border-bottom: 1px solid var(--border-color); padding-bottom: 20px;">
                <div class="ovarias-modal-image-wrapper" style="width: 100%; max-height: 450px; overflow: hidden; border-radius: 8px; border: 1px solid var(--border-color); background: #fcfcfc; display: flex; align-items: center; justify-content: center; position: relative;">
                    <img src="" alt="Donor Photo" class="ovarias-modal-avatar" id="modal-avatar" style="max-height: 450px; max-width: 100%; width: auto; height: auto; object-fit: contain; border-radius: 0; border: none; margin: 0 auto; display: block;">
                </div>
                <!-- Gallery Thumbnails Container -->
                <div class="ovarias-modal-gallery" id="modal-gallery" style="display: flex; gap: 8px; overflow-x: auto; padding: 5px 0; margin-top: 5px; justify-content: center; width: 100%;"></div>
                
                <div class="ovarias-modal-header-info" style="text-align: center; width: 100%;">
                    <h2 id="modal-name" style="margin-top: 0; margin-bottom: 8px; color: var(--text-dark); font-size: 24px; font-weight: 800;"></h2>
                    <div class="ovarias-modal-badges" style="display: flex; gap: 12px; justify-content: center;">
                        <span class="ovarias-modal-badge" style="background: var(--primary-light); color: #4D5842; padding: 6px 14px; border-radius: 30px; font-size: 13px; font-weight: 600; border: 1px solid #CDDCBF;">Age: <strong id="modal-age"></strong></span>
                        <span class="ovarias-modal-badge" style="background: var(--primary-light); color: #4D5842; padding: 6px 14px; border-radius: 30px; font-size: 13px; font-weight: 600; border: 1px solid #CDDCBF;">Blood: <strong id="modal-blood"></strong></span>
                    </div>
                </div>
            </div>
            
            <div class="ovarias-modal-details-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px 30px; margin-bottom: 30px; background: var(--primary-light); padding: 24px; border-radius: 8px; border: 1px solid var(--border-color);">
                <div class="ovarias-modal-group" style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Donor ID</label>
                    <span id="modal-donor-id" style="font-size: 15px; color: var(--primary); font-weight: bold;"></span>
                </div>
                <div class="ovarias-modal-group" style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Nationality</label>
                    <span id="modal-nationality" style="font-size: 15px; color: var(--text-dark); font-weight: 600;"></span>
                </div>
                <div class="ovarias-modal-group" style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Education</label>
                    <span id="modal-education" style="font-size: 15px; color: var(--text-dark); font-weight: 600;"></span>
                </div>
                <div class="ovarias-modal-group" style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Height</label>
                    <span id="modal-height" style="font-size: 15px; color: var(--text-dark); font-weight: 600;"></span>
                </div>
                <div class="ovarias-modal-group" style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Weight</label>
                    <span id="modal-weight" style="font-size: 15px; color: var(--text-dark); font-weight: 600;"></span>
                </div>
                <div class="ovarias-modal-group" style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Hair Colour</label>
                    <span id="modal-hair" style="font-size: 15px; color: var(--text-dark); font-weight: 600;"></span>
                </div>
                <div class="ovarias-modal-group" style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Eye Colour</label>
                    <span id="modal-eyes" style="font-size: 15px; color: var(--text-dark); font-weight: 600;"></span>
                </div>
                <div class="ovarias-modal-group" style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Previous Donations</label>
                    <span id="modal-num-donations" style="font-size: 15px; color: var(--text-dark); font-weight: 600;"></span>
                </div>
                <div class="ovarias-modal-group" id="modal-egg-type-container" style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Egg Category</label>
                    <span id="modal-egg-type" style="font-size: 15px; color: var(--text-dark); font-weight: 600;"></span>
                </div>
                <div class="ovarias-modal-group" id="modal-num-eggs-container" style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Eggs Available</label>
                    <span id="modal-num-eggs" style="font-size: 15px; color: var(--text-dark); font-weight: 600;"></span>
                </div>
                <div class="ovarias-modal-group" id="modal-storage-country-container" style="display: flex; flex-direction: column; gap: 6px; grid-column: span 2;">
                    <label style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Storage Location</label>
                    <span id="modal-storage-country" style="font-size: 15px; color: var(--text-dark); font-weight: 600;"></span>
                </div>
                <div class="ovarias-modal-group" style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Ethnic Origin</label>
                    <span id="modal-ethnic-origin" style="font-size: 15px; color: var(--text-dark); font-weight: 600;"></span>
                </div>
                <div class="ovarias-modal-group" style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Race & Ethnicity</label>
                    <span id="modal-race-ethnicity" style="font-size: 15px; color: var(--text-dark); font-weight: 600;"></span>
                </div>
                <div class="ovarias-modal-group" style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Body & Face Shape</label>
                    <span id="modal-body-face" style="font-size: 15px; color: var(--text-dark); font-weight: 600;"></span>
                </div>
                <div class="ovarias-modal-group" style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Nose & Lips</label>
                    <span id="modal-nose-lips" style="font-size: 15px; color: var(--text-dark); font-weight: 600;"></span>
                </div>
                <div class="ovarias-modal-group" style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Hair Type & Skin</label>
                    <span id="modal-hairtype-skin" style="font-size: 15px; color: var(--text-dark); font-weight: 600;"></span>
                </div>
                <div class="ovarias-modal-group" style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Freckles</label>
                    <span id="modal-freckles" style="font-size: 15px; color: var(--text-dark); font-weight: 600;"></span>
                </div>
            </div>

            <!-- Narrative details sections -->
            <div class="ovarias-modal-sections" style="border-top: 1px solid var(--border-color); padding-top: 28px; display: flex; flex-direction: column; gap: 20px;">
                <div class="ovarias-modal-section">
                    <h3 style="margin: 0 0 10px 0; font-size: 16px; font-weight: 700; color: var(--primary); text-transform: uppercase; letter-spacing: 0.5px;">Health & Lifestyle</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; font-size: 14px; color: var(--text-dark); line-height: 1.6;">
                        <div><strong>Proven Fertility:</strong> <span id="modal-proven-fertility"></span></div>
                        <div><strong>Hearing / Vision:</strong> <span id="modal-hearing-vision"></span></div>
                        <div><strong>Glasses / Contact Lenses:</strong> <span id="modal-glasses-lenses"></span></div>
                        <div><strong>Surgeries / Allergies:</strong> <span id="modal-surgeries-allergies"></span></div>
                        <div><strong>Dental / Twins History:</strong> <span id="modal-dental-twins"></span></div>
                        <div><strong>Smoking / Alcohol / Vaping:</strong> <span id="modal-smoking-alcohol-vaping"></span></div>
                        <div><strong>Recreational Drugs / Meds:</strong> <span id="modal-drugs-meds"></span></div>
                        <div><strong>Anonymity & Genetic Testing:</strong> <span id="modal-anonymity-testing"></span></div>
                    </div>
                </div>

                <div class="ovarias-modal-section" style="border-top: 1px solid var(--border-color); padding-top: 20px;">
                    <h3 style="margin: 0 0 10px 0; font-size: 16px; font-weight: 700; color: var(--primary); text-transform: uppercase; letter-spacing: 0.5px;">Preferences & Personality</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; font-size: 14px; color: var(--text-dark); line-height: 1.6;">
                        <div><strong>Zodiac Sign:</strong> <span id="modal-zodiac-sign"></span></div>
                        <div><strong>Favourite Colour:</strong> <span id="modal-fav-colour"></span></div>
                        <div><strong>Favourite Dish / Season:</strong> <span id="modal-fav-dish-season"></span></div>
                        <div><strong>Favourite Holiday / Sport:</strong> <span id="modal-fav-holiday-sport"></span></div>
                        <div><strong>Favourite Music Style:</strong> <span id="modal-fav-music"></span></div>
                        <div><strong>Childhood Dream:</strong> <span id="modal-childhood-dream"></span></div>
                        <div><strong>Favourite Book / Movie:</strong> <span id="modal-fav-book-movie"></span></div>
                        <div><strong>Countries Visited:</strong> <span id="modal-countries-visited"></span></div>
                        <div><strong>Goals in Life:</strong> <span id="modal-goals-in-life"></span></div>
                        <div><strong>Idols & Heroes:</strong> <span id="modal-idols-heroes"></span></div>
                        <div><strong>Personality Words:</strong> <span id="modal-personality-words"></span></div>
                        <div><strong>Strong / Weak Side:</strong> <span id="modal-strong-weak"></span></div>
                        <div><strong>Favourite Lessons:</strong> <span id="modal-favourite-lessons-val"></span></div>
                    </div>
                </div>

                <div class="ovarias-modal-section" style="border-top: 1px solid var(--border-color); padding-top: 20px;">
                    <h3 style="margin: 0 0 10px 0; font-size: 16px; font-weight: 700; color: var(--primary); text-transform: uppercase; letter-spacing: 0.5px;">Medical & Family History</h3>
                    <div id="modal-medical-history-display" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 8px; font-size: 12px; max-height: 200px; overflow-y: auto; background: #fafbf9; padding: 12px; border-radius: 6px; border: 1px solid var(--border-color);">
                        <!-- Loaded dynamically via JS -->
                    </div>
                </div>
            <div class="ovarias-modal-sections" style="border-top: 1px solid var(--border-color); padding-top: 28px; display: flex; flex-direction: column; gap: 20px;">
                <div class="ovarias-modal-section">
                    <h3 style="margin: 0 0 10px 0; font-size: 16px; font-weight: 700; color: var(--primary); text-transform: uppercase; letter-spacing: 0.5px;">About Me</h3>
                    <p id="modal-about" style="margin: 0; font-size: 15px; color: var(--text-dark); line-height: 1.6;"></p>
                </div>
                <div class="ovarias-modal-section">
                    <h3 style="margin: 0 0 10px 0; font-size: 16px; font-weight: 700; color: var(--primary); text-transform: uppercase; letter-spacing: 0.5px;">Hobbies & Interests</h3>
                    <p id="modal-hobbies" style="margin: 0; font-size: 15px; color: var(--text-dark); line-height: 1.6;"></p>
                </div>
                <div class="ovarias-modal-section">
                    <h3 style="margin: 0 0 10px 0; font-size: 16px; font-weight: 700; color: var(--primary); text-transform: uppercase; letter-spacing: 0.5px;">Why I Want to Donate</h3>
                    <p id="modal-why" style="margin: 0; font-size: 15px; color: var(--text-dark); line-height: 1.6;"></p>
                </div>
            </div>
            
            <div style="margin-top: 35px; display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="action-btn btn-trigger-edit-from-view" style="background: #2e7d32; color: #fff; border: none; padding: 10px 24px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 14px;">Edit Profile</button>
                <button type="button" class="action-btn btn-close-donor-modal" style="background: var(--primary); color: #fff; border: none; padding: 10px 24px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 14px;">Close</button>
            </div>
        </div>
    </div>
</div> <!-- end donor-modal-view-panel -->

    <!-- Edit Mode Panel inside the single modal -->
    <div id="donor-modal-edit-panel" style="display: none;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 12px; margin-right: 45px;">
            <h3 style="margin: 0; color: #555A4E; font-size: 20px; font-weight: bold;">Edit Donor Profile</h3>
            <button type="button" class="action-btn btn-cancel-edit-to-view" style="background: #eee; color: #333; border: 1px solid #ccc; padding: 6px 14px; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 13px;">Back to Profile</button>
        </div>
        
        <form id="ovarias-edit-donor-form" enctype="multipart/form-data">
            <input type="hidden" id="edit-donor-user-id" name="user_id" value="">
            
            <div style="max-height: 65vh; overflow-y: auto; padding-right: 12px; margin-bottom: 20px;">
                <!-- Prominent Photos Section -->
                <div style="background: #FAFBF9; border: 1px solid #c2c7bd; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
                    <h4 style="margin: 0 0 12px 0; color: #555A4E; font-size: 14px; font-weight: bold; border-bottom: 1px solid #e0e4dc; padding-bottom: 6px;">Profile & Gallery Photos</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Primary Profile Picture</label>
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                <img id="edit-avatar-preview" src="" style="width: 48px; height: 48px; object-fit: cover; border-radius: 50%; border: 1px solid #bbb; display: none;">
                                <span id="edit-avatar-note" style="font-size: 11px; color: #666;">Current photo</span>
                            </div>
                            <input type="file" id="edit-donor-avatar" name="profile_image" accept="image/*" class="table-inline-input" style="width: 100%; box-sizing: border-box; padding: 6px; font-size: 12px; background: #fff;">
                            <span style="display: block; font-size: 11px; color: #777; margin-top: 4px;">Choose a file to replace the main profile photo</span>
                        </div>
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Additional Gallery Photos (Multiple)</label>
                            <div id="edit-gallery-preview" style="display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 8px; min-height: 24px; align-items: center;"></div>
                            <input type="file" id="edit-donor-gallery" name="donor_gallery[]" multiple accept="image/*" class="table-inline-input" style="width: 100%; box-sizing: border-box; padding: 6px; font-size: 12px; background: #fff;">
                            <span style="display: block; font-size: 11px; color: #2e7d32; font-weight: bold; margin-top: 4px;">You can select multiple photos at one time (hold Ctrl/Cmd to select several)</span>
                        </div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">First Name</label>
                        <input type="text" id="edit-first-name" name="first_name" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Last Name</label>
                        <input type="text" id="edit-last-name" name="last_name" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Unique Donor ID</label>
                        <input type="text" id="edit-donor-id" name="donor_id" class="table-inline-input" style="width: 100%; box-sizing: border-box;" placeholder="e.g. OVARIAS-24">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Availability Status</label>
                        <select id="edit-donor-avail" name="availability_status" class="table-inline-select" style="width: 100%; box-sizing: border-box;">
                            <option value="Available">Available</option>
                            <option value="Reserved">Reserved</option>
                            <option value="Temporarily Unavailable">Temporarily Unavailable</option>
                            <option value="Not Available">Not Available</option>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Egg Category</label>
                        <select id="edit-donor-egg-type" name="egg_type" class="table-inline-select" style="width: 100%; box-sizing: border-box;">
                            <option value="Fresh">Fresh Egg Donor</option>
                            <option value="Frozen">Frozen Egg Donor</option>
                            <option value="Both">Both Category</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Frozen Eggs Available</label>
                        <input type="number" id="edit-donor-num-eggs" name="num_eggs" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Storage Country / Loc</label>
                        <input type="text" id="edit-donor-storage" name="storage_country" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Date of Birth</label>
                        <input type="date" id="edit-donor-dob" name="dob" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Nationality</label>
                        <input type="text" id="edit-donor-nationality" name="nationality" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Blood Group</label>
                        <select id="edit-donor-blood" name="blood_group" class="table-inline-select" style="width: 100%; box-sizing: border-box;">
                            <option value="">Select Group</option>
                            <option value="A+">A+</option>
                            <option value="A-">A-</option>
                            <option value="B+">B+</option>
                            <option value="B-">B-</option>
                            <option value="AB+">AB+</option>
                            <option value="AB-">AB-</option>
                            <option value="O+">O+</option>
                            <option value="O-">O-</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Height (cm)</label>
                        <input type="number" id="edit-donor-height" name="height" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Weight (kg)</label>
                        <input type="number" id="edit-donor-weight" name="weight" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Eye Colour</label>
                        <input type="text" id="edit-donor-eyes" name="eye_colour" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Hair Colour</label>
                        <input type="text" id="edit-donor-hair" name="hair_colour" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Level of Education</label>
                        <input type="text" id="edit-donor-education" name="education_level" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Field of Study</label>
                        <input type="text" id="edit-donor-study" name="field_of_study" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Occupation</label>
                        <input type="text" id="edit-donor-occupation" name="occupation" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Languages Spoken</label>
                        <input type="text" id="edit-donor-languages" name="languages_spoken" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                </div>

                <!-- Extended Physical Characteristics -->
                <h4 style="margin: 20px 0 10px 0; color: #555A4E; font-size: 14px; border-bottom: 1px solid #ddd; padding-bottom: 5px;">Physical Characteristics</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Ethnic Origin</label>
                        <input type="text" id="edit-donor-ethnic-origin" name="ethnic_origin" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Race</label>
                        <input type="text" id="edit-donor-race" name="race" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Ethnicity</label>
                        <input type="text" id="edit-donor-ethnicity" name="ethnicity" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Body Type</label>
                        <input type="text" id="edit-donor-body-type" name="body_type" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Face Shape</label>
                        <input type="text" id="edit-donor-face-shape" name="face_shape" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Nose Shape</label>
                        <input type="text" id="edit-donor-nose-shape" name="nose_shape" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Lips Shape</label>
                        <input type="text" id="edit-donor-lips-shape" name="lips_shape" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Hair Type</label>
                        <input type="text" id="edit-donor-hair-type" name="hair_type" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Skin Tone</label>
                        <input type="text" id="edit-donor-skin-tone" name="skin_tone" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Freckles</label>
                        <select id="edit-donor-freckles" name="freckles" class="table-inline-select" style="width: 100%; box-sizing: border-box;">
                            <option value="">Select</option>
                            <option value="None">None</option>
                            <option value="Few">Few</option>
                            <option value="Many">Many</option>
                        </select>
                    </div>
                </div>

                <!-- Health & Lifestyle -->
                <h4 style="margin: 20px 0 10px 0; color: #555A4E; font-size: 14px; border-bottom: 1px solid #ddd; padding-bottom: 5px;">Health & Lifestyle</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Proven Fertility</label>
                        <select id="edit-donor-proven-fertility" name="proven_fertility" class="table-inline-select" style="width: 100%; box-sizing: border-box;">
                            <option value="">Select</option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Hearing</label>
                        <input type="text" id="edit-donor-hearing" name="hearing" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Vision</label>
                        <input type="text" id="edit-donor-vision" name="vision" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Wearing Glasses</label>
                        <select id="edit-donor-wearing-glasses" name="wearing_glasses" class="table-inline-select" style="width: 100%; box-sizing: border-box;">
                            <option value="">Select</option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Wearing Contact Lenses</label>
                        <select id="edit-donor-wearing-lenses" name="wearing_lenses" class="table-inline-select" style="width: 100%; box-sizing: border-box;">
                            <option value="">Select</option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Surgeries</label>
                        <input type="text" id="edit-donor-surgeries" name="surgeries" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Allergies</label>
                        <input type="text" id="edit-donor-allergies" name="allergies" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Dental History</label>
                        <input type="text" id="edit-donor-dental-history" name="dental_history" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Twins Family History</label>
                        <input type="text" id="edit-donor-twins-history" name="twins_history" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Alcohol Use</label>
                        <input type="text" id="edit-donor-alcohol-use" name="alcohol_use" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Smoking Tobacco</label>
                        <input type="text" id="edit-donor-smoking-tobacco" name="smoking_tobacco" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Vaping</label>
                        <input type="text" id="edit-donor-vaping" name="vaping" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Drug Use</label>
                        <input type="text" id="edit-donor-drug-use" name="drug_use" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Current Medications</label>
                        <input type="text" id="edit-donor-medications" name="medications" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                </div>

                <!-- Preferences & Personality -->
                <h4 style="margin: 20px 0 10px 0; color: #555A4E; font-size: 14px; border-bottom: 1px solid #ddd; padding-bottom: 5px;">Preferences & Personality</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Zodiac Sign</label>
                        <input type="text" id="edit-donor-zodiac-sign" name="zodiac_sign" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Favourite Colour</label>
                        <input type="text" id="edit-donor-fav-colour" name="fav_colour" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Favourite Dish</label>
                        <input type="text" id="edit-donor-fav-dish" name="fav_dish" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Favourite Season</label>
                        <input type="text" id="edit-donor-fav-season" name="fav_season" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Favourite Holiday</label>
                        <input type="text" id="edit-donor-fav-holiday" name="fav_holiday" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Favourite Sport</label>
                        <input type="text" id="edit-donor-fav-sport" name="fav_sport" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Favourite Music</label>
                        <input type="text" id="edit-donor-fav-music" name="fav_music" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Childhood Dream</label>
                        <input type="text" id="edit-donor-childhood-dream" name="childhood_dream" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Favourite Author</label>
                        <input type="text" id="edit-donor-fav-author" name="fav_author" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Favourite Movie</label>
                        <input type="text" id="edit-donor-fav-movie" name="fav_movie" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Countries Visited</label>
                        <input type="text" id="edit-donor-countries-visited" name="countries_visited" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Goals in Life</label>
                        <input type="text" id="edit-donor-goals-in-life" name="goals_in_life" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Idols / Heroes</label>
                        <input type="text" id="edit-donor-idols-heroes" name="idols_heroes" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Personality Words</label>
                        <input type="text" id="edit-donor-personality-words" name="personality_words" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Strong Side</label>
                        <input type="text" id="edit-donor-strong-side" name="strong_side" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Weak Side</label>
                        <input type="text" id="edit-donor-weak-side" name="weak_side" class="table-inline-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                </div>

                <!-- Narrative -->
                <h4 style="margin: 20px 0 10px 0; color: #555A4E; font-size: 14px; border-bottom: 1px solid #ddd; padding-bottom: 5px;">Narrative Sections</h4>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">About Me</label>
                    <textarea id="edit-donor-about" name="about_me" class="table-inline-input" style="width: 100%; height: 70px; box-sizing: border-box;"></textarea>
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Hobbies & Interests</label>
                    <textarea id="edit-donor-hobbies" name="hobbies" class="table-inline-input" style="width: 100%; height: 70px; box-sizing: border-box;"></textarea>
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555A4E;">Why I Want to Donate</label>
                    <textarea id="edit-donor-why" name="why_donate" class="table-inline-input" style="width: 100%; height: 70px; box-sizing: border-box;"></textarea>
                </div>

                <!-- Medical History Checklist -->
                <h4 style="margin: 20px 0 10px 0; color: #555A4E; font-size: 14px; border-bottom: 1px solid #ddd; padding-bottom: 5px;">Medical & Family History Checklist</h4>
                <div style="max-height: 220px; overflow-y: auto; background: #fafbf9; padding: 10px; border-radius: 6px; border: 1px solid #c2c7bd; display: grid; grid-template-columns: 1fr; gap: 8px;">
                    <?php foreach ($admin_conditions as $cond_key => $cond_label): ?>
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 12px; cursor: pointer; color: #444;">
                            <input type="checkbox" class="edit-med-checkbox" name="medical_history[<?php echo esc_attr($cond_key); ?>]" value="Yes" data-key="<?php echo esc_attr($cond_key); ?>">
                            <span><?php echo esc_html($cond_label); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid #eee; padding-top: 15px;">
                <button type="button" class="action-btn btn-cancel-edit-to-view" style="background: #888; color: #fff; padding: 10px 20px; font-weight: bold; border-radius: 6px; font-size: 13px; border: none; cursor: pointer;">Cancel</button>
                <button type="submit" id="btn-submit-edit-donor" class="action-btn" style="background: #2e7d32; color: #fff; padding: 10px 24px; font-weight: bold; border-radius: 6px; font-size: 13px; border: none; cursor: pointer;">Save Profile Changes</button>
            </div>
        </form>
    </div> <!-- end donor-modal-edit-panel -->
</div> <!-- end ovarias-modal-container -->
</div> <!-- end donor-detail-modal -->
