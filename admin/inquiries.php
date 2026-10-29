<?php
require_once '../includes/db_connect.php';
require_once '../includes/auth.php';

check_access('admin');

$error_msg = "";
$success_msg = "";

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$course_filter = isset($_GET['course_filter']) ? trim($_GET['course_filter']) : '';

// Handle Delete Inquiry & Student
if (isset($_GET['delete_id']) && !empty($_GET['delete_id'])) {
    if (!validate_csrf($_GET['csrf_token'] ?? '')) {
        $error_msg = "Security validation failed for delete request. Please try again.";
    } else {
        $del_inq_id = intval($_GET['delete_id']);
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT inquiry_id, user_id, name, email FROM inquiries WHERE inquiry_id = :id");
            $stmt->execute(['id' => $del_inq_id]);
            $inq_data = $stmt->fetch();

            if ($inq_data) {
                $user_id_del = $inq_data['user_id'];

                if ($user_id_del) {
                    // If a student application record was created, delete documents, status history, and student record
                    $st_stmt = $pdo->prepare("SELECT student_id FROM students WHERE user_id = :uid");
                    $st_stmt->execute(['uid' => $user_id_del]);
                    $st_rows = $st_stmt->fetchAll();

                    foreach ($st_rows as $st) {
                        $sid = $st['student_id'];
                        $d_stmt = $pdo->prepare("SELECT photo, marksheet10, marksheet12, leaving_certificate, aadhaar FROM documents WHERE student_id = :sid");
                        $d_stmt->execute(['sid' => $sid]);
                        $doc_files = $d_stmt->fetch();
                        if ($doc_files) {
                            foreach (['photo', 'marksheet10', 'marksheet12', 'leaving_certificate', 'aadhaar'] as $field) {
                                if (!empty($doc_files[$field])) {
                                    $fpath = "../uploads/" . $field . "/" . basename($doc_files[$field]);
                                    if (file_exists($fpath)) {
                                        @unlink($fpath);
                                    }
                                }
                            }
                        }
                        $pdo->prepare("DELETE FROM payments WHERE student_id = :sid")->execute(['sid' => $sid]);
                        $pdo->prepare("DELETE FROM documents WHERE student_id = :sid")->execute(['sid' => $sid]);
                        $pdo->prepare("DELETE FROM status_history WHERE student_id = :sid")->execute(['sid' => $sid]);
                        $pdo->prepare("DELETE FROM students WHERE student_id = :sid")->execute(['sid' => $sid]);
                    }

                    // Delete inquiry and applicant user account
                    $pdo->prepare("DELETE FROM inquiries WHERE inquiry_id = :id")->execute(['id' => $del_inq_id]);
                    $pdo->prepare("DELETE FROM users WHERE user_id = :uid")->execute(['uid' => $user_id_del]);
                } else {
                    $pdo->prepare("DELETE FROM inquiries WHERE inquiry_id = :id")->execute(['id' => $del_inq_id]);
                }

                $pdo->commit();
                $success_msg = "Inquiry applicant '{$inq_data['name']}' ({$inq_data['email']}) deleted successfully.";
            } else {
                $pdo->rollBack();
                $error_msg = "Inquiry record not found.";
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error_msg = "Failed to delete inquiry: " . $e->getMessage();
        }
    }
}

// Handle Status update
if (isset($_POST['update_status']) && isset($_POST['inquiry_id'])) {
    if (!validate_csrf()) {
        $error_msg = "Security validation failed. Please try again.";
    } else {
        $inq_id = intval($_POST['inquiry_id']);
        $new_status = trim($_POST['status'] ?? 'New');
        $allowed_statuses = ['New', 'Contacted', 'Resolved', 'Closed'];
        if (!in_array($new_status, $allowed_statuses, true)) {
            $new_status = 'New';
        }
        try {
            $upd = $pdo->prepare("UPDATE inquiries SET status = :status WHERE inquiry_id = :id");
            $upd->execute(['status' => $new_status, 'id' => $inq_id]);
            $success_msg = "Inquiry status updated successfully.";
        } catch (PDOException $e) {
            $error_msg = "Failed to update status: " . $e->getMessage();
        }
    }
}

// Fetch Courses for filter
$courses = [];
try {
    $c_stmt = $pdo->query("SELECT course_id, course_name FROM courses ORDER BY course_name ASC");
    $courses = $c_stmt->fetchAll();
} catch (PDOException $e) {
}

// Query inquiries
$sql = "
    SELECT i.*, c.course_name, u.role,
           s.student_id, s.admission_no, s.is_submitted, s.status as application_status
    FROM inquiries i
    LEFT JOIN courses c ON i.course_id = c.course_id
    LEFT JOIN users u ON i.user_id = u.user_id
    LEFT JOIN students s ON i.user_id = s.user_id
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (i.name ILIKE :search OR i.email ILIKE :search OR i.mobile ILIKE :search)";
    $params['search'] = "%{$search}%";
}

