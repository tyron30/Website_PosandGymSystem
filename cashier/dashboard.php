<?php
include "../config/db.php";

if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
     exit();
}

$user = $_SESSION['user'];
if ($user['role'] !== 'cashier') {
    header("Location: ../index.php");
    exit();
}

// Fetch gym settings
$settings = $conn->query("SELECT * FROM gym_settings WHERE id = 1")->fetch_assoc();
if (!$settings) {
    // Insert default settings if not exists
    $conn->query("INSERT INTO gym_settings (gym_name, logo_path, background_path) VALUES ('Gym Management System', 'gym logo.jpg', 'gym background.jpg')");
    $settings = $conn->query("SELECT * FROM gym_settings WHERE id = 1")->fetch_assoc();
}

// Check if student discount feature is enabled
$student_discount_enabled = isset($settings['student_discount_enabled']) ? $settings['student_discount_enabled'] : true;

// Fetch data for modals
$members = $conn->query("SELECT m.*, CASE WHEN EXISTS (SELECT 1 FROM payments p WHERE p.member_id = m.id AND p.is_student_discount = 1) THEN 1 ELSE 0 END as has_discount FROM members m ORDER BY m.id DESC");
$payments = $conn->query("SELECT p.*, m.fullname FROM payments p JOIN members m ON p.member_id = m.id ORDER BY p.payment_date DESC");
$active_members = $conn->query("SELECT * FROM members WHERE status = 'ACTIVE' ORDER BY id DESC");

// Fetch attendance counts for different periods
$today = date('Y-m-d');
$current_month = date('Y-m');
$current_year = date('Y');

// Per Session (Daily)
$daily_attendance = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE DATE(checkin_time) = '$today'")->fetch_assoc()['count'];

// Monthly
$monthly_attendance = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE DATE_FORMAT(checkin_time, '%Y-%m') = '$current_month'")->fetch_assoc()['count'];

// Half Month (Last 15 days)
$half_month_start = date('Y-m-d', strtotime('-15 days'));
$half_month_attendance = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE DATE(checkin_time) >= '$half_month_start'")->fetch_assoc()['count'];

