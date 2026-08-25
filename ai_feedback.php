<?php
/**
 * ai_feedback.php — หน้า "ผู้ช่วย AI ตรวจเรียงความ"
 * ---------------------------------------------------------------------------
 * ใช้ได้ทั้งนักเรียน ครู และผู้เชี่ยวชาญ (เห็นข้อมูลต่างกันตามบทบาท)
 *   - นักเรียน  : กดให้ AI ตรวจเรียงความของตนเอง และดูข้อเสนอแนะย้อนหลังทุกรอบ
 *   - ครู       : ตรวจแทนนักเรียนคนใดก็ได้ ดูภาพรวมทั้งชั้น ลบผลที่ไม่เหมาะสม และตั้งค่า AI
 *   - ผู้เชี่ยวชาญ: ดูผลตรวจของนักเรียนได้อย่างเดียว (ไม่สั่งตรวจ ไม่ตั้งค่า)
 */
$page_title = 'ผู้ช่วย AI ตรวจเรียงความ - ระบบประเมินเรียงความ';
require_once 'auth_helper.php';
require_login();
require_once 'ai_config.php';
require_once 'header.php';

$aiRole      = $sessionUser['role'];
$aiIsTeacher = ($aiRole === 'teacher');
$aiIsStudent = ($aiRole === 'student');
$aiPhases    = ai_all_phases();
?>

<div id="view-ai-feedback" class="text-start">
  <div class="mb-3">
    <a href="index.php" class="btn btn-link text-decoration-none text-secondary fw-bold p-0">
      <i class="bi bi-arrow-left-short"></i> กลับหน้าเมนูหลัก
    </a>
  </div>

  <!-- หัวเรื่อง -->
  <div class="card border-0 shadow-lg rounded-4 overflow-hidden mb-4">
    <div class="p-4 text-white" style="background: linear-gradient(135deg, #4c1d95 0%, #6d28d9 50%, #0d7377 100%);">
      <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
        <div>
          <h4 class="fw-bold mb-1"><i class="bi bi-robot me-2"></i>ผู้ช่วย AI ตรวจเรียงความ</h4>
          <p class="text-white-50 mb-0 small">
            ให้ AI อ่านเรียงความแล้วชี้จุดแข็ง จุดที่ควรปรับปรุง และแนวทางแก้ไขตามเกณฑ์การประเมินของคุณครู
          </p>
        </div>
        <span class="badge bg-white text-dark px-3 py-2 fs-7 fw-bold">
          <i class="bi bi-person-fill me-1"></i><?php echo htmlspecialchars($sessionUser['name']); ?>
        </span>
      </div>
    </div>
    <div class="bg-light border-top px-4 py-2 small text-muted">
      <i class="bi bi-info-circle me-1"></i>
      ข้อเสนอแนะและคะแนนจาก AI เป็นเพียง<strong>แนวทางเพื่อพัฒนางานเขียน</strong>
      ไม่ใช่คะแนนจริง และไม่ถูกนำไปรวมกับคะแนนของครู เพื่อน หรือการประเมินตนเอง
    </div>
  </div>

  <!-- แถบสถานะระบบ AI -->
  <div id="aiStatusBar" class="alert border-0 rounded-3 small d-none" role="alert"></div>

