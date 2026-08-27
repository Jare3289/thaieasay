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

/**
 * สไตล์ร่วมของเอกสารรายงาน (A4 แนวตั้ง)
 * $scope = '' (ค่าเริ่มต้น) → ใช้กับทั้งหน้า เหมาะกับเอกสารสำหรับพิมพ์ที่ไม่มีอย่างอื่นในหน้า
 * $scope = '.rp-doc' ฯลฯ  → จำกัดสไตล์ไว้เฉพาะในกล่องนั้น ใช้ตอนฝังรายงานในหน้าเว็บของระบบ
 *                            (กันไม่ให้ชนกับ Bootstrap ของหน้าเว็บ เช่นคลาส .card)
 */
function rp_styles($scope = '') {
    $root = ($scope === '' || $scope === null) ? 'body' : $scope;
    $css  = <<<'RPCSS'
  /* ขนาดตัวอักษรของเอกสารนี้กำหนดเป็น "พอยต์ (pt)" ทั้งหมด อยู่ในช่วง 14-16 pt
     ตามมาตรฐานเอกสารราชการไทย (TH Sarabun New 16 pt) เพื่อให้อ่านง่ายทั้งบนจอและบนกระดาษ
     ตัวเลข/ตารางใช้ 14 pt ส่วนหัวข้อใหญ่ขึ้นไปตามลำดับความสำคัญ
     ความกว้างเนื้อหาบนจอถูกตั้งให้เท่ากับกระดาษ A4 จริง (190 มม.) จะได้เห็นการจัดหน้าตรงกับตอนพิมพ์ */
  @@ * { box-sizing: border-box; }
  @@ {
    font-family: "TH Sarabun New", "Sarabun", "Segoe UI", Tahoma, sans-serif;
    color: #1e293b; margin: 0; padding: 0; background: #f1f5f9;
    font-size: 16pt; line-height: 1.45;
  }
  @@ .toolbar {
    position: sticky; top: 0; z-index: 10;
    background: #0f172a; color: #fff; padding: 10px 16px;
    display: flex; gap: 10px; align-items: center; justify-content: center; flex-wrap: wrap;
    font-size: 12pt;
  }
  @@ .toolbar button, @@ .toolbar a {
    border: 0; border-radius: 999px; padding: 8px 20px; font-size: 12pt;
    font-weight: 700; cursor: pointer; text-decoration: none; display: inline-block;
    font-family: inherit;
  }
  @@ .btn-print { background: #ef4444; color: #fff; }
  @@ .btn-back { background: #e2e8f0; color: #0f172a; }
  @@ .btn-alt { background: #2563eb; color: #fff; }
  @@ .toolbar .hint { font-size: 11pt; color: #cbd5e1; }

  /* 770px - padding ซ้ายขวา = 718px ≈ 190 มม. เท่ากับพื้นที่พิมพ์จริงของ A4 */
  @@ .sheet {
    background: #fff; max-width: 770px; margin: 16px auto; padding: 22px 26px;
    box-shadow: 0 4px 18px rgba(0,0,0,0.08);
  }
  @@ .doc-head { text-align: center; border-bottom: 3px double #334155; padding-bottom: 8px; }
  @@ .doc-head h1 { font-size: 22pt; margin: 0 0 2px; line-height: 1.25; }
  @@ .doc-head .sub { font-size: 16pt; color: #475569; }
  @@ .idbox {
    display: flex; flex-wrap: wrap; gap: 2px 20px; font-size: 15pt;
    margin: 8px 0 12px; padding: 6px 10px; background: #f8fafc;
    border: 1px solid #e2e8f0; border-radius: 8px; line-height: 1.4;
  }
  @@ .idbox b { color: #0f172a; }
  @@ .idbox .grow { margin-left: auto; }

  @@ h2.sec-title {
    font-size: 18pt; margin: 14px 0 7px; padding: 4px 10px; line-height: 1.35;
    background: #1e3a8a; color: #fff; border-radius: 6px;
  }
  @@ h2.sec-title span { font-weight: 400; font-size: 14pt; opacity: .88; }
  @@ h3.sub-title { font-size: 16pt; margin: 10px 0 4px; color: #1e3a8a; }

  /* การ์ดสรุปตัวเลข — จัดเป็นตารางกริดให้ทุกใบกว้างเท่ากันเสมอ ใบสุดท้ายจะไม่ยืดเต็มแถว */
  @@ .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(128px, 1fr)); gap: 7px; }
  @@ .card {
    border: 1px solid #cbd5e1; border-radius: 8px;
    padding: 6px 9px; background: #f8fafc;
  }
  @@ .card .lbl { font-size: 14pt; color: #64748b; line-height: 1.3; }
  @@ .card .val { font-size: 20pt; font-weight: 700; color: #0f172a; line-height: 1.25; }
  @@ .card .foot { font-size: 14pt; color: #64748b; line-height: 1.3; }

  @@ table { width: 100%; border-collapse: collapse; font-size: 14pt; }
  @@ th, @@ td { border: 1px solid #cbd5e1; padding: 2px 5px; vertical-align: middle; text-align: left; line-height: 1.35; }
  @@ th { background: #f1f5f9; font-weight: 700; text-align: center; }
  @@ td.num, @@ th.num { text-align: center; white-space: nowrap; }
  /* ช่องที่จัดกึ่งกลางแต่ยอมให้ตัดบรรทัดได้ (เช่น วันที่-เวลา) กันไม่ให้ไปเบียดคอลัมน์ข้าง ๆ */
  @@ td.wrap, @@ th.wrap { text-align: center; white-space: normal; }
  @@ tfoot td { background: #f8fafc; font-weight: 700; }
  @@ tr.hi td { background: #fffbeb; }

  @@ .bar { height: 8px; border-radius: 999px; background: #e2e8f0; overflow: hidden; min-width: 55px; }
  @@ .bar > span { display: block; height: 100%; border-radius: 999px; background: #2563eb; }
  @@ .bar.pre > span { background: #94a3b8; }
  @@ .bar.post > span { background: #2563eb; }
  @@ .bar.ai > span { background: #7c3aed; }

  @@ .delta { font-weight: 700; border-radius: 999px; padding: 0 6px; font-size: 14pt; white-space: nowrap; }
  @@ .delta.up { background: #dcfce7; color: #166534; }
  @@ .delta.down { background: #fee2e2; color: #991b1b; }
  @@ .delta.flat { background: #e2e8f0; color: #475569; }

  @@ .pill { border-radius: 999px; padding: 0 8px; font-size: 14pt; font-weight: 700; white-space: nowrap; }
  @@ .lv-4 { background: #dbeafe; color: #1e40af; }
  @@ .lv-3 { background: #dcfce7; color: #166534; }
  @@ .lv-2 { background: #fef9c3; color: #854d0e; }
  @@ .lv-1 { background: #ffedd5; color: #9a3412; }
  @@ .lv-0 { background: #fee2e2; color: #991b1b; }

  @@ .muted { color: #94a3b8; }
  @@ .note {
    font-size: 14pt; color: #475569; background: #f8fafc; line-height: 1.4;
    border-left: 3px solid #94a3b8; padding: 5px 9px; border-radius: 4px; margin-top: 5px;
  }
  @@ .twocol { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
  @@ .box { border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px 9px; font-size: 14pt; line-height: 1.4; }
  @@ .box h4 { margin: 0 0 3px; font-size: 15pt; }
  @@ .box.good { border-left: 3px solid #10b981; }
  @@ .box.watch { border-left: 3px solid #f59e0b; }
  @@ .box.info { border-left: 3px solid #3b82f6; }
  @@ .box ul { margin: 0; padding-left: 20px; }
  @@ .box li { margin-bottom: 2px; }

  @@ .checklist { display: grid; grid-template-columns: 1fr 1fr; gap: 0 16px; font-size: 14pt; }
  @@ .checklist .item { display: flex; justify-content: space-between; gap: 8px;
                     border-bottom: 1px dotted #e2e8f0; padding: 1px 3px 1px 0; }
  @@ .checklist .yes { color: #166534; font-weight: 700; white-space: nowrap; }
  @@ .checklist .no { color: #b91c1c; font-weight: 700; white-space: nowrap; }

  /* ---- บทวิเคราะห์รายบุคคล ---- */
  @@ .ins { border: 1px solid #cbd5e1; border-left-width: 4px; border-radius: 8px;
         padding: 6px 10px; margin-bottom: 6px; font-size: 14pt; line-height: 1.45;
         page-break-inside: avoid; }
  @@ .ins h4 { margin: 0 0 2px; font-size: 15pt; }
  @@ .ins ul { margin: 3px 0 0; padding-left: 20px; }
  @@ .ins li { margin-bottom: 1px; }
  @@ .ins.good { border-color: #cbd5e1; border-left-color: #10b981; background: #f0fdf4; }
  @@ .ins.warn { border-color: #cbd5e1; border-left-color: #f59e0b; background: #fffbeb; }
  @@ .ins.info { border-color: #cbd5e1; border-left-color: #3b82f6; background: #f8fafc; }
  @@ .ins.good h4 { color: #047857; }
  @@ .ins.warn h4 { color: #b45309; }
  @@ .ins.info h4 { color: #1e40af; }

  /* ---- เรียงความฉบับเต็ม ---- */
  @@ .essay-doc { border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 12px; margin-bottom: 8px;
               page-break-inside: auto; }
  @@ .essay-doc > .head { display: flex; justify-content: space-between; gap: 10px; flex-wrap: wrap;
                       border-bottom: 1px dashed #cbd5e1; padding-bottom: 3px; margin-bottom: 5px; }
  @@ .essay-doc > .head b { font-size: 15pt; }
  @@ .essay-doc .meta { font-size: 14pt; color: #64748b; }
  @@ .essay-doc .topic { font-size: 14pt; color: #475569; margin-bottom: 4px; }
  @@ .essay-doc .part { font-size: 14pt; font-weight: 700; color: #1e3a8a; margin-top: 4px; }
  @@ .essay-doc p { margin: 0 0 4px; text-indent: 2.2em; line-height: 1.7; }

  /* ---- คู่ ป้ายกำกับ/ค่า และป้ายสถานะสั้น ๆ ---- */
  @@ .kv { font-size: 14pt; line-height: 1.5; }
  @@ .kv b { color: #0f172a; }
  @@ .chips { display: flex; flex-wrap: wrap; gap: 4px; }

  @@ .signrow { display: flex; gap: 34px; margin-top: 22px; page-break-inside: avoid; }
  @@ .signrow .sign { flex: 1; text-align: center; font-size: 15pt; color: #475569; }
  @@ .signrow .line { border-bottom: 1px dotted #64748b; height: 30px; margin-bottom: 3px; }

  @@ .foot-note { margin-top: 12px; font-size: 14pt; color: #64748b; line-height: 1.4;
               border-top: 1px solid #e2e8f0; padding-top: 5px; }
  @@ .no-data { text-align: center; color: #64748b; padding: 40px; font-size: 16pt; }
  @@ .page-break { page-break-after: always; }

  @media print {
    @@ { background: #fff; }
    @@ .no-print { display: none !important; }
    @@ .sheet { box-shadow: none; margin: 0; max-width: none; padding: 0; }
    @@ .sheet + .sheet { page-break-before: always; }
    @@ table, @@ .box, @@ .signrow, @@ .cards { page-break-inside: avoid; }
    @@ tr { page-break-inside: avoid; }
    @@ thead { display: table-header-group; }   /* ตารางยาวข้ามหน้า ให้หัวตารางซ้ำทุกหน้า */
    @page { size: A4 portrait; margin: 10mm 10mm 12mm; }
  }

  /* จอแคบ (ดูบนมือถือก่อนสั่งพิมพ์) — ให้ตารางเลื่อนแนวนอนได้แทนที่จะบีบตัวอักษร */
  @media screen and (max-width: 820px) {
    @@ .sheet { padding: 16px 14px; }
    @@ .twocol, @@ .checklist { grid-template-columns: 1fr; }
  }
RPCSS;
    echo "<style>\n" . str_replace('@@', $root, $css) . "\n</style>";
}
