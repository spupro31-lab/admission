<?php
require_once '../includes/db_connect.php';
require_once '../includes/auth.php';


check_access('admin');

try {

    $stats = [
        'total_apps' => $pdo->query("SELECT COUNT(*) FROM students WHERE is_submitted = 1")->fetchColumn(),
        'approved' => $pdo->query("SELECT COUNT(*) FROM students WHERE status = 'Approved'")->fetchColumn(),
        'pending' => $pdo->query("SELECT COUNT(*) FROM students WHERE status = 'Pending' AND is_submitted = 1")->fetchColumn(),
        'rejected' => $pdo->query("SELECT COUNT(*) FROM students WHERE status = 'Rejected'")->fetchColumn(),
        'courses' => $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn(),
        'staff' => $pdo->query("SELECT COUNT(*) FROM admission_staff")->fetchColumn()
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

            <div class="row g-4 mb-4">

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
                    <div class="card bg-white p-3 stat-card approved h-100">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted fw-bold">Active Staff</small>
                                <h3 class="fw-bold mb-0 mt-1 text-purple" style="color: #6f42c1;"><?php echo $stats['staff']; ?></h3>
                            </div>
                            <i class="fa-solid fa-user-tie stat-icon text-purple" style="color: #6f42c1;"></i>
                        </div>
                    </div>
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