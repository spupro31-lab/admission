<?php
require_once '../includes/db_connect.php';
require_once '../includes/auth.php';

check_access('admin');

$error_msg = "";
$success_msg = "";

// Handle Delete Inquiry & Student from Dashboard
if (isset($_GET['delete_inquiry_id']) && !empty($_GET['delete_inquiry_id'])) {
    if (!validate_csrf($_GET['csrf_token'] ?? '')) {
        $error_msg = "Security validation failed for delete request. Please try again.";
    } else {
        $del_inq_id = intval($_GET['delete_inquiry_id']);
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

try {

    $stats = [
        'total_inquiries' => $pdo->query("SELECT COUNT(*) FROM inquiries")->fetchColumn(),
        'total_apps' => $pdo->query("SELECT COUNT(*) FROM students WHERE is_submitted = 1")->fetchColumn(),
        'approved' => $pdo->query("SELECT COUNT(*) FROM students WHERE status = 'Approved'")->fetchColumn(),
        'pending' => $pdo->query("SELECT COUNT(*) FROM students WHERE status = 'Pending' AND is_submitted = 1")->fetchColumn(),
        'rejected' => $pdo->query("SELECT COUNT(*) FROM students WHERE status = 'Rejected'")->fetchColumn(),
        'courses' => $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn(),
        'staff' => $pdo->query("SELECT COUNT(*) FROM admission_staff")->fetchColumn(),
        'revenue' => (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'Completed'")->fetchColumn(),
        'refunded' => (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'Refunded'")->fetchColumn()
    ];


    $gender_stmt = $pdo->query("SELECT gender, COUNT(*) as count FROM students WHERE is_submitted = 1 GROUP BY gender");
    $gender_data = $gender_stmt->fetchAll();

    $gender_labels = [];
    $gender_counts = [];
    foreach ($gender_data as $g) {
        $gender_labels[] = $g['gender'];
        $gender_counts[] = (int)$g['count'];
    }


    $category_stmt = $pdo->query("SELECT category, COUNT(*) as count FROM students WHERE is_submitted = 1 GROUP BY category");
    $category_data = $category_stmt->fetchAll();

    $category_labels = [];
    $category_counts = [];
    foreach ($category_data as $c) {
        $category_labels[] = $c['category'];
        $category_counts[] = (int)$c['count'];
    }

    $recent_inquiries_stmt = $pdo->query("
        SELECT i.*, c.course_name, s.student_id, s.is_submitted, s.status as application_status
        FROM inquiries i
        LEFT JOIN courses c ON i.course_id = c.course_id
        LEFT JOIN students s ON i.user_id = s.user_id
        ORDER BY i.inquiry_id DESC
        LIMIT 6
    ");
    $recent_inquiries = $recent_inquiries_stmt->fetchAll();

    $recent_apps_stmt = $pdo->query("
        SELECT s.student_id, s.full_name, s.email, s.status, s.created_at, c.course_name 
        FROM students s 
        LEFT JOIN courses c ON s.course_id = c.course_id 
        WHERE s.is_submitted = 1 
        ORDER BY s.created_at DESC 
        LIMIT 5
    ");
    $recent_apps = $recent_apps_stmt->fetchAll();
} catch (PDOException $e) {
    die("Database Query Failed: " . $e->getMessage());
}

$page_title = "Admin Dashboard";
include '../includes/header.php';
?>

<div class="wrapper">

    <?php include '../includes/sidebar.php'; ?>


    <div id="content">
        <?php render_topbar(); ?>

        <div class="container-fluid">
            <?php render_page_header('Administrator Command Center', '<span class="badge bg-danger"><i class="fa-solid fa-shield-halved me-1"></i>Secure Admin</span>'); ?>

            <?php render_alert($error_msg, 'danger', false, true); ?>
            <?php render_alert($success_msg, 'success', false, true); ?>

            <div class="row g-4 mb-4">

                <div class="col-12 col-sm-6 col-md-4 col-xl-2">
                    <a href="inquiries.php" class="text-decoration-none">
                        <div class="card bg-white p-3 stat-card courses h-100">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-muted fw-bold">Inquiries</small>
                                    <h3 class="fw-bold mb-0 mt-1 text-info"><?php echo $stats['total_inquiries']; ?></h3>
                                </div>
                                <i class="fa-solid fa-clipboard-question stat-icon text-info"></i>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-12 col-sm-6 col-md-4 col-xl-2">
                    <div class="card bg-white p-3 stat-card courses h-100">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted fw-bold">Total Apps</small>
                                <h3 class="fw-bold mb-0 mt-1"><?php echo $stats['total_apps']; ?></h3>
                            </div>
                            <i class="fa-solid fa-file-invoice stat-icon text-primary"></i>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-md-4 col-xl-2">
                    <div class="card bg-white p-3 stat-card pending h-100">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted fw-bold">Pending</small>
                                <h3 class="fw-bold mb-0 mt-1 text-warning"><?php echo $stats['pending']; ?></h3>
                            </div>
                            <i class="fa-solid fa-spinner fa-spin stat-icon text-warning"></i>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-md-4 col-xl-2">
                    <div class="card bg-white p-3 stat-card approved h-100">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted fw-bold">Approved</small>
                                <h3 class="fw-bold mb-0 mt-1 text-success"><?php echo $stats['approved']; ?></h3>
                            </div>
                            <i class="fa-solid fa-circle-check stat-icon text-success"></i>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-md-4 col-xl-2">
                    <div class="card bg-white p-3 stat-card rejected h-100">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted fw-bold">Rejected</small>
                                <h3 class="fw-bold mb-0 mt-1 text-danger"><?php echo $stats['rejected']; ?></h3>
                            </div>
                            <i class="fa-solid fa-circle-xmark stat-icon text-danger"></i>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-md-4 col-xl-2">
                    <div class="card bg-white p-3 stat-card courses h-100">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted fw-bold">Active Courses</small>
                                <h3 class="fw-bold mb-0 mt-1 text-info"><?php echo $stats['courses']; ?></h3>
                            </div>
                            <i class="fa-solid fa-book-bookmark stat-icon text-info"></i>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-md-4 col-xl-2">
                    <a href="payments.php" class="text-decoration-none">
                        <div class="card bg-white p-3 stat-card approved h-100 border-start border-4 border-success">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-muted fw-bold">Active Fee Revenue</small>
                                    <h4 class="fw-bold mb-0 mt-1 text-success">₹<?php echo number_format($stats['revenue'], 0); ?></h4>
                                    <small class="text-danger" style="font-size: 0.72rem;">₹<?php echo number_format($stats['refunded'], 0); ?> Refunded</small>
                                </div>
                                <i class="fa-solid fa-wallet stat-icon text-success"></i>
                            </div>
                        </div>
                    </a>
                </div>
            </div>


            <div class="row">

                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <i class="fa-solid fa-chart-pie me-2"></i>Gender-wise Application Statistics
                        </div>
                        <div class="card-body d-flex justify-content-center align-items-center" style="position: relative; height:320px;">
                            <?php if (empty($gender_counts)): ?>
                                <p class="text-muted">No student applications submitted yet to map data.</p>
                            <?php else: ?>
                                <canvas id="genderChart"></canvas>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>


                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <i class="fa-solid fa-chart-bar me-2"></i>Category-wise Application Statistics
                        </div>
                        <div class="card-body d-flex justify-content-center align-items-center" style="position: relative; height:320px;">
                            <?php if (empty($category_counts)): ?>
                                <p class="text-muted">No student applications submitted yet to map data.</p>
                            <?php else: ?>
                                <canvas id="categoryChart"></canvas>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Inquiries Section with Delete Action -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="fa-solid fa-clipboard-question text-info me-2"></i>Recent Admission Inquiries
                    </h6>
                    <a href="inquiries.php" class="btn btn-sm btn-outline-primary fw-semibold">
                        View All Inquiries <i class="fa-solid fa-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-uppercase small text-muted">
                                <tr>
                                    <th>#</th>
                                    <th>Applicant Name</th>
                                    <th>Email ID</th>
                                    <th>Mobile No.</th>
                                    <th>Program Preference</th>
                                    <th>Inquiry Date</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_inquiries)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">
                                            No inquiries recorded yet.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recent_inquiries as $idx => $inq): ?>
                                        <tr>
                                            <td class="text-muted fw-bold"><?php echo $idx + 1; ?></td>
                                            <td>
                                                <div class="fw-bold text-dark"><?php echo e($inq['name']); ?></div>
                                                <small class="text-muted">User ID: #<?php echo e($inq['user_id'] ?? 'N/A'); ?></small>
                                            </td>
                                            <td>
                                                <a href="mailto:<?php echo e($inq['email']); ?>" class="text-decoration-none">
                                                    <?php echo e($inq['email']); ?>
                                                </a>
                                            </td>
                                            <td class="font-monospace small">
                                                <?php echo e($inq['mobile']); ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border">
                                                    <?php echo e($inq['course_name'] ?? 'General'); ?>
                                                </span>
                                            </td>
                                            <td class="small text-muted font-monospace">
                                                <?php echo e(substr($inq['created_at'], 0, 16)); ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($inq['student_id'])): ?>
                                                    <?php if ((int)$inq['is_submitted'] === 1): ?>
                                                        <span class="badge bg-success">Submitted</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning text-dark">In Progress</span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inquiry Only</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <a href="dashboard.php?delete_inquiry_id=<?php echo $inq['inquiry_id']; ?>&csrf_token=<?php echo csrf_token(); ?>" 
                                                   class="btn btn-sm btn-outline-danger" 
                                                   onclick="return confirm('Are you sure you want to delete inquiry for <?php echo addslashes(e($inq['name'])); ?>? This will delete the inquiry and the associated student account.');" 
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


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        <?php if (!empty($gender_counts)): ?>
            // 1. Initialize Gender Chart
            const ctxGender = document.getElementById('genderChart').getContext('2d');
            new Chart(ctxGender, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode($gender_labels); ?>,
                    datasets: [{
                        data: <?php echo json_encode($gender_counts); ?>,
                        backgroundColor: ['#0f4c81', '#328cc1', '#ff9f1c', '#e71d36'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        <?php endif; ?>

        <?php if (!empty($category_counts)): ?>
            // 2. Initialize Category Chart
            const ctxCategory = document.getElementById('categoryChart').getContext('2d');
            new Chart(ctxCategory, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($category_labels); ?>,
                    datasets: [{
                        label: 'Number of Applicants',
                        data: <?php echo json_encode($category_counts); ?>,
                        backgroundColor: '#328cc1',
                        borderColor: '#0f4c81',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        <?php endif; ?>
    });
</script>

<?php include '../includes/footer.php'; ?>