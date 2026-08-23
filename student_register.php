<?php
require_once 'includes/db_connect.php';
require_once 'includes/auth.php';


redirect_if_logged_in();

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';


    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error_msg = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error_msg = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error_msg = "Passwords do not match.";
    } else {
        try {

            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);

            if ($stmt->rowCount() > 0) {
                $error_msg = "This email is already registered. Please login.";
            } else {

                $hashed_password = password_hash($password, PASSWORD_DEFAULT);


                $insert_stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, 'student')");
                $insert_stmt->execute([
                    'name' => $name,
                    'email' => $email,
                    'password' => $hashed_password
                ]);


                $new_user_id = $pdo->lastInsertId();

                login_user($new_user_id, 'student', $name, $email);
                app_redirect('student/dashboard.php');
            }
        } catch (PDOException $e) {
            $error_msg = "Database Error: " . $e->getMessage();
        }
    }
}

$is_public_page = true;
$body_class = "login-page-body role-student";
$page_title = "Student Registration";
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
                    <i class="fa-solid fa-user-plus text-primary"></i> Fast & Easy Registration
                </span>
                <h1 class="home-hero-title mb-3">
                    Start Your Journey at <span class="text-primary">SCT College</span>
                </h1>
                <p class="home-hero-subtitle mb-4">
                    Create your official student portal account to unlock digital admission workflows, apply for engineering & technical programs, and track document verification in real time.
                </p>

                <div class="auth-feature-list d-none d-md-flex mb-4">
                    <div class="auth-feature-item">
                        <div class="auth-feature-icon icon-blue">
                            <i class="fa-solid fa-id-card"></i>
                        </div>
                        <div>
                            <strong class="text-dark d-block">Instant Profile Creation</strong>
                            <span class="text-muted small">Set up your student account to save application progress anytime.</span>
                        </div>
                    </div>
                    <div class="auth-feature-item">
                        <div class="auth-feature-icon icon-blue">
                            <i class="fa-solid fa-file-arrow-up"></i>
                        </div>
                        <div>
                            <strong class="text-dark d-block">Digital Document Vault</strong>
                            <span class="text-muted small">Upload your 10th/12th marksheets, certificates, and ID proof securely.</span>
                        </div>
                    </div>
                    <div class="auth-feature-item">
                        <div class="auth-feature-icon icon-blue">
                            <i class="fa-solid fa-bell"></i>
                        </div>
                        <div>
                            <strong class="text-dark d-block">Live Status Updates</strong>
                            <span class="text-muted small">Receive notifications as staff review and verify your submitted credentials.</span>
                        </div>
                    </div>
                </div>

                <div class="auth-support-box text-start d-flex align-items-center gap-3">
                    <div class="fs-4 text-primary">
                        <i class="fa-solid fa-circle-question"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block fw-semibold">Questions about Registration?</small>
                        <span class="small text-dark fw-bold">Reach out to our helpline at <a href="mailto:admissions@statecollege.edu" class="text-decoration-underline">admissions@statecollege.edu</a></span>
                    </div>
                </div>
            </div>

            <!-- Right Auth Form Card Column -->
            <div class="col-12 col-lg-6">
                <div class="auth-card">
                    <div class="auth-card-header">
                        <div class="auth-card-logo">
                            <i class="fa-solid fa-user-graduate"></i>
                        </div>
                        <h4 class="fw-extrabold text-dark mb-1">Create Account</h4>
                        <p class="text-muted small mb-0">Join State College of Technology Admission Portal</p>
                    </div>

                    <?php render_alert($error_msg, 'danger', false, true); ?>

                    <form action="student_register.php" method="POST">
                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold text-dark small">Full Name</label>
                            <div class="input-group auth-input-group">
                                <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                                <input type="text" class="form-control" id="name" name="name" placeholder="John Doe" value="<?php echo e($name ?? ''); ?>" required autofocus>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold text-dark small">Email Address</label>
                            <div class="input-group auth-input-group">
                                <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" placeholder="student@example.com" value="<?php echo e($email ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold text-dark small">Password <span class="text-muted fw-normal">(Min. 6 chars)</span></label>
                            <div class="input-group auth-input-group has-toggle">
                                <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Create a password" required minlength="6">
                                <button type="button" class="password-toggle-btn" onclick="togglePassword('password', this)" title="Toggle password visibility">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="confirm_password" class="form-label fw-semibold text-dark small">Confirm Password</label>
                            <div class="input-group auth-input-group has-toggle">
                                <span class="input-group-text"><i class="fa-solid fa-shield-halved"></i></span>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required>
                                <button type="button" class="password-toggle-btn" onclick="togglePassword('confirm_password', this)" title="Toggle password visibility">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-premium-primary w-100 py-2.5 fw-bold text-center">
                            <i class="fa-solid fa-user-plus me-2"></i>Register Now
                        </button>
                    </form>

                    <div class="text-center mt-4 pt-3 border-top border-slate-200">
                        <span class="text-muted small">Already have an account?</span>
                        <a href="login.php?role=student" class="text-decoration-none small fw-bold ms-1 text-primary">Login here</a>
                    </div>

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