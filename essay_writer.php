<?php
$page_title = 'บันทึกเรียงความ - ระบบประเมินเรียงความ';
require_once 'auth_helper.php';
require_login('student');
require_once 'header.php';
?>

<div id="view-essay-writer" class="text-start">
  <div class="mb-3">
    <a href="index.php" class="btn btn-link text-decoration-none text-secondary fw-bold p-0">
      <i class="bi bi-arrow-left-short"></i> กลับหน้าเมนูหลัก
    </a>
  </div>

  <!-- Header -->
  <div class="card border-0 shadow-lg rounded-4 overflow-hidden mb-4">
    <div class="p-4 text-white" style="background: linear-gradient(135deg, #0f4c75 0%, #1b6ca8 50%, #0d7377 100%);">
      <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
        <div>
          <h4 class="fw-bold mb-1"><i class="bi bi-pencil-square me-2"></i>บันทึกเรียงความของฉัน</h4>
          <p class="text-white-50 mb-0 small">
            เขียนเรียงความบนกระดาษก่อน แล้วพิมพ์ข้อความลงในระบบเพื่อเก็บข้อมูลวิเคราะห์เชิงคุณภาพ
          </p>
        </div>
        <span class="badge bg-white text-dark px-3 py-2 fs-7 fw-bold">
          <i class="bi bi-person-fill me-1"></i><?php echo htmlspecialchars($sessionUser['name']); ?>
        </span>
      </div>
    </div>
  </div>

  <!-- Phase Selector -->
  <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white p-4">
    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-journal-bookmark text-primary me-2"></i>เลือกหน่วยการเรียน / รอบที่ต้องการบันทึก</h6>
    <div class="row g-2">
      <div class="col-6 col-md-3">
        <button class="essay-phase-btn btn btn-outline-primary w-100 rounded-3 py-3 fw-bold" data-unit="pretest" onclick="setEssayUnit('pretest')">
          <div class="fs-4 mb-1">📝</div>
          <div class="small">ก่อนเรียน</div>
          <div class="text-muted" style="font-size:0.72rem;">Pretest</div>
        </button>
      </div>
      <div class="col-6 col-md-3">
        <button class="essay-phase-btn btn btn-outline-success w-100 rounded-3 py-3 fw-bold active-phase" data-unit="task1" onclick="setEssayUnit('task1')">
          <div class="fs-4 mb-1">📚</div>
          <div class="small">ภาระงาน หน่วยที่ 1</div>
          <div class="text-muted" style="font-size:0.72rem;">Task Unit 1</div>
        </button>
      </div>
      <div class="col-6 col-md-3">
        <button class="essay-phase-btn btn btn-outline-warning w-100 rounded-3 py-3 fw-bold" data-unit="task2" onclick="setEssayUnit('task2')">
          <div class="fs-4 mb-1">📖</div>
          <div class="small">ภาระงาน หน่วยที่ 2</div>
          <div class="text-muted" style="font-size:0.72rem;">Task Unit 2</div>
        </button>
      </div>
      <div class="col-6 col-md-3">
        <button class="essay-phase-btn btn btn-outline-danger w-100 rounded-3 py-3 fw-bold" data-unit="posttest" onclick="setEssayUnit('posttest')">
          <div class="fs-4 mb-1">🎓</div>
          <div class="small">หลังเรียน</div>
          <div class="text-muted" style="font-size:0.72rem;">Posttest</div>
        </button>
      </div>
    </div>

    <!-- ตัวเลือกร่าง (แสดงเฉพาะภาระงานหน่วยที่ 1/2): D1 = ร่างที่ 1, D2 = ร่างที่ 2 -->
    <div id="draftSelector" class="mt-3 d-none">
      <div class="d-flex align-items-center flex-wrap gap-2 bg-light border rounded-3 p-2">
        <span class="fw-bold text-secondary small ms-1 me-1"><i class="bi bi-layers-half me-1"></i>เลือกร่างของภาระงาน:</span>
        <button type="button" class="draft-btn btn btn-outline-secondary btn-sm rounded-pill px-3 fw-bold active-draft" data-draft="d1" onclick="setEssayDraft('d1')">
          ร่างที่ 1 (D1)
        </button>
        <button type="button" class="draft-btn btn btn-outline-secondary btn-sm rounded-pill px-3 fw-bold" data-draft="d2" onclick="setEssayDraft('d2')">
          ร่างที่ 2 (D2)
        </button>
        <span class="text-muted small ms-auto me-1"><i class="bi bi-info-circle me-1"></i>คุณครูจะให้คะแนนจาก <strong>ร่างที่ 2 (D2)</strong> เท่านั้น</span>
      </div>
    </div>
  </div>

  <!-- Writing Area -->
  <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
    <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between py-3 px-4 rounded-top-4">
      <div class="d-flex align-items-center gap-3">
        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-file-text-fill text-primary me-1"></i>กรอกเนื้อหาเรียงความ</h6>
        <span id="currentPhaseBadge" class="badge bg-success">ภาระงาน หน่วยที่ 1</span>
      </div>
      <div class="d-flex align-items-center gap-3">
        <span id="saveStatusBadge" class="badge bg-light text-muted small d-none"></span>
        <span id="wordCountBadge" class="badge bg-primary-subtle text-primary fw-bold px-3 py-2 rounded-pill">
          <i class="bi bi-fonts me-1"></i>0 คำ
        </span>
      </div>
    </div>
    <div class="card-body p-4">

      <!-- หัวข้อที่ครูกำหนด (นักเรียนไม่ต้องตั้งชื่อเรื่องเอง) -->
      <div class="mb-3 p-3 rounded-3 border" style="background-color:#eef6ff; border-color:#cfe2ff !important;">
        <div class="fw-bold text-primary small mb-1"><i class="bi bi-bookmark-star-fill me-1"></i>หัวข้อเรียงความ (คุณครูกำหนด)</div>
        <div id="essayTopicText" class="fs-5 fw-semibold text-dark">
          <span class="text-muted fst-italic fs-6">กำลังโหลดหัวข้อ...</span>
        </div>
      </div>

      <!-- โหมดดูฉบับที่บันทึกไว้ (อ่านอย่างเดียว คล้ายเอกสารพิมพ์) — แสดงเมื่อรอบนี้มีเนื้อหาบันทึกไว้แล้ว -->
      <div id="essayViewPanel" class="d-none">
        <div class="essay-view-doc" id="essayViewContent"></div>
        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
          <span class="text-muted small" id="essayViewMeta"></span>
          <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold" onclick="showEssayEdit()">
            <i class="bi bi-pencil-fill me-1"></i>แก้ไข
          </button>
        </div>
      </div>

      <div id="essayEditPanel">
      <!-- แจ้งเตือนเมื่อคุณครูปิดรับการส่งงานของรอบที่เลือกอยู่ -->
      <div id="phaseClosedNotice" class="alert alert-warning border-0 rounded-3 small mb-3 py-2 d-none" role="alert">
        <i class="bi bi-lock-fill me-2"></i>
        คุณครูยังไม่เปิดรับการส่งงานของรอบนี้ คุณดูหรือแก้ไขร่างไว้ก่อนได้ แต่จะยังบันทึกส่งไม่ได้จนกว่าคุณครูจะเปิดรับ
      </div>

      <!-- Instructions -->
      <div class="alert alert-info border-0 rounded-3 small mb-3 py-2" role="alert">
        <i class="bi bi-info-circle-fill me-2"></i>
        พิมพ์เรียงความตามที่เขียนไว้บนกระดาษให้ตรงกันมากที่สุด ระบบจะนับจำนวนคำและบันทึกเก็บไว้เพื่อนำไปวิเคราะห์เชิงคุณภาพโดยคุณครูผู้สอน
      </div>

      <div class="d-flex justify-content-end mb-2">
        <button type="button" class="btn btn-link btn-sm text-decoration-none d-none" id="backToViewBtn" onclick="showEssayView()">
          <i class="bi bi-eye me-1"></i>กลับไปดูฉบับที่บันทึกไว้ (ไม่แก้ไข)
        </button>
      </div>

      <!-- Essay sections -->
      <div class="space-y-4">
        <!-- 1. Introduction -->
        <div class="mb-4 p-3 rounded-3" style="background-color: #f8fafc; border: 1px solid #e2e8f0;">
          <label for="essayIntro" class="form-label fw-bold text-dark fs-6">
            ✍️ 1. ส่วนคำนำ (Introduction) <span class="text-danger">*</span>
          </label>
          <p class="text-muted small mb-2">เขียนคำนำเพื่อดึงดูดความสนใจและระบุประเด็นหลักของเรื่อง</p>
          <textarea id="essayIntro" class="form-control border-2 rounded-3 essay-section-input" rows="4"
            placeholder="พิมพ์ส่วนคำนำที่นี่..." oninput="updateWordCount()" style="font-family: 'TH Sarabun PSK', 'THSarabunPSK', 'TH SarabunPSK', 'TH Sarabun New', 'Sarabun', 'Leelawadee UI', 'Tahoma', sans-serif; font-size: 1rem; line-height: 1.8;"></textarea>
        </div>

        <!-- 2. Body -->
        <div class="mb-4 p-3 rounded-3" style="background-color: #f0fdf4; border: 1px solid #dcfce7;">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <label class="form-label fw-bold text-dark fs-6 mb-0">
              📚 2. ส่วนเนื้อเรื่อง (Body Paragraphs) <span class="text-danger">*</span>
            </label>
            <button type="button" class="btn btn-sm btn-success rounded-pill px-3" onclick="addBodyParagraph()">
              <i class="bi bi-plus-circle me-1"></i> เพิ่มย่อหน้าเนื้อเรื่อง
            </button>
          </div>
          <p class="text-muted small mb-3">พิมพ์รายละเอียดเนื้อเรื่อง แยกเป็นย่อหน้าต่าง ๆ (มีได้หลายย่อหน้า)</p>
          <div id="bodyParagraphsContainer">
            <!-- Dynamically populated body textareas -->
          </div>
        </div>

        <!-- 3. Conclusion -->
        <div class="mb-4 p-3 rounded-3" style="background-color: #fef2f2; border: 1px solid #fee2e2;">
          <label for="essayConclusion" class="form-label fw-bold text-dark fs-6">
            🎓 3. ส่วนสรุป (Conclusion) <span class="text-danger">*</span>
          </label>
          <p class="text-muted small mb-2">เขียนสรุปย้ำประเด็นหลักและสรุปความคิดเห็นทั้งหมด</p>
          <textarea id="essayConclusion" class="form-control border-2 rounded-3 essay-section-input" rows="4"
            placeholder="พิมพ์ส่วนสรุปที่นี่..." oninput="updateWordCount()" style="font-family: 'TH Sarabun PSK', 'THSarabunPSK', 'TH SarabunPSK', 'TH Sarabun New', 'Sarabun', 'Leelawadee UI', 'Tahoma', sans-serif; font-size: 1rem; line-height: 1.8;"></textarea>
        </div>
      </div>

      <div class="d-flex justify-content-between mt-2 mb-3">
        <span class="text-muted small"><i class="bi bi-keyboard me-1"></i>พิมพ์แบ่งออกตามโครงสร้างองค์ประกอบ 3 ส่วนของเรียงความ</span>
        <span id="charCount" class="text-muted small">0 ตัวอักษร</span>
      </div>

      <!-- Stats Preview -->
      <div class="row g-3 mb-3" id="statsRow">
        <div class="col-4">
          <div class="card border-0 rounded-3 p-3 text-center bg-light">
            <div class="fs-4 fw-extrabold text-primary mb-0" id="statWords">0</div>
            <div class="text-muted small">จำนวนคำ</div>
          </div>
        </div>
        <div class="col-4">
          <div class="card border-0 rounded-3 p-3 text-center bg-light">
            <div class="fs-4 fw-extrabold text-success mb-0" id="statChars">0</div>
            <div class="text-muted small">ตัวอักษร</div>
          </div>
        </div>
        <div class="col-4">
          <div class="card border-0 rounded-3 p-3 text-center bg-light">
            <div class="fs-4 fw-extrabold text-warning mb-0" id="statParagraphs">0</div>
            <div class="text-muted small">ย่อหน้า</div>
          </div>
        </div>
      </div>

      <!-- ตรวจสอบการสะกดคำ + แยกคำทั้งหน้า — แสดงจุดที่น่าสงสัยไว้ตรงนี้เลย ไม่ต้องกดดูก่อน -->
      <div class="mb-4 p-3 bg-light rounded-3">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
          <span class="small text-muted" id="spellCheckStatus">
            <i class="bi bi-search me-1"></i>พิมพ์เรียงความเพื่อตรวจการสะกดคำ
          </span>
          <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3" onclick="openSpellingReview()">
            <i class="bi bi-pencil-square me-1"></i>เปิดแก้ไขทีละจุด
          </button>
        </div>
        <div id="spellCheckPreview" class="essay-view-doc d-none"></div>
      </div>
      </div>

    </div>

    <!-- Save Footer -->
    <div class="card-footer bg-white border-top px-4 py-3 rounded-bottom-4">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
          <div id="lastSavedInfo" class="text-muted small d-none">
            <i class="bi bi-clock text-success me-1"></i>
            บันทึกล่าสุด: <span id="lastSavedTime">-</span>
          </div>
          <div class="text-muted small">กดปุ่ม "บันทึกเรียงความ" เพื่อเก็บข้อมูลลงในระบบ</div>
        </div>
        <div class="d-flex gap-2" id="essayFooterActions">
          <button id="clearBtn" class="btn btn-outline-secondary rounded-pill px-4" onclick="clearEssay()">
            <i class="bi bi-arrow-counterclockwise"></i> ล้างข้อมูล
          </button>
          <button id="saveBtn" class="btn btn-success btn-lg rounded-pill px-5 fw-bold shadow-sm" onclick="saveEssay()">
            <i class="bi bi-cloud-upload-fill me-2"></i>บันทึกเรียงความ
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Saved Essays History -->
  <div class="card border-0 shadow-sm rounded-4 bg-white">
    <div class="card-header bg-white border-bottom py-3 px-4 rounded-top-4 d-flex align-items-center justify-content-between">
      <h6 class="fw-bold text-dark mb-0"><i class="bi bi-archive-fill text-secondary me-2"></i>เรียงความที่บันทึกไว้แล้ว</h6>
      <button class="btn btn-outline-secondary btn-sm rounded-pill" onclick="loadSavedList()">
        <i class="bi bi-arrow-clockwise me-1"></i>รีเฟรช
      </button>
    </div>
    <div class="card-body p-4">
      <div id="savedEssayList" class="text-muted small text-center py-4">
        <i class="bi bi-hourglass-split me-2"></i>กำลังโหลดรายการ...
      </div>
    </div>
  </div>
