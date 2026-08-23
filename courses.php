<?php
require_once 'includes/db_connect.php';
require_once 'includes/auth.php';

try {
    $stmt = $pdo->query("SELECT * FROM courses ORDER BY department, course_name");
    $courses_from_db = $stmt->fetchAll();
} catch (PDOException $e) {
    $courses_from_db = [];
}

// Map short/abbreviated database course names to full expanded forms
$course_full_names = [
    'B.Sc. Computer Science' => 'Bachelor of Science in Computer Science',
    'Bachelor of Computer Applications (BCA)' => 'Bachelor of Computer Applications',
    'B.Com. (General)' => 'Bachelor of Commerce (General)',
    'B.A. English Literature' => 'Bachelor of Arts in English Literature',
    'B.Sc. Information Technology (B.Sc. IT)' => 'Bachelor of Science in Information Technology'
];

// Details map keyed by full form and standard name
$course_details_map = [
    'Bachelor of Science in Computer Science' => [
        'icon' => 'fa-laptop-code text-primary',
        'duration' => '3 Years (6 Semesters)',
        'eligibility' => 'Higher Secondary (10+2) with Mathematics as a core subject, minimum 50% marks.',
        'curriculum' => [
            'Programming in C++ & Java',
            'Data Structures & Algorithms',
            'Database Management Systems (DBMS)',
            'Operating Systems & Networking',
            'Software Engineering & Web Basics'
        ]
    ],
    'Bachelor of Computer Applications' => [
        'icon' => 'fa-mobile-screen-button text-success',
        'duration' => '3 Years (6 Semesters)',
        'eligibility' => 'Higher Secondary (10+2) in any stream with English, minimum 45% marks.',
        'curriculum' => [
            'Web Technologies (HTML, CSS, JS)',
            'Object-Oriented Programming',
            'Mobile App Development',
            'Cloud Computing Foundations',
            'Software Testing & Projects'
        ]
    ],
    'Bachelor of Commerce (General)' => [
        'icon' => 'fa-chart-pie text-warning',
        'duration' => '3 Years (6 Semesters)',
        'eligibility' => 'Higher Secondary (10+2) in Commerce or Science stream, minimum 50% marks.',
        'curriculum' => [
            'Financial & Management Accounting',
            'Business Law & Corporate Governance',
            'Micro & Macro Economics',
            'Direct & Indirect Taxation (GST)',
            'Auditing & Financial Modeling'
        ]
    ],
    'Bachelor of Arts in English Literature' => [
        'icon' => 'fa-book-open-reader text-danger',
        'duration' => '3 Years (6 Semesters)',
        'eligibility' => 'Higher Secondary (10+2) in any stream with minimum 50% marks in English.',
        'curriculum' => [
            'History of English Literature',
            'Classical & Modern Poetry',
            'Drama & Creative Writing',
            'Linguistics & Phonetics',
            'Literary Criticism & Media Writing'
        ]
    ],
    'Bachelor of Science in Information Technology' => [
        'icon' => 'fa-shield-halved text-info',
        'duration' => '3 Years (6 Semesters)',
        'eligibility' => 'Higher Secondary (10+2) with Mathematics/IT, minimum 45% marks.',
        'curriculum' => [
            'System Administration & Linux',
            'Cybersecurity & Network Security',
            'Web Application Architecture',
            'Big Data & Analytics',
            'IT Infrastructure & DevOps'
        ]
    ]
];

$default_details = [
    'icon' => 'fa-graduation-cap text-secondary',
    'duration' => '3 Years (6 Semesters)',
    'eligibility' => 'Higher Secondary (10+2) from a recognized board with minimum 45% marks.',
    'curriculum' => [
        'Core Foundational Coursework',
        'Specialized Subject Modules',
        'Elective Modules',
        'Practical Laboratory Work'
    ]
];

$is_public_page = true;
$body_class = "courses-page-body";
$page_title = "Academic Courses & Programs";
include 'includes/header.php';
?>

<!-- Course Page Header Banner -->
<div class="bg-white border-bottom py-4 shadow-sm">
    <div class="container">
        <div class="row align-items-center gy-3">
            <div class="col-md-7 text-center text-md-start">
                <span class="text-primary fw-bold small text-uppercase tracking-wider d-block mb-1">
                    <i class="fa-solid fa-graduation-cap me-1"></i> Official Course Catalog
                </span>
                <h2 class="fw-extrabold text-dark mb-0">State College of Technology</h2>
            </div>
            <div class="col-md-5 text-center text-md-end">
                <h4 class="fw-bold text-primary mb-0 mt-3">
                    <i class="fa-solid fa-book-open me-2"></i>Academic Programs & Courses
                </h4>
            </div>
        </div>
    </div>
</div>

