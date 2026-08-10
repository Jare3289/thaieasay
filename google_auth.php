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

function is_teacher() {
    return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'teacher';
}

/** เรียก Google token endpoint (แลก code หรือ refresh) */
function google_token_request($post) {
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($post),
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT => 30,
    ]);
    $resp = curl_exec($ch);
    $err = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($resp === false) return ['ok' => false, 'error' => 'cURL error: ' . $err];
    $data = json_decode($resp, true);
    if ($code >= 400 || isset($data['error'])) {
        return ['ok' => false, 'error' => $data['error_description'] ?? ($data['error'] ?? 'HTTP ' . $code)];
    }
    return ['ok' => true, 'data' => $data];
}

/** เก็บ token ลง session (รวม refresh_token เดิมถ้า Google ไม่ส่งมาใหม่) */
function google_store_tokens($data) {
    $prev = $_SESSION['google_tokens'] ?? [];
    $_SESSION['google_tokens'] = [
        'access_token'  => $data['access_token'] ?? ($prev['access_token'] ?? null),
        'refresh_token' => $data['refresh_token'] ?? ($prev['refresh_token'] ?? null),
        'expires_at'    => time() + (int)($data['expires_in'] ?? 3600) - 60,
    ];
}

/** คืน access token ที่ใช้งานได้ (refresh อัตโนมัติถ้าหมดอายุ) หรือ null */
function google_get_access_token() {
    $tok = $_SESSION['google_tokens'] ?? null;
    if (!$tok || empty($tok['access_token'])) return null;
    if (time() < ($tok['expires_at'] ?? 0)) return $tok['access_token'];
    // หมดอายุ → refresh
    if (empty($tok['refresh_token'])) return null;
    $r = google_token_request([
        'client_id'     => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'refresh_token' => $tok['refresh_token'],
        'grant_type'    => 'refresh_token',
    ]);
    if (!$r['ok']) return null;
    google_store_tokens($r['data']);
    return $_SESSION['google_tokens']['access_token'];
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
