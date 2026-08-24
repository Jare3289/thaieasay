<?php
// เสิร์ฟไอคอน PWA ผ่าน PHP แทนการให้เบราว์เซอร์ขอไฟล์ .png ตรง ๆ
// สาเหตุ: เซิร์ฟเวอร์ (nginx บนโฮสต์ Plesk ปัจจุบัน) ตอบ 404 กับไฟล์รูปภาพทุกไฟล์
// (ปัญหาที่ชั้นเว็บเซิร์ฟเวอร์ ไม่เกี่ยวกับไฟล์ในโปรเจกต์ แก้จากโค้ดฝั่งนี้ไม่ได้โดยตรง)
// แต่ไฟล์ .php เสิร์ฟได้ปกติ จึงอ้อมผ่าน PHP อ่านไฟล์แล้วส่งออกไปแทน
// รายชื่อไฟล์อนุญาต (whitelist) กันไม่ให้อ่านไฟล์อื่นนอกเหนือจากไอคอนที่กำหนดไว้
$allowed = [
    'icon-192.png'     => 'icons/icon-192.png',
    'icon-512.png'     => 'icons/icon-512.png',
    'maskable-192.png' => 'icons/maskable-192.png',
    'maskable-512.png' => 'icons/maskable-512.png',
    'icon-180.png'     => 'icons/icon-180.png',
    'favicon-32.png'   => 'icons/favicon-32.png',
];

$f = isset($_GET['f']) ? $_GET['f'] : '';
if (!isset($allowed[$f])) {
    http_response_code(404);
    exit;
}

$path = __DIR__ . '/' . $allowed[$f];
if (!is_file($path)) {
    http_response_code(404);
    exit;
}

header('Content-Type: image/png');
header('Cache-Control: public, max-age=86400');
header('Content-Length: ' . filesize($path));
readfile($path);
