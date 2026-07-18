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
    $action = $_POST['action'];
    $remarks = isset($_POST['remarks']) ? trim($_POST['remarks']) : '';
    
    if ($action === 'approve') {
        try {
            // Get total seats for this student's course
            $stmt_seats = $pdo->prepare("SELECT total_seats FROM courses WHERE course_id = :course_id");
            $stmt_seats->execute(['course_id' => $student['course_id']]);
            $total_seats = $stmt_seats->fetchColumn();
            
            // Count already approved students for this course
            $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM students WHERE course_id = :course_id AND status = 'Approved'");
            $stmt_count->execute(['course_id' => $student['course_id']]);
            $approved_count = $stmt_count->fetchColumn();
            
            if ($approved_count >= $total_seats) {
                $error_msg = "Cannot approve student: No vacant seats available in this course.";
            } else {
                $pdo->beginTransaction();
                
                $update_stmt = $pdo->prepare("UPDATE students SET status = 'Approved' WHERE student_id = :student_id");
                $update_stmt->execute(['student_id' => $student_id]);
                
                $hist_stmt = $pdo->prepare("INSERT INTO status_history (student_id, status, remarks) VALUES (:student_id, 'Approved', :remarks)");
                $hist_stmt->execute([
                    'student_id' => $student_id,
                    'remarks' => !empty($remarks) ? $remarks : "Verified and approved by staff member: " . $_SESSION['name']
                ]);
                
                $pdo->commit();
                
                header("Location: dashboard.php?msg=approved");
                exit;
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
                
                
                $update_stmt = $pdo->prepare("UPDATE students SET status = 'Rejected', is_submitted = 0 WHERE student_id = :student_id");
                $update_stmt->execute(['student_id' => $student_id]);
                
                
                $hist_stmt = $pdo->prepare("INSERT INTO status_history (student_id, status, remarks) VALUES (:student_id, 'Rejected', :remarks)");
                $hist_stmt->execute([
                    'student_id' => $student_id,
                    'remarks' => $remarks
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

$page_title = "Verify Student Application";
include '../includes/header.php';
?>

<div class="wrapper">
    
    <?php include '../includes/sidebar.php'; ?>

    
    <div id="content">
        <?php render_topbar('Verification Desk', '<a href="dashboard.php" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Back to Applicants</a>'); ?>

        <div class="container-fluid">
            
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
                                <div class="bg-light p-4 rounded border">
                                    <h5 class="fw-bold mb-3"><i class="fa-solid fa-gavel me-2"></i>Verification Action</h5>
                                    
                                    <form action="verify.php?id=<?php echo $student_id; ?>" method="POST" id="verifyForm">
                                        
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <label for="remarks" class="form-label mb-0 fw-semibold">Review Remarks / Reason for Rejection</label>
                                                <div class="btn-group" role="group" aria-label="Template shortcuts">
                                                    <button type="button" class="btn btn-outline-success btn-sm px-2 py-0.5" style="font-size: 0.75rem;" id="btnTemplateApprove">
                                                        <i class="fa-solid fa-circle-check me-1"></i>Approve
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger btn-sm px-2 py-0.5" style="font-size: 0.75rem;" id="btnTemplateReject">
                                                        <i class="fa-solid fa-circle-xmark me-1"></i>Reject
                                                    </button>
                                                </div>
                                            </div>
                                            <textarea class="form-control" id="remarks" name="remarks" rows="6" placeholder="Enter feedback here... Required for rejections."></textarea>
                                        </div>

                                        <div class="row g-2">
                                            
                                            <div class="col-md-6">
                                                <button type="submit" name="action" value="reject" class="btn btn-danger w-100 py-2 fw-bold" onclick="return confirmReject();">
                                                    <i class="fa-solid fa-circle-xmark me-1"></i>Reject Application
                                                </button>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <button type="submit" name="action" value="approve" class="btn btn-success w-100 py-2 fw-bold" onclick="return confirm('Are you sure you want to approve this application?');">
                                                    <i class="fa-solid fa-circle-check me-1"></i>Approve Admission
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-secondary text-center" role="alert">
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
// Prevent rejection without remarks
function confirmReject() {
    const remarks = document.getElementById('remarks').value.trim();
    if (remarks === "") {
        alert("You must provide remarks stating the reason for rejection.");
        return false;
    }
    return confirm("Are you sure you want to reject this application?");
}

// Auto-populate template buttons
document.getElementById('btnTemplateApprove').addEventListener('click', function() {
    const courseName = <?php echo json_encode($student['course_name']); ?>;
    const staffName = <?php echo json_encode($_SESSION['name']); ?>;
    const textarea = document.getElementById('remarks');
    textarea.value = `Your 📑Application Submitted Successfully.\nAdmission Confirm ✔.\n\nThank You,\nConfirm By Faculty of : ${courseName}\nFaculty Name - ${staffName}`;
});

document.getElementById('btnTemplateReject').addEventListener('click', function() {
    const textarea = document.getElementById('remarks');
    textarea.value = `Admission Not Confirm ❌.\nYour Application has been Rejected due to discrepancy in\n 1. [rejected document name].\n 2. [rejected Fillup name/Email/Number].\n\nPlease correct the details or re-upload clear documents to submit again.\n\nThank You,\nAdmission Desk`;
    
    // Focus the textarea and highlight the placeholder to be replaced
    textarea.focus();
    const placeholder = "[rejected document name]";
    const text = textarea.value;
    const startIdx = text.indexOf(placeholder);
    if (startIdx !== -1) {
        textarea.setSelectionRange(startIdx, startIdx + placeholder.length);
    }
});
</script>

<?php include '../includes/footer.php'; ?>

