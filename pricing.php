<?php
require_once __DIR__ . '/auth.php';

// Store login state for Javascript access
$is_logged_in = is_logged_in() ? 'true' : 'false';
$logged_user_id = $_SESSION['user_id'] ?? 0;
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bit2byte - Professional Membership & SaaS Subscriptions</title>
    <meta name="description" content="Upgrade your Bit2byte membership. Choose from our Free, Student Premium, Professional Member, Company Partner, and Platinum Sponsor plans.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    
    <!-- Global stylesheets -->
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="animations.css">
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Pricing page custom styling -->
    <link rel="stylesheet" href="pricing.css">
</head>
<body class="saas-theme">
    <!-- Navbar (server-rendered, role-aware) -->
    <?php echo render_navbar(); ?>

    <main class="pricing-page-wrapper">
        <div class="pricing-container">
            
            <!-- SaaS Hero Header -->
            <header class="pricing-header">
                <span class="pricing-tagline">🚀 Elevate Your Coding Journey</span>
                <h1 class="pricing-title">Find the Perfect Plan for Your Goals</h1>
                <p class="pricing-desc">
                    Access premium resources, join exclusive events, recruit top student talent, or sponsor our growing developer community.
                </p>
            </header>

            <!-- Billing Toggle Switch -->
            <div class="billing-toggle-container">
                <span id="labelMonthly" class="toggle-label active">Monthly Billing</span>
                <input type="checkbox" id="billingCheckbox" class="billing-checkbox">
                <div id="switchWrapper" class="switch-wrapper" aria-label="Toggle billing cycle">
                    <div class="switch-trigger"></div>
                </div>
                <span id="labelYearly" class="toggle-label">
                    Yearly Billing <span class="discount-badge">Save ~20%</span>
                </span>
            </div>

            <!-- 5-Tier Pricing Grid -->
            <div class="pricing-grid saas-5-grid">
                
                <!-- Plan 1: Free Member -->
                <article class="pricing-card" data-plan="Free Member">
                    <div class="card-header">
                        <span class="plan-badge-label">Free</span>
                        <h2 class="card-title">Free Member</h2>
                        <p class="card-subtitle">Connect and learn the basics with the community</p>
                    </div>
                    <div class="price-display">
                        <span class="price-currency">৳</span>
                        <span class="price-amount" data-monthly="0" data-yearly="0">0</span>
                        <span class="price-period">/ month</span>
                    </div>
                    <ul class="card-features">
                        <li><span class="feature-icon"><i class="fas fa-check"></i></span> View public events</li>
                        <li><span class="feature-icon"><i class="fas fa-check"></i></span> View announcements</li>
                        <li><span class="feature-icon"><i class="fas fa-check"></i></span> Basic profile dashboard</li>
                        <li><span class="feature-icon"><i class="fas fa-check"></i></span> Join limited events</li>
                        <li class="disabled"><span class="feature-icon"><i class="fas fa-times"></i></span> Premium workshops</li>
                        <li class="disabled"><span class="feature-icon"><i class="fas fa-times"></i></span> Career mentorship & portal</li>
                    </ul>
                    <button class="card-cta secondary subscribe-btn" data-plan-id="1">Get Started</button>
                </article>

                <!-- Plan 2: Student Premium (Recommended) -->
                <article class="pricing-card popular" data-plan="Student Premium">
                    <div class="popular-badge">Recommended</div>
                    <div class="card-header">
                        <span class="plan-badge-label premium">SaaS Premium</span>
                        <h2 class="card-title">Student Premium</h2>
                        <p class="card-subtitle">Unlocks workshops, career preparation, and networking</p>
                    </div>
                    <div class="price-display">
                        <span class="price-currency">৳</span>
                        <span class="price-amount" data-monthly="99" data-yearly="999">99</span>
                        <span class="price-period">/ month</span>
                    </div>
                    <ul class="card-features">
                        <li><span class="feature-icon"><i class="fas fa-check"></i></span> Everything in Free</li>
                        <li><span class="feature-icon"><i class="fas fa-check"></i></span> Unlimited event registration</li>
                        <li><span class="feature-icon"><i class="fas fa-check"></i></span> Access to Premium workshops</li>
                        <li><span class="feature-icon"><i class="fas fa-check"></i></span> Interview & CP resources</li>
                        <li><span class="feature-icon"><i class="fas fa-check"></i></span> Resume review & Job board</li>
                        <li class="disabled"><span class="feature-icon"><i class="fas fa-times"></i></span> Industry mentorship</li>
                    </ul>
                    <button class="card-cta primary subscribe-btn" data-plan-id="2" data-plan-yearly-id="3">Upgrade Student</button>
                </article>

                <!-- Plan 3: Professional Member -->
                <article class="pricing-card" data-plan="Professional Member">
                    <div class="card-header">
                        <span class="plan-badge-label pro">Professional</span>
                        <h2 class="card-title">Professional</h2>
                        <p class="card-subtitle">For alumni and professionals looking to mentor/recruit</p>
                    </div>
                    <div class="price-display">
                        <span class="price-currency">৳</span>
                        <span class="price-amount" data-monthly="299" data-yearly="2999">299</span>
                        <span class="price-period">/ month</span>
                    </div>
                    <ul class="card-features">
                        <li><span class="feature-icon"><i class="fas fa-check"></i></span> Everything in Student Premium</li>
                        <li><span class="feature-icon"><i class="fas fa-check"></i></span> Career mentorship portal</li>
                        <li><span class="feature-icon"><i class="fas fa-check"></i></span> Industry recruitment networks</li>
                        <li><span class="feature-icon"><i class="fas fa-check"></i></span> Advanced certifications</li>
                        <li><span class="feature-icon"><i class="fas fa-check"></i></span> Profile verification badge</li>
                        <li><span class="feature-icon"><i class="fas fa-check"></i></span> Sponsor speaker slots</li>
                    </ul>
                    <button class="card-cta secondary subscribe-btn" data-plan-id="4" data-plan-yearly-id="5">Go Professional</button>
                </article>

                <!-- Plan 4: Company Partner -->
                <article class="pricing-card" data-plan="Company Partner">
                    <div class="card-header">
                        <span class="plan-badge-label partner">Partner</span>
                        <h2 class="card-title">Company Partner</h2>
                        <p class="card-subtitle">For software firms looking to recruit students</p>
                    </div>
                    <div class="price-display">
                        <span class="price-currency">৳</span>
                        <span class="price-amount" data-monthly="999" data-yearly="9999">999</span>
                        <span class="price-period">/ month</span>
                    </div>
                    <ul class="card-features">
                        <li><span class="feature-icon"><i class="fas fa-check"></i></span> Company profile page</li>
                        <li><span class="feature-icon"><i class="fas fa-check"></i></span> Post Jobs & Internships</li>
                        <li><span class="feature-icon"><i class="fas fa-check"></i></span> Manage student applicants</li>
                        <li><span class="feature-icon"><i class="fas fa-check"></i></span> Search talent database</li>
                        <li><span class="feature-icon"><i class="fas fa-check"></i></span> Run recruitment campaigns</li>
                        <li><span class="feature-icon"><i class="fas fa-check"></i></span> Logo branding on events</li>
                    </ul>
                    <button class="card-cta secondary subscribe-btn" data-plan-id="6" data-plan-yearly-id="7">Become Partner</button>
                </article>

                <!-- Plan 5: Platinum Sponsor -->
                <article class="pricing-card" data-plan="Platinum Sponsor">
                    <div class="card-header">
                        <span class="plan-badge-label sponsor">Sponsor</span>
                        <h2 class="card-title">Platinum Sponsor</h2>
                        <p class="card-subtitle">Premium branding & featured community placements</p>
                    </div>
                    <div class="price-display">
                        <span class="price-currency">Custom</span>
                        <span class="price-amount" data-monthly="Enterprise" data-yearly="Enterprise">Enterprise</span>
                        <span class="price-period"></span>
                    </div>
                    <ul class="card-features">
                        <li><span class="feature-icon"><i class="fas fa-check"></i></span> Featured homepage branding</li>
                        <li><span class="feature-icon"><i class="fas fa-check"></i></span> Primary event sponsorships</li>
                        <li><span class="feature-icon"><i class="fas fa-check"></i></span> Dedicated talent manager</li>
                        <li><span class="feature-icon"><i class="fas fa-check"></i></span> Custom hackathon host rights</li>
                        <li><span class="feature-icon"><i class="fas fa-check"></i></span> Advanced recruitment outreach</li>
                        <li><span class="feature-icon"><i class="fas fa-check"></i></span> Sponsorship Analytics</li>
                    </ul>
                    <button class="card-cta secondary contact-sponsor-btn">Contact Sales</button>
                </article>

            </div>

            <!-- Comparison Table -->
            <section class="comparison-section">
                <h2 class="section-subtitle">Detailed Feature Comparison</h2>
                <div class="comparison-table-wrapper">
                    <table class="comparison-table">
                        <thead>
                            <tr>
                                <th>Features</th>
                                <th>Free</th>
                                <th>Student Premium</th>
                                <th>Professional</th>
                                <th>Company Partner</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="feature-name">View Public Events & News</td>
                                <td><i class="fas fa-check text-success"></i></td>
                                <td><i class="fas fa-check text-success"></i></td>
                                <td><i class="fas fa-check text-success"></i></td>
                                <td><i class="fas fa-check text-success"></i></td>
                            </tr>
                            <tr>
                                <td class="feature-name">Dynamic Club Dashboards</td>
                                <td><i class="fas fa-check text-success"></i></td>
                                <td><i class="fas fa-check text-success"></i> (Premium UI)</td>
                                <td><i class="fas fa-check text-success"></i> (Pro UI)</td>
                                <td><i class="fas fa-check text-success"></i> (Recruiter UI)</td>
                            </tr>
                            <tr>
                                <td class="feature-name">Premium Workshops & CodeLabs</td>
                                <td><i class="fas fa-times text-muted"></i></td>
                                <td><i class="fas fa-check text-success"></i></td>
                                <td><i class="fas fa-check text-success"></i></td>
                                <td><i class="fas fa-times text-muted"></i></td>
                            </tr>
                            <tr>
                                <td class="feature-name">Coding Interview Prep Materials</td>
                                <td><i class="fas fa-times text-muted"></i></td>
                                <td><i class="fas fa-check text-success"></i></td>
                                <td><i class="fas fa-check text-success"></i></td>
                                <td><i class="fas fa-times text-muted"></i></td>
                            </tr>
                            <tr>
                                <td class="feature-name">Submit Resume to Recruiters</td>
                                <td><i class="fas fa-times text-muted"></i></td>
                                <td><i class="fas fa-check text-success"></i></td>
                                <td><i class="fas fa-check text-success"></i></td>
                                <td><i class="fas fa-times text-muted"></i></td>
                            </tr>
                            <tr>
                                <td class="feature-name">Post Jobs & Internships</td>
                                <td><i class="fas fa-times text-muted"></i></td>
                                <td><i class="fas fa-times text-muted"></i></td>
                                <td><i class="fas fa-times text-muted"></i></td>
                                <td><i class="fas fa-check text-success"></i> (Unlimited)</td>
                            </tr>
                            <tr>
                                <td class="feature-name">Search Student Talent Database</td>
                                <td><i class="fas fa-times text-muted"></i></td>
                                <td><i class="fas fa-times text-muted"></i></td>
                                <td><i class="fas fa-times text-muted"></i></td>
                                <td><i class="fas fa-check text-success"></i></td>
                            </tr>
                            <tr>
                                <td class="feature-name">Analytics & Engagement Stats</td>
                                <td><i class="fas fa-times text-muted"></i></td>
                                <td><i class="fas fa-times text-muted"></i></td>
                                <td><i class="fas fa-times text-muted"></i></td>
                                <td><i class="fas fa-check text-success"></i></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Testimonials Grid -->
            <section class="testimonials-section">
                <h2 class="section-subtitle">What Our Community Says</h2>
                <div class="testimonials-grid">
                    <div class="testimonial-card">
                        <p class="testimonial-text">
                            "The premium workshops helped me prep for coding interviews. I landed an internship at a top tech company through the Bit2byte company partner database!"
                        </p>
                        <div class="testimonial-author">
                            <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=100&h=100&fit=crop" alt="Student">
                            <div>
                                <h4>Tanvir Rahman</h4>
                                <span>Software Engineering Student</span>
                            </div>
                        </div>
                    </div>
                    <div class="testimonial-card">
                        <p class="testimonial-text">
                            "As a hiring partner, recruiting from Bit2Byte has been incredible. We have access to pre-vetted students who are ready to make a dynamic impact."
                        </p>
                        <div class="testimonial-author">
                            <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=100&h=100&fit=crop" alt="Recruiter">
                            <div>
                                <h4>Tasnim Ahmed</h4>
                                <span>HR Director, DevTech Solutions</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Accordion FAQ Grid -->
            <section class="faq-section">
                <h2 class="section-subtitle">Frequently Asked Questions</h2>
                <div class="faq-grid">
                    <div class="faq-item">
                        <button class="faq-question">What payment methods do you support? <i class="fas fa-chevron-down"></i></button>
                        <div class="faq-answer">
                            <p>We support bKash, Nagad, Rocket, SSLCommerz, and local credit/debit cards. Our gateway utilizes secure encryption protocols to protect your transactions.</p>
                        </div>
                    </div>
                    <div class="faq-item">
                        <button class="faq-question">Can I switch plans later? <i class="fas fa-chevron-down"></i></button>
                        <div class="faq-answer">
                            <p>Yes, you can upgrade, downgrade, or cancel your active subscription plan at any time directly through your Membership portal in the user dashboard.</p>
                        </div>
                    </div>
                    <div class="faq-item">
                        <button class="faq-question">How does company recruitment access work? <i class="fas fa-chevron-down"></i></button>
                        <div class="faq-answer">
                            <p>Registered Company Partners get access to a recruiters dashboard where they can post jobs/internships, review applicants, and browse profiles of premium student members who opt-in to recruitments.</p>
                        </div>
                    </div>
                </div>
            </section>

        </div>
    </main>

    <!-- Payment/Checkout Modal -->
    <div id="checkoutModal" class="saas-modal">
        <div class="modal-content">
            <header class="modal-header">
                <h3><i class="fas fa-shield-halved"></i> Secure Membership Checkout</h3>
                <button id="closeModalBtn" class="close-modal">&times;</button>
            </header>
            
            <form id="checkoutForm">
                <input type="hidden" id="checkoutPlanId" name="plan_id">
                <input type="hidden" id="checkoutBillingCycle" name="billing_cycle">
                
                <div class="checkout-summary-card">
                    <div class="summary-row">
                        <span class="summary-label">Membership Tier:</span>
                        <span id="summaryPlanName" class="summary-value">Student Premium</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Billing Cycle:</span>
                        <span id="summaryBillingCycle" class="summary-value">Monthly</span>
                    </div>
                    <div class="summary-row total-row">
                        <span class="summary-label">Total Amount:</span>
                        <span id="summaryPrice" class="summary-value text-accent">৳99.00</span>
                    </div>
                </div>

                <div class="form-group">
                    <label>Select Payment Gateway</label>
                    <div class="payment-gateway-selector">
                        <label class="gateway-option active">
                            <input type="radio" name="payment_method" value="bkash" checked>
                            <span class="gateway-logo bkash">bKash</span>
                        </label>
                        <label class="gateway-option">
                            <input type="radio" name="payment_method" value="nagad">
                            <span class="gateway-logo nagad">Nagad</span>
                        </label>
                        <label class="gateway-option">
                            <input type="radio" name="payment_method" value="card">
                            <span class="gateway-logo card">Visa/Mastercard</span>
                        </label>
                    </div>
                </div>

                <div class="form-group gateway-input-group" id="phoneGatewayInput">
                    <label for="gatewayPhone">Your Account Number</label>
                    <div class="input-with-icon">
                        <i class="fas fa-phone"></i>
                        <input type="text" id="gatewayPhone" name="gateway_phone" placeholder="01XXXXXXXXX" required>
                    </div>
                </div>

                <div class="form-group gateway-input-group" id="cardGatewayInput" style="display:none;">
                    <label for="gatewayCard">Card Details</label>
                    <div class="input-with-icon">
                        <i class="fas fa-credit-card"></i>
                        <input type="text" id="gatewayCard" placeholder="4111 2222 3333 4444">
                    </div>
                </div>

                <button type="submit" class="card-cta primary submit-payment-btn">
                    <span class="payment-btn-content">
                        Authorize Payment (Demo)
                        <span id="paymentLoader" class="btn-loader"></span>
                    </span>
                </button>
                
                <p class="modal-disclaimer">
                    <i class="fas fa-lock"></i> Secured payment processing demo. No real currency is transferred.
                </p>
            </form>
        </div>
    </div>

    <!-- Contact Sponsor Modal -->
    <div id="sponsorModal" class="saas-modal">
        <div class="modal-content">
            <header class="modal-header">
                <h3>Contact Sponsor Relations</h3>
                <button id="closeSponsorModalBtn" class="close-modal">&times;</button>
            </header>
            <form action="contact-submit.php" method="POST" class="inquiry-form">
                <div class="form-group">
                    <label for="sponsorName">Contact Person</label>
                    <input type="text" id="sponsorName" name="name" required>
                </div>
                <div class="form-group">
                    <label for="sponsorEmail">Corporate Email</label>
                    <input type="email" id="sponsorEmail" name="email" required>
                </div>
                <div class="form-group">
                    <label for="sponsorMessage">Sponsorship Interest Details</label>
                    <textarea id="sponsorMessage" name="message" rows="4" placeholder="Briefly describe your company sponsorship goals..." required></textarea>
                </div>
                <button type="submit" class="card-cta primary">Submit Sponsor Inquiry</button>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>Bit2byte</h4>
                    <p>Where Code Meets Creativity</p>
                </div>
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="index.php#about">About</a></li>
                        <li><a href="pricing.php">Services & Pricing</a></li>
                        <li><a href="index.php#events">Events</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Contact</h4>
                    <p>Email: info@bit2byte.com</p>
                    <p>Location: KUET Campus, Khulna</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 Bit2byte Coding Club. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Pass login states to pricing.js -->
    <script>
        const IS_LOGGED_IN = <?php echo $is_logged_in; ?>;
    </script>
    <script src="pricing.js"></script>
    <script>
        // Mobile nav toggle
        const navToggle = document.getElementById('navToggle');
        const navMenu = document.getElementById('navMenu');
        if (navToggle) {
            navToggle.addEventListener('click', () => {
                navMenu.classList.toggle('active');
                navToggle.classList.toggle('active');
            });
        }
    </script>
</body>
</html>
