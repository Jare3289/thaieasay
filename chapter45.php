<?php
/**
 * chapter45.php — หน้า "วิเคราะห์บทที่ 4 และบทที่ 5"
 * ---------------------------------------------------------------------------
 * รวมทุกอย่างที่ต้องใช้เขียนผลการวิจัยไว้ในหน้าเดียว
 *   1) ตรวจความพร้อมของข้อมูล — บอกตรง ๆ ว่ายังขาดอะไรก่อนจะเขียนบทที่ 4-5 ได้ครบ
 *   2) ตัวเลขสถิติทั้งหมด (ตาราง 12 และตาราง 14) คำนวณจากข้อมูลจริงในระบบ
 *   3) ปุ่มให้ระบบวิเคราะห์ทีละหัวข้อ หรือสั่งรวดเดียวทั้ง 21 หัวข้อ
 *   4) กล่องบันทึกหลังสอน — แหล่งข้อมูลของ "ข้อเสนอแนะ" ในบทที่ 5
 *   5) ปุ่มเปิดร่างบทที่ 4-5 ฉบับประกอบเสร็จ พร้อมพิมพ์หรือคัดลอกไปวางในวิทยานิพนธ์
 *
 * สิทธิ์: ครูสั่งวิเคราะห์และแก้ไขได้ · ผู้เชี่ยวชาญดูผลได้อย่างเดียว
 */
$page_title = 'วิเคราะห์บทที่ 4 และบทที่ 5 - ระบบประเมินเรียงความ';
require_once 'auth_helper.php';
require_login();
if (!in_array($_SESSION['user']['role'], ['teacher', 'expert'], true)) {
    header('Location: index.php');
    exit;
}
require_once 'chapter45_engine.php';
require_once 'header.php';

$c45IsTeacher = ($sessionUser['role'] === 'teacher');
?>

<div id="view-chapter45" class="text-start">
  <div class="mb-3">
    <a href="index.php" class="btn btn-link text-decoration-none text-secondary fw-bold p-0">
      <i class="bi bi-arrow-left-short"></i> กลับหน้าเมนูหลัก
    </a>
  </div>

  <!-- หัวเรื่อง -->
  <div class="card border-0 shadow-lg rounded-4 overflow-hidden mb-4">
    <div class="p-4 text-white" style="background: linear-gradient(135deg, #0f766e 0%, #4c1d95 55%, #831843 100%);">
      <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
        <div>
          <h4 class="fw-bold mb-1"><i class="bi bi-journal-richtext me-2"></i>วิเคราะห์บทที่ 4 และบทที่ 5</h4>
          <p class="text-white-50 mb-0 small">
            รวบรวมข้อมูลจริงในระบบ คำนวณสถิติทุกตัวที่โครงวิทยานิพนธ์ต้องใช้
            แล้วให้ระบบเรียบเรียงเป็นเนื้อหาบทที่ 4 และบทที่ 5 ทีละหัวข้อ
          </p>
        </div>
        <div class="text-end">
          <span class="badge bg-white text-dark px-3 py-2 fw-bold d-block mb-2">
            <i class="bi bi-person-fill me-1"></i><?php echo htmlspecialchars($sessionUser['name']); ?>
          </span>
          <a href="chapter45_print.php" target="_blank" class="btn btn-light btn-sm fw-bold rounded-pill px-3 mb-2">
            <i class="bi bi-file-earmark-text me-1"></i>เปิดร่างบทที่ 4-5
          </a>
<?php if ($c45IsTeacher): ?>
          <button id="c45ExportBtn" class="btn btn-sm fw-bold rounded-pill px-3 text-white d-block w-100"
                  style="background:linear-gradient(135deg,#1a1a2e,#4c1d95);" type="button" onclick="sendChapter45ReportToGoogleDocs()">
            <i class="bi bi-google me-1"></i>ส่งออกหน้านี้ทั้งหมดเป็น Google Doc
          </button>
          <div id="c45GoogleStatusBox" class="small text-white-50 mt-1"></div>
<?php endif; ?>
        </div>
      </div>
    </div>
    <div class="bg-light border-top px-4 py-2 small text-muted">
      <i class="bi bi-shield-check me-1"></i>
      <strong>ตัวเลขทุกตัวคำนวณด้วยระบบ ไม่ได้ให้ระบบคิด</strong> —
      ระบบทำหน้าที่เรียบเรียงเป็นภาษาวิชาการเท่านั้น และข้อความที่ยกเป็นตัวอย่างจะถูกตรวจซ้ำว่า
      <strong>ปรากฏอยู่ในผลงานจริงของนักเรียน</strong> ถ้าไม่ตรงระบบจะขึ้นเตือนเป็นสีแดงให้ตรวจก่อนนำไปใช้
    </div>
  </div>

  <div id="c45Alert" class="alert border-0 rounded-3 small d-none" role="alert"></div>

  <!-- 1) ความพร้อมของข้อมูล -->
  <div class="card border-0 shadow-sm rounded-4 mb-4" style="border-top:4px solid #0f766e !important;">
    <div class="card-body p-4">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h5 class="fw-bold mb-0"><i class="bi bi-clipboard-check me-2 text-success"></i>ความพร้อมของข้อมูล</h5>
        <div class="d-flex align-items-center gap-2">
          <span id="c45ReadyBadge" class="badge bg-secondary px-3 py-2">กำลังตรวจ…</span>
          <button class="btn btn-sm btn-outline-secondary rounded-pill" onclick="c45Load()">
            <i class="bi bi-arrow-clockwise"></i> ตรวจใหม่
          </button>
        </div>
      </div>
      <div id="c45Readiness" class="row g-2"><div class="col-12 text-muted small">กำลังโหลด…</div></div>
    </div>
  </div>

  <!-- 2) ตาราง 12 -->
  <div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
      <h5 class="fw-bold mb-1"><i class="bi bi-table me-2 text-primary"></i>ตาราง 12 ผลการเปรียบเทียบก่อนเรียนและหลังเรียน</h5>
      <p class="text-muted small mb-3" id="c45QuantNote">—</p>
      <div class="table-responsive"><table class="table table-sm align-middle mb-0" id="c45QuantTable"></table></div>
      <div id="c45QuantExtra" class="mt-3 small"></div>
    </div>
  </div>

  <!-- 3) ตาราง 14 -->
  <div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
      <h5 class="fw-bold mb-1"><i class="bi bi-list-ol me-2 text-danger"></i>ตาราง 14 จำนวนและร้อยละของนักเรียนที่ปรากฏข้อบกพร่อง</h5>
      <p class="text-muted small mb-3" id="c45DefectNote">—</p>
      <div class="table-responsive"><table class="table table-sm align-middle mb-0" id="c45DefectTable"></table></div>
      <div id="c45MechBox" class="mt-3 small"></div>
    </div>
  </div>

<?php if ($c45IsTeacher): ?>
  <!-- 4) สั่งให้ระบบวิเคราะห์ -->
  <div class="card border-0 shadow-sm rounded-4 mb-4" style="border-top:4px solid #6d28d9 !important;">
    <div class="card-body p-4">
      <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
        <div>
          <h5 class="fw-bold mb-1"><i class="bi bi-file-earmark-check me-2" style="color:#6d28d9"></i>ให้ระบบวิเคราะห์และเรียบเรียง</h5>
          <p class="text-muted small mb-0">
            ระบบจะสั่งทีละหัวข้อตามลำดับที่ถูกต้อง (ตัวบ่งชี้ → องค์ประกอบ → ภาพรวม → บทที่ 5)
            เพราะหัวข้อสรุปต้องอ่านผลของหัวข้อย่อยก่อน
          </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
          <button id="c45RunAllBtn" class="btn btn-lg fw-bold rounded-pill px-4 text-white"
                  style="background:linear-gradient(135deg,#6d28d9,#0d7377);" onclick="c45RunAll()">
            <i class="bi bi-stars me-1"></i>วิเคราะห์ทั้งหมด
          </button>
          <button id="c45StopBtn" class="btn btn-outline-danger rounded-pill px-3 d-none" onclick="c45Stop()">
            <i class="bi bi-stop-circle"></i> หยุด
          </button>
          <button class="btn btn-outline-secondary rounded-pill px-3" onclick="c45ClearAll()">
            <i class="bi bi-trash3"></i> ล้างผลทั้งหมด
          </button>
        </div>
      </div>
      <div id="c45Progress" class="d-none">
        <div class="progress rounded-pill mb-2" style="height:10px;">
          <div id="c45ProgressBar" class="progress-bar" style="width:0%;background:#6d28d9"></div>
        </div>
        <div id="c45ProgressLabel" class="small text-muted mb-2"></div>
        <div id="c45RunLog" class="small bg-light rounded-3 p-2"
             style="max-height:220px; overflow:auto; font-family:ui-monospace,monospace;"></div>
      </div>
    </div>
  </div>
<?php endif; ?>

  <!-- 5) ผลวิเคราะห์รายหัวข้อ -->
  <div id="c45Results" class="mb-4"></div>

