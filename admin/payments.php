<?php
require_once '../includes/db_connect.php';
require_once '../includes/auth.php';

check_access('admin');

$error_msg = "";
$success_msg = "";

// Handle Manual Refund Action by Admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'refund_payment') {
    if (!validate_csrf()) {
        $error_msg = "Security validation failed. Please refresh the page and try again.";
    } else {
        $payment_id = intval($_POST['payment_id'] ?? 0);
        $refund_reason = trim($_POST['refund_reason'] ?? 'Refund processed by Admin');

        if ($payment_id > 0) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT p.*, s.admission_no, s.full_name FROM payments p JOIN students s ON p.student_id = s.student_id WHERE p.payment_id = :pid");
            $stmt->execute(['pid' => $payment_id]);
            $payment_row = $stmt->fetch();

            if ($payment_row) {
                if ($payment_row['status'] === 'Refunded') {
                    $error_msg = "This payment is already marked as refunded.";
                    $pdo->rollBack();
                } else {
                    $now_str = date('Y-m-d H:i:s');
                    // 1. Update payments table
                    $upd_pay = $pdo->prepare("UPDATE payments SET status = 'Refunded', refund_reason = :reason, refunded_at = :ref_at WHERE payment_id = :pid");
                    $upd_pay->execute([
                        'reason' => $refund_reason,
                        'ref_at' => $now_str,
                        'pid' => $payment_id
                    ]);

                    // 2. Update students table
                    $upd_stu = $pdo->prepare("UPDATE students SET payment_status = 'Refunded' WHERE student_id = :sid");
                    $upd_stu->execute(['sid' => $payment_row['student_id']]);

                    // 3. Log in status history
                    $hist_stmt = $pdo->prepare("INSERT INTO status_history (student_id, status, remarks) VALUES (:sid, :status, :remarks)");
                    $hist_stmt->execute([
                        'sid' => $payment_row['student_id'],
                        'status' => 'Pending',
                        'remarks' => "Payment of ₹" . number_format($payment_row['amount'], 2) . " marked as Refunded / Returned. Reason: " . $refund_reason
                    ]);

                    $pdo->commit();
                    $success_msg = "Payment for application " . e($payment_row['admission_no']) . " has been successfully refunded / returned.";
                }
            } else {
                $pdo->rollBack();
                $error_msg = "Payment record not found.";
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error_msg = "Refund failed: " . $e->getMessage();
        }
    }
}
}

// Filters & Search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status_filter']) ? trim($_GET['status_filter']) : '';
$method_filter = isset($_GET['method_filter']) ? trim($_GET['method_filter']) : '';
$course_filter = isset($_GET['course_filter']) ? trim($_GET['course_filter']) : '';

// 1. Calculating Real-Time Totals (Calculating all time)
try {
    $stats_total_collected = (float) $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'Completed'")->fetchColumn();
    $stats_total_gross     = (float) $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments")->fetchColumn();
    $stats_total_refunded  = (float) $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'Refunded'")->fetchColumn();
    $stats_count_total     = (int)   $pdo->query("SELECT COUNT(*) FROM payments")->fetchColumn();
    $stats_count_completed = (int)   $pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'Completed'")->fetchColumn();
    $stats_count_refunded  = (int)   $pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'Refunded'")->fetchColumn();
    $stats_count_unpaid    = (int)   $pdo->query("SELECT COUNT(*) FROM students WHERE is_submitted = 1 AND payment_status = 'Unpaid'")->fetchColumn();
} catch (PDOException $e) {
    die("Database Calculation Error: " . $e->getMessage());
}

// Fetch Courses for filter
$courses = $pdo->query("SELECT * FROM courses ORDER BY course_name ASC")->fetchAll();

