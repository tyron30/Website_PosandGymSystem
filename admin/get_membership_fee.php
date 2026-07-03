<?php
include "../config/db.php";
include "../get_membership_fees.php";

if (isset($_GET['plan'])) {
    $plan = $_GET['plan'];
    $fee = getMembershipFeeByPlan($plan);

    header('Content-Type: application/json');
    echo json_encode(['fee' => $fee]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Plan parameter required']);
}
?>
