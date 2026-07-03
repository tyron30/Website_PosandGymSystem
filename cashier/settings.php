<?php
include "../config/db.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'cashier') {
    header("Location: ../index.php");
    exit();
}

$user = $_SESSION['user'];

// Fetch gym settings
$settings = $conn->query("SELECT * FROM gym_settings WHERE id = 1")->fetch_assoc();
if (!$settings) {
    // Insert default settings if not exists
    $conn->query("INSERT INTO gym_settings (gym_name, logo_path, background_path) VALUES ('Gym Management System', 'gym logo.jpg', 'gym background.jpg')");
    $settings = $conn->query("SELECT * FROM gym_settings WHERE id = 1")->fetch_assoc();
}

// Handle password change
$message = '';
$message_type = '';

if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    $errors = [];

    // Validate current password
    if (!password_verify($current_password, $user['password'])) {
        $errors[] = "Current password is incorrect.";
    }

    // Validate new password
    if (empty($new_password)) {
        $errors[] = "New password is required.";
    } elseif (strlen($new_password) < 6) {
        $errors[] = "New password must be at least 6 characters long.";
    }

    // Validate password confirmation
    if ($new_password !== $confirm_password) {
        $errors[] = "New password and confirmation do not match.";
    }

    if (empty($errors)) {
        // Hash new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update password in database
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $user['id']);

        if ($stmt->execute()) {
            $message = "Password changed successfully!";
            $message_type = "success";
            // Update session with new password hash
            $_SESSION['user']['password'] = $hashed_password;
            $user['password'] = $hashed_password;
        } else {
            $message = "Failed to update password. Please try again.";
            $message_type = "danger";
        }
        $stmt->close();
    } else {
        $message = implode(' ', $errors);
        $message_type = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Gym Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/style.css?v=20260630f" rel="stylesheet">
    <script src="../assets/toast.js"></script>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <nav id="sidebar" class="bg-<?php echo htmlspecialchars($settings['sidebar_theme']); ?> <?php echo ($settings['sidebar_theme'] == 'light') ? 'text-dark' : 'text-white'; ?> vh-100" style="width: 250px;">
            <div class="p-3">
                <div class="text-center mb-4">
                    <img src="../<?php echo htmlspecialchars($settings['logo_path']); ?>" alt="Gym Logo" class="rounded-circle mb-2" style="width: 80px; height: 80px;">
                    <h5 class="fw-bold"><?php echo htmlspecialchars($settings['gym_name']); ?></h5>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item mb-2">
                        <a class="nav-link <?php echo ($settings['sidebar_theme'] == 'light') ? 'text-dark' : 'text-white'; ?>" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i><span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link <?php echo ($settings['sidebar_theme'] == 'light') ? 'text-dark' : 'text-white'; ?>" href="members.php">
                            <i class="fas fa-users me-2"></i><span>Members</span>
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link <?php echo ($settings['sidebar_theme'] == 'light') ? 'text-dark' : 'text-white'; ?>" href="pos.php">
                            <i class="fas fa-cash-register me-2"></i><span>Point of Sale</span>
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link <?php echo ($settings['sidebar_theme'] == 'light') ? 'text-dark' : 'text-white'; ?>" href="attendance.php">
                            <i class="fas fa-calendar-check me-2"></i><span>Attendance</span>
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link <?php echo ($settings['sidebar_theme'] == 'light') ? 'text-dark' : 'text-white'; ?>" href="../admin/website_settings.php">
                            <i class="fas fa-globe me-2"></i><span>Website</span>
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link <?php echo ($settings['sidebar_theme'] == 'light') ? 'text-dark' : 'text-white'; ?> active" href="settings.php">
                            <i class="fas fa-cog me-2"></i><span>Settings</span>
                        </a>
                    </li>
                    <li class="nav-item mt-4">
                        <a class="nav-link <?php echo ($settings['sidebar_theme'] == 'light') ? 'text-dark' : 'text-white'; ?>" href="../logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i><span>Logout</span>
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="flex-grow-1">
            <!-- Top Bar -->
            <nav class="navbar navbar-light bg-light border-bottom">
                <div class="container-fluid">
                    <button class="btn btn-outline-secondary me-3" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <span class="navbar-brand mb-0 h1">Settings - <?php echo htmlspecialchars($user['fullname']); ?> (Cashier)</span>
                </div>
            </nav>

            <div class="container-fluid mt-4">
                <div class="row">
                    <div class="col-12">
                        <h1 class="h3 mb-4"><i class="fas fa-cog me-2"></i>Settings</h1>
                    </div>
                </div>

                <div class="row">
                    <!-- Database Management -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><i class="fas fa-database me-2"></i>Database Management</h5>
                            </div>
                            <div class="card-body">
                                <h6>Backup Database</h6>
                                <p class="text-muted">Download a full backup of the gym database as an SQL file.</p>
                                <a href="../admin/backup_db.php" class="btn btn-primary">
                                    <i class="fas fa-download me-2"></i><span>Backup Database</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Change Password -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><i class="fas fa-key me-2"></i>Change Password</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Current Password *</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">New Password *</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                                        <div class="form-text">Password must be at least 6 characters long.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirm New Password *</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                                    </div>

                                    <button type="submit" name="change_password" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Change Password
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="card mt-3">
                            <div class="card-body">
                                <h6 class="card-title"><i class="fas fa-shield-alt me-2"></i>Password Security Tips</h6>
                                <ul class="list-unstyled mb-0">
                                    <li><i class="fas fa-check text-success me-2"></i>Use at least 6 characters</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Include a mix of letters and numbers</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Avoid using common words</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Change your password regularly</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <footer class="bg-light text-center text-muted py-3 mt-5 border-top">
                <div class="container">
                    <small>Developed by Tyron Del Valle</small>
                </div>
            </footer>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;

            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });

        // Show toasts for PHP messages
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (!empty($message)): ?>
            showToast('<?php echo addslashes($message); ?>', '<?php echo $message_type; ?>');
            <?php endif; ?>
        });
    </script>
    <script src="../assets/sidebar.js"></script>
</body>
</html>