// 2. Fetch Payments List with Filters
try {
    $sql = "
        SELECT p.*, s.admission_no, s.full_name, s.mobile, s.email, s.status AS admission_status, c.course_name
        FROM payments p
        JOIN students s ON p.student_id = s.student_id
        LEFT JOIN courses c ON s.course_id = c.course_id
        WHERE 1=1
    ";
    $params = [];

    if (!empty($search)) {
        $sql .= " AND (s.admission_no LIKE :s1 OR s.full_name LIKE :s2 OR p.transaction_id LIKE :s3 OR CAST(s.mobile AS VARCHAR) LIKE :s4)";
        $params['s1'] = "%$search%";
        $params['s2'] = "%$search%";
        $params['s3'] = "%$search%";
        $params['s4'] = "%$search%";
    }

    if (!empty($status_filter)) {
        $sql .= " AND p.status = :status";
        $params['status'] = $status_filter;
    }

    if (!empty($method_filter)) {
        $sql .= " AND p.payment_method LIKE :method";
        $params['method'] = "%$method_filter%";
    }

    if (!empty($course_filter)) {
        $sql .= " AND s.course_id = :cid";
        $params['cid'] = $course_filter;
    }

    $sql .= " ORDER BY p.payment_id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $payments = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Query Error: " . $e->getMessage());
}

$page_title = "Payment Transactions & Accounting";
include '../includes/header.php';
?>

