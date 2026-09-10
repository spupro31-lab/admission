<?php
require_once '../includes/db_connect.php';
require_once '../includes/auth.php';


check_access('admin');

$error_msg = "";
$success_msg = "";

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status_filter']) ? trim($_GET['status_filter']) : '';
$course_filter = isset($_GET['course_filter']) ? trim($_GET['course_filter']) : '';
$payment_filter = isset($_GET['payment_filter']) ? trim($_GET['payment_filter']) : '';
$sort_by = isset($_GET['sort_by']) ? trim($_GET['sort_by']) : 'newest';

if (isset($_GET['delete_id']) && !empty($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    try {
        $pdo->beginTransaction();


        $stmt = $pdo->prepare("
            SELECT s.user_id, s.student_id, d.photo, d.marksheet10, d.marksheet12, d.leaving_certificate, d.aadhaar 
            FROM students s 
            LEFT JOIN documents d ON s.student_id = d.student_id 
            WHERE s.student_id = :id
        ");
        $stmt->execute(['id' => $delete_id]);
        $data = $stmt->fetch();

        if ($data) {
            $user_id_to_del = $data['user_id'];


            $file_fields = ['photo', 'marksheet10', 'marksheet12', 'leaving_certificate', 'aadhaar'];
            foreach ($file_fields as $field) {
                if (!empty($data[$field])) {
                    $file_path = "../uploads/" . $field . "/" . basename($data[$field]);
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                }
            }

            // Explicitly delete child records to avoid relying on CASCADE
            $del_docs = $pdo->prepare("DELETE FROM documents WHERE student_id = :sid");
            $del_docs->execute(['sid' => $delete_id]);

            $del_hist = $pdo->prepare("DELETE FROM status_history WHERE student_id = :sid");
            $del_hist->execute(['sid' => $delete_id]);

            $del_student = $pdo->prepare("DELETE FROM students WHERE student_id = :sid");
            $del_student->execute(['sid' => $delete_id]);

            $del_user = $pdo->prepare("DELETE FROM users WHERE user_id = :uid");
            $del_user->execute(['uid' => $user_id_to_del]);

            $pdo->commit();
            $success_msg = "Student and all linked account records/files deleted successfully.";
        } else {
            $pdo->rollBack();
            $error_msg = "Student record not found.";
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error_msg = "Failed to delete student: " . $e->getMessage();
    }
}


$courses = $pdo->query("SELECT * FROM courses ORDER BY course_name ASC")->fetchAll();


try {
    $list_sql = "
        SELECT s.*, c.course_name 
        FROM students s 
        LEFT JOIN courses c ON s.course_id = c.course_id
        WHERE 1=1
    ";
    $list_params = [];

    if (!empty($search)) {
        $list_sql .= " AND (s.admission_no LIKE :search1 
                      OR s.full_name LIKE :search2 
                      OR CAST(s.mobile AS TEXT) LIKE :search3)";
        $list_params['search1'] = "%$search%";
        $list_params['search2'] = "%$search%";
        $list_params['search3'] = "%$search%";
    }

    if (!empty($status_filter)) {
        $list_sql .= " AND s.status = :status_filter";
        $list_params['status_filter'] = $status_filter;
    }

    if (!empty($course_filter)) {
        $list_sql .= " AND s.course_id = :course_filter";
        $list_params['course_filter'] = $course_filter;
    }

    if (!empty($payment_filter)) {
        $list_sql .= " AND s.payment_status = :payment_filter";
        $list_params['payment_filter'] = $payment_filter;
    }


    $order_clause = " ORDER BY s.student_id DESC";
    if ($sort_by === 'oldest') {
        $order_clause = " ORDER BY s.student_id ASC";
    } elseif ($sort_by === 'pct_high') {
        $order_clause = " ORDER BY s.twelfth_percentage DESC";
    } elseif ($sort_by === 'pct_low') {
        $order_clause = " ORDER BY s.twelfth_percentage ASC";
    } elseif ($sort_by === 'name_asc') {
        $order_clause = " ORDER BY s.full_name ASC";
    }

    $list_sql .= $order_clause;

    $list_stmt = $pdo->prepare($list_sql);
    $list_stmt->execute($list_params);
    $students = $list_stmt->fetchAll();
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

$page_title = "Manage Student Accounts";
include '../includes/header.php';
?>

<div class="wrapper">

    <?php include '../includes/sidebar.php'; ?>


    <div id="content">
        <?php render_topbar(); ?>

        <div class="container-fluid">
            <?php render_page_header('Student Accounts Desk', '<a href="add_student.php" class="btn btn-sm btn-primary"><i class="fa-solid fa-user-plus me-1"></i>Add Student</a>'); ?>

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




            <div class="card mb-4 shadow-sm border-0">
                <div class="card-body">
                    <form action="manage_students.php" method="GET" class="row g-3 align-items-end">
                        <div class="col-12 col-sm-6 col-lg-3">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search"
                                placeholder="ID, Name, Mobile..." value="<?php echo e($search); ?>">
                        </div>
                        <div class="col-6 col-sm-3 col-lg-2">
                            <label for="status_filter" class="form-label">Status</label>
                            <select class="form-select" id="status_filter" name="status_filter">
                                <option value="">All Statuses</option>
                                <option value="Pending" <?php echo ($status_filter === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="Approved" <?php echo ($status_filter === 'Approved') ? 'selected' : ''; ?>>Approved</option>
                                <option value="Rejected" <?php echo ($status_filter === 'Rejected') ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                        </div>
                        <div class="col-6 col-sm-3 col-lg-2">
                            <label for="payment_filter" class="form-label">Payment</label>
                            <select class="form-select" id="payment_filter" name="payment_filter">
                                <option value="">All Payments</option>
                                <option value="Paid" <?php echo ($payment_filter === 'Paid') ? 'selected' : ''; ?>>Paid</option>
                                <option value="Unpaid" <?php echo ($payment_filter === 'Unpaid') ? 'selected' : ''; ?>>Unpaid</option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-2">
                            <label for="course_filter" class="form-label">Course</label>
                            <select class="form-select" id="course_filter" name="course_filter">
                                <option value="">All Courses</option>
                                <?php foreach ($courses as $c): ?>
                                    <option value="<?php echo $c['course_id']; ?>" <?php echo ($course_filter == $c['course_id']) ? 'selected' : ''; ?>>
                                        <?php echo e($c['course_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-9 col-sm-4 col-lg-2">
                            <label for="sort_by" class="form-label">Sort By</label>
                            <select class="form-select" id="sort_by" name="sort_by">
                                <option value="newest" <?php echo ($sort_by === 'newest') ? 'selected' : ''; ?>>Newest First</option>
                                <option value="oldest" <?php echo ($sort_by === 'oldest') ? 'selected' : ''; ?>>Oldest First</option>
                                <option value="pct_high" <?php echo ($sort_by === 'pct_high') ? 'selected' : ''; ?>>12th Std (%) High-to-Low</option>
                                <option value="pct_low" <?php echo ($sort_by === 'pct_low') ? 'selected' : ''; ?>>12th Std (%) Low-to-High</option>
                                <option value="name_asc" <?php echo ($sort_by === 'name_asc') ? 'selected' : ''; ?>>Name: A to Z</option>
                            </select>
                        </div>
                        <div class="col-3 col-sm-2 col-lg-1">
                            <a href="manage_students.php" class="btn btn-outline-secondary w-100 py-2" title="Reset"><i class="fa-solid fa-rotate-left"></i></a>
                        </div>
                    </form>
                </div>
            </div>


            <div class="card">
                <div class="card-header">
                    <i class="fa-solid fa-users me-2"></i>Students Database Records
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table align-middle">
                            <thead>
                                <tr>
                                    <th>Sr. No.</th>
                                    <th>Admission ID</th>
                                    <th>Student Name</th>
                                    <th>Course Preference</th>
                                    <th>Mobile No</th>
                                    <th>Status</th>
                                    <th>Submission</th>
                                    <th class="text-center text-nowrap-action">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($students)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">No student records registered in database.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php $sr_no = 1; ?>
                                    <?php foreach ($students as $s): ?>
                                        <tr>
                                            <td><?php echo $sr_no++; ?></td>
                                            <td class="fw-bold text-primary"><?php echo e($s['admission_no']); ?></td>
                                            <td><?php echo e($s['full_name']); ?></td>
                                            <td><?php echo e($s['course_name']); ?></td>
                                            <td><?php echo e($s['mobile']); ?></td>
                                            <td>
                                                <?php if ($s['status'] === 'Pending'): ?>
                                                    <span class="badge badge-pending">Pending</span>
                                                <?php elseif ($s['status'] === 'Approved'): ?>
                                                    <span class="badge badge-approved">Approved</span>
                                                <?php elseif ($s['status'] === 'Rejected'): ?>
                                                    <span class="badge badge-rejected">Rejected</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($s['is_submitted'] == 1): ?>
                                                    <span class="badge bg-success-subtle text-success">Submitted</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning-subtle text-warning">Draft</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center text-nowrap-action">

                                                <a href="view_students.php?id=<?php echo $s['student_id']; ?>" class="btn btn-sm btn-outline-primary me-1" title="View Profile">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>

                                                <a href="edit_student.php?id=<?php echo $s['student_id']; ?>" class="btn btn-sm btn-outline-secondary me-1" title="Edit Profile">
                                                    <i class="fa-solid fa-user-pen"></i>
                                                </a>

                                                <a href="manage_students.php?delete_id=<?php echo $s['student_id']; ?>&search=<?php echo urlencode($search); ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('DANGER: Deleting this student will wipe out their credentials, documents, and logs. Proceed?');">
                                                    <i class="fa-solid fa-user-minus"></i>
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('search');
        if (searchInput) {
            // Restore focus and cursor position to the end if there's a search value
            if (searchInput.value) {
                searchInput.focus();
                const val = searchInput.value;
                searchInput.value = '';
                searchInput.value = val;
            }

            // Debounce typing in search input
            let timeout = null;
            searchInput.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    searchInput.form.submit();
                }, 600); // 600ms debounce
            });
        }

        // Auto-submit form when any dropdown selection changes
        const selects = document.querySelectorAll('form select');
        selects.forEach(select => {
            select.addEventListener('change', function() {
                this.form.submit();
            });
        });
    });
</script>
<?php include '../includes/footer.php'; ?>