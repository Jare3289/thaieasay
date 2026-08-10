<?php
/**
 * google_auth.php — จัดการการยืนยันตัวตน OAuth 2.0 กับ Google
 *   ?action=status     → คืนสถานะ JSON (configured / connected / redirect_uri)
 *   ?action=connect    → พาไปหน้าขออนุญาตของ Google (แล้วกลับมาที่ callback)
 *   ?action=callback   → รับ code แลกเป็น token เก็บใน session แล้วกลับหน้าเดิม
 *   ?action=disconnect → ล้าง token ออกจาก session
 *
 * เฉพาะคุณครูเท่านั้น (เป็นเจ้าของบัญชี Google ที่จะเก็บไฟล์รายงาน)
 */
require_once 'auth_helper.php';
require_once 'google_config.php';
require_once 'google_lib.php';

// ใช้ชื่อ is_teacher() เดิมภายในไฟล์นี้ (แมปไปยังฟังก์ชันกลาง)
if (!function_exists('is_teacher')) {
    function is_teacher() { return google_is_teacher(); }
}

// ให้ตัวจัดการคำสั่ง (dispatcher) ทำงานเฉพาะเมื่อเรียก google_auth.php โดยตรงเท่านั้น
// ไม่ให้ทำงานตอนถูก include จากไฟล์อื่น (เช่น google_upload_doc.php ที่ต้องการเพียงฟังก์ชัน)
$__is_direct = isset($_SERVER['SCRIPT_FILENAME'])
    && @realpath($_SERVER['SCRIPT_FILENAME']) === @realpath(__FILE__);
if (!$__is_direct) {
    return; // ถูก include → หยุดแค่ประกาศฟังก์ชัน ไม่รัน dispatcher
}

$action = $_GET['action'] ?? 'status';

// ---------- status ----------
if ($action === 'status') {
    header('Content-Type: application/json; charset=utf-8');
    if (!is_teacher()) { echo json_encode(['configured' => google_is_configured(), 'connected' => false, 'error' => 'ต้องเป็นคุณครู']); exit; }
    $connected = !empty($_SESSION['google_tokens']['refresh_token']) || !empty($_SESSION['google_tokens']['access_token']);
    echo json_encode([
        'configured'   => google_is_configured(),
        'connected'    => $connected,
        'redirect_uri' => google_redirect_uri(),
    ]);
    exit;
}

// ---------- disconnect ----------
if ($action === 'disconnect') {
    unset($_SESSION['google_tokens']);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => true]);
    exit;
}

// ---------- connect ----------
if ($action === 'connect') {
    if (!is_teacher()) { header('Location: login.php'); exit; }
    if (!google_is_configured()) { die('ยังไม่ได้ตั้งค่า Google API — โปรดกรอก Client ID/Secret ใน google_config.php'); }
    $_SESSION['google_oauth_state']  = bin2hex(random_bytes(16));
    $_SESSION['google_oauth_return'] = isset($_GET['return']) ? $_GET['return'] : 'research_analysis.php';
    $params = http_build_query([
        'client_id'     => GOOGLE_CLIENT_ID,
        'redirect_uri'  => google_redirect_uri(),
        'response_type' => 'code',
        'scope'         => GOOGLE_SCOPES,
        'access_type'   => 'offline',
        'prompt'        => 'consent',      // บังคับให้ได้ refresh_token เสมอ
        'state'         => $_SESSION['google_oauth_state'],
        'include_granted_scopes' => 'true',
    ]);
    header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . $params);
    exit;
}

// ---------- callback ----------
if ($action === 'callback') {
    if (!is_teacher()) { header('Location: login.php'); exit; }
    if (isset($_GET['error'])) { die('การเชื่อมต่อถูกยกเลิก: ' . htmlspecialchars($_GET['error'])); }
    $state = $_GET['state'] ?? '';
    if (!$state || !isset($_SESSION['google_oauth_state']) || !hash_equals($_SESSION['google_oauth_state'], $state)) {
        die('สถานะความปลอดภัย (state) ไม่ถูกต้อง โปรดลองเชื่อมต่อใหม่');
    }
    $code = $_GET['code'] ?? '';
    if (!$code) { die('ไม่พบรหัสอนุญาต (code)'); }
    $r = google_token_request([
        'client_id'     => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'code'          => $code,
        'grant_type'    => 'authorization_code',
        'redirect_uri'  => google_redirect_uri(),
    ]);
    if (!$r['ok']) { die('แลกโทเคนไม่สำเร็จ: ' . htmlspecialchars($r['error'])); }
    google_store_tokens($r['data']);
    unset($_SESSION['google_oauth_state']);
    $return = $_SESSION['google_oauth_return'] ?? 'research_analysis.php';
    unset($_SESSION['google_oauth_return']);
    // กันการ redirect ออกนอกโดเมน — อนุญาตเฉพาะ path ภายใน
    if (!preg_match('#^/[^/\\\\]#', $return) && strpos($return, 'research_analysis.php') !== 0) {
        $return = 'research_analysis.php#section-export';
    }
    header('Location: ' . $return);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['error' => 'unknown action']);