</div>

<style>
.essay-phase-btn { transition: all 0.2s ease; min-height: 90px; }
.essay-phase-btn .phase-closed-tag { font-size: 0.62rem; }
.essay-phase-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.1); }
.essay-phase-btn.active-phase {
  background-color: var(--bs-success) !important;
  border-color: var(--bs-success) !important;
  color: white !important;
  box-shadow: 0 4px 15px rgba(25,135,84,0.35);
}
.draft-btn.active-draft {
  background-color: #0d3b66 !important;
  border-color: #0d3b66 !important;
  color: #fff !important;
}
#essayContent::placeholder { color: #adb5bd; }
.essay-content-preview {
  white-space: pre-wrap;
  font-family: "TH Sarabun PSK", "THSarabunPSK", "TH SarabunPSK", "TH Sarabun New", "Sarabun", "Leelawadee UI", "Tahoma", sans-serif;
  font-size: 0.9rem;
  line-height: 1.7;
  max-height: 120px;
  overflow: hidden;
  position: relative;
}
.essay-content-preview::after {
  content: '';
  position: absolute;
  bottom: 0; left: 0; right: 0;
  height: 40px;
  background: linear-gradient(transparent, white);
}
/* โหมดดูฉบับที่บันทึกไว้ (อ่านอย่างเดียว) — จัดหน้าให้อ่านคล้ายเอกสารเรียงความจริง (เหมือน essay_print.php) */
.essay-view-doc {
  font-family: "TH Sarabun PSK", "THSarabunPSK", "TH SarabunPSK", "TH Sarabun New", "Sarabun", "Leelawadee UI", "Tahoma", sans-serif;
  font-size: 1.05rem;
  line-height: 2;
}
.essay-view-doc .essay-view-para {
  margin: 0 0 0.75em;
  text-indent: 2.5em;
  text-align: justify;
}
.essay-view-doc .thai-word { border-bottom: 1px dotted #b9c4c4; }
.essay-view-doc .no-content { color: #888; font-style: italic; text-indent: 0; text-align: center; }
#spellCheckPreview { background: #fff; border: 1px solid #f0e6c8; border-radius: 10px; padding: 12px 16px; font-size: 0.95rem; }
#spellCheckPreview .trw-static-flag, #essayViewContent .trw-static-flag, #savedEssayList .trw-static-flag { cursor: pointer; }
/* การ์ดในรายการ "บันทึกไว้แล้ว" — แสดงเนื้อหาเต็มแบบเดียวกัน */
.saved-essay-full { font-family: "TH Sarabun PSK", "THSarabunPSK", "TH SarabunPSK", "TH Sarabun New", "Sarabun", "Leelawadee UI", "Tahoma", sans-serif; font-size: 0.95rem; line-height: 1.9; }
.saved-essay-full .essay-view-para { margin: 0 0 0.6em; text-indent: 2em; text-align: justify; }
.saved-essay-full .thai-word { border-bottom: 1px dotted #c9c2b3; }
</style>

<script src="thai_review.js"></script>
<script>
const phaseLabels = {
  pretest:  'ก่อนเรียน (Pretest)',
  task1_d1: 'ภาระงาน หน่วยที่ 1 · ร่างที่ 1 (D1)',
  task1_d2: 'ภาระงาน หน่วยที่ 1 · ร่างที่ 2 (D2)',
  task2_d1: 'ภาระงาน หน่วยที่ 2 · ร่างที่ 1 (D1)',
  task2_d2: 'ภาระงาน หน่วยที่ 2 · ร่างที่ 2 (D2)',
  posttest: 'หลังเรียน (Posttest)'
};
const phaseBadgeColors = {
  pretest: 'bg-primary',
  task1_d1: 'bg-success', task1_d2: 'bg-success',
  task2_d1: 'bg-warning text-dark', task2_d2: 'bg-warning text-dark',
  posttest: 'bg-danger'
};

// แยกสถานะเป็น 2 ระดับ: หน่วย (pretest/task1/task2/posttest) และร่าง (d1/d2 เฉพาะภาระงาน)
let currentUnit = 'task1';
let currentDraft = 'd1';
let currentEssayPhase = 'task1_d1';
let autoSaveTimer = null;
let bodyParagraphCount = 0;

// รวมหน่วย + ร่าง เป็นคีย์ essay_phase: ภาระงานมีร่าง (task1_d1) ส่วนก่อน/หลังเรียนไม่มีร่าง
function computeEssayPhase(unit, draft) {
  return (unit === 'task1' || unit === 'task2') ? (unit + '_' + draft) : unit;
}

// หัวข้อที่ครูกำหนด (map: pretest/task1/task2/posttest → หัวข้อ) — ภาระงานใช้หัวข้อเดียวกันทั้ง D1/D2
let essayTopics = {};
// สถานะเปิด/ปิดรับการส่งของแต่ละรอบที่ครูกำหนด (map: pretest/task1/task2/posttest → bool)
let essayOpen = {};
async function loadEssayTopics() {
  try {
    const res = await fetch('api.php?action=get_essay_topics');
    const data = await res.json();
    if (data.success) { essayTopics = data.topics || {}; essayOpen = data.open || {}; }
  } catch (e) { essayTopics = {}; essayOpen = {}; }
  applyPhaseOpenState();
  updateTopicDisplay();
  updateSubmitAvailability();
}

// รอบนี้ครูเปิดรับการส่งหรือไม่ (unit = pretest/task1/posttest ตรงกับคีย์สถานะโดยตรง)
function isUnitOpen(unit) {
  return essayOpen[unit] !== false;
}

// ติดป้าย "ปิดรับ" บนปุ่มหน่วยที่ครูปิดรับ เพื่อให้นักเรียนเห็นชัดว่าส่งรอบไหนได้
function applyPhaseOpenState() {
  document.querySelectorAll('.essay-phase-btn').forEach(btn => {
    const unit = btn.getAttribute('data-unit');
    const open = isUnitOpen(unit);
    let tag = btn.querySelector('.phase-closed-tag');
    if (!open) {
      if (!tag) {
        tag = document.createElement('div');
        tag.className = 'phase-closed-tag badge bg-danger mt-1';
        tag.innerHTML = '<i class="bi bi-lock-fill me-1"></i>ปิดรับ';
        btn.appendChild(tag);
      }
    } else if (tag) {
      tag.remove();
    }
  });
}

// เปิด/ปิดปุ่มบันทึกตามสถานะรอบที่เลือกอยู่ พร้อมแสดงข้อความแจ้งเมื่อรอบถูกปิดรับ
function updateSubmitAvailability() {
  const open = isUnitOpen(currentUnit);
  const saveBtn = document.getElementById('saveBtn');
  const notice  = document.getElementById('phaseClosedNotice');
  if (saveBtn) saveBtn.disabled = !open;
  if (notice)  notice.classList.toggle('d-none', open);
}
function updateTopicDisplay() {
  const el = document.getElementById('essayTopicText');
  if (!el) return;
  const topic = (essayTopics[currentUnit] || '').trim();
  el.innerHTML = topic
    ? escapeHtmlWriter(topic)
    : '<span class="text-muted fst-italic fs-6">คุณครูยังไม่กำหนดหัวข้อสำหรับงานนี้</span>';
}
function escapeHtmlWriter(s) {
  return String(s == null ? '' : s)
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

function addBodyParagraph(content = "") {
  bodyParagraphCount++;
  const container = document.getElementById('bodyParagraphsContainer');
  const div = document.createElement('div');
  div.className = 'mb-3 body-paragraph-item position-relative p-3 bg-white rounded-3 border';
  div.id = `body_para_container_${bodyParagraphCount}`;
  div.innerHTML = `
    <div class="d-flex justify-content-between align-items-center mb-2">
      <span class="fw-bold text-secondary small">ย่อหน้าที่ ${container.children.length + 1}</span>
      <button type="button" class="btn btn-sm btn-link text-danger text-decoration-none p-0" onclick="removeBodyParagraph(${bodyParagraphCount})">
        <i class="bi bi-trash"></i> ลบย่อหน้านี้
      </button>
    </div>
    <textarea class="form-control border-2 rounded-3 body-para-textarea essay-section-input" rows="5"
      placeholder="พิมพ์เนื้อหาย่อหน้านี้..." oninput="updateWordCount()" style="font-family: 'TH Sarabun PSK', 'THSarabunPSK', 'TH SarabunPSK', 'TH Sarabun New', 'Sarabun', 'Leelawadee UI', 'Tahoma', sans-serif; font-size: 1rem; line-height: 1.8;">${content}</textarea>
  `;
  container.appendChild(div);
  reindexBodyParagraphs();
  updateWordCount();
}

function removeBodyParagraph(id) {
  const container = document.getElementById('bodyParagraphsContainer');
  if (container.children.length <= 1) {
    showToast('ต้องมีส่วนเนื้อเรื่องอย่างน้อย 1 ย่อหน้า', 'error');
    return;
  }
  const item = document.getElementById(`body_para_container_${id}`);
  if (item) {
    item.remove();
    reindexBodyParagraphs();
    updateWordCount();
  }
}

function reindexBodyParagraphs() {
  const container = document.getElementById('bodyParagraphsContainer');
  Array.from(container.children).forEach((child, index) => {
    const label = child.querySelector('.fw-bold.text-secondary');
    if (label) {
      label.textContent = `ย่อหน้าที่ ${index + 1}`;
    }
  });
}

// เลือกหน่วยการเรียน — ภาระงานจะเปิดแถบเลือกร่าง (D1/D2) ให้ด้วย
function setEssayUnit(unit, opts) {
  currentUnit = unit;
  const isTask = (unit === 'task1' || unit === 'task2');

  // แสดง/ซ่อนแถบเลือกร่าง เฉพาะภาระงาน
  const draftSel = document.getElementById('draftSelector');
  if (draftSel) draftSel.classList.toggle('d-none', !isTask);

  // ไฮไลต์ปุ่มหน่วยที่เลือก
  document.querySelectorAll('.essay-phase-btn').forEach(b => b.classList.remove('active-phase'));
  const activeBtn = document.querySelector(`.essay-phase-btn[data-unit="${unit}"]`);
  if (activeBtn) activeBtn.classList.add('active-phase');

  currentEssayPhase = computeEssayPhase(unit, currentDraft);
  updatePhaseBadge();
  updateTopicDisplay();
  updateSubmitAvailability();
  loadEssayForPhase(currentEssayPhase, opts);
}

// เลือกร่าง (D1/D2) ของภาระงานหน่วยปัจจุบัน
function setEssayDraft(draft, opts) {
  currentDraft = draft;
  document.querySelectorAll('.draft-btn').forEach(b => b.classList.remove('active-draft'));
  const activeBtn = document.querySelector(`.draft-btn[data-draft="${draft}"]`);
  if (activeBtn) activeBtn.classList.add('active-draft');

  currentEssayPhase = computeEssayPhase(currentUnit, draft);
  updatePhaseBadge();
  loadEssayForPhase(currentEssayPhase, opts);
}

function updatePhaseBadge() {
  const badge = document.getElementById('currentPhaseBadge');
  badge.textContent = phaseLabels[currentEssayPhase] || currentEssayPhase;
  badge.className = 'badge ' + (phaseBadgeColors[currentEssayPhase] || 'bg-secondary');
}

// เปิดแก้ไขจากรายการที่บันทึกไว้ (แปลงคีย์ phase → หน่วย + ร่าง) — เข้าโหมดแก้ไขทันที ตรงตามป้ายปุ่ม "แก้ไข"
function openEssayPhase(phase) {
  const m = /^(task[12])_(d[12])$/.exec(phase);
  if (m) {
    currentDraft = m[2];
    document.querySelectorAll('.draft-btn').forEach(b => b.classList.toggle('active-draft', b.getAttribute('data-draft') === m[2]));
    setEssayUnit(m[1], { forceEdit: true });
  } else {
    setEssayUnit(phase, { forceEdit: true });
  }
}

// กันข้อมูลหายกรณีเน็ตหลุด/เซสชันหมดอายุ/ปิดแท็บกลางคัน — เก็บร่างที่พิมพ์ไว้ (ยังไม่ได้กดบันทึก) ลง localStorage ของเครื่อง แยกตามนักเรียน+รอบ
const DRAFT_OWNER_ID = <?php echo json_encode($_SESSION['user']['id']); ?>;
function draftKey(phase) {
  return `thaieasay_essay_draft_${DRAFT_OWNER_ID}_${phase}`;
}
function saveDraftToLocalStorage(phase) {
  const intro = document.getElementById('essayIntro').value;
  const conclusion = document.getElementById('essayConclusion').value;
  const body = Array.from(document.querySelectorAll('.body-para-textarea')).map(ta => ta.value);
  const hasContent = (intro + conclusion + body.join('')).trim() !== '';
  try {
    if (hasContent) {
      localStorage.setItem(draftKey(phase), JSON.stringify({ intro, body, conclusion, savedAt: Date.now() }));
    } else {
      localStorage.removeItem(draftKey(phase));
    }
  } catch (e) { /* localStorage เต็มหรือถูกปิดใช้งาน — ข้ามไปเงียบ ๆ ไม่กระทบการพิมพ์ */ }
}
function clearDraftFromLocalStorage(phase) {
  try { localStorage.removeItem(draftKey(phase)); } catch (e) {}
}

// ตรวจร่างที่ค้างใน localStorage ของรอบนี้ (พิมพ์ไว้แต่ไม่ทันกดบันทึกจากครั้งก่อน) แล้วเสนอกู้คืนให้ผู้ใช้เลือกเอง
function checkAndOfferDraftRestore(phase) {
  let draft = null;
  try {
    const raw = localStorage.getItem(draftKey(phase));
    if (raw) draft = JSON.parse(raw);
  } catch (e) { draft = null; }
  if (!draft) return;

  const curIntro = document.getElementById('essayIntro').value;
  const curConclusion = document.getElementById('essayConclusion').value;
  const curBody = Array.from(document.querySelectorAll('.body-para-textarea')).map(ta => ta.value);
  const isSameAsCurrent = draft.intro === curIntro && draft.conclusion === curConclusion
    && JSON.stringify(draft.body) === JSON.stringify(curBody);
  if (isSameAsCurrent) {
    // ร่างในเครื่องตรงกับข้อมูลที่โหลดมาแสดงอยู่แล้ว (บันทึกสำเร็จไปก่อนหน้านี้) — ล้างทิ้งได้เลย ไม่ต้องถาม
    clearDraftFromLocalStorage(phase);
    return;
  }

  const savedAtText = new Date(draft.savedAt).toLocaleString('th-TH');
  const wantRestore = confirm(
    `พบร่างเรียงความที่เคยพิมพ์ไว้เมื่อ ${savedAtText} แต่ยังไม่ได้กดบันทึก (อาจเกิดจากเน็ตหลุดหรือเซสชันหมดอายุกลางคัน)\n\nต้องการกู้คืนร่างนี้กลับมาแทนที่ข้อมูลที่แสดงอยู่หรือไม่?`
  );
  if (wantRestore) {
    document.getElementById('essayIntro').value = draft.intro || '';
    document.getElementById('essayConclusion').value = draft.conclusion || '';
    const container = document.getElementById('bodyParagraphsContainer');
    container.innerHTML = '';
    const bodyList = (draft.body && draft.body.length) ? draft.body : [''];
    bodyList.forEach(t => addBodyParagraph(t));
    updateWordCount();
    showEssayEdit();
    showToast('กู้คืนร่างที่ยังไม่ได้บันทึกเรียบร้อยแล้ว อย่าลืมกดบันทึกอีกครั้ง', 'success');
  } else {
    clearDraftFromLocalStorage(phase);
  }
}

// ตัดคำภาษาไทยด้วย Intl.Segmenter (ใช้ตัวเดียวกับ thai_review.js) เพื่อแสดงขอบเขตคำในโหมดดูฉบับที่บันทึกไว้
function essayWordSegmentedHTML(text) {
  const esc = s => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  if (typeof ThaiReview === 'undefined' || !ThaiReview.segmentText) {
    return esc(text).replace(/\n/g, '<br>');
  }
  let html = '';
  ThaiReview.segmentText(text).forEach(seg => {
    const e = esc(seg.text).replace(/\n/g, '<br>');
    html += seg.isWord ? `<span class="thai-word">${e}</span>` : e;
  });
  return html;
}

// แยกเนื้อหาเรียงความ (JSON หรือข้อความล้วน) เป็นรายการย่อหน้าเรียงตามลำดับ (คำนำ/เนื้อเรื่อง.../สรุป)
function parseEssayContentToParas(contentStr) {
  const paras = [];
  if (!contentStr) return paras;
  let parsed = null;
  try { parsed = JSON.parse(contentStr); } catch (e) { parsed = null; }
  if (parsed && typeof parsed === 'object' && parsed.introduction !== undefined) {
    if (parsed.introduction && parsed.introduction.trim()) paras.push(parsed.introduction);
    if (Array.isArray(parsed.body)) {
      parsed.body.forEach(p => { if (p && p.trim()) paras.push(p); });
    }
    if (parsed.conclusion && parsed.conclusion.trim()) paras.push(parsed.conclusion);
  } else if (String(contentStr).trim()) {
    String(contentStr).split(/\n{2,}/).forEach(p => { if (p.trim()) paras.push(p); });
  }
  return paras;
}

// สร้าง HTML แบบเอกสารเรียงความ (คล้าย essay_print.php) จากเนื้อหา JSON — แสดงเฉพาะขอบเขตคำ ไม่ตรวจคำผิด
function renderEssayDocHTML(contentStr, paraClass) {
  const paras = parseEssayContentToParas(contentStr);
  if (!paras.length) return '<div class="no-content">— ยังไม่มีเนื้อหาเรียงความ —</div>';
  return paras.map(p => `<p class="${paraClass}">${essayWordSegmentedHTML(p)}</p>`).join('');
}

// เหมือน renderEssayDocHTML แต่ตรวจคำผิด/คำภาษาอื่น/การเว้นวรรครอบ ๆ ด้วย แล้วไฮไลต์จุดที่น่าสงสัย
// ให้เห็นตรง ๆ (ใช้ในโหมดดูฉบับที่บันทึกไว้ และรายการ "เรียงความที่บันทึกไว้แล้ว" ด้านล่างหน้า)
async function renderEssayDocHTMLWithChecks(contentStr, paraClass) {
  const paras = parseEssayContentToParas(contentStr);
  if (!paras.length) return '<div class="no-content">— ยังไม่มีเนื้อหาเรียงความ —</div>';

  let sets = { misspelled: [], foreign: [], spacing: [] };
  try {
    const res = await fetch('api.php?action=check_thai_spelling', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ text: paras.join('\n') })
    });
    const data = await res.json();
    if (data.success) sets = { misspelled: data.misspelled, foreign: data.foreign, spacing: data.spacing };
  } catch (e) {
    // เงียบไว้ — แสดงเนื้อหาแบบไม่มีไฮไลต์ถ้าตรวจไม่สำเร็จ
  }
  return paras.map(p => `<p class="${paraClass}">${ThaiReview.renderStaticHTML(p, sets)}</p>`).join('');
}

// สลับไปโหมดดูฉบับที่บันทึกไว้ (อ่านอย่างเดียว)
function showEssayView() {
  document.getElementById('essayViewPanel').classList.remove('d-none');
  document.getElementById('essayEditPanel').classList.add('d-none');
  document.getElementById('essayFooterActions').classList.add('d-none');
}

// สลับไปโหมดแก้ไข (กล่องข้อความ) — ปุ่ม "กลับไปดูฉบับที่บันทึกไว้" จะโผล่มาเฉพาะตอนมีฉบับที่บันทึกไว้แล้วเท่านั้น
let currentPhaseHasSavedContent = false;
function showEssayEdit() {
  document.getElementById('essayViewPanel').classList.add('d-none');
  document.getElementById('essayEditPanel').classList.remove('d-none');
  document.getElementById('essayFooterActions').classList.remove('d-none');
  const backBtn = document.getElementById('backToViewBtn');
  if (backBtn) backBtn.classList.toggle('d-none', !currentPhaseHasSavedContent);
}

// อัปเดตโหมดดูฉบับที่บันทึกไว้ให้ตรงกับข้อมูลที่เพิ่งบันทึกสำเร็จ (ไม่ต้องโหลดจากเซิร์ฟเวอร์ใหม่)
async function refreshEssayViewFromParts(intro, bodyParas, conclusion, wordCount) {
  currentPhaseHasSavedContent = true;
  const contentStr = JSON.stringify({ introduction: intro, body: bodyParas, conclusion: conclusion });
  document.getElementById('essayViewContent').innerHTML = await renderEssayDocHTMLWithChecks(contentStr, 'essay-view-para');
  const now = new Date().toLocaleString('th-TH');
  document.getElementById('essayViewMeta').textContent = `${(wordCount || 0).toLocaleString('th-TH')} คำ · บันทึกล่าสุด: ${now}`;
  const backBtn = document.getElementById('backToViewBtn');
  if (backBtn) backBtn.classList.remove('d-none');
}

async function loadEssayForPhase(phase, opts) {
  opts = opts || {};
  const statusBadge = document.getElementById('saveStatusBadge');
  statusBadge.textContent = 'กำลังโหลด...';
  statusBadge.className = 'badge bg-info small';
  statusBadge.classList.remove('d-none');

  try {
    const res = await fetch(`api.php?action=get_essay&essay_phase=${phase}`);
    const data = await res.json();

    const container = document.getElementById('bodyParagraphsContainer');
    container.innerHTML = '';

    if (data.success && data.found) {
      const contentStr = data.data.essay_content || '';
      
      try {
        const obj = JSON.parse(contentStr);
        if (obj && typeof obj === 'object' && obj.introduction !== undefined) {
          document.getElementById('essayIntro').value = obj.introduction || '';
          document.getElementById('essayConclusion').value = obj.conclusion || '';
          if (obj.body && Array.isArray(obj.body)) {
            obj.body.forEach(paraText => {
              addBodyParagraph(paraText);
            });
          } else {
            addBodyParagraph();
          }
        } else {
          document.getElementById('essayIntro').value = contentStr;
          addBodyParagraph();
        }
      } catch (e) {
        document.getElementById('essayIntro').value = contentStr;
        addBodyParagraph();
      }

      updateWordCount();

      const dt = new Date(data.data.updated_at);
      document.getElementById('lastSavedTime').textContent = dt.toLocaleString('th-TH');
      document.getElementById('lastSavedInfo').classList.remove('d-none');

      statusBadge.textContent = '✓ มีข้อมูลบันทึกไว้แล้ว';
      statusBadge.className = 'badge bg-success small';

      currentPhaseHasSavedContent = true;
      document.getElementById('essayViewContent').innerHTML = await renderEssayDocHTMLWithChecks(contentStr, 'essay-view-para');
      document.getElementById('essayViewMeta').textContent =
        `${(data.data.word_count || 0).toLocaleString('th-TH')} คำ · บันทึกล่าสุด: ${dt.toLocaleString('th-TH')}`;
      if (opts.forceEdit) { showEssayEdit(); } else { showEssayView(); }
    } else {
      document.getElementById('essayIntro').value = '';
      document.getElementById('essayConclusion').value = '';
      addBodyParagraph();
      updateWordCount();
      document.getElementById('lastSavedInfo').classList.add('d-none');

      statusBadge.textContent = 'ยังไม่มีข้อมูล';
      statusBadge.className = 'badge bg-secondary small';

      currentPhaseHasSavedContent = false;
      showEssayEdit();
    }

    checkAndOfferDraftRestore(phase);
  } catch(err) {
    statusBadge.textContent = '⚠️ โหลดไม่ได้';
    statusBadge.className = 'badge bg-danger small';
    showEssayEdit();
  }
}

// นับจำนวนคำในข้อความภาษาไทยให้ถูกต้อง (ภาษาไทยไม่มีช่องว่างคั่นระหว่างคำ
// จึงใช้ Intl.Segmenter ซึ่งตัดคำตามพจนานุกรมของ ICU แทนการแยกด้วยช่องว่างแบบเดิม)
let __thaiWordSegmenter = null;
if (typeof Intl !== 'undefined' && typeof Intl.Segmenter === 'function') {
  try {
    __thaiWordSegmenter = new Intl.Segmenter('th', { granularity: 'word' });
  } catch (e) {
    __thaiWordSegmenter = null;
  }
}
function countThaiWords(text) {
  // อนุโลม "เ" สองตัวติดกัน (เ + เ) แทน "แ" ได้ก่อนตัดคำ กันตัวตัดคำสับสน (ดูรายละเอียดใน thai_review.js)
  const t = text.trim().replace(/เเ/g, 'แ');
  if (!t) return 0;
  if (__thaiWordSegmenter) {
    let count = 0;
    for (const part of __thaiWordSegmenter.segment(t)) {
      if (part.isWordLike) count++;
    }
    return count;
  }
  return t.split(/[\s\n\r]+/).filter(w => w.length > 0).length;
}

function updateWordCount() {
  const introText = document.getElementById('essayIntro').value;
  const conclusionText = document.getElementById('essayConclusion').value;
  const bodyTexts = Array.from(document.querySelectorAll('.body-para-textarea')).map(ta => ta.value);

  const allTextCombined = [introText, ...bodyTexts, conclusionText].join('\n\n').trim();
  const chars = allTextCombined.length;

  const wordCountIntro = countThaiWords(introText);
  const wordCountConclusion = countThaiWords(conclusionText);
  let wordCountBody = 0;
  bodyTexts.forEach(t => {
    wordCountBody += countThaiWords(t);
  });

  const words = wordCountIntro + wordCountBody + wordCountConclusion;
  const paragraphs = (introText.trim() ? 1 : 0) + bodyTexts.filter(t => t.trim().length > 0).length + (conclusionText.trim() ? 1 : 0);

  document.getElementById('statWords').textContent = words.toLocaleString('th-TH');
  document.getElementById('statChars').textContent = chars.toLocaleString('th-TH');
  document.getElementById('statParagraphs').textContent = paragraphs;
  document.getElementById('charCount').textContent = chars.toLocaleString('th-TH') + ' ตัวอักษร';
  
  const wBadge = document.getElementById('wordCountBadge');
  if (wBadge) {
    wBadge.innerHTML = `<i class="bi bi-fonts me-1"></i>${words.toLocaleString('th-TH')} คำ`;
  }

  clearTimeout(autoSaveTimer);
  autoSaveTimer = setTimeout(() => {
    const badge = document.getElementById('saveStatusBadge');
    if (allTextCombined.trim()) {
      badge.textContent = '✏️ มีการแก้ไข (ยังไม่บันทึก)';
      badge.className = 'badge bg-warning text-dark small';
      badge.classList.remove('d-none');
    }
    // เก็บร่างที่พิมพ์อยู่ไว้ในเครื่องด้วย เผื่อกดบันทึกไม่ทัน (เน็ตหลุด/เซสชันหมดอายุ/ปิดแท็บกลางคัน)
    saveDraftToLocalStorage(currentEssayPhase);
  }, 800);

  // ตรวจคำผิดแบบหน่วงเวลา (เรียก API ที่เทียบกับพจนานุกรม — หนักกว่าการนับคำ จึงไม่เรียกทุกครั้งที่พิมพ์)
  clearTimeout(spellCheckTimer);
  const statusEl = document.getElementById('spellCheckStatus');
  const previewEl = document.getElementById('spellCheckPreview');
  if (!allTextCombined.trim()) {
    statusEl.innerHTML = '<i class="bi bi-search me-1"></i>พิมพ์เรียงความเพื่อตรวจการสะกดคำ';
    previewEl.classList.add('d-none');
    previewEl.innerHTML = '';
  } else {
    spellCheckTimer = setTimeout(refreshSpellCheckStatus, 1500);
  }
}

let spellCheckTimer = null;
async function refreshSpellCheckStatus() {
  const statusEl = document.getElementById('spellCheckStatus');
  const previewEl = document.getElementById('spellCheckPreview');
  const paragraphs = buildReviewParagraphs();
  if (paragraphs.length === 0) return;
  try {
    const res = await fetch('api.php?action=check_thai_spelling', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ text: paragraphs.map(p => p.text).join('\n') })
    });
    const data = await res.json();
    if (!data.success) return;
    const sets = { misspelled: data.misspelled, foreign: data.foreign, spacing: data.spacing };
    const n = (data.misspelled ? data.misspelled.length : 0) + (data.foreign ? data.foreign.length : 0) + (data.spacing ? data.spacing.length : 0);
    if (n > 0) {
      statusEl.innerHTML = `<i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>พบคำที่น่าสงสัย ${n} คำ — ดูจุดที่ไฮไลต์ด้านล่าง (คลิกเพื่อแก้ไข)`;
      previewEl.innerHTML = paragraphs.map(p => `<p class="essay-view-para">${ThaiReview.renderStaticHTML(p.text, sets)}</p>`).join('');
      previewEl.classList.remove('d-none');
    } else {
      statusEl.innerHTML = '<i class="bi bi-check-circle-fill text-success me-1"></i>ไม่พบคำที่น่าสงสัยในขณะนี้';
      previewEl.classList.add('d-none');
      previewEl.innerHTML = '';
    }
  } catch (e) {
    // เงียบไว้ — ไม่ให้กระทบการพิมพ์งานหลัก
  }
}

