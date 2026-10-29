<?php
require_once 'includes/db_connect.php';
require_once 'includes/auth.php';
require_once 'includes/mailer.php';

redirect_if_logged_in();

$error_msg = '';
$success_msg = '';
$inquiry_completed = false;
$completed_data = [];

// Fetch courses list for dropdown
$courses = [];
$course_id = intval($_POST['course_id'] ?? ($_GET['course_id'] ?? 0));
try {
    $c_stmt = $pdo->query("SELECT course_id, course_name, department, semester FROM courses ORDER BY course_name ASC");
    $courses = $c_stmt->fetchAll();
} catch (PDOException $e) {
    $error_msg = "Could not load courses: " . $e->getMessage();
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!validate_csrf()) {
        $error_msg = "Security validation failed. Please refresh the page and try again.";
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $mobile = preg_replace('/[^0-9]/', '', trim($_POST['mobile'] ?? ''));
        $course_id = intval($_POST['course_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');

    // Validation
    if (empty($name) || empty($email) || empty($mobile) || empty($course_id)) {
        $error_msg = "Please fill in all required fields (Name, Email, Mobile, and Course).";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Please enter a valid email address.";
    } elseif (strlen($mobile) < 10 || strlen($mobile) > 12) {
        $error_msg = "Please enter a valid 10 to 12 digit mobile number.";
    } else {
        try {
            // Check if email already registered in users table
            $stmt = $pdo->prepare("SELECT user_id, role FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $existing_user = $stmt->fetch();

            if ($existing_user) {
                $error_msg = "This email address is already registered. Please <a href='login.php?role=student&email=" . urlencode($email) . "' class='alert-link fw-bold'>click here to login</a>.";
            } else {
                // Check if mobile is already used in students table
                $chk_mob = $pdo->prepare("SELECT student_id FROM students WHERE mobile = :mobile");
                $chk_mob->execute(['mobile' => $mobile]);
                if ($chk_mob->rowCount() > 0) {
                    $error_msg = "This mobile number is already registered with another student application.";
                } else {
                    // 1. Auto-generate secure password
                    $generated_password = generate_student_password(8);
                    $hashed_password = password_hash($generated_password, PASSWORD_DEFAULT);

                    // 2. Insert into users table
                    $insert_user = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, 'student') RETURNING user_id");
                    $insert_user->execute([
                        'name' => $name,
                        'email' => $email,
                        'password' => $hashed_password
                    ]);
                    $new_user_id = $insert_user->fetchColumn();

                    // Fallback if RETURNING is not supported (e.g. MySQL)
                    if (!$new_user_id) {
                        $new_user_id = $pdo->lastInsertId();
                    }

                    // 3. Insert into inquiries table
                    $insert_inq = $pdo->prepare("INSERT INTO inquiries (user_id, name, email, mobile, course_id, message, status) VALUES (:user_id, :name, :email, :mobile, :course_id, :message, 'New')");
                    $insert_inq->execute([
                        'user_id' => $new_user_id,
                        'name' => $name,
                        'email' => $email,
                        'mobile' => $mobile,
                        'course_id' => $course_id,
                        'message' => $message
                    ]);

                    // 4. Fetch course name for email
                    $cname_stmt = $pdo->prepare("SELECT course_name FROM courses WHERE course_id = :cid");
                    $cname_stmt->execute(['cid' => $course_id]);
                    $course_name = $cname_stmt->fetchColumn() ?: 'General Admission Program';

                    // 5. Send Email with credentials
                    $mail_res = send_inquiry_credentials_email($name, $email, $mobile, $course_name, $generated_password);

                    $inquiry_completed = true;
                    $completed_data = [
                        'name' => $name,
                        'email' => $email,
                        'mobile' => $mobile,
                        'course_name' => $course_name,
                        'mail_res' => $mail_res
                    ];
                }
            }
        } catch (PDOException $e) {
            $error_msg = "Database Error: " . $e->getMessage();
        }
    }
    }
}

$is_public_page = true;
$body_class = "login-page-body role-student";
$page_title = "Admission Inquiry Form";
include 'includes/header.php';
?>

