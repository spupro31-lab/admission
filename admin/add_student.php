<?php
require_once '../includes/db_connect.php';
require_once '../includes/auth.php';


check_access('admin');

$error_msg = "";
$success_msg = "";


$courses = $pdo->query("SELECT * FROM courses ORDER BY course_name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];


    $full_name = trim($_POST['full_name']);
    $father_name = trim($_POST['father_name']);
    $mother_name = trim($_POST['mother_name']);
    $gender = $_POST['gender'];
    $dob = $_POST['dob'];
    $category = trim($_POST['category']);
    $mobile = trim($_POST['mobile']);
    $mobile = preg_replace('/[^0-9]/', '', trim($_POST['mobile'] ?? ''));
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $pincode = trim($_POST['pincode']);
    $pincode = preg_replace('/[^0-9]/', '', trim($_POST['pincode'] ?? ''));

    $tenth_percentage = floatval($_POST['tenth_percentage']);
    $twelfth_percentage = floatval($_POST['twelfth_percentage']);
    $school_name = trim($_POST['school_name']);
    $passing_year = intval($_POST['passing_year']);

    $course_id = intval($_POST['course_id']);


    if (empty($name) || empty($email) || empty($password) || empty($full_name) || empty($father_name) || empty($mother_name) || empty($mobile) || empty($address) || empty($school_name) || empty($course_id)) {
        $error_msg = "Please fill in all mandatory account and admission fields.";
    } elseif ($twelfth_percentage < 35) {
        $error_msg = "Eligibility Alert: Applicant must have at least 35% in 12th standard.";
    } elseif (strlen($mobile) < 10 || strlen($mobile) > 12) {
        $error_msg = "Please enter a valid 10 to 12 digit mobile number.";
    } elseif (strlen($pincode) < 4 || strlen($pincode) > 10) {
        $error_msg = "Please enter a valid numeric pincode.";
    } else {
        try {

            $chk_email = $pdo->prepare("SELECT user_id FROM users WHERE email = :email");
            $chk_email->execute(['email' => $email]);


            $chk_mobile = $pdo->prepare("SELECT student_id FROM students WHERE mobile = :mobile");
            $chk_mobile->execute(['mobile' => $mobile]);

            if ($chk_email->rowCount() > 0) {
                $error_msg = "The email address is already registered.";
            } elseif ($chk_mobile->rowCount() > 0) {
                $error_msg = "The mobile number is already registered.";
            } else {
                $pdo->beginTransaction();


                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $ins_user = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, 'student')");
                $ins_user->execute([
                    'name' => $name,
                    'email' => $email,
                    'password' => $hashed_password
                ]);
                $new_user_id = $pdo->lastInsertId();


                $year = date('Y');
                $prefix = "ADM" . $year;
                $admission_no = generate_admission_no($pdo);

                $max_stmt = $pdo->prepare("SELECT admission_no FROM students WHERE admission_no LIKE :prefix ORDER BY admission_no DESC LIMIT 1");
                $max_stmt->execute(['prefix' => $prefix . "%"]);
                $row = $max_stmt->fetch();

                if ($row) {
                    $last_num = (int) substr($row['admission_no'], 7);
                    $seq_num = $last_num + 1;
                } else {
                    $seq_num = 1;
                }
                $admission_no = sprintf("ADM%s%03d", $year, $seq_num);


                $ins_student = $pdo->prepare("
                    INSERT INTO students (
                        user_id, admission_no, full_name, father_name, mother_name, gender, dob, category, mobile, email,
                        address, city, state, pincode, tenth_percentage, twelfth_percentage, school_name,
                        passing_year, course_id, status, is_submitted
                    ) VALUES (
                        :user_id, :admission_no, :full_name, :father_name, :mother_name, :gender, :dob, :category, :mobile, :email,
                        :address, :city, :state, :pincode, :tenth_percentage, :twelfth_percentage, :school_name,
                        :passing_year, :course_id, 'Pending', 0
                    )
                ");

                $ins_student->execute([
                    'user_id' => $new_user_id,
                    'admission_no' => $admission_no,
                    'full_name' => $full_name,
                    'father_name' => $father_name,
                    'mother_name' => $mother_name,
                    'gender' => $gender,
                    'dob' => $dob,
                    'category' => $category,
                    'mobile' => $mobile,
                    'email' => $email,
                    'address' => $address,
                    'city' => $city,
                    'state' => $state,
                    'pincode' => $pincode,
                    'tenth_percentage' => $tenth_percentage,
                    'twelfth_percentage' => $twelfth_percentage,
                    'school_name' => $school_name,
                    'passing_year' => $passing_year,
                    'course_id' => $course_id
                ]);
                $new_student_id = $pdo->lastInsertId();


                $ins_docs = $pdo->prepare("INSERT INTO documents (student_id) VALUES (:student_id)");
                $ins_docs->execute(['student_id' => $new_student_id]);


                $ins_hist = $pdo->prepare("INSERT INTO status_history (student_id, status, remarks) VALUES (:student_id, 'Pending', 'Profile registered by Administrator.')");
                $ins_hist->execute(['student_id' => $new_student_id]);

                $pdo->commit();
                $success_msg = "Student registered successfully. Admission No: " . $admission_no;


                header("Location: manage_students.php?msg=added");
                exit;
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error_msg = "Database Error: " . $e->getMessage();
        }
    }
}

$page_title = "Add Student Profile";
include '../includes/header.php';
?>

<div class="wrapper">

    <?php include '../includes/sidebar.php'; ?>


    <div id="content">
        <?php render_topbar(); ?>

        <div class="container-fluid">
            <?php render_page_header('Create Student Application', '<a href="manage_students.php" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Back to List</a>'); ?>

            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i><?php echo e($error_msg); ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <i class="fa-solid fa-user-plus me-2"></i>Register Student Details
                </div>
                <div class="card-body">
                    <form action="add_student.php" method="POST">


                        <h5 class="fw-bold text-primary border-bottom pb-2 mb-3"><i class="fa-solid fa-key me-2"></i>1. Login Account Credentials</h5>
                        <div class="row g-3 mb-4">
                        <h5 class="fw-bold text-primary border-bottom pb-2 mb-3"><i class="fa-solid fa-key me-2"></i>1. Login Account Credentials</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-12 col-sm-6 col-md-4">
                                <label class="form-label">Account Display Name</label>
                                <input type="text" class="form-control" name="name" placeholder="e.g. Samuel Jackson" required>
                            </div>
                            <div class="col-12 col-sm-6 col-md-4">
                                <label class="form-label">Account Email Address</label>
                                <input type="email" class="form-control" name="email" placeholder="e.g. sam@gmail.com" required>
                            </div>
                            <div class="col-12 col-sm-6 col-md-4">
                                <label class="form-label">Password</label>
                                <input type="password" class="form-control" name="password" placeholder="Min. 6 characters" required>
                            </div>
                        </div>


                        <h5 class="fw-bold text-primary border-bottom pb-2 mb-3"><i class="fa-solid fa-address-card me-2"></i>2. Personal Details</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-12 col-sm-6 col-md-4">
                                <label class="form-label">Student Full Name</label>
                                <input type="text" class="form-control" name="full_name" required>
                            </div>
                            <div class="col-12 col-sm-6 col-md-4">
                                <label class="form-label">Father's Name</label>
                                <input type="text" class="form-control" name="father_name" required>
                            </div>
                            <div class="col-12 col-sm-6 col-md-4">
                                <label class="form-label">Mother's Name</label>
                                <input type="text" class="form-control" name="mother_name" required>
                            </div>
                            <div class="col-12 col-sm-6 col-md-4">
                                <label class="form-label">Gender</label>
                                <select class="form-select form-control" name="gender" required>
                                    <option value="">Choose...</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-12 col-sm-6 col-md-4">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" name="dob" required>
                            </div>
                            <div class="col-12 col-sm-6 col-md-4">
                                <label class="form-label">Category</label>
                                <select class="form-select form-control" name="category" required>
                                    <option value="">Choose...</option>
                                    <option value="General">General</option>
                                    <option value="OBC">OBC</option>
                                    <option value="SC">SC</option>
                                    <option value="ST">ST</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-12 col-sm-6 col-md-4">
                                <label class="form-label">Mobile Number</label>
                                <input type="tel" class="form-control" name="mobile" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Address</label>
                                <input type="text" class="form-control" name="address" required>
                            </div>
                            <div class="col-12 col-sm-4 col-md-2">
                                <label class="form-label">City</label>
                                <input type="text" class="form-control" name="city" required>
                            </div>
                            <div class="col-12 col-sm-4 col-md-2">
                                <label class="form-label">State</label>
                                <input type="text" class="form-control" name="state" required>
                            </div>
                            <div class="col-12 col-sm-4 col-md-2">
                                <label class="form-label">Pincode</label>
                                <input type="text" class="form-control" name="pincode" required>
                            </div>
                        </div>


                        <h5 class="fw-bold text-primary border-bottom pb-2 mb-3"><i class="fa-solid fa-graduation-cap me-2"></i>3. Academic Scores</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label">10th Std Percentage (%)</label>
                                <input type="number" step="0.01" min="0" max="100" class="form-control" name="tenth_percentage" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">12th Std Percentage (%)</label>
                                <input type="number" step="0.01" min="0" max="100" class="form-control" name="twelfth_percentage" required>
                                <small class="text-muted">Min eligibility: 35%</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Previous School Board Name</label>
                                <input type="text" class="form-control" name="school_name" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Passing Year</label>
                                <input type="number" class="form-control" name="passing_year" value="<?php echo date('Y'); ?>" required>
                            </div>
                        </div>


                        <h5 class="fw-bold text-primary border-bottom pb-2 mb-3"><i class="fa-solid fa-book-bookmark me-2"></i>4. Degree Selection</h5>
                        <div class="row g-3 mb-5">
                            <div class="col-md-12">
                                <label class="form-label">Select Preferred Course</label>
                                <select class="form-select form-control" name="course_id" required>
                                    <option value="">Select course...</option>
                                    <?php foreach ($courses as $c): ?>
                                        <option value="<?php echo $c['course_id']; ?>">
                                            <?php echo e($c['course_name']) . " (" . e($c['department']) . ")"; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between pt-3 border-top">
                            <a href="manage_students.php" class="btn btn-outline-secondary py-2"><i class="fa-solid fa-arrow-left me-1"></i>Cancel</a>
                            <button type="submit" class="btn btn-primary px-5 py-2 fw-bold">Register Student</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>