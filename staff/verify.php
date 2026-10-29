<?php
require_once '../includes/db_connect.php';
require_once '../includes/auth.php';


check_access('staff');

$error_msg = "";
$success_msg = "";


if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: dashboard.php");
    exit;
}

$student_id = intval($_GET['id']);
$student = null;
$documents = null;

try {

    $stmt = $pdo->prepare("
        SELECT s.*, c.course_name, c.department, c.semester 
        FROM students s 
        LEFT JOIN courses c ON s.course_id = c.course_id 
        WHERE s.student_id = :student_id AND s.is_submitted = 1
    ");
    $stmt->execute(['student_id' => $student_id]);
    $student = $stmt->fetch();

    if (!$student) {

        header("Location: dashboard.php");
        exit;
    }


    $doc_stmt = $pdo->prepare("SELECT * FROM documents WHERE student_id = :student_id");
    $doc_stmt->execute(['student_id' => $student_id]);
    $documents = $doc_stmt->fetch();
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validate_csrf()) {
        $error_msg = "Security validation failed. Please refresh the page and try again.";
    } else {
        $action = $_POST['action'];
        $remarks = isset($_POST['remarks']) ? mb_substr(trim($_POST['remarks']), 0, 1000) : '';

    if ($action === 'approve') {
        try {
            if (empty($student['course_id'])) {
                $error_msg = "Cannot approve student: No course assigned to this student.";
            } else {
                // Get total seats for this student's course
                $stmt_seats = $pdo->prepare("SELECT total_seats FROM courses WHERE course_id = :course_id");
                $stmt_seats->execute(['course_id' => $student['course_id']]);
                $total_seats = (int) $stmt_seats->fetchColumn();

                // Count already approved students for this course
                $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM students WHERE course_id = :course_id AND status = 'Approved'");
                $stmt_count->execute(['course_id' => $student['course_id']]);
                $approved_count = (int) $stmt_count->fetchColumn();

                if ($total_seats > 0 && $approved_count >= $total_seats) {
                    $error_msg = "Cannot approve student: No vacant seats available in this course.";
                } else {
                    $pdo->beginTransaction();

                    $update_stmt = $pdo->prepare("UPDATE students SET status = 'Approved' WHERE student_id = :student_id");
                    $update_stmt->execute(['student_id' => $student_id]);

                    $hist_stmt = $pdo->prepare("INSERT INTO status_history (student_id, status, remarks) VALUES (:student_id, 'Approved', :remarks)");
                    $hist_stmt->execute([
                        'student_id' => $student_id,
                        'remarks' => !empty($remarks) ? $remarks : mb_substr("Verified and approved by staff member: " . $_SESSION['name'], 0, 175)
                    ]);

                    $pdo->commit();

                    header("Location: dashboard.php?msg=approved");
                    exit;
                }
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error_msg = "Transaction failed: " . $e->getMessage();
        }
    } elseif ($action === 'reject') {

        if (empty($remarks)) {
            $error_msg = "Remarks are mandatory when rejecting an application.";
        } else {
            try {
                $pdo->beginTransaction();


                // When application is rejected, also set payment_status to 'Refunded' (Payment is back)
                $update_stmt = $pdo->prepare("UPDATE students SET status = 'Rejected', payment_status = 'Refunded', is_submitted = 0 WHERE student_id = :student_id");
                $update_stmt->execute(['student_id' => $student_id]);

                // Update payments table to mark status as Refunded
                $now_str = date('Y-m-d H:i:s');
                $pay_stmt = $pdo->prepare("UPDATE payments SET status = 'Refunded', refund_reason = :reason, refunded_at = :ref_at WHERE student_id = :student_id AND status = 'Completed'");
                $pay_stmt->execute([
                    'reason' => 'Form Rejected by Staff: ' . $remarks,
                    'ref_at' => $now_str,
                    'student_id' => $student_id
                ]);

                $hist_remarks = $remarks . " [Payment Status: Fee refunded / Payment is back to source account]";
                $hist_stmt = $pdo->prepare("INSERT INTO status_history (student_id, status, remarks) VALUES (:student_id, 'Rejected', :remarks)");
                $hist_stmt->execute([
                    'student_id' => $student_id,
                    'remarks' => $hist_remarks
                ]);

                $pdo->commit();
                $success_msg = "Application has been rejected and student notified.";

                header("Location: dashboard.php?msg=rejected");
                exit;
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error_msg = "Transaction failed: " . $e->getMessage();
            }
        }
    }
}
}

$page_title = "Verify Student Application";
include '../includes/header.php';
?>

<div class="wrapper">

    <?php include '../includes/sidebar.php'; ?>


    <div id="content">
        <?php render_topbar(); ?>

        <div class="container-fluid">
            <?php render_page_header('Verification Desk', '<a href="dashboard.php" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Back to Applicants</a>'); ?>

            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success" role="alert">
                    <i class="fa-solid fa-circle-check me-2"></i><?php echo $success_msg; ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i><?php echo $error_msg; ?>
                </div>
            <?php endif; ?>

            <div class="row">

                <div class="col-lg-6">

                    <div class="card">
                        <div class="card-header">
                            <i class="fa-solid fa-address-card me-2"></i>Applicant Details Preview
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <?php if ($documents && !empty($documents['photo'])): ?>
                                    <img src="../uploads/photo/<?php echo e($documents['photo']); ?>"
                                        alt="<?php echo e($student['full_name']); ?>"
                                        class="border border-3 border-primary shadow-sm rounded"
                                        style="width: 120px; height: 120px; object-fit: cover;"
                                        onerror="this.style.display='none'; document.getElementById('staffPhotoFallback').classList.remove('d-none'); document.getElementById('staffPhotoFallback').classList.add('d-inline-flex');">
                                    <div id="staffPhotoFallback" class="border border-3 border-secondary bg-light d-none align-items-center justify-content-center rounded" 
                                         style="width: 120px; height: 120px;">
                                        <i class="fa-solid fa-user-large fa-3x text-muted"></i>
                                    </div>
                                <?php else: ?>
                                    <div class="border border-3 border-secondary bg-light d-inline-flex align-items-center justify-content-center rounded"
                                        style="width: 120px; height: 120px;">
                                        <i class="fa-solid fa-user-large fa-3x text-muted"></i>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="row mb-4 p-3 bg-light rounded border border-light-subtle">
                                <div class="col-md-6">
                                    <small class="text-muted block">Admission ID</small>
                                    <div class="fw-bold fs-5 text-primary"><?php echo e($student['admission_no']); ?></div>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted block">Current Decision</small>
                                    <div>
                                        <?php if ($student['status'] === 'Pending'): ?>
                                            <span class="badge badge-pending">Pending Verification</span>
                                        <?php elseif ($student['status'] === 'Approved'): ?>
                                            <span class="badge badge-approved">Approved</span>
                                        <?php elseif ($student['status'] === 'Rejected'): ?>
                                            <span class="badge badge-rejected">Rejected</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>


                            <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">Personal Details</h6>
                            <div class="row g-2 mb-4">
                                <div class="col-md-12"><strong>Student Full Name:</strong> <?php echo e($student['full_name']); ?></div>
                                <div class="col-md-6"><strong>Father's Name:</strong> <?php echo e($student['father_name']); ?></div>
                                <div class="col-md-6"><strong>Mother's Name:</strong> <?php echo e($student['mother_name']); ?></div>
                                <div class="col-md-6"><strong>Gender:</strong> <?php echo e($student['gender']); ?></div>
                                <div class="col-md-6"><strong>DOB:</strong> <?php echo date('d-M-Y', strtotime($student['dob'])); ?></div>
                                <div class="col-md-6"><strong>Category:</strong> <?php echo e($student['category']); ?></div>
                                <div class="col-md-6"><strong>Mobile:</strong> <?php echo e($student['mobile']); ?></div>
                                <div class="col-md-6"><strong>Email:</strong> <?php echo e($student['email']); ?></div>
                                <div class="col-md-12"><strong>Address:</strong> <?php echo e($student['address']) . ", " . e($student['city']) . ", " . e($student['state']) . " - " . e($student['pincode']); ?></div>
                            </div>


                            <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">Academic Scorecards</h6>
                            <div class="row g-2 mb-4">
                                <div class="col-md-6"><strong>10th Percentage:</strong> <?php echo e($student['tenth_percentage']); ?>%</div>
                                <div class="col-md-6"><strong>12th Percentage:</strong> <span class="fw-bold <?php echo ($student['twelfth_percentage'] >= 35) ? 'text-success' : 'text-danger'; ?>"><?php echo e($student['twelfth_percentage']); ?>%</span></div>
                                <div class="col-md-6"><strong>Previous School:</strong> <?php echo e($student['school_name']); ?></div>
                                <div class="col-md-6"><strong>Passing Year:</strong> <?php echo e($student['passing_year']); ?></div>
                            </div>


                            <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">Course Preferred</h6>
                            <div class="row g-2 mb-4">
                                <div class="col-md-12"><strong>Program:</strong> <?php echo e($student['course_name']); ?></div>
                                <div class="col-md-6"><strong>Department:</strong> <?php echo e($student['department']); ?></div>
                                <div class="col-md-6"><strong>Semester:</strong> <?php echo e($student['semester']); ?></div>
                            </div>


                            <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">Processing Fee Payment</h6>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <strong>Status:</strong>
                                    <?php if ($student['payment_status'] === 'Paid'): ?>
                                        <span class="badge bg-success-subtle text-success font-weight-bold px-2 py-1"><i class="fa-solid fa-circle-check me-1"></i>Paid</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger font-weight-bold px-2 py-1"><i class="fa-solid fa-circle-xmark me-1"></i>Unpaid</span>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <strong>UPI Transaction Ref:</strong>
                                    <span class="font-monospace text-dark fw-bold"><?php echo e($student['transaction_id'] ? $student['transaction_id'] : 'N/A'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="col-lg-6">

                    <div class="card">
                        <div class="card-header">
                            <i class="fa-solid fa-folder-open me-2"></i>Document Verification Checklist
                        </div>
                        <div class="card-body">
                            <?php if (!$documents): ?>
                                <div class="alert alert-danger" role="alert">
                                    <i class="fa-solid fa-triangle-exclamation me-2"></i>No documents uploaded yet by this applicant.
                                </div>
                            <?php else: ?>
                                <ul class="list-group list-group-flush mb-4">

                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                                        <div>
                                            <i class="fa-regular fa-image text-primary me-2"></i><strong>Candidate Photo</strong>
                                        </div>
                                        <?php if (!empty($documents['photo'])): ?>
                                            <a href="../uploads/photo/<?php echo e($documents['photo']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fa-solid fa-eye me-1"></i>View Document
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Not Uploaded</span>
                                        <?php endif; ?>
                                    </li>


                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                                        <div>
                                            <i class="fa-regular fa-file-pdf text-danger me-2"></i><strong>10th Marksheet</strong>
                                        </div>
                                        <?php if (!empty($documents['marksheet10'])): ?>
                                            <a href="../uploads/marksheet10/<?php echo e($documents['marksheet10']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fa-solid fa-eye me-1"></i>View Document
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Not Uploaded</span>
                                        <?php endif; ?>
                                    </li>


                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                                        <div>
                                            <i class="fa-regular fa-file-pdf text-danger me-2"></i><strong>12th Marksheet</strong>
                                        </div>
                                        <?php if (!empty($documents['marksheet12'])): ?>
                                            <a href="../uploads/marksheet12/<?php echo e($documents['marksheet12']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fa-solid fa-eye me-1"></i>View Document
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Not Uploaded</span>
                                        <?php endif; ?>
                                    </li>


                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                                        <div>
                                            <i class="fa-regular fa-file-word text-info me-2"></i><strong>Leaving Certificate</strong>
                                        </div>
                                        <?php if (!empty($documents['leaving_certificate'])): ?>
                                            <a href="../uploads/leaving_certificate/<?php echo e($documents['leaving_certificate']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fa-solid fa-eye me-1"></i>View Document
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Not Uploaded</span>
                                        <?php endif; ?>
                                    </li>


                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                                        <div>
                                            <i class="fa-regular fa-address-card text-success me-2"></i><strong>Aadhaar Card</strong>
                                        </div>
                                        <?php if (!empty($documents['aadhaar'])): ?>
                                            <a href="../uploads/aadhaar/<?php echo e($documents['aadhaar']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fa-solid fa-eye me-1"></i>View Document
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Not Uploaded</span>
                                        <?php endif; ?>
                                    </li>
                                </ul>
                            <?php endif; ?>


                            <?php if ($student['status'] === 'Pending'): ?>
                                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mt-4">
                                    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                                        <h5 class="fw-bold mb-0 text-dark">
                                            <i class="fa-solid fa-gavel me-2 text-primary"></i>Verification Action
                                        </h5>
                                        <span class="badge bg-warning text-dark px-2.5 py-1 rounded-pill small">
                                            <i class="fa-solid fa-clock me-1"></i>Awaiting Decision
                                        </span>
                                    </div>
                                    <div class="card-body p-4 bg-light bg-opacity-50">
                                        <form action="verify.php?id=<?php echo $student_id; ?>" method="POST" id="verifyForm">
                                            <?php echo csrf_field(); ?>
                                            <div class="mb-3">
                                                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                                                    <label for="remarks" class="form-label mb-0 fw-bold text-dark small">
                                                        Review Remarks / Rejection Feedback
                                                    </label>
                                                    <div class="d-flex align-items-center gap-1 flex-wrap">
                                                        <span class="small text-muted me-1">Insert template:</span>
                                                        <button type="button" class="btn btn-sm btn-outline-success py-1 px-2.5 rounded-pill fw-semibold" id="btnTemplateApprove" title="Load approval template & mute reject button">
                                                            <i class="fa-solid fa-circle-check me-1"></i>Approval
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2.5 rounded-pill fw-semibold" id="btnTemplateReject" title="Load rejection template & mute approve button">
                                                            <i class="fa-solid fa-circle-xmark me-1"></i>Rejection
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 rounded-pill fw-semibold" id="btnTemplateReset" title="Reset remarks & restore both buttons">
                                                            <i class="fa-solid fa-rotate-left"></i>
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Single Clean Textarea -->
                                                <textarea class="form-control rounded-3" 
                                                          id="remarks" 
                                                          name="remarks" 
                                                          rows="5" 
                                                          maxlength="1000" 
                                                          placeholder="Enter feedback or remarks here... Required when rejecting an application."></textarea>
                                                
                                                <div class="d-flex justify-content-between align-items-center mt-1.5 px-1 flex-wrap gap-2">
                                                    <small class="text-muted font-monospace"><span id="charCount">0</span>/1000</small>
                                                </div>
                                            </div>

                                            <div class="row g-2 pt-2">
                                                <div class="col-md-6">
                                                    <button type="submit" name="action" value="reject" id="btnSubmitReject" class="btn btn-danger w-100 py-2.5 fw-bold rounded-3 shadow-sm transition-all" onclick="return confirmReject();">
                                                        <i class="fa-solid fa-circle-xmark me-1"></i>Reject Application
                                                    </button>
                                                </div>
                                                <div class="col-md-6">
                                                    <button type="submit" name="action" value="approve" id="btnSubmitApprove" class="btn btn-success w-100 py-2.5 fw-bold rounded-3 shadow-sm transition-all" onclick="return confirm('Are you sure you want to approve this application?');">
                                                        <i class="fa-solid fa-circle-check me-1"></i>Approve Admission
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-secondary text-center rounded-3 mt-4" role="alert">
                                    <i class="fa-solid fa-lock me-2"></i>This application has already been processed (Decision: <strong><?php echo $student['status']; ?></strong>).
                                </div>
                            <?php endif; ?>     
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const textarea = document.getElementById('remarks');
    const charCount = document.getElementById('charCount');
    const btnSubmitApprove = document.getElementById('btnSubmitApprove');
    const btnSubmitReject = document.getElementById('btnSubmitReject');
    const btnApprove = document.getElementById('btnTemplateApprove');
    const btnReject = document.getElementById('btnTemplateReject');
    const btnReset = document.getElementById('btnTemplateReset');
    const actionNotice = document.getElementById('activeActionNotice');

    function updateCounter() {
        if (textarea && charCount) {
            charCount.textContent = textarea.value.length;
        }
    }

    // Set interactive mode: 'approve', 'reject', or 'both'
    function setActionMode(mode) {
        if (mode === 'approve') {
            // Mute Reject button at bottom
            if (btnSubmitReject) {
                btnSubmitReject.disabled = true;
                btnSubmitReject.classList.add('opacity-25', 'pe-none');
                btnSubmitReject.style.filter = 'grayscale(1)';
            }
            // Enable and highlight Approve button at bottom
            if (btnSubmitApprove) {
                btnSubmitApprove.disabled = false;
                btnSubmitApprove.classList.remove('opacity-25', 'pe-none');
                btnSubmitApprove.style.filter = 'none';
            }

            // Top template pills
            if (btnApprove) {
                btnApprove.classList.remove('btn-outline-success');
                btnApprove.classList.add('btn-success', 'text-white');
            }
            if (btnReject) {
                btnReject.classList.remove('btn-danger', 'text-white');
                btnReject.classList.add('btn-outline-danger');
            }

            if (actionNotice) {
                actionNotice.innerHTML = '<span class="text-success fw-bold"><i class="fa-solid fa-circle-check me-1"></i>Approval Mode Active</span> &bull; <span class="text-muted">Reject Application button is muted</span>';
            }
        } else if (mode === 'reject') {
            // Mute Approve button at bottom
            if (btnSubmitApprove) {
                btnSubmitApprove.disabled = true;
                btnSubmitApprove.classList.add('opacity-25', 'pe-none');
                btnSubmitApprove.style.filter = 'grayscale(1)';
            }
            // Enable and highlight Reject button at bottom
            if (btnSubmitReject) {
                btnSubmitReject.disabled = false;
                btnSubmitReject.classList.remove('opacity-25', 'pe-none');
                btnSubmitReject.style.filter = 'none';
            }

            // Top template pills
            if (btnReject) {
                btnReject.classList.remove('btn-outline-danger');
                btnReject.classList.add('btn-danger', 'text-white');
            }
            if (btnApprove) {
                btnApprove.classList.remove('btn-success', 'text-white');
                btnApprove.classList.add('btn-outline-success');
            }

            if (actionNotice) {
                actionNotice.innerHTML = '<span class="text-danger fw-bold"><i class="fa-solid fa-circle-xmark me-1"></i>Rejection Mode Active</span> &bull; <span class="text-muted">Approve Admission button is muted</span>';
            }
        } else {
            // Reset both buttons to active
            if (btnSubmitApprove) {
                btnSubmitApprove.disabled = false;
                btnSubmitApprove.classList.remove('opacity-25', 'pe-none');
                btnSubmitApprove.style.filter = 'none';
            }
            if (btnSubmitReject) {
                btnSubmitReject.disabled = false;
                btnSubmitReject.classList.remove('opacity-25', 'pe-none');
                btnSubmitReject.style.filter = 'none';
            }

            if (btnApprove) {
                btnApprove.classList.remove('btn-success', 'text-white');
                btnApprove.classList.add('btn-outline-success');
            }
            if (btnReject) {
                btnReject.classList.remove('btn-danger', 'text-white');
                btnReject.classList.add('btn-outline-danger');
            }

            if (actionNotice) {
                actionNotice.innerHTML = '<i class="fa-solid fa-circle-info me-1"></i>Select a template to auto-fill remarks and lock the matching action.';
            }
        }
    }

    if (textarea) {
        textarea.addEventListener('input', function() {
            updateCounter();
            if (textarea.value.trim() === '') {
                setActionMode('both');
            }
        });
    }

    // Prevent rejection without remarks
    function confirmReject() {
        if (!textarea || textarea.value.trim() === "") {
            alert("You must provide remarks stating the reason for rejection.");
            if (textarea) textarea.focus();
            return false;
        }
        return confirm("Are you sure you want to reject this application?");
    }

    // Auto-populate template buttons with muting logic
    if (btnApprove) {
        btnApprove.addEventListener('click', function() {
            const courseName = <?php echo json_encode($student['course_name']); ?>;
            const staffName = <?php echo json_encode($_SESSION['name']); ?>;
            const staffEmail = <?php echo json_encode($_SESSION['email'] ?? ''); ?>;
            textarea.value = `Your Application Submitted Successfully.\nAdmission Confirm.\n\nThank You,\nConfirm By Faculty of : ${courseName}\nFaculty Name - ${staffName}\nFaculty Email - ${staffEmail}`;
            updateCounter();
            setActionMode('approve');
            textarea.focus();
        });
    }

    if (btnReject) {
        btnReject.addEventListener('click', function() {
            textarea.value = `Admission Rejected due to:\n1. [document name / criteria discrepancy]\n2. [clarification required]\n\nPlease correct and re-submit.\n\nThank You,\nAdmission Desk`;
            updateCounter();
            setActionMode('reject');

            textarea.focus();
            const placeholder = "[document name / criteria discrepancy]";
            const text = textarea.value;
            const startIdx = text.indexOf(placeholder);
            if (startIdx !== -1) {
                textarea.setSelectionRange(startIdx, startIdx + placeholder.length);
            }
        });
    }

    if (btnReset) {
        btnReset.addEventListener('click', function() {
            textarea.value = '';
            updateCounter();
            setActionMode('both');
            textarea.focus();
        });
    }
</script>

<?php include '../includes/footer.php'; ?>