<!-- Search & Filter Controls -->
<div class="container my-4">
    <div class="row align-items-center gy-3 bg-white p-3 rounded-4 border">
        <div class="col-12 col-lg-7 mt-0">
            <div class="d-flex align-items-center gap-2 flex-wrap" id="deptFilters">
                <button class="btn btn-primary btn-sm fw-bold px-3 filter-btn active" data-dept="all">All Courses</button>
                <button class="btn btn-outline-secondary btn-sm fw-semibold px-3 filter-btn" data-dept="Science & IT">Science & IT</button>
                <button class="btn btn-outline-secondary btn-sm fw-semibold px-3 filter-btn" data-dept="Commerce">Commerce</button>
                <button class="btn btn-outline-secondary btn-sm fw-semibold px-3 filter-btn" data-dept="Arts & Humanities">Arts & Humanities</button>
            </div>
        </div>
        <div class="col-12 col-lg-5 mt-3 mt-lg-0">
            <div class="input-group">
                <span class="input-group-text bg-white rounded-start-4 border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                <input type="text" id="courseSearch" class="form-control rounded-end-4 border-start-0" placeholder="Search course name or subject...">
            </div>
        </div>
    </div>
</div>

<!-- Course Cards List -->
<div class="container mb-5">
    <?php if (empty($courses_from_db)): ?>
        <div class="alert alert-info text-center shadow-sm py-4">
            <i class="fa-solid fa-circle-info fs-3 mb-2 text-info"></i>
            <h5>No Active Courses Found</h5>
            <p class="mb-0 text-muted">Course database is currently empty. Please check back later or contact admin.</p>
        </div>
    <?php else: ?>
        <div class="row g-4" id="courseCardList">
            <?php foreach ($courses_from_db as $course):
                $c_name = $course['course_name'];
                $full_name = $course_full_names[$c_name] ?? $c_name;
                $details = $course_details_map[$full_name] ?? $course_details_map[$c_name] ?? $default_details;
            ?>
                <div class="col-md-6 col-lg-4 course-item"
                    data-dept="<?php echo e($course['department']); ?>"
                    data-search="<?php echo e(strtolower($full_name . ' ' . $c_name . ' ' . $course['department'] . ' ' . implode(' ', $details['curriculum']))); ?>">
                    <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden d-flex flex-column">
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <span class="badge bg-primary text-white px-2.5 py-1 rounded-pill small"><?php echo e($course['department']); ?></span>
                            </div>

                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px; font-size: 20px;">
                                    <i class="fa-solid <?php echo e($details['icon']); ?>"></i>
                                </div>
                                <h4 class="card-title fw-bold text-dark mb-0 fs-5"><?php echo e($full_name); ?></h4>
                            </div>

                            <div class="mb-3 pb-3 border-bottom text-muted small">
                                <i class="fa-regular fa-clock me-1 text-primary"></i> <strong>Duration:</strong> <?php echo e($details['duration']); ?>

                            </div>

                            <div class="mb-3">
                                <h6 class="fw-bold text-dark small mb-1"><i class="fa-solid fa-graduation-cap me-1 text-secondary"></i>Eligibility:</h6>
                                <p class="text-muted small mb-0" style="line-height: 1.5;"><?php echo e($details['eligibility']); ?></p>
                            </div>

                            <div class="flex-grow-1">
                                <h6 class="fw-bold text-dark small mb-2"><i class="fa-solid fa-list-check me-1 text-success"></i>Key Subjects:</h6>
                                <ul class="list-unstyled mb-0">
                                    <?php foreach ($details['curriculum'] as $item): ?>
                                        <li class="small text-muted mb-1.5 d-flex align-items-start">
                                            <i class="fa-solid fa-circle-check text-success me-2 mt-1" style="font-size: 11px;"></i>
                                            <span><?php echo e($item); ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Clean Bottom Call-to-Action -->
    <div class="text-center mt-5 p-4 bg-white rounded shadow-sm border">
        <h4 class="fw-bold text-dark mb-2">Ready to apply for admission?</h4>
        <p class="text-muted mb-3 mx-auto" style="max-width: 550px;">Create your account, fill in your marks, upload required documents, and track your status online.</p>
        <div class="d-flex justify-content-center gap-3 flex-wrap">
            <a href="student_register.php" class="btn btn-primary fw-bold px-4 py-2"><i class="fa-solid fa-user-plus me-1"></i>Register Account</a>
            <a href="login.php?role=student" class="btn btn-outline-secondary fw-bold px-4 py-2"><i class="fa-solid fa-right-to-bracket me-1"></i>Student Login</a>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const filterBtns = document.querySelectorAll('.filter-btn');
        const searchInput = document.getElementById('courseSearch');
        const cards = document.querySelectorAll('.course-item');

        let activeDept = 'all';
        let searchQuery = '';

        filterBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                filterBtns.forEach(b => {
                    b.classList.remove('btn-primary', 'active');
                    b.classList.add('btn-outline-secondary');
                });
                this.classList.remove('btn-outline-secondary');
                this.classList.add('btn-primary', 'active');
                activeDept = this.getAttribute('data-dept');
                filterCards();
            });
        });

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                searchQuery = this.value.toLowerCase().trim();
                filterCards();
            });
        }

        function filterCards() {
            cards.forEach(card => {
                const dept = card.getAttribute('data-dept');
                const searchData = card.getAttribute('data-search');

                const matchesDept = (activeDept === 'all' || dept === activeDept);
                const matchesSearch = (!searchQuery || searchData.includes(searchQuery));

                if (matchesDept && matchesSearch) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
    });
</script>

<?php include 'includes/footer.php'; ?>