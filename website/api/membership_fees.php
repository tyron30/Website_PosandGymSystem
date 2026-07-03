<?php
/**
 * website/api/membership_fees.php
 * Returns live pricing straight from gym_settings — the SAME table and
 * columns the admin edits in admin/settings.php (Membership Fees tab).
 * Website pricing cards auto-update whenever admin/cashier changes prices.
 */
require_once __DIR__ . '/config.php';

$fees = $conn->query("SELECT per_session_fee, half_month_fee, one_month_fee, student_discount_enabled FROM gym_settings WHERE id = 1")->fetch_assoc();

// Fallback defaults if settings row is somehow missing
$session  = isset($fees['per_session_fee']) ? (float)$fees['per_session_fee'] : 50;
$half     = isset($fees['half_month_fee'])  ? (float)$fees['half_month_fee']  : 300;
$monthly  = isset($fees['one_month_fee'])   ? (float)$fees['one_month_fee']   : 500;
$disc_on  = isset($fees['student_discount_enabled']) ? (bool)$fees['student_discount_enabled'] : true;

// Student discount is a fixed 20% off the monthly fee, matching admin/payments.php and admin/members.php
$disc_pct    = 20;
$student_fee = $disc_on ? round($monthly * (1 - $disc_pct / 100)) : $monthly;

ok([
    'per_session_fee'          => $session,
    'half_month_fee'           => $half,
    'monthly_fee'              => $monthly,
    'student_fee'              => $student_fee,
    'student_discount_enabled' => $disc_on,
    'student_discount_percent' => $disc_pct,
]);