// Annual
$annual_attendance = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE YEAR(checkin_time) = '$current_year'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashier Dashboard - Gym Management System</title>
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
                        <a class="nav-link <?php echo ($settings['sidebar_theme'] == 'light') ? 'text-dark' : 'text-white'; ?> active" href="dashboard.php">
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
                        <a class="nav-link <?php echo ($settings['sidebar_theme'] == 'light') ? 'text-dark' : 'text-white'; ?>" href="settings.php">
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
                    <span class="navbar-brand mb-0 h1">Welcome, <?php echo htmlspecialchars($user['fullname']); ?> (Cashier)</span>
                </div>
            </nav>

            <div class="container-fluid mt-4">
                <div class="row">
                    <div class="col-12">
                        <h1 class="h3 mb-4">Cashier Dashboard</h1>
                        <div class="row">
                            <div class="col-md-4 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title"><i class="fas fa-users me-2"></i>Total Members</h5>
                                        <p class="card-text display-4"><?php echo $conn->query("SELECT COUNT(*) as count FROM members")->fetch_assoc()['count']; ?></p>
                                        <a href="members.php" class="btn btn-primary">Manage Members</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title"><i class="fas fa-cash-register me-2"></i>Today's POS Sales</h5>
                                        <p class="card-text display-4">₱<?php echo number_format($conn->query("SELECT SUM(total_amount) as total FROM pos_sales WHERE DATE(sale_date) = CURDATE()")->fetch_assoc()['total'] ?? 0, 2); ?></p>
                                        <a href="pos.php" class="btn btn-primary">Go to POS</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title"><i class="fas fa-calendar-check me-2"></i>Today's Check-ins</h5>
                                        <p class="card-text display-4"><?php echo $conn->query("SELECT COUNT(*) as count FROM attendance WHERE DATE(checkin_time) = CURDATE()")->fetch_assoc()['count']; ?></p>
                                        <a href="attendance.php" class="btn btn-primary">View Attendance</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title"><i class="fas fa-user-check me-2"></i>Active Members</h5>
                                        <p class="card-text display-4"><?php echo $conn->query("SELECT COUNT(*) as count FROM members WHERE status = 'ACTIVE'")->fetch_assoc()['count']; ?></p>
                                        <a href="members.php" class="btn btn-primary">View Members</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Additional POS Metrics Row -->
                        <div class="row mt-3">
                            <div class="col-md-4 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title"><i class="fas fa-shopping-cart me-2"></i>Monthly POS Sales</h6>
                                        <p class="card-text h4">₱<?php echo number_format($conn->query("SELECT SUM(total_amount) as total FROM pos_sales WHERE MONTH(sale_date) = MONTH(CURDATE()) AND YEAR(sale_date) = YEAR(CURDATE())")->fetch_assoc()['total'] ?? 0, 2); ?></p>
                                        <small class="text-muted">This month's total sales</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title"><i class="fas fa-chart-line me-2"></i>Items Sold Today</h6>
                                        <p class="card-text h4"><?php echo $conn->query("SELECT SUM(quantity) as total FROM pos_sale_items psi JOIN pos_sales ps ON psi.sale_id = ps.id WHERE DATE(ps.sale_date) = CURDATE()")->fetch_assoc()['total'] ?? 0; ?></p>
                                        <small class="text-muted">Total items sold today</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title"><i class="fas fa-boxes me-2"></i>Low Stock Items</h6>
                                        <p class="card-text h4"><?php echo $conn->query("SELECT COUNT(*) as count FROM pos_items WHERE stock_quantity <= 10 AND is_active = 1")->fetch_assoc()['count']; ?></p>
                                        <small class="text-muted">Items with ≤10 stock</small>
                                    </div>
                                </div>
                            </div>
                        </div>
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

    <!-- Members Modal -->
    <div class="modal fade" id="membersModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">All Members Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Full Name</th>
                                    <th>Plan</th>
                                    <th>Paid</th>
                                    <?php if ($student_discount_enabled): ?>
                                    <th>Student</th>
                                    <?php endif; ?>
                                    <th>Expiry</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($member = $members->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($member['fullname']); ?></td>
                                    <td><?php echo htmlspecialchars($member['plan']); ?></td>
                                    <td>
                                        <?php
                                        if ($member['plan'] == 'Per Session') {
                                            echo '1 session';
                                        } elseif ($member['plan'] == 'Half Month') {
                                            echo 'Half month';
                                        } elseif ($member['plan'] == 'Monthly') {
                                            echo '1 month';
                                        } else {
                                            echo htmlspecialchars($member['plan']);
                                        }
                                        ?>
                                    </td>
                                    <?php if ($student_discount_enabled): ?>
                                    <td><?php echo $member['is_student'] ? '<span class="badge bg-info">Student</span>' : '<span class="badge bg-secondary">Regular</span>'; ?></td>
                                    <?php endif; ?>
                                    <td>
                                        <?php
                                        $start_date = strtotime($member['start_date']);
                                        $end_date = strtotime($member['end_date']);
                                        $today = strtotime(date('Y-m-d'));

                                        if ($member['plan'] == 'Per Session') {
                                            echo '1 day';
                                        } elseif ($today < $start_date) {
                                            $days_to_start = ceil(($start_date - $today) / (60 * 60 * 24));
                                            echo '<span class="badge bg-info">Starts in ' . $days_to_start . ' days</span>';
                                        } elseif ($today > $end_date) {
                                            echo '<span class="badge bg-danger">Expired</span>';
                                        } else {
                                            $days_diff = ($end_date - $today) / (60 * 60 * 24);
                                            if ($days_diff <= 7) {
                                                echo '<span class="badge bg-warning">' . ceil($days_diff) . ' days left</span>';
                                            } else {
                                                echo '<span class="badge bg-success">' . ceil($days_diff) . ' days left</span>';
                                            }
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payments Modal -->
    <div class="modal fade" id="paymentsModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">All Payments Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Member</th>
                                    <th>Amount</th>
                                    <th>Receipt No</th>
                                    <th>Payment Method</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($payment = $payments->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($payment['id']); ?></td>
                                    <td><?php echo htmlspecialchars($payment['fullname']); ?></td>
                                    <td>₱<?php echo number_format($payment['amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($payment['receipt_no']); ?></td>
                                    <td><?php echo htmlspecialchars($payment['payment_method'] ?? 'Cash'); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($payment['payment_date'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Members Modal -->
    <div class="modal fade" id="activeMembersModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Active Members Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Join Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($active_member = $active_members->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($active_member['id']); ?></td>
                                    <td><?php echo htmlspecialchars($active_member['fullname']); ?></td>
                                    <td><?php echo htmlspecialchars($active_member['email']); ?></td>
                                    <td><?php echo htmlspecialchars($active_member['phone']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($active_member['start_date'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/sidebar.js"></script>
</body>
</html>
