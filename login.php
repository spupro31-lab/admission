<?php
require_once 'includes/db_connect.php';
require_once 'includes/auth.php';


redirect_if_logged_in();

$error_msg = '';

$role = normalize_role($_GET['role'] ?? 'student');
$email = trim($_POST['email'] ?? ($_GET['email'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        $error_msg = "Security validation failed. Please refresh the page and try again.";
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = normalize_role($_POST['role'] ?? 'student');

        if (empty($email) || empty($password)) {
            $error_msg = "Please fill in all fields.";
        } else {
            try {
                if ($role === 'staff') {

                    $stmt = $pdo->prepare("SELECT * FROM admission_staff WHERE email = :email");
                    $stmt->execute(['email' => $email]);
                    $user = $stmt->fetch();
                    $id_col = 'staff_id';
                } else {

                    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email AND role = :role");
                    $stmt->execute(['email' => $email, 'role' => $role]);
                    $user = $stmt->fetch();
                    $id_col = 'user_id';
                }

                if ($user && password_verify($password, $user['password'])) {
                    login_user($user[$id_col], $role, $user['name'], $user['email']);

                    // If student logs in, redirect directly to admission form if not submitted yet
                    if ($role === 'student') {
                        $chk_stmt = $pdo->prepare("SELECT student_id, is_submitted FROM students WHERE user_id = :uid");
                        $chk_stmt->execute(['uid' => $user['user_id']]);
                        $stud_record = $chk_stmt->fetch();

                        if (!$stud_record || (int)$stud_record['is_submitted'] === 0) {
                            app_redirect('student/apply.php');
                        }
                    }

                    app_redirect(role_dashboard_path($role));
                } else {
                    $error_msg = "Invalid " . strtolower(role_label($role)) . " email or password.";
                }
            } catch (PDOException $e) {
                $error_msg = "Database Error: " . $e->getMessage();
            }
        }
    }
}

$is_public_page = true;
$body_class = "login-page-body role-" . $role;
$page_title = ucfirst($role) . " Login";
include 'includes/header.php';
?>

<section class="sct-auth-page">
    <div class="container">
        <div class="row align-items-center g-4 g-lg-5 min-vh-content">

            <!-- LEFT: campus image + college branding -->
            <div class="col-lg-6 d-none d-lg-block">
                <div class="sct-auth-left">
                    <div class="sct-auth-img-wrap">
                        <img
                            src="assets/images/<?php echo $role === 'staff' ? 'staff_bg.png' : ($role === 'admin' ? 'admin_bg.png' : 'university_campus.png'); ?>"
                            alt="State College of Technology"
                            class="sct-auth-img">
                        <!-- Overlay brand strip -->
                        <div class="sct-auth-img-overlay">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="fa-solid fa-graduation-cap fs-4 text-white"></i>
                                <span class="fw-bold text-white fs-5">SCT Portal</span>
                            </div>
                            <p class="text-white-50 small mb-0">State College of Technology &mdash; Est. 1998</p>
                            <div class="sct-auth-overlay-tags mt-3 d-flex flex-wrap gap-2">
                                <span class="sct-overlay-tag">NAAC Accredited</span>
                                <span class="sct-overlay-tag">UGC Approved</span>
                                <span class="sct-overlay-tag">Est. 1998</span>
                            </div>
                        </div>
                    </div>

                    <!-- Info bullets below image -->
                    <div class="sct-auth-bullets mt-3">
                        <?php if ($role === 'student'): ?>
                            <div class="sct-auth-bullet">
                                <i class="fa-solid fa-circle-check text-primary"></i>
                                <span>Track application &amp; document verification in real time</span>
                            </div>
                            <div class="sct-auth-bullet">
                                <i class="fa-solid fa-circle-check text-primary"></i>
                                <span>Download offer letter &amp; payment receipt instantly</span>
                            </div>
                            <div class="sct-auth-bullet">
                                <i class="fa-solid fa-circle-check text-primary"></i>
                                <span>Upload 10th/12th marksheets, Aadhaar &amp; LC securely</span>
                            </div>
                        <?php elseif ($role === 'staff'): ?>
                            <div class="sct-auth-bullet">
                                <i class="fa-solid fa-circle-check text-success"></i>
                                <span>Review &amp; verify student academic documents</span>
                            </div>
                            <div class="sct-auth-bullet">
                                <i class="fa-solid fa-circle-check text-success"></i>
                                <span>Approve or reject applications with remarks</span>
                            </div>
                            <div class="sct-auth-bullet">
                                <i class="fa-solid fa-circle-check text-success"></i>
                                <span>Export admission reports &amp; course allocation data</span>
                            </div>
                        <?php else: ?>
                            <div class="sct-auth-bullet">
                                <i class="fa-solid fa-circle-check text-warning"></i>
                                <span>Manage courses, staff accounts &amp; seat capacity</span>
                            </div>
                            <div class="sct-auth-bullet">
                                <i class="fa-solid fa-circle-check text-warning"></i>
                                <span>Oversee all student applications &amp; payments</span>
                            </div>
                            <div class="sct-auth-bullet">
                                <i class="fa-solid fa-circle-check text-warning"></i>
                                <span>Access full audit trails &amp; system analytics</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- RIGHT: login form -->
            <div class="col-12 col-lg-6">
                <div class="sct-auth-card">

                    <!-- Card header -->
                    <div class="sct-auth-card-head">
                        <div class="sct-auth-card-logo <?php echo $role === 'staff' ? 'logo-teal' : ($role === 'admin' ? 'logo-amber' : ''); ?>">
                            <i class="fa-solid fa-graduation-cap"></i>
                        </div>
                        <div>
                            <h4 class="sct-auth-card-title">
                                <?php if ($role === 'student'): ?>Student Sign In
                                <?php elseif ($role === 'staff'): ?>Staff Sign In
                                <?php else: ?>Admin Sign In
                                <?php endif; ?>
                            </h4>
                            <p class="sct-auth-card-sub">State College of Technology &mdash; Admission Portal</p>
                        </div>
                    </div>

                    <!-- Role switcher tabs -->
                    <div class="auth-role-tabs mb-4">
                        <a href="login.php?role=student" class="nav-link <?php echo $role === 'student' ? 'active-student' : ''; ?>">
                            <i class="fa-solid fa-user"></i> Student
                        </a>
                        <a href="login.php?role=staff" class="nav-link <?php echo $role === 'staff' ? 'active-staff' : ''; ?>">
                            <i class="fa-solid fa-user-tie"></i> Staff
                        </a>
                        <a href="login.php?role=admin" class="nav-link <?php echo $role === 'admin' ? 'active-admin' : ''; ?>">
                            <i class="fa-solid fa-screwdriver-wrench"></i> Admin
                        </a>
                    </div>

                    <?php render_alert($error_msg, 'danger', false, true); ?>

                    <form action="login.php?role=<?php echo urlencode($role); ?>" method="POST">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="role" value="<?php echo e($role); ?>">

                        <div class="mb-3">
                            <label for="email" class="sct-form-label">
                                <?php echo $role === 'student' ? 'Student Email' : ($role === 'staff' ? 'Staff Email' : 'Admin Email'); ?>
                            </label>
                            <div class="input-group auth-input-group">
                                <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email"
                                    placeholder="name@domain.edu"
                                    value="<?php echo e($email ?? ''); ?>" required autofocus>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="sct-form-label">Password</label>
                            <div class="input-group auth-input-group has-toggle">
                                <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password"
                                    placeholder="Enter your password" required>
                                <button type="button" class="password-toggle-btn"
                                    onclick="togglePassword('password', this)" title="Show/hide password">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <?php
                        $btn_text = $role === 'student' ? 'Sign In as Student' : ($role === 'staff' ? 'Sign In as Staff' : 'Sign In as Admin');
                        $btn_color = $role === 'staff' ? 'sct-btn-teal' : ($role === 'admin' ? 'sct-btn-amber' : 'sct-btn-primary');
                        ?>
                        <button type="submit" class="sct-submit-btn <?php echo $btn_color; ?>">
                            <i class="fa-solid fa-right-to-bracket"></i> <?php echo $btn_text; ?>
                        </button>
                    </form>

                    <!-- Footer links -->
                    <div class="sct-auth-card-footer">
                        <?php if ($role === 'student'): ?>
                            <span class="text-muted small">New applicant?
                                <a href="inquiry.php" class="fw-bold text-primary text-decoration-none">Submit Inquiry &rarr;</a>
                            </span>
                        <?php endif; ?>
                        <a href="index.php" class="text-muted small text-decoration-none">
                            <i class="fa-solid fa-arrow-left me-1"></i>Back to Home
                        </a>
                    </div>

                </div>

                <!-- Helpdesk note -->
                <div class="sct-auth-helpdesk mt-3">
                    <i class="fa-solid fa-headset"></i>
                    <span>Need help? Call <strong>+91 (079) 2324-5678</strong> or email
                        <a href="mailto:admissions@statecollege.edu.in">admissions@statecollege.edu.in</a>
                    </span>
                </div>

            </div>
        </div>
    </div>
</section>

<script>
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>

<?php include 'includes/footer.php'; ?>