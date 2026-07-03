<?php
include "config/db.php";

$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if (!isset($_GET['token'])) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'No token provided']);
        exit();
    }
    header("Location: index.php");
    exit();
}

$token = $_GET['token'];

// Minimum minutes a member must stay before checking out
define('MIN_CHECKIN_MINUTES', 3);

$stmt = $conn->prepare("SELECT id, fullname, status, end_date FROM members WHERE qr_token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $response = ['error' => 'Invalid QR code. Member not found.'];
} else {
    $member     = $result->fetch_assoc();
    $today      = date('Y-m-d');
    $status     = strtolower($member['status']);
    $is_expired = ($today > $member['end_date']) || in_array($status, ['expired', 'inactive']);

    if ($is_expired) {
        $response = ['error' => 'Membership expired. Please renew to continue.'];
    } else {
        $check_stmt = $conn->prepare(
            "SELECT id, checkin_time, checkout_time,
                    TIMESTAMPDIFF(SECOND, checkin_time, NOW()) AS elapsed_secs
             FROM attendance
             WHERE member_id = ? AND DATE(checkin_time) = CURDATE()
             ORDER BY checkin_time DESC LIMIT 1"
        );
        $check_stmt->bind_param("i", $member['id']);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows === 0) {
            // No record today -> CHECK IN
            $ins = $conn->prepare("INSERT INTO attendance (member_id, checkin_time) VALUES (?, NOW())");
            $ins->bind_param("i", $member['id']);
            if ($ins->execute()) {
                $response = [
                    'success' => 'Check-in successful! Welcome, ' . htmlspecialchars($member['fullname']) . '.',
                    'type'    => 'checkin',
                    'info'    => 'You can check out after ' . MIN_CHECKIN_MINUTES . ' minutes.'
                ];
            } else {
                $response = ['error' => 'Failed to record check-in. Please try again.'];
            }
            $ins->close();
        } else {
            $record = $check_result->fetch_assoc();

            if ($record['checkout_time'] !== null) {
                $response = ['error' => 'You have already checked in and checked out today.'];
            } else {
                // Use MySQL-computed elapsed seconds to avoid PHP/MySQL timezone mismatch
                $elapsed_secs = (int)$record['elapsed_secs'];
                $min_secs     = MIN_CHECKIN_MINUTES * 60;

                if ($elapsed_secs < $min_secs) {
                    $remaining    = $min_secs - $elapsed_secs;
                    $rem_min      = floor($remaining / 60);
                    $rem_sec      = $remaining % 60;
                    $rem_str      = $rem_min > 0
                        ? "{$rem_min} min " . ($rem_sec > 0 ? "{$rem_sec} sec" : "")
                        : "{$rem_sec} seconds";

                    $response = [
                        'error' => 'Too soon to check out! Please wait ' . trim($rem_str) . ' more before checking out.',
                        'type'  => 'too_soon'
                    ];
                } else {
                    // Minimum time passed -> CHECK OUT
                    $out = $conn->prepare("UPDATE attendance SET checkout_time = NOW() WHERE id = ?");
                    $out->bind_param("i", $record['id']);
                    if ($out->execute()) {
                        $hours   = floor($elapsed_secs / 3600);
                        $minutes = floor(($elapsed_secs % 3600) / 60);
                        $dur_str = $hours > 0
                            ? "{$hours}h {$minutes}m"
                            : "{$minutes} minute" . ($minutes !== 1 ? 's' : '');

                        $response = [
                            'success' => 'Check-out successful! Goodbye, ' . htmlspecialchars($member['fullname']) . '. Duration: ' . $dur_str . '.',
                            'type'    => 'checkout'
                        ];
                    } else {
                        $response = ['error' => 'Failed to record check-out. Please try again.'];
                    }
                    $out->close();
                }
            }
        }
        $check_stmt->close();
    }
}
$stmt->close();

if ($is_ajax) {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

$type = isset($response['type']) ? $response['type'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Attendance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body text-center p-4">
                    <img src="gym logo.jpg" alt="Gym Logo" class="rounded-circle mb-3" style="width:80px;height:80px;">
                    <h2 class="card-title mb-4">QR Attendance</h2>

                    <?php if (isset($response['error'])): ?>
                        <?php if ($type === 'too_soon'): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-clock me-2"></i><?php echo htmlspecialchars($response['error']); ?>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($response['error']); ?>
                        </div>
                        <?php endif; ?>
                    <?php elseif (isset($response['success'])): ?>
                        <?php if ($type === 'checkout'): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-sign-out-alt me-2"></i><?php echo htmlspecialchars($response['success']); ?>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-success">
                            <i class="fas fa-sign-in-alt me-2"></i><?php echo htmlspecialchars($response['success']); ?>
                            <?php if (!empty($response['info'])): ?>
                            <div class="mt-1 small"><i class="fas fa-clock me-1"></i><?php echo htmlspecialchars($response['info']); ?></div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <a href="index.php" class="btn btn-primary mt-3">
                        <i class="fas fa-home me-2"></i>Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>