// ส่งเนื้อหาแยกเป็น 3 ส่วน (ส่วนนำ/เนื้อหาหลายย่อหน้า/สรุป) ไปบันทึก — ใช้ร่วมกันโดยปุ่ม "บันทึกเรียงความ"
// และหน้าต่างตรวจสอบการสะกดคำทั้งหน้า (เมื่อกด "บันทึกการแก้ไข" ในหน้าต่างนั้น)
async function submitEssayContent(intro, bodyParagraphs, conclusion) {
  const res = await fetch('api.php?action=save_essay', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      essay_phase:  currentEssayPhase,
      introduction: intro,
      body:         bodyParagraphs,
      conclusion:   conclusion
    })
  });
  return res.json();
}

// สร้างรายการย่อหน้าทั้งฉบับ (คำนำ/เนื้อเรื่องแต่ละย่อหน้า/สรุป) สำหรับส่งให้หน้าต่างตรวจสอบการสะกดคำ
function buildReviewParagraphs() {
  const intro = document.getElementById('essayIntro').value;
  const conclusion = document.getElementById('essayConclusion').value;
  const bodyTextareas = Array.from(document.querySelectorAll('.body-para-textarea'));
  const paras = [];
  if (intro.trim()) paras.push({ label: 'intro', text: intro });
  bodyTextareas.forEach((ta, i) => {
    if (ta.value.trim()) paras.push({ label: 'body:' + i, text: ta.value });
  });
  if (conclusion.trim()) paras.push({ label: 'concl', text: conclusion });
  return paras;
}

