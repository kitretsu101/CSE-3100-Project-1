<?php
require_once __DIR__ . '/../auth.php';
require_login();

// Guard against admins or companies accidentally ending up here
if ($_SESSION['role'] === 'admin') {
    header('Location: ../admin/dashboard.php');
    exit;
} elseif ($_SESSION['role'] === 'company') {
    header('Location: ../company/dashboard.php');
    exit;
}

$userId = $_SESSION['user_id'];
$user = find_user_by_id($userId);

// Fetch active subscription
$subscription = get_active_subscription($userId);
$planName = $subscription['plan_name'] ?? 'Free Member';
$startDate = $subscription['start_date'] ?? '-';
$endDate = $subscription['end_date'] ?? null;
$daysRemaining = $endDate ? get_days_remaining($endDate) : null;

// Determine access state
$isPremium = has_premium_access($userId);

// Handle Mock Resume upload (stores resume link in session/db)
$resumeMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resume_action'])) {
    if (!$isPremium) {
        $resumeMessage = "<span class='text-danger'>🔒 Resume portal is a premium feature. Please upgrade.</span>";
    } else {
        $resumeUrl = htmlspecialchars(trim($_POST['resume_url'] ?? ''), ENT_QUOTES, 'UTF-8');
        if ($resumeUrl) {
            // Store resume URL in user session or update student details
            $_SESSION['resume_url'] = $resumeUrl;
            
            // Log it in database by updating student_id or phone (we can mock it in users metadata or simple update)
            global $conn;
            $stmt = $conn->prepare("UPDATE users SET phone = ? WHERE id = ?"); // We use phone field to stash resume for simplicity
            $stmt->bind_param("si", $resumeUrl, $userId);
            $stmt->execute();
            $stmt->close();
            
            $resumeMessage = "<span class='text-success'>✓ Resume URL saved successfully! Companies can now view it.</span>";
        }
    }
}

// Fetch resume from database (using phone field as mock storage)
$userResume = $user['phone'] ?? '';

// Handle job application
$jobApplyMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_job_id'])) {
    if (!$isPremium) {
        $jobApplyMessage = "<span class='text-danger'>🔒 Job applications are reserved for Premium members.</span>";
    } else {
        $jobId = intval($_POST['apply_job_id']);
        if (empty($userResume)) {
            $jobApplyMessage = "<span class='text-danger'>⚠️ Please upload your resume first before applying.</span>";
        } else {
            global $conn;
            // Check if already applied
            $stmt = $conn->prepare("SELECT id FROM job_applications WHERE job_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $jobId, $userId);
            $stmt->execute();
            $applied = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if ($applied) {
                $jobApplyMessage = "<span class='text-warning'>⚠️ You have already applied for this position.</span>";
            } else {
                $stmt = $conn->prepare("INSERT INTO job_applications (job_id, user_id, resume_url) VALUES (?, ?, ?)");
                $stmt->bind_param("iis", $jobId, $userId, $userResume);
                $stmt->execute();
                $stmt->close();
                $jobApplyMessage = "<span class='text-success'>✓ Application submitted successfully to the company!</span>";
            }
        }
    }
}

