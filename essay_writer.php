<?php
$page_title = 'บันทึกเรียงความ - ระบบประเมินเรียงความอัจฉริยะ';
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
          <div class="small">ภารงาน หน่วยที่ 1</div>
          <div class="text-muted" style="font-size:0.72rem;">Task Unit 1</div>
        </button>
      </div>
      <div class="col-6 col-md-3">
        <button class="essay-phase-btn btn btn-outline-warning w-100 rounded-3 py-3 fw-bold" data-unit="task2" onclick="setEssayUnit('task2')">
          <div class="fs-4 mb-1">📖</div>
          <div class="small">ภารงาน หน่วยที่ 2</div>
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

    <!-- ตัวเลือกร่าง (แสดงเฉพาะภารงานหน่วยที่ 1/2): D1 = ร่างที่ 1, D2 = ร่างที่ 2 -->
    <div id="draftSelector" class="mt-3 d-none">
      <div class="d-flex align-items-center flex-wrap gap-2 bg-light border rounded-3 p-2">
        <span class="fw-bold text-secondary small ms-1 me-1"><i class="bi bi-layers-half me-1"></i>เลือกร่างของภารงาน:</span>
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
        <span id="currentPhaseBadge" class="badge bg-success">ภารงาน หน่วยที่ 1</span>
      </div>
      <div class="d-flex align-items-center gap-3">
        <span id="saveStatusBadge" class="badge bg-light text-muted small d-none"></span>
        <span id="wordCountBadge" class="badge bg-primary-subtle text-primary fw-bold px-3 py-2 rounded-pill">
          <i class="bi bi-fonts me-1"></i>0 คำ
        </span>
      </div>
    </div>
    <div class="card-body p-4">

      <!-- Title Field -->
      <div class="mb-3">
        <label for="essayTitle" class="form-label fw-bold text-secondary small text-uppercase tracking-wider">
          ชื่อเรื่องเรียงความ <span class="text-muted fw-normal">(ถ้ามี)</span>
        </label>
        <input type="text" id="essayTitle" class="form-control form-control-lg border-2 rounded-3 fw-semibold"
          placeholder="เช่น: เรียงความเรื่อง ความรักต่อแผ่นดิน..." maxlength="200">
      </div>

      <!-- Instructions -->
      <div class="alert alert-info border-0 rounded-3 small mb-3 py-2" role="alert">
        <i class="bi bi-info-circle-fill me-2"></i>
        พิมพ์เรียงความตามที่เขียนไว้บนกระดาษให้ตรงกันมากที่สุด ระบบจะนับจำนวนคำและบันทึกเก็บไว้เพื่อนำไปวิเคราะห์เชิงคุณภาพโดยคุณครูผู้สอน
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
            placeholder="พิมพ์ส่วนคำนำที่นี่..." oninput="updateWordCount()" style="font-family: 'Sarabun', sans-serif; font-size: 1rem; line-height: 1.8;"></textarea>
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
            placeholder="พิมพ์ส่วนสรุปที่นี่..." oninput="updateWordCount()" style="font-family: 'Sarabun', sans-serif; font-size: 1rem; line-height: 1.8;"></textarea>
        </div>
      </div>

      <div class="d-flex justify-content-between mt-2 mb-3">
        <span class="text-muted small"><i class="bi bi-keyboard me-1"></i>พิมพ์แบ่งออกตามโครงสร้างองค์ประกอบ 3 ส่วนของเรียงความ</span>
        <span id="charCount" class="text-muted small">0 ตัวอักษร</span>
      </div>

      <!-- Stats Preview -->
      <div class="row g-3 mb-4" id="statsRow">
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
        <div class="d-flex gap-2">
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
  font-family: 'Sarabun', sans-serif;
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
</style>