<?php if ($aiIsTeacher): ?>
  <!-- ตั้งค่า AI (เฉพาะครู) -->
  <div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white border-bottom py-3 px-4 rounded-top-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
      <h6 class="fw-bold text-dark mb-0"><i class="bi bi-sliders text-primary me-2"></i>ตั้งค่าผู้ช่วย AI</h6>
      <button class="btn btn-outline-secondary btn-sm rounded-pill px-3" type="button"
              data-bs-toggle="collapse" data-bs-target="#aiSettingsBody">
        <i class="bi bi-chevron-expand me-1"></i>เปิด/ปิดการตั้งค่า
      </button>
    </div>
    <div class="collapse" id="aiSettingsBody">
      <div class="card-body p-4">
        <div class="alert alert-primary border-0 rounded-3 small">
          <i class="bi bi-key-fill me-1"></i>
          ต้องมี <strong>API key</strong> ของผู้ให้บริการ AI ก่อนจึงจะใช้งานได้ —
          มีหลายเจ้าที่<strong>ให้ใช้ฟรี</strong> ดูวิธีขอทีละขั้นได้ในไฟล์ <code>AI_SETUP.md</code>
        </div>

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-bold small">ผู้ให้บริการ AI</label>
            <select id="aiProvider" class="form-select border-2 rounded-3" onchange="onProviderChange()"></select>
            <div class="form-text small" id="aiProviderHint"></div>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold small">ชื่อโมเดล</label>
            <div class="input-group">
              <input type="text" id="aiModel" class="form-control border-2 rounded-start-3" placeholder="เช่น gemini-3.6-flash">
              <button class="btn btn-outline-secondary rounded-end-3" type="button" onclick="useDefaultModel()"
                      title="ล้างช่องนี้เพื่อกลับไปใช้โมเดลเริ่มต้นของผู้ให้บริการ">
                <i class="bi bi-arrow-counterclockwise"></i> ใช้ค่าเริ่มต้น
              </button>
            </div>
            <div class="form-text small">
              เว้นว่างไว้เพื่อใช้โมเดลเริ่มต้นของผู้ให้บริการ (แนะนำ — ระบบจะตามรุ่นใหม่ให้เองเมื่อผู้ให้บริการเลิกใช้รุ่นเก่า)
            </div>
          </div>
          <div class="col-md-8">
            <label class="form-label fw-bold small">API key</label>
            <input type="password" id="aiApiKey" class="form-control border-2 rounded-3" autocomplete="off"
                   placeholder="วาง API key ที่นี่ (เว้นว่างไว้ = ใช้คีย์เดิม)">
            <div class="form-text small" id="aiKeyHint"></div>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-bold small">เว็บที่ให้บริการ (Base URL)</label>
            <input type="text" id="aiBaseUrl" class="form-control border-2 rounded-3" placeholder="ใช้ค่าเริ่มต้น">
          </div>
        </div>

        <hr class="my-4">

        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" id="aiEnabled">
          <label class="form-check-label fw-bold small" for="aiEnabled">เปิดใช้งานผู้ช่วย AI</label>
        </div>
        <div class="form-text small">
          ปิดสวิตช์นี้เมื่อไม่ต้องการให้สั่งตรวจเพิ่ม — ผลตรวจที่บันทึกไว้แล้วยังแสดงให้นักเรียนดูได้ตามปกติ
        </div>
        <div class="alert alert-secondary border-0 rounded-3 small mt-3 mb-0">
          <i class="bi bi-person-lock me-1"></i>
          <strong>คุณครูเป็นผู้สั่งตรวจเพียงผู้เดียว</strong> — นักเรียนกดให้ AI ตรวจเองไม่ได้
          เห็นได้เฉพาะผลที่คุณครูตรวจให้แล้วเท่านั้น
        </div>

        <div class="d-flex justify-content-end gap-2 mt-4">
          <button class="btn btn-outline-danger rounded-pill px-3" onclick="clearApiKey()">
            <i class="bi bi-trash me-1"></i>ลบ API key
          </button>
          <button class="btn btn-primary rounded-pill px-4 fw-bold" id="aiSaveSettingsBtn" onclick="saveAiSettings()">
            <i class="bi bi-check2-circle me-1"></i>บันทึกการตั้งค่า
          </button>
        </div>

        <div id="aiUsageBox" class="mt-4 small text-muted"></div>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php if ($aiIsTeacher): ?>
  <!-- ตรวจทั้งรอบรวดเดียว (ครูเท่านั้น) -->
  <div class="card border-0 shadow-sm rounded-4 mb-4" style="border-top:4px solid #6d28d9 !important;">
    <div class="card-header bg-white border-bottom py-3 px-4 rounded-top-4">
      <h6 class="fw-bold text-dark mb-0">
        <i class="bi bi-lightning-charge-fill text-warning me-2"></i>ตรวจทั้งรอบรวดเดียว
      </h6>
      <div class="text-muted small mt-1">
        เลือกรอบงานแล้วกดครั้งเดียว ระบบจะไล่ตรวจเรียงความของนักเรียนทีละฉบับจนครบ
      </div>
    </div>
    <div class="card-body p-4">
      <div class="row g-3 align-items-end">
        <div class="col-md-4">
          <label class="form-label fw-bold small">รอบงานที่จะตรวจ</label>
          <select id="batchPhase" class="form-select border-2 rounded-3" onchange="loadBatchTargets()">
            <?php foreach ($aiPhases as $ph): ?>
            <option value="<?php echo $ph; ?>"><?php echo htmlspecialchars(ai_phase_label($ph)); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-bold small">ห้องเรียน</label>
          <select id="batchRoom" class="form-select border-2 rounded-3" onchange="loadBatchTargets()">
            <option value="">ทุกห้องเรียน</option>
          </select>
        </div>
        <div class="col-md-5">
          <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" id="batchSkipDone" checked onchange="paintBatchSummary()">
            <label class="form-check-label small" for="batchSkipDone">
              ข้ามฉบับที่เคยตรวจแล้ว <span class="text-muted">(ประหยัดโควตา)</span>
            </label>
          </div>
          <div class="d-flex gap-2">
            <button id="batchStartBtn" class="btn fw-bold rounded-pill px-4 text-white flex-grow-1"
                    style="background:linear-gradient(135deg,#6d28d9,#0d7377);" onclick="startBatchReview()">
              <i class="bi bi-play-fill me-1"></i>เริ่มตรวจทั้งรอบ
            </button>
            <button id="batchStopBtn" class="btn btn-outline-danger rounded-pill px-3 d-none" onclick="stopBatchReview()">
              <i class="bi bi-stop-fill me-1"></i>หยุด
            </button>
          </div>
        </div>
      </div>

      <div id="batchSummary" class="mt-3 small text-muted">กำลังโหลดรายการ...</div>

      <div id="batchProgressWrap" class="mt-3 d-none">
        <div class="d-flex justify-content-between small fw-bold mb-1">
          <span id="batchProgressLabel">กำลังตรวจ...</span>
          <span id="batchProgressCount">0 / 0</span>
        </div>
        <div class="progress" style="height:10px;">
          <div id="batchProgressBar" class="progress-bar" role="progressbar"
               style="width:0%; background:linear-gradient(90deg,#6d28d9,#0d7377);"></div>
        </div>
      </div>

      <div id="batchLog" class="mt-3 d-none border rounded-3" style="max-height:320px; overflow:auto;"></div>
    </div>
  </div>