<div class="wrapper">
    <?php include '../includes/sidebar.php'; ?>

    <div id="content">
        <?php render_topbar(); ?>

        <div class="container-fluid">
            <?php render_page_header(
                'Fee Payments & Revenue Desk', 
                '<button onclick="window.print()" class="btn btn-sm btn-outline-secondary me-2"><i class="fa-solid fa-print me-1"></i>Print Report</button>'
            ); ?>

            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-circle-check me-2"></i><?php echo $success_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i><?php echo $error_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Real-Time Financial Overview Cards (Calculating all time) -->
            <div class="row g-3 mb-4">
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-3 h-100 bg-primary text-white">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-white-50 text-uppercase fw-semibold mb-1" style="font-size: 0.78rem;">Net Active Revenue</h6>
                                    <h3 class="fw-bold mb-0 text-white">₹<?php echo number_format($stats_total_collected, 2); ?></h3>
                                    <small class="text-white-50"><?php echo $stats_count_completed; ?> Active completed payments</small>
                                </div>
                                <div class="bg-white bg-opacity-25 rounded-circle p-3 text-white">
                                    <i class="fa-solid fa-vault fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                        <div class="card-body p-4 border-start border-4 border-success">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted text-uppercase fw-semibold mb-1" style="font-size: 0.78rem;">Gross Collections</h6>
                                    <h3 class="fw-bold mb-0 text-dark">₹<?php echo number_format($stats_total_gross, 2); ?></h3>
                                    <small class="text-success fw-medium"><i class="fa-solid fa-receipt me-1"></i><?php echo $stats_count_total; ?> Total attempts recorded</small>
                                </div>
                                <div class="bg-success-subtle text-success rounded-circle p-3">
                                    <i class="fa-solid fa-hand-holding-dollar fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                        <div class="card-body p-4 border-start border-4 border-danger">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted text-uppercase fw-semibold mb-1" style="font-size: 0.78rem;">Payment is Back (Refunded)</h6>
                                    <h3 class="fw-bold mb-0 text-danger">₹<?php echo number_format($stats_total_refunded, 2); ?></h3>
                                    <small class="text-danger fw-medium"><i class="fa-solid fa-arrow-rotate-left me-1"></i><?php echo $stats_count_refunded; ?> Refunded transactions</small>
                                </div>
                                <div class="bg-danger-subtle text-danger rounded-circle p-3">
                                    <i class="fa-solid fa-arrow-rotate-left fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                        <div class="card-body p-4 border-start border-4 border-warning">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted text-uppercase fw-semibold mb-1" style="font-size: 0.78rem;">Unpaid Submitted Forms</h6>
                                    <h3 class="fw-bold mb-0 text-warning"><?php echo $stats_count_unpaid; ?></h3>
                                    <small class="text-muted">Awaiting fee submission</small>
                                </div>
                                <div class="bg-warning-subtle text-warning rounded-circle p-3">
                                    <i class="fa-solid fa-clock fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="card mb-4 shadow-sm border-0">
                <div class="card-body">
                    <form action="payments.php" method="GET" class="row g-3 align-items-end">
                        <div class="col-12 col-md-4">
                            <label for="search" class="form-label fw-semibold small">Search Payments</label>
                            <input type="text" class="form-control" id="search" name="search"
                                placeholder="Ref ID, Admission No, Student Name..." value="<?php echo e($search); ?>">
                        </div>

                        <div class="col-6 col-md-2">
                            <label for="status_filter" class="form-label fw-semibold small">Payment Status</label>
                            <select class="form-select" id="status_filter" name="status_filter">
                                <option value="">All Statuses</option>
                                <option value="Completed" <?php echo ($status_filter === 'Completed') ? 'selected' : ''; ?>>Completed (Active)</option>
                                <option value="Refunded" <?php echo ($status_filter === 'Refunded') ? 'selected' : ''; ?>>Refunded (Payment Back)</option>
                            </select>
                        </div>

                        <div class="col-6 col-md-2">
                            <label for="method_filter" class="form-label fw-semibold small">Payment Method</label>
                            <select class="form-select" id="method_filter" name="method_filter">
                                <option value="">All Methods</option>
                                <option value="UPI" <?php echo ($method_filter === 'UPI') ? 'selected' : ''; ?>>UPI</option>
                            </select>
                        </div>

                        <div class="col-8 col-md-3">
                            <label for="course_filter" class="form-label fw-semibold small">Course</label>
                            <select class="form-select" id="course_filter" name="course_filter">
                                <option value="">All Courses</option>
                                <?php foreach ($courses as $c): ?>
                                    <option value="<?php echo $c['course_id']; ?>" <?php echo ($course_filter == $c['course_id']) ? 'selected' : ''; ?>>
                                        <?php echo e($c['course_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-4 col-md-1">
                            <a href="payments.php" class="btn btn-outline-secondary w-100 py-2" title="Reset Filters">
                                <i class="fa-solid fa-rotate-left"></i>
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Payments Database Records Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-dark">
                        <i class="fa-solid fa-list-check me-2 text-primary"></i>All Payment Transactions (<?php echo count($payments); ?>)
                    </span>
                    <span class="badge bg-light text-muted border">Fee Rate: ₹500.00 / Student</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">#</th>
                                    <th>Transaction Ref</th>
                                    <th>Student & Admission No</th>
                                    <th>Course</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Paid Date & Time</th>
                                    <th>Status</th>
                                    <th class="text-center pe-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($payments)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-5">
                                            <i class="fa-solid fa-receipt fa-3x text-muted mb-3 d-block opacity-50"></i>
                                            No payment records found matching your filters.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php $sr = 1; ?>
                                    <?php foreach ($payments as $p): ?>
                                        <tr>
                                            <td class="ps-3 text-muted"><?php echo $sr++; ?></td>
                                            <td>
                                                <span class="font-monospace fw-bold text-primary"><?php echo e($p['transaction_id']); ?></span>
                                            </td>
                                            <td>
                                                <div class="fw-semibold text-dark"><?php echo e($p['full_name']); ?></div>
                                                <a href="view_students.php?id=<?php echo $p['student_id']; ?>" class="text-muted small text-decoration-none">
                                                    <i class="fa-solid fa-id-card me-1"></i><?php echo e($p['admission_no']); ?>
                                                </a>
                                            </td>
                                            <td class="small text-muted"><?php echo e($p['course_name'] ?? 'Not Specified'); ?></td>
                                            <td>
                                                <span class="fw-bold fs-6 <?php echo ($p['status'] === 'Refunded') ? 'text-decoration-line-through text-muted' : 'text-success'; ?>">
                                                    ₹<?php echo number_format($p['amount'], 2); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border">
                                                    <i class="fa-solid fa-qrcode text-primary me-1"></i>
                                                    <?php echo e($p['payment_method']); ?>
                                                </span>
                                            </td>
                                            <td class="small text-muted">
                                                <?php echo date('d-M-Y', strtotime($p['created_at'])); ?><br>
                                                <small class="text-secondary"><?php echo date('h:i A', strtotime($p['created_at'])); ?></small>
                                            </td>
                                            <td>
                                                <?php if ($p['status'] === 'Completed'): ?>
                                                    <span class="badge bg-success-subtle text-success px-2 py-1">
                                                        <i class="fa-solid fa-circle-check me-1"></i>Completed
                                                    </span>
                                                <?php elseif ($p['status'] === 'Refunded'): ?>
                                                    <span class="badge bg-danger-subtle text-danger px-2 py-1" title="<?php echo e($p['refund_reason'] ?: 'Refunded on rejection'); ?>">
                                                        <i class="fa-solid fa-arrow-rotate-left me-1"></i>Payment is Back
                                                    </span>
                                                    <?php if (!empty($p['refunded_at'])): ?>
                                                        <div class="small text-muted" style="font-size: 0.72rem;">
                                                            Back at: <?php echo date('d-M-y H:i', strtotime($p['refunded_at'])); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center pe-3 text-nowrap">
                                                <a href="view_students.php?id=<?php echo $p['student_id']; ?>" class="btn btn-sm btn-outline-primary me-1" title="View Student Profile">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                                <?php if ($p['status'] === 'Completed'): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            onclick="openRefundModal('<?php echo $p['payment_id']; ?>', '<?php echo e($p['admission_no']); ?>', '<?php echo e(addslashes($p['full_name'])); ?>', '<?php echo number_format($p['amount'], 2); ?>')"
                                                            title="Issue Refund / Send Payment Back">
                                                        <i class="fa-solid fa-arrow-rotate-left"></i> Refund
                                                    </button>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary-subtle text-secondary small">Refunded</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Refund Confirmation Modal -->
<div class="modal fade" id="refundModal" tabindex="-1" aria-labelledby="refundModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="payments.php">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="refund_payment">
                <input type="hidden" name="payment_id" id="modal_payment_id" value="">
                
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="refundModalLabel">
                        <i class="fa-solid fa-arrow-rotate-left me-2"></i>Send Payment Back (Process Refund)
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to mark this fee payment as <strong>Refunded / Payment is Back</strong>?</p>
                    <div class="p-3 bg-light rounded border mb-3">
                        <div><strong>Student:</strong> <span id="modal_student_name"></span></div>
                        <div><strong>Admission No:</strong> <span id="modal_admission_no"></span></div>
                        <div><strong>Refund Amount:</strong> <span class="text-danger fw-bold">₹<span id="modal_amount"></span></span></div>
                    </div>
                    <div class="mb-3">
                        <label for="refund_reason" class="form-label fw-bold">Reason for Refund / Remarks:</label>
                        <input type="text" class="form-control" id="refund_reason" name="refund_reason" value="Application rejected / Student requested fee refund" required>
                    </div>
                    <small class="text-muted">This will change the student's status to show <em>Payment is Back</em> and update financial totals immediately.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm & Return Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openRefundModal(paymentId, admissionNo, studentName, amount) {
    document.getElementById('modal_payment_id').value = paymentId;
    document.getElementById('modal_admission_no').innerText = admissionNo;
    document.getElementById('modal_student_name').innerText = studentName;
    document.getElementById('modal_amount').innerText = amount;
    
    var modal = new bootstrap.Modal(document.getElementById('refundModal'));
    modal.show();
}

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search');
    if (searchInput) {
        let timeout = null;
        searchInput.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                searchInput.form.submit();
            }, 600);
        });
    }

    const selects = document.querySelectorAll('form select');
    selects.forEach(select => {
        select.addEventListener('change', function() {
            this.form.submit();
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>