<script>
const phaseLabels = {
  pretest:  'ก่อนเรียน (Pretest)',
  task1_d1: 'ภารงาน หน่วยที่ 1 · ร่างที่ 1 (D1)',
  task1_d2: 'ภารงาน หน่วยที่ 1 · ร่างที่ 2 (D2)',
  task2_d1: 'ภารงาน หน่วยที่ 2 · ร่างที่ 1 (D1)',
  task2_d2: 'ภารงาน หน่วยที่ 2 · ร่างที่ 2 (D2)',
  posttest: 'หลังเรียน (Posttest)'
};
const phaseBadgeColors = {
  pretest: 'bg-primary',
  task1_d1: 'bg-success', task1_d2: 'bg-success',
  task2_d1: 'bg-warning text-dark', task2_d2: 'bg-warning text-dark',
  posttest: 'bg-danger'
};

// แยกสถานะเป็น 2 ระดับ: หน่วย (pretest/task1/task2/posttest) และร่าง (d1/d2 เฉพาะภารงาน)
let currentUnit = 'task1';
let currentDraft = 'd1';
let currentEssayPhase = 'task1_d1';
let autoSaveTimer = null;
let bodyParagraphCount = 0;

// รวมหน่วย + ร่าง เป็นคีย์ essay_phase: ภารงานมีร่าง (task1_d1) ส่วนก่อน/หลังเรียนไม่มีร่าง
function computeEssayPhase(unit, draft) {
  return (unit === 'task1' || unit === 'task2') ? (unit + '_' + draft) : unit;
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
      placeholder="พิมพ์เนื้อหาย่อหน้านี้..." oninput="updateWordCount()" style="font-family: 'Sarabun', sans-serif; font-size: 1rem; line-height: 1.8;">${content}</textarea>
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

// เลือกหน่วยการเรียน — ภารงานจะเปิดแถบเลือกร่าง (D1/D2) ให้ด้วย
function setEssayUnit(unit) {
  currentUnit = unit;
  const isTask = (unit === 'task1' || unit === 'task2');

  // แสดง/ซ่อนแถบเลือกร่าง เฉพาะภารงาน
  const draftSel = document.getElementById('draftSelector');
  if (draftSel) draftSel.classList.toggle('d-none', !isTask);

  // ไฮไลต์ปุ่มหน่วยที่เลือก
  document.querySelectorAll('.essay-phase-btn').forEach(b => b.classList.remove('active-phase'));
  const activeBtn = document.querySelector(`.essay-phase-btn[data-unit="${unit}"]`);
  if (activeBtn) activeBtn.classList.add('active-phase');

  currentEssayPhase = computeEssayPhase(unit, currentDraft);
  updatePhaseBadge();
  loadEssayForPhase(currentEssayPhase);
}

// เลือกร่าง (D1/D2) ของภารงานหน่วยปัจจุบัน
function setEssayDraft(draft) {
  currentDraft = draft;
  document.querySelectorAll('.draft-btn').forEach(b => b.classList.remove('active-draft'));
  const activeBtn = document.querySelector(`.draft-btn[data-draft="${draft}"]`);
  if (activeBtn) activeBtn.classList.add('active-draft');

  currentEssayPhase = computeEssayPhase(currentUnit, draft);
  updatePhaseBadge();
  loadEssayForPhase(currentEssayPhase);
}

function updatePhaseBadge() {
  const badge = document.getElementById('currentPhaseBadge');
  badge.textContent = phaseLabels[currentEssayPhase] || currentEssayPhase;
  badge.className = 'badge ' + (phaseBadgeColors[currentEssayPhase] || 'bg-secondary');
}

// เปิดแก้ไขจากรายการที่บันทึกไว้ (แปลงคีย์ phase → หน่วย + ร่าง)
function openEssayPhase(phase) {
  const m = /^(task[12])_(d[12])$/.exec(phase);
  if (m) {
    currentDraft = m[2];
    document.querySelectorAll('.draft-btn').forEach(b => b.classList.toggle('active-draft', b.getAttribute('data-draft') === m[2]));
    setEssayUnit(m[1]);
  } else {
    setEssayUnit(phase);
  }
}

async function loadEssayForPhase(phase) {
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
      document.getElementById('essayTitle').value = data.data.essay_title || '';
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
    } else {
      document.getElementById('essayTitle').value = '';
      document.getElementById('essayIntro').value = '';
      document.getElementById('essayConclusion').value = '';
      addBodyParagraph();
      updateWordCount();
      document.getElementById('lastSavedInfo').classList.add('d-none');

      statusBadge.textContent = 'ยังไม่มีข้อมูล';
      statusBadge.className = 'badge bg-secondary small';
    }
  } catch(err) {
    statusBadge.textContent = '⚠️ โหลดไม่ได้';
    statusBadge.className = 'badge bg-danger small';
  }
}

