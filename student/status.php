<?php
require_once '../includes/db_connect.php';
require_once '../includes/auth.php';


check_access('student');

$user_id = $_SESSION['user_id'];
$student = null;
$history = [];

try {

    $stmt = $pdo->prepare("SELECT student_id, admission_no, status, payment_status, transaction_id FROM students WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $user_id]);
    $student = $stmt->fetch();

    if ($student) {

        $hist_stmt = $pdo->prepare("SELECT * FROM status_history WHERE student_id = :student_id ORDER BY history_id DESC");
        $hist_stmt->execute(['student_id' => $student['student_id']]);
        $history = $hist_stmt->fetchAll();
    }
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

$page_title = "Track Application Status";
include '../includes/header.php';
?>

<div class="wrapper">

    <?php include '../includes/sidebar.php'; ?>


    <div id="content">
        <?php render_topbar(); ?>

        <div class="container-fluid">
            <?php render_page_header('Application Status Tracker'); ?>
            <?php if (!$student): ?>
                <div class="alert alert-warning" role="alert">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i>You have not initiated an admission form yet. Please fill the admission details first.
                </div>
            <?php else: ?>
                <div class="row g-4">

                    <div class="col-12 col-lg-4">
                        <div class="card bg-white p-4 h-100">
                            <h5 class="fw-bold text-primary mb-3">Application Status</h5>
                            <div class="mb-3">
                                <strong>Admission Number:</strong>
                                <div class="fs-5 text-dark fw-bold"><?php echo e($student['admission_no']); ?></div>
                            </div>
                            <div>
                                <strong>Current Decision:</strong>
                                <div class="mt-1">
                                    <?php if ($student['status'] === 'Pending'): ?>
                                        <span class="badge bg-warning text-dark px-3 py-2 fs-6"><i class="fa-solid fa-spinner fa-spin me-1"></i>Pending</span>
                                    <?php elseif ($student['status'] === 'Approved'): ?>
                                        <span class="badge bg-success px-3 py-2 fs-6"><i class="fa-solid fa-circle-check me-1"></i>Approved</span>
                                    <?php elseif ($student['status'] === 'Rejected'): ?>
                                        <span class="badge bg-danger px-3 py-2 fs-6"><i class="fa-solid fa-circle-xmark me-1"></i>Rejected</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <hr>
                            <div>
                                <strong>Processing Fee Payment:</strong>
                                <div class="mt-2">
                                    <?php if ($student['payment_status'] === 'Refunded'): ?>
                                        <div class="alert alert-danger mb-0 p-3">
                                            <div class="fw-bold"><i class="fa-solid fa-arrow-rotate-left me-1"></i>Payment is Back (Refunded)</div>
                                            <div class="small mt-1 text-dark">
                                                Since your form was rejected, your application processing fee of <strong>₹500.00</strong> has been processed for refund back to your source account.
                                            </div>
                                            <?php if (!empty($student['transaction_id'])): ?>
                                                <div class="small text-muted mt-1 font-monospace">Ref: <?php echo e($student['transaction_id']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php elseif ($student['payment_status'] === 'Paid'): ?>
                                        <span class="badge bg-success-subtle text-success px-3 py-2">
                                            <i class="fa-solid fa-circle-check me-1"></i>Paid (₹500.00)
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning px-3 py-2">
                                            <i class="fa-solid fa-clock me-1"></i>Unpaid
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if ($student['status'] === 'Approved'): ?>
                                <hr>
                                <a href="receipt.php" target="_blank" class="btn btn-success w-100"><i class="fa-solid fa-file-pdf me-2"></i>Download Receipt</a>
                            <?php endif; ?>
                        </div>
                    </div>


                    <div class="col-12 col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <i class="fa-solid fa-clock-rotate-left me-2"></i>Status History & Remarks Log
                            </div>
                            <div class="card-body">
                                <?php if (empty($history)): ?>
                                    <p class="text-muted text-center py-4">No reviews recorded yet. Please submit your application.</p>
                                <?php else: ?>
                                    <div class="d-flex flex-column gap-3">
                                        <?php foreach ($history as $log): ?>
                                            <div class="p-3 rounded-3 border bg-light shadow-sm">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <div>
                                                        <?php if ($log['status'] === 'Pending'): ?>
                                                            <span class="badge bg-warning text-dark px-2.5 py-1.5"><i class="fa-solid fa-spinner fa-spin me-1"></i>Pending</span>
                                                        <?php elseif ($log['status'] === 'Approved'): ?>
                                                            <span class="badge bg-success text-white px-2.5 py-1.5"><i class="fa-solid fa-circle-check me-1"></i>Approved</span>
                                                        <?php elseif ($log['status'] === 'Rejected'): ?>
                                                            <span class="badge bg-danger text-white px-2.5 py-1.5"><i class="fa-solid fa-circle-xmark me-1"></i>Rejected</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <span class="text-muted small"><i class="fa-regular fa-clock me-1"></i><?php echo date('d-M-Y h:i A', strtotime($log['updated_at'])); ?></span>
                                                </div>
                                                <div class="bg-white p-2.5 rounded border small text-muted" style="white-space: pre-wrap;">
                                                    <strong class="text-dark d-block mb-1">Staff Remarks:</strong>
                                                    <?php echo e($log['remarks']); ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>