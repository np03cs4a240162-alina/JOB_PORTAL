<?php
/**
 * Mock Sparrow SMS Dispatcher
 */
class SmsNotifier {
    public static function sendStatusAlert($phone, $seekerName, $jobTitle, $companyName, $status) {
        // Sanitize phone or default
        $targetPhone = !empty($phone) ? $phone : '+977 9801234567';
        
        // Custom message based on status
        $msg = "";
        if ($status === 'accepted') {
            $msg = "Hi {$seekerName}, exciting news! You have been SHORTLISTED for the '{$jobTitle}' position at {$companyName}. Please log in to your SmartJob portal to coordinate your interviewing schedule.";
        } elseif ($status === 'rejected') {
            $msg = "Hi {$seekerName}, thank you for applying for the '{$jobTitle}' position at {$companyName}. Your profile has been archived for this opportunity. We wish you success in your search!";
        } else {
            $msg = "Hi {$seekerName}, your application status for the '{$jobTitle}' position at {$companyName} has been updated to {$status}. Check details on SmartJob.";
        }

        // Log SMS event locally to mock Nepalese telecom dispatch
        $logFile = __DIR__ . '/../sparrow_sms_debug.txt';
        $timestamp = date('Y-m-d H:i:s');
        $logContent = "[{$timestamp}] SMS SENT TO: {$targetPhone} | SMS API Provider: SparrowSMS Nepal\nPayload Message: \"{$msg}\"\n" . str_repeat('-', 80) . "\n";
        
        @file_put_contents($logFile, $logContent, FILE_APPEND);
        
        return [
            'success' => true,
            'sms_provider' => 'SparrowSMS Nepal',
            'recipient' => $targetPhone,
            'message' => $msg
        ];
    }
}
