<?php
/**
 * google_upload_doc.php — อัปโหลดรายงาน (HTML) ขึ้น Google Drive แล้วแปลงเป็น Google Docs
 * รับ POST (JSON): { html: "<html>...", title: "ชื่อไฟล์" }
 * คืน: { success:true, link:"https://docs.google.com/...", id:"..." }
 *      หรือ { success:false, error:"...", reauth:true? }
 */
require_once 'auth_helper.php';
require_once 'google_config.php';
require_once 'google_auth.php'; // ใช้ google_get_access_token()

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
    echo json_encode(['success' => false, 'error' => 'ต้องเป็นคุณครู']); exit;
}
if (!google_is_configured()) {
    echo json_encode(['success' => false, 'error' => 'ยังไม่ได้ตั้งค่า Google API']); exit;
}

$access = google_get_access_token();
if (!$access) {
    echo json_encode(['success' => false, 'error' => 'ยังไม่ได้เชื่อมต่อบัญชี Google หรือโทเคนหมดอายุ', 'reauth' => true]); exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$html  = isset($input['html'])  ? $input['html']  : '';
$title = isset($input['title']) ? trim($input['title']) : 'รายงานผลการวิจัย';
if ($html === '') { echo json_encode(['success' => false, 'error' => 'ไม่พบเนื้อหารายงาน']); exit; }

// สร้าง multipart/related: ส่วนที่ 1 = metadata (JSON), ส่วนที่ 2 = เนื้อหา HTML
$boundary = 'thaieasay_' . bin2hex(random_bytes(8));
$metadata = json_encode([
    'name'     => $title,
    'mimeType' => 'application/vnd.google-apps.document', // แปลง HTML → Google Docs อัตโนมัติ
]);

$body  = "--$boundary\r\n";
$body .= "Content-Type: application/json; charset=UTF-8\r\n\r\n";
$body .= $metadata . "\r\n";
$body .= "--$boundary\r\n";
$body .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
$body .= $html . "\r\n";
$body .= "--$boundary--";

$ch = curl_init('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&fields=id,webViewLink');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $body,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $access,
        'Content-Type: multipart/related; boundary=' . $boundary,
        'Content-Length: ' . strlen($body),
    ],
    CURLOPT_TIMEOUT => 60,
]);
$resp = curl_exec($ch);
$err  = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($resp === false) { echo json_encode(['success' => false, 'error' => 'cURL error: ' . $err]); exit; }
$data = json_decode($resp, true);

if ($code === 401) { echo json_encode(['success' => false, 'error' => 'โทเคนหมดอายุ โปรดเชื่อมต่อใหม่', 'reauth' => true]); exit; }
if ($code >= 400 || !isset($data['id'])) {
    $msg = isset($data['error']['message']) ? $data['error']['message'] : ('HTTP ' . $code);
    echo json_encode(['success' => false, 'error' => 'อัปโหลดไม่สำเร็จ: ' . $msg]); exit;
}

echo json_encode([
    'success' => true,
    'id'      => $data['id'],
    'link'    => isset($data['webViewLink']) ? $data['webViewLink'] : ('https://docs.google.com/document/d/' . $data['id'] . '/edit'),
]);