// เขียนข้อความที่แก้ไขจากหน้าต่างตรวจสอบกลับเข้ากล่องข้อความเดิมตามป้าย label
function applyReviewParagraphsToForm(paragraphs) {
  const bodyTextareas = Array.from(document.querySelectorAll('.body-para-textarea'));
  paragraphs.forEach(p => {
    if (p.label === 'intro') {
      document.getElementById('essayIntro').value = p.text;
    } else if (p.label === 'concl') {
      document.getElementById('essayConclusion').value = p.text;
    } else if (p.label.indexOf('body:') === 0) {
      const idx = parseInt(p.label.split(':')[1], 10);
      if (bodyTextareas[idx]) bodyTextareas[idx].value = p.text;
    }
  });
}

// คลิกคำที่ไฮไลต์ในตัวอย่างที่แสดงไว้เลย (ไม่ต้องกดปุ่มก่อน) → เปิดหน้าต่างแก้ไขทันที
document.getElementById('spellCheckPreview').addEventListener('click', (ev) => {
  if (ev.target.closest && ev.target.closest('.trw-static-flag')) {
    openSpellingReview();
  }
});

// คลิกคำที่ไฮไลต์ในโหมดดูฉบับที่บันทึกไว้ → สลับไปโหมดแก้ไขแล้วเปิดหน้าต่างแก้ไขทันที
document.getElementById('essayViewContent').addEventListener('click', (ev) => {
  if (ev.target.closest && ev.target.closest('.trw-static-flag')) {
    showEssayEdit();
    openSpellingReview();
  }
});

