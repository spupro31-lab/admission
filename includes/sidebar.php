<?php
require_once __DIR__ . '/auth.php';

$current_page = basename($_SERVER['PHP_SELF']);
$role = current_role() ?: '';
$user_name = $_SESSION['name'] ?? 'Guest';

$student_is_submitted = false;
if ($role === 'student' && isset($_SESSION['user_id'])) {
    if (!isset($pdo)) {
        require_once __DIR__ . '/db_connect.php';
    }
    try {
        $stmt = $pdo->prepare("SELECT is_submitted FROM students WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
        $sub_status = $stmt->fetchColumn();
        if ($sub_status !== false && (int)$sub_status === 1) {
            $student_is_submitted = true;
        }
    } catch (PDOException $e) {
    }
}


$nav_items = [
    'admin' => [
        ['href' => 'admin/dashboard.php', 'icon' => 'fa-gauge', 'label' => 'Dashboard', 'pages' => ['dashboard.php']],
        ['href' => 'admin/manage_courses.php', 'icon' => 'fa-book', 'label' => 'Courses', 'pages' => ['manage_courses.php']],
        ['href' => 'admin/manage_staff.php', 'icon' => 'fa-user-tie', 'label' => 'Staff', 'pages' => ['manage_staff.php']],
        ['href' => 'admin/manage_students.php', 'icon' => 'fa-users', 'label' => 'Students', 'pages' => ['manage_students.php', 'add_student.php', 'edit_student.php', 'view_students.php']],
    ],
    'staff' => [
        ['href' => 'staff/dashboard.php', 'icon' => 'fa-gauge', 'label' => 'Dashboard', 'pages' => ['dashboard.php']],
        ['href' => 'staff/reports.php', 'icon' => 'fa-chart-line', 'label' => 'Reports', 'pages' => ['reports.php']],
    ],
    'student' => [
        ['href' => 'student/dashboard.php', 'icon' => 'fa-gauge', 'label' => 'Dashboard', 'pages' => ['dashboard.php']],
        ['href' => 'student/apply.php', 'icon' => 'fa-file-signature', 'label' => 'Admission Form', 'pages' => ['apply.php']],
        ['href' => 'student/upload.php', 'icon' => 'fa-file-arrow-up', 'label' => 'Documents', 'pages' => ['upload.php']],
        ['href' => 'student/payment.php', 'icon' => 'fa-credit-card', 'label' => 'Payment', 'pages' => ['payment.php']],
        ['href' => 'student/status.php', 'icon' => 'fa-clock-rotate-left', 'label' => 'Status', 'pages' => ['status.php']],
    ],
];

$items = $nav_items[$role] ?? [];
?>
<nav id="sidebar">
    <div class="sidebar-header">
        <h4><i class="fa-solid fa-graduation-cap me-2"></i>College Portal</h4>
        <small>Welcome, <?php echo e($user_name); ?></small>
    </div>

    <ul class="list-unstyled components">
        <?php foreach ($items as $item):
            $is_disabled = false;
            if ($role === 'student' && $student_is_submitted) {
                if (in_array($item['href'], ['student/apply.php', 'student/upload.php', 'student/payment.php'], true)) {
                    $is_disabled = true;
                }
            }
        ?>
            <li class="<?php
                        $li_classes = [];
                        if (in_array($current_page, $item['pages'], true)) {
                            $li_classes[] = 'active';
                        }
                        if ($is_disabled) {
                            $li_classes[] = 'disabled';
                        }
                        echo implode(' ', $li_classes);
                        ?>">
                <?php if ($is_disabled): ?>
                    <a href="javascript:void(0);" onclick="event.preventDefault();" class="disabled-nav-link" title="Application already submitted">
                        <i class="fa-solid <?php echo e($item['icon']); ?>"></i>
                        <?php echo e($item['label']); ?>
                    </a>
                <?php else: ?>
                    <a href="<?php echo e(app_url($item['href'])); ?>">
                        <i class="fa-solid <?php echo e($item['icon']); ?>"></i>
                        <?php echo e($item['label']); ?>
                    </a>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>

        <?php if (!empty($items)): ?>
            <li class="nav-divider" aria-hidden="true"></li>
        <?php endif; ?>

        <li class="<?php echo ($current_page === 'profile.php') ? 'active' : ''; ?>">
            <a href="<?php echo e(app_url('profile.php')); ?>"><i class="fa-solid fa-user-gear"></i> My Profile</a>
        </li>
        <li>
            <a href="<?php echo e(app_url('logout.php')); ?>" class="logout-link"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </li>
    </ul>
</nav>