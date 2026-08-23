<?php
require_once 'includes/db_connect.php';
require_once 'includes/auth.php';


redirect_if_logged_in();

$error_msg = '';

$role = normalize_role($_GET['role'] ?? 'student');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
                app_redirect(role_dashboard_path($role));
            } else {
                $error_msg = "Invalid " . strtolower(role_label($role)) . " email or password.";
            }
        } catch (PDOException $e) {
            $error_msg = "Database Error: " . $e->getMessage();
        }
    }
}

$is_public_page = true;
$body_class = "login-page-body role-" . $role;
$page_title = ucfirst($role) . " Login";
include 'includes/header.php';
?>

<div class="auth-hero-section">
    <div class="home-hero-decor home-hero-decor--top"></div>
    <div class="home-hero-decor home-hero-decor--bottom"></div>

    <div class="container position-relative">
        <div class="row align-items-center g-4 g-lg-5">
            <!-- Left Info Column -->
            <div class="col-lg-6 text-center text-lg-start">
                <span class="auth-trust-badge mb-3">
                    <i class="fa-solid fa-shield-halved text-success"></i> Official Institutional Portal
                </span>
                <h1 class="home-hero-title mb-3">
                    <?php if ($role === 'student'): ?>
                        Welcome Back, <span class="text-primary">Future Innovator</span>
                    <?php elseif ($role === 'staff'): ?>
                        Staff Review & <span class="text-teal" style="color: var(--teal);">Verification Portal</span>
                    <?php else: ?>
                        System Governance & <span class="text-warning" style="color: var(--amber);">Admin Control</span>
                    <?php endif; ?>
                </h1>
                <p class="home-hero-subtitle mb-4">
                    <?php if ($role === 'student'): ?>
                        Access your State College of Technology admission dashboard to monitor your application, upload marksheets, choose course preferences, and receive direct status updates.
                    <?php elseif ($role === 'staff'): ?>
                        Log in to evaluate incoming student applications, review academic transcripts, record verification comments, and process admission decisions efficiently.
                    <?php else: ?>
                        Log in to manage course catalogs, configure staff accounts, oversee admission pipelines, and access administrative maintenance logs.
                    <?php endif; ?>
                </p>

                <div class="auth-feature-list d-none d-md-flex mb-4">
                    <?php if ($role === 'student'): ?>
                        <div class="auth-feature-item">
                            <div class="auth-feature-icon icon-blue">
                                <i class="fa-solid fa-file-invoice"></i>
                            </div>
                            <div>
                                <strong class="text-dark d-block">Real-time Application Status</strong>
                                <span class="text-muted small">Track your document verification and merit approval progress 24/7.</span>
                            </div>
                        </div>
                        <div class="auth-feature-item">
                            <div class="auth-feature-icon icon-blue">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                            </div>
                            <div>
                                <strong class="text-dark d-block">Secure Document Transcripts</strong>
                                <span class="text-muted small">Upload high-resolution 10th/12th marksheets and identity proofs safely.</span>
                            </div>
                        </div>
                    <?php elseif ($role === 'staff'): ?>
                        <div class="auth-feature-item">
                            <div class="auth-feature-icon icon-teal">
                                <i class="fa-solid fa-user-check"></i>
                            </div>
                            <div>
                                <strong class="text-dark d-block">Streamlined Verification</strong>
                                <span class="text-muted small">Cross-check academic marks and flag discrepancies with staff notes.</span>
                            </div>
                        </div>
                        <div class="auth-feature-item">
                            <div class="auth-feature-icon icon-teal">
                                <i class="fa-solid fa-file-export"></i>
                            </div>
                            <div>
                                <strong class="text-dark d-block">Report Generation</strong>
                                <span class="text-muted small">Export applicant lists, course allocations, and verification summaries.</span>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="auth-feature-item">
                            <div class="auth-feature-icon icon-amber">
                                <i class="fa-solid fa-sliders"></i>
                            </div>
                            <div>
                                <strong class="text-dark d-block">Central Control Panel</strong>
                                <span class="text-muted small">Manage users, adjust seat capacities, and update admission criteria.</span>
                            </div>
                        </div>
                        <div class="auth-feature-item">
                            <div class="auth-feature-icon icon-amber">
                                <i class="fa-solid fa-database"></i>
                            </div>
                            <div>
                                <strong class="text-dark d-block">System Audit Logs</strong>
                                <span class="text-muted small">Full audit trails for student enrollments and staff actions.</span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="auth-support-box text-start d-flex align-items-center gap-3">
                    <div class="fs-4 text-primary">
                        <i class="fa-solid fa-headset"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block fw-semibold">Need Help Logging In?</small>
                        <span class="small text-dark fw-bold">Contact Admission Helpdesk at <a href="mailto:admissions@statecollege.edu" class="text-decoration-underline">admissions@statecollege.edu</a></span>
                    </div>
                </div>
            </div>

            <!-- Right Auth Form Card Column -->
            <div class="col-12 col-lg-6">
                <div class="auth-card">
                    <div class="auth-card-header">
                        <div class="auth-card-logo">
                            <i class="fa-solid fa-graduation-cap"></i>
                        </div>
                        <h4 class="fw-extrabold text-dark mb-1">Sign In to SCT</h4>
                        <p class="text-muted small mb-0">State College of Technology Admission Portal</p>
                    </div>

                    <!-- Role Selector Pills -->
                    <div class="auth-role-tabs">
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
                        <input type="hidden" name="role" value="<?php echo e($role); ?>">

                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold text-dark small">
                                <?php echo $role === 'student' ? 'Student Email Address' : ($role === 'staff' ? 'Official Staff Email' : 'Administrator Email'); ?>
                            </label>
                            <div class="input-group auth-input-group">
                                <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" placeholder="name@domain.edu" value="<?php echo e($email ?? ''); ?>" required autofocus>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="password" class="form-label fw-semibold text-dark small mb-0">Password</label>
                            </div>
                            <div class="input-group auth-input-group has-toggle">
                                <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                                <button type="button" class="password-toggle-btn" onclick="togglePassword('password', this)" title="Toggle password visibility">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <?php
                        $btn_class = $role === 'student' ? 'btn-premium-primary' : ($role === 'staff' ? 'btn-premium-sky' : 'btn-premium-amber');
                        $btn_text = $role === 'student' ? 'Sign In as Student' : ($role === 'staff' ? 'Sign In as Staff' : 'Sign In as Admin');
                        ?>
                        <button type="submit" class="btn <?php echo $btn_class; ?> w-100 py-2.5 mt-2 fw-bold text-center">
                            <i class="fa-solid fa-right-to-bracket me-2"></i><?php echo $btn_text; ?>
                        </button>
                    </form>

                    <?php if ($role === 'student'): ?>
                        <div class="text-center mt-4 pt-3 border-top border-slate-200">
                            <span class="text-muted small">Don't have an account yet?</span>
                            <a href="student_register.php" class="text-decoration-none small fw-bold ms-1 text-primary">Create New Account</a>
                        </div>
                    <?php endif; ?>

                    <div class="text-center mt-3">
                        <a href="index.php" class="text-decoration-none small text-muted">
                            <i class="fa-solid fa-arrow-left me-1"></i>Back to Main Website
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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