// เปิดหน้าต่างตรวจสอบการสะกดคำ/แยกคำทั้งหน้า (thai_review.js)
async function openSpellingReview() {
  const paragraphs = buildReviewParagraphs();
  if (paragraphs.length === 0) {
    showToast('กรุณาพิมพ์เนื้อเรียงความก่อน', 'error');
    return;
  }

  let misspelled = [];
  let foreign = [];
  let spacing = [];
  try {
    const res = await fetch('api.php?action=check_thai_spelling', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ text: paragraphs.map(p => p.text).join('\n') })
    });
    const data = await res.json();
    if (data.success) { misspelled = data.misspelled; foreign = data.foreign; spacing = data.spacing; }
  } catch (e) {
    // เงียบไว้ — เปิดหน้าต่างได้แม้ตรวจคำผิดไม่สำเร็จ (จะไม่มีคำไฮไลต์)
  }

  ThaiReview.open({
    paragraphs,
    misspelled,
    foreign,
    spacing,
    onSave: async (editedParagraphs) => {
      applyReviewParagraphsToForm(editedParagraphs);
      const intro = document.getElementById('essayIntro').value.trim();
      const conclusion = document.getElementById('essayConclusion').value.trim();
      const bodyParas = Array.from(document.querySelectorAll('.body-para-textarea')).map(ta => ta.value.trim()).filter(Boolean);

      const data = await submitEssayContent(intro, bodyParas, conclusion);
      if (!data.success) {
        throw new Error(data.error || 'บันทึกไม่สำเร็จ');
      }

      updateWordCount();
      const now = new Date().toLocaleString('th-TH');
      document.getElementById('lastSavedTime').textContent = now;
      document.getElementById('lastSavedInfo').classList.remove('d-none');
      await refreshEssayViewFromParts(intro, bodyParas, conclusion, data.word_count);
      clearDraftFromLocalStorage(currentEssayPhase);
      loadSavedList();
    }
  });
}

