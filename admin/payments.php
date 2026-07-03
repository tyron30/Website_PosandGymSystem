    <?php
include "../config/db.php";

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'cashier'])) {
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

// Check if student discount feature is enabled
$student_discount_enabled = isset($settings['student_discount_enabled']) ? $settings['student_discount_enabled'] : true;

// Function to generate unique receipt number
function generateReceiptNo() {
    return 'R' . date('Ymd') . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
}

// Handle payment addition
if (isset($_POST['add_payment'])) {
    $member_id = $_POST['member_id'];
    $amount = $_POST['amount'];
    $receipt_no = $_POST['receipt_no'];
    $payment_method = $_POST['payment_method'];
    $notes = $_POST['notes'];

    // Check if receipt_no already exists
    $check_stmt = $conn->prepare("SELECT id FROM payments WHERE receipt_no = ?");
    $check_stmt->bind_param("s", $receipt_no);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    if ($check_result->num_rows > 0) {
        $error = "Receipt No '$receipt_no' already exists. Please use a unique Receipt No.";
        $check_stmt->close();
    } else {
        $check_stmt->close();

        // Check if member is a student
        $member_query = $conn->prepare("SELECT is_student, student_id FROM members WHERE id = ?");
        $member_query->bind_param("i", $member_id);
        $member_query->execute();
        $member_result = $member_query->get_result();
        $member = $member_result->fetch_assoc();

        $is_student_discount = ($student_discount_enabled && $member['is_student']) ? 1 : 0;
        $student_id = ($student_discount_enabled && $member['is_student']) ? $member['student_id'] : null;
        $discount_amount = ($student_discount_enabled && $member['is_student']) ? ($amount * 0.20) : 0.00; // 20% discount for students
        $final_amount = $amount - $discount_amount; // Final amount after discount

        $reference_no = isset($_POST['reference_no']) ? trim($_POST['reference_no']) : null;

        // Validate reference number for non-cash payments
        if ($payment_method !== 'Cash' && empty($reference_no)) {
            $error = "Reference number is required for " . $payment_method . " payments.";
        } else {
            $stmt = $conn->prepare("INSERT INTO payments (member_id, amount, receipt_no, payment_method, reference_no, notes, is_student_discount, student_id, discount_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("idssssisd", $member_id, $amount, $receipt_no, $payment_method, $reference_no, $notes, $is_student_discount, $student_id, $discount_amount);
            $stmt->execute();
            $stmt->close();
            $member_query->close();

            // Auto check-in the member to attendance
            $attendance_stmt = $conn->prepare("INSERT INTO attendance (member_id, checkin_time) VALUES (?, NOW())");
            $attendance_stmt->bind_param("i", $member_id);
            $attendance_stmt->execute();
            $attendance_stmt->close();

            // Redirect or show success message
            header("Location: payments.php?success=1");
            exit();
        }
    }
}

// Fetch payments
$payments = $conn->query("SELECT p.*, m.fullname, m.is_student, u.fullname as created_by_name FROM payments p JOIN members m ON p.member_id = m.id LEFT JOIN users u ON p.created_by = u.id ORDER BY p.payment_date DESC");

// Fetch members for dropdown (exclude members who already have payments)
$members = $conn->query("SELECT id, fullname, is_student FROM members WHERE id NOT IN (SELECT member_id FROM payments)");

// Handle member_id from URL parameter
$selected_member_id = isset($_GET['member_id']) ? (int)$_GET['member_id'] : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Management - Gym Management System</title>
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
                    <span class="navbar-brand mb-0 h1">Payment Management - <?php echo htmlspecialchars($user['fullname']); ?> (Admin)</span>
                </div>
            </nav>

            <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3">Payment Management</h1>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                        <i class="fas fa-plus me-1"></i>Add Payment
                    </button>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Receipt No</th>
                                        <th>Member</th>
                                        <?php if ($student_discount_enabled): ?>
                                        <th>Student</th>
                                        <?php endif; ?>
                                        <th>Total Amount</th>
                                        <th>Payment Method</th>
                                        <th>Reference No</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($payment = $payments->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($payment['receipt_no']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['fullname']); ?></td>
                                        <?php if ($student_discount_enabled): ?>
                                        <td><?php if ($payment['is_student']): ?><span class="badge bg-info">Student</span><?php else: ?>No<?php endif; ?></td>
                                        <?php endif; ?>
                                        <td>₱<?php echo number_format($payment['amount'] - $payment['discount_amount'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($payment['payment_method'] ?? 'Cash'); ?></td>
                                        <td><?php echo htmlspecialchars($payment['reference_no'] ?? '-'); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($payment['payment_date'])); ?></td>
                                        <td>
                                            <a href="print_receipt.php?id=<?php echo $payment['id']; ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                                <i class="fas fa-print"></i> Print
                                            </a>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deletePayment(<?php echo $payment['id']; ?>)">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
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
    </div>

      <nav class="navbar navbar-light bg-light border-bottom">
                <div class="container-fluid">
                    <a href="members.php" class="btn btn-outline-secondary me-3">
                        <i class ="fas fa-arrow-left me-1"></i>Back to Members 
                    </a>

            </nav>

    <!-- Footer -->
    <footer class="bg-light text-center text-muted py-3 mt-5 border-top">
        <div class="container">
            <small>Developed by Tyron Del Valle</small>
        </div>
    </footer>

    <!-- Add Payment Modal -->
    <div class="modal fade" id="addPaymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Member</label>
                            <select class="form-control" name="member_id" id="member_select" required>
                                <option value="">Select Member</option>
                                <?php
                                $members->data_seek(0); // Reset pointer
                                while ($member = $members->fetch_assoc()): ?>
                                <option value="<?php echo $member['id']; ?>" data-is-student="<?php echo $member['is_student']; ?>">
                                    <?php echo htmlspecialchars($member['fullname']); ?>
                                    <?php if ($student_discount_enabled && $member['is_student']): ?>
                                        <span class="badge bg-info">Student (20% discount)</span>
                                    <?php endif; ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Amount</label>
                            <input type="number" step="0.01" class="form-control" name="amount" id="amount_input" required>
                            <div id="discount_info" class="mt-2" style="display: none;">
                                <small class="text-success">Student discount applied: <span id="discount_amount">₱0.00</span> (20% off)</small>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Receipt No</label>
                            <input type="text" class="form-control" name="receipt_no" id="receipt_no" readonly required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment Method</label>
                            <select class="form-control" name="payment_method" id="payment_method">
                                <option value="Cash">Cash</option>
                                <option value="Card">Card</option>
                                <option value="Online">Online</option>
                                <option value="GCash">GCash</option>
                                <option value="Maya">Maya</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                            </select>
                        </div>
                        <div class="mb-3 reference-field" style="display: none;">
                            <label class="form-label">Reference Number</label>
                            <input type="text" class="form-control" name="reference_no" id="reference_no" placeholder="Enter reference number">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_payment" class="btn btn-success">Add Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle member selection and student discount
        document.getElementById('member_select').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const isStudent = selectedOption.getAttribute('data-is-student') === '1';
            const discountInfo = document.getElementById('discount_info');
            const discountAmountSpan = document.getElementById('discount_amount');
            const amountInput = document.getElementById('amount_input');

            if (isStudent) {
                discountInfo.style.display = 'block';
                // Calculate discount when amount changes
                amountInput.addEventListener('input', updateDiscountDisplay);
                updateDiscountDisplay();
            } else {
                discountInfo.style.display = 'none';
                amountInput.removeEventListener('input', updateDiscountDisplay);
            }
        });

        function updateDiscountDisplay() {
            const amountInput = document.getElementById('amount_input');
            const discountAmountSpan = document.getElementById('discount_amount');
            const amount = parseFloat(amountInput.value) || 0;
            const discount = amount * 0.20; // 20% discount
            discountAmountSpan.textContent = '₱' + discount.toFixed(2);
        }

        // Handle student checkbox in members.php
        const studentCheckbox = document.getElementById('is_student');
        if (studentCheckbox) {
            studentCheckbox.addEventListener('change', function() {
                const studentIdField = document.querySelector('.student-id-field');
                if (this.checked) {
                    studentIdField.style.display = 'block';
                } else {
                    studentIdField.style.display = 'none';
                }
            });
        }

        // Handle payment deletion
        function deletePayment(paymentId) {
            if (confirm('Are you sure you want to delete this payment? This action cannot be undone.')) {
                fetch('delete_payment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'payment_id=' + paymentId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Payment deleted successfully!', 'success');
                        location.reload();
                    } else {
                        showToast('Failed to delete payment: ' + (data.message || 'Unknown error'), 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('An error occurred while deleting the payment.', 'danger');
                });
            }
        }

        // Function to generate receipt number
        function generateReceiptNumber() {
            const today = new Date();
            const dateStr = today.getFullYear().toString() +
                           (today.getMonth() + 1).toString().padStart(2, '0') +
                           today.getDate().toString().padStart(2, '0');
            const randomStr = Math.random().toString(36).substring(2, 8).toUpperCase();
            return 'R' + dateStr + randomStr;
        }

        // Function to populate receipt number
        function populateReceiptNumber() {
            const receiptInput = document.getElementById('receipt_no');
            receiptInput.value = generateReceiptNumber();
        }

        // Handle payment method selection
        const paymentMethodSelect = document.getElementById('payment_method');
        if (paymentMethodSelect) {
            paymentMethodSelect.addEventListener('change', function() {
                const referenceField = document.querySelector('.reference-field');
                if (this.value !== 'Cash') {
                    referenceField.style.display = 'block';
                    referenceField.querySelector('label').innerHTML = 'Reference Number *';
                    referenceField.querySelector('input').setAttribute('required', 'required');
                } else {
                    referenceField.style.display = 'none';
                    referenceField.querySelector('input').removeAttribute('required');
                }
            });
        }

        // Auto-populate receipt number when modal is shown
        document.getElementById('addPaymentModal').addEventListener('show.bs.modal', function() {
            populateReceiptNumber();
        });

        // Auto-open modal and pre-select member if member_id is in URL
        <?php if ($selected_member_id): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const memberSelect = document.getElementById('member_select');/*  */
            memberSelect.value = '<?php echo $selected_member_id; ?>';

            // Trigger change event to show discount if applicable
            const event = new Event('change');
            memberSelect.dispatchEvent(event);

            // Open the modal
            const modal = new bootstrap.Modal(document.getElementById('addPaymentModal'));
            modal.show();
        });
        <?php endif; ?>
    </script>
    <script src="../assets/sidebar.js"></script>
</body>
</html>
