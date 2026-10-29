<?php
require_once '../includes/db_connect.php';
require_once '../includes/auth.php';

check_access('student');

$user_id = $_SESSION['user_id'];
$error_msg = "";
$success_msg = "";

$student = null;
$has_record = false;
$courses = [];
$inquiry = null;
$auto_fetched_from_inquiry = false;

try {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $user_id]);
    $student = $stmt->fetch();

    if ($student) {
        $has_record = true;

        if ($student['is_submitted'] == 1) {
            header("Location: dashboard.php");
            exit;
        }
    } else {
        // Auto-fetch inquiry details for this student
        $inq_stmt = $pdo->prepare("SELECT * FROM inquiries WHERE user_id = :user_id OR email = :email ORDER BY inquiry_id DESC LIMIT 1");
        $inq_stmt->execute([
            'user_id' => $user_id,
            'email' => $_SESSION['email'] ?? ''
        ]);
        $inquiry = $inq_stmt->fetch();
        if ($inquiry) {
            $auto_fetched_from_inquiry = true;
        }
    }

    $course_stmt = $pdo->query("SELECT * FROM courses ORDER BY course_name ASC");
    $courses = $course_stmt->fetchAll();
} catch (PDOException $e) {
    $error_msg = "Database Error: " . $e->getMessage();
}

// Initialize form data with existing record, inquiry data, or defaults
$form_data = [
    'full_name'          => $has_record ? ($student['full_name'] ?? '') : ($inquiry['name'] ?? $_SESSION['name'] ?? ''),
    'father_name'        => $has_record ? ($student['father_name'] ?? '') : '',
    'mother_name'        => $has_record ? ($student['mother_name'] ?? '') : '',
    'gender'             => $has_record ? ($student['gender'] ?? '') : '',
    'dob'                => $has_record ? ($student['dob'] ?? '') : '',
    'category'           => $has_record ? ($student['category'] ?? '') : '',
    'mobile'             => $has_record ? ($student['mobile'] ?? '') : ($inquiry['mobile'] ?? ''),
    'address'            => $has_record ? ($student['address'] ?? '') : '',
    'city'               => $has_record ? ($student['city'] ?? '') : '',
    'state'              => $has_record ? ($student['state'] ?? '') : '',
    'pincode'            => $has_record ? ($student['pincode'] ?? '') : '',
    'tenth_percentage'   => $has_record ? ($student['tenth_percentage'] ?? '') : '',
    'twelfth_percentage' => $has_record ? ($student['twelfth_percentage'] ?? '') : '',
    'school_name'        => $has_record ? ($student['school_name'] ?? '') : '',
    'passing_year'       => $has_record ? ($student['passing_year'] ?? '') : date('Y'),
    'course_id'          => $has_record ? ($student['course_id'] ?? '') : ($inquiry['course_id'] ?? ''),
];

