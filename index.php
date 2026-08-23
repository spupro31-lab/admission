<?php
require_once 'includes/auth.php';

$is_public_page = true;
$body_class = "homepage-body";
$page_title = "State College of Technology - Academic Excellence";
include 'includes/header.php';
?>


<div class="home-hero-section">
    <div class="home-hero-decor home-hero-decor--top"></div>
    <div class="home-hero-decor home-hero-decor--bottom"></div>

    <div class="container position-relative py-3">
        <div class="row justify-content-center">
            <div class="col-lg-10 text-center">
                <h1 class="home-hero-title mb-3">
                    State College of <span class="text-primary">Technology</span>
                </h1>
                <p class="home-hero-subtitle mx-auto mb-4" style="max-width: 720px;">
                    Welcome to the State College of Technology. Experience a fully digital, streamlined admission process. Apply for courses, upload your credentials, and track your application status in real time.
                </p>
                <div class="d-flex gap-3 justify-content-center flex-wrap">
                    <a href="portal.php" class="btn btn-premium-primary rounded-4 btn-lg px-4 py-3 fw-bold d-inline-flex align-items-center gap-2 shadow-sm">
                        <i class="fa-solid fa-right-to-bracket"></i> Admission Portal Access
                    </a>
                    <a href="courses.php" class="btn btn-premium-secondary rounded-4 btn-lg px-4 py-3 fw-bold d-inline-flex align-items-center gap-2">
                        <i class="fa-solid fa-book-open"></i> Explore Courses
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="container my-5">
    <div class="row g-4 text-center">
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="home-stat-card card border-0 h-100">
                <div class="fs-1 fw-extrabold text-primary mb-1">94%</div>
                <div class="fw-bold text-dark small text-uppercase">Placement Rate</div>
                <p class="text-muted small mt-2 mb-0">Consistently placed technical and commerce graduates in top firms.</p>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="home-stat-card card border-0 h-100 stat-success">
                <div class="fs-1 fw-extrabold text-success mb-1">120+</div>
                <div class="fw-bold text-dark small text-uppercase">Top Recruiters</div>
                <p class="text-muted small mt-2 mb-0">Direct recruitment ties with MNCs and tech startups.</p>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="home-stat-card card border-0 h-100 stat-warning">
                <div class="fs-1 fw-extrabold text-warning mb-1">50K+</div>
                <div class="fw-bold text-dark small text-uppercase">Library Archive</div>
                <p class="text-muted small mt-2 mb-0">Vast collection of books, academic journals, and digital logs.</p>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="home-stat-card card border-0 h-100 stat-danger">
                <div class="fs-1 fw-extrabold text-danger mb-1">₹15 LPA</div>
                <div class="fw-bold text-dark small text-uppercase">Highest Package</div>
                <p class="text-muted small mt-2 mb-0">Impressive packages achieved in national hiring portals.</p>
            </div>
        </div>
    </div>
</div>


