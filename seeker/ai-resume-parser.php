<?php
require_once '../config/session.php';
$user = requireLogin();
if ($user['role'] !== 'seeker') {
    header('Location: ../index.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="AI Resume Parser and skill gap matching console.">
    <title>AI SmartParser Hub | SmartJob Nepal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <style>
        body {
            background-color: var(--bg-deep);
            color: var(--text-main);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
        }
        .dashboard-layout {
            padding-top: 120px;
            padding-bottom: 60px;
        }
        .dashboard-panel {
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 32px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 32px;
        }
        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 16px;
        }
        .panel-header h3 {
            font-size: 24px;
            font-weight: 900;
            letter-spacing: -1px;
            color: var(--text-main);
        }
        .select-group {
            margin-bottom: 24px;
            text-align: left;
        }
        .select-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 12px;
            font-weight: 800;
            color: var(--text-dim);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .select-group select {
            width: 100%;
            padding: 14px 18px;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            font-size: 15px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            outline: none;
            background: var(--bg-surface);
            color: var(--text-main);
            transition: var(--transition);
        }
        .select-group select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--primary-glow);
        }
        .ai-result-grid {
            display: grid;
            grid-template-columns: 1.2fr 1.8fr;
            gap: 32px;
            margin-top: 32px;
        }
        @media (max-width: 768px) {
            .ai-result-grid {
                grid-template-columns: 1fr;
            }
        }
        .bento-glow-card {
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 32px;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }
        .bento-glow-card::after {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 4px;
            background: linear-gradient(to right, var(--primary), #ef4444);
        }
        .badge-pill {
            display: inline-block;
            background: rgba(244, 124, 72, 0.08);
            color: var(--primary);
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 800;
            margin-right: 8px;
            margin-bottom: 8px;
        }
        .progress-ring-container {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 24px 0;
            position: relative;
        }
        .progress-text {
            position: absolute;
            font-size: 28px;
            font-weight: 900;
            color: var(--text-main);
            letter-spacing: -1px;
        }
        .loader-sequence {
            text-align: center;
            padding: 40px 20px;
        }
        .loader-step {
            font-size: 14px;
            font-weight: 700;
            color: var(--text-muted);
            margin-top: 12px;
            transition: var(--transition);
        }
    </style>
</head>
<body>

<header class="global-nav"></header>

<div class="container dashboard-layout" style="max-width: 1000px;">
    
    <div class="dashboard-panel animate-in">
        <div class="panel-header">
            <h3><i class="fas fa-microchip" style="color: var(--primary); margin-right: 10px;"></i> AI SmartParser Hub</h3>
            <a href="dashboard.html" class="btn btn-glass" style="font-size: 13px; padding: 10px 16px;">
                <i class="fas fa-arrow-left" style="margin-right: 6px;"></i> Dashboard
            </a>
        </div>

        <div id="alert-box"></div>

        <div style="background: rgba(244, 124, 72, 0.04); border: 1px solid var(--border); padding: 24px; border-radius: var(--radius-lg); margin-bottom: 32px;">
            <h4 style="font-size: 16px; font-weight: 850; margin-bottom: 8px; color: var(--text-main);"><i class="fas fa-circle-info" style="color: var(--primary); margin-right: 8px;"></i> Optimize Your Career Score</h4>
            <p style="font-size: 13.5px; color: var(--text-muted); line-weight: 1.6; margin: 0; font-weight: 500;">
                Select your uploaded resume PDF below and run our Gemini AI matching engine. SmartParser analyzes your experience against Nepal's active tech demands, identifies critical skill gaps, and populates your profile instantly.
            </p>
        </div>

        <div class="select-group">
            <label for="select-resume">Select Resume from Vault</label>
            <select id="select-resume">
                <option value="">-- Loading available Resumes --</option>
            </select>
        </div>

        <div style="display: flex; gap: 12px;">
            <button id="parse-btn" onclick="startAIAnalysis()" class="btn btn-primary" style="flex: 1; padding: 14px; font-size: 14px;">
                <i class="fas fa-wand-magic-sparkles" style="margin-right: 8px;"></i> Run SmartAI Extraction
            </button>
            <a href="resume-manager.html" class="btn btn-glass" style="padding: 14px 20px; font-size: 14px;">
                <i class="fas fa-plus" style="margin-right: 8px;"></i> Upload Resume
            </a>
        </div>
    </div>

    <!-- loader screen -->
    <div id="ai-loading" class="dashboard-panel animate-in" style="display: none;">
        <div class="loader-sequence">
            <i class="fas fa-spinner fa-spin" style="font-size: 40px; color: var(--primary);"></i>
            <div class="loader-step" id="loader-step-text">Reading PDF structure...</div>
            <div style="width: 100%; max-width: 300px; height: 6px; background: var(--border); border-radius: 10px; margin: 20px auto 0; overflow: hidden; position: relative;">
                <div id="loader-progress-bar" style="width: 10%; height: 100%; background: var(--primary); transition: width 0.4s ease;"></div>
            </div>
        </div>
    </div>

    <!-- Results Panel -->
    <div id="ai-results-panel" style="display: none;" class="animate-in">
        <div class="ai-result-grid">
            
            <!-- LEFT: Score Bento -->
            <div class="bento-glow-card">
                <h4 style="font-size: 16px; font-weight: 850; text-align: center; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted);">Career Fit Score</h4>
                
                <div class="progress-ring-container">
                    <svg width="160" height="160">
                        <circle cx="80" cy="80" r="70" stroke="var(--border)" stroke-width="12" fill="transparent" />
                        <circle id="progress-circle" cx="80" cy="80" r="70" stroke="var(--primary)" stroke-width="12" fill="transparent"
                                stroke-dasharray="440" stroke-dashoffset="440" stroke-linecap="round" style="transition: stroke-dashoffset 1s ease-out; transform: rotate(-90deg); transform-origin: 50% 50%;" />
                    </svg>
                    <div class="progress-text" id="score-val">0%</div>
                </div>

                <div style="background: var(--bg-deep); border-radius: var(--radius); padding: 18px; border: 1px solid var(--border); text-align: center; margin-top: 24px;">
                    <div style="font-size: 12px; font-weight: 800; color: var(--text-dim); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Extracted Phone</div>
                    <strong style="font-size: 14px;" id="ext-phone">--</strong>
                </div>
            </div>

            <!-- RIGHT: Profile Details & Skill Gaps -->
            <div style="display: flex; flex-direction: column; gap: 32px;">
                
                <div class="bento-glow-card">
                    <h3 style="font-size: 18px; font-weight: 900; margin-bottom: 20px; color: var(--text-main); border-bottom: 1px solid var(--border); padding-bottom: 12px;"><i class="fas fa-shield-halved" style="color: var(--primary); margin-right: 8px;"></i> SmartSkill Gap Analysis</h3>
                    <p style="font-size: 14px; line-height: 1.7; color: var(--text-muted); font-weight: 500; margin-bottom: 0;" id="ext-skill-gap">--</p>
                </div>

                <div class="bento-glow-card">
                    <h3 style="font-size: 18px; font-weight: 900; margin-bottom: 20px; color: var(--text-main); border-bottom: 1px solid var(--border); padding-bottom: 12px;"><i class="fas fa-graduation-cap" style="color: var(--primary); margin-right: 8px;"></i> Parsed Education</h3>
                    <strong style="font-size: 14.5px; color: var(--text-main); display: block;" id="ext-edu">--</strong>
                    <div style="margin-top: 24px;">
                        <h4 style="font-size: 13px; font-weight: 800; color: var(--text-dim); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px;">Parsed Professional Experience</h4>
                        <strong style="font-size: 14.5px; color: var(--text-main); display: block;" id="ext-exp">--</strong>
                    </div>
                </div>

                <div class="bento-glow-card">
                    <h3 style="font-size: 18px; font-weight: 900; margin-bottom: 20px; color: var(--text-main); border-bottom: 1px solid var(--border); padding-bottom: 12px;"><i class="fas fa-tags" style="color: var(--primary); margin-right: 8px;"></i> Extracted Skill Tags</h3>
                    <div id="ext-skills-container"></div>
                </div>

            </div>

        </div>

        <div style="text-align: center; margin-top: 40px;">
            <button onclick="location.href='../jobs/listing.html'" class="btn btn-primary" style="padding: 16px 36px; font-size: 15px; border-radius: 30px; box-shadow: var(--shadow-lg);">
                <i class="fas fa-briefcase" style="margin-right: 8px;"></i> Find AI-Matched Vacancies Now
            </button>
        </div>
    </div>

</div>

<footer class="premium-footer" style="padding: 60px 0; border-top: 1px solid var(--border); margin-top: 80px;">
    <div class="container" style="text-align: center; color: var(--text-dim); font-size: 14px; font-weight: 600;">
        <p>&copy; 2026 SmartJob Nepal. All rights reserved.</p>
    </div>
</footer>

<script src="../assets/js/main.js"></script>
<script>
    async function init() {
        const user = await checkAuth('seeker');
        if (!user) return;

        // Load resumes select options
        const res = await apiGet('/upload.php');
        const select = document.getElementById('select-resume');
        
        if (res.success && res.data && res.data.length > 0) {
            select.innerHTML = res.data.map(r => `
                <option value="${r.id}">${escHtml(r.filename)} (Uploaded ${new Date(r.uploaded_at).toLocaleDateString()})</option>
            `).join('');
        } else {
            select.innerHTML = `<option value="">-- No resumes uploaded in vault yet --</option>`;
            document.getElementById('parse-btn').disabled = true;
        }
    }

    const steps = [
        { text: 'Reading PDF document structure...', progress: 25 },
        { text: 'Tokenizing professional career items...', progress: 50 },
        { text: 'Comparing candidate skills with Nepalese tech pipeline...', progress: 75 },
        { text: 'Finalizing Skill Gap Recommendation logs...', progress: 100 }
    ];

    function runMockSequence() {
        return new Promise(resolve => {
            let step = 0;
            const bar = document.getElementById('loader-progress-bar');
            const txt = document.getElementById('loader-step-text');
            
            const interval = setInterval(() => {
                if (step < steps.length) {
                    bar.style.width = steps[step].progress + '%';
                    txt.textContent = steps[step].text;
                    step++;
                } else {
                    clearInterval(interval);
                    resolve();
                }
            }, 800);
        });
    }

    async function startAIAnalysis() {
        const resumeId = document.getElementById('select-resume').value;
        if (!resumeId) return;

        // Reset display
        document.getElementById('ai-results-panel').style.display = 'none';
        document.getElementById('ai-loading').style.display = 'block';

        // Run premium visual extraction loading steps
        await runMockSequence();

        const res = await apiPost('/ai-parser.php', { resume_id: resumeId });
        document.getElementById('ai-loading').style.display = 'none';

        if (res.success) {
            showAlert('alert-box', 'Gemini AI parsing complete! Your seeker profile is now optimized.', 'success');
            
            const data = res.data;
            document.getElementById('ext-phone').textContent = data.phone;
            document.getElementById('ext-skill-gap').textContent = data.skill_gap_analysis;
            document.getElementById('ext-edu').textContent = data.education;
            document.getElementById('ext-exp').textContent = data.experience;
            
            // Skill badges
            const skillsContainer = document.getElementById('ext-skills-container');
            skillsContainer.innerHTML = data.skills.map(s => `
                <span class="badge-pill">${escHtml(s)}</span>
            `).join('');

            // Circular progress matching ring
            const pct = data.match_percentage;
            document.getElementById('score-val').textContent = pct + '%';
            
            const circle = document.getElementById('progress-circle');
            const radius = circle.r.baseVal.value;
            const circumference = 2 * Math.PI * radius;
            const offset = circumference - (pct / 100) * circumference;
            circle.style.strokeDasharray = circumference;
            circle.style.strokeDashoffset = offset;

            document.getElementById('ai-results-panel').style.display = 'block';
            document.getElementById('ai-results-panel').scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else {
            showAlert('alert-box', res.error || 'Parsing failed.', 'error');
        }
    }

    init();
</script>
</body>
</html>