function updateWordCount() {
  const introText = document.getElementById('essayIntro').value;
  const conclusionText = document.getElementById('essayConclusion').value;
  const bodyTexts = Array.from(document.querySelectorAll('.body-para-textarea')).map(ta => ta.value);

  const allTextCombined = [introText, ...bodyTexts, conclusionText].join('\n\n').trim();
  const chars = allTextCombined.length;

  const wordCountIntro = introText.trim() ? introText.trim().split(/[\s\n\r]+/).filter(w => w.length > 0).length : 0;
  const wordCountConclusion = conclusionText.trim() ? conclusionText.trim().split(/[\s\n\r]+/).filter(w => w.length > 0).length : 0;
  let wordCountBody = 0;
  bodyTexts.forEach(t => {
    wordCountBody += t.trim() ? t.trim().split(/[\s\n\r]+/).filter(w => w.length > 0).length : 0;
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
  }, 800);
}

async function saveEssay() {
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

  const essayContentObj = {
    introduction: intro,
    body: bodyParagraphs,
    conclusion: conclusion
  };

  const btn = document.getElementById('saveBtn');
  const origHTML = btn.innerHTML;
  btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>กำลังบันทึก...`;
  btn.disabled = true;

  try {
    const res = await fetch('api.php?action=save_essay', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        essay_phase:   currentEssayPhase,
        essay_title:   document.getElementById('essayTitle').value.trim(),
        essay_content: JSON.stringify(essayContentObj)
      })
    });
    const data = await res.json();

    if (data.success) {
      const now = new Date().toLocaleString('th-TH');
      document.getElementById('lastSavedTime').textContent = now;
      document.getElementById('lastSavedInfo').classList.remove('d-none');

      const statusBadge = document.getElementById('saveStatusBadge');
      statusBadge.textContent = `✓ บันทึกแล้ว (${data.word_count} คำ)`;
      statusBadge.className = 'badge bg-success small';
      statusBadge.classList.remove('d-none');

      showToast(`บันทึกเรียงความ "${phaseLabels[currentEssayPhase]}" สำเร็จ! (${data.word_count} คำ)`, 'success');
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
  document.getElementById('essayTitle').value = '';
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

  container.innerHTML = found.map(item => {
    let preview = '';
    try {
      const obj = JSON.parse(item.data.essay_content);
      if (obj && typeof obj === 'object' && obj.introduction !== undefined) {
        const intro = obj.introduction || '';
        const firstBody = (obj.body && obj.body[0]) ? obj.body[0] : '';
        const conc = obj.conclusion || '';
        preview = `[คำนำ] ${intro}\n[เนื้อเรื่อง] ${firstBody}...\n[สรุป] ${conc}`;
      } else {
        preview = item.data.essay_content || '';
      }
    } catch (e) {
      preview = item.data.essay_content || '';
    }

    const previewTrunc = preview.substring(0, 200).trim();
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
          <div class="text-muted small mb-2 text-start" style="white-space: pre-wrap; line-height: 1.5; max-height: 80px; overflow: hidden; position: relative;">
            ${previewTrunc}${preview.length >= 200 ? '...' : ''}
          </div>
          <div class="text-muted text-start" style="font-size:0.75rem;">
            <i class="bi bi-clock me-1"></i>บันทึกล่าสุด: ${dateStr}
          </div>
        </div>
      </div>
    `;
  }).join('');
}

// Init
(async function() {
  // ค่าเริ่มต้น: ภารงานหน่วยที่ 1 · ร่างที่ 1 (D1)
  setEssayUnit('task1');
  await loadSavedList();
})();
</script>

<?php require_once 'footer.php'; ?>
