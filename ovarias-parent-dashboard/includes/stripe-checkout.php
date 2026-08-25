<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle Dummy Payment Redirect (Bypass Stripe Checkout for Testing)
 */
if (!function_exists('ovarias_parent_handle_checkout_redirect')) {
    function ovarias_parent_handle_checkout_redirect() {
        if (!isset($_POST['ovarias_buy_premium'])) {
            return;
        }

        if (!is_user_logged_in()) {
            wp_safe_redirect(wp_login_url());
            exit;
        }

        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'ovarias_checkout_nonce')) {
            wp_die('Security check failed');
        }

        $user_id = get_current_user_id();
        $current_user = wp_get_current_user();
        
        // Set premium access to active instantly (Dummy Payment)
        update_user_meta($user_id, 'is_premium_parent', '1');
        $pay_date = current_time('mysql');
        update_user_meta($user_id, 'ovarias_payment_date', $pay_date);
        
        $first_name = get_user_meta($user_id, 'first_name', true) ?: $current_user->first_name;
        $last_name = get_user_meta($user_id, 'last_name', true) ?: $current_user->last_name;
        
        // Send Confirmation Email to Client (Section 3.2)
        $to_client = $current_user->user_email;
        $client_subject = "Your Ovarias Premium Access is Active!";
        $client_body = "Hi " . $first_name . ",\r\n\r\n";
        $client_body .= "Thank you for upgrading! Your 12-month premium membership is now active.\r\n";
        $client_body .= "You can now search, filter, and review our Fresh and Frozen donor grids.\r\n\r\n";
        $client_body .= "Browse the catalog here:\r\n";
        $client_body .= home_url('/intended-parent-dashboard/') . "\r\n\r\n";
        $client_body .= "Best regards,\r\nOvarias Egg Bank Team";
        wp_mail($to_client, $client_subject, $client_body);
        
        // Send Transaction Email Notification to Administrator (Section 3.2)
        $admin_email = get_option('admin_email');
        $txn_id = 'TXN_' . substr(md5($user_id . $pay_date), 0, 10);
        $admin_subject = "New Premium Parent Registration - " . $first_name . " " . $last_name;
        $admin_body = "A new premium parent registration has been processed successfully (Test Mode):\r\n\r\n";
        $admin_body .= "Client Name: " . $first_name . " " . $last_name . "\r\n";
        $admin_body .= "Email Address: " . $to_client . "\r\n";
        $admin_body .= "Registration Date: " . $pay_date . "\r\n";
        $admin_body .= "Payment Status: Paid\r\n";
        $admin_body .= "Payment Date: " . $pay_date . "\r\n";
        $admin_body .= "Payment Amount: $199.00\r\n";
        $admin_body .= "Transaction ID: " . $txn_id . "\r\n";
        $admin_body .= "Account Status: Active\r\n";
        wp_mail($admin_email, $admin_subject, $admin_body);
        
        $dashboard_url = home_url('/intended-parent-dashboard/');
        wp_safe_redirect(add_query_arg('payment_success', '1', $dashboard_url));
        exit;
    }
    add_action('init', 'ovarias_parent_handle_checkout_redirect');
}

/**
 * Helper to get Stripe configurations dynamically (Left as fallback helpers)
 */
if (!function_exists('ovarias_parent_get_stripe_key')) {
    function ovarias_parent_get_stripe_key() {
        return 'sk_test_dummy';
    }
}

if (!function_exists('ovarias_parent_get_stripe_price')) {
    function ovarias_parent_get_stripe_price() {
        return 199.00;
    }
}

if (!function_exists('ovarias_parent_get_stripe_currency')) {
    function ovarias_parent_get_stripe_currency() {
        return 'usd';
    }
}
