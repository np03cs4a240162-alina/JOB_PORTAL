<?php
$base = 'http://localhost/JOB_PORTAL/api';
$cookieJar = __DIR__ . '/func_cookies.txt';
@unlink($cookieJar);

function req($method,$url,$data=null,$cookieJar=null,$headers=[]){
    $ch=curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    if($cookieJar){curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);curl_setopt($ch, CURLOPT_COOKIEFILE,$cookieJar);}    
    if($data!==null){$json=json_encode($data);curl_setopt($ch,CURLOPT_POSTFIELDS,$json);$headers[]='Content-Type: application/json';}
    if(!empty($headers)) curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
    $r=curl_exec($ch);$info=curl_getinfo($ch);curl_close($ch);
    return ['code'=>$info['http_code'],'raw'=>$r,'json'=>json_decode($r,true)];
}

$report = [];

// 1. Login as seeker
$seekerCreds=['email'=>'qa_seeker@example.com','password'=>'QaSeeker1!'];
$login = req('POST', "$base/auth.php?action=login", $seekerCreds, $cookieJar);
$report['seeker_login']=$login;
echo "Seeker login: {$login['code']}\n";
$csrf = $login['json']['csrf_token'] ?? null;
if (!$csrf) {
    // try me to get token
    $me = req('GET', "$base/auth.php?action=me", null, $cookieJar);
    $csrf = $me['json']['csrf_token'] ?? null;
    $report['seeker_me']=$me;
}
if (!$csrf) { echo "No csrf token for seeker; aborting seeker actions.\n"; file_put_contents(__DIR__.'/qa_functional_report.json', json_encode($report, JSON_PRETTY_PRINT)); exit(0); }

// 2. Get first job id
$jobs = req('GET', "$base/jobs.php", null, $cookieJar);
$report['jobs_list']=$jobs;
$jobId = $jobs['json']['data'][0]['id'] ?? null;
echo "Found job id: $jobId\n";
if ($jobId) {
    // 3. Save job
    $save = req('POST', "$base/saved.php", ['job_id'=>$jobId], $cookieJar, ['X-CSRF-Token: '.$csrf]);
    $report['save_job']=$save;
    echo "Save job: {$save['code']}\n";

    // 4. Apply for job
    $apply = req('POST', "$base/applications.php?action=apply", ['job_id'=>$jobId,'resume_note'=>'QA test apply'], $cookieJar, ['X-CSRF-Token: '.$csrf]);
    $report['apply_job']=$apply;
    echo "Apply job: {$apply['code']}\n";
}

// 5. Login as employer and create a job
@unlink($cookieJar);
$employerCreds=['email'=>'qa_employer@example.com','password'=>'QaEmployer1!'];
$login2 = req('POST', "$base/auth.php?action=login", $employerCreds, $cookieJar);
$report['employer_login']=$login2;
$csrf2 = $login2['json']['csrf_token'] ?? null;
if (!$csrf2) { $me2 = req('GET', "$base/auth.php?action=me", null, $cookieJar); $csrf2 = $me2['json']['csrf_token'] ?? null; $report['employer_me']=$me2; }
if ($csrf2) {
    $newJob = [
        'title' => 'QA Created Job '.time(),
        'company' => 'QA Company',
        'salary' => '$1000',
        'category' => 'IT',
        'location' => 'Remote',
        'description' => 'Created by QA script',
        'type' => 'Full Time',
        'workplace' => 'Remote',
        'industry' => 'Software',
        'deadline' => null,
        'experience_level' => 'entry'
    ];
    $create = req('POST', "$base/jobs.php", $newJob, $cookieJar, ['X-CSRF-Token: '.$csrf2]);
    $report['create_job']=$create; echo "Create job: {$create['code']}\n";
    $createdId = $create['json']['id'] ?? null;
    if ($createdId) {
        // delete the job as cleanup
        $del = req('DELETE', "$base/jobs.php?id=$createdId", null, $cookieJar, ['X-CSRF-Token: '.$csrf2]);
        $report['deleted_job']=$del; echo "Deleted job: {$del['code']}\n";
    }
}

file_put_contents(__DIR__.'/qa_functional_report.json', json_encode($report, JSON_PRETTY_PRINT));
echo "Functional report saved to tests/qa_functional_report.json\n";
