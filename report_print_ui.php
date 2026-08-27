<?php
/**
 * report_print_ui.php — ส่วนหน้าตาที่ใช้ร่วมกันของเอกสารรายงานสำหรับพิมพ์
 * ใช้โดย student_report_print.php (รายบุคคล) และ class_report_print.php (ภาพรวมชั้นเรียน)
 *
 * เอกสารทั้งสองเป็น HTML ฝั่งเซิร์ฟเวอร์ล้วน ไม่โหลดฟอนต์/สคริปต์ภายนอกใด ๆ
 * เพื่อให้สั่งพิมพ์หรือบันทึกเป็น PDF ได้ทันทีแม้เครื่องไม่มีอินเทอร์เน็ต
 */

/** หนีอักขระ HTML */
function rp_esc($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/** ตัวเลขในรายงาน — ไม่มีข้อมูลให้แสดงขีดกลาง ไม่ใช่เลข 0 (สำคัญกับการอ่านผลวิจัย) */
function rp_num($v, $digits = 2) {
    if ($v === null || $v === '') return '—';
    return number_format((float)$v, $digits);
}

/** ส่วนต่างแบบมีเครื่องหมาย +/- พร้อมสีเขียว/แดง */
function rp_diff($v, $digits = 2, $suffix = '') {
    if ($v === null || $v === '') return '<span class="muted">—</span>';
    $f = (float)$v;
    $cls  = ($f > 0) ? 'up' : (($f < 0) ? 'down' : 'flat');
    $sign = ($f > 0) ? '+' : '';
    $txt  = ($f == 0) ? 'เท่าเดิม' : $sign . number_format($f, $digits) . $suffix;
    return '<span class="delta ' . $cls . '">' . rp_esc($txt) . '</span>';
}

/** แถบสัดส่วนคะแนน (ใช้แทนกราฟ เพราะพิมพ์ออกกระดาษได้แน่นอนกว่ารูป) */
function rp_bar($value, $max, $cls = '') {
    $pct = ($max > 0 && $value !== null) ? max(0, min(100, ((float)$value / (float)$max) * 100)) : 0;
    return '<div class="bar ' . rp_esc($cls) . '"><span style="width:' . round($pct, 1) . '%"></span></div>';
}

/** วันที่แบบไทยอย่างย่อ (พ.ศ.) สำหรับหัวเอกสาร */
function rp_thai_date($ts = null) {
    $t = $ts ? strtotime($ts) : time();
    if (!$t) return '—';
    $months = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.',
               'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    return date('j', $t) . ' ' . $months[(int)date('n', $t)] . ' ' . (date('Y', $t) + 543);
}

/** วันที่-เวลาแบบสั้น ใช้ในตาราง */
function rp_when($ts) {
    if (!$ts) return '—';
    $t = strtotime($ts);
    return $t ? date('j/n/Y H:i', $t) : '—';
}

/** ป้ายระดับคุณภาพ */
function rp_level_badge($level) {
    if ($level === '' || $level === null) return '<span class="muted">—</span>';
    $map = [
        'ดีมาก'        => 'lv-4',
        'ดี'           => 'lv-3',
        'ปานกลาง'      => 'lv-2',
        'พอใช้'        => 'lv-1',
        'ต้องปรับปรุง' => 'lv-0',
    ];
    $cls = $map[$level] ?? 'lv-2';
    return '<span class="pill ' . $cls . '">' . rp_esc($level) . '</span>';
}

/** แถบเครื่องมือด้านบน (ไม่ถูกพิมพ์ลงกระดาษ) */
function rp_toolbar($extraHtml = '') {
    ?>
    <div class="toolbar no-print">
      <button class="btn-print" onclick="window.print()">🖨️ พิมพ์ / บันทึกเป็น PDF</button>
      <?php echo $extraHtml; ?>
      <button class="btn-back" onclick="if(window.opener){window.close();}else{history.back();}">ปิดหน้าต่าง</button>
    </div>
    <?php
}

/** สไตล์ร่วมของเอกสารรายงาน (A4 แนวตั้ง) */
function rp_styles() {
    ?>
<style>
  * { box-sizing: border-box; }
  body {
    font-family: "TH Sarabun New", "Sarabun", "Segoe UI", Tahoma, sans-serif;
    color: #1e293b; margin: 0; padding: 0; background: #f1f5f9;
  }
  .toolbar {
    position: sticky; top: 0; z-index: 10;
    background: #0f172a; color: #fff; padding: 10px 16px;
    display: flex; gap: 10px; align-items: center; justify-content: center; flex-wrap: wrap;
  }
  .toolbar button, .toolbar a {
    border: 0; border-radius: 999px; padding: 8px 20px; font-size: 15px;
    font-weight: 700; cursor: pointer; text-decoration: none; display: inline-block;
  }
  .btn-print { background: #ef4444; color: #fff; }
  .btn-back  { background: #e2e8f0; color: #0f172a; }
  .btn-alt   { background: #2563eb; color: #fff; }
  .toolbar .hint { font-size: 13.5px; color: #cbd5e1; }

  .sheet {
    background: #fff; max-width: 820px; margin: 16px auto; padding: 26px 30px;
    box-shadow: 0 4px 18px rgba(0,0,0,0.08);
  }
  .doc-head { text-align: center; border-bottom: 3px double #334155; padding-bottom: 10px; }
  .doc-head h1 { font-size: 23px; margin: 0 0 3px; }
  .doc-head .sub { font-size: 15px; color: #475569; }
  .idbox {
    display: flex; flex-wrap: wrap; gap: 4px 22px; font-size: 14.5px;
    margin: 10px 0 14px; padding: 8px 12px; background: #f8fafc;
    border: 1px solid #e2e8f0; border-radius: 8px;
  }
  .idbox b { color: #0f172a; }
  .idbox .grow { margin-left: auto; }

  h2.sec-title {
    font-size: 16.5px; margin: 16px 0 8px; padding: 5px 10px;
    background: #1e3a8a; color: #fff; border-radius: 6px;
  }
  h2.sec-title span { font-weight: 400; font-size: 13.5px; opacity: .85; }
  h3.sub-title { font-size: 15px; margin: 12px 0 5px; color: #1e3a8a; }

  .cards { display: flex; flex-wrap: wrap; gap: 8px; }
  .card {
    flex: 1 1 130px; border: 1px solid #cbd5e1; border-radius: 8px;
    padding: 8px 10px; background: #f8fafc;
  }
  .card .lbl { font-size: 12.5px; color: #64748b; }
  .card .val { font-size: 21px; font-weight: 700; color: #0f172a; line-height: 1.25; }
  .card .foot { font-size: 12px; color: #64748b; }

  table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
  th, td { border: 1px solid #cbd5e1; padding: 4px 7px; vertical-align: middle; text-align: left; }
  th { background: #f1f5f9; font-weight: 700; text-align: center; }
  td.num, th.num { text-align: center; white-space: nowrap; }
  tfoot td { background: #f8fafc; font-weight: 700; }
  tr.hi td { background: #fffbeb; }

  .bar { height: 7px; border-radius: 999px; background: #e2e8f0; overflow: hidden; min-width: 60px; }
  .bar > span { display: block; height: 100%; border-radius: 999px; background: #2563eb; }
  .bar.pre > span  { background: #94a3b8; }
  .bar.post > span { background: #2563eb; }
  .bar.ai > span   { background: #7c3aed; }

  .delta { font-weight: 700; border-radius: 999px; padding: 0 7px; font-size: 12.5px; white-space: nowrap; }
  .delta.up   { background: #dcfce7; color: #166534; }
  .delta.down { background: #fee2e2; color: #991b1b; }
  .delta.flat { background: #e2e8f0; color: #475569; }

  .pill { border-radius: 999px; padding: 1px 9px; font-size: 12.5px; font-weight: 700; white-space: nowrap; }
  .lv-4 { background: #dbeafe; color: #1e40af; }
  .lv-3 { background: #dcfce7; color: #166534; }
  .lv-2 { background: #fef9c3; color: #854d0e; }
  .lv-1 { background: #ffedd5; color: #9a3412; }
  .lv-0 { background: #fee2e2; color: #991b1b; }

  .muted { color: #94a3b8; }
  .note {
    font-size: 13px; color: #475569; background: #f8fafc;
    border-left: 3px solid #94a3b8; padding: 6px 10px; border-radius: 4px; margin-top: 6px;
  }
  .twocol { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
  .box { border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 10px; font-size: 13.5px; }
  .box h4 { margin: 0 0 5px; font-size: 14px; }
  .box.good  { border-left: 3px solid #10b981; }
  .box.watch { border-left: 3px solid #f59e0b; }
  .box.info  { border-left: 3px solid #3b82f6; }
  .box ul { margin: 0; padding-left: 18px; }
  .box li { margin-bottom: 3px; }

  .checklist { display: grid; grid-template-columns: 1fr 1fr; gap: 2px 14px; font-size: 13.5px; }
  .checklist .item { display: flex; justify-content: space-between; border-bottom: 1px dotted #e2e8f0; padding: 2px 0; }
  .checklist .yes { color: #166534; font-weight: 700; }
  .checklist .no  { color: #b91c1c; font-weight: 700; }

  .signrow { display: flex; gap: 40px; margin-top: 26px; page-break-inside: avoid; }
  .signrow .sign { flex: 1; text-align: center; font-size: 13.5px; color: #475569; }
  .signrow .line { border-bottom: 1px dotted #64748b; height: 34px; margin-bottom: 4px; }

  .foot-note { margin-top: 14px; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 6px; }
  .no-data { text-align: center; color: #64748b; padding: 40px; }
  .page-break { page-break-after: always; }

  @media print {
    body { background: #fff; }
    .no-print { display: none !important; }
    .sheet { box-shadow: none; margin: 0; max-width: none; padding: 0; }
    .sheet + .sheet { page-break-before: always; }
    table, .box, .signrow, .cards { page-break-inside: avoid; }
    tr { page-break-inside: avoid; }
    @page { size: A4 portrait; margin: 12mm 12mm 14mm; }
  }
</style>
    <?php
}