$field_errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        $error_msg = "Security validation failed. Please refresh the page and try again.";
    } else {
        // 1. Capture ALL submitted values so valid data is never wiped
    $form_data['full_name']          = trim($_POST['full_name'] ?? '');
    $form_data['father_name']        = trim($_POST['father_name'] ?? '');
    $form_data['mother_name']        = trim($_POST['mother_name'] ?? '');
    $form_data['gender']             = trim($_POST['gender'] ?? '');
    $form_data['dob']                = trim($_POST['dob'] ?? '');
    $form_data['category']           = trim($_POST['category'] ?? '');
    $form_data['mobile']             = preg_replace('/[^0-9]/', '', trim($_POST['mobile'] ?? ''));
    $form_data['address']            = trim($_POST['address'] ?? '');
    $form_data['city']               = trim($_POST['city'] ?? '');
    $form_data['state']              = trim($_POST['state'] ?? '');
    $form_data['pincode']            = preg_replace('/[^0-9]/', '', trim($_POST['pincode'] ?? ''));
    $form_data['tenth_percentage']   = trim($_POST['tenth_percentage'] ?? '');
    $form_data['twelfth_percentage'] = trim($_POST['twelfth_percentage'] ?? '');
    $form_data['school_name']        = trim($_POST['school_name'] ?? '');
    $form_data['passing_year']       = trim($_POST['passing_year'] ?? '');
    $form_data['course_id']          = intval($_POST['course_id'] ?? 0);

    // 2. Validate compulsory fields
    $field_labels = [
        'full_name'          => 'Student Full Name',
        'father_name'        => 'Father\'s Name',
        'mother_name'        => 'Mother\'s Name',
        'gender'             => 'Gender',
        'dob'                => 'Date of Birth',
        'category'           => 'Category',
        'mobile'             => 'Mobile Number',
        'address'            => 'Correspondence Address',
        'city'               => 'City',
        'state'              => 'State',
        'pincode'            => 'Pincode',
        'tenth_percentage'   => '10th Percentage',
        'twelfth_percentage' => '12th Percentage',
        'school_name'        => 'School / Board Name',
        'passing_year'       => 'Passing Year',
        'course_id'          => 'Preferred Course'
    ];

    $missing_fields = [];
    foreach ($field_labels as $f_key => $f_lbl) {
        if ($form_data[$f_key] === '' || $form_data[$f_key] === null || ($f_key === 'course_id' && $form_data['course_id'] <= 0)) {
            $field_errors[$f_key] = "{$f_lbl} is compulsory.";
            $missing_fields[] = $f_lbl;
        }
    }

    // 3. Specific Field Validations:
    // If a specific field fails validation, we ONLY clear THAT specific field ($form_data[$field] = ''),
    // while leaving all other fields filled and intact.

    // 12th Percentage Validation
    if (!empty($form_data['twelfth_percentage'])) {
        $twelfth_val = floatval($form_data['twelfth_percentage']);
        if ($twelfth_val < 35.0) {
            $msg = "Eligibility Alert: You must have at least 35% in 12th standard to apply.";
            $error_msg = $msg;
            $field_errors['twelfth_percentage'] = $msg;
            $form_data['twelfth_percentage'] = ''; // Remove ONLY the erroneous detail
        } elseif ($twelfth_val > 100.0) {
            $msg = "12th percentage cannot exceed 100%.";
            $error_msg = $error_msg ?: $msg;
            $field_errors['twelfth_percentage'] = $msg;
            $form_data['twelfth_percentage'] = '';
        }
    }

    // 10th Percentage Validation
    if (!empty($form_data['tenth_percentage'])) {
        $tenth_val = floatval($form_data['tenth_percentage']);
        if ($tenth_val <= 0 || $tenth_val > 100.0) {
            $msg = "Please enter a valid 10th percentage between 1% and 100%.";
            $error_msg = $error_msg ?: $msg;
            $field_errors['tenth_percentage'] = $msg;
            $form_data['tenth_percentage'] = ''; // Remove ONLY the erroneous detail
        }
    }

    // Mobile Validation & Duplicate Check
    if (!empty($form_data['mobile'])) {
        if (strlen($form_data['mobile']) < 10 || strlen($form_data['mobile']) > 12) {
            $msg = "Please enter a valid 10 to 12 digit mobile number.";
            $error_msg = $error_msg ?: $msg;
            $field_errors['mobile'] = $msg;
            $form_data['mobile'] = ''; // Remove ONLY the erroneous detail
        } else {
            try {
                $dup_check_sql = "SELECT student_id FROM students WHERE mobile = :mobile";
                if ($has_record) {
                    $dup_check_sql .= " AND student_id != :student_id";
                }
                $dup_stmt = $pdo->prepare($dup_check_sql);
                $dup_params = ['mobile' => $form_data['mobile']];
                if ($has_record) {
                    $dup_params['student_id'] = $student['student_id'];
                }
                $dup_stmt->execute($dup_params);

                if ($dup_stmt->rowCount() > 0) {
                    $msg = "Mobile number ({$form_data['mobile']}) is already registered by another applicant.";
                    $error_msg = $error_msg ?: $msg;
                    $field_errors['mobile'] = $msg;
                    $form_data['mobile'] = ''; // Remove ONLY the erroneous detail
                }
            } catch (PDOException $e) {
                $error_msg = "Database Error: " . $e->getMessage();
            }
        }
    }

    // Pincode Validation
    if (!empty($form_data['pincode'])) {
        if (strlen($form_data['pincode']) < 4 || strlen($form_data['pincode']) > 10) {
            $msg = "Please enter a valid numeric pincode (4 to 10 digits).";
            $error_msg = $error_msg ?: $msg;
            $field_errors['pincode'] = $msg;
            $form_data['pincode'] = ''; // Remove ONLY the erroneous detail
        }
    }

    if (!empty($missing_fields) && empty($error_msg)) {
        $error_msg = "Please fill in all compulsory fields: " . implode(', ', array_slice($missing_fields, 0, 3)) . (count($missing_fields) > 3 ? '...' : '.');
    }

    // 4. If No Errors -> Save and Continue
    if (empty($error_msg) && empty($field_errors)) {
        try {
            $full_name          = $form_data['full_name'];
            $father_name        = $form_data['father_name'];
            $mother_name        = $form_data['mother_name'];
            $gender             = $form_data['gender'];
            $dob                = $form_data['dob'];
            $category           = $form_data['category'];
            $mobile             = $form_data['mobile'];
            $address            = $form_data['address'];
            $city               = $form_data['city'];
            $state              = $form_data['state'];
            $pincode            = intval($form_data['pincode']);
            $tenth_percentage   = floatval($form_data['tenth_percentage']);
            $twelfth_percentage = floatval($form_data['twelfth_percentage']);
            $school_name        = $form_data['school_name'];
            $passing_year       = intval($form_data['passing_year']);
            $course_id          = intval($form_data['course_id']);

            if ($has_record) {
                $update_sql = "UPDATE students SET 
                    full_name = :full_name, father_name = :father_name, mother_name = :mother_name, gender = :gender, dob = :dob,
                    category = :category, mobile = :mobile, address = :address, city = :city,
                    state = :state, pincode = :pincode, tenth_percentage = :tenth_percentage,
                    twelfth_percentage = :twelfth_percentage, school_name = :school_name,
                    passing_year = :passing_year, course_id = :course_id 
                    WHERE student_id = :student_id";

                $update_stmt = $pdo->prepare($update_sql);
                $update_stmt->execute([
                    'full_name'          => $full_name,
                    'father_name'        => $father_name,
                    'mother_name'        => $mother_name,
                    'gender'             => $gender,
                    'dob'                => $dob,
                    'category'           => $category,
                    'mobile'             => $mobile,
                    'address'            => $address,
                    'city'               => $city,
                    'state'              => $state,
                    'pincode'            => $pincode,
                    'tenth_percentage'   => $tenth_percentage,
                    'twelfth_percentage' => $twelfth_percentage,
                    'school_name'        => $school_name,
                    'passing_year'       => $passing_year,
                    'course_id'          => $course_id,
                    'student_id'         => $student['student_id']
                ]);

                $success_msg = "Admission details updated successfully.";
            } else {
                $admission_no = generate_admission_no($pdo);

                $insert_sql = "INSERT INTO students (
                    user_id, admission_no, full_name, father_name, mother_name, gender, dob, category, mobile, email,
                    address, city, state, pincode, tenth_percentage, twelfth_percentage, school_name,
                    passing_year, course_id, status, is_submitted
                ) VALUES (
                    :user_id, :admission_no, :full_name, :father_name, :mother_name, :gender, :dob, :category, :mobile, :email,
                    :address, :city, :state, :pincode, :tenth_percentage, :twelfth_percentage, :school_name,
                    :passing_year, :course_id, 'Pending', 0
                )";

                $insert_stmt = $pdo->prepare($insert_sql);
                $insert_stmt->execute([
                    'user_id'            => $user_id,
                    'admission_no'       => $admission_no,
                    'full_name'          => $full_name,
                    'father_name'        => $father_name,
                    'mother_name'        => $mother_name,
                    'gender'             => $gender,
                    'dob'                => $dob,
                    'category'           => $category,
                    'mobile'             => $mobile,
                    'email'              => $_SESSION['email'],
                    'address'            => $address,
                    'city'               => $city,
                    'state'              => $state,
                    'pincode'            => $pincode,
                    'tenth_percentage'   => $tenth_percentage,
                    'twelfth_percentage' => $twelfth_percentage,
                    'school_name'        => $school_name,
                    'passing_year'       => $passing_year,
                    'course_id'          => $course_id
                ]);

                $success_msg = "Details submitted successfully. Proceeding to document upload...";
            }

            // Continue to Step 2: Upload Documents
            header("Location: upload.php");
            exit;
        } catch (PDOException $e) {
            $error_msg = "Database Error: " . $e->getMessage();
        }
    }
}
}

