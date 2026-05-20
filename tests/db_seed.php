<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
$db = getDB();

function upsertUser($db, $email, $name, $pass, $role) {
    $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    if ($row) {
        echo "User $email already exists (id={$row['id']}).\n";
        return $row['id'];
    }
    $db->prepare('INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)')->execute([$name,$email,password_hash($pass,PASSWORD_DEFAULT),$role]);
    $id = $db->lastInsertId();
    echo "Created user $email id=$id\n";
    if ($role === 'seeker') {
        $db->prepare('INSERT INTO seeker_profiles (user_id) VALUES (?)')->execute([$id]);
    } else if ($role === 'employer') {
        $db->prepare('INSERT INTO employer_profiles (user_id) VALUES (?)')->execute([$id]);
    }
    return $id;
}

$seekerEmail = 'qa_seeker@example.com';
$employerEmail = 'qa_employer@example.com';
$seekerPass = 'QaSeeker1!';
$employerPass = 'QaEmployer1!';

upsertUser($db, $seekerEmail, 'QA Seeker', $seekerPass, 'seeker');
upsertUser($db, $employerEmail, 'QA Employer', $employerPass, 'employer');

echo "Seeding complete.\n";
