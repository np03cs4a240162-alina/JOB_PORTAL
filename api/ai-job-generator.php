<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once '../config/cors.php';
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/gemini.php';
require_once 'rbac.php';

$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();
$user   = requireLogin();

if ($user['role'] !== 'employer') {
    jsonResponse(['success' => false, 'error' => 'Only verified employers can generate job listings.'], 403);
}

if ($method === 'POST') {
    requireCsrf();
    $data = getBody();
    
    $title      = trim($data['title'] ?? '');
    $company    = trim($data['company'] ?? '');
    $industry   = trim($data['industry'] ?? '');
    $type       = trim($data['type'] ?? 'full-time');
    $workplace  = trim($data['workplace'] ?? 'Remote');
    $salary     = trim($data['salary'] ?? 'Negotiable');
    $experience = trim($data['experience_level'] ?? 'mid');
    
    if (empty($title) || empty($company)) {
        jsonResponse(['success' => false, 'error' => 'Job title and company are required.'], 400);
    }
    
    try {
        $gemini = getGemini();
        
        if (!$gemini) {
            // Fallback to template if Gemini not available
            $generatedListing = generateJobDescriptionTemplate($title, $company, $industry, $type, $workplace, $salary, $experience);
        } else {
            // Use Gemini AI to generate description
            $generatedListing = $gemini->generateJobDescription($title, $company, $industry, $type, $experience);
        }
        
        logActivity($user['id'], $user['name'], $user['role'], 'Generated AI Job Description', "Title: $title | Company: $company");
        
        jsonResponse([
            'success' => true,
            'message' => 'Job description generated successfully using AI.',
            'data' => $generatedListing
        ]);
        
    } catch (Exception $e) {
        error_log("Gemini Error: " . $e->getMessage());
        
        // Fallback to template
        $generatedListing = generateJobDescriptionTemplate($title, $company, $industry, $type, $workplace, $salary, $experience);
        
        jsonResponse([
            'success' => true,
            'message' => 'Job description generated using template (AI service temporary unavailable).',
            'data' => $generatedListing
        ]);
    }
}

jsonResponse(['success' => false, 'error' => 'Invalid Request.'], 405);

/**
 * Fallback template generator
 */
function generateJobDescriptionTemplate($title, $company, $industry, $type, $workplace, $salary, $experience) {
    $aboutUs = "**About {$company}**\nWe are a fast-growing, innovative organization operating in the {$industry} sector. Our mission is to build world-class products while maintaining a highly collaborative, transparent, and flexible engineering culture. We are currently expanding our {$workplace} team and looking for a passionate {$title} to join us on this journey.\n\n";
    
    $theRole = "**The Role**\nAs a {$title} ({$experience}-level), you will be a core contributor to our primary technology stack. You will take ownership of complex features, collaborate closely with cross-functional product teams, and help architect scalable solutions that directly impact our user base.\n\n";
    
    $responsibilities = "**Core Responsibilities**\n"
        . "• Design, develop, and maintain high-performance, scalable code.\n"
        . "• Collaborate with product managers, designers, and other engineers to deliver new features.\n"
        . "• Identify bottlenecks and bugs, and devise elegant solutions to mitigate these issues.\n"
        . "• Participate in code reviews and advocate for engineering best practices and code quality.\n"
        . "• Help mentor junior engineers and contribute to team knowledge sharing.\n\n";
        
    $requirements = "**Key Requirements**\n"
        . "• Proven {$experience}-level experience in a similar role within the {$industry} industry.\n"
        . "• Strong proficiency in modern frameworks, programming languages, and scalable architectures relevant to the role.\n"
        . "• Deep understanding of RESTful APIs, database design, and cloud infrastructure.\n"
        . "• Familiarity with version control (Git), CI/CD pipelines, and Agile methodologies.\n"
        . "• Excellent problem-solving skills and a strong sense of ownership.\n\n";
        
    $benefits = "**Why Join Us?**\n"
        . "• Competitive compensation structure ({$salary}).\n"
        . "• Flexible {$type} contract with a {$workplace} working environment.\n"
        . "• Comprehensive health, wellness, and learning stipends.\n"
        . "• A flat hierarchy where your ideas are heard and implemented quickly.\n"
        . "• Regular team retreats and tech-focused offsites.";
        
    return $aboutUs . $theRole . $responsibilities . $requirements . $benefits;
}

