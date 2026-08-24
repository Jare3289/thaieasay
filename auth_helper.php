<?php
// auth_helper.php

// 1. Configure session cookie parameters (30 days) to prevent session drops on mobile
$session_lifetime = 30 * 24 * 60 * 60; // 30 days

// สำคัญ: session_set_cookie_params() ด้านล่างกำหนดอายุของ "คุกกี้" ในเบราว์เซอร์เท่านั้น
// แต่ข้อมูลเซสชันฝั่งเซิร์ฟเวอร์ (ไฟล์ session จริง) ยังถูกลบทิ้งได้เร็วกว่านั้นมาก เพราะค่าเริ่มต้นของ
// PHP (session.gc_maxlifetime) มักตั้งไว้แค่ ~24 นาที ถ้าไม่ปรับตรงนี้ด้วย พอผู้ใช้พิมพ์เรียงความ/ประเมินผล
// นานเกิน 24 นาทีแล้วกด "บันทึก" ระบบจะมองว่าไม่ได้ล็อกอิน ทำให้ข้อมูลที่พิมพ์ไว้บันทึกไม่ติดและเด้งออกจากระบบ
// จึงต้องปรับให้ตรงกับอายุคุกกี้ (30 วัน) เพื่อไม่ให้เซสชันหลุดกลางคันขณะทำงาน
ini_set('session.gc_maxlifetime', (string)$session_lifetime);

if (PHP_VERSION_ID >= 70300) {
    session_set_cookie_params([
        'lifetime' => $session_lifetime,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
} else {
    session_set_cookie_params(
        $session_lifetime,
        '/',
        '',
        isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        true
    );
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db_config.php';

// 2. Server-side auto-login cookie check
if (!isset($_SESSION['user']) && isset($_COOKIE['remember_user'])) {
    $cookie_data = json_decode($_COOKIE['remember_user'], true);
    if ($cookie_data && isset($cookie_data['role']) && isset($cookie_data['loginId'])) {
        $role = $cookie_data['role'];
        $loginId = trim($cookie_data['loginId']);
        
        if ($role === 'teacher' && $loginId === 'admin') {
            $_SESSION['user'] = [
                'id' => 'admin',
                'name' => 'ครูผู้สอน',
                'role' => 'teacher'
            ];
        } else if ($role === 'expert' && ($loginId === 'admin1' || $loginId === 'admin2')) {
            $expertNum = ($loginId === 'admin1') ? '1' : '2';
            $_SESSION['user'] = [
                'id' => $loginId,
                'name' => 'ผู้เชี่ยวชาญ ' . $expertNum,
                'role' => 'expert'
            ];
        } else if ($role === 'student') {
            try {
                $stmt = $pdo->prepare('SELECT * FROM students WHERE student_id = ?');
                $stmt->execute([$loginId]);
                $student = $stmt->fetch();
                if ($student) {
                    $_SESSION['user'] = [
                        'id' => $student['student_id'],
                        'name' => formatNamePrefix($student['student_name']),
                        'role' => 'student'
                    ];
                }
            } catch (Exception $e) {
                // Ignore silent db error
            }
        }
    }
}

// 3. Authorization helper
function require_login($required_role = null) {
    if (!isset($_SESSION['user'])) {
        header('Location: login.php');
        exit;
    }
    if ($required_role && $_SESSION['user']['role'] !== $required_role) {
        header('Location: index.php');
        exit;
    }
}

// 4. Format name prefixes (remove space after นาย, นาง, นางสาว, ด.ช., ด.ญ., ครู, อาจารย์, ผู้เชี่ยวชาญ)
function formatNamePrefix($name) {
    if (empty($name)) return '';
    // ตัดคำนำหน้าชื่อ (นาย/นางสาว/นาง ฯลฯ) ออก ให้เหลือแต่ชื่อ-นามสกุล
    return trim(preg_replace('/^(นาย|นางสาว|นาง|ด\.ช\.|ด\.ญ\.|เด็กชาย|เด็กหญิง|ครู|อาจารย์|ผู้เชี่ยวชาญ)\s*/u', '', $name));
}