<?php endif; ?>

  <!-- เลือกงานที่จะตรวจ / ดูผล -->
  <div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
      <div class="row g-3 align-items-end">
<?php if (!$aiIsStudent): ?>
        <div class="col-md-5">
          <label class="form-label fw-bold small">นักเรียน</label>
          <select id="aiStudentSelect" class="form-select border-2 rounded-3" onchange="onSelectionChange()">
            <option value="">— กำลังโหลดรายชื่อ —</option>
          </select>
        </div>
<?php endif; ?>
        <div class="col-md-<?php echo $aiIsStudent ? '12' : ($aiIsTeacher ? '4' : '7'); ?>">
          <label class="form-label fw-bold small">รอบงาน</label>
          <select id="aiPhaseSelect" class="form-select border-2 rounded-3" onchange="onSelectionChange()">
            <?php foreach ($aiPhases as $ph): ?>
            <option value="<?php echo $ph; ?>"><?php echo htmlspecialchars(ai_phase_label($ph)); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
<?php if ($aiIsTeacher): ?>
        <div class="col-md-3 d-grid">
          <button id="aiReviewBtn" class="btn btn-lg fw-bold rounded-pill shadow-sm text-white"
                  style="background: linear-gradient(135deg,#6d28d9,#0d7377);" onclick="runAiReview()">
            <i class="bi bi-stars me-1"></i>ตรวจฉบับนี้
          </button>
        </div>
<?php endif; ?>
      </div>
      <div id="aiQuotaText" class="text-muted small mt-3"></div>
    </div>
  </div>

  <!-- ผลการตรวจ -->
  <div id="aiFeedbackPanel">
    <div class="text-center text-muted py-5">
      <i class="bi bi-hourglass-split fs-3 d-block mb-2"></i>กำลังโหลด...
    </div>
  </div>

<?php if (!$aiIsStudent): ?>
  <!-- ภาพรวมทั้งชั้น (ครู/ผู้เชี่ยวชาญ) -->
  <div class="card border-0 shadow-sm rounded-4 mt-4">
    <div class="card-header bg-white border-bottom py-3 px-4 rounded-top-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
      <h6 class="fw-bold text-dark mb-0"><i class="bi bi-table text-secondary me-2"></i>ภาพรวมผลตรวจ AI ทั้งชั้น</h6>
      <button class="btn btn-outline-secondary btn-sm rounded-pill" onclick="loadAiOverview()">
        <i class="bi bi-arrow-clockwise me-1"></i>รีเฟรช
      </button>
    </div>
    <div class="card-body p-0">
      <div id="aiOverviewBox" style="max-height:520px; overflow:auto;">
        <div class="text-center text-muted py-5"><i class="bi bi-hourglass-split me-2"></i>กำลังโหลด...</div>
      </div>
    </div>
  </div>
<?php endif; ?>
</div>

<script src="ai_review.js"></script>
<script>
const AI_ROLE       = <?php echo json_encode($aiRole); ?>;
const AI_IS_STUDENT = <?php echo $aiIsStudent ? 'true' : 'false'; ?>;
const AI_IS_TEACHER = <?php echo $aiIsTeacher ? 'true' : 'false'; ?>;
const AI_MY_ID      = <?php echo json_encode($sessionUser['id']); ?>;

let aiStatus    = null;   // สถานะฟีเจอร์ AI ของผู้ใช้คนนี้
let aiProviders = [];     // รายชื่อผู้ให้บริการ (เฉพาะครู)

// ป้ายชื่อรอบงานและตัวหนีอักขระ ใช้ของกลางจาก ai_review.js
const AI_PHASE_LABELS = AI_PHASE_LABEL_MAP;
const esc = aiEsc;

function currentStudentId() {
  if (AI_IS_STUDENT) return AI_MY_ID;
  const el = document.getElementById('aiStudentSelect');
  return el ? el.value : '';
}
function currentPhase() {
  return document.getElementById('aiPhaseSelect').value;
}

// ---------------------------------------------------------------- สถานะระบบ
async function loadAiStatus() {
  const st = await aiGetStatus();
  if (st) aiStatus = st;
  paintStatusBar();
  updateReviewButton();
}

function paintStatusBar() {
  const bar = document.getElementById('aiStatusBar');
  if (!aiStatus) { bar.classList.add('d-none'); return; }

  let cls = 'alert-success', html = '';
  if (!aiStatus.enabled) {
    cls = 'alert-secondary';
    html = '<i class="bi bi-pause-circle me-1"></i>คุณครูปิดการใช้งานผู้ช่วย AI ไว้ชั่วคราว — ยังดูผลตรวจเดิมที่เคยบันทึกไว้ได้';
  } else if (!aiStatus.configured) {
    cls = 'alert-warning';
    html = AI_IS_TEACHER
      ? '<i class="bi bi-exclamation-triangle me-1"></i>ยังไม่ได้ตั้งค่า API key — กดปุ่ม "เปิด/ปิดการตั้งค่า" ด้านล่างเพื่อใส่คีย์ก่อนใช้งาน'
      : '<i class="bi bi-exclamation-triangle me-1"></i>ระบบ AI ยังไม่พร้อมใช้งาน กรุณาแจ้งคุณครูให้ตั้งค่าก่อน';
  } else if (!AI_IS_TEACHER) {
    cls = 'alert-info';
    html = '<i class="bi bi-eye me-1"></i>หน้านี้แสดงผลตรวจที่คุณครูให้ AI ตรวจไว้แล้ว — เลือกรอบงานด้านล่างเพื่อดู';
  } else {
    html = '<i class="bi bi-check-circle me-1"></i>ผู้ช่วย AI พร้อมใช้งาน';
  }
  bar.className = 'alert border-0 rounded-3 small ' + cls;
  bar.innerHTML = html;
  bar.classList.remove('d-none');
}

