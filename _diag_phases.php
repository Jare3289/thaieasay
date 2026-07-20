<?php
// หน้าตรวจสอบชั่วคราว: ดูการกระจายของ test_phase ในตาราง evaluations (ครูเท่านั้น)
require_once 'auth_helper.php';
require_login('teacher');
header('Content-Type: text/html; charset=utf-8');
echo '<!DOCTYPE html><html lang="th"><head><meta charset="UTF-8"><title>ตรวจ test_phase</title>'
   . '<style>body{font-family:sans-serif;padding:24px}table{border-collapse:collapse}td,th{border:1px solid #ccc;padding:6px 12px}</style></head><body>';
echo '<h2>การกระจายของ test_phase ในตาราง evaluations</h2>';
try {
    $rows = $pdo->query("
        SELECT
            COALESCE(NULLIF(test_phase,''), '(ว่าง/NULL)') AS phase,
            evaluator_type,
            COUNT(*) AS n
        FROM evaluations
        GROUP BY COALESCE(NULLIF(test_phase,''), '(ว่าง/NULL)'), evaluator_type
        ORDER BY phase, evaluator_type
    ")->fetchAll(PDO::FETCH_ASSOC);

    $total = $pdo->query("SELECT COUNT(*) c FROM evaluations")->fetch()['c'];
    echo "<p>จำนวนแถวทั้งหมดในตาราง evaluations = <b>$total</b></p>";

    if (!$rows) {
        echo '<p style="color:#b00">ไม่มีข้อมูลในตาราง evaluations เลย</p>';
    } else {
        echo '<table><tr><th>test_phase</th><th>evaluator_type</th><th>จำนวน</th></tr>';
        foreach ($rows as $r) {
            echo '<tr><td>' . htmlspecialchars($r['phase']) . '</td><td>'
               . htmlspecialchars($r['evaluator_type']) . '</td><td style="text-align:center">'
               . (int)$r['n'] . '</td></tr>';
        }
        echo '</table>';
    }
} catch (Exception $e) {
    echo '<p style="color:#b00">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
}
echo '</body></html>';
