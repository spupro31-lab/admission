<?php
require_once 'includes/auth.php';

$is_public_page = true;
$body_class = "homepage-body";
$page_title = "State College of Technology - Academic Excellence";
include 'includes/header.php';
?>


<section class="sct-hero">
    <div class="container">
        <div class="row align-items-center g-4 g-lg-5">

            <!-- LEFT: college text + actions -->
            <div class="col-lg-6">

                <div class="sct-hero__eyebrow mb-3">
                    <span class="sct-hero__badge">
                        <i class="fa-solid fa-circle-dot me-1" style="font-size:8px;color:#22c55e;vertical-align:middle;"></i>
                        Admissions Open &mdash; 2026&ndash;27
                    </span>
                    <span class="sct-hero__estd ms-2">Est. 1998</span>
                </div>

                <h1 class="sct-hero__title mb-2">
                    State College of<br>
                    <span class="sct-hero__title--accent">Technology</span>
                </h1>
                <p class="sct-hero__tagline mb-3">Affiliated to State University &bull; NAAC Accredited &bull; Approved by UGC</p>

                <p class="sct-hero__subtitle mb-4">
                    Shaping engineers, scientists and innovators since 1998. Join SCT's fully digital admission process &mdash; apply online, track your status in real time, and receive your offer letter without visiting the campus.
                </p>

                <div class="d-flex flex-wrap gap-2 mb-4">
                    <a href="inquiry.php" class="sct-btn sct-btn--primary">
                        <i class="fa-solid fa-file-circle-plus"></i> Apply for Admission
                    </a>
                    <a href="login.php?role=student" class="sct-btn sct-btn--outline">
                        <i class="fa-solid fa-right-to-bracket"></i> Student Login
                    </a>
                    <a href="courses.php" class="sct-btn sct-btn--ghost">
                        <i class="fa-solid fa-book-open"></i> View Courses
                    </a>
                </div>

                <!-- Stat strip -->
                <div class="sct-hero__stats d-flex flex-wrap gap-3">
                    <div class="sct-stat">
                        <span class="sct-stat__num">5000+</span>
                        <span class="sct-stat__label">Alumni</span>
                    </div>
                    <div class="sct-stat-divider"></div>
                    <div class="sct-stat">
                        <span class="sct-stat__num">94%</span>
                        <span class="sct-stat__label">Placement</span>
                    </div>
                    <div class="sct-stat-divider"></div>
                    <div class="sct-stat">
                        <span class="sct-stat__num">12+</span>
                        <span class="sct-stat__label">Courses</span>
                    </div>
                    <div class="sct-stat-divider"></div>
                    <div class="sct-stat">
                        <span class="sct-stat__num">28</span>
                        <span class="sct-stat__label">Years of Excellence</span>
                    </div>
                </div>
            </div>

            <!-- RIGHT: campus image -->
            <div class="col-lg-6">
                <div class="sct-hero__img-wrap">
                    <img src="assets/images/university_campus.png"
                         alt="State College of Technology Campus"
                         class="sct-hero__campus-img">
                    <!-- Floating info badge on image -->
                    <div class="sct-hero__img-badge">
                        <i class="fa-solid fa-location-dot text-danger me-1"></i>
                        SCT Main Campus &bull; Block A &amp; B
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>


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


<div class="container my-5 py-3" id="steps">
    <div class="text-center mb-5">
        <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-1.5 rounded-pill fw-bold small mb-2">
            <i class="fa-solid fa-list-check me-1"></i> Simple Admission Workflow
        </span>
        <h2 class="section-title mb-2">How It Works</h2>
        <p class="section-subtitle mb-0">Follow four straightforward steps from initial inquiry to final admission confirmation.</p>
    </div>

    <div class="row g-4">
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 text-center bg-white">
                <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 54px; height: 54px; font-size: 20px;">
                    <i class="fa-solid fa-file-signature"></i>
                </div>
                <span class="badge bg-light text-muted border rounded-pill px-2.5 py-1 small mb-2 align-self-center">Step 01</span>
                <h5 class="fw-bold text-dark mb-2">Submit Inquiry</h5>
                <p class="text-muted small mb-0">Fill in basic contact details and select your preferred degree program to register.</p>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 text-center bg-white">
                <div class="rounded-circle bg-info bg-opacity-10 text-info d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 54px; height: 54px; font-size: 20px;">
                    <i class="fa-solid fa-right-to-bracket"></i>
                </div>
                <span class="badge bg-light text-muted border rounded-pill px-2.5 py-1 small mb-2 align-self-center">Step 02</span>
                <h5 class="fw-bold text-dark mb-2">Portal Access</h5>
                <p class="text-muted small mb-0">Log in securely to your student portal using your registered email credentials.</p>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 text-center bg-white">
                <div class="rounded-circle bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 54px; height: 54px; font-size: 20px;">
                    <i class="fa-solid fa-clipboard-check"></i>
                </div>
                <span class="badge bg-light text-muted border rounded-pill px-2.5 py-1 small mb-2 align-self-center">Step 03</span>
                <h5 class="fw-bold text-dark mb-2">Complete Profile</h5>
                <p class="text-muted small mb-0">Provide academic scores, address details, and pay the nominal processing fee.</p>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 text-center bg-white">
                <div class="rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 54px; height: 54px; font-size: 20px;">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <span class="badge bg-light text-muted border rounded-pill px-2.5 py-1 small mb-2 align-self-center">Step 04</span>
                <h5 class="fw-bold text-dark mb-2">Verification & Letter</h5>
                <p class="text-muted small mb-0">Upload documents, track staff verification in real time, and download your admission receipt.</p>
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
            Begin your admission with a simple online inquiry. Complete your application, upload required documents, and track your admission status instantly.
        </p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="inquiry.php" class="btn btn-premium-primary btn-lg px-4 py-2 fw-bold">
                <i class="fa-solid fa-file-circle-plus me-1"></i> Inquiry for Admission
            </a>
            <a href="login.php?role=student" class="btn btn-premium-secondary btn-lg px-4 py-2 fw-bold">
                <i class="fa-solid fa-right-to-bracket me-1"></i> Student Portal Login
            </a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>