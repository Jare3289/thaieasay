<?php
/**
 * teacher_scores.php — "ตารางคะแนนที่ครูเป็นผู้ประเมิน" (ใช้ตัดสินผลการเรียน)
 * ---------------------------------------------------------------------------
 * หน้านี้แสดงเฉพาะคะแนนที่ "ครูเป็นผู้ประเมิน" เท่านั้น
 * คะแนนประเมินตนเอง คะแนนจากเพื่อน และคะแนนของผู้เชี่ยวชาญไม่ถูกนำมารวมแม้แต่ค่าเดียว
 *
 * ประกอบด้วย
 *   0) รายการงานที่ครูยังไม่ได้ตรวจ พร้อมลิงก์กดไปตรวจได้ทันที (ขึ้นก่อนทุกตาราง)
 *   1) ตารางคะแนนครูรายบุคคล 4 รอบ พร้อมค่าเฉลี่ยและส่วนเบี่ยงเบนมาตรฐานท้ายตาราง
 *   2) สถิติเปรียบเทียบแบบจับคู่ 2 คู่ (ก่อนเรียน↔หลังเรียน · หน่วยที่ 1↔หน่วยที่ 2) แยกกันคนละชุด
 *   3) ตารางคะแนนที่ระบบตรวจ รายบุคคล 4 รอบ พร้อม M, SD
 *   4) สถิติเปรียบเทียบแบบจับคู่ของคะแนนอัตโนมัติ
 *   5) เทียบคะแนนอัตโนมัติกับคะแนนครูรายรอบบนสเกลเดียวกัน
 *
 * ตัวเลขทุกตัวคำนวณด้วยฟังก์ชันสถิติชุดเดียวกับบทที่ 4-5 (chapter45_stats.php)
 * พารามิเตอร์ (GET): group (กลุ่มการวิจัย · 'all' = ทุกกลุ่ม), classroom (ห้องเรียน), export=csv
 */
$page_title = 'ตารางคะแนนครูประเมิน - ระบบประเมินเรียงความ';
require_once 'auth_helper.php';
require_login('teacher');
require_once 'teacher_scores_data.php';

// ---- ขอบเขตข้อมูล ----
$tsGroupRaw = isset($_GET['group']) ? trim((string)$_GET['group']) : '';
$tsGroup    = ($tsGroupRaw === 'all') ? '' : $tsGroupRaw;
$tsRoom     = isset($_GET['classroom']) ? trim((string)$_GET['classroom']) : '';

$tsRep    = ts_report($pdo, ['group' => $tsGroup, 'classroom' => $tsRoom]);
$tsRounds = $tsRep['rounds'];
$tsPairs  = $tsRep['pair_defs'];
$tsMax    = $tsRep['meta']['full_max'];
$tsAiMax  = $tsRep['meta']['ai_max'];

$tsScopeParts = [];
$tsScopeParts[] = ($tsGroup === '') ? 'ทุกกลุ่มการวิจัย' : $tsGroup;
if ($tsRoom !== '') $tsScopeParts[] = 'ห้อง ' . $tsRoom;
$tsScope = implode(' · ', $tsScopeParts);

