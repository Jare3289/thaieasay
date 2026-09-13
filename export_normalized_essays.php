<?php
/**
 * ดาวน์โหลดเรียงความฉบับจัดเว้นวรรคแล้วเป็นไฟล์ข้อความสำหรับคุณครู
 *
 * ส่งออกเฉพาะฉบับที่ยังตรงกับต้นฉบับปัจจุบัน เพื่อไม่ให้คุณครูนำฉบับเก่า
 * ที่นักเรียนแก้ไขภายหลังไปใช้ตรวจหรือวิเคราะห์โดยไม่ตั้งใจ
 */
require_once 'auth_helper.php';
require_once 'writing_check_config.php';

require_login();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'เฉพาะคุณครูเท่านั้นที่ดาวน์โหลดเรียงความฉบับจัดวรรคแล้วได้';
    exit;
}

$room = isset($_GET['classroom']) ? trim((string)$_GET['classroom']) : '';
$phases = ai_norm_phases();
$phaseOrder = array_flip($phases);

$sql = "
    SELECT se.student_id, se.essay_phase, se.intro_content AS source_intro,
           se.body_content AS source_body, se.conclusion_content AS source_conclusion,
           n.intro_content, n.body_content, n.conclusion_content, n.source_hash,
           s.student_name, s.classroom
      FROM student_essays se
      JOIN students s ON s.student_id = se.student_id
      JOIN essay_normalized n
        ON n.student_id = se.student_id AND n.essay_phase = se.essay_phase
     WHERE se.essay_phase IN (" . implode(',', array_fill(0, count($phases), '?')) . ")
";
$params = $phases;
if ($room !== '') {
    $sql .= ' AND s.classroom = ?';
    $params[] = $room;
}
$sql .= ' ORDER BY se.student_id ASC, se.essay_phase ASC';

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'ยังไม่มีเรียงความฉบับจัดวรรคแล้วให้ดาวน์โหลด';
    exit;
}

// ตัดฉบับที่ล้าสมัยออก และเรียงรอบงานตามลำดับที่ใช้ในการเรียน
$validRows = [];
foreach ($rows as $row) {
    $sourceBody = json_decode((string)($row['source_body'] ?? ''), true);
    if (!is_array($sourceBody)) {
        $sourceBody = (($row['source_body'] ?? '') !== '') ? [(string)$row['source_body']] : [];
    }
    $sourceBody = array_values(array_filter(array_map('strval', $sourceBody), function ($part) {
        return trim($part) !== '';
    }));
    $currentHash = ai_essay_hash(
        (string)($row['source_intro'] ?? ''),
        $sourceBody,
        (string)($row['source_conclusion'] ?? '')
    );
    if (hash_equals((string)($row['source_hash'] ?? ''), $currentHash)) {
        $validRows[] = $row;
    }
}

usort($validRows, function ($a, $b) use ($phaseOrder) {
    $studentCompare = strnatcmp((string)$a['student_id'], (string)$b['student_id']);
    if ($studentCompare !== 0) return $studentCompare;
    return ($phaseOrder[$a['essay_phase']] ?? PHP_INT_MAX)
         - ($phaseOrder[$b['essay_phase']] ?? PHP_INT_MAX);
});

if (!$validRows) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'ไม่พบเรียงความฉบับจัดวรรคแล้วที่ตรงกับต้นฉบับปัจจุบัน';
    exit;
}

$studentNumbers = [];
foreach ($validRows as $row) {
    $sid = (string)$row['student_id'];
    if (!isset($studentNumbers[$sid])) {
        $studentNumbers[$sid] = count($studentNumbers) + 1;
    }
}

$lines = [
    'เรียงความฉบับจัดเว้นวรรคแล้วสำหรับตรวจและวิเคราะห์',
    'เรียงลำดับนักเรียนจากรหัสนักเรียน จำนวน ' . count($studentNumbers) . ' คน',
    str_repeat('=', 72),
];

foreach ($validRows as $row) {
    $sid = (string)$row['student_id'];
    $body = json_decode((string)($row['body_content'] ?? ''), true);
    if (!is_array($body)) $body = [];
    $essayParts = array_merge(
        [(string)($row['intro_content'] ?? '')],
        array_values(array_map('strval', $body)),
        [(string)($row['conclusion_content'] ?? '')]
    );
    $essayParts = array_values(array_filter($essayParts, function ($part) {
        return trim($part) !== '';
    }));

    $lines[] = '';
    $lines[] = 'นักเรียนคนที่ ' . $studentNumbers[$sid] . ' (รหัสนักเรียน ' . $sid . ')';
    $lines[] = 'ชื่อ-สกุล: ' . formatNamePrefix((string)($row['student_name'] ?? ''));
    $lines[] = 'ห้องเรียน: ' . ((string)($row['classroom'] ?? '') ?: '-');
    $lines[] = 'รอบงาน: ' . ai_phase_label((string)$row['essay_phase']);
    $lines[] = str_repeat('-', 72);
    $lines[] = implode("\n\n", $essayParts);
    $lines[] = str_repeat('=', 72);
}

$filename = 'normalized_student_essays_' . gmdate('Y-m-d') . '.txt';
header('Content-Type: text/plain; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('X-Content-Type-Options: nosniff');
echo "\xEF\xBB\xBF" . implode("\r\n", $lines) . "\r\n";