$page_title = $has_record ? "Edit Admission Form" : "Fill Admission Form";
include '../includes/header.php';
?>

<div class="wrapper">
    <?php include '../includes/sidebar.php'; ?>

    <div id="content">
        <?php render_topbar(); ?>

        <div class="container-fluid">
            <?php render_page_header($page_title); ?>

            <div class="status-card-premium status-card-step">
                <div class="status-stepper-premium">
                    <div class="status-stepper-step-premium active">
                        <div class="status-stepper-dot-premium">1</div>
                        <div class="status-stepper-label-premium">Fill Details</div>
                    </div>
                    <div class="status-stepper-step-premium">
                        <div class="status-stepper-dot-premium">2</div>
                        <div class="status-stepper-label-premium">Upload Docs</div>
                    </div>
                    <div class="status-stepper-step-premium">
                        <div class="status-stepper-dot-premium">3</div>
                        <div class="status-stepper-label-premium">Payment</div>
                    </div>
                    <div class="status-stepper-step-premium">
                        <div class="status-stepper-dot-premium">4</div>
                        <div class="status-stepper-label-premium">Submit</div>
                    </div>
                </div>
            </div>

            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger shadow-sm border-0 d-flex align-items-center mb-4" role="alert">
                    <i class="fa-solid fa-triangle-exclamation fs-4 me-3 flex-shrink-0"></i>
                    <div>
                        <strong>Please check the highlighted field:</strong> <?php echo e($error_msg); ?>
                        <div class="small text-muted mt-1">Your other valid details have been preserved. You only need to correct the highlighted input.</div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($auto_fetched_from_inquiry && empty($_POST)): ?>
                <div class="alert alert-info border-0 shadow-sm d-flex align-items-center mb-4" role="alert">
                    <i class="fa-solid fa-wand-magic-sparkles text-primary fs-3 me-3 flex-shrink-0"></i>
                    <div>
                        <strong class="text-dark d-block">Inquiry Details Auto-Fetched:</strong>
                        <span class="small">We have automatically loaded your <strong>Full Name</strong>, <strong>Email</strong>, <strong>Mobile Number</strong>, and <strong>Selected Course</strong> from your admission inquiry form. Please fill in the remaining personal, address, and academic information below.</span>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="fa-solid fa-file-invoice me-2 text-primary"></i>Admission Registration Form
                    </h6>
                </div>
                <div class="card-body p-4">
                    <form action="apply.php" method="POST" id="admissionForm">
                        <?php echo csrf_field(); ?>

                        <!-- SECTION 1: Personal Information -->
                        <h5 class="fw-bold text-primary border-bottom pb-2 mb-4">
                            <i class="fa-solid fa-user me-2"></i>Personal Information
                        </h5>
                        <div class="row g-3 mb-4">

                            <!-- Full Name -->
                            <div class="col-12 col-md-6">
                                <label for="full_name" class="form-label small fw-semibold text-dark">Student Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?php echo isset($field_errors['full_name']) ? 'is-invalid' : ''; ?>" id="full_name" name="full_name"
                                    value="<?php echo e($form_data['full_name']); ?>" required>
                                <?php if (isset($field_errors['full_name'])): ?>
                                    <div class="invalid-feedback"><?php echo e($field_errors['full_name']); ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Father's Name -->
                            <div class="col-12 col-sm-6 col-md-3">
                                <label for="father_name" class="form-label small fw-semibold text-dark">Father's Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?php echo isset($field_errors['father_name']) ? 'is-invalid' : ''; ?>" id="father_name" name="father_name"
                                    value="<?php echo e($form_data['father_name']); ?>" placeholder="Father's full name" required>
                                <?php if (isset($field_errors['father_name'])): ?>
                                    <div class="invalid-feedback"><?php echo e($field_errors['father_name']); ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Mother's Name -->
                            <div class="col-12 col-sm-6 col-md-3">
                                <label for="mother_name" class="form-label small fw-semibold text-dark">Mother's Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?php echo isset($field_errors['mother_name']) ? 'is-invalid' : ''; ?>" id="mother_name" name="mother_name"
                                    value="<?php echo e($form_data['mother_name']); ?>" placeholder="Mother's full name" required>
                                <?php if (isset($field_errors['mother_name'])): ?>
                                    <div class="invalid-feedback"><?php echo e($field_errors['mother_name']); ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Gender -->
                            <div class="col-12 col-sm-6 col-md-4">
                                <label for="gender" class="form-label small fw-semibold text-dark">Gender <span class="text-danger">*</span></label>
                                <select class="form-select form-control <?php echo isset($field_errors['gender']) ? 'is-invalid' : ''; ?>" id="gender" name="gender" required>
                                    <option value="">Choose...</option>
                                    <option value="Male" <?php echo ($form_data['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo ($form_data['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                                    <option value="Other" <?php echo ($form_data['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                                <?php if (isset($field_errors['gender'])): ?>
                                    <div class="invalid-feedback"><?php echo e($field_errors['gender']); ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Date of Birth -->
                            <div class="col-12 col-sm-6 col-md-4">
                                <label for="dob" class="form-label small fw-semibold text-dark">Date of Birth <span class="text-danger">*</span></label>
                                <input type="date" class="form-control <?php echo isset($field_errors['dob']) ? 'is-invalid' : ''; ?>" id="dob" name="dob"
                                    value="<?php echo e($form_data['dob']); ?>" required>
                                <?php if (isset($field_errors['dob'])): ?>
                                    <div class="invalid-feedback"><?php echo e($field_errors['dob']); ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Category -->
                            <div class="col-12 col-sm-6 col-md-4">
                                <label for="category" class="form-label small fw-semibold text-dark">Category <span class="text-danger">*</span></label>
                                <select class="form-select form-control <?php echo isset($field_errors['category']) ? 'is-invalid' : ''; ?>" id="category" name="category" required>
                                    <option value="">Choose...</option>
                                    <option value="General" <?php echo ($form_data['category'] === 'General') ? 'selected' : ''; ?>>General</option>
                                    <option value="OBC" <?php echo ($form_data['category'] === 'OBC') ? 'selected' : ''; ?>>OBC</option>
                                    <option value="SC" <?php echo ($form_data['category'] === 'SC') ? 'selected' : ''; ?>>SC</option>
                                    <option value="ST" <?php echo ($form_data['category'] === 'ST') ? 'selected' : ''; ?>>ST</option>
                                    <option value="Other" <?php echo ($form_data['category'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                                <?php if (isset($field_errors['category'])): ?>
                                    <div class="invalid-feedback"><?php echo e($field_errors['category']); ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Mobile Number -->
                            <div class="col-12 col-sm-6 col-md-4">
                                <label for="mobile" class="form-label small fw-semibold text-dark">Mobile Number <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control <?php echo isset($field_errors['mobile']) ? 'is-invalid' : ''; ?>" id="mobile" name="mobile"
                                    value="<?php echo e($form_data['mobile']); ?>" placeholder="10-digit mobile" required>
                                <?php if (isset($field_errors['mobile'])): ?>
                                    <div class="invalid-feedback"><?php echo e($field_errors['mobile']); ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Registered Email (Read-only) -->
                            <div class="col-12 col-md-4">
                                <label for="email" class="form-label small fw-semibold text-dark">Email (Registered)</label>
                                <input type="email" class="form-control bg-light" id="email" name="email"
                                    value="<?php echo e($_SESSION['email']); ?>" readonly>
                            </div>

                            <!-- Correspondence Address -->
                            <div class="col-12 col-md-4">
                                <label for="address" class="form-label small fw-semibold text-dark">Correspondence Address <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?php echo isset($field_errors['address']) ? 'is-invalid' : ''; ?>" id="address" name="address"
                                    value="<?php echo e($form_data['address']); ?>" placeholder="Street, landmark, locality" required>
                                <?php if (isset($field_errors['address'])): ?>
                                    <div class="invalid-feedback"><?php echo e($field_errors['address']); ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- City -->
                            <div class="col-12 col-sm-4">
                                <label for="city" class="form-label small fw-semibold text-dark">City <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?php echo isset($field_errors['city']) ? 'is-invalid' : ''; ?>" id="city" name="city"
                                    value="<?php echo e($form_data['city']); ?>" placeholder="City name" required>
                                <?php if (isset($field_errors['city'])): ?>
                                    <div class="invalid-feedback"><?php echo e($field_errors['city']); ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- State -->
                            <div class="col-12 col-sm-4">
                                <label for="state" class="form-label small fw-semibold text-dark">State <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?php echo isset($field_errors['state']) ? 'is-invalid' : ''; ?>" id="state" name="state"
                                    value="<?php echo e($form_data['state']); ?>" placeholder="State name" required>
                                <?php if (isset($field_errors['state'])): ?>
                                    <div class="invalid-feedback"><?php echo e($field_errors['state']); ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Pincode -->
                            <div class="col-12 col-sm-4">
                                <label for="pincode" class="form-label small fw-semibold text-dark">Pincode <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?php echo isset($field_errors['pincode']) ? 'is-invalid' : ''; ?>" id="pincode" name="pincode"
                                    value="<?php echo e($form_data['pincode']); ?>" placeholder="6-digit pincode" required>
                                <?php if (isset($field_errors['pincode'])): ?>
                                    <div class="invalid-feedback"><?php echo e($field_errors['pincode']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>


                        <!-- SECTION 2: Academic Details -->
                        <h5 class="fw-bold text-primary border-bottom pb-2 mb-4">
                            <i class="fa-solid fa-user-graduate me-2"></i>Academic Details
                        </h5>
                        <div class="row g-3 mb-4">

                            <!-- 10th Percentage -->
                            <div class="col-12 col-sm-6 col-md-3">
                                <label for="tenth_percentage" class="form-label small fw-semibold text-dark">10th Std Percentage (%) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" max="100" class="form-control <?php echo isset($field_errors['tenth_percentage']) ? 'is-invalid' : ''; ?>" id="tenth_percentage" name="tenth_percentage"
                                    value="<?php echo e($form_data['tenth_percentage']); ?>" placeholder="e.g. 85.50" required>
                                <?php if (isset($field_errors['tenth_percentage'])): ?>
                                    <div class="invalid-feedback"><?php echo e($field_errors['tenth_percentage']); ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- 12th Percentage (Eligibility min 35%) -->
                            <div class="col-12 col-sm-6 col-md-3">
                                <label for="twelfth_percentage" class="form-label small fw-semibold text-dark">12th Std Percentage (%) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" max="100" class="form-control <?php echo isset($field_errors['twelfth_percentage']) ? 'is-invalid' : ''; ?>" id="twelfth_percentage" name="twelfth_percentage"
                                    value="<?php echo e($form_data['twelfth_percentage']); ?>" placeholder="e.g. 78.40" required>
                                <small class="text-muted d-block">Eligibility: Min. 35% required</small>
                                <?php if (isset($field_errors['twelfth_percentage'])): ?>
                                    <div class="invalid-feedback d-block"><?php echo e($field_errors['twelfth_percentage']); ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- School / Board Name -->
                            <div class="col-12 col-sm-8 col-md-4">
                                <label for="school_name" class="form-label small fw-semibold text-dark">Previous School / Board Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?php echo isset($field_errors['school_name']) ? 'is-invalid' : ''; ?>" id="school_name" name="school_name"
                                    value="<?php echo e($form_data['school_name']); ?>" placeholder="School name / Board" required>
                                <?php if (isset($field_errors['school_name'])): ?>
                                    <div class="invalid-feedback"><?php echo e($field_errors['school_name']); ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Passing Year -->
                            <div class="col-12 col-sm-4 col-md-2">
                                <label for="passing_year" class="form-label small fw-semibold text-dark">Passing Year <span class="text-danger">*</span></label>
                                <input type="number" min="2000" max="2026" class="form-control <?php echo isset($field_errors['passing_year']) ? 'is-invalid' : ''; ?>" id="passing_year" name="passing_year"
                                    value="<?php echo e($form_data['passing_year']); ?>" required>
                                <?php if (isset($field_errors['passing_year'])): ?>
                                    <div class="invalid-feedback"><?php echo e($field_errors['passing_year']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>


                        <!-- SECTION 3: Course Selection -->
                        <h5 class="fw-bold text-primary border-bottom pb-2 mb-4">
                            <i class="fa-solid fa-book-bookmark me-2"></i>Course Selection
                        </h5>
                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <label for="course_id" class="form-label small fw-semibold text-dark">Select Preferred Program <span class="text-danger">*</span></label>
                                <select class="form-select form-control <?php echo isset($field_errors['course_id']) ? 'is-invalid' : ''; ?>" id="course_id" name="course_id" required>
                                    <option value="">Select a Course...</option>
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?php echo $course['course_id']; ?>"
                                            <?php echo ((string)$form_data['course_id'] === (string)$course['course_id']) ? 'selected' : ''; ?>>
                                            <?php echo e($course['course_name']) . " (" . e($course['department']) . " - " . e($course['semester']) . ")"; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($field_errors['course_id'])): ?>
                                    <div class="invalid-feedback"><?php echo e($field_errors['course_id']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>


                        <div class="d-flex justify-content-between mt-5 pt-3 border-top">
                            <a href="dashboard.php" class="btn btn-outline-secondary py-2">
                                <i class="fa-solid fa-arrow-left me-1"></i>Cancel & Back
                            </a>
                            <button type="submit" class="btn btn-premium-primary px-5 py-2 fw-bold shadow-sm">
                                <i class="fa-solid fa-floppy-disk me-1"></i>Save Details & Continue &rarr;
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('admissionForm');
        if (!form) return;

        const userId = '<?php echo $_SESSION['user_id']; ?>';
        const storageKey = `admission_form_draft_${userId}`;

        <?php if (!empty($field_errors)): ?>
            // If the server reported validation errors, remove only the erroneous fields from client-side draft
            try {
                const draft = JSON.parse(localStorage.getItem(storageKey) || '{}');
                <?php foreach (array_keys($field_errors) as $errKey): ?>
                    delete draft['<?php echo $errKey; ?>'];
                <?php endforeach; ?>
                localStorage.setItem(storageKey, JSON.stringify(draft));
            } catch (e) {}
        <?php else: ?>
            // If opening a clean form, restore saved drafts for fields that are currently empty
            try {
                const saved = localStorage.getItem(storageKey);
                if (saved) {
                    const data = JSON.parse(saved);
                    for (const key in data) {
                        const field = form.elements[key];
                        if (field && key !== 'email') {
                            // Only restore if PHP didn't already populate a value
                            if (!field.value || field.value.trim() === '') {
                                if (field.type === 'checkbox' || field.type === 'radio') {
                                    field.checked = (field.value === data[key]);
                                } else {
                                    field.value = data[key];
                                }
                            }
                        }
                    }
                }
            } catch (e) {}
        <?php endif; ?>

        // Save progress to localStorage as the user types
        function saveProgress() {
            const data = {};
            const formData = new FormData(form);
            for (const [key, value] of formData.entries()) {
                data[key] = value;
            }
            localStorage.setItem(storageKey, JSON.stringify(data));
        }

        form.addEventListener('input', saveProgress);
        form.addEventListener('change', saveProgress);

        // Client-side quick check
        form.addEventListener('submit', function(event) {
            const percentageInput = document.getElementById('twelfth_percentage');
            if (percentageInput && percentageInput.value !== '') {
                const percentage = parseFloat(percentageInput.value);
                if (percentage < 35.0) {
                    alert("Eligibility Alert: You must have scored a minimum of 35% in your 12th standard examinations to apply.");
                    percentageInput.value = ''; // Clear only the erroneous field
                    percentageInput.classList.add('is-invalid');
                    percentageInput.focus();
                    event.preventDefault();
                    return false;
                }
            }
            // Note: DO NOT clear localStorage here before server validates!
            // It will be cleared once server saves successfully and redirects to upload.php.
        });
    });
</script>

<?php include '../includes/footer.php'; ?>