/* ---------------------------------------------------------------------------
 * ส่งออก CSV (UTF-8 BOM เพื่อให้ Excel อ่านภาษาไทยได้ถูกต้อง)
 * ต้องทำก่อน header.php เพราะยังไม่มีการส่งเนื้อหาหน้าเว็บออกไป
 * ------------------------------------------------------------------------- */
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // กันสูตรถูกรันเมื่อเปิดใน Excel (CSV formula injection)
    // ยกเว้นค่าที่เป็น "ตัวเลขล้วน" เช่น -0.91 ซึ่งไม่ใช่สูตร ถ้าเติมเครื่องหมายนำหน้าจะกลายเป็นข้อความ
    // แล้วนำไปคำนวณต่อใน Excel ไม่ได้ ทั้งที่ตารางนี้เป็นตารางคะแนนล้วน ๆ
    $cell = function ($v) {
        if (is_int($v) || is_float($v)) return $v;
        $s = (string)$v;
        if ($s === '' || preg_match('/^-?\d+(\.\d+)?$/', $s)) return $s;
        if (preg_match('/^[=\-+@\t\r]/', $s)) $s = "'" . $s;
        return $s;
    };
    $fname = preg_replace('/[\\\\\/:*?"<>|]+/u', '_',
        'คะแนนครูประเมิน_' . $tsScope . '_' . date('Y-m-d') . '.csv');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $fname . '"; filename*=UTF-8\'\'' . rawurlencode($fname));
    header('Cache-Control: no-store, no-cache, must-revalidate');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");

    // ระบุตัวคั่น/ตัวครอบ/ตัวหนีให้ครบ — PHP 8.4 ขึ้นไปเตือนถ้าไม่ส่ง $escape มาเอง
    $w = function ($row) use ($out, $cell) { fputcsv($out, array_map($cell, $row), ',', '"', "\\"); };

    $w(['ตารางคะแนนที่ครูเป็นผู้ประเมินเท่านั้น (ไม่รวมคะแนนประเมินตนเอง/เพื่อน/ผู้เชี่ยวชาญ)']);
    $w(['ขอบเขต', $tsScope, 'จำนวนนักเรียน', $tsRep['meta']['n_students'],
        'คะแนนเต็ม', $tsMax, 'ออกรายงานเมื่อ', $tsRep['meta']['generated_at']]);
    $w([]);

    // --- ตารางที่ 1: คะแนนครูรายบุคคล ---
    $w(['ตารางที่ 1  คะแนนที่ครูเป็นผู้ประเมินรายบุคคล (เต็ม ' . $tsMax . ' คะแนน)']);
    $head = ['ลำดับ', 'รหัสนักเรียน', 'ชื่อ-สกุล', 'ห้อง'];
    foreach ($tsRounds as $r) $head[] = $r['label'];
    $head[] = 'ตรวจแล้ว (จาก 4 รอบ)';
    $w($head);
    foreach ($tsRep['students'] as $s) {
        $row = [$s['no'], $s['id'], $s['name'], $s['classroom']];
        foreach ($tsRounds as $rk => $r) {
            $t = $s['teacher'][$rk];
            if ($t && $t['total'] !== null)       $row[] = number_format($t['total'], 2);
            elseif (!empty($s['essay'][$rk]))     $row[] = 'ยังไม่ตรวจ';
            else                                  $row[] = 'ยังไม่ส่งงาน';
        }
        $row[] = $s['teacher_done'];
        $w($row);
    }
    foreach ([['n (คนที่ตรวจแล้ว)', 'n'], ['ค่าเฉลี่ย (M)', 'mean'], ['ส่วนเบี่ยงเบนมาตรฐาน (SD)', 'sd'],
              ['ต่ำสุด', 'min'], ['สูงสุด', 'max']] as $stat) {
        $row = ['', '', $stat[0], ''];
        foreach ($tsRounds as $rk => $r) {
            $v = $tsRep['columns'][$rk][$stat[1]];
            $row[] = ($stat[1] === 'n') ? $v : (($v === null) ? '—' : number_format($v, 2));
        }
        $row[] = '';
        $w($row);
    }
    $w([]);

    // --- ตารางที่ 2: สถิติจับคู่ของคะแนนครู ---
    $w(['ตารางที่ 2  ผลการเปรียบเทียบแบบจับคู่ จากคะแนนครูประเมินอย่างเดียว (คิดแยกกันคนละคู่)']);
    $w(['คู่เทียบ', 'รายการ', 'คะแนนเต็ม', 'n', 'M รอบแรก', 'SD รอบแรก', 'M รอบหลัง', 'SD รอบหลัง',
        'ผลต่างเฉลี่ย', 'SD ผลต่าง', 't', 'df', 'p', "Cohen's dz", 'ขนาดอิทธิพล']);
    foreach ($tsRep['pairs'] as $pk => $p) {
        foreach ($p['rows'] as $row) {
            $w([$p['label'], $row['label'], $row['max'], $row['n'],
                ts_num($row['a_mean']), ts_num($row['a_sd']),
                ts_num($row['b_mean']), ts_num($row['b_sd']),
                ts_num($row['diff']), ts_num($row['sd_diff']),
                ts_num($row['t'], 3), $row['df'], ts_p($row['p']),
                ts_num($row['dz'], 3), $row['effect']]);
        }
    }
    $w([]);

    // --- ตารางที่ 3: คะแนนอัตโนมัติรายบุคคล ---
    $w(['ตารางที่ 3  คะแนนที่ระบบตรวจรายบุคคล (เต็ม ' . $tsAiMax . ' คะแนน — เฉพาะข้อที่ระบบตรวจได้)']);
    $head = ['ลำดับ', 'รหัสนักเรียน', 'ชื่อ-สกุล', 'ห้อง'];
    foreach ($tsRounds as $r) $head[] = $r['label'];
    $w($head);
    foreach ($tsRep['students'] as $s) {
        $row = [$s['no'], $s['id'], $s['name'], $s['classroom']];
        foreach ($tsRounds as $rk => $r) {
            $a = $s['ai'][$rk];
            if ($a)                            $row[] = number_format($a['total'], 2);
            elseif (!empty($s['essay'][$rk]))  $row[] = 'ระบบยังไม่ตรวจ';
            else                               $row[] = 'ยังไม่ส่งงาน';
        }
        $w($row);
    }
    foreach ([['n (ฉบับที่ระบบตรวจแล้ว)', 'n'], ['ค่าเฉลี่ย (M)', 'mean'], ['ส่วนเบี่ยงเบนมาตรฐาน (SD)', 'sd']] as $stat) {
        $row = ['', '', $stat[0], ''];
        foreach ($tsRounds as $rk => $r) {
            $v = $tsRep['ai_columns'][$rk][$stat[1]];
            $row[] = ($stat[1] === 'n') ? $v : (($v === null) ? '—' : number_format($v, 2));
        }
        $w($row);
    }
    $w([]);

    // --- ตารางที่ 4: สถิติจับคู่ของคะแนนอัตโนมัติ ---
    $w(['ตารางที่ 4  ผลการเปรียบเทียบแบบจับคู่ จากคะแนนที่ระบบตรวจ (เต็ม ' . $tsAiMax . ')']);
    $w(['คู่เทียบ', 'n', 'M รอบแรก', 'SD รอบแรก', 'M รอบหลัง', 'SD รอบหลัง',
        'ผลต่างเฉลี่ย', 'SD ผลต่าง', 't', 'df', 'p', "Cohen's dz"]);
    foreach ($tsRep['ai_pairs'] as $pk => $p) {
        $row = $p['row'];
        $w([$p['label'], $row['n'], ts_num($row['a_mean']), ts_num($row['a_sd']),
            ts_num($row['b_mean']), ts_num($row['b_sd']), ts_num($row['diff']), ts_num($row['sd_diff']),
            ts_num($row['t'], 3), $row['df'], ts_p($row['p']), ts_num($row['dz'], 3)]);
    }
    $w([]);

    // --- ตารางที่ 5: เทียบระบบกับครู ---
    $w(['ตารางที่ 5  เทียบคะแนนอัตโนมัติกับคะแนนครู รายรอบ บนสเกลเดียวกัน (เฉพาะข้อที่ระบบตรวจได้ เต็ม ' . $tsAiMax . ')']);
    $w(['รอบ', 'n', 'M ครู', 'SD ครู', 'M ระบบ', 'SD ระบบ', 'ผลต่าง (ระบบ − ครู)', 't', 'df', 'p', 'Pearson r']);
    foreach ($tsRep['agreement'] as $rk => $row) {
        $w([$tsRounds[$rk]['label'], $row['n'], ts_num($row['a_mean']), ts_num($row['a_sd']),
            ts_num($row['b_mean']), ts_num($row['b_sd']), ts_num($row['diff']),
            ts_num($row['t'], 3), $row['df'], ts_p($row['p']), ts_num($row['r']['r'] ?? null, 3)]);
    }
    $w([]);

    // --- รายการที่ยังไม่ได้ตรวจ ---
    $w(['รายการที่ครูยังไม่ได้ตรวจ']);
    $w(['สถานะ', 'รหัสนักเรียน', 'ชื่อ-สกุล', 'ห้อง', 'รอบ']);
    foreach ($tsRep['pending']['teacher']['ready'] as $it) {
        $w(['ส่งงานแล้ว รอครูตรวจ', $it['id'], $it['name'], $it['classroom'], $it['round_label']]);
    }
    foreach ($tsRep['pending']['teacher']['no_work'] as $it) {
        $w(['ยังไม่ส่งงาน จึงยังตรวจไม่ได้', $it['id'], $it['name'], $it['classroom'], $it['round_label']]);
    }
    fclose($out);
    exit;
}

require_once 'header.php';

/** ลิงก์ไปให้คะแนนนักเรียนคนนั้นในรอบนั้นทันที */
function ts_grade_link($sid, $round) {
    return 'evaluation.php?mode=teacher&phase=' . rawurlencode($round) . '&student=' . rawurlencode($sid);
}
$tsQuery = function ($extra = []) use ($tsGroupRaw, $tsRoom) {
    $q = array_merge(['group' => ($tsGroupRaw === '' ? 'all' : $tsGroupRaw), 'classroom' => $tsRoom], $extra);
    return 'teacher_scores.php?' . http_build_query($q);
};
$tsPend = $tsRep['pending'];
?>

