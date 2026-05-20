<?php
/**
 * AI Chatbot System
 * Provides intelligent career guidance and job search assistance
 */

require_once __DIR__ . '/gemini.php';

class CareerAdvisorBot {
    private $gemini;
    private $db;
    private $userId;
    
    public function __construct($userId = null) {
        $this->gemini = getGemini();
        if (!$this->gemini) {
            throw new Exception('Gemini AI service unavailable');
        }
        $this->userId = $userId;
    }

    /**
     * Main chat handler - responds to user messages
     */
    public function chat($message, $context = []) {
        $systemPrompt = $this->buildSystemPrompt($context);
        
        $prompt = <<<PROMPT
$systemPrompt

User message: $message

Provide a helpful, concise response (2-3 sentences max) about career guidance, job search, or skill development.
PROMPT;

        try {
            $response = $this->gemini->generateContent($prompt);
            return $response;
        } catch (Exception $e) {
            error_log("Chatbot error: " . $e->getMessage());
            return $this->getFallbackResponse($message);
        }
    }

    /**
     * Career guidance based on user profile
     */
    public function getCareerGuidance($skills, $experience, $goal = null) {
        $prompt = <<<PROMPT
A job seeker has the following profile:
- Skills: $skills
- Experience: $experience
- Career Goal: $goal

Provide 3 specific, actionable career advice tips to help them advance. Format as numbered list.
Focus on: skill gaps, certifications, industries where they'd excel, or networking strategies.
PROMPT;

        try {
            return $this->gemini->generateContent($prompt);
        } catch (Exception $e) {
            return "Career guidance temporarily unavailable. Please try again later.";
        }
    }

    /**
     * Suggest jobs based on skills
     */
    public function suggestJobs($skills, $experience, $location = 'Nepal') {
        $prompt = <<<PROMPT
Based on these qualifications, suggest 5 job titles/roles suitable in $location:
- Skills: $skills
- Experience Level: $experience

Format as a numbered list with brief explanation for each.
PROMPT;

        try {
            return $this->gemini->generateContent($prompt);
        } catch (Exception $e) {
            return "Job suggestions temporarily unavailable.";
        }
    }

    /**
     * Interview preparation help
     */
    public function prepareForInterview($jobTitle, $company, $skills) {
        $prompt = <<<PROMPT
Help prepare for an interview at $company for a $jobTitle position.
Candidate skills: $skills

Provide:
1. 3 typical interview questions for this role
2. 2 questions the candidate should ask the interviewer
3. 1 tip for making a great impression

Keep response concise and actionable.
PROMPT;

        try {
            return $this->gemini->generateContent($prompt);
        } catch (Exception $e) {
            return "Interview prep temporarily unavailable.";
        }
    }

    /**
     * Resume improvement suggestions
     */
    public function suggestResimeImprovements($skills, $experience, $summary) {
        $prompt = <<<PROMPT
Review this candidate profile and suggest improvements:
- Summary: $summary
- Skills: $skills
- Experience: $experience

Provide 3 specific suggestions to make their resume/profile more attractive to employers.
PROMPT;

        try {
            return $this->gemini->generateContent($prompt);
        } catch (Exception $e) {
            return "Resume suggestions temporarily unavailable.";
        }
    }

    /**
     * Build system prompt with context
     */
    private function buildSystemPrompt($context) {
        $base = "You are CareerBot, a friendly and professional career advisor for a Nepal-based job portal. You help job seekers:
- Find suitable career opportunities
- Develop their skills and competencies
- Prepare for job interviews
- Navigate career transitions
- Build professional networks

Be encouraging, practical, and specific in your advice. Keep responses concise and actionable.";

        if (!empty($context['user_role'])) {
            $base .= "\n\nThe user is a job {$context['user_role']}.";
        }

        if (!empty($context['looking_for'])) {
            $base .= "\n\nThey are looking for: {$context['looking_for']}";
        }

        return $base;
    }

    /**
     * Fallback response when AI unavailable
     */
    private function getFallbackResponse($message) {
        $keywords = strtolower($message);
        
        if (strpos($keywords, 'job') !== false || strpos($keywords, 'application') !== false) {
            return "I can help you find the right job! Check our job listings or upload your resume for AI matching. What specific role are you interested in?";
        }
        
        if (strpos($keywords, 'interview') !== false) {
            return "Great question! I recommend: 1) Research the company thoroughly 2) Practice common questions 3) Prepare examples of your achievements. What role are you interviewing for?";
        }
        
        if (strpos($keywords, 'skill') !== false) {
            return "Building skills is crucial! Consider our training hub for industry-relevant courses. Which skills are you looking to develop?";
        }
        
        if (strpos($keywords, 'resume') !== false) {
            return "Your resume is important! I can help parse it and suggest improvements. Try uploading your resume to get AI-powered insights.";
        }
        
        return "I'm here to help with career guidance! Ask me about job search, skills, interviews, or career development.";
    }
}

/**
 * Store chat message in database
 */
function storeChatMessage($userId, $message, $response, $type = 'career_advisor') {
    global $db;
    if (!isset($GLOBALS['db'])) {
        require_once __DIR__ . '/db.php';
        $GLOBALS['db'] = getDB();
    }
    
    try {
        $stmt = $GLOBALS['db']->prepare(
            'INSERT INTO chat_messages (user_id, message, response, bot_type) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $message, $response, $type]);
        return $GLOBALS['db']->lastInsertId();
    } catch (Exception $e) {
        error_log("Failed to store chat message: " . $e->getMessage());
        return null;
    }
}

/**
 * Get chat history for user
 */
function getChatHistory($userId, $limit = 50) {
    global $db;
    if (!isset($GLOBALS['db'])) {
        require_once __DIR__ . '/db.php';
        $GLOBALS['db'] = getDB();
    }
    
    try {
        $stmt = $GLOBALS['db']->prepare(
            'SELECT message, response, created_at FROM chat_messages WHERE user_id = ? ORDER BY created_at DESC LIMIT ?'
        );
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Failed to fetch chat history: " . $e->getMessage());
        return [];
    }
}

/**
 * Get bot status
 */
function getBotStatus() {
    try {
        $gemini = getGemini();
        return [
            'status' => 'online',
            'model' => env('GEMINI_MODEL', 'gemini-1.5-pro'),
            'message' => 'Career Advisor Bot is ready to help!'
        ];
    } catch (Exception $e) {
        return [
            'status' => 'offline',
            'message' => 'Chatbot temporarily unavailable. Try again later.'
        ];
    }
}
