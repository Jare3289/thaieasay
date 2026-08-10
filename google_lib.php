<?php
/**
 * google_lib.php — ฟังก์ชันช่วยเหลือ Google OAuth (ไม่มี dispatcher)
 * ใช้ร่วมกันโดย google_auth.php และ google_upload_doc.php
 * ปลอดภัยต่อการ include ซ้ำ (require_once) — ประกาศเฉพาะฟังก์ชัน ไม่มีการ echo/exit
 */
require_once 'google_config.php';

if (!function_exists('google_is_teacher')) {
    function google_is_teacher() {
        return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'teacher';
    }
}

if (!function_exists('google_token_request')) {
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
}

if (!function_exists('google_store_tokens')) {
    /** เก็บ token ลง session (รวม refresh_token เดิมถ้า Google ไม่ส่งมาใหม่) */
    function google_store_tokens($data) {
        $prev = $_SESSION['google_tokens'] ?? [];
        $_SESSION['google_tokens'] = [
            'access_token'  => $data['access_token'] ?? ($prev['access_token'] ?? null),
            'refresh_token' => $data['refresh_token'] ?? ($prev['refresh_token'] ?? null),
            'expires_at'    => time() + (int)($data['expires_in'] ?? 3600) - 60,
        ];
    }
}

if (!function_exists('google_get_access_token')) {
    /** คืน access token ที่ใช้งานได้ (refresh อัตโนมัติถ้าหมดอายุ) หรือ null */
    function google_get_access_token() {
        $tok = $_SESSION['google_tokens'] ?? null;
        if (!$tok || empty($tok['access_token'])) return null;
        if (time() < ($tok['expires_at'] ?? 0)) return $tok['access_token'];
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
}
