<?php
require_once __DIR__ . '/config/app.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (hasApprovedRole()) {
        header('Location: ' . getDashboardUrl($_SESSION['role_id']));
        exit();
    } else {
        header('Location: ' . BASE_URL . 'auth/apply_role.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Halal Institute of Development Philippines - The premier halal certification body ensuring halal compliance for businesses in the Philippines">
    <title>Halal Keeps</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar" id="mainNavbar">
    <div class="container">
        <a href="<?= BASE_URL ?>" class="navbar-brand">
            <div class="brand-icon">
                <i class="fas fa-certificate"></i>
            </div>
            HID Philippines
        </a>
        
        <div class="navbar-nav">
            <a href="#features">Features</a>
            <a href="#process">Process</a>
            <a href="#about">About</a>
            <a href="<?= BASE_URL ?>auth/login.php" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Sign In
            </a>
        </div>
        
        <button class="navbar-toggle" id="navToggle">
            <i class="fas fa-bars"></i>
        </button>
    </div>
</nav>

<!-- Hero Section -->
<section class="hero" id="hero">
    <div class="container">
        <div class="hero-content">
            <div class="hero-text animate-fade-in-up">
                <div class="hero-badge">
                    <i class="fas fa-shield-alt"></i>
                    Certified Halal Assurance
                </div>
                
                <h1>
                    Ensuring <span>Halal</span> Compliance for Every Filipino
                </h1>
                
                <p>
                    The Halal Institute of Development Philippines provides comprehensive halal certification 
                    services, ensuring that products and services meet the highest halal standards through 
                    rigorous evaluation, inspection, and laboratory analysis.
                </p>
                
                <div class="hero-buttons">
                    <a href="<?= BASE_URL ?>auth/login.php" class="btn btn-accent btn-lg">
                        <i class="fas fa-rocket"></i> Get Started
                    </a>
                    <a href="#process" class="btn btn-outline btn-lg">
                        <i class="fas fa-play-circle"></i> Learn More
                    </a>
                </div>
            </div>
            
            <div class="hero-visual animate-fade-in-up" style="animation-delay: 0.3s">
                <div class="hero-card">
                    <div class="card-icon">
                        <i class="fas fa-certificate"></i>
                    </div>
                    <h3>Halal Keeps Certification Portal</h3>
                    <p>
                        Complete digital platform for halal certification — from application 
                        to certificate issuance. Track your progress in real-time.
                    </p>
                    <div class="hero-stats">
                        <div class="hero-stat">
                            <div class="value">500+</div>
                            <div class="label">Certified</div>
                        </div>
                        <div class="hero-stat">
                            <div class="value">50+</div>
                            <div class="label">Auditors</div>
                        </div>
                        <div class="hero-stat">
                            <div class="value">99%</div>
                            <div class="label">Accuracy</div>
                        </div>
                    </div>
                </div>
                
                <div class="floating-badge" style="top: -10px; right: -30px;">
                    <div class="badge-dot green"></div>
                    Halal Verified
                </div>
                
                <div class="floating-badge" style="bottom: 30px; left: -40px; animation-delay: 1.5s;">
                    <div class="badge-dot gold"></div>
                    ISO Compliant
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="section" id="features" style="background: white;">
    <div class="container">
        <div class="section-header">
            <div class="section-badge">
                <i class="fas fa-star"></i> Our Services
            </div>
            <h2>Comprehensive Halal Certification</h2>
            <p>From application to certification, we provide end-to-end halal assurance services with full transparency.</p>
        </div>
        
        <div class="features-grid">
            <div class="feature-card card">
                <div class="feature-icon" style="background: var(--primary-100); color: var(--primary-700);">
                    <i class="fas fa-file-alt"></i>
                </div>
                <h3>Application Management</h3>
                <p>Submit and track your halal certification application online with real-time status updates and notifications.</p>
            </div>
            
            <div class="feature-card card">
                <div class="feature-icon" style="background: var(--accent-100); color: var(--accent-700);">
                    <i class="fas fa-search"></i>
                </div>
                <h3>Professional Auditing</h3>
                <p>Expert technical and Shariah auditors conduct thorough inspections to ensure complete halal compliance.</p>
            </div>
            
            <div class="feature-card card">
                <div class="feature-icon" style="background: #dbeafe; color: #1d4ed8;">
                    <i class="fas fa-flask"></i>
                </div>
                <h3>Laboratory Analysis</h3>
                <p>State-of-the-art laboratory testing with detailed analysis reports for complete product verification.</p>
            </div>
            
            <div class="feature-card card">
                <div class="feature-icon" style="background: #ede9fe; color: #6d28d9;">
                    <i class="fas fa-gavel"></i>
                </div>
                <h3>Impartial Review</h3>
                <p>Independent committee review ensures fair, unbiased certification decisions for every application.</p>
            </div>
            
            <div class="feature-card card">
                <div class="feature-icon" style="background: #fce7f3; color: #be185d;">
                    <i class="fas fa-award"></i>
                </div>
                <h3>Halal Certificate</h3>
                <p>Official halal certification with logo and certificate, recognized and trusted across the Philippines.</p>
            </div>
            
            <div class="feature-card card">
                <div class="feature-icon" style="background: #ccfbf1; color: #0d9488;">
                    <i class="fas fa-store"></i>
                </div>
                <h3>Restaurant Listing</h3>
                <p>Certified businesses are listed for customers to discover, order from, and review halal establishments.</p>
            </div>
        </div>
    </div>
</section>

<!-- Process Section -->
<section class="section" id="process" style="background: var(--neutral-50);">
    <div class="container">
        <div class="section-header">
            <div class="section-badge">
                <i class="fas fa-route"></i> How It Works
            </div>
            <h2>Certification Process</h2>
            <p>Follow our streamlined certification process to get your business halal certified.</p>
        </div>
        
        <div class="process-steps">
            <div class="process-step">
                <div class="step-number">1</div>
                <h4>Letter of Intent</h4>
                <p>Submit your letter of intent with company information and menu list</p>
            </div>
            
            <div class="process-step">
                <div class="step-number">2</div>
                <h4>Evaluation</h4>
                <p>Our evaluators verify your application and requirements</p>
            </div>
            
            <div class="process-step">
                <div class="step-number">3</div>
                <h4>Inspection</h4>
                <p>Technical and Shariah auditors conduct on-site inspections</p>
            </div>
            
            <div class="process-step">
                <div class="step-number">4</div>
                <h4>Certification</h4>
                <p>Receive your official Halal Certificate and logo</p>
            </div>
        </div>
        
        <!-- Detailed Steps -->
        <div style="margin-top: 60px;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
                <?php 
                $steps = [
                    ['num' => '1', 'title' => 'Create Letter of Intent', 'desc' => 'Business owner submits company information, menu list, and letter of intent', 'icon' => 'fas fa-pen-fancy'],
                    ['num' => '2', 'title' => 'LOI Verification', 'desc' => 'Evaluator verifies the letter of intent and provides feedback', 'icon' => 'fas fa-check-double'],
                    ['num' => '3', 'title' => 'Compile Requirements', 'desc' => 'Business owner compiles HDP application requirements and supporting documents', 'icon' => 'fas fa-folder-open'],
                    ['num' => '4', 'title' => 'Application Verification', 'desc' => 'Evaluator verifies application form and all requirements', 'icon' => 'fas fa-clipboard-check'],
                    ['num' => '5', 'title' => 'Terms of Reference', 'desc' => 'Creating terms of reference and registration agreement', 'icon' => 'fas fa-file-contract'],
                    ['num' => '6', 'title' => 'Inspection Schedule', 'desc' => 'Evaluator creates inspection schedule with dates, inspectors, and fees', 'icon' => 'fas fa-calendar-check'],
                    ['num' => '7', 'title' => 'Conduct Inspection', 'desc' => 'Technical and Shariah auditors conduct on-site inspection', 'icon' => 'fas fa-search-location'],
                    ['num' => '8', 'title' => 'NCR & Lab Analysis', 'desc' => 'Generate non-conformance reports and conduct laboratory analysis', 'icon' => 'fas fa-flask'],
                    ['num' => '9', 'title' => 'Committee Review', 'desc' => 'Impartial and Decision Committees review evidence and make decisions', 'icon' => 'fas fa-users'],
                    ['num' => '10', 'title' => 'Certificate Award', 'desc' => 'President awards the halal certificate and logo to the business', 'icon' => 'fas fa-award'],
                ];
                foreach ($steps as $i => $step):
                ?>
                <div class="card" style="padding: 24px; display: flex; gap: 16px; align-items: flex-start;">
                    <div style="min-width: 48px; height: 48px; border-radius: 12px; background: var(--primary-100); color: var(--primary-700); display: flex; align-items: center; justify-content: center; font-weight: 800; font-family: var(--font-display);">
                        <?= $step['num'] ?>
                    </div>
                    <div>
                        <h4 style="font-family: var(--font-display); font-weight: 700; margin-bottom: 4px; font-size: 0.95rem;"><?= $step['title'] ?></h4>
                        <p style="font-size: 0.85rem; color: var(--neutral-500); line-height: 1.5;"><?= $step['desc'] ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<!-- User Roles Section -->
<section class="section" id="about" style="background: white;">
    <div class="container">
        <div class="section-header">
            <div class="section-badge">
                <i class="fas fa-users"></i> User Roles
            </div>
            <h2>Who Uses Our System?</h2>
            <p>Our platform serves multiple stakeholders in the halal certification ecosystem.</p>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
            <?php
            $roles_display = [
                ['icon' => 'fas fa-user', 'title' => 'Customer', 'desc' => 'Browse halal restaurants, place orders, and leave reviews', 'color' => 'green'],
                ['icon' => 'fas fa-store', 'title' => 'Business Owner', 'desc' => 'Apply for halal certification and manage your restaurant', 'color' => 'gold'],
                ['icon' => 'fas fa-clipboard-check', 'title' => 'Evaluator', 'desc' => 'Verify applications and create inspection schedules', 'color' => 'blue'],
                ['icon' => 'fas fa-search', 'title' => 'Auditor', 'desc' => 'Conduct technical and Shariah compliance inspections', 'color' => 'purple'],
                ['icon' => 'fas fa-balance-scale', 'title' => 'Impartial Committee', 'desc' => 'Review evidence and make impartial certification recommendations', 'color' => 'teal'],
                ['icon' => 'fas fa-gavel', 'title' => 'Decision Committee', 'desc' => 'Make final decisions on halal certification grants', 'color' => 'red'],
                ['icon' => 'fas fa-crown', 'title' => 'President', 'desc' => 'Award certificates and oversee the certification body', 'color' => 'gold'],
                ['icon' => 'fas fa-inbox', 'title' => 'Receiving Officer', 'desc' => 'Receive and manage laboratory sample submissions', 'color' => 'blue'],
                ['icon' => 'fas fa-microscope', 'title' => 'Lab Analyst', 'desc' => 'Conduct laboratory analysis and generate reports', 'color' => 'purple'],
            ];
            foreach ($roles_display as $role):
            ?>
            <div class="card" style="padding: 24px; text-align: center; transition: all 0.3s ease;">
                <div style="width: 60px; height: 60px; border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; font-size: 1.3rem; 
                    <?php if($role['color'] == 'green'): ?>background: var(--primary-100); color: var(--primary-700);
                    <?php elseif($role['color'] == 'gold'): ?>background: var(--accent-100); color: var(--accent-700);
                    <?php elseif($role['color'] == 'blue'): ?>background: #dbeafe; color: #1d4ed8;
                    <?php elseif($role['color'] == 'purple'): ?>background: #ede9fe; color: #6d28d9;
                    <?php elseif($role['color'] == 'teal'): ?>background: #ccfbf1; color: #0d9488;
                    <?php elseif($role['color'] == 'red'): ?>background: #fee2e2; color: #dc2626;
                    <?php endif; ?>">
                    <i class="<?= $role['icon'] ?>"></i>
                </div>
                <h4 style="font-family: var(--font-display); font-weight: 700; margin-bottom: 8px;"><?= $role['title'] ?></h4>
                <p style="font-size: 0.85rem; color: var(--neutral-500); line-height: 1.5;"><?= $role['desc'] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="section" style="background: var(--gradient-hero); padding: 80px 0;">
    <div class="container" style="text-align: center;">
        <h2 style="font-family: var(--font-display); font-size: 2.5rem; font-weight: 800; color: white; margin-bottom: 16px;">
            Ready to Get Halal Certified?
        </h2>
        <p style="color: rgba(255,255,255,0.8); font-size: 1.1rem; max-width: 500px; margin: 0 auto 32px;">
            Join hundreds of businesses that have earned their halal certification through our platform.
        </p>
        <a href="<?= BASE_URL ?>auth/login.php" class="btn btn-accent btn-lg">
            <i class="fas fa-rocket"></i> Get Started Now
        </a>
    </div>
</section>

<!-- Footer -->
<footer class="main-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <h3>
                    <i class="fas fa-certificate" style="color: var(--primary-400);"></i>
                    HID Philippines
                </h3>
                <p>
                    The Halal Institute of Development Philippines is committed to ensuring 
                    halal compliance across the nation, providing trusted certification services 
                    for businesses and consumers alike.
                </p>
            </div>
            
            <div class="footer-links">
                <h4>Quick Links</h4>
                <a href="#features">Features</a>
                <a href="#process">Process</a>
                <a href="#about">About Us</a>
                <a href="<?= BASE_URL ?>auth/login.php">Sign In</a>
            </div>
            
            <div class="footer-links">
                <h4>Services</h4>
                <a href="#">Halal Certification</a>
                <a href="#">Laboratory Testing</a>
                <a href="#">Auditing Services</a>
                <a href="#">Consultancy</a>
            </div>
            
            <div class="footer-links">
                <h4>Contact</h4>
                <a href="#"><i class="fas fa-map-marker-alt"></i> Manila, Philippines</a>
                <a href="#"><i class="fas fa-phone"></i> +63 2 1234 5678</a>
                <a href="#"><i class="fas fa-envelope"></i> info@hidphilippines.com</a>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> Halal Institute of Development Philippines. All rights reserved.</p>
            <div style="display: flex; gap: 16px;">
                <a href="#" style="color: var(--neutral-400); transition: color 0.3s;">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <a href="#" style="color: var(--neutral-400); transition: color 0.3s;">
                    <i class="fab fa-twitter"></i>
                </a>
                <a href="#" style="color: var(--neutral-400); transition: color 0.3s;">
                    <i class="fab fa-instagram"></i>
                </a>
            </div>
        </div>
    </div>
</footer>

<script src="<?= BASE_URL ?>assets/js/main.js"></script>
</body>
</html>
