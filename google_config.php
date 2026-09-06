<?php
/**
 * google_config.php — การตั้งค่าเชื่อมต่อ Google Drive / Docs API
 * ---------------------------------------------------------------------------
 * วิธีตั้งค่า (ทำครั้งเดียว):
 *   1. เข้า https://console.cloud.google.com/ → สร้างโปรเจกต์ใหม่
 *   2. เมนู "APIs & Services" → "Enabled APIs & services" → เปิดใช้ "Google Drive API"
 *   3. เมนู "OAuth consent screen" → เลือก External → กรอกข้อมูลแอป → เพิ่มอีเมลคุณครูใน Test users
 *   4. เมนู "Credentials" → Create Credentials → OAuth client ID → ประเภท "Web application"
 *      - Authorized redirect URIs: ใส่ค่าที่แสดงในหน้า "สถานะการเชื่อมต่อ" (google_auth.php?action=status)
 *        โดยปกติคือ  https://<โดเมนของคุณ>/google_auth.php?action=callback
 *   5. คัดลอก Client ID และ Client Secret มาวางด้านล่างนี้
 */

// ====== ค่าลับ (Client ID/Secret) ======
// เก็บไว้ในไฟล์ google_secrets.php ที่ไม่ถูก commit ขึ้น git (ดู .gitignore)
// สร้างไฟล์นี้โดยคัดลอกจาก google_secrets.sample.php แล้วกรอกค่าจริง
// ถ้าไม่มีไฟล์นั้น ให้ปล่อยค่าว่างไว้ (ระบบจะแจ้งว่ายังไม่ได้ตั้งค่า)
if (file_exists(__DIR__ . '/google_secrets.php')) {
    require_once __DIR__ . '/google_secrets.php';
}
if (!defined('GOOGLE_CLIENT_ID'))     define('GOOGLE_CLIENT_ID',     '');
if (!defined('GOOGLE_CLIENT_SECRET')) define('GOOGLE_CLIENT_SECRET', '');
// =======================================

// ขอบเขตสิทธิ์: สร้างไฟล์ใน Drive ของผู้ใช้ (drive.file = เข้าถึงเฉพาะไฟล์ที่แอปสร้างเอง — ปลอดภัยสุด)
define('GOOGLE_SCOPES', 'https://www.googleapis.com/auth/drive.file');

/** ตรวจว่าเว็บกำลังรันผ่าน HTTPS หรือไม่ (รองรับกรณีอยู่หลัง reverse proxy ด้วย) */
function google_is_https() {
    return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
}

/**
 * คำนวณ redirect URI แบบอัตโนมัติจากโดเมนที่กำลังรันอยู่
 * (ต้องนำค่านี้ไปลงทะเบียนใน Google Cloud Console → Authorized redirect URIs ให้ตรงกันเป๊ะ)
 */
function google_redirect_uri() {
    $scheme = google_is_https() ? 'https' : 'http';
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    // โฟลเดอร์ที่ไฟล์นี้ตั้งอยู่ (เผื่อระบบอยู่ในซับไดเรกทอรี)
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    return $scheme . '://' . $host . $dir . '/google_auth.php?action=callback';
}

function google_is_configured() {
    return GOOGLE_CLIENT_ID !== '' && GOOGLE_CLIENT_SECRET !== '';
}
