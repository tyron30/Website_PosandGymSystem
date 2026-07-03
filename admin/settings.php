<?php
include "../config/db.php";

if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit();
}

$user = $_SESSION['user'];
if ($user['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Fetch current gym settings
$settings = $conn->query("SELECT * FROM gym_settings WHERE id = 1")->fetch_assoc();
if (!$settings) {
    // Insert default settings if not exists
    $conn->query("INSERT INTO gym_settings (gym_name, logo_path, background_path, sidebar_theme) VALUES ('Gym Management System', 'gym logo.jpg', 'gym background.jpg', 'primary')");
    $settings = $conn->query("SELECT * FROM gym_settings WHERE id = 1")->fetch_assoc();
}

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_name'])) {
        // Update gym name
        $new_name = trim($_POST['gym_name']);
        if (!empty($new_name)) {
            $stmt = $conn->prepare("UPDATE gym_settings SET gym_name = ? WHERE id = 1");
            $stmt->bind_param("s", $new_name);
            if ($stmt->execute()) {
                $message = "Gym name updated successfully!";
                $message_type = "success";
                $settings['gym_name'] = $new_name;
            } else {
                $message = "Error updating gym name.";
                $message_type = "danger";
            }
        } else {
            $message = "Gym name cannot be empty.";
            $message_type = "danger";
        }
    } elseif (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        // Handle logo upload
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (in_array($_FILES['logo']['type'], $allowed_types) && $_FILES['logo']['size'] <= $max_size) {
            $file_extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $logo_dir = '../uploads/gym_logos/';
            if (!is_dir($logo_dir)) mkdir($logo_dir, 0755, true);
            $new_filename = 'gym_logo_' . time() . '.' . $file_extension;
            $upload_path = $logo_dir . $new_filename;

            if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path)) {
                $stmt = $conn->prepare("UPDATE gym_settings SET logo_path = ? WHERE id = 1");
$logo_path = 'uploads/gym_logos/' . $new_filename; $stmt->bind_param("s", $logo_path);
                if ($stmt->execute()) {
                    $message = "Gym logo updated successfully!";
                    $message_type = "success";
                    $settings['logo_path'] = 'uploads/gym_logos/' . $new_filename;
                } else {
                    $message = "Error updating logo in database.";
                    $message_type = "danger";
                }
            } else {
                $message = "Error uploading logo file.";
                $message_type = "danger";
            }
        } else {
            $message = "Invalid file type or size. Please upload a JPEG, PNG, or GIF image under 5MB.";
            $message_type = "danger";
        }
    } elseif (isset($_FILES['background']) && $_FILES['background']['error'] == 0) {
        // Handle background upload
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (in_array($_FILES['background']['type'], $allowed_types) && $_FILES['background']['size'] <= $max_size) {
            $file_extension = pathinfo($_FILES['background']['name'], PATHINFO_EXTENSION);
            $bg_dir = '../uploads/gym_backgrounds/';
            if (!is_dir($bg_dir)) mkdir($bg_dir, 0755, true);
            $new_filename = 'gym_background_' . time() . '.' . $file_extension;
            $upload_path = $bg_dir . $new_filename;

            if (move_uploaded_file($_FILES['background']['tmp_name'], $upload_path)) {
                $stmt = $conn->prepare("UPDATE gym_settings SET background_path = ? WHERE id = 1");
$bg_path = 'uploads/gym_backgrounds/' . $new_filename; $stmt->bind_param("s", $bg_path);
                if ($stmt->execute()) {
                    $message = "Gym background updated successfully!";
                    $message_type = "success";
                    $settings['background_path'] = 'uploads/gym_backgrounds/' . $new_filename;
                } else {
                    $message = "Error updating background in database.";
                    $message_type = "danger";
                }
            } else {
                $message = "Error uploading background file.";
                $message_type = "danger";
            }
        } else {
            $message = "Invalid file type or size. Please upload a JPEG, PNG, or GIF image under 5MB.";
            $message_type = "danger";
        }
    } elseif (isset($_POST['update_sidebar_theme'])) {
        // Update sidebar theme
        $new_theme = trim($_POST['sidebar_theme']);
        $allowed_themes = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'dark', 'light'];
        if (in_array($new_theme, $allowed_themes)) {
            $stmt = $conn->prepare("UPDATE gym_settings SET sidebar_theme = ? WHERE id = 1");
            $stmt->bind_param("s", $new_theme);
            if ($stmt->execute()) {
                $message = "Sidebar theme updated successfully!";
                $message_type = "success";
                $settings['sidebar_theme'] = $new_theme;
            } else {
                $message = "Error updating sidebar theme.";
                $message_type = "danger";
            }
        } else {
            $message = "Invalid sidebar theme selected.";
            $message_type = "danger";
        }
    } elseif (isset($_POST['update_student_discount'])) {
        // Update student discount feature
        $student_discount_enabled = isset($_POST['student_discount_enabled']) ? 1 : 0;
        $stmt = $conn->prepare("UPDATE gym_settings SET student_discount_enabled = ? WHERE id = 1");
        $stmt->bind_param("i", $student_discount_enabled);
        if ($stmt->execute()) {
            $message = "Student discount feature " . ($student_discount_enabled ? "enabled" : "disabled") . " successfully!";
            $message_type = "success";
            $settings['student_discount_enabled'] = $student_discount_enabled;
        } else {
            $message = "Error updating student discount feature.";
            $message_type = "danger";
        }
    } elseif (isset($_POST['save_website_contact'])) {
        // Ensure website columns exist
        foreach ([
            "ALTER TABLE gym_settings ADD COLUMN IF NOT EXISTS address VARCHAR(255) DEFAULT ''",
            "ALTER TABLE gym_settings ADD COLUMN IF NOT EXISTS phone VARCHAR(30) DEFAULT ''",
            "ALTER TABLE gym_settings ADD COLUMN IF NOT EXISTS email VARCHAR(120) DEFAULT ''",
            "ALTER TABLE gym_settings ADD COLUMN IF NOT EXISTS gcash_number VARCHAR(30) DEFAULT ''",
            "ALTER TABLE gym_settings ADD COLUMN IF NOT EXISTS gcash_name VARCHAR(100) DEFAULT ''",
            "ALTER TABLE gym_settings ADD COLUMN IF NOT EXISTS facebook_url VARCHAR(255) DEFAULT ''",
            "ALTER TABLE gym_settings ADD COLUMN IF NOT EXISTS instagram_url VARCHAR(255) DEFAULT ''",
            "ALTER TABLE gym_settings ADD COLUMN IF NOT EXISTS hours VARCHAR(255) DEFAULT 'Monday - Sunday: 5:00 AM - 10:00 PM'",
            "ALTER TABLE gym_settings ADD COLUMN IF NOT EXISTS about_text TEXT",
            "ALTER TABLE gym_settings ADD COLUMN IF NOT EXISTS map_embed TEXT",
        ] as $sql) { $conn->query($sql); }

        $address      = trim($_POST['address']      ?? '');
        $phone        = trim($_POST['phone']        ?? '');
        $email        = trim($_POST['email']        ?? '');
        $gcash_number = trim($_POST['gcash_number'] ?? '');
        $gcash_name   = trim($_POST['gcash_name']   ?? '');
        $facebook_url = trim($_POST['facebook_url'] ?? '');
        $instagram_url= trim($_POST['instagram_url']?? '');
        $hours        = trim($_POST['hours']        ?? '');
        $about_text   = trim($_POST['about_text']   ?? '');
        $map_embed    = trim($_POST['map_embed']    ?? '');

        $stmt = $conn->prepare("UPDATE gym_settings SET
            address=?, phone=?, email=?, gcash_number=?, gcash_name=?,
            facebook_url=?, instagram_url=?, hours=?, about_text=?, map_embed=?
            WHERE id=1");
        $stmt->bind_param('ssssssssss',
            $address, $phone, $email, $gcash_number, $gcash_name,
            $facebook_url, $instagram_url, $hours, $about_text, $map_embed);
        if ($stmt->execute()) {
            $message      = 'Website contact info updated successfully!';
            $message_type = 'success';
            $settings     = $conn->query("SELECT * FROM gym_settings WHERE id=1")->fetch_assoc();
        } else {
            $message      = 'Error saving website info.';
            $message_type = 'danger';
        }

    } elseif (isset($_POST['update_membership_fees'])) {
        // Update membership fees
        $fees = [
            'per_session_fee' => floatval($_POST['per_session_fee']),
            'half_month_fee' => floatval($_POST['half_month_fee']),
            'one_month_fee' => floatval($_POST['one_month_fee']),
            'two_months_fee' => floatval($_POST['two_months_fee']),
            'three_months_fee' => floatval($_POST['three_months_fee']),
            'four_months_fee' => floatval($_POST['four_months_fee']),
            'five_months_fee' => floatval($_POST['five_months_fee']),
            'six_months_fee' => floatval($_POST['six_months_fee']),
            'seven_months_fee' => floatval($_POST['seven_months_fee']),
            'eight_months_fee' => floatval($_POST['eight_months_fee']),
            'nine_months_fee' => floatval($_POST['nine_months_fee']),
            'ten_months_fee' => floatval($_POST['ten_months_fee']),
            'eleven_months_fee' => floatval($_POST['eleven_months_fee']),
            'one_year_fee' => floatval($_POST['one_year_fee']),
            'two_years_fee' => floatval($_POST['two_years_fee']),
            'three_years_fee' => floatval($_POST['three_years_fee'])
        ];

        $stmt = $conn->prepare("UPDATE gym_settings SET
                                per_session_fee = ?, half_month_fee = ?, one_month_fee = ?,
                                two_months_fee = ?, three_months_fee = ?, four_months_fee = ?,
                                five_months_fee = ?, six_months_fee = ?, seven_months_fee = ?,
                                eight_months_fee = ?, nine_months_fee = ?, ten_months_fee = ?,
                                eleven_months_fee = ?, one_year_fee = ?, two_years_fee = ?,
                                three_years_fee = ? WHERE id = 1");

        $stmt->bind_param("dddddddddddddddd",
            $fees['per_session_fee'], $fees['half_month_fee'], $fees['one_month_fee'],
            $fees['two_months_fee'], $fees['three_months_fee'], $fees['four_months_fee'],
            $fees['five_months_fee'], $fees['six_months_fee'], $fees['seven_months_fee'],
            $fees['eight_months_fee'], $fees['nine_months_fee'], $fees['ten_months_fee'],
            $fees['eleven_months_fee'], $fees['one_year_fee'], $fees['two_years_fee'],
            $fees['three_years_fee']
        );

        if ($stmt->execute()) {
            $message = "Membership fees updated successfully!";
            $message_type = "success";
            // Update settings array with new values
            foreach ($fees as $key => $value) {
                $settings[$key] = $value;
            }
        } else {
            $message = "Error updating membership fees.";
            $message_type = "danger";
        }
    } elseif (isset($_POST['change_password'])) {
        // Change admin password
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        $errors = [];

        if (!password_verify($current_password, $user['password'])) {
            $errors[] = "Current password is incorrect.";
        }

        if (empty($new_password)) {
            $errors[] = "New password is required.";
        } elseif (strlen($new_password) < 6) {
            $errors[] = "New password must be at least 6 characters long.";
        }

        if ($new_password !== $confirm_password) {
            $errors[] = "New password and confirmation do not match.";
        }

        if (empty($errors)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $user['id']);
            if ($stmt->execute()) {
                $message = "Password changed successfully!";
                $message_type = "success";
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
                        <a class="nav-link <?php echo ($settings['sidebar_theme'] == 'light') ? 'text-dark' : 'text-white'; ?>" href="reports.php">
                            <i class="fas fa-chart-bar me-2"></i><span>Reports</span>
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link <?php echo ($settings['sidebar_theme'] == 'light') ? 'text-dark' : 'text-white'; ?>" href="employees.php">
                            <i class="fas fa-user-tie me-2"></i><span>Employees</span>
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link <?php echo ($settings['sidebar_theme'] == 'light') ? 'text-dark' : 'text-white'; ?>" href="website_settings.php">
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
                    <span class="navbar-brand mb-0 h1">Settings - <?php echo htmlspecialchars($user['fullname']); ?> (Admin)</span>
                </div>
            </nav>

            <div class="container-fluid mt-4">
                <div class="row">
                    <div class="col-12">
                        <h1 class="h3 mb-4">Gym Settings</h1>



                        <div class="row">
                            <!-- Gym Name Settings -->
                            <div class="col-md-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0"><i class="fas fa-building me-2"></i>Gym Name</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST">
                                            <div class="mb-3">
                                                <label for="gym_name" class="form-label">Current Gym Name</label>
                                                <input type="text" class="form-control" id="gym_name" name="gym_name"
                                                       value="<?php echo htmlspecialchars($settings['gym_name']); ?>" required>
                                            </div>
                                            <button type="submit" name="update_name" class="btn btn-primary">
                                                <i class="fas fa-save me-2"></i>Update Name
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Student Discount Feature Settings -->
                            <div class="col-md-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0"><i class="fas fa-graduation-cap me-2"></i>Student Discount Feature</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST">
                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="student_discount_enabled" name="student_discount_enabled"
                                                           <?php echo (isset($settings['student_discount_enabled']) && $settings['student_discount_enabled']) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="student_discount_enabled">
                                                        Enable Student Discount Feature
                                                    </label>
                                                </div>
                                                <div class="form-text">
                                                    When enabled, student discount options will be available in member management and payment processing.
                                                    When disabled, all student discount features will be hidden and non-functional.
                                                </div>
                                            </div>
                                            <button type="submit" name="update_student_discount" class="btn btn-primary">
                                                <i class="fas fa-save me-2"></i>Update Student Discount Setting
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Logo Settings -->
                            <div class="col-md-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0"><i class="fas fa-image me-2"></i>Gym Logo</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3 text-center">
                                            <img src="../<?php echo htmlspecialchars($settings['logo_path']); ?>"
                                                 alt="Current Logo" class="img-thumbnail mb-3" style="max-width: 150px; max-height: 150px;">
                                            <p class="text-muted small">Current: <?php echo htmlspecialchars($settings['logo_path']); ?></p>
                                        </div>
                                        <form method="POST" enctype="multipart/form-data">
                                            <div class="mb-3">
                                                <label for="logo" class="form-label">Upload New Logo</label>
                                                <input type="file" class="form-control" id="logo" name="logo" accept="image/*" required>
                                                <div class="form-text">Accepted formats: JPEG, PNG, GIF. Max size: 5MB</div>
                                            </div>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-upload me-2"></i>Upload Logo
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Background Settings -->
                            <div class="col-md-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0"><i class="fas fa-image me-2"></i>Gym Background</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3 text-center">
                                            <img src="../<?php echo htmlspecialchars($settings['background_path']); ?>"
                                                 alt="Current Background" class="img-thumbnail mb-3" style="max-width: 300px; max-height: 200px;">
                                            <p class="text-muted small">Current: <?php echo htmlspecialchars($settings['background_path']); ?></p>
                                        </div>
                                        <form method="POST" enctype="multipart/form-data">
                                            <div class="mb-3">
                                                <label for="background" class="form-label">Upload New Background</label>
                                                <input type="file" class="form-control" id="background" name="background" accept="image/*" required>
                                                <div class="form-text">Accepted formats: JPEG, PNG, GIF. Max size: 5MB</div>
                                            </div>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-upload me-2"></i>Upload Background
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Database Management -->
                            <div class="col-md-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0"><i class="fas fa-database me-2"></i>Database Management</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <h6>Backup Database</h6>
                                                <p class="text-muted">Download a full backup of the gym database as an SQL file.</p>
                                                <a href="backup_db.php" class="btn btn-primary">
                                                    <i class="fas fa-download me-2"></i><span>Backup Database</span>
                                                </a>
                                            </div>
                                        </div>
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
                            </div>

                            <!-- Sidebar Theme Settings -->
                            <div class="col-md-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0"><i class="fas fa-palette me-2"></i>Sidebar Theme</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST">
                                            <div class="mb-3">
                                                <label for="sidebar_theme" class="form-label">Select Sidebar Color</label>
                                                <select class="form-select" id="sidebar_theme" name="sidebar_theme" required>
                                                    <option value="primary" <?php echo ($settings['sidebar_theme'] == 'primary') ? 'selected' : ''; ?>>Blue (Primary)</option>
                                                    <option value="secondary" <?php echo ($settings['sidebar_theme'] == 'secondary') ? 'selected' : ''; ?>>Gray (Secondary)</option>
                                                    <option value="success" <?php echo ($settings['sidebar_theme'] == 'success') ? 'selected' : ''; ?>>Green (Success)</option>
                                                    <option value="danger" <?php echo ($settings['sidebar_theme'] == 'danger') ? 'selected' : ''; ?>>Red (Danger)</option>
                                                    <option value="warning" <?php echo ($settings['sidebar_theme'] == 'warning') ? 'selected' : ''; ?>>Yellow (Warning)</option>
                                                    <option value="info" <?php echo ($settings['sidebar_theme'] == 'info') ? 'selected' : ''; ?>>Cyan (Info)</option>
                                                    <option value="dark" <?php echo ($settings['sidebar_theme'] == 'dark') ? 'selected' : ''; ?>>Dark</option>
                                                    <option value="light" <?php echo ($settings['sidebar_theme'] == 'light') ? 'selected' : ''; ?>>Light</option>
                                                </select>
                                                <div class="form-text">This theme will apply to all users (admin and cashier).</div>
                                            </div>
                                            <button type="submit" name="update_sidebar_theme" class="btn btn-primary">
                                                <i class="fas fa-save me-2"></i>Update Sidebar Theme
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Membership Fees Settings -->
                            <div class="col-12 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0"><i class="fas fa-money-bill-wave me-2"></i>Membership Fees Configuration</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST">
                                            <p class="text-muted mb-3">Set the exact fees for each membership plan. These fees will automatically populate in member management and payment processing.</p>
                                            <div class="row">
                                                <div class="col-md-6 col-lg-4 mb-3">
                                                    <label for="per_session_fee" class="form-label">Per Session Fee (₱)</label>
                                                    <input type="number" class="form-control" id="per_session_fee" name="per_session_fee"
                                                           value="<?php echo isset($settings['per_session_fee']) ? number_format($settings['per_session_fee'], 2, '.', '') : '50.00'; ?>"
                                                           min="0" step="0.01" required>
                                                </div>
                                                <div class="col-md-6 col-lg-4 mb-3">
                                                    <label for="half_month_fee" class="form-label">Half Month Fee (₱)</label>
                                                    <input type="number" class="form-control" id="half_month_fee" name="half_month_fee"
                                                           value="<?php echo isset($settings['half_month_fee']) ? number_format($settings['half_month_fee'], 2, '.', '') : '300.00'; ?>"
                                                           min="0" step="0.01" required>
                                                </div>
                                                <div class="col-md-6 col-lg-4 mb-3">
                                                    <label for="one_month_fee" class="form-label">1 Month Fee (₱)</label>
                                                    <input type="number" class="form-control" id="one_month_fee" name="one_month_fee"
                                                           value="<?php echo isset($settings['one_month_fee']) ? number_format($settings['one_month_fee'], 2, '.', '') : '500.00'; ?>"
                                                           min="0" step="0.01" required>
                                                </div>
                                                <div class="col-md-6 col-lg-4 mb-3">
                                                    <label for="two_months_fee" class="form-label">2 Months Fee (₱)</label>
                                                    <input type="number" class="form-control" id="two_months_fee" name="two_months_fee"
                                                           value="<?php echo isset($settings['two_months_fee']) ? number_format($settings['two_months_fee'], 2, '.', '') : '900.00'; ?>"
                                                           min="0" step="0.01" required>
                                                </div>
                                                <div class="col-md-6 col-lg-4 mb-3">
                                                    <label for="three_months_fee" class="form-label">3 Months Fee (₱)</label>
                                                    <input type="number" class="form-control" id="three_months_fee" name="three_months_fee"
                                                           value="<?php echo isset($settings['three_months_fee']) ? number_format($settings['three_months_fee'], 2, '.', '') : '1300.00'; ?>"
                                                           min="0" step="0.01" required>
                                                </div>
                                                <div class="col-md-6 col-lg-4 mb-3">
                                                    <label for="four_months_fee" class="form-label">4 Months Fee (₱)</label>
                                                    <input type="number" class="form-control" id="four_months_fee" name="four_months_fee"
                                                           value="<?php echo isset($settings['four_months_fee']) ? number_format($settings['four_months_fee'], 2, '.', '') : '1700.00'; ?>"
                                                           min="0" step="0.01" required>
                                                </div>
                                                <div class="col-md-6 col-lg-4 mb-3">
                                                    <label for="five_months_fee" class="form-label">5 Months Fee (₱)</label>
                                                    <input type="number" class="form-control" id="five_months_fee" name="five_months_fee"
                                                           value="<?php echo isset($settings['five_months_fee']) ? number_format($settings['five_months_fee'], 2, '.', '') : '2100.00'; ?>"
                                                           min="0" step="0.01" required>
                                                </div>
                                                <div class="col-md-6 col-lg-4 mb-3">
                                                    <label for="six_months_fee" class="form-label">6 Months Fee (₱)</label>
                                                    <input type="number" class="form-control" id="six_months_fee" name="six_months_fee"
                                                           value="<?php echo isset($settings['six_months_fee']) ? number_format($settings['six_months_fee'], 2, '.', '') : '2500.00'; ?>"
                                                           min="0" step="0.01" required>
                                                </div>
                                                <div class="col-md-6 col-lg-4 mb-3">
                                                    <label for="seven_months_fee" class="form-label">7 Months Fee (₱)</label>
                                                    <input type="number" class="form-control" id="seven_months_fee" name="seven_months_fee"
                                                           value="<?php echo isset($settings['seven_months_fee']) ? number_format($settings['seven_months_fee'], 2, '.', '') : '2900.00'; ?>"
                                                           min="0" step="0.01" required>
                                                </div>
                                                <div class="col-md-6 col-lg-4 mb-3">
                                                    <label for="eight_months_fee" class="form-label">8 Months Fee (₱)</label>
                                                    <input type="number" class="form-control" id="eight_months_fee" name="eight_months_fee"
                                                           value="<?php echo isset($settings['eight_months_fee']) ? number_format($settings['eight_months_fee'], 2, '.', '') : '3300.00'; ?>"
                                                           min="0" step="0.01" required>
                                                </div>
                                                <div class="col-md-6 col-lg-4 mb-3">
                                                    <label for="nine_months_fee" class="form-label">9 Months Fee (₱)</label>
                                                    <input type="number" class="form-control" id="nine_months_fee" name="nine_months_fee"
                                                           value="<?php echo isset($settings['nine_months_fee']) ? number_format($settings['nine_months_fee'], 2, '.', '') : '3700.00'; ?>"
                                                           min="0" step="0.01" required>
                                                </div>
                                                <div class="col-md-6 col-lg-4 mb-3">
                                                    <label for="ten_months_fee" class="form-label">10 Months Fee (₱)</label>
                                                    <input type="number" class="form-control" id="ten_months_fee" name="ten_months_fee"
                                                           value="<?php echo isset($settings['ten_months_fee']) ? number_format($settings['ten_months_fee'], 2, '.', '') : '4100.00'; ?>"
                                                           min="0" step="0.01" required>
                                                </div>
                                                <div class="col-md-6 col-lg-4 mb-3">
                                                    <label for="eleven_months_fee" class="form-label">11 Months Fee (₱)</label>
                                                    <input type="number" class="form-control" id="eleven_months_fee" name="eleven_months_fee"
                                                           value="<?php echo isset($settings['eleven_months_fee']) ? number_format($settings['eleven_months_fee'], 2, '.', '') : '4500.00'; ?>"
                                                           min="0" step="0.01" required>
                                                </div>
                                                <div class="col-md-6 col-lg-4 mb-3">
                                                    <label for="one_year_fee" class="form-label">1 Year Fee (₱)</label>
                                                    <input type="number" class="form-control" id="one_year_fee" name="one_year_fee"
                                                           value="<?php echo isset($settings['one_year_fee']) ? number_format($settings['one_year_fee'], 2, '.', '') : '5000.00'; ?>"
                                                           min="0" step="0.01" required>
                                                </div>
                                                <div class="col-md-6 col-lg-4 mb-3">
                                                    <label for="two_years_fee" class="form-label">2 Years Fee (₱)</label>
                                                    <input type="number" class="form-control" id="two_years_fee" name="two_years_fee"
                                                           value="<?php echo isset($settings['two_years_fee']) ? number_format($settings['two_years_fee'], 2, '.', '') : '9000.00'; ?>"
                                                           min="0" step="0.01" required>
                                                </div>
                                                <div class="col-md-6 col-lg-4 mb-3">
                                                    <label for="three_years_fee" class="form-label">3 Years Fee (₱)</label>
                                                    <input type="number" class="form-control" id="three_years_fee" name="three_years_fee"
                                                           value="<?php echo isset($settings['three_years_fee']) ? number_format($settings['three_years_fee'], 2, '.', '') : '13000.00'; ?>"
                                                           min="0" step="0.01" required>
                                                </div>
                                            </div>
                                            <button type="submit" name="update_membership_fees" class="btn btn-primary">
                                                <i class="fas fa-save me-2"></i>Update Membership Fees
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
                </div>
            </div>
        </div>
    </div>


                        <!-- ── Website & Contact Info Card ── -->
                        <div class="card mb-4" id="website-info-card">
                            <div class="card-header d-flex align-items-center justify-content-between">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-globe me-2 text-warning"></i>Website &amp; Contact Information
                                </h5>
                                <a href="website_settings.php" class="btn btn-sm btn-outline-warning">
                                    <i class="fas fa-external-link-alt me-1"></i>Manage Website
                                </a>
                            </div>
                            <div class="card-body">
                                <p class="text-muted small mb-4">
                                    <i class="fas fa-info-circle me-1"></i>
                                    These details appear on your public promotional website. The gym name and logo above are also used by the website automatically.
                                </p>
                                <form method="POST">
                                    <div class="row g-3">
                                        <div class="col-md-12">
                                            <label class="form-label fw-semibold">Gym Address</label>
                                            <input type="text" class="form-control" name="address"
                                                value="<?php echo htmlspecialchars($settings['address'] ?? ''); ?>"
                                                placeholder="e.g. 123 Main Street, Your City, Philippines">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Phone / Contact Number</label>
                                            <input type="text" class="form-control" name="phone"
                                                value="<?php echo htmlspecialchars($settings['phone'] ?? ''); ?>"
                                                placeholder="09XX XXX XXXX">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Email Address</label>
                                            <input type="email" class="form-control" name="email"
                                                value="<?php echo htmlspecialchars($settings['email'] ?? ''); ?>"
                                                placeholder="gym@email.com">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">
                                                <i class="fas fa-mobile-alt me-1 text-primary"></i>GCash Number
                                            </label>
                                            <input type="text" class="form-control" name="gcash_number"
                                                value="<?php echo htmlspecialchars($settings['gcash_number'] ?? ''); ?>"
                                                placeholder="09XX XXX XXXX">
                                            <div class="form-text">This is shown on the website booking modal for online payments.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">GCash Account Name</label>
                                            <input type="text" class="form-control" name="gcash_name"
                                                value="<?php echo htmlspecialchars($settings['gcash_name'] ?? ''); ?>"
                                                placeholder="Name registered on GCash">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">
                                                <i class="fab fa-facebook me-1 text-primary"></i>Facebook Page URL
                                            </label>
                                            <input type="url" class="form-control" name="facebook_url"
                                                value="<?php echo htmlspecialchars($settings['facebook_url'] ?? ''); ?>"
                                                placeholder="https://facebook.com/yourpage">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">
                                                <i class="fab fa-instagram me-1 text-danger"></i>Instagram URL
                                            </label>
                                            <input type="url" class="form-control" name="instagram_url"
                                                value="<?php echo htmlspecialchars($settings['instagram_url'] ?? ''); ?>"
                                                placeholder="https://instagram.com/yourprofile">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">Operating Hours</label>
                                            <input type="text" class="form-control" name="hours"
                                                value="<?php echo htmlspecialchars($settings['hours'] ?? ''); ?>"
                                                placeholder="Monday – Sunday: 5:00 AM – 10:00 PM">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">About Us Text</label>
                                            <textarea class="form-control" name="about_text" rows="3"
                                                placeholder="Short description of your gym shown on the website..."><?php echo htmlspecialchars($settings['about_text'] ?? ''); ?></textarea>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">Google Maps Embed URL</label>
                                            <input type="text" class="form-control" name="map_embed"
                                                value="<?php echo htmlspecialchars($settings['map_embed'] ?? ''); ?>"
                                                placeholder="Paste the src=&quot;...&quot; URL from Google Maps embed code">
                                            <div class="form-text">
                                                Go to Google Maps → Share → Embed a map → Copy only the <code>src="..."</code> URL.
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <button type="submit" name="save_website_contact" class="btn btn-warning">
                                                <i class="fas fa-save me-2"></i>Save Website Info
                                            </button>
                                            <a href="../website/" target="_blank" class="btn btn-outline-secondary ms-2">
                                                <i class="fas fa-eye me-1"></i>Preview Website
                                            </a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

    <!-- Footer -->
    <footer class="bg-light text-center text-muted py-3 mt-5 border-top">
        <div class="container">
            <small>Developed by Tyron Del Valle</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password confirmation validation
        const confirmPasswordField = document.getElementById('confirm_password');
        if (confirmPasswordField) {
            confirmPasswordField.addEventListener('input', function() {
                const newPassword = document.getElementById('new_password').value;
                const confirmPassword = this.value;
                if (newPassword !== confirmPassword) {
                    this.setCustomValidity('Passwords do not match');
                } else {
                    this.setCustomValidity('');
                }
            });
        }

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