if (!empty($course_filter)) {
    $sql .= " AND i.course_id = :course_id";
    $params['course_id'] = intval($course_filter);
}

$sql .= " ORDER BY i.inquiry_id DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $inquiries = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_msg = "Database Error: " . $e->getMessage();
    $inquiries = [];
}

$page_title = "Manage Admission Inquiries";
include '../includes/header.php';
?>

<div class="wrapper">
    <?php include '../includes/sidebar.php'; ?>

    <div id="content">
        <?php render_topbar(); ?>

        <div class="container-fluid">
            <?php render_page_header($page_title, '
                <a href="../inquiry.php" target="_blank" class="btn btn-premium-primary btn-sm">
                    <i class="fa-solid fa-plus me-1"></i>New Inquiry Form
                </a>
            '); ?>

            <?php render_alert($error_msg, 'danger', false, true); ?>
            <?php render_alert($success_msg, 'success', false, true); ?>

            <!-- Filter Card -->
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-body">
                    <form method="GET" action="inquiries.php" class="row g-3 align-items-end">
                        <div class="col-12 col-md-5">
                            <label class="form-label small fw-semibold">Search Applicant</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="fa-solid fa-search text-muted"></i></span>
                                <input type="text" name="search" class="form-control" placeholder="Search by name, email, or mobile..." value="<?php echo e($search); ?>">
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-semibold">Course Program</label>
                            <select name="course_filter" class="form-select">
                                <option value="">All Courses</option>
                                <?php foreach ($courses as $c): ?>
                                    <option value="<?php echo $c['course_id']; ?>" <?php echo ($course_filter == $c['course_id']) ? 'selected' : ''; ?>>
                                        <?php echo e($c['course_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                <i class="fa-solid fa-filter me-1"></i>Filter
                            </button>
                            <a href="inquiries.php" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Inquiries Table Card -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="fa-solid fa-clipboard-list text-primary me-2"></i>Total Inquiries: <?php echo count($inquiries); ?>
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-uppercase small text-muted">
                                <tr>
                                    <th>#</th>
                                    <th>Applicant Name</th>
                                    <th>Email Address</th>
                                    <th>Mobile No.</th>
                                    <th>Preferred Course</th>
                                    <th>Admission Status</th>
                                    <th>Inquiry Date</th>
                                    <th>Remarks</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($inquiries)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-5 text-muted">
                                            <i class="fa-solid fa-inbox fs-2 mb-2 d-block"></i>
                                            No inquiries recorded yet.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($inquiries as $idx => $inq): ?>
                                        <tr>
                                            <td class="text-muted fw-bold"><?php echo $idx + 1; ?></td>
                                            <td>
                                                <div class="fw-bold text-dark"><?php echo e($inq['name']); ?></div>
                                                <small class="text-muted">User ID: #<?php echo e($inq['user_id'] ?? 'N/A'); ?></small>
                                            </td>
                                            <td>
                                                <a href="mailto:<?php echo e($inq['email']); ?>" class="text-decoration-none text-primary">
                                                    <?php echo e($inq['email']); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <a href="tel:<?php echo e($inq['mobile']); ?>" class="text-decoration-none text-dark font-monospace">
                                                    <?php echo e($inq['mobile']); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border">
                                                    <?php echo e($inq['course_name'] ?? 'Not Specified'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (!empty($inq['student_id'])): ?>
                                                    <?php if ((int)$inq['is_submitted'] === 1): ?>
                                                        <span class="badge bg-success">
                                                            <i class="fa-solid fa-check me-1"></i>Form Submitted (<?php echo e($inq['application_status']); ?>)
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning text-dark">
                                                            <i class="fa-solid fa-pen me-1"></i>Form in Progress
                                                        </span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">
                                                        <i class="fa-solid fa-clock me-1"></i>Inquiry Only
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="small text-muted font-monospace">
                                                <?php echo e(substr($inq['created_at'], 0, 16)); ?>
                                            </td>
                                            <td class="small text-muted" style="max-width: 200px;">
                                                <?php echo e($inq['message'] ?: '-'); ?>
                                            </td>
                                            <td class="text-end">
                                                <a href="inquiries.php?delete_id=<?php echo $inq['inquiry_id']; ?>&csrf_token=<?php echo csrf_token(); ?>" 
                                                   class="btn btn-sm btn-outline-danger" 
                                                   onclick="return confirm('Are you sure you want to delete inquiry for <?php echo addslashes(e($inq['name'])); ?>? This will also remove their associated student login account.');" 
                                                   title="Delete Inquiry & Account">
                                                    <i class="fa-solid fa-trash-can me-1"></i>Delete
                                                </a>
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

<?php include '../includes/footer.php'; ?>