<div class="container my-5 py-3">
    <h2 class="section-title">Explore Our Academic Fields</h2>
    <p class="section-subtitle">
        We offer modern, comprehensive curriculum options designed by industry specialists to prepare you for global technology landscapes.
    </p>

    <div class="row g-4 justify-content-center">
        <div class="col-12 col-md-6 col-lg-4">
            <div class="premium-card home-program-card card-cs h-100">
                <div class="d-flex flex-column h-100 justify-content-between">
                    <div>
                        <div class="card-icon-wrapper">
                            <i class="fa-solid fa-code"></i>
                        </div>
                        <h3>Computer Science & IT</h3>
                        <p>Programming, Algorithms, Data Systems, Cybersecurity networks, Cloud Computing, and App Development frameworks.</p>
                    </div>
                    <div class="border-top pt-3 mt-3">
                        <a href="courses.php" class="text-decoration-none small fw-bold text-primary d-inline-flex align-items-center gap-1">
                            Syllabus details <i class="fa-solid fa-arrow-right-long"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-4">
            <div class="premium-card home-program-card card-commerce h-100">
                <div class="d-flex flex-column h-100 justify-content-between">
                    <div>
                        <div class="card-icon-wrapper">
                            <i class="fa-solid fa-chart-line"></i>
                        </div>
                        <h3>Commerce & Accounting</h3>
                        <p>Financial reporting, corporate governance, micro/macroeconomics, taxation systems, auditing, and corporate laws.</p>
                    </div>
                    <div class="border-top pt-3 mt-3">
                        <a href="courses.php" class="text-decoration-none small fw-bold text-warning d-inline-flex align-items-center gap-1">
                            Syllabus details <i class="fa-solid fa-arrow-right-long"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-4">
            <div class="premium-card home-program-card card-humanities h-100">
                <div class="d-flex flex-column h-100 justify-content-between">
                    <div>
                        <div class="card-icon-wrapper">
                            <i class="fa-solid fa-book-open-reader"></i>
                        </div>
                        <h3>Humanities & Languages</h3>
                        <p>Linguistics, creative writing, drama critiques, classical and modern English poetry studies, and critical thinking development.</p>
                    </div>
                    <div class="border-top pt-3 mt-3">
                        <a href="courses.php" class="text-decoration-none small fw-bold text-danger d-inline-flex align-items-center gap-1">
                            Syllabus details <i class="fa-solid fa-arrow-right-long"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="container my-5" id="steps">
    <div class="timeline-section">
        <h2 class="section-title">Application Journey</h2>
        <p class="section-subtitle">Follow these 4 simple steps to complete your admission process at State College of Technology.</p>

        <div class="timeline-stepper px-2 px-md-4">
            <div class="step-card">
                <div class="step-number-bubble">1</div>
                <h5>Quick Registration</h5>
                <p>Create your credentials using an active email address and mobile number.</p>
            </div>
            <div class="step-card">
                <div class="step-number-bubble">2</div>
                <h5>Academic Details</h5>
                <p>Provide 10th and 12th details and choose your preferred engineering course stream.</p>
            </div>
            <div class="step-card">
                <div class="step-number-bubble">3</div>
                <h5>Document Upload</h5>
                <p>Securely upload photo, signature, board marksheet, leaving certificate, and Aadhaar card.</p>
            </div>
            <div class="step-card">
                <div class="step-number-bubble">4</div>
                <h5>Track & Receipt</h5>
                <p>Monitor real-time verification and download your official PDF admission letter.</p>
            </div>
        </div>
    </div>
</div>


<div class="campus-facilities-section" id="facilities">
    <div class="container">
        <h2 class="section-title">Campus & Infrastructure</h2>
        <p class="section-subtitle">State-of-the-art facilities designed to foster academic success and research innovation.</p>
        <div class="row g-4 mt-2">
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="facility-card text-center h-100">
                    <div class="facility-icon-wrapper bg-light text-primary mx-auto">
                        <i class="fa-solid fa-book-bookmark"></i>
                    </div>
                    <h4>Central Library</h4>
                    <p class="small text-muted mb-0">Over 50,000 reference books, international journals, and a high-speed digital research archive.</p>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="facility-card text-center h-100">
                    <div class="facility-icon-wrapper bg-light text-success mx-auto">
                        <i class="fa-solid fa-microchip"></i>
                    </div>
                    <h4>Advanced IT Labs</h4>
                    <p class="small text-muted mb-0">Intel Core i9 systems, high-speed fiber internet, and dedicated servers for software experimentation.</p>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="facility-card text-center h-100">
                    <div class="facility-icon-wrapper bg-light text-warning mx-auto">
                        <i class="fa-solid fa-building-columns"></i>
                    </div>
                    <h4>Smart Classrooms</h4>
                    <p class="small text-muted mb-0">Acoustically treated lecture halls equipped with projection systems and interactive displays.</p>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="facility-card text-center h-100">
                    <div class="facility-icon-wrapper bg-light text-danger mx-auto">
                        <i class="fa-solid fa-volleyball"></i>
                    </div>
                    <h4>Sports & Hostels</h4>
                    <p class="small text-muted mb-0">Dedicated gyms, courts for indoor/outdoor games, and hygienic, comfortable hostel rooms.</p>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="home-cta-section">
    <div class="container text-center">
        <h3 class="home-cta-title">Build Your Technical Career Path Today</h3>
        <p class="home-cta-subtitle">
            Registration is quick and online. Log in, specify your board marks, upload your PDF and picture credentials, and track your admission status instantly.
        </p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="portal.php" class="btn btn-premium-primary btn-lg px-4 py-2 fw-bold">
                <i class="fa-solid fa-user-plus me-1"></i> Register & Apply Online
            </a>
            <a href="login.php?role=student" class="btn btn-premium-secondary btn-lg px-4 py-2 fw-bold">
                Student Portal Login
            </a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>