// ปุ่ม "ให้ AI ตรวจ" ใช้ได้หรือไม่ ขึ้นกับสิทธิ์ + รอบที่เลือก + โควตาคงเหลือ
function updateReviewButton() {
  const btn   = document.getElementById('aiReviewBtn');
  const quota = document.getElementById('aiQuotaText');
  if (!btn) {
    // นักเรียน/ผู้เชี่ยวชาญไม่มีปุ่มตรวจ — ไม่ต้องแสดงข้อความโควตา
    if (quota) quota.textContent = '';
    return;
  }

  if (!aiStatus) { btn.disabled = true; return; }

  let reason = '';
  if (!aiStatus.can_review) {
    reason = aiStatus.enabled ? 'ยังไม่ได้ตั้งค่า API key' : 'ผู้ช่วย AI ถูกปิดใช้งานอยู่';
  } else if (aiStatus.quota_left <= 0) {
    reason = 'วันนี้ใช้โควตาครบแล้ว (' + aiStatus.quota_limit + ' ครั้ง/วัน)';
  } else if (!currentStudentId()) {
    reason = 'กรุณาเลือกนักเรียนก่อน';
  }

  btn.disabled = (reason !== '');
  btn.title = reason || 'ส่งเรียงความรอบนี้ให้ AI ตรวจ';
  quota.innerHTML = reason
    ? '<i class="bi bi-info-circle me-1"></i>' + esc(reason)
    : '<i class="bi bi-battery-half me-1"></i>วันนี้ใช้ไปแล้ว ' + aiStatus.quota_used + ' จาก ' + aiStatus.quota_limit
      + ' ครั้ง · เรียงความต้องยาวอย่างน้อย ' + aiStatus.min_words + ' คำ';
}

// ------------------------------------------------------------ ดึง/แสดงผลตรวจ
async function loadFeedback() {
  const panel = document.getElementById('aiFeedbackPanel');
  const sid   = currentStudentId();
  const phase = currentPhase();
  if (!sid) {
    panel.innerHTML = emptyBox('เลือกนักเรียนเพื่อดูผลตรวจของ AI');
    return;
  }
  panel.innerHTML = `<div class="card border-0 shadow-sm rounded-4"><div class="card-body">${aiLoadingHTML('กำลังโหลดผลตรวจ...')}</div></div>`;
  const fb = await aiGetFeedback(sid, phase);
  if (!fb) {
    panel.innerHTML = emptyBox('ยังไม่มีผลตรวจของ AI สำหรับรอบงานนี้ — กดปุ่ม "ให้ AI ตรวจ" ด้านบนเพื่อเริ่ม');
    return;
  }
  renderFeedback(fb);
}

function emptyBox(msg) {
  return `<div class="card border-0 shadow-sm rounded-4"><div class="card-body">${aiEmptyHTML(msg)}</div></div>`;
}

// วาดผลตรวจลงในการ์ด (ครูเห็นปุ่มลบด้วย)
function renderFeedback(fb) {
  document.getElementById('aiFeedbackPanel').innerHTML =
    `<div class="card border-0 shadow-sm rounded-4 mb-4"><div class="card-body p-4">`
    + aiFeedbackHTML(fb, { deleteAction: AI_IS_TEACHER ? 'deleteFeedback()' : '' })
    + `</div></div>`;
}

// -------------------------------------------------------------- สั่งให้ตรวจ
async function runAiReview() {
  const btn   = document.getElementById('aiReviewBtn');
  const sid   = currentStudentId();
  const phase = currentPhase();
  if (!sid) { showToast('กรุณาเลือกนักเรียนก่อน', 'error'); return; }

  const original = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>AI กำลังอ่าน...';
  document.getElementById('aiFeedbackPanel').innerHTML =
    `<div class="card border-0 shadow-sm rounded-4"><div class="card-body">`
    + aiLoadingHTML('AI กำลังอ่านเรียงความและเขียนข้อเสนอแนะ', 'ปกติใช้เวลาประมาณ 15-40 วินาที กรุณาอย่าปิดหน้านี้')
    + `</div></div>`;

  try {
    const data = await aiRequestReview(sid, phase);
    if (!data.success) {
      showToast(data.error || 'ตรวจไม่สำเร็จ', 'error');
      document.getElementById('aiFeedbackPanel').innerHTML = aiErrorHTML(data.error || 'ตรวจไม่สำเร็จ');
      return;
    }
    if (aiStatus) {
      aiStatus.quota_left = data.quota_left;
      aiStatus.quota_used = aiStatus.quota_limit - data.quota_left;
    }
    renderFeedback(data.feedback);
    showToast('AI ตรวจเรียงความเรียบร้อยแล้ว');
    if (!AI_IS_STUDENT) loadAiOverview();
  } finally {
    btn.innerHTML = original;
    updateReviewButton();
  }
}

