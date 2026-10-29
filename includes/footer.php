<?php if (isset($is_public_page) && $is_public_page === true): ?>

    <footer class="premium-footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-5 col-md-12">
                    <h5 class="d-flex align-items-center">
                        <i class="fa-solid fa-graduation-cap me-2 text-info fs-4"></i>
                        <span>State College of Technology</span>
                    </h5>
                    <p class="mt-3 text-muted">A premier institution offering state-of-the-art technical education. Empowering students since 2002 to build the systems and innovations of tomorrow.</p>
                    <div class="mt-3 text-muted small">
                        <span class="badge bg-light text-dark border me-2"><i class="fa-solid fa-certificate text-primary me-1"></i>AICTE Approved</span>
                        <span class="badge bg-light text-dark border"><i class="fa-solid fa-award text-success me-1"></i>State University Affiliated</span>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <h5 class="text-dark">Quick Links</h5>
                    <ul>
                        <li><a href="<?php echo app_base_path(); ?>portal.php">Portal Selection</a></li>
                        <li><a href="<?php echo app_base_path(); ?>index.php#steps">Admission Steps</a></li>
                        <li><a href="<?php echo app_base_path(); ?>courses.php">Academic Courses</a></li>
                        <li><a href="<?php echo app_base_path(); ?>inquiry.php">Inquiry for Admission</a></li>
                    </ul>
                </div>

                <div class="col-lg-4 col-md-6">
                    <h5 class="text-dark">Help & Support</h5>
                    <p class="small text-muted mb-2">
                        <i class="fa-solid fa-envelope me-2 text-info"></i>
                        <a href="mailto:admissions@statecollege.edu.in" class="text-muted text-decoration-none">admissions@statecollege.edu.in</a>
                    </p>
                    <p class="small text-muted mb-2">
                        <i class="fa-solid fa-phone me-2 text-info"></i>
                        <a href="tel:+917923245678" class="text-muted text-decoration-none">+91 (079) 2324-5678</a>
                    </p>
                    <p class="small text-muted">
                        <i class="fa-solid fa-location-dot me-2 text-info"></i>State College Campus, University Road, SCT Knowledge Corridor
                    </p>
                </div>
            </div>

            <div class="footer-bottom text-center">
                <p class="mb-0">&copy; <?php echo date("Y"); ?> State College of Technology. All Rights Reserved.</p>
            </div>
        </div>
    </footer>
<?php endif; ?>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const sidebarCollapse = document.getElementById("sidebarCollapse");
        const sidebar = document.getElementById("sidebar");
        const overlay = document.getElementById("sidebarOverlay");

        function closeSidebar() {
            if (sidebar) sidebar.classList.remove("active");
            if (overlay) overlay.classList.remove("active");
        }

        function toggleSidebar(e) {
            if (e) e.stopPropagation();
            if (sidebar) {
                sidebar.classList.toggle("active");
                if (window.innerWidth <= 768 && overlay) {
                    if (sidebar.classList.contains("active")) {
                        overlay.classList.add("active");
                    } else {
                        overlay.classList.remove("active");
                    }
                }
            }
        }

        if (sidebarCollapse) {
            sidebarCollapse.addEventListener("click", toggleSidebar);
        }

        if (overlay) {
            overlay.addEventListener("click", closeSidebar);
        }

        document.addEventListener("click", function(e) {
            if (window.innerWidth <= 768 && sidebar && sidebar.classList.contains("active")) {
                if (!sidebar.contains(e.target) && sidebarCollapse && !sidebarCollapse.contains(e.target)) {
                    closeSidebar();
                }
            }
        });

        window.addEventListener("resize", function() {
            if (window.innerWidth > 768 && overlay) {
                overlay.classList.remove("active");
            }
        });
    });
</script>
</body>

</html>