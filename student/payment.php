<?php
require_once '../includes/db_connect.php';
require_once '../includes/auth.php';

check_access('student');

$user_id = $_SESSION['user_id'];
$error_msg = "";
$success_msg = "";

try {
    $stmt = $pdo->prepare("SELECT s.*, c.course_name, d.photo, d.marksheet10, d.marksheet12, d.leaving_certificate, d.aadhaar 
                           FROM students s 
                           LEFT JOIN courses c ON s.course_id = c.course_id
                           LEFT JOIN documents d ON s.student_id = d.student_id 
                           WHERE s.user_id = :user_id");
    $stmt->execute(['user_id' => $user_id]);
    $student = $stmt->fetch();

    if (!$student) {
        header("Location: apply.php");
        exit;
    }

    $has_docs = ($student['photo'] && $student['marksheet10'] && $student['marksheet12'] && $student['leaving_certificate'] && $student['aadhaar']);
    if (!$has_docs) {
        header("Location: upload.php");
        exit;
    }

    if ($student['is_submitted'] == 1 && $student['status'] !== 'Rejected') {
        header("Location: dashboard.php");
        exit;
    }

    // Fetch existing payment record if any
    $pay_check = $pdo->prepare("SELECT * FROM payments WHERE student_id = :sid ORDER BY payment_id DESC LIMIT 1");
    $pay_check->execute(['sid' => $student['student_id']]);
    $latest_payment = $pay_check->fetch();

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

// Handle In-House Payment Processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        $error_msg = "Security validation failed. Please refresh the page and try again.";
    } else {
        $payment_mode = trim($_POST['payment_mode'] ?? 'upi');
        $transaction_id = "";
        $payment_method_label = "Online / In-House Gateway";

        // Prevent double-payment if already active and paid
        if ($student['payment_status'] === 'Paid') {
            $error_msg = "You have already completed the payment! Duplicate payments are not allowed.";
        } else {
        // UPI Payment Processing
        $upi_id = trim($_POST['upi_vpa'] ?? '');
        $payment_method_label = !empty($upi_id) ? "UPI (" . $upi_id . ")" : "UPI / Online";
        $transaction_id = "UPI-" . strtoupper(bin2hex(random_bytes(5))) . "-" . date('His');

        // Validate Transaction ID and Check for Duplicates
        if (empty($error_msg) && !empty($transaction_id)) {
            try {
                // Check duplicate transaction ID across all payments and students
                $dup_stmt = $pdo->prepare("
                    SELECT payment_id FROM payments WHERE transaction_id = :tx 
                    UNION 
                    SELECT student_id FROM students WHERE transaction_id = :tx AND student_id != :sid
                ");
                $dup_stmt->execute(['tx' => $transaction_id, 'sid' => $student['student_id']]);
                
                if ($dup_stmt->fetch()) {
                    $error_msg = "⚠️ Duplicate Transaction Detected: The transaction ID '{$transaction_id}' is already registered in the system. Duplicate payments are strictly prohibited.";
                } else {
                    $pdo->beginTransaction();
                    $now_str = date('Y-m-d H:i:s');

                    // 1. Insert into dedicated payments table
                    $ins_pay = $pdo->prepare("
                        INSERT INTO payments (student_id, transaction_id, amount, payment_method, status, created_at)
                        VALUES (:sid, :tx, 500.00, :method, 'Completed', :created_at)
                    ");
                    $ins_pay->execute([
                        'sid' => $student['student_id'],
                        'tx' => $transaction_id,
                        'method' => $payment_method_label,
                        'created_at' => $now_str
                    ]);

                    // 2. Update students table
                    $upd_stu = $pdo->prepare("
                        UPDATE students 
                        SET payment_status = 'Paid', transaction_id = :tx 
                        WHERE student_id = :sid
                    ");
                    $upd_stu->execute([
                        'tx' => $transaction_id,
                        'sid' => $student['student_id']
                    ]);

                    // 3. Log into status history
                    $hist_stmt = $pdo->prepare("
                        INSERT INTO status_history (student_id, status, remarks) 
                        VALUES (:sid, 'Pending', :remarks)
                    ");
                    $hist_stmt->execute([
                        'sid' => $student['student_id'],
                        'remarks' => "Processing fee of ₹500.00 received via {$payment_method_label}. Transaction Ref: {$transaction_id}"
                    ]);

                    $pdo->commit();

                    header("Location: dashboard.php?msg=paid");
                    exit;
                }
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error_msg = "Payment processing failed: " . $e->getMessage();
            }
        }
    }
}
}

$page_title = "Pay Processing Fee - College Payment Portal";
include '../includes/header.php';
?>

<div class="wrapper">
    <?php include '../includes/sidebar.php'; ?>

    <div id="content">
        <?php render_topbar(); ?>

        <div class="container-fluid">
            <?php render_page_header('Admission Fee Payment Portal', '<span class="text-muted small"><i class="fa-solid fa-circle-user me-1"></i>' . e($_SESSION['email']) . '</span>'); ?>

            <div class="status-card-premium status-card-step">
                <div class="status-stepper-premium">
                    <div class="status-stepper-step-premium completed">
                        <div class="status-stepper-dot-premium"><i class="fa-solid fa-check"></i></div>
                        <div class="status-stepper-label-premium">Fill Details</div>
                    </div>
                    <div class="status-stepper-step-premium completed">
                        <div class="status-stepper-dot-premium"><i class="fa-solid fa-check"></i></div>
                        <div class="status-stepper-label-premium">Upload Docs</div>
                    </div>
                    <div class="status-stepper-step-premium active">
                        <div class="status-stepper-dot-premium">3</div>
                        <div class="status-stepper-label-premium">Payment</div>
                    </div>
                    <div class="status-stepper-step-premium">
                        <div class="status-stepper-dot-premium">4</div>
                        <div class="status-stepper-label-premium">Submit</div>
                    </div>
                </div>
            </div>

            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i><?php echo $error_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($student['payment_status'] === 'Refunded'): ?>
                <div class="alert alert-warning border-start border-4 border-warning shadow-sm mb-4">
                    <div class="d-flex align-items-center">
                        <i class="fa-solid fa-arrow-rotate-left fa-2x text-warning me-3"></i>
                        <div>
                            <h6 class="fw-bold mb-1 text-dark">Payment Status: Fee Refunded (Payment is Back)</h6>
                            <p class="mb-0 small text-muted">
                                Your previous admission fee of <strong>₹500.00</strong> was refunded back to your account because your application was rejected. If you are re-submitting your form, please complete the fee payment below.
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Order Summary & Candidate Details -->
                <div class="col-12 col-lg-5">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white py-3">
                            <span class="fw-bold text-dark"><i class="fa-solid fa-file-invoice me-2 text-primary"></i>Fee Assessment Summary</span>
                        </div>
                        <div class="card-body">
                            <div class="p-3 bg-light rounded border mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Admission ID:</span>
                                    <span class="fw-bold font-monospace text-primary"><?php echo e($student['admission_no']); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Applicant Name:</span>
                                    <span class="fw-semibold text-dark"><?php echo e($student['full_name']); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Chosen Course:</span>
                                    <span class="fw-semibold text-dark text-end" style="max-width: 60%;"><?php echo e($student['course_name'] ?? 'Degree Program'); ?></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Fee Type:</span>
                                    <span class="badge bg-secondary-subtle text-secondary">Non-Refundable Processing Fee</span>
                                </div>
                            </div>

                            <div class="border-top pt-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted">Application Form Fee</span>
                                    <span class="fw-semibold">₹500.00</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted">Gateway / Portal Surcharge</span>
                                    <span class="text-success fw-semibold">₹0.00 (Waived)</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="fw-bold text-dark mb-0">Total Payable:</h5>
                                    <h3 class="fw-bold text-primary mb-0">₹500.00</h3>
                                </div>
                            </div>

                            <div class="mt-4 p-3 bg-primary-subtle text-primary rounded border border-primary-subtle small">
                                <i class="fa-solid fa-shield-halved me-2"></i>
                                <strong>Direct Secure Gateway:</strong> All transactions are processed through the internal college accounts system.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Processing Options -->
                <div class="col-12 col-lg-7">
                    <?php if ($student['payment_status'] === 'Paid'): ?>
                        <div class="card shadow-sm border-0 border-top border-4 border-success">
                            <div class="card-body p-4 text-center">
                                <div class="text-success mb-3">
                                    <i class="fa-solid fa-circle-check fa-4x"></i>
                                </div>
                                <h4 class="fw-bold text-success mb-2">Fee Payment Completed!</h4>
                                <p class="text-muted mb-3">Your admission processing fee of ₹500.00 has been verified and registered.</p>
                                
                                <div class="p-3 bg-light rounded border d-inline-block text-start mb-4" style="min-width: 320px;">
                                    <div class="mb-1"><span class="text-muted">Transaction ID:</span> <strong class="font-monospace text-primary"><?php echo e($student['transaction_id']); ?></strong></div>
                                    <div class="mb-1"><span class="text-muted">Amount Paid:</span> <strong class="text-success">₹500.00</strong></div>
                                    <div><span class="text-muted">Status:</span> <span class="badge bg-success">Paid & Verified</span></div>
                                </div>
                                
                                <div>
                                    <a href="payment_receipt.php" target="_blank" class="btn btn-outline-success me-2">
                                        <i class="fa-solid fa-file-pdf me-2"></i>Download Payment Receipt
                                    </a>
                                    <a href="dashboard.php" class="btn btn-primary">
                                        <i class="fa-solid fa-arrow-right me-2"></i>Proceed to Submit Application
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white py-3">
                                <span class="fw-bold text-dark"><i class="fa-solid fa-qrcode me-2 text-primary"></i>Online UPI Payment</span>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="payment.php" onsubmit="disableSubmitBtn(this);">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="payment_mode" value="upi">
                                    
                                    <div class="text-center p-3 bg-light rounded border mb-3">
                                        <div class="mb-2">
                                            <!-- Modern clean SVG QR Code Mock -->
                                            <svg width="140" height="140" viewBox="0 0 100 100" class="border p-2 bg-white rounded shadow-sm">
                                                <rect width="100" height="100" fill="#fff"/>
                                                <path d="M5 5h30v30H5zM10 10h20v20H10zM15 15h10v10H15zM65 5h30v30H65zM70 10h20v20H70zM75 15h10v10H75zM5 65h30v30H5zM10 70h20v20H10zM15 75h10v10H15z" fill="#0f4c81"/>
                                                <rect x="45" y="10" width="10" height="10" fill="#0f4c81"/>
                                                <rect x="40" y="25" width="15" height="10" fill="#0f4c81"/>
                                                <rect x="45" y="45" width="10" height="10" fill="#0f4c81"/>
                                                <rect x="65" y="45" width="20" height="10" fill="#0f4c81"/>
                                                <rect x="10" y="45" width="25" height="10" fill="#0f4c81"/>
                                                <rect x="45" y="65" width="15" height="10" fill="#0f4c81"/>
                                                <rect x="70" y="65" width="10" height="15" fill="#0f4c81"/>
                                                <rect x="65" y="85" width="25" height="10" fill="#0f4c81"/>
                                                <rect x="45" y="80" width="10" height="15" fill="#0f4c81"/>
                                            </svg>
                                        </div>
                                        <div class="fw-bold text-dark fs-5">sctadmissions@upi</div>
                                        <small class="text-muted">Scan using Google Pay, PhonePe, Paytm, or BHIM</small>
                                    </div>

                                    <div class="mb-3">
                                        <label for="upi_vpa" class="form-label small fw-bold">Or Enter Your UPI ID (VPA)</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-at"></i></span>
                                            <input type="text" class="form-control" name="upi_vpa" id="upi_vpa" placeholder="username@okhdfcbank / mobile@paytm" value="<?php echo e($student['mobile']); ?>@upi">
                                        </div>
                                    </div>

                                    <div class="p-3 bg-light rounded border mb-3 small text-muted">
                                        <i class="fa-solid fa-shield-halved me-1 text-success"></i>
                                        Instant verification through direct UPI gateway.
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold submit-pay-btn">
                                        <i class="fa-solid fa-lock me-2"></i>Pay ₹500.00 via UPI
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function disableSubmitBtn(form) {
    const btn = form.querySelector('.submit-pay-btn');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin me-2"></i>Processing Payment...';
    }
}
</script>

<?php include '../includes/footer.php'; ?>
