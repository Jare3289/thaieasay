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
 * $fontFamily = null (ค่าเริ่มต้น) → ใช้ฟอนต์ "TH Sarabun New"/"Sarabun" ตามมาตรฐานเอกสารราชการไทย
 *               (ใช้กับเอกสารสำหรับพิมพ์/PDF เท่านั้น — ไม่โหลดฟอนต์ภายนอก พิมพ์ได้แม้ไม่มีอินเทอร์เน็ต)
 *             → ระบุค่าอื่น (เช่นฟอนต์เดียวกับหน้าเว็บ) เมื่อฝังรายงานไว้ดูบนหน้าเว็บของระบบ (ไม่ใช่ตอนพิมพ์)
 */
function rp_styles($scope = '', $fontFamily = null) {
    $root = ($scope === '' || $scope === null) ? 'body' : $scope;
    $font = ($fontFamily !== null && $fontFamily !== '')
        ? $fontFamily : '"TH Sarabun New", "Sarabun", "Segoe UI", Tahoma, sans-serif';
    $css  = <<<'RPCSS'
  /* ขนาดตัวอักษรของเอกสารนี้กำหนดเป็น "พอยต์ (pt)" ทั้งหมด ตามมาตรฐานเอกสารราชการไทย
     (TH Sarabun New 16 pt) เพื่อให้อ่านง่ายทั้งบนจอและบนกระดาษ
     ความกว้างเนื้อหาบนจอถูกตั้งให้เท่ากับกระดาษ A4 จริง (190 มม.) จะได้เห็นการจัดหน้าตรงกับตอนพิมพ์

     หลักการจัดตัวอักษรของเอกสารนี้ — ให้ "ตัวเน้น" กับ "ตัวปกติ" อยู่ด้วยกัน ไม่ให้ทุกอย่างหนักเท่ากัน
     จนอ่านแล้วอึดอัด:
       - ตัวเลขและคำตัดสิน (คะแนน ระดับคุณภาพ ส่วนต่าง) = ตัวหนา สีเข้ม → สายตาจับได้ก่อน
       - ป้ายกำกับและคำอธิบายประกอบ = ตัวปกติ สีอ่อนลงหนึ่งระดับ → ไม่แย่งความสนใจ
       - เนื้อความยาว (เรียงความ บทวิเคราะห์) = ตัวปกติ ระยะบรรทัดโปร่ง อ่านต่อเนื่องได้สบาย
     และเว้นระยะหายใจให้มากขึ้นทั้งในตาราง กล่อง และระหว่างหัวข้อ */
  @@ * { box-sizing: border-box; }
  @@ {
    font-family: FONTFAMILY;
    color: #1e293b; margin: 0; padding: 0; background: #f1f5f9;
    font-size: 16pt; line-height: 1.55;
    font-weight: 400;
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
    background: #fff; max-width: 770px; margin: 16px auto; padding: 26px 30px 30px;
    box-shadow: 0 4px 18px rgba(0,0,0,0.08);
  }
  @@ .doc-head { text-align: center; border-bottom: 2px solid #1e3a8a; padding-bottom: 10px; }
  @@ .doc-head h1 { font-size: 23pt; font-weight: 700; margin: 0 0 3px; line-height: 1.3; color: #0f172a; }
  @@ .doc-head .sub { font-size: 15.5pt; font-weight: 400; color: #64748b; }
  @@ .idbox {
    display: flex; flex-wrap: wrap; gap: 4px 22px; font-size: 15pt;
    margin: 12px 0 16px; padding: 9px 13px; background: #f8fafc;
    border: 1px solid #e2e8f0; border-left: 4px solid #1e3a8a; border-radius: 8px;
    line-height: 1.55; color: #475569;
  }
  /* ป้ายกำกับเป็นตัวปกติสีอ่อน ค่าที่ตามมาเป็นตัวหนาสีเข้ม */
  @@ .idbox b { color: #0f172a; font-weight: 700; }
  @@ .idbox .grow { margin-left: auto; }

  /* หัวข้อใหญ่: เปลี่ยนจากแถบทึบเต็มความกว้าง (กินหมึกและดูหนัก) มาเป็นเส้นนำสีน้ำเงิน
     + ตัวอักษรหนาสีเข้ม อ่านง่ายกว่าและยังแยกส่วนได้ชัดแม้เครื่องพิมพ์ไม่พิมพ์พื้นหลัง */
  @@ h2.sec-title {
    font-size: 18.5pt; font-weight: 700; color: #1e3a8a;
    margin: 20px 0 9px; padding: 2px 0 5px 12px; line-height: 1.4;
    border-left: 5px solid #1e3a8a; border-bottom: 1px solid #dbe2ef;
  }
  @@ .sheet > .sec-title:first-of-type, @@ .doc-head + .sec-title { margin-top: 14px; }

  /* ---- ส่วนนำ และสารบัญ (หน้าแรกของรายงาน ก่อนเข้าเนื้อหาส่วนที่ 1) ---- */
  @@ .front-sec { margin-bottom: 6px; }
  @@ .front-sec p { margin: 0 0 8px; line-height: 1.8; text-align: justify; }
  @@ h2.front-title { margin-top: 8px; }
  @@ .toc-list { list-style: none; margin: 4px 0 0; padding: 0; counter-reset: toc; }
  @@ .toc-list li { counter-increment: toc; margin-bottom: 2px; }
  @@ .toc-list li a {
    display: flex; align-items: baseline; gap: 6px; text-decoration: none; color: #1e293b;
    padding: 5px 4px; border-bottom: 1px dotted #dbe2ef; font-size: 15pt;
  }
  @@ .toc-list li a::before {
    content: "ส่วนที่ " counter(toc) " ·"; font-weight: 700; color: #1e3a8a; white-space: nowrap;
  }
  @@ .toc-list li a:hover { background: #f8fafc; }
  @@ .toc-title { color: #1e293b; }
  @@ h2.sec-title span { font-weight: 400; font-size: 14.5pt; color: #64748b; }
  @@ h3.sub-title {
    font-size: 16pt; font-weight: 700; margin: 14px 0 6px; color: #1e40af;
    padding-bottom: 3px; border-bottom: 1px dotted #cbd5e1;
  }

  /* การ์ดสรุปตัวเลข — จัดเป็นตารางกริดให้ทุกใบกว้างเท่ากันเสมอ ใบสุดท้ายจะไม่ยืดเต็มแถว */
  @@ .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(132px, 1fr)); gap: 9px; margin: 10px 0; }
  @@ .card {
    border: 1px solid #dbe2ef; border-top: 3px solid #1e3a8a; border-radius: 8px;
    padding: 8px 11px 9px; background: #fbfcfe;
  }
  /* ป้ายกำกับตัวปกติสีอ่อน — ตัวเลขตัวหนาใหญ่ ให้สายตาจับตัวเลขได้ก่อน */
  @@ .card .lbl { font-size: 14pt; font-weight: 400; color: #64748b; line-height: 1.35; }
  @@ .card .val { font-size: 21pt; font-weight: 700; color: #0f172a; line-height: 1.3; margin: 2px 0 1px; }
  @@ .card .foot { font-size: 13.5pt; font-weight: 400; color: #64748b; line-height: 1.35; }

  @@ table { width: 100%; border-collapse: collapse; font-size: 14.5pt; margin: 8px 0 4px; }
  @@ th, @@ td {
    border: 1px solid #dbe2ef; padding: 5px 9px;
    vertical-align: middle; text-align: left; line-height: 1.45;
  }
  /* หัวตารางเป็นตัวหนาแต่ไม่ทึบจนหนัก และเนื้อตารางเป็นตัวปกติ */
  @@ th { background: #eef2f9; font-weight: 700; color: #1e3a8a; text-align: center; }
  @@ td { font-weight: 400; }
  /* แถบสลับสีอ่อน ๆ ช่วยให้สายตาไล่บรรทัดยาว ๆ ไม่หลง */
  @@ tbody tr:nth-child(even) td { background: #fbfcfe; }
  /* ช่องตัวเลขคือใจความของตาราง จึงเน้นให้หนากว่าคำอธิบายรอบ ๆ */
  @@ td.num, @@ th.num { text-align: center; white-space: nowrap; }
  @@ td.num { font-weight: 600; color: #0f172a; }
  /* ช่องที่จัดกึ่งกลางแต่ยอมให้ตัดบรรทัดได้ (เช่น วันที่-เวลา) กันไม่ให้ไปเบียดคอลัมน์ข้าง ๆ */
  @@ td.wrap, @@ th.wrap { text-align: center; white-space: normal; }
  @@ tfoot td { background: #eef2f9; font-weight: 700; color: #0f172a; }
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

  @@ .muted { color: #94a3b8; font-weight: 400; }
  /* ตัวช่วยจัดน้ำหนักตัวอักษรในเนื้อหา ใช้คู่กันเพื่อไม่ให้ทุกอย่างหนักเท่ากัน */
  @@ .em  { font-weight: 700; color: #0f172a; }
  @@ .dim { font-weight: 400; color: #64748b; }
  @@ .lead { font-size: 15.5pt; line-height: 1.7; color: #334155; }
  @@ .note {
    font-size: 14pt; color: #475569; background: #f8fafc; line-height: 1.55;
    border-left: 3px solid #94a3b8; padding: 7px 11px; border-radius: 6px; margin-top: 7px;
  }
  @@ .twocol { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
  @@ .box {
    border: 1px solid #dbe2ef; border-radius: 8px; padding: 9px 12px;
    font-size: 14.5pt; line-height: 1.55;
  }
  @@ .box h4 { margin: 0 0 5px; font-size: 15pt; font-weight: 700; }
  @@ .box.good { border-left: 4px solid #10b981; }
  @@ .box.watch { border-left: 4px solid #f59e0b; }
  @@ .box.info { border-left: 4px solid #3b82f6; }
  @@ .box ul { margin: 0; padding-left: 22px; }
  @@ .box li { margin-bottom: 3px; }

  @@ .checklist { display: grid; grid-template-columns: 1fr 1fr; gap: 0 22px; font-size: 14.5pt; }
  @@ .checklist .item { display: flex; justify-content: space-between; gap: 10px;
                     border-bottom: 1px dotted #dbe2ef; padding: 4px 4px 4px 0; line-height: 1.5; }
  @@ .checklist .yes { color: #166534; font-weight: 700; white-space: nowrap; }
  @@ .checklist .no { color: #b91c1c; font-weight: 700; white-space: nowrap; }

  /* ---- บทวิเคราะห์รายบุคคล ---- */
  @@ .ins { border: 1px solid #dbe2ef; border-left-width: 5px; border-radius: 8px;
         padding: 9px 13px; margin-bottom: 8px; font-size: 14.5pt; line-height: 1.6;
         page-break-inside: avoid; }
  @@ .ins h4 { margin: 0 0 4px; font-size: 15.5pt; font-weight: 700; }
  @@ .ins ul { margin: 6px 0 0; padding-left: 22px; }
  @@ .ins li { margin-bottom: 3px; }
  @@ .ins.good { border-color: #cbd5e1; border-left-color: #10b981; background: #f0fdf4; }
  @@ .ins.warn { border-color: #cbd5e1; border-left-color: #f59e0b; background: #fffbeb; }
  @@ .ins.info { border-color: #cbd5e1; border-left-color: #3b82f6; background: #f8fafc; }
  @@ .ins.good h4 { color: #047857; }
  @@ .ins.warn h4 { color: #b45309; }
  @@ .ins.info h4 { color: #1e40af; }

  /* ---- เรียงความฉบับเต็ม ---- */
  @@ .essay-doc { border: 1px solid #dbe2ef; border-radius: 8px; padding: 10px 14px; margin-bottom: 10px;
               page-break-inside: auto; }
  @@ .essay-doc > .head { display: flex; justify-content: space-between; gap: 10px; flex-wrap: wrap;
                       border-bottom: 1px dashed #cbd5e1; padding-bottom: 5px; margin-bottom: 8px; }
  @@ .essay-doc > .head b { font-size: 15.5pt; font-weight: 700; }
  @@ .essay-doc .meta { font-size: 13.5pt; font-weight: 400; color: #64748b; }
  @@ .essay-doc .topic { font-size: 14pt; font-weight: 400; color: #475569; margin-bottom: 6px; }
  @@ .essay-doc .part { font-size: 14pt; font-weight: 700; color: #1e3a8a; margin-top: 9px; margin-bottom: 2px; }
  /* ตัวเรียงความของนักเรียนคือส่วนที่อ่านต่อเนื่องยาวที่สุด จึงให้ระยะบรรทัดโปร่งที่สุดในเอกสาร */
  @@ .essay-doc p { margin: 0 0 5px; text-indent: 2.2em; line-height: 1.75; font-weight: 400; }

  /* ---- คู่ ป้ายกำกับ/ค่า และป้ายสถานะสั้น ๆ ---- */
  /* บรรทัด "ป้ายกำกับ: ค่า" — ป้ายเป็นตัวหนา ส่วนค่าเป็นตัวปกติ อ่านไล่ลงมาได้สบาย */
  @@ .kv { font-size: 14.5pt; font-weight: 400; line-height: 1.6; margin-bottom: 2px; }
  @@ .kv b { color: #0f172a; font-weight: 700; }
  @@ .chips { display: flex; flex-wrap: wrap; gap: 5px; }

  @@ .signrow { display: flex; gap: 34px; margin-top: 30px; page-break-inside: avoid; }
  @@ .signrow .sign { flex: 1; text-align: center; font-size: 15pt; color: #475569; }
  @@ .signrow .line { border-bottom: 1px dotted #64748b; height: 30px; margin-bottom: 3px; }

  @@ .foot-note { margin-top: 18px; font-size: 13.5pt; font-weight: 400; color: #64748b; line-height: 1.6;
               border-top: 1px solid #e2e8f0; padding-top: 8px; }
  @@ .no-data { text-align: center; color: #64748b; padding: 40px; font-size: 16pt; }
  @@ .page-break { page-break-after: always; }

  @media print {
    /* บังคับให้เครื่องพิมพ์พิมพ์พื้นหลังและเส้นสีตามที่ออกแบบไว้
       ถ้าไม่ใส่ เบราว์เซอร์จะตัดพื้นหลังทิ้ง เอกสารจะกลายเป็นตัวหนังสือดำล้วนแบนราบ อ่านยาก */
    @@, @@ * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    @@ { background: #fff; }
    @@ .no-print { display: none !important; }
    @@ .sheet { box-shadow: none; margin: 0; max-width: none; padding: 0; }
    @@ .sheet + .sheet { page-break-before: always; }
    @@ table, @@ .box, @@ .signrow, @@ .cards, @@ .ins { page-break-inside: avoid; }
    @@ tr { page-break-inside: avoid; }
    @@ thead { display: table-header-group; }   /* ตารางยาวข้ามหน้า ให้หัวตารางซ้ำทุกหน้า */
    /* หัวข้อต้องไม่ตกค้างอยู่ท้ายหน้าโดยไม่มีเนื้อหาตามมา และห้ามทิ้งบรรทัดเดี่ยวข้ามหน้า */
    @@ h2.sec-title, @@ h3.sub-title { break-after: avoid; page-break-after: avoid; }
    @@ h2.sec-title { margin-top: 15px; }
    @@ p, @@ li, @@ .kv { orphans: 2; widows: 2; }
    @page { size: A4 portrait; margin: 12mm 12mm 14mm; }
  }

  /* จอแคบ (ดูบนมือถือก่อนสั่งพิมพ์) — ให้ตารางเลื่อนแนวนอนได้แทนที่จะบีบตัวอักษร */
  @media screen and (max-width: 820px) {
    @@ .sheet { padding: 18px 16px; }
    @@ .twocol, @@ .checklist { grid-template-columns: 1fr; }
    @@ h2.sec-title { margin-top: 20px; }
  }
RPCSS;
    $css = str_replace('FONTFAMILY', $font, $css);
    echo "<style>\n" . str_replace('@@', $root, $css) . "\n</style>";
}