async function deleteFeedback() {
  if (!confirm('ยืนยันลบผลตรวจของ AI สำหรับเรียงความฉบับนี้?')) return;
  try {
    const res = await fetch('api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'delete_ai_feedback', student_id: currentStudentId(), essay_phase: currentPhase() })
    });
    const data = await res.json();
    if (!data.success) { showToast(data.error || 'ลบไม่สำเร็จ', 'error'); return; }
    showToast('ลบผลตรวจเรียบร้อยแล้ว');
    loadFeedback();
    loadAiOverview();
  } catch (err) {
    showToast('เชื่อมต่อไม่สำเร็จ', 'error');
  }
}

function onSelectionChange() {
  updateReviewButton();
  loadFeedback();
}

// ------------------------------------------------- ภาพรวมทั้งชั้น (ครู/ผชช.)
async function loadAiOverview() {
  const box = document.getElementById('aiOverviewBox');
  if (!box) return;
  try {
    const res  = await fetch('api.php?action=get_all_ai_feedback');
    const data = await res.json();
    if (!data.success) { box.innerHTML = `<div class="text-center text-muted py-4">${esc(data.error)}</div>`; return; }
    if (!data.list.length) {
      box.innerHTML = '<div class="text-center text-muted py-5"><i class="bi bi-inbox fs-3 d-block mb-2"></i>ยังไม่มีผลตรวจของ AI</div>';
      return;
    }
    const rows = data.list.map(r => `
      <tr style="cursor:pointer;" onclick="jumpTo('${esc(r.student_id)}','${esc(r.essay_phase)}')">
        <td class="fw-semibold text-nowrap">${esc(r.student_id)}</td>
        <td>${esc(r.student_name)}${r.classroom ? ` <span class="badge bg-info-subtle text-info-emphasis small">ห้อง ${esc(r.classroom)}</span>` : ''}</td>
        <td class="small text-nowrap">${esc(r.phase_label)}</td>
        <td class="text-center fw-bold text-nowrap">${r.total_score} <span class="text-muted fw-normal">/ ${r.max_score}</span></td>
        <td class="text-center small text-nowrap">${esc(r.quality_level || '-')}</td>
        <td class="small text-muted text-nowrap">${esc(String(r.updated_at || '').slice(0, 16))}</td>
      </tr>`).join('');
    box.innerHTML = `
      <table class="table table-hover table-bordered align-middle mb-0">
        <thead class="table-light" style="position:sticky; top:0; z-index:1;">
          <tr>
            <th class="text-nowrap">รหัส</th><th>ชื่อสกุล</th><th class="text-nowrap">รอบงาน</th>
            <th class="text-center text-nowrap">คะแนน AI</th><th class="text-center text-nowrap">ระดับ</th><th class="text-nowrap">ตรวจเมื่อ</th>
          </tr>
        </thead>
        <tbody>${rows}</tbody>
      </table>`;
  } catch (err) {
    box.innerHTML = '<div class="text-center text-muted py-4">โหลดข้อมูลไม่สำเร็จ</div>';
  }
}