<section class="sct-auth-page">
    <div class="container">
        <div class="row align-items-start g-4 g-lg-5">

            <!-- LEFT: campus image + college info -->
            <div class="col-lg-5 d-none d-lg-block" style="position:sticky;top:100px;">
                <div class="sct-auth-left">
                    <div class="sct-auth-img-wrap">
                        <img src="assets/images/university_portal.png"
                             alt="State College of Technology Campus"
                             class="sct-auth-img">
                        <div class="sct-auth-img-overlay">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="fa-solid fa-graduation-cap fs-4 text-white"></i>
                                <span class="fw-bold text-white fs-5">SCT Admissions</span>
                            </div>
                            <p class="text-white-50 small mb-0">State College of Technology &mdash; Est. 1998</p>
                            <div class="sct-auth-overlay-tags mt-3 d-flex flex-wrap gap-2">
                                <span class="sct-overlay-tag">NAAC Accredited</span>
                                <span class="sct-overlay-tag">UGC Approved</span>
                                <span class="sct-overlay-tag">AICTE Approved</span>
                            </div>
                        </div>
                    </div>

                    <!-- Why SCT bullets -->
                    <div class="sct-auth-bullets mt-3">
                        <div class="sct-auth-bullet">
                            <i class="fa-solid fa-circle-check text-primary"></i>
                            <span>Get portal access instantly after submitting this form</span>
                        </div>
                        <div class="sct-auth-bullet">
                            <i class="fa-solid fa-circle-check text-primary"></i>
                            <span>Login credentials sent directly to your email</span>
                        </div>
                        <div class="sct-auth-bullet">
                            <i class="fa-solid fa-circle-check text-primary"></i>
                            <span>94% placement rate &mdash; 120+ recruiting partners</span>
                        </div>
                        <div class="sct-auth-bullet">
                            <i class="fa-solid fa-circle-check text-primary"></i>
                            <span>100% online admission — no campus visit required</span>
                        </div>
                    </div>

                    <!-- Helpdesk -->
                    <div class="sct-auth-helpdesk mt-3">
                        <i class="fa-solid fa-headset"></i>
                        <span>Helpdesk: <strong>+91 (079) 2324-5678</strong> &bull;
                            <a href="mailto:admissions@statecollege.edu.in">admissions@statecollege.edu.in</a>
                        </span>
                    </div>
                </div>
            </div>

            <!-- RIGHT: form / success card -->
            <div class="col-12 col-lg-7">

                <?php if ($inquiry_completed): ?>
                <!-- SUCCESS STATE -->
                <div class="sct-auth-card text-center">
                    <div class="sct-success-icon mb-3">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <h3 class="fw-bold text-dark mb-2">Inquiry Submitted Successfully!</h3>
                    <p class="text-muted mb-4" style="max-width:480px;margin:auto;">
                        Your inquiry has been registered. Portal login credentials have been sent to
                        <strong class="text-dark font-monospace"><?php echo e($completed_data['email']); ?></strong>.
                        Check your inbox (and spam folder).
                    </p>

                    <!-- Summary -->
                    <div class="sct-summary-box text-start mb-4">
                        <div class="sct-summary-row">
                            <span class="sct-summary-label">Applicant Name</span>
                            <span class="sct-summary-val"><?php echo e($completed_data['name']); ?></span>
                        </div>
                        <div class="sct-summary-row">
                            <span class="sct-summary-label">Course Applied</span>
                            <span class="sct-summary-val"><?php echo e($completed_data['course_name']); ?></span>
                        </div>
                        <div class="sct-summary-row">
                            <span class="sct-summary-label">Mobile</span>
                            <span class="sct-summary-val"><?php echo e($completed_data['mobile']); ?></span>
                        </div>
                        <div class="sct-summary-row">
                            <span class="sct-summary-label">Email</span>
                            <span class="sct-summary-val font-monospace"><?php echo e($completed_data['email']); ?></span>
                        </div>
                        <div class="sct-summary-row border-0 pb-0">
                            <span class="sct-summary-label">Status</span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 fw-bold">
                                <i class="fa-solid fa-circle-check me-1"></i> Inquiry Registered
                            </span>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <a href="login.php?role=student&email=<?php echo urlencode($completed_data['email']); ?>"
                           class="sct-submit-btn sct-btn-primary">
                            <i class="fa-solid fa-right-to-bracket"></i> Proceed to Student Login
                        </a>
                        <a href="index.php" class="sct-btn sct-btn--ghost text-center justify-content-center">
                            <i class="fa-solid fa-house"></i> Back to Homepage
                        </a>
                    </div>
                </div>

                <?php else: ?>
                <!-- INQUIRY FORM -->
                <div class="sct-auth-card">

                    <div class="sct-auth-card-head">
                        <div class="sct-auth-card-logo">
                            <i class="fa-solid fa-file-pen"></i>
                        </div>
                        <div>
                            <h4 class="sct-auth-card-title">Admission Inquiry Form</h4>
                            <p class="sct-auth-card-sub">Academic Year 2026&ndash;27 &mdash; State College of Technology</p>
                        </div>
                    </div>

                    <?php render_alert($error_msg, 'danger', true, true); ?>

                    <form action="inquiry.php" method="POST" id="inquiryForm">
                        <?php echo csrf_field(); ?>
                        <div class="row g-3">

                            <!-- Full Name -->
                            <div class="col-12">
                                <label for="name" class="sct-form-label">
                                    Student Full Name <span class="text-danger">*</span>
                                </label>
                                <div class="input-group auth-input-group">
                                    <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                                    <input type="text" class="form-control" id="name" name="name"
                                        placeholder="e.g. Rahul Sharma"
                                        value="<?php echo e($name ?? ''); ?>" required autofocus>
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="col-12 col-md-6">
                                <label for="email" class="sct-form-label">
                                    Email Address <span class="text-danger">*</span>
                                </label>
                                <div class="input-group auth-input-group">
                                    <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email"
                                        placeholder="student@example.com"
                                        value="<?php echo e($email ?? ''); ?>" required>
                                </div>
                            </div>

                            <!-- Mobile -->
                            <div class="col-12 col-md-6">
                                <label for="mobile" class="sct-form-label">
                                    Mobile Number <span class="text-danger">*</span>
                                </label>
                                <div class="input-group auth-input-group">
                                    <span class="input-group-text"><i class="fa-solid fa-phone"></i></span>
                                    <input type="tel" class="form-control" id="mobile" name="mobile"
                                        placeholder="10-digit mobile number"
                                        pattern="[0-9]{10,12}" maxlength="12"
                                        value="<?php echo e($mobile ?? ''); ?>" required>
                                </div>
                            </div>

                            <!-- Course -->
                            <div class="col-12">
                                <label for="course_id" class="sct-form-label">
                                    Select Course of Interest <span class="text-danger">*</span>
                                </label>
                                <div class="input-group auth-input-group">
                                    <span class="input-group-text"><i class="fa-solid fa-graduation-cap"></i></span>
                                    <select class="form-select form-control" id="course_id" name="course_id" required>
                                        <option value="">-- Choose Degree / Program --</option>
                                        <?php foreach ($courses as $c): ?>
                                            <option value="<?php echo $c['course_id']; ?>"
                                                <?php echo (isset($course_id) && $course_id == $c['course_id']) ? 'selected' : ''; ?>>
                                                <?php echo e($c['course_name']) . ' (' . e($c['department']) . ')'; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Remarks -->
                            <div class="col-12">
                                <label for="message" class="sct-form-label">
                                    Questions / Remarks <span class="text-muted fw-normal">(Optional)</span>
                                </label>
                                <textarea class="form-control sct-textarea" id="message" name="message" rows="3"
                                    placeholder="Any questions about eligibility, fees or documents..."><?php echo e($message ?? ''); ?></textarea>
                            </div>

                            <!-- Submit -->
                            <div class="col-12 pt-1">
                                <button type="submit" class="sct-submit-btn sct-btn-primary">
                                    <i class="fa-solid fa-paper-plane"></i> Submit Admission Inquiry
                                </button>
                            </div>

                        </div>
                    </form>

                    <div class="sct-auth-card-footer">
                        <span class="text-muted small">Already registered?
                            <a href="login.php?role=student" class="fw-bold text-primary text-decoration-none">Student Login &rarr;</a>
                        </span>
                        <a href="index.php" class="text-muted small text-decoration-none">
                            <i class="fa-solid fa-arrow-left me-1"></i>Back to Home
                        </a>
                    </div>

                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