// Fetch active jobs from company profiles
global $conn;
$jobs = [];
$r = $conn->query("
    SELECT jp.id, jp.title, jp.type, jp.description, jp.requirements, cp.company_name, cp.industry 
    FROM job_postings jp
    JOIN company_profiles cp ON jp.company_id = cp.id
    WHERE jp.status = 'active'
    ORDER BY jp.created_at DESC
");
if ($r) {
    while ($row = $r->fetch_assoc()) {
        $jobs[] = $row;
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bit2byte - Member Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../animations.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background-color: #0b1116;
            color: #f8fafc;
            font-family: 'Poppins', sans-serif;
        }
        .dashboard-wrapper {
            max-width: 1200px;
            margin: 4rem auto;
            padding: 0 1.5rem;
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 2rem;
            align-items: start;
        }
        .profile-sidebar {
            background-color: #111a22;
            border: 1px solid rgba(32, 178, 170, 0.15);
            border-radius: 16px;
            padding: 2rem;
            text-align: center;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #20b2aa, #17a2b8);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: #ffffff;
            margin: 0 auto 1.5rem;
            font-weight: 700;
        }
        .profile-name {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 0.3rem;
        }
        .profile-role-badge {
            background-color: rgba(32, 178, 170, 0.1);
            color: #20b2aa;
            padding: 0.3rem 1rem;
            font-size: 0.75rem;
            font-weight: 700;
            border-radius: 20px;
            text-transform: uppercase;
            border: 1px solid rgba(32, 178, 170, 0.2);
            margin-bottom: 1.5rem;
            display: inline-block;
        }
        .profile-details {
            text-align: left;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            padding-top: 1.5rem;
            font-size: 0.88rem;
            color: #94a3b8;
        }
        .detail-row {
            margin-bottom: 0.8rem;
            display: flex;
            justify-content: space-between;
        }
        .detail-label {
            font-weight: 600;
        }
        
        /* Membership Stats Card */
        .membership-card {
            background: linear-gradient(135deg, #111a22, #0d141b);
            border: 1.5px solid rgba(32, 178, 170, 0.2);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            position: relative;
        }
        .plan-badge {
            float: right;
            padding: 0.3rem 1.2rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            color: #fff;
        }
        .plan-badge.free { background-color: #64748b; }
        .plan-badge.premium { background-color: #f59e0b; box-shadow: 0 0 15px rgba(245, 158, 11, 0.2); }
        .plan-badge.pro { background-color: #8b5cf6; }
        .plan-badge.partner { background-color: #3b82f6; }
        .plan-badge.sponsor { background-color: #ec4899; }
        
        .membership-title {
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #f8fafc, #20b2aa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .progress-container {
            margin-top: 1.5rem;
        }
        .progress-bar-bg {
            background-color: #1a2530;
            border-radius: 10px;
            height: 12px;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.05);
        }
        .progress-bar-fill {
            background: linear-gradient(90deg, #20b2aa, #10b981);
            height: 100%;
            border-radius: 10px;
            transition: width 0.5s ease;
        }
        .progress-text {
            display: flex;
            justify-content: space-between;
            margin-top: 0.5rem;
            font-size: 0.82rem;
            color: #94a3b8;
        }
        .renew-btn {
            background: linear-gradient(135deg, #20b2aa, #17a2b8);
            color: #ffffff;
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.88rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-top: 1rem;
            transition: 0.2s ease;
        }
        .renew-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(32, 178, 170, 0.3);
        }
        
        /* Features and Locking Overlay */
        .workspace-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid rgba(32, 178, 170, 0.15);
            padding-bottom: 0.5rem;
        }
        
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        
        .feature-card {
            background-color: #111a22;
            border: 1px solid rgba(32, 178, 170, 0.15);
            border-radius: 16px;
            padding: 1.8rem;
            position: relative;
            min-height: 250px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        /* Blurred Locking Gate overlay */
        .lock-overlay {
            position: absolute;
            inset: 0;
            background: rgba(17, 26, 34, 0.85);
            backdrop-filter: blur(8px);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            text-align: center;
            z-index: 10;
        }
        .lock-icon {
            font-size: 2.2rem;
            color: #f59e0b;
            margin-bottom: 1rem;
        }
        .lock-title {
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }
        .lock-desc {
            font-size: 0.82rem;
            color: #94a3b8;
            margin-bottom: 1.2rem;
            line-height: 1.4;
        }
        .lock-upgrade-btn {
            background-color: #f59e0b;
            color: #111a22;
            font-weight: 700;
            font-size: 0.8rem;
            padding: 0.5rem 1.2rem;
            border-radius: 6px;
            text-decoration: none;
            transition: 0.2s ease;
        }
        .lock-upgrade-btn:hover {
            background-color: #d97706;
        }
        
        /* Resume form */
        .resume-form-group {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        .resume-form-group input {
            flex: 1;
            padding: 0.6rem 1rem;
            background-color: #0b1116;
            border: 1px solid rgba(32, 178, 170, 0.3);
            border-radius: 8px;
            color: #ffffff;
            font-size: 0.88rem;
        }
        .resume-form-group input:focus {
            outline: none;
            border-color: #20b2aa;
        }
        
        /* Jobs List and applications */
        .jobs-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .job-card {
            background-color: #0f1820;
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 12px;
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .job-card:hover {
            border-color: rgba(32, 178, 170, 0.2);
        }
        .job-info h4 {
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 0.3rem;
            color: #ffffff;
        }
        .job-meta {
            font-size: 0.8rem;
            color: #94a3b8;
            display: flex;
            gap: 1rem;
        }
        .job-meta span i {
            color: #20b2aa;
            margin-right: 0.3rem;
        }
        .job-badge {
            background-color: rgba(16, 185, 129, 0.1);
            color: #10b981;
            padding: 0.2rem 0.6rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .apply-job-btn {
            background: none;
            border: 1.5px solid var(--primary-color);
            color: var(--primary-color);
            padding: 0.5rem 1.2rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s ease;
        }
        .apply-job-btn:hover {
            background-color: var(--primary-color);
            color: #ffffff;
        }
        
        .alert-box {
            padding: 0.8rem 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php echo render_navbar(); ?>

    <main class="dashboard-wrapper">
        <div class="dashboard-grid">
            
            <!-- Sidebar -->
            <aside class="profile-sidebar">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                </div>
                <h3 class="profile-name"><?php echo htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                <span class="profile-role-badge">Member @bit2byte</span>
                
                <div class="profile-details">
                    <div class="detail-row">
                        <span class="detail-label">Username:</span>
                        <span><?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Email:</span>
                        <span><?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Student ID:</span>
                        <span><?php echo htmlspecialchars($user['student_id'] ?: 'Not Provided', ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Department:</span>
                        <span><?php echo htmlspecialchars($user['department'] ?: 'Not Provided', ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                </div>
            </aside>

            <!-- Main Content Workspace -->
            <section class="dashboard-workspace">
                
                <!-- 1. Membership Subscription Stats Panel -->
                <div class="membership-card">
                    <!-- Dynamic Tier Badge -->
                    <span class="plan-badge <?php 
                        if ($planName === 'Free Member') echo 'free';
                        elseif ($planName === 'Student Premium') echo 'premium';
                        elseif ($planName === 'Professional Member') echo 'pro';
                        else echo 'sponsor';
                    ?>">
                        <?php echo htmlspecialchars($planName, ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                    
                    <h2 class="membership-title">Subscription Overview</h2>
                    <p style="color:#94a3b8; font-size:0.9rem;">
                        Your current account tier dictates active privileges on the Bit2Byte community platform.
                    </p>
                    
                    <!-- Progress Card -->
                    <div class="progress-container">
                        <div class="progress-bar-bg">
                            <div class="progress-bar-fill" style="width: <?php 
                                if ($planName === 'Free Member') echo '100%';
                                elseif ($daysRemaining === null) echo '100%';
                                else echo (($daysRemaining / 365) * 100) . '%';
                            ?>;"></div>
                        </div>
                        <div class="progress-text">
                            <span>Status: <strong>Active</strong></span>
                            <span>
                                <?php 
                                    if ($planName === 'Free Member') {
                                        echo 'Lifetime Free Account';
                                    } elseif ($daysRemaining === null) {
                                        echo 'Unlimited Access';
                                    } else {
                                        echo "Expires in {$daysRemaining} Days (End Date: {$endDate})";
                                    }
                                ?>
                            </span>
                        </div>
                    </div>
                    
                    <div style="display:flex; gap: 1rem;">
                        <a href="../pricing.php" class="renew-btn">
                            <i class="fas fa-chevron-up"></i> Upgrade / Manage Plan
                        </a>
                    </div>
                </div>

                <!-- Alert Message from Actions -->
                <?php if ($resumeMessage): ?>
                    <div class="alert-box" style="background-color:rgba(32,178,170,0.1); border:1px solid var(--primary-color); color:var(--primary-color);">
                        <?php echo $resumeMessage; ?>
                    </div>
                <?php endif; ?>

                <?php if ($jobApplyMessage): ?>
                    <div class="alert-box" style="background-color:rgba(16,185,129,0.1); border:1px solid #10b981; color:#10b981;">
                        <?php echo $jobApplyMessage; ?>
                    </div>
                <?php endif; ?>

                <!-- 2. Feature locking Grid -->
                <h3 class="workspace-title">SaaS Premium Workspace</h3>
                
                <div class="feature-grid">
                    
                    <!-- Feature Card 1: Resume Submission Portal -->
                    <article class="feature-card">
                        <?php if (!$isPremium): ?>
                            <div class="lock-overlay">
                                <i class="fas fa-lock lock-icon"></i>
                                <h4 class="lock-title">Premium Feature</h4>
                                <p class="lock-desc">Upgrade to Student Premium to submit your resume to tech recruiters and company partners.</p>
                                <a href="../pricing.php" class="lock-upgrade-btn">Unlock Now</a>
                            </div>
                        <?php endif; ?>
                        
                        <h4 style="font-weight:700; margin-bottom: 0.8rem;"><i class="fas fa-file-pdf text-accent" style="color:var(--primary-color);"></i> Resume Review Portal</h4>
                        <p style="font-size:0.85rem; color:#94a3b8; line-height: 1.5; margin-bottom: 1.5rem;">
                            Provide your live online resume URL (Google Drive, GitHub Pages, or LinkedIn). Verified company recruiters will review this for active jobs.
                        </p>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="resume_action" value="save">
                            <div class="resume-form-group">
                                <input type="url" name="resume_url" placeholder="https://drive.google.com/..." value="<?php echo htmlspecialchars($userResume, ENT_QUOTES, 'UTF-8'); ?>" required>
                                <button type="submit" class="renew-btn" style="margin-top:0; padding:0.5rem 1rem;">Save</button>
                            </div>
                        </form>
                        <?php if (!empty($userResume)): ?>
                            <p style="font-size: 0.78rem; color:#10b981; margin-top: 0.8rem;">
                                <i class="fas fa-circle-check"></i> Resume link is active on your profile.
                            </p>
                        <?php endif; ?>
                    </article>

                    <!-- Feature Card 2: Interview prep materials -->
                    <article class="feature-card">
                        <?php if (!$isPremium): ?>
                            <div class="lock-overlay">
                                <i class="fas fa-lock lock-icon"></i>
                                <h4 class="lock-title">Premium Feature</h4>
                                <p class="lock-desc">Upgrade to Student Premium to unlock interview mock scripts, CP problems, and learning modules.</p>
                                <a href="../pricing.php" class="lock-upgrade-btn">Unlock Now</a>
                            </div>
                        <?php endif; ?>
                        
                        <h4 style="font-weight:700; margin-bottom: 0.8rem;"><i class="fas fa-laptop-code" style="color:var(--primary-color);"></i> Interview & CP Resources</h4>
                        <p style="font-size:0.85rem; color:#94a3b8; line-height: 1.5; margin-bottom: 1rem;">
                            Curated resources compiled by our coding club alumni who now work at Google, Meta, and top local tech firms:
                        </p>
                        <ul style="font-size: 0.82rem; color:#cbd5e1; list-style-type:square; padding-left: 1.2rem; display: flex; flex-direction:column; gap:0.4rem;">
                            <li>LeetCode Patterns & Solutions Guide</li>
                            <li>Advanced Competitive Programming Algorithms</li>
                            <li>System Design Cheat Sheets</li>
                            <li>Mock Technical Interview Videos</li>
                        </ul>
                    </article>

                </div>

                <!-- 3. Recruitment Jobs board (Premium Gate) -->
                <div style="position:relative; background-color:#111a22; border:1px solid rgba(32,178,170,0.15); border-radius:16px; padding:2rem; margin-bottom: 4rem;">
                    <?php if (!$isPremium): ?>
                        <div class="lock-overlay">
                            <i class="fas fa-lock lock-icon"></i>
                            <h4 class="lock-title">Premium Feature</h4>
                            <p class="lock-desc">Upgrade to Student Premium to unlock recruiter dashboards and apply directly to software roles.</p>
                            <a href="../pricing.php" class="lock-upgrade-btn">Unlock Now</a>
                        </div>
                    <?php endif; ?>

                    <h3 style="font-weight:700; font-size:1.3rem; margin-bottom: 0.5rem;"><i class="fas fa-briefcase text-accent" style="color:#20b2aa;"></i> Partner Job & Internship Openings</h3>
                    <p style="font-size:0.85rem; color:#94a3b8; margin-bottom: 1.5rem;">
                        Browse active career opportunities posted by Bit2Byte company sponsors. Apply directly using your uploaded resume.
                    </p>

                    <div class="jobs-list">
                        <?php if (empty($jobs)): ?>
                            <p style="color:#94a3b8; font-size:0.9rem; text-align:center; padding: 1.5rem;">No active opportunities listed at the moment.</p>
                        <?php else: ?>
                            <?php foreach ($jobs as $j): ?>
                                <div class="job-card">
                                    <div class="job-info">
                                        <div style="display:flex; gap: 0.5rem; align-items:center; margin-bottom: 0.3rem;">
                                            <h4><?php echo htmlspecialchars($j['title'], ENT_QUOTES, 'UTF-8'); ?></h4>
                                            <span class="job-badge"><?php echo htmlspecialchars($j['type'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        </div>
                                        <div class="job-meta">
                                            <span><i class="fas fa-building"></i> <?php echo htmlspecialchars($j['company_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                            <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($j['industry'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        </div>
                                        <p style="font-size:0.8rem; color:#cbd5e1; margin-top:0.6rem; max-width:600px;">
                                            <strong>Description:</strong> <?php echo htmlspecialchars($j['description'], ENT_QUOTES, 'UTF-8'); ?><br/>
                                            <strong>Requirements:</strong> <?php echo htmlspecialchars($j['requirements'], ENT_QUOTES, 'UTF-8'); ?>
                                        </p>
                                    </div>
                                    <form method="POST" action="">
                                        <input type="hidden" name="apply_job_id" value="<?php echo $j['id']; ?>">
                                        <button type="submit" class="apply-job-btn">Apply Now</button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

            </section>
            
        </div>
    </main>

    <footer class="footer">
        <div class="footer-container">
            <div class="footer-bottom" style="text-align:center;">
                <p>&copy; 2026 Bit2byte Coding Club. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>
