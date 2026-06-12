<?php
require_once __DIR__ . '/../auth.php';

// Access Gate
if (!is_logged_in() || $_SESSION['role'] !== 'company') {
    header('Location: ../user-login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Get company profile
global $conn;
$stmt = $conn->prepare("SELECT id, company_name, industry, website, logo, description, contact_email FROM company_profiles WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$company = $stmt->get_result()->fetch_assoc();
$stmt->close();

// If company profile missing, seed a default one
if (!$company) {
    $companyName = $_SESSION['username'] . ' Solutions';
    $contactEmail = $_SESSION['email'];
    $industry = 'Software';
    $stmt = $conn->prepare("INSERT INTO company_profiles (user_id, company_name, industry, contact_email) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $userId, $companyName, $industry, $contactEmail);
    $stmt->execute();
    $stmt->close();
    
    // Refetch
    $stmt = $conn->prepare("SELECT id, company_name, industry, website, logo, description, contact_email FROM company_profiles WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $company = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$companyId = $company['id'];
$profileUpdated = '';
$jobPosted = '';
$appUpdated = '';

// ─── 1. Handle Profile Updates ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type']) && $_POST['action_type'] === 'update_profile') {
    $compName = htmlspecialchars(trim($_POST['company_name'] ?? ''), ENT_QUOTES, 'UTF-8');
    $compInd  = htmlspecialchars(trim($_POST['industry'] ?? ''), ENT_QUOTES, 'UTF-8');
    $compWeb  = htmlspecialchars(trim($_POST['website'] ?? ''), ENT_QUOTES, 'UTF-8');
    $compMail = htmlspecialchars(trim($_POST['contact_email'] ?? ''), ENT_QUOTES, 'UTF-8');
    $compDesc = htmlspecialchars(trim($_POST['description'] ?? ''), ENT_QUOTES, 'UTF-8');
    
    if ($compName && $compMail) {
        $stmt = $conn->prepare("UPDATE company_profiles SET company_name = ?, industry = ?, website = ?, contact_email = ?, description = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $compName, $compInd, $compWeb, $compMail, $compDesc, $companyId);
        if ($stmt->execute()) {
            $profileUpdated = "<span class='text-success'>✓ Company profile updated successfully!</span>";
            // Refresh variables
            $company['company_name'] = $compName;
            $company['industry'] = $compInd;
            $company['website'] = $compWeb;
            $company['contact_email'] = $compMail;
            $company['description'] = $compDesc;
        } else {
            $profileUpdated = "<span class='text-danger'>⚠️ Profile update failed. Try again.</span>";
        }
        $stmt->close();
    }
}

// ─── 2. Handle Job Postings ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type']) && $_POST['action_type'] === 'post_job') {
    $jobTitle = htmlspecialchars(trim($_POST['job_title'] ?? ''), ENT_QUOTES, 'UTF-8');
    $jobType  = htmlspecialchars(trim($_POST['job_type'] ?? ''), ENT_QUOTES, 'UTF-8');
    $jobDesc  = htmlspecialchars(trim($_POST['job_desc'] ?? ''), ENT_QUOTES, 'UTF-8');
    $jobReq   = htmlspecialchars(trim($_POST['job_req'] ?? ''), ENT_QUOTES, 'UTF-8');
    
    if ($jobTitle && $jobType) {
        $stmt = $conn->prepare("INSERT INTO job_postings (company_id, title, type, description, requirements) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $companyId, $jobTitle, $jobType, $jobDesc, $jobReq);
        if ($stmt->execute()) {
            $jobPosted = "<span class='text-success'>✓ Job position posted successfully!</span>";
        } else {
            $jobPosted = "<span class='text-danger'>⚠️ Posting failed. Try again.</span>";
        }
        $stmt->close();
    }
}

// ─── 3. Handle Job Application Status Changes ───────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type']) && $_POST['action_type'] === 'update_app_status') {
    $appId  = intval($_POST['app_id'] ?? 0);
    $status = htmlspecialchars(trim($_POST['status'] ?? ''), ENT_QUOTES, 'UTF-8');
    
    if ($appId && in_array($status, ['pending', 'reviewed', 'accepted', 'rejected'])) {
        $stmt = $conn->prepare("
            UPDATE job_applications ja
            JOIN job_postings jp ON ja.job_id = jp.id
            SET ja.status = ?
            WHERE ja.id = ? AND jp.company_id = ?
        ");
        $stmt->bind_param("sii", $status, $appId, $companyId);
        if ($stmt->execute()) {
            $appUpdated = "<span class='text-success'>✓ Application status updated to " . ucfirst($status) . ".</span>";
        }
        $stmt->close();
    }
}

// ─── 4. Fetch Active Job Postings ────────────────────────────────────
$postings = [];
$stmt = $conn->prepare("SELECT id, title, type, status, created_at FROM job_postings WHERE company_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $companyId);
$stmt->execute();
$r = $stmt->get_result();
while ($row = $r->fetch_assoc()) {
    $postings[] = $row;
}
$stmt->close();

// ─── 5. Fetch Candidates / Applicants ────────────────────────────────
$applicants = [];
$stmt = $conn->prepare("
    SELECT ja.id as app_id, ja.resume_url, ja.status as app_status, ja.created_at,
           jp.title as job_title, u.full_name, u.email as candidate_email, u.department
    FROM job_applications ja
    JOIN job_postings jp ON ja.job_id = jp.id
    JOIN users u ON ja.user_id = u.id
    WHERE jp.company_id = ?
    ORDER BY ja.created_at DESC
");
$stmt->bind_param("i", $companyId);
$stmt->execute();
$r = $stmt->get_result();
while ($row = $r->fetch_assoc()) {
    $applicants[] = $row;
}
$stmt->close();

// Calculate Analytics
$totalPostings = count($postings);
$totalApplicants = count($applicants);
$profileViews = 150 + ($totalApplicants * 4); // Mock view counter formula
$engagementRate = $totalPostings > 0 ? round(($totalApplicants / ($totalPostings * 10)) * 100, 1) : 0.0;
if ($engagementRate > 100) $engagementRate = 95.8;
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bit2byte - Company Partner Workspace</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../animations.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background-color: #090e12;
            color: #f8fafc;
            font-family: 'Poppins', sans-serif;
        }
        .dashboard-wrapper {
            max-width: 1300px;
            margin: 4rem auto;
            padding: 0 1.5rem;
        }
        .workspace-header {
            border-bottom: 1px solid rgba(59, 130, 246, 0.2);
            padding-bottom: 1.5rem;
            margin-bottom: 2.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .workspace-title {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #f8fafc, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* Stats widgets */
        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        .stat-card {
            background-color: #111822;
            border: 1px solid rgba(59, 130, 246, 0.15);
            border-radius: 14px;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1.2rem;
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
        }
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background-color: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .stat-info h3 {
            font-size: 1.8rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 0.3rem;
        }
        .stat-info p {
            color: #94a3b8;
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Main Layout Grid */
        .layout-grid {
            display: grid;
            grid-template-columns: 1fr 450px;
            gap: 2rem;
            align-items: start;
        }
        
        .main-workspace {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }
        
        .workspace-card {
            background-color: #111822;
            border: 1px solid rgba(59, 130, 246, 0.15);
            border-radius: 16px;
            padding: 2.2rem 2rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.4);
        }
        .card-subtitle {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 1.2rem;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            padding-bottom: 0.6rem;
        }
        .card-subtitle i {
            color: #3b82f6;
        }
        
        /* Form inputs */
        .form-group {
            margin-bottom: 1.2rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.88rem;
            font-weight: 600;
            color: #cbd5e1;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem 1rem;
            background-color: #070b0f;
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 10px;
            color: #ffffff;
            font-size: 0.9rem;
            transition: 0.2s ease;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 10px rgba(59, 130, 246, 0.2);
        }
        
        .submit-btn {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: #ffffff;
            border: none;
            padding: 0.75rem 1.8rem;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.92rem;
            cursor: pointer;
            transition: 0.2s ease;
            box-shadow: 0 4px 10px rgba(59, 130, 246, 0.25);
        }
        .submit-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 15px rgba(59, 130, 246, 0.45);
        }
        
        /* Applicants / Job lists */
        .record-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .record-item {
            background-color: #070b0f;
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 12px;
            padding: 1.2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .record-info h5 {
            font-size: 1.05rem;
            font-weight: 700;
            margin-bottom: 0.2rem;
            color: #ffffff;
        }
        .record-meta {
            font-size: 0.8rem;
            color: #94a3b8;
            display: flex;
            gap: 1rem;
            margin-top: 0.3rem;
        }
        .record-meta i {
            color: #3b82f6;
        }
        
        .status-badge {
            padding: 0.25rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .status-badge.pending { background-color: rgba(245, 158, 11, 0.15); color: #f59e0b; }
        .status-badge.reviewed { background-color: rgba(59, 130, 246, 0.15); color: #3b82f6; }
        .status-badge.accepted { background-color: rgba(16, 185, 129, 0.15); color: #10b981; }
        .status-badge.rejected { background-color: rgba(239, 68, 68, 0.15); color: #ef4444; }
        
        .app-action-form {
            display: flex;
            gap: 0.4rem;
        }
        .app-action-select {
            padding: 0.4rem;
            font-size: 0.8rem;
            background-color: #111822;
            color: #ffffff;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 6px;
        }
        .app-action-btn {
            background-color: rgba(59, 130, 246, 0.15);
            color: #3b82f6;
            border: none;
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
        }
        
        .alert-box {
            padding: 0.8rem 1.2rem;
            border-radius: 8px;
            font-weight: 600;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
        
        @media (max-width: 1024px) {
            .layout-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php echo render_navbar(); ?>

    <main class="dashboard-wrapper">
        
        <!-- Header -->
        <header class="workspace-header">
            <div>
                <h1 class="workspace-title">Company Workspace</h1>
                <p style="color:#94a3b8; font-size:0.95rem; margin-top:0.3rem;">
                    Manage your software recruiting campaigns, listings, and student applications.
                </p>
            </div>
            <span class="status-badge reviewed" style="background-color:rgba(59, 130, 246, 0.1); padding: 0.5rem 1.5rem; font-size:0.85rem;">
                <i class="fas fa-handshake"></i> Verified Partner
            </span>
        </header>

        <!-- Analytics counters -->
        <div class="analytics-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-briefcase"></i></div>
                <div class="stat-info">
                    <h3><?php echo $totalPostings; ?></h3>
                    <p>Job Listings</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-info">
                    <h3><?php echo $totalApplicants; ?></h3>
                    <p>Total Applicants</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-eye"></i></div>
                <div class="stat-info">
                    <h3><?php echo $profileViews; ?></h3>
                    <p>Company Views</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                <div class="stat-info">
                    <h3><?php echo $engagementRate; ?>%</h3>
                    <p>Student Engagement</p>
                </div>
            </div>
        </div>

        <!-- Layout Grid -->
        <div class="layout-grid">
            
            <!-- Left Workspace: Recruiting and Applicants -->
            <div class="main-workspace">
                
                <!-- Feedback banners -->
                <?php if ($appUpdated): ?>
                    <div class="alert-box" style="background-color:rgba(59,130,246,0.1); border:1px solid #3b82f6; color:#3b82f6;">
                        <?php echo $appUpdated; ?>
                    </div>
                <?php endif; ?>

                <?php if ($jobPosted): ?>
                    <div class="alert-box" style="background-color:rgba(16,185,129,0.1); border:1px solid #10b981; color:#10b981;">
                        <?php echo $jobPosted; ?>
                    </div>
                <?php endif; ?>

                <!-- Active Applicants list -->
                <section class="workspace-card">
                    <h3 class="card-subtitle"><i class="fas fa-user-graduate"></i> Manage Student Applications</h3>
                    
                    <div class="record-list">
                        <?php if (empty($applicants)): ?>
                            <p style="color:#94a3b8; font-size:0.9rem; text-align:center; padding:2rem 0;">No student applicants yet.</p>
                        <?php else: ?>
                            <?php foreach ($applicants as $app): ?>
                                <div class="record-item">
                                    <div class="record-info">
                                        <h5><?php echo htmlspecialchars($app['full_name'], ENT_QUOTES, 'UTF-8'); ?></h5>
                                        <div class="record-meta">
                                            <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($app['candidate_email'], ENT_QUOTES, 'UTF-8'); ?></span>
                                            <span><i class="fas fa-graduation-cap"></i> <?php echo htmlspecialchars($app['department'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        </div>
                                        <div class="record-meta" style="margin-top:0.5rem; font-weight:600; color:#ffffff;">
                                            <span>Applying for: <strong class="text-accent" style="color:#3b82f6;"><?php echo htmlspecialchars($app['job_title'], ENT_QUOTES, 'UTF-8'); ?></strong></span>
                                            <span>Applied: <?php echo date('d M Y', strtotime($app['created_at'])); ?></span>
                                        </div>
                                        
                                        <!-- Resume download -->
                                        <div style="margin-top:0.8rem;">
                                            <a href="<?php echo htmlspecialchars($app['resume_url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="status-badge reviewed" style="text-decoration:none; padding:0.3rem 0.8rem; font-size:0.8rem; display:inline-block;">
                                                <i class="fas fa-external-link-alt"></i> View Resume Link
                                            </a>
                                        </div>
                                    </div>
                                    
                                    <!-- Status update -->
                                    <div style="text-align:right;">
                                        <div style="margin-bottom:0.8rem;">
                                            <span class="status-badge <?php echo $app['app_status']; ?>">
                                                <?php echo htmlspecialchars($app['app_status'], ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        </div>
                                        <form method="POST" action="" class="app-action-form">
                                            <input type="hidden" name="action_type" value="update_app_status">
                                            <input type="hidden" name="app_id" value="<?php echo $app['app_id']; ?>">
                                            <select name="status" class="app-action-select" required>
                                                <option value="" disabled selected>Update</option>
                                                <option value="reviewed">Reviewed</option>
                                                <option value="accepted">Accept</option>
                                                <option value="rejected">Reject</option>
                                            </select>
                                            <button type="submit" class="app-action-btn">Save</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Post new job/internship -->
                <section class="workspace-card">
                    <h3 class="card-subtitle"><i class="fas fa-circle-plus"></i> Post Job or Internship</h3>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="action_type" value="post_job">
                        
                        <div class="form-group">
                            <label for="job_title">Position Title</label>
                            <input type="text" id="job_title" name="job_title" placeholder="e.g., Associate Backend Developer (PHP)" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="job_type">Employment Type</label>
                            <select id="job_type" name="job_type" required>
                                <option value="Job">Full-time Job</option>
                                <option value="Internship">Internship (Paid)</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="job_desc">Job Description & Responsibilities</label>
                            <textarea id="job_desc" name="job_desc" rows="4" placeholder="Briefly describe project stack, features under management, dev methodologies..." required></textarea>
                        </div>

                        <div class="form-group">
                            <label for="job_req">Candidate Requirements</label>
                            <textarea id="job_req" name="job_req" rows="4" placeholder="e.g., MySQL triggers, prepared statements, Vanilla CSS Grid, OOP PHP..." required></textarea>
                        </div>

                        <button type="submit" class="submit-btn">Publish Recruitment Listing</button>
                    </form>
                </section>
                
            </div>

            <!-- Right Workspace Sidebar: Company Profile Setup -->
            <aside class="company-sidebar">
                
                <?php if ($profileUpdated): ?>
                    <div class="alert-box" style="background-color:rgba(59,130,246,0.1); border:1px solid #3b82f6; color:#3b82f6;">
                        <?php echo $profileUpdated; ?>
                    </div>
                <?php endif; ?>

                <div class="workspace-card">
                    <h3 class="card-subtitle"><i class="fas fa-building"></i> Company Profile Setup</h3>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="action_type" value="update_profile">
                        
                        <div class="form-group">
                            <label for="company_name">Company Corporate Name</label>
                            <input type="text" id="company_name" name="company_name" value="<?php echo htmlspecialchars($company['company_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="industry">Industry Area</label>
                            <input type="text" id="industry" name="industry" value="<?php echo htmlspecialchars($company['industry'] ?: 'Technology', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>

                        <div class="form-group">
                            <label for="website">Company Website URL</label>
                            <input type="url" id="website" name="website" placeholder="https://example.com" value="<?php echo htmlspecialchars($company['website'] ?: '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>

                        <div class="form-group">
                            <label for="contact_email">HR Contact Email</label>
                            <input type="email" id="contact_email" name="contact_email" value="<?php echo htmlspecialchars($company['contact_email'], ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="description">Company Brief Overview</label>
                            <textarea id="description" name="description" rows="5" placeholder="Describe core products, engineering size, stack..."><?php echo htmlspecialchars($company['description'] ?: '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>

                        <button type="submit" class="submit-btn" style="width:100%;">Save Changes</button>
                    </form>
                </div>
            </aside>

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
