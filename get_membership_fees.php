<?php
include "config/db.php";

function getMembershipFees() {
    global $conn;

    // Fetch gym settings
    $settings = $conn->query("SELECT * FROM gym_settings WHERE id = 1")->fetch_assoc();

    if (!$settings) {
        // Return default values if settings don't exist
        return [
            'per_session_fee' => 50.00,
            'half_month_fee' => 300.00,
            'one_month_fee' => 500.00,
            'two_months_fee' => 900.00,
            'three_months_fee' => 1300.00,
            'four_months_fee' => 1700.00,
            'five_months_fee' => 2100.00,
            'six_months_fee' => 2500.00,
            'seven_months_fee' => 2900.00,
            'eight_months_fee' => 3300.00,
            'nine_months_fee' => 3700.00,
            'ten_months_fee' => 4100.00,
            'eleven_months_fee' => 4500.00,
            'one_year_fee' => 5000.00,
            'two_years_fee' => 9000.00,
            'three_years_fee' => 13000.00
        ];
    }

    return [
        'per_session_fee' => isset($settings['per_session_fee']) ? floatval($settings['per_session_fee']) : 50.00,
        'half_month_fee' => isset($settings['half_month_fee']) ? floatval($settings['half_month_fee']) : 300.00,
        'one_month_fee' => isset($settings['one_month_fee']) ? floatval($settings['one_month_fee']) : 500.00,
        'two_months_fee' => isset($settings['two_months_fee']) ? floatval($settings['two_months_fee']) : 900.00,
        'three_months_fee' => isset($settings['three_months_fee']) ? floatval($settings['three_months_fee']) : 1300.00,
        'four_months_fee' => isset($settings['four_months_fee']) ? floatval($settings['four_months_fee']) : 1700.00,
        'five_months_fee' => isset($settings['five_months_fee']) ? floatval($settings['five_months_fee']) : 2100.00,
        'six_months_fee' => isset($settings['six_months_fee']) ? floatval($settings['six_months_fee']) : 2500.00,
        'seven_months_fee' => isset($settings['seven_months_fee']) ? floatval($settings['seven_months_fee']) : 2900.00,
        'eight_months_fee' => isset($settings['eight_months_fee']) ? floatval($settings['eight_months_fee']) : 3300.00,
        'nine_months_fee' => isset($settings['nine_months_fee']) ? floatval($settings['nine_months_fee']) : 3700.00,
        'ten_months_fee' => isset($settings['ten_months_fee']) ? floatval($settings['ten_months_fee']) : 4100.00,
        'eleven_months_fee' => isset($settings['eleven_months_fee']) ? floatval($settings['eleven_months_fee']) : 4500.00,
        'one_year_fee' => isset($settings['one_year_fee']) ? floatval($settings['one_year_fee']) : 5000.00,
        'two_years_fee' => isset($settings['two_years_fee']) ? floatval($settings['two_years_fee']) : 9000.00,
        'three_years_fee' => isset($settings['three_years_fee']) ? floatval($settings['three_years_fee']) : 13000.00
    ];
}

function getMembershipFeeByPlan($plan) {
    $fees = getMembershipFees();

    switch ($plan) {
        case 'Per Session':
            return $fees['per_session_fee'];
        case 'Half Month':
            return $fees['half_month_fee'];
        case '1 Month':
            return $fees['one_month_fee'];
        case '2 Months':
            return $fees['two_months_fee'];
        case '3 Months':
            return $fees['three_months_fee'];
        case '4 Months':
            return $fees['four_months_fee'];
        case '5 Months':
            return $fees['five_months_fee'];
        case '6 Months':
            return $fees['six_months_fee'];
        case '7 Months':
            return $fees['seven_months_fee'];
        case '8 Months':
            return $fees['eight_months_fee'];
        case '9 Months':
            return $fees['nine_months_fee'];
        case '10 Months':
            return $fees['ten_months_fee'];
        case '11 Months':
            return $fees['eleven_months_fee'];
        case '1 Year':
            return $fees['one_year_fee'];
        case '2 Years':
            return $fees['two_years_fee'];
        case '3 Years':
            return $fees['three_years_fee'];
        default:
            return 0.00;
    }
}
?>
