<?php
/**
 * Gemini AI Integration Helper
 * Uses Google's Gemini API for AI-powered features
 */

require_once __DIR__ . '/env.php';

class GeminiAI {
    private $apiKey;
    private $model;
    private $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';

    public function __construct() {
        $this->apiKey = env('GEMINI_API_KEY');
        $this->model = env('GEMINI_MODEL', 'gemini-1.5-pro');

        if (!$this->apiKey) {
            throw new Exception('GEMINI_API_KEY not configured in .env');
        }
    }

    /**
     * Generate job description using AI
     */
    public function generateJobDescription($jobTitle, $company, $industry, $type, $experience) {
        $prompt = <<<PROMPT
Generate a compelling job description for the following position:

Job Title: $jobTitle
Company: $company
Industry: $industry
Job Type: $type
Experience Level: $experience

Create a professional job description that includes:
1. Brief role overview
2. Key responsibilities (5-7 bullet points)
3. Required qualifications (4-6 bullet points)
4. Nice-to-have skills (3-4 bullet points)
5. Benefits and perks (4-6 bullet points)

Format the response in clear markdown with bold headers.
PROMPT;

        return $this->generateContent($prompt);
    }

    /**
     * Parse resume and extract information
     */
    public function parseResume($resumeText) {
        $prompt = <<<PROMPT
Analyze the following resume/CV and extract structured information:

$resumeText

Please provide a JSON response with the following structure:
{
  "name": "full name",
  "email": "email address",
  "phone": "phone number",
  "skills": ["skill1", "skill2", ...],
  "experience": [
    {
      "title": "job title",
      "company": "company name",
      "duration": "duration",
      "description": "brief description"
    }
  ],
  "education": [
    {
      "degree": "degree name",
      "institution": "institution name",
      "year": "graduation year",
      "field": "field of study"
    }
  ],
  "summary": "brief professional summary"
}

Respond ONLY with valid JSON, no other text.
PROMPT;

        $response = $this->generateContent($prompt);
        
        // Try to parse JSON response
        $decoded = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        return ['raw' => $response];
    }

    /**
     * Match job seeker to job opportunities
     */
    public function matchSeekerToJobs($seekerProfile, $jobs) {
        $prompt = <<<PROMPT
Based on the job seeker profile below, match them with the most suitable job opportunities.

SEEKER PROFILE:
{$seekerProfile}

AVAILABLE JOBS:
{$jobs}

Provide a JSON response with match scores (0-100) for each job, considering:
- Skill match
- Experience level match
- Location preferences
- Career growth potential

Response format:
{
  "matches": [
    {
      "job_id": "id",
      "match_score": 85,
      "reason": "explanation of why this is a good match"
    }
  ]
}

Respond ONLY with valid JSON.
PROMPT;

        $response = $this->generateContent($prompt);
        $decoded = json_decode($response, true);
        
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        return ['raw' => $response];
    }

    /**
     * Generate interview questions
     */
    public function generateInterviewQuestions($jobTitle, $skills) {
        $prompt = <<<PROMPT
Generate 10 relevant interview questions for a $jobTitle position requiring these skills: $skills

Format as a JSON array:
[
  {
    "question": "question text",
    "type": "technical|behavioral|situational",
    "difficulty": "easy|medium|hard"
  }
]

Respond ONLY with valid JSON.
PROMPT;

        $response = $this->generateContent($prompt);
        $decoded = json_decode($response, true);
        
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        return ['raw' => $response];
    }

    /**
     * Core method to call Gemini API
     */
    private function generateContent($prompt) {
        $url = "{$this->baseUrl}/{$this->model}:generateContent?key={$this->apiKey}";

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 2048,
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("Gemini API Error: $error");
        }

        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMsg = $errorData['error']['message'] ?? 'Unknown error';
            throw new Exception("Gemini API ($httpCode): $errorMsg");
        }

        $decoded = json_decode($response, true);

        if (isset($decoded['candidates'][0]['content']['parts'][0]['text'])) {
            return $decoded['candidates'][0]['content']['parts'][0]['text'];
        }

        throw new Exception('Invalid Gemini API response');
    }
}

/**
 * Singleton instance
 */
$gemini = null;

function getGemini() {
    global $gemini;
    if ($gemini === null) {
        try {
            $gemini = new GeminiAI();
        } catch (Exception $e) {
            error_log("Gemini initialization failed: " . $e->getMessage());
            return null;
        }
    }
    return $gemini;
}
