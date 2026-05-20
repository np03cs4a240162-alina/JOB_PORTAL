<?php
require_once '../config/db.php';
require_once '../config/session.php';

$seekerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$db = getDB();

if (!$seekerId) {
    echo "<h1>Candidate profile not found.</h1>";
    exit;
}

// Fetch candidate basic information
$userStmt = $db->prepare('SELECT id, name, email FROM users WHERE id = ? AND role = "seeker"');
$userStmt->execute([$seekerId]);
$seekerUser = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$seekerUser) {
    echo "<h1>Candidate profile not found.</h1>";
    exit;
}

// Fetch candidate profile details
$profStmt = $db->prepare('SELECT phone, skills, experience, bio, photo, parsed_education, parsed_experience, resume_file_path FROM seeker_profiles WHERE user_id = ?');
$profStmt->execute([$seekerId]);
$profile = $profStmt->fetch(PDO::FETCH_ASSOC);
$p = $profile ?: [];

// Get avatar photo url
$photoUrl = '../assets/images/default-avatar.png'; // default fallback
if (!empty($p['photo'])) {
    if (strpos($p['photo'], 'uploads/') === 0) {
        $photoUrl = '../' . $p['photo'];
    } else {
        $photoUrl = '../uploads/portraits/' . $p['photo'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Candidate Career Portfolio for <?php echo htmlspecialchars($seekerUser['name']); ?>">
    <title><?php echo htmlspecialchars($seekerUser['name']); ?> | Career Portfolio</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <style>
        body {
            background-color: var(--bg-deep);
            color: var(--text-main);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 80px 20px;
        }
        .portfolio-card {
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            width: 100%;
            max-width: 800px;
            padding: 48px;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
        }
        .portfolio-card::after {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 6px;
            background: linear-gradient(to right, var(--primary), #ef4444);
        }
        .avatar-circle {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid var(--border);
            margin: 0 auto 24px;
            box-shadow: var(--shadow-sm);
            background: var(--bg-deep);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .avatar-circle img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .section-title {
            font-size: 16px;
            font-weight: 900;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: var(--primary);
            margin-bottom: 16px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .badge {
            display: inline-block;
            background: rgba(244, 124, 72, 0.08);
            color: var(--primary);
            border: 1px solid rgba(244, 124, 72, 0.15);
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 750;
            margin-right: 8px;
            margin-bottom: 8px;
        }
        .back-nav {
            position: absolute;
            top: 24px;
            left: 24px;
        }
    </style>
</head>
<body>

<div class="portfolio-card animate-in">
    <div class="back-nav">
        <a href="../index.html" class="btn btn-glass" style="font-size: 12px; padding: 8px 12px; border-radius: 30px;">
            <i class="fas fa-home" style="margin-right: 6px;"></i> SmartJob
        </a>
    </div>

    <!-- Header / Avatar -->
    <div style="text-align: center; margin-bottom: 40px;">
        <div class="avatar-circle">
            <img src="<?php echo htmlspecialchars($photoUrl); ?>" alt="Portrait" onerror="this.onerror=null; this.src='../assets/images/default-avatar.png';">
        </div>
        <h1 style="font-size: 32px; font-weight: 900; letter-spacing: -1.5px; color: var(--text-main); margin-bottom: 8px;">
            <?php echo htmlspecialchars($seekerUser['name']); ?>
        </h1>
        <p style="color: var(--text-muted); font-size: 15px; font-weight: 600; margin-bottom: 16px;">
            <i class="fas fa-briefcase" style="margin-right: 6px; color: var(--primary);"></i> 
            <?php echo !empty($p['experience']) ? htmlspecialchars($p['experience']) . ' of Experience' : 'Technology Candidate'; ?>
        </p>
        
        <div style="display: flex; justify-content: center; gap: 20px; color: var(--text-muted); font-size: 13.5px; font-weight: 600;">
            <span><i class="far fa-envelope" style="margin-right: 6px;"></i> <?php echo htmlspecialchars($seekerUser['email']); ?></span>
            <?php if(!empty($p['phone'])): ?>
                <span><i class="fas fa-phone-flip" style="margin-right: 6px;"></i> <?php echo htmlspecialchars($p['phone']); ?></span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bio Section -->
    <?php if(!empty($p['bio'])): ?>
    <div style="margin-bottom: 36px;">
        <h3 class="section-title"><i class="far fa-user"></i> Professional Bio</h3>
        <p style="font-size: 14.5px; line-height: 1.7; color: var(--text-muted); font-weight: 500;">
            <?php echo nl2br(htmlspecialchars($p['bio'])); ?>
        </p>
    </div>
    <?php endif; ?>

    <!-- Education & Work Section -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px; margin-bottom: 36px;">
        <div>
            <h3 class="section-title"><i class="fas fa-graduation-cap"></i> Academic Credentials</h3>
            <p style="font-size: 14px; font-weight: 700; color: var(--text-main);">
                <?php echo !empty($p['parsed_education']) ? htmlspecialchars($p['parsed_education']) : 'Bachelor of Computer Science / Engineering (Self-Taught / IT Background)'; ?>
            </p>
        </div>
        <div>
            <h3 class="section-title"><i class="fas fa-history"></i> Employment History</h3>
            <p style="font-size: 14px; font-weight: 700; color: var(--text-main);">
                <?php echo !empty($p['parsed_experience']) ? htmlspecialchars($p['parsed_experience']) : 'Software Engineer / Professional Tech Contributor'; ?>
            </p>
        </div>
    </div>

    <!-- Skills Section -->
    <?php if(!empty($p['skills'])): ?>
    <div style="margin-bottom: 40px;">
        <h3 class="section-title"><i class="fas fa-tags"></i> Core Technical Stack</h3>
        <div style="margin-top: 12px;">
            <?php 
            $skillsArr = explode(',', $p['skills']);
            foreach ($skillsArr as $skill): 
                if (trim($skill) !== ''):
            ?>
                <span class="badge"><?php echo htmlspecialchars(trim($skill)); ?></span>
            <?php 
                endif;
            endforeach; 
            ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Resume Downloader -->
    <?php if(!empty($p['resume_file_path'])): ?>
    <div style="text-align: center; border-top: 1px solid var(--border); padding-top: 32px;">
        <a href="../<?php echo htmlspecialchars($p['resume_file_path']); ?>" target="_blank" class="btn btn-primary" style="padding: 14px 32px; border-radius: 30px; font-size: 14px;">
            <i class="fas fa-file-pdf" style="margin-right: 8px;"></i> Access Full Candidate CV PDF
        </a>
    </div>
    <?php endif; ?>

</div>

<script>
    // Apply saved client theme immediately
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
</script>
</body>
</html>
