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

// Database credentials (empty password consistent with config/db.php)
$host = 'localhost';
$user_db = 'root';
$pass = '';
$db_name = 'gym_db';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['sql_file'])) {
    $file = $_FILES['sql_file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $message = "File upload error.";
        $message_type = "danger";
    } elseif (!preg_match('/\\.sql$/i', $file['name'])) {
        $message = "Only SQL files are allowed.";
        $message_type = "danger";
    } elseif ($file['size'] > 50 * 1024 * 1024) {
        $message = "File size exceeds 50MB.";
        $message_type = "danger";
    } else {
        $sql_content = file_get_contents($file['tmp_name']);
        if ($sql_content === false) {
            $message = "Error reading uploaded file.";
            $message_type = "danger";
        } else {
            $import_conn = new mysqli($host, $user_db, $pass, $db_name);
            if ($import_conn->connect_error) {
                $message = "Database connection failed for import: " . $import_conn->connect_error;
                $message_type = "danger";
            } else {
                if ($import_conn->multi_query($sql_content)) {
                    do {
                        if ($result = $import_conn->store_result()) {
                            $result->free();
                        }
                    } while ($import_conn->more_results() && $import_conn->next_result());

                    if ($import_conn->errno) {
                        $message = "Error importing database: " . $import_conn->error;
                        $message_type = "danger";
                    } else {
                        $message = "Database imported successfully!";
                        $message_type = "success";
                    }
                } else {
                    $message = "No SQL queries found in file.";
                    $message_type = "danger";
                }
                $import_conn->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Database - Gym Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/style.css?v=20260630f" rel="stylesheet">
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <nav id="sidebar" class="bg-primary text-white vh-100" style="width: 250px;">
            <div class="p-3">
                <div class="text-center mb-4">
                    <img src="../gym logo.jpg" alt="Gym Logo" class="rounded-circle mb-2" style="width: 80px; height: 80px;">
                    <h5 class="fw-bold">Gym Management System</h5>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item mb-2">
                        <a class="nav-link text-white" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i><span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link text-white" href="members.php">
                            <i class="fas fa-users me-2"></i><span>Members</span>
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link text-white" href="settings.php">
                            <i class="fas fa-cog me-2"></i><span>Settings</span>
                        </a>
                    </li>
                    <li class="nav-item mt-4">
                        <a class="nav-link text-white" href="../logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i><span>Logout</span>
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="flex-grow-1">
            <nav class="navbar navbar-light bg-light border-bottom">
                <div class="container-fluid">
                    <button class="btn btn-outline-secondary me-3" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <span class="navbar-brand mb-0 h1">Import Database</span>
                </div>
            </nav>
            <div class="container-fluid mt-4">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="mb-0"><i class="fas fa-upload me-2"></i>Import Database Backup</h3>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($message)): ?>
                                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                                        <?php echo htmlspecialchars($message); ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>
                                <p class="text-muted mb-4">
                                    Upload an SQL file to import database backup. Max file size: 50MB. <strong>Warning:</strong> This will overwrite existing data!
                                </p>
                                <form method="POST" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label for="sql_file" class="form-label">Select SQL File</label>
                                        <input type="file" class="form-control" id="sql_file" name="sql_file" accept=".sql" required>
                                        <div class="form-text">Only .sql files up to 50MB are allowed.</div>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-database me-2"></i>Import Database
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/sidebar.js"></script>
</body>
</html>