function jumpTo(sid, phase) {
  const sSel = document.getElementById('aiStudentSelect');
  if (sSel) sSel.value = sid;
  document.getElementById('aiPhaseSelect').value = phase;
  onSelectionChange();
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

async function loadStudents() {
  const sel = document.getElementById('aiStudentSelect');
  if (!sel) return;
  try {
    const res  = await fetch('api.php?action=get_students_list');
    const data = await res.json();
    if (!data.success) { sel.innerHTML = '<option value="">โหลดรายชื่อไม่สำเร็จ</option>'; return; }
    const opts = Object.keys(data.students).map(id =>
      `<option value="${esc(id)}">${esc(id)} — ${esc(data.students[id])}</option>`).join('');
    sel.innerHTML = '<option value="">— เลือกนักเรียน —</option>' + opts;
  } catch (err) {
    sel.innerHTML = '<option value="">โหลดรายชื่อไม่สำเร็จ</option>';
  }
}

<?php if ($aiIsTeacher): ?>
// --------------------------------------------- ตรวจทั้งรอบรวดเดียว (ครู)
// ตรวจทีละฉบับจากฝั่งหน้าเว็บ ไม่ยิงรวดเดียวไปที่เซิร์ฟเวอร์ เพราะ PHP บนโฮสต์มีเพดาน
// เวลาทำงาน และการเว้นจังหวะระหว่างฉบับช่วยไม่ให้ชนลิมิต "จำนวนคำขอต่อนาที" ของผู้ให้บริการ
let batchTargets = [];      // รายการเรียงความในรอบที่เลือก
let batchRunning = false;   // กำลังตรวจอยู่หรือไม่
let batchStopRequested = false;

const BATCH_GAP_MS       = 2000;   // เว้นจังหวะระหว่างฉบับ
const BATCH_RATELIMIT_MS = 30000;  // ถ้าโดนจำกัดอัตราคำขอ ให้พักนานขึ้นแล้วลองใหม่ 1 ครั้ง

const sleep = ms => new Promise(r => setTimeout(r, ms));

// รายชื่อห้องเรียน (ใช้กรองก่อนตรวจ เผื่อครูอยากตรวจทีละห้อง)
async function loadBatchRooms() {
  try {
    const res  = await fetch('api.php?action=get_students_full');
    const data = await res.json();
    if (!data.success || !Array.isArray(data.students)) return;
    const rooms = [...new Set(data.students.map(s => s.classroom).filter(Boolean))].sort();
    const sel = document.getElementById('batchRoom');
    sel.innerHTML = '<option value="">ทุกห้องเรียน</option>'
      + rooms.map(r => `<option value="${esc(r)}">ห้อง ${esc(r)}</option>`).join('');
  } catch (err) { /* ไม่มีตัวกรองห้องก็ยังตรวจทั้งรอบได้ */ }
}

async function loadBatchTargets() {
  const box = document.getElementById('batchSummary');
  box.textContent = 'กำลังโหลดรายการ...';
  try {
    const params = new URLSearchParams({
      action: 'get_ai_batch_targets',
      essay_phase: document.getElementById('batchPhase').value
    });
    const room = document.getElementById('batchRoom').value;
    if (room) params.set('classroom', room);

    const res  = await fetch('api.php?' + params.toString());
    const data = await res.json();
    if (!data.success) { box.textContent = data.error || 'โหลดรายการไม่สำเร็จ'; batchTargets = []; return; }
    batchTargets = data.targets || [];
    batchTargets.__tooShort = data.too_short || 0;
    batchTargets.__minWords = data.min_words || 0;
    paintBatchSummary();
  } catch (err) {
    box.textContent = 'โหลดรายการไม่สำเร็จ';
    batchTargets = [];
  }
}

// รายการที่จะตรวจจริงในรอบนี้ (ขึ้นกับว่าติ๊ก "ข้ามฉบับที่เคยตรวจแล้ว" ไว้ไหม)
function batchQueue() {
  const skipDone = document.getElementById('batchSkipDone').checked;
  return skipDone ? batchTargets.filter(t => !t.reviewed) : batchTargets.slice();
}

function paintBatchSummary() {
  const box = document.getElementById('batchSummary');
  const btn = document.getElementById('batchStartBtn');
  if (!batchTargets.length) {
    box.innerHTML = '<i class="bi bi-inbox me-1"></i>ยังไม่มีเรียงความที่ส่งเข้ามาในรอบนี้';
    btn.disabled = true;
    return;
  }
  const queue = batchQueue();
  const done  = batchTargets.filter(t => t.reviewed).length;
  const tooShort = batchTargets.__tooShort || 0;

  // ประเมินเวลาคร่าว ๆ: AI ใช้เวลาราว 25 วินาทีต่อฉบับ บวกจังหวะพักระหว่างฉบับ
  const mins = Math.max(1, Math.round(queue.length * (25000 + BATCH_GAP_MS) / 60000));

  let html = `<i class="bi bi-list-check me-1"></i>ส่งแล้ว <strong>${batchTargets.length}</strong> ฉบับ`
    + ` · ตรวจไปแล้ว <strong>${done}</strong> · <strong class="text-primary">จะตรวจรอบนี้ ${queue.length} ฉบับ</strong>`;
  if (tooShort > 0) {
    html += `<br><i class="bi bi-exclamation-triangle text-warning me-1"></i>`
      + `ข้าม ${tooShort} ฉบับที่สั้นกว่า ${batchTargets.__minWords} คำ (ระบบไม่ส่งให้ AI ตรวจ)`;
  }
  if (queue.length > 0) {
    html += `<br><i class="bi bi-clock me-1"></i>ใช้เวลาประมาณ ${mins} นาที — เปิดหน้านี้ค้างไว้จนกว่าจะเสร็จ`;
    if (aiStatus && aiStatus.quota_left < queue.length) {
      html += `<br><i class="bi bi-battery-low text-danger me-1"></i>`
        + `<strong class="text-danger">โควตาวันนี้เหลือ ${aiStatus.quota_left} ครั้ง ไม่พอตรวจครบ</strong> — ระบบจะตรวจเท่าที่โควตาเหลือ`;
    }
  }
  box.innerHTML = html;
  btn.disabled = (queue.length === 0);
}

function batchLogLine(icon, cls, name, msg) {
  const log = document.getElementById('batchLog');
  log.classList.remove('d-none');
  const row = document.createElement('div');
  row.className = 'px-3 py-2 border-bottom small d-flex gap-2 align-items-start';
  row.innerHTML = `<i class="bi ${icon} ${cls} mt-1"></i>`
    + `<span class="flex-grow-1"><strong>${esc(name)}</strong>`
    + (msg ? ` <span class="text-muted">— ${esc(msg)}</span>` : '') + '</span>';
  log.appendChild(row);
  log.scrollTop = log.scrollHeight;
}

function setBatchProgress(done, total, label) {
  document.getElementById('batchProgressWrap').classList.remove('d-none');
  document.getElementById('batchProgressCount').textContent = done + ' / ' + total;
  document.getElementById('batchProgressLabel').textContent = label;
  const pct = total > 0 ? Math.round((done / total) * 100) : 0;
  document.getElementById('batchProgressBar').style.width = pct + '%';
}

function stopBatchReview() {
  batchStopRequested = true;
  document.getElementById('batchProgressLabel').textContent = 'กำลังหยุดหลังตรวจฉบับปัจจุบันเสร็จ...';
}

async function startBatchReview() {
  if (batchRunning) return;
  const queue = batchQueue();
  if (!queue.length) return;

  const phaseLabel = AI_PHASE_LABELS[document.getElementById('batchPhase').value] || '';
  if (!confirm(`เริ่มให้ AI ตรวจ ${queue.length} ฉบับของ "${phaseLabel}" ใช่ไหม?\n\n`
      + `ใช้เวลาประมาณ ${Math.max(1, Math.round(queue.length * 27000 / 60000))} นาที `
      + `กรุณาเปิดหน้านี้ค้างไว้จนกว่าจะเสร็จ`)) return;

  batchRunning = true;
  batchStopRequested = false;
  document.getElementById('batchStartBtn').disabled = true;
  document.getElementById('batchStopBtn').classList.remove('d-none');
  document.getElementById('batchPhase').disabled = true;
  document.getElementById('batchRoom').disabled = true;
  document.getElementById('batchLog').innerHTML = '';

  let ok = 0, failed = 0, i = 0;
  try {
    for (const t of queue) {
      if (batchStopRequested) { batchLogLine('bi-stop-circle', 'text-secondary', 'หยุดตามคำสั่ง', `ตรวจไปแล้ว ${i} ฉบับ`); break; }

      setBatchProgress(i, queue.length, `กำลังตรวจ: ${t.student_name}`);

      let data = await aiRequestReview(t.student_id, document.getElementById('batchPhase').value);

      // โดนจำกัดจำนวนคำขอต่อนาที → พักแล้วลองใหม่อีกครั้งเดียว
      if (!data.success && /โควตาฟรีของผู้ให้บริการ|429/.test(data.error || '')) {
        batchLogLine('bi-hourglass-split', 'text-warning', t.student_name, 'ผู้ให้บริการจำกัดอัตราคำขอ กำลังพักแล้วลองใหม่');
        await sleep(BATCH_RATELIMIT_MS);
        if (batchStopRequested) break;
        data = await aiRequestReview(t.student_id, document.getElementById('batchPhase').value);
      }

      i++;
      if (data.success) {
        ok++;
        t.reviewed = true;
        const fb = data.feedback || {};
        batchLogLine('bi-check-circle-fill', 'text-success', t.student_name,
          `${fb.total_score}/${fb.max_score} · ${fb.quality_level || '-'}`);
        if (typeof data.quota_left === 'number' && aiStatus) {
          aiStatus.quota_left = data.quota_left;
          aiStatus.quota_used = aiStatus.quota_limit - data.quota_left;
        }
      } else {
        failed++;
        batchLogLine('bi-x-circle-fill', 'text-danger', t.student_name, data.error || 'ตรวจไม่สำเร็จ');
        // โควตารายวันหมด = ตรวจต่อไปก็ไม่ผ่าน หยุดทั้งชุดเลยดีกว่าปล่อยให้พังทีละฉบับ
        if (/ใช้ AI ตรวจครบ/.test(data.error || '')) {
          batchLogLine('bi-battery', 'text-danger', 'หยุดอัตโนมัติ', 'โควตารายวันหมดแล้ว');
          break;
        }
      }

      setBatchProgress(i, queue.length, `ตรวจแล้ว ${i} จาก ${queue.length} ฉบับ`);
      if (i < queue.length && !batchStopRequested) await sleep(BATCH_GAP_MS);
    }
  } finally {
    batchRunning = false;
    document.getElementById('batchStopBtn').classList.add('d-none');
    document.getElementById('batchPhase').disabled = false;
    document.getElementById('batchRoom').disabled = false;
    document.getElementById('batchProgressLabel').textContent =
      `เสร็จสิ้น — สำเร็จ ${ok} ฉบับ` + (failed ? ` · ไม่สำเร็จ ${failed} ฉบับ` : '');
    showToast(`ตรวจเสร็จแล้ว: สำเร็จ ${ok} ฉบับ` + (failed ? `, ไม่สำเร็จ ${failed} ฉบับ` : ''),
      failed ? 'error' : 'success');
    paintBatchSummary();
    updateReviewButton();
    loadAiOverview();
    loadFeedback();
  }
}

// ------------------------------------------------------- ตั้งค่า AI (ครู)
async function loadAiSettings() {
  try {
    const res  = await fetch('api.php?action=get_ai_settings');
    const data = await res.json();
    if (!data.success) { showToast(data.error || 'โหลดการตั้งค่าไม่สำเร็จ', 'error'); return; }
    aiProviders = data.providers;

    const sel = document.getElementById('aiProvider');
    sel.innerHTML = data.providers.map(p =>
      `<option value="${esc(p.key)}">${esc(p.label)}</option>`).join('');
    sel.value = data.settings.provider;

    document.getElementById('aiModel').value    = data.settings.model || '';
    document.getElementById('aiBaseUrl').value  = data.settings.base_url || '';
    document.getElementById('aiEnabled').checked = !!data.settings.enabled;

    const hint = document.getElementById('aiKeyHint');
    if (data.settings.locked_by_file) {
      hint.innerHTML = '<span class="text-success"><i class="bi bi-shield-lock me-1"></i>ใช้คีย์จากไฟล์ ai_secrets.php บนเซิร์ฟเวอร์ '
        + '(' + esc(data.settings.api_key_masked) + ') — ค่าที่กรอกในหน้านี้จะไม่ถูกใช้</span>';
    } else if (data.settings.has_key) {
      hint.innerHTML = 'มีคีย์บันทึกไว้แล้ว: <code>' + esc(data.settings.api_key_masked) + '</code> · เว้นว่างไว้ = ใช้คีย์เดิม';
    } else {
      hint.textContent = 'ยังไม่มี API key ในระบบ';
    }

    onProviderChange(true);
    renderUsage(data.usage);
  } catch (err) {
    console.error(err);
  }
}

function onProviderChange(initial) {
  const key = document.getElementById('aiProvider').value;
  const p   = aiProviders.find(x => x.key === key);
  const hint = document.getElementById('aiProviderHint');
  if (!p) { hint.textContent = ''; return; }
  hint.innerHTML = p.key_url
    ? `ขอ API key ฟรีได้ที่ <a href="${esc(p.key_url)}" target="_blank" rel="noopener">${esc(p.key_url)}</a>`
    : 'กรอก Base URL ของเซิร์ฟเวอร์เองในช่องด้านขวา';
  // เปลี่ยนผู้ให้บริการ = เสนอโมเดล/URL เริ่มต้นของเจ้านั้นให้ (ไม่ทับตอนโหลดหน้าครั้งแรก)
  if (!initial) {
    document.getElementById('aiModel').value   = p.default_model || '';
    document.getElementById('aiBaseUrl').value = p.default_base_url || '';
  }
}

// ล้างชื่อโมเดลที่บันทึกไว้ เพื่อกลับไปใช้ค่าเริ่มต้นของผู้ให้บริการที่กำหนดไว้ในโค้ด
// (ใช้เมื่อผู้ให้บริการเลิกให้บริการโมเดลรุ่นเดิม แล้วระบบขึ้นว่า "ไม่พบโมเดลที่ตั้งค่าไว้")
function useDefaultModel() {
  document.getElementById('aiModel').value = '';
  showToast('ล้างชื่อโมเดลแล้ว — กด "บันทึกการตั้งค่า" เพื่อใช้โมเดลเริ่มต้นของผู้ให้บริการ');
}

function renderUsage(usage) {
  const box = document.getElementById('aiUsageBox');
  if (!usage || !usage.length) { box.innerHTML = '<i class="bi bi-graph-up me-1"></i>ยังไม่มีการเรียกใช้ AI'; return; }
  const rows = usage.map(u =>
    `<tr><td>${esc(u.d)}</td><td class="text-center">${u.total}</td><td class="text-center text-success">${u.ok}</td>
     <td class="text-center text-danger">${u.total - u.ok}</td></tr>`).join('');
  box.innerHTML = `<div class="fw-bold mb-2"><i class="bi bi-graph-up me-1"></i>การเรียกใช้ AI ย้อนหลัง 7 วัน</div>
    <table class="table table-sm table-bordered mb-0" style="max-width:420px;">
      <thead class="table-light"><tr><th>วันที่</th><th class="text-center">รวม</th><th class="text-center">สำเร็จ</th><th class="text-center">ไม่สำเร็จ</th></tr></thead>
      <tbody>${rows}</tbody></table>`;
}

async function saveAiSettings() {
  const btn = document.getElementById('aiSaveSettingsBtn');
  btn.disabled = true;
  try {
    const res = await fetch('api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        action: 'save_ai_settings',
        provider: document.getElementById('aiProvider').value,
        model: document.getElementById('aiModel').value.trim(),
        base_url: document.getElementById('aiBaseUrl').value.trim(),
        api_key: document.getElementById('aiApiKey').value.trim(),
        enabled: document.getElementById('aiEnabled').checked
      })
    });
    const data = await res.json();
    if (!data.success) { showToast(data.error || 'บันทึกไม่สำเร็จ', 'error'); return; }
    document.getElementById('aiApiKey').value = '';
    showToast('บันทึกการตั้งค่าเรียบร้อยแล้ว');
    await loadAiSettings();
    await loadAiStatus();
  } catch (err) {
    showToast('เชื่อมต่อไม่สำเร็จ', 'error');
  } finally {
    btn.disabled = false;
  }
}

