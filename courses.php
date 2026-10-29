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

// Course card banner images
$course_images = [
    'Bachelor of Science in Computer Science' => 'assets/images/courses/bsc_cs.jpg',
    'B.Sc. Computer Science' => 'assets/images/courses/bsc_cs.jpg',
    'Bachelor of Computer Applications (BCA)' => 'assets/images/courses/bca.jpg',
    'Bachelor of Computer Applications' => 'assets/images/courses/bca.jpg',
    'B.Com. (General)' => 'assets/images/courses/bcom.jpg',
    'Bachelor of Commerce (General)' => 'assets/images/courses/bcom.jpg',
    'B.A. English Literature' => 'assets/images/courses/ba_english.jpg',
    'Bachelor of Arts in English Literature' => 'assets/images/courses/ba_english.jpg',
    'B.Sc. Information Technology (B.Sc. IT)' => 'assets/images/courses/bsc_it.jpg',
    'Bachelor of Science in Information Technology' => 'assets/images/courses/bsc_it.jpg'
];
$default_course_image = 'assets/images/courses/default.jpg';

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
                $course_img = $course_images[$full_name] ?? $course_images[$c_name] ?? $default_course_image;
            ?>
                <div class="col-md-6 col-lg-4 course-item"
                    data-dept="<?php echo e($course['department']); ?>"
                    data-search="<?php echo e(strtolower($full_name . ' ' . $c_name . ' ' . $course['department'] . ' ' . implode(' ', $details['curriculum']))); ?>">
                    <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden course-clean-card d-flex flex-column">
                        <!-- Course Image (Newly Added) -->
                        <div class="position-relative overflow-hidden course-card-img-box">
                            <img src="<?php echo e($course_img); ?>" 
                                 alt="<?php echo e($full_name); ?>" 
                                 class="w-100 course-card-img" 
                                 loading="lazy">
                            <span class="badge bg-primary text-white position-absolute top-0 end-0 m-3 px-3 py-1.5 rounded-pill shadow-sm small">
                                <?php echo e($course['department']); ?>
                            </span>
                        </div>

                        <!-- Course Name & View Details Button -->
                        <div class="card-body p-4 d-flex flex-column justify-content-between flex-grow-1">
                            <div class="mb-3">
                                <h4 class="card-title fw-bold text-dark mb-0 fs-5 lh-sm"><?php echo e($full_name); ?></h4>
                            </div>

                            <div class="pt-2 mt-auto">
                                <button type="button" 
                                        class="btn btn-outline-primary w-100 py-2.5 rounded-3 fw-bold d-flex align-items-center justify-content-center gap-2 view-details-btn"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#courseModal<?php echo (int)$course['course_id']; ?>">
                                    <i class="fa-solid fa-right-to-bracket"></i> View Details
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Dialog Boxes / Modals for Course Details with Images -->
        <?php foreach ($courses_from_db as $course):
            $c_name = $course['course_name'];
            $full_name = $course_full_names[$c_name] ?? $c_name;
            $details = $course_details_map[$full_name] ?? $course_details_map[$c_name] ?? $default_details;
            $course_img = $course_images[$full_name] ?? $course_images[$c_name] ?? $default_course_image;
        ?>
            <div class="modal fade" id="courseModal<?php echo (int)$course['course_id']; ?>" tabindex="-1" aria-labelledby="modalLabel<?php echo (int)$course['course_id']; ?>" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                        <div class="modal-header border-0 bg-light px-4 py-3 align-items-center">
                            <div>
                                <h5 class="modal-title fw-bold text-dark mb-0 fs-5" id="modalLabel<?php echo (int)$course['course_id']; ?>"><?php echo e($full_name); ?></h5>
                            </div>
                            <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body p-4">
                            <!-- Course Image in Dialog -->
                            <div class="rounded-4 overflow-hidden mb-4 shadow-sm position-relative">
                                <img src="<?php echo e($course_img); ?>" alt="<?php echo e($full_name); ?>" class="w-100" style="max-height: 280px; object-fit: cover;">
                            </div>

                            <!-- Meta Specifications -->
                            <div class="row g-3 mb-4">
                                <div class="col-sm-6">
                                    <div class="p-3 bg-light rounded-3 border h-100">
                                        <div class="text-muted small fw-semibold mb-1">
                                            <i class="fa-regular fa-clock me-1 text-primary"></i> Course Duration
                                        </div>
                                        <div class="fw-bold text-dark"><?php echo e($details['duration']); ?></div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="p-3 bg-light rounded-3 border h-100">
                                        <div class="text-muted small fw-semibold mb-1">
                                            <i class="fa-solid fa-users-line me-1 text-success"></i> Available Seats
                                        </div>
                                        <div class="fw-bold text-dark"><?php echo !empty($course['total_seats']) ? (int)$course['total_seats'] . ' Seats / Intake' : '60 Seats / Intake'; ?></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Eligibility Criteria -->
                            <div class="mb-4 p-3 rounded-3 bg-primary bg-opacity-10 border border-primary border-opacity-25">
                                <h6 class="fw-bold text-primary mb-1">
                                    <i class="fa-solid fa-graduation-cap me-2"></i>Eligibility Criteria
                                </h6>
                                <p class="text-dark small mb-0 lh-base"><?php echo e($details['eligibility']); ?></p>
                            </div>

                            <!-- Key Subjects / Curriculum -->
                            <div class="mb-2">
                                <h6 class="fw-bold text-dark mb-3">
                                    <i class="fa-solid fa-list-check me-2 text-success"></i>Key Subjects & Curriculum Modules
                                </h6>
                                <div class="row g-2">
                                    <?php foreach ($details['curriculum'] as $item): ?>
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center p-2 rounded-4 bg-light border text-dark small">
                                                <i class="fa-solid fa-circle-check text-success me-2 flex-shrink-0"></i>
                                                <span><?php echo e($item); ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer border-top bg-light px-4 py-3 d-flex justify-content-between align-items-center">
                            <a href="inquiry.php?course_id=<?php echo (int)$course['course_id']; ?>" class="btn btn-primary px-4 py-2 fw-bold rounded-3">
                                <i class="fa-solid fa-file-circle-plus me-1"></i> Apply for this Course
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
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