<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit();
}
$courses = [
    ['code' => 'BNG101', 'name' => 'Bangla', 'fee' => 300, 'credits' => 3],
    ['code' => 'ENG101', 'name' => 'English', 'fee' => 300, 'credits' => 3],
    ['code' => 'MAT101', 'name' => 'Math', 'fee' => 300, 'credits' => 3],
    ['code' => 'SCI101', 'name' => 'Science', 'fee' => 300, 'credits' => 3],
    ['code' => 'ICT101', 'name' => 'ICT', 'fee' => 300, 'credits' => 3],
];
$conn->begin_transaction();
try {
    $courseIds = [];
    foreach ($courses as $c) {
        $stmt = $conn->prepare("SELECT id FROM courses WHERE course_code = ?");
        $stmt->bind_param("s", $c['code']);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $courseIds[$c['code']] = (int)$row['id'];
        } else {
            $ins = $conn->prepare("INSERT INTO courses (course_code, course_name, description, credits, fee) VALUES (?, ?, ?, ?, ?)");
            $desc = $c['name'] . ' course';
            $ins->bind_param("sssid", $c['code'], $c['name'], $desc, $c['credits'], $c['fee']);
            $ins->execute();
            $courseIds[$c['code']] = $conn->insert_id;
        }
    }
    $teacherMap = [
        ['username' => 'teacher_bangla', 'first' => 'Bangla', 'last' => 'Teacher', 'email' => 'teacher_bangla@example.com', 'code' => 'BNG101'],
        ['username' => 'teacher_english', 'first' => 'English', 'last' => 'Teacher', 'email' => 'teacher_english@example.com', 'code' => 'ENG101'],
        ['username' => 'teacher_math', 'first' => 'Math', 'last' => 'Teacher', 'email' => 'teacher_math@example.com', 'code' => 'MAT101'],
        ['username' => 'teacher_science', 'first' => 'Science', 'last' => 'Teacher', 'email' => 'teacher_science@example.com', 'code' => 'SCI101'],
        ['username' => 'teacher_ict', 'first' => 'ICT', 'last' => 'Teacher', 'email' => 'teacher_ict@example.com', 'code' => 'ICT101'],
    ];
    foreach ($teacherMap as $t) {
        $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check->bind_param("s", $t['username']);
        $check->execute();
        $r = $check->get_result();
        if ($r->num_rows == 0) {
            $passwordHash = password_hash("password", PASSWORD_BCRYPT);
            $subjectId = $courseIds[$t['code']];
            $u = $conn->prepare("INSERT INTO users (username, password, role, first_name, last_name, email, phone, subject_id, assigned_classes, is_active) VALUES (?, ?, 'teacher', ?, ?, ?, ?, ?, ?, 1)");
            $phone = '0171' . str_pad((string)rand(1000000, 9999999), 7, '0', STR_PAD_LEFT);
            $classes = 'Class 9A, Class 10B';
            $u->bind_param("ssssssis", $t['username'], $passwordHash, $t['first'], $t['last'], $t['email'], $phone, $subjectId, $classes);
            $u->execute();
        }
    }
    $firstNames = ["Akash","Shamim","Rahim","Karim","Hasan","Hossain","Abdullah","Alamin","Shakib","Rafi","Rifat","Imran","Jannat","Nusrat","Sumaiya","Sultana","Ayesha","Tamanna","Tania","Rumana","Mizan","Nayeem","Rakib","Sabbir","Sadia","Shila","Shampa","Mahmud","Shahriar","Nazia","Fahim","Farhan","Arif","Amin","Rubel","Parvez","Sami","Sadia","Maliha","Rashed","Sadia","Mehedi","Sadia","Touhid"];
    $lastNames = ["Ahmed","Rahman","Hossain","Islam","Chowdhury","Khan","Siddique","Hasan","Ali","Akter","Begum","Sarker","Mia","Uddin","Biswas","Saha","Roy","Talukdar","Nazmul","Anwar","Kabir","Haque","Ferdous","Mahmud","Rahim","Faruque","Ashraf","Jahan"];
    $studentsCreated = 0;
    $studentIds = [];
    for ($i = 1; $i <= 200; $i++) {
        $sid = 'STU' . str_pad((string)$i, 4, '0', STR_PAD_LEFT);
        $first = $firstNames[array_rand($firstNames)];
        $last = $lastNames[array_rand($lastNames)];
        $email = strtolower($first . '.' . $last . '.' . $i . '@example.com');
        $phone = '01' . rand(3,9) . str_pad((string)rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
        $exists = $conn->prepare("SELECT id FROM students WHERE student_id = ?");
        $exists->bind_param("s", $sid);
        $exists->execute();
        $exr = $exists->get_result();
        if ($exr->num_rows == 0) {
            $ins = $conn->prepare("INSERT INTO students (student_id, first_name, last_name, email, phone, status) VALUES (?, ?, ?, ?, ?, 'active')");
            $ins->bind_param("sssss", $sid, $first, $last, $email, $phone);
            $ins->execute();
            $studentIds[] = $conn->insert_id;
            $studentsCreated++;
        } else {
            $row = $exr->fetch_assoc();
            $studentIds[] = (int)$row['id'];
        }
    }
    $enroll = $conn->prepare("INSERT IGNORE INTO student_courses (student_id, course_id, enrollment_date, status) VALUES (?, ?, CURDATE(), 'enrolled')");
    $att = $conn->prepare("INSERT IGNORE INTO attendance (student_id, course_id, attendance_date, status) VALUES (?, ?, CURDATE(), 'present')");
    $amount = 300.00;
    foreach ($studentIds as $sidNum) {
        foreach ($courseIds as $code => $cidNum) {
            $enroll->bind_param("ii", $sidNum, $cidNum);
            $enroll->execute();
            $att->bind_param("ii", $sidNum, $cidNum);
            $att->execute();
            $checkPay = $conn->prepare("SELECT id FROM payments WHERE student_id = ? AND course_id = ? AND payment_date = CURDATE()");
            $checkPay->bind_param("ii", $sidNum, $cidNum);
            $checkPay->execute();
            $pr = $checkPay->get_result();
            if ($pr->num_rows == 0) {
                $pay = $conn->prepare("INSERT INTO payments (student_id, course_id, amount, payment_date, payment_method, status) VALUES (?, ?, ?, CURDATE(), 'cash', 'paid')");
                $pay->bind_param("iid", $sidNum, $cidNum, $amount);
                $pay->execute();
            }
        }
    }
    $conn->commit();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Seed completed',
        'courses' => count($courseIds),
        'teachers' => 5,
        'students_created' => $studentsCreated,
        'enrollments' => count($studentIds) * count($courseIds),
        'attendance' => count($studentIds) * count($courseIds),
        'payments' => count($studentIds) * count($courseIds)
    ]);
} catch (Exception $e) {
    $conn->rollback();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Seed failed']);
}