<?php if ($c45IsTeacher): ?>
  <!-- 6) บันทึกหลังสอน -->
  <div class="card border-0 shadow-sm rounded-4 mb-4" style="border-top:4px solid #f59e0b !important;">
    <div class="card-body p-4">
      <h5 class="fw-bold mb-1"><i class="bi bi-journal-bookmark me-2 text-warning"></i>บันทึกหลังสอน</h5>
      <p class="text-muted small mb-3">
        โครงบทที่ 5 กำหนดว่า <strong>ข้อเสนอแนะสำหรับการนำผลวิจัยไปใช้ต้องเขียนจากปัญหาที่พบจริง</strong>
        ไม่ใช่จากตัวเลข — บันทึกไว้ที่นี่แล้ว ระบบจะเรียบเรียงให้เป็นข้อเสนอแนะตามรูปแบบของวิทยานิพนธ์
      </p>
      <div class="row g-2 align-items-end mb-3">
        <div class="col-md-3">
          <label class="form-label small fw-bold mb-1">ขั้นของ POA</label>
          <select id="c45LogStage" class="form-select form-select-sm"></select>
        </div>
        <div class="col-md-2">
          <label class="form-label small fw-bold mb-1">หน่วยการเรียนรู้</label>
          <select id="c45LogUnit" class="form-select form-select-sm">
            <option value="0">ไม่ระบุ</option><option value="1">หน่วยที่ 1</option><option value="2">หน่วยที่ 2</option>
          </select>
        </div>
        <div class="col-md-7">
          <label class="form-label small fw-bold mb-1">ปัญหาที่พบจริง <span class="text-danger">*</span></label>
          <input id="c45LogProblem" class="form-control form-control-sm"
                 placeholder="เช่น นักเรียนบางส่วนลังเลที่จะเขียนร่างแรก เพราะรู้สึกว่ายังไม่ได้รับความรู้">
        </div>
        <div class="col-md-6">
          <label class="form-label small fw-bold mb-1">แนวทางแก้ไขที่ใช้แล้วได้ผล</label>
          <input id="c45LogSolution" class="form-control form-control-sm"
                 placeholder="เช่น ชี้แจงตั้งแต่ต้นว่าร่างแรกไม่ได้มีไว้ตัดสินคะแนน">
        </div>
        <div class="col-md-4">
          <label class="form-label small fw-bold mb-1">ข้อสังเกต/หลักฐานประกอบ</label>
          <input id="c45LogEvidence" class="form-control form-control-sm" placeholder="เช่น คาบที่ 2 หน่วยที่ 1">
        </div>
        <div class="col-md-2 d-grid">
          <input type="hidden" id="c45LogId" value="0">
          <button class="btn btn-warning fw-bold btn-sm" onclick="c45SaveLog()">
            <i class="bi bi-plus-lg"></i> <span id="c45LogBtnText">เพิ่มบันทึก</span>
          </button>
        </div>
      </div>
      <div id="c45LogList"></div>
    </div>
  </div>

  <!-- 7) ข้อมูลประจำงานวิจัย -->
  <div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
      <h5 class="fw-bold mb-1"><i class="bi bi-gear me-2 text-secondary"></i>ข้อมูลประจำงานวิจัย</h5>
      <p class="text-muted small mb-3">
        ค่าที่กรอกที่นี่ถูกนำไปใช้ในย่อหน้าเปิดบทที่ 5 และเป็นตัวกำหนดว่าจะใช้ผลงานรอบใดเป็น
        &quot;ผลงานครั้งที่ 1 และครั้งที่ 2&quot; ในการวิเคราะห์เชิงคุณภาพ
      </p>
      <div id="c45MetaForm" class="row g-2"></div>
      <div class="mt-3">
        <button class="btn btn-primary btn-sm fw-bold rounded-pill px-4" onclick="c45SaveMeta()">
          <i class="bi bi-save me-1"></i>บันทึกข้อมูลประจำงานวิจัย
        </button>
      </div>
    </div>
  </div>
<?php endif; ?>
</div>

<script>
const C45_IS_TEACHER = <?php echo $c45IsTeacher ? 'true' : 'false'; ?>;
let c45Data = null;
let c45Stopped = false;
let c45Running = false;

// สลับแสดง/ซ่อนชื่อจริงของนักเรียนในตัวอย่างที่ยกมา — ใช้ไล่หาต้นฉบับเท่านั้น
// (ค่าเริ่มต้นคือซ่อน เพราะบทที่ 4 ฉบับจริงต้องอ้างด้วยหมายเลขนักเรียนเสมอ ห้ามใช้ชื่อจริง)
let c45RevealNames = (function () {
  try { return localStorage.getItem('c45_reveal_names') === '1'; } catch (e) { return false; }
})();
function c45ToggleRevealNames(checked) {
  c45RevealNames = !!checked;
  try { localStorage.setItem('c45_reveal_names', c45RevealNames ? '1' : '0'); } catch (e) {}
  c45PaintResults();
}