async function clearApiKey() {
  if (!confirm('ยืนยันลบ API key ออกจากระบบ? ผู้ช่วย AI จะใช้งานไม่ได้จนกว่าจะใส่คีย์ใหม่')) return;
  try {
    const res = await fetch('api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'save_ai_settings', api_key: '__CLEAR__' })
    });
    const data = await res.json();
    if (!data.success) { showToast(data.error || 'ลบไม่สำเร็จ', 'error'); return; }
    showToast('ลบ API key เรียบร้อยแล้ว');
    await loadAiSettings();
    await loadAiStatus();
  } catch (err) {
    showToast('เชื่อมต่อไม่สำเร็จ', 'error');
  }
}
<?php endif; ?>

// Init
(async function () {
  const url = new URLSearchParams(window.location.search);
  const ph  = url.get('phase');
  if (ph && AI_PHASE_LABELS[ph]) document.getElementById('aiPhaseSelect').value = ph;

  if (!AI_IS_STUDENT) {
    await loadStudents();
    const sid = url.get('student_id');
    const sel = document.getElementById('aiStudentSelect');
    if (sid && sel) sel.value = sid;
  }
<?php if ($aiIsTeacher): ?>
  await loadAiSettings();
<?php endif; ?>
  await loadAiStatus();
<?php if ($aiIsTeacher): ?>
  await loadBatchRooms();
  if (ph && AI_PHASE_LABELS[ph]) document.getElementById('batchPhase').value = ph;
  await loadBatchTargets();
<?php endif; ?>
  await loadFeedback();
  if (!AI_IS_STUDENT) loadAiOverview();
})();
</script>

<?php require_once 'footer.php'; ?>
