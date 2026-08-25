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

        <div class="row g-3">
          <div class="col-md-6">
            <div class="form-check form-switch mb-2">
              <input class="form-check-input" type="checkbox" id="aiEnabled">
              <label class="form-check-label fw-bold small" for="aiEnabled">เปิดใช้งานผู้ช่วย AI ทั้งระบบ</label>
            </div>
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="aiStudentEnabled">
              <label class="form-check-label fw-bold small" for="aiStudentEnabled">ให้นักเรียนกดตรวจด้วยตนเองได้</label>
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold small">รอบงานที่นักเรียนกดตรวจเองได้</label>
            <div class="d-flex flex-wrap gap-3">
              <?php foreach ($aiPhases as $ph): ?>
              <div class="form-check">
                <input class="form-check-input ai-phase-check" type="checkbox" value="<?php echo $ph; ?>" id="aiph_<?php echo $ph; ?>">
                <label class="form-check-label small" for="aiph_<?php echo $ph; ?>"><?php echo htmlspecialchars(ai_phase_label($ph)); ?></label>
              </div>
              <?php endforeach; ?>
            </div>
            <div class="form-text small">
              แนะนำให้<strong>ไม่ติ๊ก</strong>รอบก่อนเรียน/หลังเรียน เพื่อให้ผลวัดสะท้อนความสามารถจริงของนักเรียน
            </div>
          </div>
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
        <div class="col-md-<?php echo $aiIsStudent ? '8' : '4'; ?>">
          <label class="form-label fw-bold small">รอบงาน</label>
          <select id="aiPhaseSelect" class="form-select border-2 rounded-3" onchange="onSelectionChange()">
            <?php foreach ($aiPhases as $ph): ?>
            <option value="<?php echo $ph; ?>"><?php echo htmlspecialchars(ai_phase_label($ph)); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-<?php echo $aiIsStudent ? '4' : '3'; ?> d-grid">
          <button id="aiReviewBtn" class="btn btn-lg fw-bold rounded-pill shadow-sm text-white"
                  style="background: linear-gradient(135deg,#6d28d9,#0d7377);" onclick="runAiReview()">
            <i class="bi bi-stars me-1"></i>ให้ AI ตรวจ
          </button>
        </div>
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
  } else if (AI_IS_STUDENT && !aiStatus.student_enabled) {
    cls = 'alert-secondary';
    html = '<i class="bi bi-lock me-1"></i>คุณครูยังไม่เปิดให้นักเรียนกดตรวจด้วย AI เอง — ดูผลที่คุณครูตรวจให้ได้ที่ด้านล่าง';
  } else {
    html = '<i class="bi bi-check-circle me-1"></i>ผู้ช่วย AI พร้อมใช้งาน';
    if (AI_IS_STUDENT && aiStatus.allowed_phases && aiStatus.allowed_phases.length) {
      const names = aiStatus.allowed_phases.map(p => AI_PHASE_LABELS[p] || p).join(' · ');
      html += ' — รอบที่กดตรวจเองได้: ' + esc(names);
    }
  }
  bar.className = 'alert border-0 rounded-3 small ' + cls;
  bar.innerHTML = html;
  bar.classList.remove('d-none');
}

// ปุ่ม "ให้ AI ตรวจ" ใช้ได้หรือไม่ ขึ้นกับสิทธิ์ + รอบที่เลือก + โควตาคงเหลือ
function updateReviewButton() {
  const btn = document.getElementById('aiReviewBtn');
  if (!btn) return;
  const quota = document.getElementById('aiQuotaText');

  if (AI_ROLE === 'expert') {
    btn.disabled = true;
    btn.title = 'ผู้เชี่ยวชาญดูผลได้อย่างเดียว';
    quota.textContent = 'บทบาทผู้เชี่ยวชาญดูผลตรวจได้อย่างเดียว ไม่สามารถสั่งให้ AI ตรวจได้';
    return;
  }
  if (!aiStatus) { btn.disabled = true; return; }

  let reason = '';
  if (!aiStatus.can_review) {
    reason = aiStatus.enabled
      ? (aiStatus.configured ? 'คุณครูยังไม่เปิดให้นักเรียนกดตรวจเอง' : 'ยังไม่ได้ตั้งค่า API key')
      : 'ผู้ช่วย AI ถูกปิดใช้งานอยู่';
  } else if (AI_IS_STUDENT && aiStatus.allowed_phases.indexOf(currentPhase()) === -1) {
    reason = 'คุณครูไม่ได้เปิดให้ใช้ AI ตรวจในรอบงานนี้';
  } else if (aiStatus.quota_left <= 0) {
    reason = 'วันนี้ใช้โควตาครบแล้ว (' + aiStatus.quota_limit + ' ครั้ง/วัน)';
  } else if (!AI_IS_STUDENT && !currentStudentId()) {
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
    document.getElementById('aiEnabled').checked        = !!data.settings.enabled;
    document.getElementById('aiStudentEnabled').checked = !!data.settings.student_enabled;
    document.querySelectorAll('.ai-phase-check').forEach(cb => {
      cb.checked = data.settings.student_phases.indexOf(cb.value) !== -1;
    });

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
    const phases = Array.from(document.querySelectorAll('.ai-phase-check:checked')).map(cb => cb.value);
    const res = await fetch('api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        action: 'save_ai_settings',
        provider: document.getElementById('aiProvider').value,
        model: document.getElementById('aiModel').value.trim(),
        base_url: document.getElementById('aiBaseUrl').value.trim(),
        api_key: document.getElementById('aiApiKey').value.trim(),
        enabled: document.getElementById('aiEnabled').checked,
        student_enabled: document.getElementById('aiStudentEnabled').checked,
        student_phases: phases
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
  await loadFeedback();
  if (!AI_IS_STUDENT) loadAiOverview();
})();
</script>

<?php require_once 'footer.php'; ?>