async function saveEssay() {
  // รอบที่ครูปิดรับ — บล็อกการส่งไว้ก่อน (ฝั่งเซิร์ฟเวอร์ตรวจซ้ำอีกชั้น)
  if (!isUnitOpen(currentUnit)) {
    showToast('คุณครูยังไม่เปิดรับการส่งงานรอบนี้ กรุณาเลือกรอบที่เปิดรับ', 'error');
    return;
  }
  const intro = document.getElementById('essayIntro').value.trim();
  const conclusion = document.getElementById('essayConclusion').value.trim();
  const bodyParagraphs = Array.from(document.querySelectorAll('.body-para-textarea')).map(ta => ta.value.trim()).filter(Boolean);

  if (!intro) {
    showToast('กรุณากรอกส่วนคำนำ (Introduction)', 'error');
    return;
  }
  if (bodyParagraphs.length === 0) {
    showToast('กรุณากรอกส่วนเนื้อเรื่องอย่างน้อย 1 ย่อหน้า (Body)', 'error');
    return;
  }
  if (!conclusion) {
    showToast('กรุณากรอกส่วนสรุป (Conclusion)', 'error');
    return;
  }

  const btn = document.getElementById('saveBtn');
  const origHTML = btn.innerHTML;
  btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>กำลังบันทึก...`;
  btn.disabled = true;

  try {
    const data = await submitEssayContent(intro, bodyParagraphs, conclusion);

    if (data.success) {
      const now = new Date().toLocaleString('th-TH');
      document.getElementById('lastSavedTime').textContent = now;
      document.getElementById('lastSavedInfo').classList.remove('d-none');

      const statusBadge = document.getElementById('saveStatusBadge');
      statusBadge.textContent = `✓ บันทึกแล้ว (${data.word_count} คำ)`;
      statusBadge.className = 'badge bg-success small';
      statusBadge.classList.remove('d-none');

      showToast(`บันทึกเรียงความ "${phaseLabels[currentEssayPhase]}" สำเร็จ! (${data.word_count} คำ)`, 'success');
      await refreshEssayViewFromParts(intro, bodyParagraphs, conclusion, data.word_count);
      // บันทึกขึ้นเซิร์ฟเวอร์สำเร็จแล้ว ไม่ต้องเก็บร่างสำรองในเครื่องอีกต่อไป
      clearDraftFromLocalStorage(currentEssayPhase);
      loadSavedList();
    } else {
      showToast('เกิดข้อผิดพลาด: ' + data.error, 'error');
    }
  } catch(err) {
    showToast('ไม่สามารถเชื่อมต่อระบบได้', 'error');
  } finally {
    btn.innerHTML = origHTML;
    btn.disabled = false;
  }
}

function clearEssay() {
  if (!confirm('คุณต้องการล้างข้อความบนหน้าจอนี้ใช่ไหม? (ไม่ได้ลบข้อมูลที่บันทึกแล้วในระบบ)')) return;
  document.getElementById('essayIntro').value = '';
  document.getElementById('essayConclusion').value = '';
  const container = document.getElementById('bodyParagraphsContainer');
  container.innerHTML = '';
  addBodyParagraph();
  updateWordCount();
}

async function loadSavedList() {
  const container = document.getElementById('savedEssayList');
  container.innerHTML = '<div class="text-center text-muted py-3"><span class="spinner-border spinner-border-sm me-2"></span>กำลังโหลด...</div>';

  const phases = ['pretest', 'task1_d1', 'task1_d2', 'task2_d1', 'task2_d2', 'posttest'];
  const results = await Promise.all(
    phases.map(ph => fetch(`api.php?action=get_essay&essay_phase=${ph}`).then(r => r.json()).catch(() => ({ success: false })))
  );

  const found = results.map((r, i) => r.success && r.found ? { phase: phases[i], data: r.data } : null).filter(Boolean);

  if (found.length === 0) {
    container.innerHTML = '<div class="text-center text-muted py-4"><i class="bi bi-inbox fs-3 d-block mb-2"></i>ยังไม่มีเรียงความที่บันทึกไว้</div>';
    return;
  }

  const htmlParts = await Promise.all(found.map(item => renderEssayDocHTMLWithChecks(item.data.essay_content, 'essay-view-para')));

  container.innerHTML = found.map((item, idx) => {
    const fullHTML = htmlParts[idx];
    const dt = new Date(item.data.updated_at);
    const dateStr = dt.toLocaleString('th-TH', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
    const badgeClass = phaseBadgeColors[item.phase] || 'bg-secondary';

    return `
      <div class="card border-0 rounded-3 mb-3 border shadow-sm" style="border-color: #e2e8f0 !important;">
        <div class="card-body p-3">
          <div class="d-flex align-items-start justify-content-between mb-2 flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
              <span class="badge ${badgeClass} px-2 py-1 small">${phaseLabels[item.phase]}</span>
              ${item.data.essay_title ? `<span class="fw-bold text-dark small">${item.data.essay_title}</span>` : '<span class="text-muted small fst-italic">ไม่มีชื่อเรื่อง</span>'}
            </div>
            <div class="d-flex align-items-center gap-2">
              <span class="badge bg-primary-subtle text-primary rounded-pill px-2 py-1 small">
                <i class="bi bi-fonts me-1"></i>${(item.data.word_count || 0).toLocaleString('th-TH')} คำ
              </span>
              <button class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="openEssayPhase('${item.phase}')">
                <i class="bi bi-pencil me-1"></i>แก้ไข
              </button>
            </div>
          </div>
          <div class="saved-essay-full text-dark text-start mb-2 p-3 rounded-3" data-phase="${item.phase}" style="background-color:#fffdf9; border:1px solid #f0e6c8;">
            ${fullHTML}
          </div>
          <div class="text-muted text-start" style="font-size:0.75rem;">
            <i class="bi bi-clock me-1"></i>บันทึกล่าสุด: ${dateStr}
          </div>
        </div>
      </div>
    `;
  }).join('');
}

// คลิกคำที่ไฮไลต์ในรายการ "เรียงความที่บันทึกไว้แล้ว" → ไปเปิดแก้ไขรอบนั้นแล้วเปิดหน้าต่างแก้ไขทันที
document.getElementById('savedEssayList').addEventListener('click', (ev) => {
  const flag = ev.target.closest && ev.target.closest('.trw-static-flag');
  if (!flag) return;
  const wrap = flag.closest('.saved-essay-full');
  const phase = wrap && wrap.dataset.phase;
  if (phase) openEssayPhase(phase);
});

// Init
(async function() {
  await loadEssayTopics();          // โหลดหัวข้อที่ครูกำหนด

  // มีรอบระบุมาจาก URL (เช่นลิงก์จากรายการ "สิ่งที่ยังไม่ได้ทำ") → เปิดตรงรอบ/ร่างนั้นทันที
  const phaseFromUrl = new URLSearchParams(window.location.search).get('phase');
  if (phaseFromUrl && /^(pretest|posttest|task[12]_d[12])$/.test(phaseFromUrl)) {
    openEssayPhase(phaseFromUrl);
  } else {
    // ค่าเริ่มต้น: ภาระงานหน่วยที่ 1 · ร่างที่ 1 (D1)
    setEssayUnit('task1');
  }
  await loadSavedList();
})();
</script>

<?php require_once 'footer.php'; ?>