/* ---------------------------------------------------------------- ตัวช่วย */
function c45Esc(s) {
  return String(s === null || s === undefined ? '' : s)
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}
function c45Num(v, d = 2) {
  if (v === null || v === undefined || v === '' || isNaN(v)) return '—';
  return Number(v).toFixed(d);
}
function c45P(p) {
  if (p === null || p === undefined || isNaN(p)) return '—';
  if (p < 0.001) return '< .001';
  return Number(p).toFixed(3).replace(/^0/, '');
}
function c45R(v, d = 3) {
  if (v === null || v === undefined || isNaN(v)) return '—';
  return Number(v).toFixed(d).replace(/^0\./, '.').replace(/^-0\./, '-.');
}
function c45Alert(msg, kind) {
  const el = document.getElementById('c45Alert');
  el.className = 'alert border-0 rounded-3 small alert-' + (kind || 'info');
  el.innerHTML = msg;
  el.classList.remove('d-none');
}
function c45Params() {
  const g = (window.TEG ? TEG.filterValue() : '') || '';
  return { group: g };
}
async function c45Api(payload) {
  const res = await fetch('api.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  });
  return await res.json();
}

/* ---------------------------------------------------------------- โหลดข้อมูล */
async function c45Load() {
  try {
    const d = await c45Api(Object.assign({ action: 'ch45_get_data' }, c45Params()));
    if (!d.success) { c45Alert(c45Esc(d.error || 'โหลดข้อมูลไม่สำเร็จ'), 'danger'); return; }
    c45Data = d;
    c45PaintReadiness();
    c45PaintQuant();
    c45PaintDefects();
    c45PaintResults();
    if (C45_IS_TEACHER) { c45PaintMeta(); c45PaintLogs(); }
    document.getElementById('c45Alert').classList.add('d-none');
  } catch (e) {
    c45Alert('เชื่อมต่อไม่สำเร็จ กรุณาลองใหม่', 'danger');
  }
}

/* ---------------------------------------------------------------- ความพร้อม */
function c45PaintReadiness() {
  const r = c45Data.readiness;
  const badge = document.getElementById('c45ReadyBadge');
  badge.className = 'badge px-3 py-2 ' + (r.counts.missing ? 'bg-danger' : (r.counts.warn ? 'bg-warning text-dark' : 'bg-success'));
  badge.textContent = r.counts.missing
    ? ('ยังขาดข้อมูล ' + r.counts.missing + ' รายการ')
    : (r.counts.warn ? ('ควรตรวจสอบ ' + r.counts.warn + ' รายการ') : 'ข้อมูลพร้อมครบทุกรายการ');

  const icon = { ok: ['bi-check-circle-fill', 'text-success'], warn: ['bi-exclamation-triangle-fill', 'text-warning'],
                 missing: ['bi-x-circle-fill', 'text-danger'] };
  document.getElementById('c45Readiness').innerHTML = r.items.map(function (it) {
    const ic = icon[it.status] || icon.warn;
    return '<div class="col-md-6"><div class="d-flex gap-2 p-2 rounded-3 bg-light h-100">'
      + '<i class="bi ' + ic[0] + ' ' + ic[1] + ' mt-1"></i><div class="small">'
      + '<div class="fw-bold">' + c45Esc(it.label) + '</div>'
      + '<div class="text-muted">' + c45Esc(it.detail) + '</div>'
      + (it.status !== 'ok' && it.fix ? '<div class="text-primary mt-1"><i class="bi bi-arrow-right-short"></i>' + c45Esc(it.fix) + '</div>' : '')
      + '</div></div></div>';
  }).join('');
}

/* ---------------------------------------------------------------- ตาราง 12 */
function c45PaintQuant() {
  const q = c45Data.quant;
  document.getElementById('c45QuantNote').innerHTML =
    'n = ' + q.n + ' คน (ผู้ที่มีคะแนนครบทั้งก่อนและหลังเรียน) · df = ' + q.df
    + ' · ทดสอบด้วย Paired-samples t-test สองทาง · *p &lt; .05';

  let h = '<thead class="table-light"><tr>'
    + '<th>ความสามารถในการเขียนเรียงความ</th><th class="text-center">คะแนนเต็ม</th>'
    + '<th class="text-center">ก่อนเรียน M</th><th class="text-center">SD</th>'
    + '<th class="text-center">หลังเรียน M</th><th class="text-center">SD</th>'
    + '<th class="text-center">t</th><th class="text-center">p</th>'
    + '<th class="text-center">dz</th><th class="text-center">ขนาดอิทธิพล</th>'
    + '</tr></thead><tbody>';
  q.rows.forEach(function (r) {
    h += '<tr' + (r.key === 'overall' ? ' class="fw-bold table-light"' : '') + '>'
      + '<td>' + c45Esc(r.label) + '</td>'
      + '<td class="text-center">' + c45Num(r.max, 0) + '</td>'
      + '<td class="text-center">' + c45Num(r.pre_mean) + '</td><td class="text-center">' + c45Num(r.pre_sd) + '</td>'
      + '<td class="text-center">' + c45Num(r.post_mean) + '</td><td class="text-center">' + c45Num(r.post_sd) + '</td>'
      + '<td class="text-center">' + c45Num(r.t, 3) + (r.sig ? '*' : '') + '</td>'
      + '<td class="text-center">' + c45P(r.p) + '</td>'
      + '<td class="text-center">' + c45Num(r.dz) + '</td>'
      + '<td class="text-center"><span class="badge bg-light text-dark">' + c45Esc(r.effect) + '</span></td>'
      + '</tr>';
  });
  h += '</tbody>';
  document.getElementById('c45QuantTable').innerHTML = h;

  // ผลทดสอบการแจกแจงและความเที่ยงระหว่างผู้ประเมิน
  let x = '';
  const n = q.normality && q.normality.overall;
  if (n && n.W !== null) {
    x += '<div class="alert alert-' + (n.normal ? 'success' : 'warning') + ' border-0 rounded-3 py-2 mb-2">'
      + '<strong>การแจกแจงของคะแนนผลต่าง (Shapiro-Wilk):</strong> W = ' + c45R(n.W) + ', p = ' + c45P(n.p) + ' — '
      + (n.normal ? 'ไม่แตกต่างจากการแจกแจงปกติ จึงใช้ Paired-samples t-test ได้'
                  : 'แตกต่างจากการแจกแจงปกติอย่างมีนัยสำคัญ ควรรายงานผลอย่างระมัดระวัง หรือเพิ่มการทดสอบไร้พารามิเตอร์ (Wilcoxon signed-rank)')
      + '</div>';
  }
  const ir = q.interrater || {};
  const keys = Object.keys(ir);
  if (keys.length) {
    x += '<div class="border rounded-3 p-2"><div class="fw-bold mb-1">ความเที่ยงระหว่างผู้ประเมิน (ICC — Inter-rater Reliability)</div>'
      + '<div class="text-muted small mb-2">ใช้ ICC แบบสองทางผสม ความสอดคล้องสัมบูรณ์ (two-way mixed effects, absolute agreement)'
      + ' เป็นค่าหลักในการสรุปผล ตามเกณฑ์แปลผลของ Koo &amp; Li (2016) — Pearson r แสดงประกอบเป็นค่าความสัมพันธ์รายคู่เท่านั้น</div>'
      + '<table class="table table-sm mb-0"><thead><tr><th>รอบ</th><th class="text-center">ผู้ประเมิน</th>'
      + '<th class="text-center">n</th><th class="text-center">ICC(3,1)</th><th class="text-center">ICC(3,k)</th>'
      + '<th class="text-center">p</th><th class="text-center">แปลผล (ยึดตาม ICC)</th><th>Pearson r รายคู่<br><small class="text-muted">(ประกอบ)</small></th>'
      + '</tr></thead><tbody>';
    keys.forEach(function (k) {
      const v = ir[k];
      x += '<tr><td>' + c45Esc(v.label) + '</td><td class="text-center">' + v.k + '</td>'
        + '<td class="text-center">' + v.n + '</td>'
        + '<td class="text-center">' + c45R(v.icc.icc1) + '</td>'
        + '<td class="text-center fw-bold">' + c45R(v.icc.iccK) + '</td>'
        + '<td class="text-center">' + c45P(v.icc.p) + '</td>'
        + '<td class="text-center"><span class="badge bg-primary-subtle text-primary-emphasis">' + c45Esc(v.icc_label) + '</span></td>'
        + '<td class="small text-muted">'
        + v.pearson.map(function (p) { return 'r = ' + c45R(p.r); }).join(', ')
        + '</td></tr>';
    });
    x += '</tbody></table></div>';
  } else {
    x += '<div class="alert alert-warning border-0 rounded-3 py-2 mb-0 small">'
      + 'ยังคำนวณความเที่ยงระหว่างผู้ประเมินไม่ได้ — ต้องมีผู้ประเมินตั้งแต่ 2 คนขึ้นไป'
      + 'ให้คะแนนผลงานชุดเดียวกันในรอบเดียวกัน (โครงบทที่ 4 กำหนดให้ต้องรายงานค่านี้ โดยยึด ICC เป็นค่าหลัก)</div>';
  }
  document.getElementById('c45QuantExtra').innerHTML = x;
}

/* ---------------------------------------------------------------- ตาราง 14 */
function c45PaintDefects() {
  const d = c45Data.defects;
  const doms = c45Data.domains;
  document.getElementById('c45DefectNote').innerHTML =
    'n = ' + d.n + ' คน (ผู้ที่มีคะแนนครบทั้งสองครั้ง) · ' + c45Esc(d.rule);

  let h = '<thead class="table-light"><tr><th>ข้อบกพร่องที่พบในผลงานเรียงความ</th>'
    + '<th class="text-center">ครั้งที่ 1<br><small>n</small></th><th class="text-center">ครั้งที่ 1<br><small>%</small></th>'
    + '<th class="text-center">ครั้งที่ 2<br><small>n</small></th><th class="text-center">ครั้งที่ 2<br><small>%</small></th>'
    + '<th class="text-center">เปลี่ยนแปลง</th></tr></thead><tbody>';
  Object.keys(doms).forEach(function (dk) {
    h += '<tr class="table-light fw-bold"><td colspan="6">ด้าน' + c45Esc(doms[dk].name) + '</td></tr>';
    doms[dk].indicators.forEach(function (id) {
      const r = d.rows[id];
      if (!r) return;
      const dp = r.diff_pct;
      const cls = (dp === null) ? 'text-muted' : (dp < 0 ? 'text-success' : (dp > 0 ? 'text-danger' : 'text-muted'));
      h += '<tr><td>' + r.no + '. ' + c45Esc(r.defect)
        + (r.genre_bound ? ' <span class="badge bg-info-subtle text-info-emphasis">ผูกกับประเภทงานเขียน</span>' : '')
        + '</td>'
        + '<td class="text-center">' + r.n1 + '</td><td class="text-center">' + c45Num(r.pct1) + '</td>'
        + '<td class="text-center">' + r.n2 + '</td><td class="text-center">' + c45Num(r.pct2) + '</td>'
        + '<td class="text-center ' + cls + '">'
        + (dp === null ? '—' : ((dp > 0 ? '+' : '') + c45Num(dp)))
        + '</td></tr>';
    });
  });
  h += '</tbody>';
  document.getElementById('c45DefectTable').innerHTML = h;

  const m = c45Data.mechanics;
  document.getElementById('c45MechBox').innerHTML =
    '<div class="border rounded-3 p-2"><div class="fw-bold mb-1">ข้อมูลกลไกการเขียนจากตัวบทจริง (ใช้ในหัวข้อ 2.4.1)</div>'
    + '<div class="row g-2">'
    + ['work1', 'work2'].map(function (k) {
        const w = m[k];
        return '<div class="col-md-6"><div class="bg-light rounded-3 p-2">'
          + '<div class="fw-bold">' + c45Esc(w.label) + ' <span class="text-muted fw-normal">(' + w.pieces + ' ฉบับ)</span></div>'
          + '<div>สะกดผิดเฉลี่ย <strong>' + c45Num(w.spell_mean) + '</strong> แห่ง/ชิ้น (SD = ' + c45Num(w.spell_sd) + ')</div>'
          + '<div>ผลงานที่สะกดผิดตั้งแต่ 3 แห่ง: <strong>' + w.spell_ge3 + '</strong> ชิ้น</div>'
          + '<div>ความยาวเฉลี่ย <strong>' + c45Num(w.word_mean, 0) + '</strong> คำ</div>'
          + '</div></div>';
      }).join('')
    + '</div><div class="text-muted mt-2"><i class="bi bi-info-circle me-1"></i>' + c45Esc(m.note) + '</div></div>';
}
</script>

<script>
/* ---------------------------------------------------------------- ผลวิเคราะห์ */
function c45JobsInOrder() {
  return Object.keys(c45Data.jobs);
}

function c45WarnBox(warnings) {
  if (!warnings || !warnings.length) return '';
  return '<div class="alert alert-warning border-0 rounded-3 py-2 small mb-2">'
    + '<i class="bi bi-exclamation-triangle-fill me-1"></i><strong>ต้องตรวจสอบก่อนนำไปใช้</strong><ul class="mb-0 ps-3">'
    + warnings.map(function (w) { return '<li>' + c45Esc(w) + '</li>'; }).join('') + '</ul></div>';
}

function c45Para(label, text) {
  if (!text) return '';
  return '<div class="mb-2"><div class="small fw-bold text-secondary">' + c45Esc(label) + '</div>'
    + '<p class="mb-0" style="text-indent:2.5em; line-height:1.9;">' + c45Esc(text) + '</p></div>';
}

function c45Excerpt(exNo, ex, roundLabel) {
  if (!ex || !ex.text) {
    return '<div class="border rounded-3 p-2 mb-2 bg-light small text-muted">'
      + 'ตัวอย่าง (' + exNo + ') — ยังไม่มีข้อความที่ยกมา' + (ex && ex.reason ? ' (' + c45Esc(ex.reason) + ')' : '')
      + '</div>';
  }
  const ok = ex.verified === true;
  const who = 'นักเรียนคนที่ ' + c45Esc(ex.student_no)
    + (c45RevealNames && ex.student_name ? ' (ชื่อจริง: ' + c45Esc(ex.student_name) + ')' : '');
  return '<div class="border rounded-3 p-2 mb-2 ' + (ok ? 'border-success-subtle' : 'border-danger') + '">'
    + '<div class="d-flex justify-content-between align-items-center mb-1">'
    + '<span class="badge ' + (ok ? 'bg-success-subtle text-success-emphasis' : 'bg-danger') + '">'
    + (ok ? '<i class="bi bi-check2-circle"></i> ตรวจแล้วตรงกับผลงานจริง' : '<i class="bi bi-exclamation-octagon"></i> ไม่ตรงกับผลงานจริง')
    + '</span>'
    + '<span class="small text-muted">ตัวอย่าง (' + exNo + ')</span></div>'
    + '<div style="line-height:1.9;">' + c45Esc(ex.text) + '</div>'
    + '<div class="small text-muted mt-1">(' + who + ' ' + c45Esc(roundLabel) + ')</div>'
    + (ex.reason ? '<div class="small text-danger mt-1">' + c45Esc(ex.reason) + '</div>' : '')
    + '</div>';
}

function c45RenderPayload(jobKey, payload) {
  if (!payload) return '';
  const meta = c45Data.meta;

  if (jobKey === 'quant_narrative') {
    return c45Para('ย่อหน้าก่อนตาราง 12 (การตรวจสอบข้อตกลงเบื้องต้น)', payload.para_method)
      + c45Para('ย่อหน้าอ่านผลภาพรวม', payload.para_overall)
      + c45Para('ย่อหน้าผลรายด้าน', payload.para_domains)
      + c45Para('ย่อหน้าเปรียบเทียบขนาดการเปลี่ยนแปลงระหว่างองค์ประกอบ', payload.para_ranking);
  }

  if (jobKey.indexOf('ind_') === 0) {
    const ind = c45Data.indicators[payload.indicator] || {};
    const ex = payload.ex_no || [0, 0];
    return '<div class="row g-2 mb-2">'
      + '<div class="col-md-6"><div class="bg-light rounded-3 p-2 h-100"><div class="small fw-bold">ช่องตาราง — '
      + c45Esc(meta.work1_label) + '</div><div class="small">' + c45Esc(payload.cell1) + '</div></div></div>'
      + '<div class="col-md-6"><div class="bg-light rounded-3 p-2 h-100"><div class="small fw-bold">ช่องตาราง — '
      + c45Esc(meta.work2_label) + '</div><div class="small">' + c45Esc(payload.cell2) + '</div></div></div>'
      + '</div>'
      + c45Para('ย่อหน้าเปิดหัวข้อ ' + (ind.sub || ''), payload.finding)
      + c45Excerpt(ex[0], payload.excerpt1, meta.work1_label)
      + c45Excerpt(ex[1], payload.excerpt2, meta.work2_label)
      + c45Para('ตัวอย่าง (' + ex[0] + ') วิเคราะห์', payload.analysis1)
      + c45Para('ตัวอย่าง (' + ex[1] + ') วิเคราะห์', payload.analysis2)
      + c45Para('ข้อสรุปจากคู่ตัวอย่าง', payload.synthesis)
      + (payload.caution ? c45Para('ข้อความกำกับการตีความ', payload.caution) : '');
  }

  if (jobKey.indexOf('domain_') === 0) {
    const d = c45Data.domains[payload.domain] || {};
    let h = c45Para('ข้อค้นพบของด้านนี้', payload.finding);
    h += '<div class="table-responsive"><table class="table table-sm"><thead class="table-light"><tr>'
      + '<th>การเปลี่ยนแปลง</th><th>' + c45Esc(meta.work1_label) + '</th><th>' + c45Esc(meta.work2_label) + '</th>'
      + '</tr></thead><tbody>';
    (payload.table_rows || []).forEach(function (r, i) {
      h += '<tr><td class="fw-bold">' + (i + 1) + '. ' + c45Esc(r.name) + '</td>'
        + '<td>' + c45Esc(r.cell1) + '</td><td>' + c45Esc(r.cell2) + '</td></tr>';
    });
    h += '</tbody></table></div>';
    return '<div class="small text-muted mb-2">ตาราง ' + c45Esc(d.table || '') + '</div>' + h;
  }

  if (jobKey === 'overview') {
    let h = c45Para('ย่อหน้านำของตอนที่ 2', payload.intro)
      + c45Para('ย่อหน้าชี้แจงเรื่องประเภทของงานเขียน', payload.genre_note);
    h += '<div class="table-responsive"><table class="table table-sm"><thead class="table-light"><tr>'
      + '<th>องค์ประกอบ</th><th>' + c45Esc(meta.work1_label) + '</th><th>' + c45Esc(meta.work2_label) + '</th>'
      + '</tr></thead><tbody>';
    Object.keys(payload.cells || {}).forEach(function (k) {
      const c = payload.cells[k];
      h += '<tr><td class="fw-bold">' + c45Esc(c.name) + '</td><td>' + c45Esc(c.work1) + '</td>'
        + '<td>' + c45Esc(c.work2) + '</td></tr>';
    });
    h += '</tbody></table></div>';
    return h;
  }

  if (jobKey === 'defect_narrative') return c45Para('ย่อหน้าใต้ตาราง 14', payload.para);

  if (jobKey === 'ch5_summary') {
    return c45Para('ย่อหน้าเปิดบทที่ 5', payload.opening)
      + c45Para('สรุปผลตอนที่ 1', payload.part1)
      + c45Para('ประโยคนำสรุปผลตอนที่ 2', payload.part2_intro)
      + c45Para('ด้านเนื้อหาสาระ', payload.part2_d1)
      + c45Para('ด้านองค์ประกอบและการลำดับเรื่อง', payload.part2_d2)
      + c45Para('ด้านการใช้สำนวนภาษา', payload.part2_d3)
      + c45Para('ด้านอักขรวิธีและกลไกการเขียน', payload.part2_d4);
  }

  if (jobKey === 'ch5_discussion') {
    const color = { 'สนับสนุน': 'success', 'ขัดแย้ง': 'danger', 'ไม่มีข้อมูลพอ': 'secondary' };
    let h = '<div class="table-responsive"><table class="table table-sm"><thead class="table-light"><tr>'
      + '<th style="width:5%">ข้อ</th><th>ข้อความในร่างอภิปรายผล</th><th class="text-center">ผลจริง</th>'
      + '<th>หลักฐาน</th><th>ข้อความที่ควรใช้แทน</th></tr></thead><tbody>';
    (payload.checks || []).forEach(function (c) {
      h += '<tr><td class="fw-bold">' + c45Esc(c.point) + '</td><td class="small">' + c45Esc(c.claim) + '</td>'
        + '<td class="text-center"><span class="badge bg-' + (color[c.verdict] || 'secondary') + '">'
        + c45Esc(c.verdict) + '</span></td>'
        + '<td class="small">' + c45Esc(c.evidence) + '</td>'
        + '<td class="small">' + (c.suggest ? c45Esc(c.suggest) : '<span class="text-muted">ไม่ต้องแก้</span>') + '</td></tr>';
    });
    h += '</tbody></table></div>';
    (payload.new_points || []).forEach(function (n) {
      h += c45Para('ประเด็นอภิปรายที่ควรเพิ่ม — ' + n.heading, n.text);
    });
    if (payload.limitation_note) h += c45Para('ข้อความเพิ่มเติมสำหรับส่วนข้อจำกัดของการวิจัย', payload.limitation_note);
    return h;
  }

  if (jobKey === 'ch5_recommend') {
    let h = '';
    (payload.items || []).forEach(function (it, i) {
      h += c45Para('ข้อเสนอแนะข้อที่ ' + (i + 1) + (it.stage ? ' — ' + it.stage : ''), it.text);
    });
    if (payload.institution) h += c45Para('ข้อเสนอแนะต่อสถานศึกษาและหน่วยงานพัฒนาครู', payload.institution);
    (payload.future || []).forEach(function (f, i) {
      h += c45Para('ข้อเสนอแนะสำหรับการวิจัยครั้งต่อไป ข้อที่ ' + (i + 1), f.text);
    });
    if (payload.note) h += '<div class="alert alert-info border-0 rounded-3 py-2 small mb-0">'
      + c45Esc(payload.note) + '</div>';
    return h;
  }

  return '<pre class="small bg-light p-2 rounded-3">' + c45Esc(JSON.stringify(payload, null, 2)) + '</pre>';
}

function c45PaintResults() {
  const jobs = c45Data.jobs;
  const groups = c45Data.job_groups;
  const results = c45Data.results || {};
  let html = '';

  html += '<div class="alert ' + (c45RevealNames ? 'alert-danger' : 'alert-warning')
    + ' border-0 rounded-3 d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">'
    + '<div class="small pe-2">'
    + '<strong>ตัวอย่างที่ยกมาอ้างชื่อนักเรียนด้วย &quot;นักเรียนคนที่ N&quot; เสมอ</strong><br>'
    + 'กดปุ่มด้านขวาเพื่อเปิดดูชื่อจริงชั่วคราว สำหรับไล่หาต้นฉบับเทียบกับผลงานจริงเท่านั้น '
    + '(บทที่ 4 ฉบับจริงห้ามใช้ชื่อจริง — ปิดโหมดนี้ก่อนคัดลอกไปใช้งานเสมอ)'
    + '</div>'
    + '<button type="button" id="c45RevealNamesToggle" class="btn btn-sm fw-bold rounded-pill px-4 flex-shrink-0 '
    + (c45RevealNames ? 'btn-danger' : 'btn-outline-dark') + '" onclick="c45ToggleRevealNames(!c45RevealNames)">'
    + '<i class="bi ' + (c45RevealNames ? 'bi-eye-fill' : 'bi-eye-slash-fill') + ' me-1"></i>'
    + (c45RevealNames ? 'กำลังแสดงชื่อจริง — กดเพื่อซ่อน' : 'แสดงชื่อจริงชั่วคราว')
    + '</button></div>';

  Object.keys(groups).forEach(function (gk) {
    const items = Object.keys(jobs).filter(function (k) { return jobs[k].group === gk; });
    if (!items.length) return;
    const done = items.filter(function (k) { return results[k]; }).length;

    html += '<div class="card border-0 shadow-sm rounded-4 mb-3"><div class="card-body p-4">'
      + '<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">'
      + '<h5 class="fw-bold mb-0">' + c45Esc(groups[gk]) + '</h5>'
      + '<span class="badge ' + (done === items.length ? 'bg-success' : 'bg-secondary') + ' px-3 py-2">'
      + 'วิเคราะห์แล้ว ' + done + '/' + items.length + ' หัวข้อ</span></div>'
      + '<div class="accordion" id="acc-' + gk + '">';

    items.forEach(function (k, idx) {
      const job = jobs[k];
      const res = results[k];
      const badge = !res ? '<span class="badge bg-light text-secondary">ยังไม่วิเคราะห์</span>'
        : (res.stale ? '<span class="badge bg-warning text-dark">ข้อมูลเปลี่ยนหลังวิเคราะห์ ควรทำใหม่</span>'
                     : ((res.warnings && res.warnings.length)
                        ? '<span class="badge bg-warning text-dark">มี ' + res.warnings.length + ' จุดต้องตรวจ</span>'
                        : '<span class="badge bg-success">เรียบร้อย</span>'));
      const id = 'c45job-' + k;
      html += '<div class="accordion-item border-0 mb-2 rounded-3 overflow-hidden" style="background:#f8f9fa;">'
        + '<h2 class="accordion-header"><button class="accordion-button collapsed bg-transparent" type="button"'
        + ' data-bs-toggle="collapse" data-bs-target="#' + id + '">'
        + '<span class="flex-grow-1 fw-bold">' + c45Esc(job.label) + '</span>'
        + '<span class="me-3">' + badge + '</span></button></h2>'
        + '<div id="' + id + '" class="accordion-collapse collapse" data-bs-parent="#acc-' + gk + '">'
        + '<div class="accordion-body bg-white">'
        + '<p class="small text-muted">' + c45Esc(job.desc) + '</p>'
        + (C45_IS_TEACHER
            ? '<div class="d-flex gap-2 mb-3 flex-wrap">'
              + '<button class="btn btn-sm btn-primary rounded-pill px-3" onclick="c45RunOne(\'' + k + '\')">'
              + '<i class="bi bi-stars me-1"></i>' + (res ? 'วิเคราะห์ใหม่' : 'วิเคราะห์หัวข้อนี้') + '</button>'
              + (res ? '<button class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="c45DeleteOne(\'' + k + '\')">'
                       + '<i class="bi bi-trash3"></i> ลบผล</button>' : '')
              + (job.indicator ? '<button class="btn btn-sm btn-outline-secondary rounded-pill px-3" '
                       + 'onclick="c45ShowEvidence(\'' + job.indicator + '\')">'
                       + '<i class="bi bi-search"></i> ดูผลงานที่ระบบคัดให้ระบบ</button>' : '')
              + '</div>'
            : '')
        + '<div id="out-' + k + '">'
        + (res ? (c45WarnBox(res.warnings) + c45RenderPayload(k, res.payload)
                  + '<div class="small text-muted mt-2">วิเคราะห์เมื่อ ' + c45Esc(res.updated_at)
                  + (res.model ? ' · โมเดล ' + c45Esc(res.model) : '') + '</div>')
                : '<div class="text-muted small">ยังไม่ได้วิเคราะห์หัวข้อนี้</div>')
        + '</div></div></div></div>';
    });

    html += '</div></div></div>';
  });

  document.getElementById('c45Results').innerHTML = html;
}

/* ---------------------------------------------------------------- สั่งวิเคราะห์ */
function c45Log(icon, cls, msg) {
  const box = document.getElementById('c45RunLog');
  if (!box) return;
  const line = document.createElement('div');
  line.className = cls || '';
  line.innerHTML = '<i class="bi ' + icon + ' me-1"></i>' + c45Esc(msg);
  box.appendChild(line);
  box.scrollTop = box.scrollHeight;
}

function c45SetProgress(done, total, label) {
  document.getElementById('c45Progress').classList.remove('d-none');
  const pct = total ? Math.round(done * 100 / total) : 0;
  document.getElementById('c45ProgressBar').style.width = pct + '%';
  document.getElementById('c45ProgressLabel').textContent = label || (done + ' / ' + total + ' หัวข้อ');
}

async function c45RunJob(jobKey, quiet) {
  const d = await c45Api(Object.assign({ action: 'ch45_run_job', job: jobKey }, c45Params()));
  if (!d.success) {
    if (!quiet) c45Alert(c45Esc(d.error || 'วิเคราะห์ไม่สำเร็จ'), 'danger');
    return { ok: false, error: d.error };
  }
  c45Data.results[jobKey] = {
    payload: d.payload, warnings: d.warnings || [], model: d.model,
    updated_at: 'เมื่อสักครู่', stale: false
  };
  return { ok: true, warnings: d.warnings || [], pending: d.pending_deps || [] };
}

async function c45RunOne(jobKey) {
  if (c45Running) { c45Alert('กำลังวิเคราะห์อยู่ กรุณารอให้เสร็จก่อน', 'warning'); return; }
  c45Running = true;
  const out = document.getElementById('out-' + jobKey);
  if (out) out.innerHTML = '<div class="text-primary small"><span class="spinner-border spinner-border-sm me-2"></span>'
    + 'กำลังให้ระบบวิเคราะห์… (ปกติใช้เวลา 15-40 วินาทีต่อหัวข้อ)</div>';
  const r = await c45RunJob(jobKey, false);
  c45Running = false;
  if (r.ok) {
    if (r.pending && r.pending.length) {
      c45Alert('วิเคราะห์เสร็จแล้ว แต่หัวข้อนี้ควรทำหลังจาก: ' + c45Esc(r.pending.join(', '))
        + ' — แนะนำให้วิเคราะห์หัวข้อเหล่านั้นก่อนแล้วทำหัวข้อนี้ซ้ำ', 'warning');
    }
    c45PaintResults();
    const el = document.getElementById('c45job-' + jobKey);
    if (el) new bootstrap.Collapse(el, { show: true });
  } else if (out) {
    out.innerHTML = '<div class="text-danger small">' + c45Esc(r.error || 'วิเคราะห์ไม่สำเร็จ') + '</div>';
  }
}

async function c45RunAll() {
  if (c45Running) return;
  if (!confirm('ระบบจะสั่งให้ระบบวิเคราะห์ทั้ง ' + Object.keys(c45Data.jobs).length
      + ' หัวข้อตามลำดับ ใช้เวลาประมาณ 5-15 นาที ผลเดิมจะถูกทับ ยืนยันหรือไม่?')) return;

  c45Running = true;
  c45Stopped = false;
  document.getElementById('c45RunAllBtn').disabled = true;
  document.getElementById('c45StopBtn').classList.remove('d-none');
  document.getElementById('c45RunLog').innerHTML = '';

  const jobs = c45JobsInOrder();
  let done = 0, failed = 0, warned = 0;
  for (const k of jobs) {
    if (c45Stopped) { c45Log('bi-stop-circle', 'text-warning', 'หยุดตามคำสั่งผู้ใช้'); break; }
    c45SetProgress(done, jobs.length, 'กำลังวิเคราะห์: ' + c45Data.jobs[k].label);
    const r = await c45RunJob(k, true);
    done++;
    if (!r.ok) {
      failed++;
      c45Log('bi-x-circle-fill', 'text-danger', c45Data.jobs[k].label + ' — ' + (r.error || 'ไม่สำเร็จ'));
    } else if (r.warnings.length) {
      warned++;
      c45Log('bi-exclamation-triangle-fill', 'text-warning',
        c45Data.jobs[k].label + ' — เสร็จแล้ว แต่มี ' + r.warnings.length + ' จุดต้องตรวจ');
    } else {
      c45Log('bi-check-circle-fill', 'text-success', c45Data.jobs[k].label + ' — เรียบร้อย');
    }
    c45SetProgress(done, jobs.length);
  }

  c45SetProgress(done, jobs.length, 'เสร็จสิ้น — สำเร็จ ' + (done - failed) + ' หัวข้อ · ไม่สำเร็จ '
    + failed + ' หัวข้อ · ต้องตรวจ ' + warned + ' หัวข้อ');
  c45Running = false;
  document.getElementById('c45RunAllBtn').disabled = false;
  document.getElementById('c45StopBtn').classList.add('d-none');
  c45PaintResults();
  c45Alert(failed
    ? ('วิเคราะห์เสร็จแล้ว แต่มี ' + failed + ' หัวข้อที่ไม่สำเร็จ — กดวิเคราะห์ซ้ำเฉพาะหัวข้อนั้นได้')
    : 'วิเคราะห์ครบทุกหัวข้อแล้ว กด "เปิดร่างบทที่ 4-5" ด้านบนเพื่อดูฉบับประกอบเสร็จ',
    failed ? 'warning' : 'success');
}

function c45Stop() { c45Stopped = true; }

async function c45DeleteOne(jobKey) {
  if (!confirm('ยืนยันลบผลวิเคราะห์ของหัวข้อนี้?')) return;
  const d = await c45Api({ action: 'ch45_delete_result', job: jobKey });
  if (!d.success) { c45Alert(c45Esc(d.error || 'ลบไม่สำเร็จ'), 'danger'); return; }
  delete c45Data.results[jobKey];
  c45PaintResults();
}

async function c45ClearAll() {
  if (!confirm('ยืนยันลบผลวิเคราะห์ทั้งหมด? (ตัวเลขสถิติและข้อมูลวิจัยไม่ถูกลบ)')) return;
  const d = await c45Api({ action: 'ch45_delete_result', job: '__all__' });
  if (!d.success) { c45Alert(c45Esc(d.error || 'ลบไม่สำเร็จ'), 'danger'); return; }
  c45Data.results = {};
  c45PaintResults();
  c45Alert('ลบผลวิเคราะห์ทั้งหมดแล้ว', 'info');
}

async function c45ShowEvidence(indicatorId) {
  const d = await c45Api(Object.assign({ action: 'ch45_get_evidence', indicator: indicatorId }, c45Params()));
  if (!d.success) { c45Alert(c45Esc(d.error || 'ดึงข้อมูลไม่สำเร็จ'), 'danger'); return; }
  const ev = d.evidence;
  const meta = c45Data.meta;
  let h = '<div class="small text-muted mb-2">นี่คือผลงานจริงที่ระบบคัดส่งให้ระบบเลือกยกเป็นตัวอย่าง — '
    + 'ระบบยกข้อความได้จากผลงานเหล่านี้เท่านั้น'
    + (c45RevealNames ? ' (กำลังแสดงชื่อจริง — ปิดสวิตช์ด้านบนก่อนคัดลอกไปใช้งาน)' : '') + '</div>';
  [['work1', meta.work1_label], ['work2', meta.work2_label]].forEach(function (p) {
    h += '<h6 class="fw-bold mt-3">' + c45Esc(p[1]) + '</h6>';
    if (!ev[p[0]] || !ev[p[0]].length) { h += '<div class="text-muted small">ไม่พบผลงานในรอบนี้</div>'; return; }
    ev[p[0]].forEach(function (c) {
      const who = 'นักเรียนคนที่ ' + c45Esc(c.no)
        + (c45RevealNames && c.name ? ' (ชื่อจริง: ' + c45Esc(c.name) + ')' : '');
      h += '<div class="border rounded-3 p-2 mb-2"><div class="small fw-bold mb-1">' + who
        + ' · คะแนนดิบ ' + (c.raw === null ? '—' : Number(c.raw).toFixed(1)) + '/4 · ' + c45Esc(c.tag) + '</div>'
        + '<div class="small" style="line-height:1.8;">' + c45Esc(c.text) + '</div></div>';
    });
  });
  c45Modal('ผลงานที่ระบบคัดให้ระบบ — ตัวบ่งชี้ ' + indicatorId, h);
}

function c45Modal(title, bodyHtml) {
  let el = document.getElementById('c45Modal');
  if (!el) {
    el = document.createElement('div');
    el.id = 'c45Modal';
    el.className = 'modal fade';
    el.innerHTML = '<div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content rounded-4">'
      + '<div class="modal-header"><h5 class="modal-title fw-bold"></h5>'
      + '<button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>'
      + '<div class="modal-body"></div></div></div>';
    document.body.appendChild(el);
  }
  el.querySelector('.modal-title').textContent = title;
  el.querySelector('.modal-body').innerHTML = bodyHtml;
  new bootstrap.Modal(el).show();
}
</script>

<script>
/* ---------------------------------------------------------------- ข้อมูลประจำงานวิจัย */
function c45PaintMeta() {
  const fields = c45Data.meta_fields;
  const meta = c45Data.meta;
  const phases = c45Data.phases;
  let h = '';
  Object.keys(fields).forEach(function (k) {
    const f = fields[k];
    const v = meta[k] === undefined ? '' : meta[k];
    let input;
    if (f.type === 'source') {
      const opts = { mean: 'คะแนนเฉลี่ยจากผู้ประเมินทุกคน (ตรงกับที่ระบุในบทที่ 4)',
                     teacher: 'คะแนนของครูผู้สอนอย่างเดียว',
                     expert: 'คะแนนของผู้เชี่ยวชาญอย่างเดียว' };
      input = '<select class="form-select form-select-sm c45-meta" data-key="' + k + '">'
        + Object.keys(opts).map(function (o) {
            return '<option value="' + o + '"' + (o === v ? ' selected' : '') + '>' + c45Esc(opts[o]) + '</option>';
          }).join('') + '</select>';
    } else if (f.type === 'phase') {
      input = '<select class="form-select form-select-sm c45-meta" data-key="' + k + '">'
        + Object.keys(phases).map(function (p) {
            return '<option value="' + c45Esc(p) + '"' + (p === v ? ' selected' : '') + '>'
              + c45Esc(phases[p]) + '</option>';
          }).join('') + '</select>';
    } else {
      input = '<input class="form-control form-control-sm c45-meta" data-key="' + k + '"'
        + (f.type === 'number' ? ' type="number" step="any"' : '')
        + ' value="' + c45Esc(v) + '">';
    }
    h += '<div class="col-md-4 col-lg-3"><label class="form-label small fw-bold mb-1">'
      + c45Esc(f.label) + '</label>' + input + '</div>';
  });
  document.getElementById('c45MetaForm').innerHTML = h;
}

async function c45SaveMeta() {
  const payload = {};
  document.querySelectorAll('.c45-meta').forEach(function (el) { payload[el.dataset.key] = el.value; });
  const d = await c45Api({ action: 'ch45_save_meta', meta: payload });
  if (!d.success) { c45Alert(c45Esc(d.error || 'บันทึกไม่สำเร็จ'), 'danger'); return; }
  c45Alert('บันทึกข้อมูลประจำงานวิจัยแล้ว — กำลังคำนวณสถิติใหม่ตามค่าที่ตั้ง', 'success');
  await c45Load();
}

/* ---------------------------------------------------------------- บันทึกหลังสอน */
function c45PaintLogs() {
  const stages = c45Data.poa_stages;
  const sel = document.getElementById('c45LogStage');
  if (sel && !sel.options.length) {
    sel.innerHTML = Object.keys(stages).map(function (k) {
      return '<option value="' + c45Esc(k) + '">' + c45Esc(stages[k]) + '</option>';
    }).join('');
  }
  const logs = c45Data.logs || [];
  const box = document.getElementById('c45LogList');
  if (!logs.length) {
    box.innerHTML = '<div class="alert alert-warning border-0 rounded-3 small mb-0">'
      + '<i class="bi bi-exclamation-triangle-fill me-1"></i>ยังไม่มีบันทึกหลังสอน — '
      + 'หัวข้อ "ข้อเสนอแนะสำหรับการนำผลวิจัยไปใช้" ในบทที่ 5 จะเขียนไม่ได้จนกว่าจะมีข้อมูลส่วนนี้</div>';
    return;
  }
  box.innerHTML = '<div class="table-responsive"><table class="table table-sm align-middle mb-0">'
    + '<thead class="table-light"><tr><th>ขั้นของ POA</th><th>หน่วย</th><th>ปัญหาที่พบ</th>'
    + '<th>แนวทางแก้ไข</th><th class="text-end">จัดการ</th></tr></thead><tbody>'
    + logs.map(function (l) {
        return '<tr><td class="small">' + c45Esc(stages[l.poa_stage] || l.poa_stage) + '</td>'
          + '<td class="small text-center">' + (Number(l.task_unit) > 0 ? l.task_unit : '—') + '</td>'
          + '<td class="small">' + c45Esc(l.problem) + '</td>'
          + '<td class="small">' + c45Esc(l.solution || '—') + '</td>'
          + '<td class="text-end text-nowrap">'
          + '<button class="btn btn-sm btn-outline-secondary rounded-pill me-1" onclick="c45EditLog(' + l.id + ')">'
          + '<i class="bi bi-pencil"></i></button>'
          + '<button class="btn btn-sm btn-outline-danger rounded-pill" onclick="c45DeleteLog(' + l.id + ')">'
          + '<i class="bi bi-trash3"></i></button></td></tr>';
      }).join('')
    + '</tbody></table></div>';
}

function c45EditLog(id) {
  const l = (c45Data.logs || []).find(function (x) { return Number(x.id) === Number(id); });
  if (!l) return;
  document.getElementById('c45LogId').value = l.id;
  document.getElementById('c45LogStage').value = l.poa_stage;
  document.getElementById('c45LogUnit').value = l.task_unit;
  document.getElementById('c45LogProblem').value = l.problem || '';
  document.getElementById('c45LogSolution').value = l.solution || '';
  document.getElementById('c45LogEvidence').value = l.evidence || '';
  document.getElementById('c45LogBtnText').textContent = 'บันทึกการแก้ไข';
  document.getElementById('c45LogProblem').focus();
}

async function c45SaveLog() {
  const log = {
    id: Number(document.getElementById('c45LogId').value || 0),
    poa_stage: document.getElementById('c45LogStage').value,
    task_unit: Number(document.getElementById('c45LogUnit').value || 0),
    problem: document.getElementById('c45LogProblem').value.trim(),
    solution: document.getElementById('c45LogSolution').value.trim(),
    evidence: document.getElementById('c45LogEvidence').value.trim()
  };
  if (!log.problem) { c45Alert('กรุณาระบุปัญหาที่พบจริง', 'warning'); return; }
  const d = await c45Api({ action: 'ch45_save_log', log: log });
  if (!d.success) { c45Alert(c45Esc(d.error || 'บันทึกไม่สำเร็จ'), 'danger'); return; }
  c45Data.logs = d.logs || [];
  ['c45LogProblem', 'c45LogSolution', 'c45LogEvidence'].forEach(function (i) {
    document.getElementById(i).value = '';
  });
  document.getElementById('c45LogId').value = '0';
  document.getElementById('c45LogBtnText').textContent = 'เพิ่มบันทึก';
  c45PaintLogs();
  c45PaintReadinessAfterLog();
}

function c45PaintReadinessAfterLog() {
  const item = (c45Data.readiness.items || []).find(function (i) { return i.key === 'logs'; });
  if (!item) return;
  const n = (c45Data.logs || []).length;
  item.status = n >= 3 ? 'ok' : (n > 0 ? 'warn' : 'missing');
  item.detail = 'บันทึกไว้แล้ว ' + n + ' รายการ';
  const c = { ok: 0, warn: 0, missing: 0 };
  c45Data.readiness.items.forEach(function (i) { c[i.status]++; });
  c45Data.readiness.counts = c;
  c45PaintReadiness();
}

async function c45DeleteLog(id) {
  if (!confirm('ยืนยันลบบันทึกหลังสอนรายการนี้?')) return;
  const d = await c45Api({ action: 'ch45_delete_log', id: id });
  if (!d.success) { c45Alert('ลบไม่สำเร็จ', 'danger'); return; }
  c45Data.logs = d.logs || [];
  c45PaintLogs();
  c45PaintReadinessAfterLog();
}

/* ============================================================
   ส่งออกข้อมูลทั้งหมดของหน้านี้ (ความพร้อมข้อมูล, ตาราง 12, ความเที่ยงระหว่างผู้ประเมิน,
   ตาราง 14, ผลวิเคราะห์รายหัวข้อทุกหัวข้อที่วิเคราะห์แล้ว, บันทึกหลังสอน, ข้อมูลประจำงานวิจัย)
   เป็น Google Doc — ใช้ c45Data ที่โหลดไว้ในหน่วยความจำอยู่แล้ว ต่อกับ google_upload_doc.php
   ตัวเดียวกับหน้าวิเคราะห์สถิติงานวิจัยและหน้าตรวจเรียงความอัตโนมัติ
   ============================================================ */
const C45_REPORT_AUTHOR = <?php echo json_encode($sessionUser['name'] ?? 'ครูผู้สอน'); ?>;

function c45WrTable(headers, rows) {
  let h = '<table class="data"><thead><tr>';
  headers.forEach(function (x) { h += '<th>' + c45Esc(x) + '</th>'; });
  h += '</tr></thead><tbody>';
  if (!rows.length) h += '<tr><td colspan="' + headers.length + '" style="text-align:center;color:#888">— ยังไม่มีข้อมูล —</td></tr>';
  rows.forEach(function (r) {
    h += '<tr>' + r.map(function (c) { return '<td>' + (c === null || c === undefined ? '' : c45Esc(c)) + '</td>'; }).join('') + '</tr>';
  });
  return h + '</tbody></table>';
}

function buildChapter45ReportHtml() {
  const now    = new Date();
  const thDate = now.toLocaleDateString('th-TH', { year: 'numeric', month: 'long', day: 'numeric' });
  const P = [];
  const d = c45Data || {};

  // ---------- ส่วนที่ 1: ความพร้อมของข้อมูล ----------
  P.push('<h1 class="secn" id="s1">ส่วนที่ 1 ความพร้อมของข้อมูล</h1>');
  const items = (d.readiness && d.readiness.items) || [];
  P.push(c45WrTable(['รายการ', 'สถานะ', 'รายละเอียด'], items.map(function (it) {
    const statusLabel = it.status === 'ok' ? 'พร้อม' : (it.status === 'warn' ? 'ควรตรวจสอบ' : 'ยังขาด');
    return [it.label, statusLabel, it.detail || ''];
  })));

  // ---------- ส่วนที่ 2: ตาราง 12 + ความเที่ยงระหว่างผู้ประเมิน ----------
  P.push('<div class="pagebreak"></div>');
  P.push('<h1 class="secn" id="s2">ส่วนที่ 2 ตาราง 12 ผลการเปรียบเทียบก่อนเรียนและหลังเรียน</h1>');
  const q = d.quant || {};
  P.push('<p>n = ' + c45Esc(q.n) + ' คน · df = ' + c45Esc(q.df) + ' · ทดสอบด้วย Paired-samples t-test สองทาง</p>');
  P.push(c45WrTable(['ความสามารถในการเขียนเรียงความ', 'คะแนนเต็ม', 'ก่อนเรียน M', 'SD', 'หลังเรียน M', 'SD', 't', 'p', 'dz', 'ขนาดอิทธิพล'],
    (q.rows || []).map(function (r) {
      return [r.label, c45Num(r.max, 0), c45Num(r.pre_mean), c45Num(r.pre_sd), c45Num(r.post_mean), c45Num(r.post_sd),
        c45Num(r.t, 3) + (r.sig ? '*' : ''), c45P(r.p), c45Num(r.dz), r.effect];
    })));

  const n2 = q.normality && q.normality.overall;
  if (n2 && n2.W !== null) {
    P.push('<p class="analysis">การแจกแจงของคะแนนผลต่าง (Shapiro-Wilk): W = ' + c45R(n2.W) + ', p = ' + c45P(n2.p) + ' — '
      + (n2.normal ? 'ไม่แตกต่างจากการแจกแจงปกติ จึงใช้ Paired-samples t-test ได้'
                   : 'แตกต่างจากการแจกแจงปกติอย่างมีนัยสำคัญ ควรรายงานผลอย่างระมัดระวัง หรือเพิ่ม Wilcoxon signed-rank') + '</p>');
  }

  P.push('<h2>ความเที่ยงระหว่างผู้ประเมิน (ICC — Inter-rater Reliability)</h2>');
  const ir = q.interrater || {};
  const irKeys = Object.keys(ir);
  if (irKeys.length) {
    P.push('<p>ใช้ ICC แบบสองทางผสม ความสอดคล้องสัมบูรณ์ (two-way mixed effects, absolute agreement) เป็นค่าหลักในการสรุปผล '
      + 'ตามเกณฑ์แปลผลของ Koo &amp; Li (2016) — Pearson r แสดงประกอบเป็นค่าความสัมพันธ์รายคู่เท่านั้น</p>');
    P.push(c45WrTable(['รอบ', 'ผู้ประเมิน (k)', 'n', 'ICC(3,1)', 'ICC(3,k)', 'p', 'แปลผล (ยึดตาม ICC)', 'Pearson r รายคู่ (ประกอบ)'],
      irKeys.map(function (k) {
        const v = ir[k];
        return [v.label, v.k, v.n, c45R(v.icc.icc1), c45R(v.icc.iccK), c45P(v.icc.p), v.icc_label,
          v.pearson.map(function (p) { return 'r = ' + c45R(p.r); }).join(', ')];
      })));
  } else {
    P.push('<p style="color:#888">ยังคำนวณความเที่ยงระหว่างผู้ประเมินไม่ได้ — ต้องมีผู้ประเมินตั้งแต่ 2 คนขึ้นไปให้คะแนนผลงานชุดเดียวกันในรอบเดียวกัน</p>');
  }

  // ---------- ส่วนที่ 3: ตาราง 14 ----------
  P.push('<div class="pagebreak"></div>');
  P.push('<h1 class="secn" id="s3">ส่วนที่ 3 ตาราง 14 จำนวนและร้อยละของนักเรียนที่ปรากฏข้อบกพร่อง</h1>');
  const def = d.defects || {};
  P.push('<p>n = ' + c45Esc(def.n) + ' คน (ผู้ที่มีคะแนนครบทั้งสองครั้ง) · ' + c45Esc(def.rule || '') + '</p>');
  Object.keys(d.domains || {}).forEach(function (dk) {
    const dom = d.domains[dk];
    P.push('<h3>ด้าน' + c45Esc(dom.name) + '</h3>');
    P.push(c45WrTable(['ข้อบกพร่องที่พบในผลงานเรียงความ', 'ครั้งที่ 1 n', 'ครั้งที่ 1 %', 'ครั้งที่ 2 n', 'ครั้งที่ 2 %', 'เปลี่ยนแปลง'],
      (dom.indicators || []).map(function (id) {
        const r = (def.rows || {})[id];
        if (!r) return null;
        return [r.no + '. ' + r.defect, r.n1, c45Num(r.pct1, 1), r.n2, c45Num(r.pct2, 1),
          (r.diff_pct === null ? '—' : (r.diff_pct > 0 ? '+' : '') + c45Num(r.diff_pct, 1) + '%')];
      }).filter(Boolean)));
  });

  // ---------- ส่วนที่ 4: ผลวิเคราะห์รายหัวข้อ ----------
  P.push('<div class="pagebreak"></div>');
  P.push('<h1 class="secn" id="s4">ส่วนที่ 4 ผลวิเคราะห์รายหัวข้อจากระบบตรวจอัตโนมัติ</h1>');
  const jobs = d.jobs || {};
  const groups = d.job_groups || {};
  const results = d.results || {};
  const doneJobs = Object.keys(jobs).filter(function (k) { return results[k]; });
  if (!doneJobs.length) {
    P.push('<p style="color:#888">— ยังไม่เคยให้ระบบวิเคราะห์หัวข้อใดเลย —</p>');
  } else {
    Object.keys(groups).forEach(function (gk) {
      const gItems = Object.keys(jobs).filter(function (k) { return jobs[k].group === gk && results[k]; });
      if (!gItems.length) return;
      P.push('<h2>' + c45Esc(groups[gk]) + '</h2>');
      gItems.forEach(function (k) {
        const job = jobs[k];
        const res = results[k];
        P.push('<h3>' + c45Esc(job.label) + '</h3>');
        if (res.warnings && res.warnings.length) {
          P.push('<p class="analysis"><strong>ต้องตรวจสอบก่อนนำไปใช้:</strong> ' + res.warnings.map(c45Esc).join(' · ') + '</p>');
        }
        P.push(c45RenderPayload(k, res.payload));
      });
    });
    const pending = Object.keys(jobs).filter(function (k) { return !results[k]; });
    if (pending.length) {
      P.push('<p style="color:#888">หัวข้อที่ยังไม่ได้วิเคราะห์ (' + pending.length + ' หัวข้อ): '
        + pending.map(function (k) { return c45Esc(jobs[k].label); }).join(' · ') + '</p>');
    }
  }

  // ---------- ส่วนที่ 5: บันทึกหลังสอน ----------
  P.push('<div class="pagebreak"></div>');
  P.push('<h1 class="secn" id="s5">ส่วนที่ 5 บันทึกหลังสอน</h1>');
  const stages = d.poa_stages || {};
  const logs = d.logs || [];
  P.push(c45WrTable(['ขั้นของ POA', 'หน่วย', 'ปัญหาที่พบจริง', 'แนวทางแก้ไข', 'ข้อสังเกต/หลักฐานประกอบ'],
    logs.map(function (l) {
      return [stages[l.poa_stage] || l.poa_stage, (Number(l.task_unit) > 0 ? l.task_unit : '—'), l.problem, l.solution || '—', l.evidence || '—'];
    })));

  // ---------- ส่วนที่ 6: ข้อมูลประจำงานวิจัย ----------
  P.push('<div class="pagebreak"></div>');
  P.push('<h1 class="secn" id="s6">ส่วนที่ 6 ข้อมูลประจำงานวิจัย</h1>');
  const metaFields = d.meta_fields || {};
  const meta = d.meta || {};
  const phases = d.phases || {};
  P.push(c45WrTable(['รายการ', 'ค่าที่ตั้งไว้'], Object.keys(metaFields).map(function (k) {
    const f = metaFields[k];
    let v = meta[k] === undefined ? '' : meta[k];
    if (f.type === 'phase') v = phases[v] || v;
    return [f.label, v];
  })));

  const body = P.join('\n');
  const secLabels = ['ส่วนที่ 1 ความพร้อมของข้อมูล', 'ส่วนที่ 2 ตาราง 12 ผลการเปรียบเทียบก่อนเรียนและหลังเรียน',
    'ส่วนที่ 3 ตาราง 14 จำนวนและร้อยละของนักเรียนที่ปรากฏข้อบกพร่อง', 'ส่วนที่ 4 ผลวิเคราะห์รายหัวข้อจากระบบตรวจอัตโนมัติ',
    'ส่วนที่ 5 บันทึกหลังสอน', 'ส่วนที่ 6 ข้อมูลประจำงานวิจัย'];
  const toc = '<div class="toc"><h1 class="secn nonum">สารบัญ</h1>'
    + secLabels.map(function (s) { return '<div class="tocitem"><span>' + c45Esc(s) + '</span></div>'; }).join('')
    + '</div>';
  const cover = '<div class="cover">'
    + '<div class="cover-top">รายงานวิเคราะห์บทที่ 4 และบทที่ 5</div>'
    + '<div class="cover-title">ข้อมูลทั้งหมดในหน้าวิเคราะห์บทที่ 4-5</div>'
    + '<div class="cover-box">วิเคราะห์แล้ว ' + doneJobs.length + ' / ' + Object.keys(jobs).length + ' หัวข้อ</div>'
    + '<div class="cover-foot">จัดทำโดย ' + c45Esc(C45_REPORT_AUTHOR) + '<br>วันที่ ' + c45Esc(thDate) + '</div>'
    + '</div>';
  const css = '@page { size: A4; margin: 2.54cm 2.2cm; }'
    + 'body { font-family: "TH Sarabun New","Sarabun","Angsana New","Cordia New",serif; font-size: 16pt; color:#000; line-height:1.5; }'
    + 'h1.secn { font-size: 20pt; color:#4c1d95; border-bottom:2pt solid #4c1d95; padding-bottom:4pt; margin:0 0 12pt; }'
    + 'h2 { font-size: 17pt; color:#4c1d95; margin:14pt 0 6pt; }'
    + 'h3 { font-size: 16pt; color:#333; margin:10pt 0 4pt; }'
    + 'p { margin: 0 0 8pt; text-align: justify; } ul { margin: 0 0 8pt 0; }'
    + 'table.data { border-collapse: collapse; width: 100%; margin: 6pt 0 12pt; font-size: 14pt; }'
    + 'table.data th { background:#4c1d95; color:#fff; border:0.75pt solid #33455f; padding:4pt 6pt; text-align:center; }'
    + 'table.data td { border:0.75pt solid #999; padding:3pt 6pt; vertical-align:top; }'
    + 'table.table { border-collapse: collapse; width: 100%; margin: 6pt 0 12pt; font-size: 14pt; }'
    + 'table.table th, table.table td { border:0.75pt solid #999; padding:3pt 6pt; vertical-align:top; }'
    + 'table.table th { background:#f3f4f6; }'
    + 'p.analysis { background:#faf7ff; border-left:3pt solid #4c1d95; padding:6pt 10pt; margin:6pt 0 12pt; }'
    + '.pagebreak { page-break-before: always; }'
    + '.cover { text-align:center; padding-top:110pt; }'
    + '.cover-top { font-size:22pt; color:#4c1d95; letter-spacing:1pt; margin-bottom:30pt; }'
    + '.cover-title { font-size:26pt; font-weight:bold; color:#111; margin-bottom:16pt; }'
    + '.cover-box { display:inline-block; border:1.5pt solid #4c1d95; border-radius:6pt; padding:8pt 20pt; font-size:16pt; color:#4c1d95; margin-bottom:60pt; }'
    + '.cover-foot { font-size:17pt; color:#333; }'
    + '.toc .tocitem { font-size:16pt; padding:5pt 0; border-bottom:0.5pt dotted #bbb; }'
    + 'h1.nonum { text-align:center; border-bottom:none; }';
  const doc = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">'
    + '<head><meta charset="utf-8"><title>รายงานวิเคราะห์บทที่ 4-5</title>'
    + '<!--[if gte mso 9]><xml><w:WordDocument><w:View>Print</w:View><w:Zoom>100</w:Zoom><w:DoNotOptimizeForBrowser/></w:WordDocument></xml><![endif]-->'
    + '<style>' + css + '</style></head><body>'
    + cover + toc + '<div class="pagebreak"></div>' + body + '</body></html>';

  return { doc, filename: 'รายงานวิเคราะห์บทที่4-5_' + now.toISOString().slice(0, 10) };
}

async function sendChapter45ReportToGoogleDocs() {
  const btn = document.getElementById('c45ExportBtn');
  const original = btn ? btn.innerHTML : '';
  if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>กำลังจัดทำและส่งเข้า Google Docs...'; }
  try {
    if (!c45Data) { throw new Error('กรุณารอให้ข้อมูลในหน้านี้โหลดเสร็จก่อน'); }
    let status;
    try { status = await (await fetch('google_auth.php?action=status&_t=' + Date.now())).json(); }
    catch (e) { throw new Error('ยังไม่ได้ตั้งค่า Google API (google_auth.php) บนเซิร์ฟเวอร์'); }
    if (!status || !status.configured) {
      showToast('ผู้ดูแลระบบยังไม่ได้ตั้งค่า Google API (โปรดกรอก Client ID/Secret ใน google_config.php)', 'error');
      return;
    }
    if (!status.connected) {
      showToast('กำลังพาไปเชื่อมต่อบัญชี Google ครั้งแรก...', 'info');
      const ret = encodeURIComponent(location.pathname + location.hash);
      window.location.href = 'google_auth.php?action=connect&return=' + ret;
      return;
    }
    const rep = buildChapter45ReportHtml();
    const httpResp = await fetch('google_upload_doc.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ html: rep.doc, title: rep.filename })
    });
    const raw = await httpResp.text();
    let res;
    try { res = JSON.parse(raw); }
    catch (e) { throw new Error('เซิร์ฟเวอร์ตอบไม่ใช่ JSON (HTTP ' + httpResp.status + '): ' + raw.slice(0, 200)); }
    if (res.success) {
      showToast('ส่งเข้า Google Docs สำเร็จ! กำลังเปิดเอกสาร...', 'success');
      window.open(res.link, '_blank');
    } else if (res.reauth) {
      const ret = encodeURIComponent(location.pathname + location.hash);
      window.location.href = 'google_auth.php?action=connect&return=' + ret;
    } else {
      throw new Error(res.error || ('อัปโหลดไม่สำเร็จ (HTTP ' + httpResp.status + ')'));
    }
  } catch (err) {
    showToast('เกิดข้อผิดพลาด: ' + err.message, 'error');
  } finally {
    if (btn) { btn.disabled = false; btn.innerHTML = original; }
  }
}

async function loadChapter45GoogleStatus() {
  const box = document.getElementById('c45GoogleStatusBox');
  if (!box) return;
  try {
    const st = await (await fetch('google_auth.php?action=status&_t=' + Date.now())).json();
    if (!st.configured) {
      box.innerHTML = '<span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle"></i> ยังไม่ได้ตั้งค่า Google API</span>';
    } else if (st.connected) {
      box.innerHTML = '<span class="badge bg-success"><i class="bi bi-check-circle"></i> เชื่อมต่อ Google แล้ว</span>';
    } else {
      box.innerHTML = '<span class="badge bg-light text-dark"><i class="bi bi-plug"></i> ยังไม่ได้เชื่อมต่อบัญชี Google</span>';
    }
  } catch (e) { box.innerHTML = ''; }
}

/* ---------------------------------------------------------------- เริ่มทำงาน */
(async function () { await c45Load(); if (C45_IS_TEACHER) loadChapter45GoogleStatus(); })();
</script>

<?php require_once 'footer.php'; ?>