<style>
  .ts-table th, .ts-table td { white-space: nowrap; text-align: center; font-size: 0.86rem; vertical-align: middle; }
  .ts-table thead th {
    background: var(--light-slate); color: var(--primary-navy); font-weight: 700;
    border-bottom: 2px solid var(--border-gray); vertical-align: middle;
  }
  .ts-table thead .grp-pre  { background: #ecfeff; color: #0e7490; }
  .ts-table thead .grp-unit { background: #eff6ff; color: #1d4ed8; }
  .ts-table thead .grp-post { background: #f5f3ff; color: #6d28d9; }
  .ts-table tbody td { border-bottom: 1px solid var(--border-gray); }
  .ts-table tbody tr:hover td { background: var(--light-blue); }
  .ts-table .ts-name { text-align: left; font-weight: 600; color: var(--primary-navy); }
  .ts-table .ts-id   { font-family: monospace; color: #64748b; font-weight: 600; }
  .ts-table tfoot td { background: #f8fafc; font-weight: 700; border-top: 2px solid var(--border-gray); }
  .ts-table tfoot .ts-stat-label { text-align: right; color: #475569; }
  .ts-score { font-family: monospace; font-weight: 700; color: #0f172a; }
  .ts-none  { color: #dc2626; font-weight: 600; }
  .ts-nowork{ color: #94a3b8; }
  .ts-sig   { color: #16a34a; font-weight: 700; }
  .ts-nosig { color: #b45309; font-weight: 700; }
  .ts-pair-head { background: linear-gradient(135deg, #0f766e 0%, #155e75 100%); color: #fff; }
  .ts-pair-head-2 { background: linear-gradient(135deg, #4338ca 0%, #6d28d9 100%); color: #fff; }
  .ts-legend { font-size: 0.8rem; color: #64748b; }
  .print-only { display: none; }

  @media print {
    @page { size: A4 landscape; margin: 8mm; }
    .app-sidebar, .app-topbar, .sidebar-backdrop, .no-print { display: none !important; }
    .app-shell { display: block !important; padding: 0 !important; }
    .app-main { gap: 0 !important; }
    .app-content { padding: 0 !important; }
    body { background: #fff !important; }
    .print-only { display: block; }
    .card { box-shadow: none !important; border: 1px solid #cbd5e1 !important; break-inside: avoid; }
    .table-responsive { overflow: visible !important; }
    .ts-table { width: 100% !important; }
    .ts-table th, .ts-table td { font-size: 8pt !important; padding: 2px 4px !important; }
    .ts-table thead th, .ts-pair-head, .ts-pair-head-2 {
      -webkit-print-color-adjust: exact; print-color-adjust: exact;
    }
  }
</style>

<div id="view-teacher-scores" class="text-start">
  <div class="mb-3 no-print">
    <a href="index.php" class="btn btn-link text-decoration-none text-secondary fw-bold p-0">
      <i class="bi bi-arrow-left-short"></i> กลับหน้าเมนูหลัก
    </a>
  </div>

  <!-- ===================== หัวเรื่อง ===================== -->
  <div class="card border-0 shadow-lg rounded-4 overflow-hidden mb-4">
    <div class="p-4 text-white" style="background: linear-gradient(135deg, #0f766e 0%, #1d4ed8 60%, #6d28d9 100%);">
      <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
        <div>
          <h4 class="fw-bold mb-1"><i class="bi bi-clipboard2-check me-2"></i>ตารางคะแนนที่ครูเป็นผู้ประเมิน</h4>
          <p class="text-white-50 mb-0 small" style="max-width: 760px;">
            การตัดสินผลใช้ <strong class="text-white">คะแนนที่ครูเป็นผู้ประเมินเท่านั้น</strong> —
            คะแนนประเมินตนเอง คะแนนจากเพื่อน และคะแนนของผู้เชี่ยวชาญ เป็นข้อมูลประกอบ
            จึงไม่ถูกนำมารวมในทุกตารางของหน้านี้
          </p>
        </div>
        <div class="text-end">
          <span class="badge bg-white text-dark px-3 py-2 fw-bold d-block mb-2">
            <i class="bi bi-people-fill me-1"></i><?php echo htmlspecialchars($tsScope); ?>
            · <?php echo (int)$tsRep['meta']['n_students']; ?> คน
          </span>
          <div class="d-flex gap-2 justify-content-end no-print">
            <a href="<?php echo htmlspecialchars($tsQuery(['export' => 'csv'])); ?>"
               class="btn btn-light btn-sm fw-bold rounded-pill px-3">
              <i class="bi bi-filetype-csv me-1"></i>ส่งออก CSV
            </a>
            <button type="button" onclick="window.print()" class="btn btn-outline-light btn-sm fw-bold rounded-pill px-3">
              <i class="bi bi-printer me-1"></i>พิมพ์
            </button>
          </div>
        </div>
      </div>
    </div>
    <div class="bg-light border-top px-4 py-2 small text-muted">
      <i class="bi bi-diagram-3 me-1"></i>
      <strong>คู่ที่จับกันไว้:</strong>
      ก่อนเรียน ↔ หลังเรียน &nbsp;·&nbsp; หน่วยที่ 1 (ร่างที่ 2) ↔ หน่วยที่ 2 (ร่างที่ 2)
      — <strong>สองคู่นี้คิดสถิติแยกกันคนละชุด</strong> แต่ละคู่ใช้เฉพาะนักเรียนที่มีคะแนนครบทั้งสองรอบของคู่นั้น
      ค่า n ของแต่ละคู่จึงอาจไม่เท่ากัน และแสดงกำกับไว้ทุกตาราง
      &nbsp;·&nbsp; ภาระงานให้คะแนนเฉพาะ<strong>ร่างที่ 2</strong> ของแต่ละหน่วย
    </div>
  </div>

  <!-- ตัวกรองห้องเรียน -->
  <div class="card border-0 shadow-sm rounded-4 mb-4 no-print">
    <div class="card-body p-3 d-flex flex-wrap align-items-center gap-2">
      <span class="fw-bold small text-muted"><i class="bi bi-funnel me-1"></i>ขอบเขตข้อมูล</span>
      <span class="badge bg-secondary-subtle text-secondary-emphasis border">
        กลุ่มการวิจัย: <?php echo htmlspecialchars($tsGroup === '' ? 'ทุกกลุ่ม' : $tsGroup); ?>
        <span class="text-muted">(เลือกที่ปุ่มกลุ่มบนแถบด้านบน)</span>
      </span>
      <div class="ms-auto d-flex align-items-center gap-2">
        <label for="tsRoomSelect" class="small fw-semibold text-muted mb-0">ห้องเรียน</label>
        <select id="tsRoomSelect" class="form-select form-select-sm" style="width: auto;"
                onchange="tsChangeRoom(this.value)">
          <option value="" <?php echo ($tsRoom === '') ? 'selected' : ''; ?>>ทุกห้อง</option>
          <?php foreach (ts_classrooms($pdo) as $room): ?>
            <option value="<?php echo htmlspecialchars($room); ?>" <?php echo ($tsRoom === $room) ? 'selected' : ''; ?>>
              ห้อง <?php echo htmlspecialchars($room); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </div>

  <div class="print-only mb-3">
    <div class="fw-bold">ตารางคะแนนที่ครูเป็นผู้ประเมิน — <?php echo htmlspecialchars($tsScope); ?>
      (<?php echo (int)$tsRep['meta']['n_students']; ?> คน)</div>
    <div class="small text-muted">ออกรายงานเมื่อ <?php echo htmlspecialchars($tsRep['meta']['generated_at']); ?>
      · ใช้คะแนนของครูผู้สอนอย่างเดียว</div>
  </div>

  <!-- ===================== 0) งานที่ครูยังไม่ได้ตรวจ ===================== -->
  <?php
    $tsReady  = $tsPend['teacher']['ready'];
    $tsNoWork = $tsPend['teacher']['no_work'];
  ?>
  <div class="card border-0 shadow-sm rounded-4 mb-4"
       style="border-top:4px solid <?php echo $tsReady ? '#dc2626' : '#16a34a'; ?> !important;">
    <div class="card-body p-4">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h5 class="fw-bold mb-0">
          <i class="bi bi-<?php echo $tsReady ? 'exclamation-triangle-fill text-danger' : 'check-circle-fill text-success'; ?> me-2"></i>
          งานที่ครูยังไม่ได้ตรวจ
        </h5>
        <div class="d-flex gap-2 flex-wrap">
          <span class="badge <?php echo $tsReady ? 'bg-danger' : 'bg-success'; ?> px-3 py-2">
            รอตรวจ <?php echo count($tsReady); ?> ชิ้น
          </span>
          <span class="badge bg-secondary px-3 py-2">ยังไม่ส่งงาน <?php echo count($tsNoWork); ?> ชิ้น</span>
        </div>
      </div>

      <!-- สรุปรายรอบ -->
      <div class="row g-2 mb-3">
        <?php foreach ($tsRounds as $rk => $r):
          $b = $tsPend['by_round'][$rk];
          $tot = (int)$tsRep['meta']['n_students'];
        ?>
          <div class="col-md-3 col-6">
            <div class="p-3 rounded-3 bg-light h-100">
              <div class="small fw-bold text-dark mb-1"><?php echo htmlspecialchars($r['label']); ?></div>
              <div class="fs-5 fw-bold <?php echo $b['teacher_ready'] ? 'text-danger' : 'text-success'; ?>">
                ตรวจแล้ว <?php echo $b['teacher_done']; ?>/<?php echo $tot; ?>
              </div>
              <div class="small text-muted">
                <?php if ($b['teacher_ready']): ?>
                  <span class="text-danger fw-semibold">รอตรวจ <?php echo $b['teacher_ready']; ?></span> ·
                <?php endif; ?>
                ยังไม่ส่ง <?php echo $b['teacher_no_work']; ?>
                <?php if ($b['ai_ready']): ?> · ระบบค้าง <?php echo $b['ai_ready']; ?><?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if ($tsReady): ?>
        <div class="alert alert-danger border-0 rounded-3 small mb-3">
          <i class="bi bi-pencil-square me-1"></i>
          <strong>นักเรียนส่งงานแล้วแต่ยังไม่มีคะแนนของครู <?php echo count($tsReady); ?> ชิ้น</strong> —
          กดที่รายการเพื่อไปให้คะแนนนักเรียนคนนั้นในรอบนั้นได้ทันที
          ตราบใดที่ยังตรวจไม่ครบ ค่าเฉลี่ยและค่า n ในตารางด้านล่างยังไม่ใช่ตัวเลขสุดท้าย
        </div>
        <div class="table-responsive" style="max-height: 340px; overflow-y: auto;">
          <table class="table table-sm table-hover align-middle mb-0 ts-table">
            <thead class="sticky-top">
              <tr>
                <th class="text-start">รหัส</th><th class="text-start">ชื่อ-สกุล</th>
                <th>ห้อง</th><th>รอบที่ต้องตรวจ</th><th class="no-print">ไปตรวจ</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($tsReady as $it): ?>
                <tr>
                  <td class="ts-id text-start"><?php echo htmlspecialchars($it['id']); ?></td>
                  <td class="ts-name"><?php echo htmlspecialchars($it['name']); ?></td>
                  <td><?php echo htmlspecialchars($it['classroom']); ?></td>
                  <td><span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle">
                    <?php echo htmlspecialchars($it['round_label']); ?></span></td>
                  <td class="no-print">
                    <a href="<?php echo htmlspecialchars(ts_grade_link($it['id'], $it['round'])); ?>"
                       class="btn btn-sm btn-danger rounded-pill px-3 fw-bold">
                      <i class="bi bi-pencil-fill me-1"></i>ให้คะแนน
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="alert alert-success border-0 rounded-3 small mb-3">
          <i class="bi bi-check-circle-fill me-1"></i>
          <strong>ครูตรวจครบทุกชิ้นที่นักเรียนส่งมาแล้ว</strong> —
          ตัวเลขทุกตารางด้านล่างจึงเป็นคะแนนชุดสมบูรณ์เท่าที่มีงานส่ง
        </div>
      <?php endif; ?>

      <?php if ($tsNoWork): ?>
        <details class="mt-2">
          <summary class="fw-semibold small text-muted" style="cursor:pointer;">
            ยังไม่ส่งงาน จึงยังตรวจไม่ได้ <?php echo count($tsNoWork); ?> ชิ้น (กดเพื่อดูรายชื่อ)
          </summary>
          <div class="table-responsive mt-2" style="max-height: 300px; overflow-y: auto;">
            <table class="table table-sm align-middle mb-0 ts-table">
              <thead><tr><th class="text-start">รหัส</th><th class="text-start">ชื่อ-สกุล</th><th>ห้อง</th><th>รอบ</th></tr></thead>
              <tbody>
                <?php foreach ($tsNoWork as $it): ?>
                  <tr>
                    <td class="ts-id text-start"><?php echo htmlspecialchars($it['id']); ?></td>
                    <td class="ts-name"><?php echo htmlspecialchars($it['name']); ?></td>
                    <td><?php echo htmlspecialchars($it['classroom']); ?></td>
                    <td class="ts-nowork"><?php echo htmlspecialchars($it['round_label']); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </details>
      <?php endif; ?>

      <?php if ($tsPend['count']['ai_ready']): ?>
        <details class="mt-2">
          <summary class="fw-semibold small text-muted" style="cursor:pointer;">
            งานที่ส่งแล้วแต่ระบบยังไม่ได้ตรวจ <?php echo $tsPend['count']['ai_ready']; ?> ชิ้น
            (กดเพื่อดูรายชื่อ · สั่งตรวจได้ที่หน้า <a href="writing_feedback.php">ระบบตรวจงานเขียนอัตโนมัติ</a>)
          </summary>
          <div class="table-responsive mt-2" style="max-height: 300px; overflow-y: auto;">
            <table class="table table-sm align-middle mb-0 ts-table">
              <thead><tr><th class="text-start">รหัส</th><th class="text-start">ชื่อ-สกุล</th><th>ห้อง</th><th>รอบ</th></tr></thead>
              <tbody>
                <?php foreach ($tsPend['ai']['ready'] as $it): ?>
                  <tr>
                    <td class="ts-id text-start"><?php echo htmlspecialchars($it['id']); ?></td>
                    <td class="ts-name"><?php echo htmlspecialchars($it['name']); ?></td>
                    <td><?php echo htmlspecialchars($it['classroom']); ?></td>
                    <td class="ts-nowork"><?php echo htmlspecialchars($it['round_label']); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </details>
      <?php endif; ?>
    </div>
  </div>

  <!-- ===================== 1) ตารางคะแนนครูรายบุคคล ===================== -->
  <div class="card border-0 shadow-sm rounded-4 mb-4" style="border-top:4px solid #0f766e !important;">
    <div class="card-body p-4">
      <h5 class="fw-bold mb-1"><i class="bi bi-table me-2 text-success"></i>ตารางที่ 1
        คะแนนที่ครูเป็นผู้ประเมินรายบุคคล</h5>
      <p class="text-muted small mb-3">
        คะแนนเต็ม <?php echo (int)$tsMax; ?> คะแนน ทั้ง 4 รอบ ·
        ช่องที่ขึ้นว่า <span class="ts-none">ยังไม่ตรวจ</span> คือมีเรียงความส่งมาแล้วแต่ครูยังไม่ได้ให้คะแนน
        (กดที่ช่องเพื่อไปให้คะแนน) · <span class="ts-nowork">ยังไม่ส่งงาน</span> คือนักเรียนยังไม่ส่งเรียงความรอบนั้น
      </p>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 ts-table">
          <thead>
            <tr>
              <th class="text-start">ที่</th>
              <th class="text-start">รหัส</th>
              <th class="text-start">ชื่อ-สกุล</th>
              <th class="grp-pre">ก่อนเรียน</th>
              <th class="grp-unit">หน่วยที่ 1<br><span class="fw-normal small">(ร่างที่ 2)</span></th>
              <th class="grp-unit">หน่วยที่ 2<br><span class="fw-normal small">(ร่างที่ 2)</span></th>
              <th class="grp-post">หลังเรียน</th>
              <th>ผลต่าง<br><span class="fw-normal small">หลัง − ก่อน</span></th>
              <th>ผลต่าง<br><span class="fw-normal small">หน่วย 2 − หน่วย 1</span></th>
              <th>ระดับคุณภาพ<br><span class="fw-normal small">(หลังเรียน)</span></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($tsRep['students'] as $s):
              $pre  = $s['teacher']['pretest'];
              $post = $s['teacher']['posttest'];
              $t1   = $s['teacher']['task1'];
              $t2   = $s['teacher']['task2'];
              $dPP  = ($pre && $post && $pre['total'] !== null && $post['total'] !== null)
                        ? $post['total'] - $pre['total'] : null;
              $dU   = ($t1 && $t2 && $t1['total'] !== null && $t2['total'] !== null)
                        ? $t2['total'] - $t1['total'] : null;
            ?>
              <tr>
                <td><?php echo (int)$s['no']; ?></td>
                <td class="ts-id text-start"><?php echo htmlspecialchars($s['id']); ?></td>
                <td class="ts-name"><?php echo htmlspecialchars($s['name']); ?></td>
                <?php foreach ($tsRounds as $rk => $r):
                  $t = $s['teacher'][$rk];
                ?>
                  <td>
                    <?php if ($t && $t['total'] !== null): ?>
                      <span class="ts-score"><?php echo number_format($t['total'], 2); ?></span>
                    <?php elseif (!empty($s['essay'][$rk])): ?>
                      <a href="<?php echo htmlspecialchars(ts_grade_link($s['id'], $rk)); ?>"
                         class="ts-none text-decoration-none" title="กดเพื่อไปให้คะแนนรอบนี้">
                        ยังไม่ตรวจ <i class="bi bi-box-arrow-up-right"></i>
                      </a>
                    <?php else: ?>
                      <span class="ts-nowork">ยังไม่ส่งงาน</span>
                    <?php endif; ?>
                  </td>
                <?php endforeach; ?>
                <td class="<?php echo ($dPP === null) ? 'ts-nowork' : (($dPP >= 0) ? 'text-success fw-bold' : 'text-danger fw-bold'); ?>">
                  <?php echo ($dPP === null) ? '—' : (($dPP >= 0 ? '+' : '') . number_format($dPP, 2)); ?>
                </td>
                <td class="<?php echo ($dU === null) ? 'ts-nowork' : (($dU >= 0) ? 'text-success fw-bold' : 'text-danger fw-bold'); ?>">
                  <?php echo ($dU === null) ? '—' : (($dU >= 0 ? '+' : '') . number_format($dU, 2)); ?>
                </td>
                <td>
                  <?php if ($post && $post['level'] !== ''): ?>
                    <span class="badge bg-primary-subtle text-primary-emphasis border border-primary-subtle">
                      <?php echo htmlspecialchars($post['level']); ?></span>
                  <?php else: ?><span class="ts-nowork">—</span><?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$tsRep['students']): ?>
              <tr><td colspan="10" class="text-center text-muted py-4">
                ไม่มีนักเรียนในขอบเขตที่เลือก — ลองเปลี่ยนกลุ่มการวิจัยหรือห้องเรียน
              </td></tr>
            <?php endif; ?>
          </tbody>
          <tfoot>
            <?php
              $tsStatRows = [
                ['n (คนที่ครูตรวจแล้ว)', 'n', 0],
                ['ค่าเฉลี่ย (M)',        'mean', 2],
                ['ส่วนเบี่ยงเบนมาตรฐาน (SD)', 'sd', 2],
                ['ต่ำสุด',               'min', 2],
                ['สูงสุด',               'max', 2],
              ];
              foreach ($tsStatRows as $sr):
            ?>
              <tr>
                <td colspan="3" class="ts-stat-label"><?php echo htmlspecialchars($sr[0]); ?></td>
                <?php foreach ($tsRounds as $rk => $r):
                  $v = $tsRep['columns'][$rk][$sr[1]];
                ?>
                  <td><?php echo ($sr[1] === 'n') ? (int)$v : ts_num($v, $sr[2]); ?></td>
                <?php endforeach; ?>
                <td colspan="3"></td>
              </tr>
            <?php endforeach; ?>
          </tfoot>
        </table>
      </div>
      <div class="ts-legend mt-2">
        <i class="bi bi-info-circle me-1"></i>
        ค่า M และ SD ในแถวท้ายตารางคิดจาก<strong>ทุกคนที่ครูตรวจแล้วในรอบนั้น</strong>
        (ยังไม่บังคับว่าต้องมีคะแนนครบคู่) จึงใช้อ่านภาพรวมของแต่ละรอบ
        ส่วนตัวเลขที่ใช้ทดสอบสมมติฐานให้ดูตารางที่ 2 ซึ่งใช้เฉพาะผู้ที่มีคะแนนครบทั้งคู่
      </div>
    </div>
  </div>

  <!-- ===================== 2) สถิติจับคู่ของคะแนนครู ===================== -->
  <?php $tsPairIdx = 0; foreach ($tsRep['pairs'] as $pk => $p): $tsPairIdx++; ?>
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
      <div class="px-4 py-3 <?php echo ($tsPairIdx === 1) ? 'ts-pair-head' : 'ts-pair-head-2'; ?>">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div>
            <h5 class="fw-bold mb-1 text-white">
              <i class="bi bi-graph-up-arrow me-2"></i>ตารางที่ 2.<?php echo $tsPairIdx; ?>
              <?php echo htmlspecialchars($p['label']); ?>
            </h5>
            <div class="small text-white-50"><?php echo htmlspecialchars($p['note']); ?></div>
          </div>
          <span class="badge bg-white text-dark px-3 py-2 fw-bold">n = <?php echo (int)$p['n']; ?> คน</span>
        </div>
      </div>
      <div class="card-body p-4">
        <?php if ($p['n'] < 2): ?>
          <div class="alert alert-warning border-0 rounded-3 small mb-0">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            ยังคำนวณสถิติของคู่นี้ไม่ได้ — ต้องมีนักเรียนที่ครูตรวจ<strong>ครบทั้งสองรอบ</strong>
            (<?php echo htmlspecialchars($tsRounds[$p['a']]['label']); ?> และ
             <?php echo htmlspecialchars($tsRounds[$p['b']]['label']); ?>)
            อย่างน้อย 2 คน ตอนนี้มี <?php echo (int)$p['n']; ?> คน
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 ts-table">
              <thead>
                <tr>
                  <th rowspan="2" class="text-start align-middle">รายการ</th>
                  <th rowspan="2" class="align-middle">คะแนนเต็ม</th>
                  <th rowspan="2" class="align-middle">n</th>
                  <th colspan="2" class="grp-pre"><?php echo htmlspecialchars($tsRounds[$p['a']]['label']); ?></th>
                  <th colspan="2" class="grp-post"><?php echo htmlspecialchars($tsRounds[$p['b']]['label']); ?></th>
                  <th rowspan="2" class="align-middle">ผลต่างเฉลี่ย</th>
                  <th rowspan="2" class="align-middle">SD ผลต่าง</th>
                  <th rowspan="2" class="align-middle">t</th>
                  <th rowspan="2" class="align-middle">df</th>
                  <th rowspan="2" class="align-middle">p</th>
                  <th rowspan="2" class="align-middle">d<sub>z</sub></th>
                  <th rowspan="2" class="align-middle">ขนาดอิทธิพล</th>
                </tr>
                <tr>
                  <th class="grp-pre">M</th><th class="grp-pre">SD</th>
                  <th class="grp-post">M</th><th class="grp-post">SD</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($p['rows'] as $row): ?>
                  <tr<?php echo ($row['key'] === 'overall') ? ' class="table-light fw-bold"' : ''; ?>>
                    <td class="text-start fw-semibold"><?php echo htmlspecialchars($row['label']); ?></td>
                    <td><?php echo number_format($row['max'], 0); ?></td>
                    <td><?php echo (int)$row['n']; ?></td>
                    <td class="ts-score"><?php echo ts_num($row['a_mean']); ?></td>
                    <td><?php echo ts_num($row['a_sd']); ?></td>
                    <td class="ts-score"><?php echo ts_num($row['b_mean']); ?></td>
                    <td><?php echo ts_num($row['b_sd']); ?></td>
                    <td class="<?php echo ($row['diff'] !== null && $row['diff'] >= 0) ? 'text-success fw-bold' : 'text-danger fw-bold'; ?>">
                      <?php echo ($row['diff'] === null) ? '—'
                                : (($row['diff'] >= 0 ? '+' : '') . number_format($row['diff'], 2)); ?>
                    </td>
                    <td><?php echo ts_num($row['sd_diff']); ?></td>
                    <td><?php echo ts_num($row['t'], 3); ?></td>
                    <td><?php echo (int)$row['df']; ?></td>
                    <td class="<?php echo $row['sig'] ? 'ts-sig' : 'ts-nosig'; ?>"><?php echo ts_p_html($row['p']); ?></td>
                    <td><?php echo ts_num($row['dz'], 3); ?></td>
                    <td><?php echo htmlspecialchars($row['effect']); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <?php
            $ov  = $p['by_key']['overall'];
            $nor = $p['normality'];
          ?>
          <div class="mt-3 p-3 rounded-3 bg-light small">
            <div class="fw-bold mb-1"><i class="bi bi-file-earmark-text me-1"></i>อ่านผลภาพรวม</div>
            <p class="mb-2">
              นักเรียนที่ครูตรวจครบทั้งสองรอบของคู่นี้ n = <strong><?php echo (int)$ov['n']; ?></strong> คน
              คะแนนเฉลี่ย<?php echo htmlspecialchars($tsRounds[$p['a']]['label']); ?>
              เท่ากับ <strong><?php echo ts_num($ov['a_mean']); ?></strong>
              (SD = <?php echo ts_num($ov['a_sd']); ?>)
              และคะแนนเฉลี่ย<?php echo htmlspecialchars($tsRounds[$p['b']]['label']); ?>
              เท่ากับ <strong><?php echo ts_num($ov['b_mean']); ?></strong>
              (SD = <?php echo ts_num($ov['b_sd']); ?>)
              ต่างกันเฉลี่ย <strong><?php echo ($ov['diff'] === null) ? '—'
                  : (($ov['diff'] >= 0 ? '+' : '') . number_format($ov['diff'], 2)); ?></strong> คะแนน
              <?php if ($ov['t'] !== null): ?>
                — t(<?php echo (int)$ov['df']; ?>) = <?php echo ts_num($ov['t'], 3); ?>,
                p = <?php echo ts_p_html($ov['p']); ?>
                <?php if ($ov['sig']): ?>
                  <span class="ts-sig">แตกต่างกันอย่างมีนัยสำคัญทางสถิติที่ระดับ .05</span>
                <?php else: ?>
                  <span class="ts-nosig">ไม่แตกต่างกันอย่างมีนัยสำคัญทางสถิติที่ระดับ .05</span>
                <?php endif; ?>
              <?php endif; ?>
            </p>
            <div class="text-muted">
              <i class="bi bi-clipboard-check me-1"></i>
              <strong>การแจกแจงของคะแนนผลต่าง (Shapiro-Wilk):</strong>
              <?php if ($nor['W'] === null): ?>
                <?php echo htmlspecialchars($nor['error'] !== '' ? $nor['error'] : 'ข้อมูลไม่พอทดสอบ'); ?>
              <?php else: ?>
                W = <?php echo ts_num($nor['W'], 3); ?>, p = <?php echo ts_p_html($nor['p']); ?> —
                <?php if ($nor['normal']): ?>
                  ไม่ต่างจากการแจกแจงปกติ ใช้ paired t-test ได้ตามปกติ
                <?php else: ?>
                  <span class="text-danger fw-semibold">ต่างจากการแจกแจงปกติ</span>
                  ควรรายงานอย่างระมัดระวัง หรือเพิ่มการทดสอบไร้พารามิเตอร์ (Wilcoxon signed-rank) ประกอบ
                <?php endif; ?>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>

  <!-- ===================== 3) ตารางคะแนนอัตโนมัติรายบุคคล ===================== -->
  <div class="card border-0 shadow-sm rounded-4 mb-4" style="border-top:4px solid #7c3aed !important;">
    <div class="card-body p-4">
      <h5 class="fw-bold mb-1"><i class="bi bi-file-earmark-check me-2 text-primary"></i>ตารางที่ 3
        คะแนนที่ระบบตรวจรายบุคคล</h5>
      <p class="text-muted small mb-3">
        คะแนนเต็ม <?php echo (int)$tsAiMax; ?> คะแนน — ระบบตรวจได้ 10 ตัวบ่งชี้
        ส่วนข้อ 4.3 ความเรียบร้อย (เต็ม <?php echo (int)$tsRep['meta']['manual_max']; ?> คะแนน)
        ต้องดูจากต้นฉบับลายมือ ครูจึงเป็นผู้ให้เอง ระบบตรวจแทนไม่ได้ ·
        ตัวเลขที่แสดงคือคะแนนหลังครูปรับรายข้อแล้ว (ถ้ามีการปรับ) ·
        <strong>คะแนนชุดนี้เป็นข้อมูลประกอบ ไม่ใช้ตัดสินผลการเรียน</strong>
      </p>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 ts-table">
          <thead>
            <tr>
              <th class="text-start">ที่</th>
              <th class="text-start">รหัส</th>
              <th class="text-start">ชื่อ-สกุล</th>
              <th class="grp-pre">ก่อนเรียน</th>
              <th class="grp-unit">หน่วยที่ 1<br><span class="fw-normal small">(ร่างที่ 2)</span></th>
              <th class="grp-unit">หน่วยที่ 2<br><span class="fw-normal small">(ร่างที่ 2)</span></th>
              <th class="grp-post">หลังเรียน</th>
              <th>ผลต่าง<br><span class="fw-normal small">หลัง − ก่อน</span></th>
              <th>ผลต่าง<br><span class="fw-normal small">หน่วย 2 − หน่วย 1</span></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($tsRep['students'] as $s):
              $aPre = $s['ai']['pretest']; $aPost = $s['ai']['posttest'];
              $a1   = $s['ai']['task1'];   $a2    = $s['ai']['task2'];
              $adPP = ($aPre && $aPost) ? $aPost['total'] - $aPre['total'] : null;
              $adU  = ($a1 && $a2)      ? $a2['total'] - $a1['total'] : null;
            ?>
              <tr>
                <td><?php echo (int)$s['no']; ?></td>
                <td class="ts-id text-start"><?php echo htmlspecialchars($s['id']); ?></td>
                <td class="ts-name"><?php echo htmlspecialchars($s['name']); ?></td>
                <?php foreach ($tsRounds as $rk => $r): $a = $s['ai'][$rk]; ?>
                  <td>
                    <?php if ($a): ?>
                      <span class="ts-score"><?php echo number_format($a['total'], 2); ?></span>
                      <?php if ($a['override'] > 0): ?>
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle"
                              title="ครูปรับคะแนนเอง <?php echo (int)$a['override']; ?> ข้อ">ปรับ <?php echo (int)$a['override']; ?></span>
                      <?php endif; ?>
                    <?php elseif (!empty($s['essay'][$rk])): ?>
                      <span class="ts-none">ระบบยังไม่ตรวจ</span>
                    <?php else: ?>
                      <span class="ts-nowork">ยังไม่ส่งงาน</span>
                    <?php endif; ?>
                  </td>
                <?php endforeach; ?>
                <td class="<?php echo ($adPP === null) ? 'ts-nowork' : (($adPP >= 0) ? 'text-success fw-bold' : 'text-danger fw-bold'); ?>">
                  <?php echo ($adPP === null) ? '—' : (($adPP >= 0 ? '+' : '') . number_format($adPP, 2)); ?>
                </td>
                <td class="<?php echo ($adU === null) ? 'ts-nowork' : (($adU >= 0) ? 'text-success fw-bold' : 'text-danger fw-bold'); ?>">
                  <?php echo ($adU === null) ? '—' : (($adU >= 0 ? '+' : '') . number_format($adU, 2)); ?>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$tsRep['students']): ?>
              <tr><td colspan="9" class="text-center text-muted py-4">ไม่มีนักเรียนในขอบเขตที่เลือก</td></tr>
            <?php endif; ?>
          </tbody>
          <tfoot>
            <?php foreach ([['n (ฉบับที่ระบบตรวจแล้ว)', 'n', 0], ['ค่าเฉลี่ย (M)', 'mean', 2],
                            ['ส่วนเบี่ยงเบนมาตรฐาน (SD)', 'sd', 2]] as $sr): ?>
              <tr>
                <td colspan="3" class="ts-stat-label"><?php echo htmlspecialchars($sr[0]); ?></td>
                <?php foreach ($tsRounds as $rk => $r): $v = $tsRep['ai_columns'][$rk][$sr[1]]; ?>
                  <td><?php echo ($sr[1] === 'n') ? (int)$v : ts_num($v, $sr[2]); ?></td>
                <?php endforeach; ?>
                <td colspan="2"></td>
              </tr>
            <?php endforeach; ?>
          </tfoot>
        </table>
      </div>
    </div>
  </div>

  <!-- ===================== 4) สถิติจับคู่ของคะแนนอัตโนมัติ ===================== -->
  <div class="card border-0 shadow-sm rounded-4 mb-4" style="border-top:4px solid #7c3aed !important;">
    <div class="card-body p-4">
      <h5 class="fw-bold mb-1"><i class="bi bi-graph-up me-2 text-primary"></i>ตารางที่ 4
        ผลการเปรียบเทียบแบบจับคู่ จากคะแนนที่ระบบตรวจ</h5>
      <p class="text-muted small mb-3">
        คู่เดียวกับตารางที่ 2 และคิดแยกกันคนละคู่เช่นเดียวกัน
        แต่ละคู่ใช้เฉพาะนักเรียนที่ระบบตรวจครบทั้งสองรอบของคู่นั้น (เต็ม <?php echo (int)$tsAiMax; ?> คะแนน)
      </p>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 ts-table">
          <thead>
            <tr>
              <th rowspan="2" class="text-start align-middle">คู่เทียบ</th>
              <th rowspan="2" class="align-middle">n</th>
              <th colspan="2" class="grp-pre">รอบแรก</th>
              <th colspan="2" class="grp-post">รอบหลัง</th>
              <th rowspan="2" class="align-middle">ผลต่างเฉลี่ย</th>
              <th rowspan="2" class="align-middle">SD ผลต่าง</th>
              <th rowspan="2" class="align-middle">t</th>
              <th rowspan="2" class="align-middle">df</th>
              <th rowspan="2" class="align-middle">p</th>
              <th rowspan="2" class="align-middle">d<sub>z</sub></th>
            </tr>
            <tr>
              <th class="grp-pre">M</th><th class="grp-pre">SD</th>
              <th class="grp-post">M</th><th class="grp-post">SD</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($tsRep['ai_pairs'] as $pk => $p): $row = $p['row']; ?>
              <tr>
                <td class="text-start fw-semibold"><?php echo htmlspecialchars($p['label']); ?></td>
                <td><?php echo (int)$row['n']; ?></td>
                <td class="ts-score"><?php echo ts_num($row['a_mean']); ?></td>
                <td><?php echo ts_num($row['a_sd']); ?></td>
                <td class="ts-score"><?php echo ts_num($row['b_mean']); ?></td>
                <td><?php echo ts_num($row['b_sd']); ?></td>
                <td class="<?php echo ($row['diff'] !== null && $row['diff'] >= 0) ? 'text-success fw-bold' : 'text-danger fw-bold'; ?>">
                  <?php echo ($row['diff'] === null) ? '—'
                            : (($row['diff'] >= 0 ? '+' : '') . number_format($row['diff'], 2)); ?>
                </td>
                <td><?php echo ts_num($row['sd_diff']); ?></td>
                <td><?php echo ts_num($row['t'], 3); ?></td>
                <td><?php echo (int)$row['df']; ?></td>
                <td class="<?php echo $row['sig'] ? 'ts-sig' : 'ts-nosig'; ?>"><?php echo ts_p_html($row['p']); ?></td>
                <td><?php echo ts_num($row['dz'], 3); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ===================== 5) เทียบระบบกับครู ===================== -->
  <div class="card border-0 shadow-sm rounded-4 mb-4" style="border-top:4px solid #b45309 !important;">
    <div class="card-body p-4">
      <h5 class="fw-bold mb-1"><i class="bi bi-people me-2 text-warning-emphasis"></i>ตารางที่ 5
        เทียบคะแนนอัตโนมัติกับคะแนนครู รายรอบ</h5>
      <p class="text-muted small mb-3">
        เทียบบน<strong>สเกลเดียวกัน</strong> คือนับเฉพาะ 10 ตัวบ่งชี้ที่ระบบตรวจได้ (เต็ม <?php echo (int)$tsAiMax; ?> คะแนน)
        โดยตัดข้อ 4.3 ความเรียบร้อยออกจากคะแนนของครูด้วย มิฉะนั้นจะเทียบคนละฐานคะแนน ·
        ใช้เฉพาะนักเรียนที่มีทั้งคะแนนครูและคะแนนอัตโนมัติในรอบนั้น
      </p>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 ts-table">
          <thead>
            <tr>
              <th rowspan="2" class="text-start align-middle">รอบ</th>
              <th rowspan="2" class="align-middle">n</th>
              <th colspan="2" class="grp-pre">ครูประเมิน</th>
              <th colspan="2" class="grp-post">ระบบตรวจ</th>
              <th rowspan="2" class="align-middle">ผลต่างเฉลี่ย<br><span class="fw-normal small">(ระบบ − ครู)</span></th>
              <th rowspan="2" class="align-middle">t</th>
              <th rowspan="2" class="align-middle">df</th>
              <th rowspan="2" class="align-middle">p</th>
              <th rowspan="2" class="align-middle">Pearson r</th>
            </tr>
            <tr>
              <th class="grp-pre">M</th><th class="grp-pre">SD</th>
              <th class="grp-post">M</th><th class="grp-post">SD</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($tsRep['agreement'] as $rk => $row): ?>
              <tr>
                <td class="text-start fw-semibold"><?php echo htmlspecialchars($tsRounds[$rk]['label']); ?></td>
                <td><?php echo (int)$row['n']; ?></td>
                <td class="ts-score"><?php echo ts_num($row['a_mean']); ?></td>
                <td><?php echo ts_num($row['a_sd']); ?></td>
                <td class="ts-score"><?php echo ts_num($row['b_mean']); ?></td>
                <td><?php echo ts_num($row['b_sd']); ?></td>
                <td class="<?php echo ($row['diff'] !== null && $row['diff'] >= 0) ? 'text-primary fw-bold' : 'text-danger fw-bold'; ?>">
                  <?php echo ($row['diff'] === null) ? '—'
                            : (($row['diff'] >= 0 ? '+' : '') . number_format($row['diff'], 2)); ?>
                </td>
                <td><?php echo ts_num($row['t'], 3); ?></td>
                <td><?php echo (int)$row['df']; ?></td>
                <td class="<?php echo $row['sig'] ? 'ts-sig' : 'ts-nosig'; ?>"><?php echo ts_p_html($row['p']); ?></td>
                <td><?php echo ts_num($row['r']['r'] ?? null, 3); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="ts-legend mt-2">
        <i class="bi bi-info-circle me-1"></i>
        ค่า p ที่<strong>ไม่</strong>มีนัยสำคัญในตารางนี้เป็นสัญญาณที่ดี — แปลว่าคะแนนของระบบ
        กับของครูไม่ต่างกันอย่างเป็นระบบ ส่วนค่า r ยิ่งเข้าใกล้ 1 ยิ่งจัดลำดับนักเรียนได้ตรงกัน
        ไม่ว่าผลจะออกมาอย่างไร <strong>การตัดสินผลยังใช้คะแนนของครูเท่านั้น</strong>
      </div>
    </div>
  </div>

</div>

<script>
  // กลุ่มการวิจัยใช้ปุ่มบนแถบด้านบนร่วมกันทุกหน้าครู (เก็บไว้ใน localStorage)
  // หน้านี้คำนวณฝั่งเซิร์ฟเวอร์ จึงต้องส่งกลุ่มไปกับ URL — เปิดครั้งแรกให้เติมค่าจากปุ่มให้อัตโนมัติ
  (function tsSyncGroupToUrl() {
    var params = new URLSearchParams(location.search);
    if (!params.has('group') && window.TEG) {
      params.set('group', TEG.get());
      location.replace(location.pathname + '?' + params.toString());
    }
  })();

  // กดเปลี่ยนกลุ่มบนแถบด้านบน → โหลดหน้าเดิมด้วยขอบเขตใหม่
  window.onTEGChange = function () {
    var params = new URLSearchParams(location.search);
    params.set('group', window.TEG ? TEG.get() : 'all');
    params.delete('export');
    location.href = location.pathname + '?' + params.toString();
  };

  function tsChangeRoom(value) {
    var params = new URLSearchParams(location.search);
    if (value) params.set('classroom', value); else params.delete('classroom');
    params.delete('export');
    location.href = location.pathname + '?' + params.toString();
  }
</script>

<?php require_once 'footer.php'; ?>
