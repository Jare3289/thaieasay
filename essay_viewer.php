<?php
$page_title = 'เรียงความนักเรียน (Essay Viewer) - ระบบประเมินเรียงความ';
require_once 'auth_helper.php';
require_login(); // ต้องล็อกอินก่อนเข้าหน้านี้

// เฉพาะครูและผู้เชี่ยวชาญเท่านั้นที่ดูเรียงความของนักเรียนทุกคนได้ (ให้สอดคล้องกับ api.php?action=get_all_essays)
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['teacher', 'expert'])) {
    header('Location: index.php');
    exit;
}

require_once 'header.php';
?>

<div id="view-essay-viewer" class="text-start">
  <div class="mb-3">
    <a href="index.php" class="btn btn-link text-decoration-none text-secondary fw-bold p-0">
      <i class="bi bi-arrow-left-short"></i> กลับหน้าเมนูหลัก
    </a>
  </div>

  <!-- ส่วนหัว -->
  <div class="bg-primary text-white p-4 rounded-4 shadow-sm mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3" style="background: linear-gradient(135deg, var(--primary-navy) 0%, var(--secondary-blue) 100%) !important;">
    <div>
      <h4 class="fw-bold mb-1"><i class="bi bi-pencil-square me-2"></i>เรียงความนักเรียน (Essay Viewer)</h4>
      <p class="mb-0 text-white-50 small">ตารางสถานะการส่งเรียงความรายบุคคลจากทุกห้องเรียนและทุกรอบการประเมิน — คลิกไอคอน PDF ในช่องเพื่อเปิดเรียงความของนักเรียนเป็นเอกสาร PDF</p>
    </div>
    <a href="dashboard.php" class="btn btn-light fw-bold px-3 rounded-pill text-nowrap shadow-sm">
      <i class="bi bi-speedometer2 me-1"></i> ไปแดชบอร์ด
    </a>
  </div>

  <?php $isTeacher = ($_SESSION['user']['role'] === 'teacher'); ?>
  <!-- กำหนดหัวข้อเรียงความแต่ละงาน (ครูกำหนด — นักเรียนจะเห็นตอนเขียน) -->
  <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
    <div class="card-body p-4">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-bookmark-star-fill text-primary me-2"></i>หัวข้อเรียงความแต่ละงาน <span class="text-muted fw-normal small">(นักเรียนจะเห็นหัวข้อนี้ตอนเขียน แทนการตั้งชื่อเรื่องเอง)</span></h6>
        <?php if ($isTeacher): ?>
        <button id="saveTopicsBtn" class="btn btn-primary btn-sm rounded-pill px-3" onclick="saveEssayTopics()">
          <i class="bi bi-save me-1"></i>บันทึกหัวข้อ
        </button>
        <?php endif; ?>
      </div>
      <div class="row g-3">
        <?php
          $topicFields = [
            'pretest'  => ['ก่อนเรียน', 'bi-pencil', 'text-primary'],
            'task1'    => ['หน่วยที่ 1', 'bi-journal-text', 'text-success'],
            'task2'    => ['หน่วยที่ 2', 'bi-journal-text', 'text-warning'],
            'posttest' => ['หลังเรียน', 'bi-mortarboard', 'text-danger'],
          ];
          foreach ($topicFields as $ph => $meta):
        ?>
        <div class="col-md-6">
          <label class="form-label fw-semibold small mb-1 <?php echo $meta[2]; ?>"><i class="bi <?php echo $meta[1]; ?> me-1"></i><?php echo $meta[0]; ?></label>
          <input type="text" id="topic_<?php echo $ph; ?>" maxlength="500"
            class="form-control form-control-sm border-2 rounded-3"
            placeholder="กำหนดหัวข้อสำหรับ<?php echo $meta[0]; ?>..."
            <?php echo $isTeacher ? '' : 'readonly'; ?>>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm rounded-4 bg-white mb-4 overflow-hidden">
    <div class="card-body p-4">

      <!-- แท็บแยกกลุ่มทดลอง / กลุ่มตัวอย่าง -->
      <ul class="nav nav-pills gap-2 bg-light p-2 rounded-3 border mb-4 flex-wrap" id="essayGroupTab" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active fw-bold text-dark px-4 py-2 rounded-3" data-group="all" onclick="setEssayGroup(this)" type="button">
            <i class="bi bi-collection me-1"></i> ทั้งหมด
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link fw-bold text-dark px-4 py-2 rounded-3" data-group="กลุ่มทดลอง" onclick="setEssayGroup(this)" type="button">
            <i class="bi bi-flask me-1"></i> กลุ่มทดลอง
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link fw-bold text-dark px-4 py-2 rounded-3" data-group="กลุ่มตัวอย่าง" onclick="setEssayGroup(this)" type="button">
            <i class="bi bi-people me-1"></i> กลุ่มตัวอย่าง
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link fw-bold text-dark px-4 py-2 rounded-3" data-group="__none__" onclick="setEssayGroup(this)" type="button">
            <i class="bi bi-question-circle me-1"></i> ยังไม่ระบุกลุ่ม
          </button>
        </li>
      </ul>

      <!-- แถบตัวกรอง -->
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <p class="text-muted small mb-0">แสดงสถานะการส่งเรียงความของนักเรียนแต่ละคน แยกตามรอบการประเมิน — เลือกกรองตามกลุ่มหรือห้องเรียนได้</p>
        <div class="d-flex gap-2 flex-wrap align-items-center">
          <select id="essayClassroomFilter" onchange="filterEssayViewer()" class="form-select form-select-sm border-2 rounded-pill" style="width:auto;">
            <option value="all">ทุกห้องเรียน</option>
          </select>
          <input type="text" id="essaySearchInput" onkeyup="filterEssayViewer()" class="form-control form-control-sm border-2 rounded-pill" placeholder="ค้นหาชื่อ หรือรหัสนักเรียน..." style="width:220px;">
          <button class="btn btn-danger btn-sm rounded-pill px-3" onclick="openRoomReport()">
            <i class="bi bi-file-earmark-pdf me-1"></i>ดูรายงานทั้งห้อง (PDF)
          </button>
          <button class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="openPrintDoc()">
            <i class="bi bi-files me-1"></i>รวมเรียงความ (PDF)
          </button>
          <button class="btn btn-outline-primary btn-sm rounded-pill px-3" onclick="exportEssaysCSV()">
            <i class="bi bi-download me-1"></i>ส่งออก CSV
          </button>
        </div>
      </div>

      <!-- แถบสรุปตัวเลข: จำนวนนักเรียน และจำนวนที่ส่งในแต่ละรอบ -->
      <div class="row g-3 mb-4 row-cols-2 row-cols-md-5" id="essaySummaryRow">
        <div class="col">
          <div class="card border-0 rounded-3 p-3 text-center bg-light">
            <div class="fs-4 fw-bold text-primary" id="essayStatStudents">-</div>
            <div class="text-muted small">นักเรียน (คน)</div>
          </div>
        </div>
        <div class="col">
          <div class="card border-0 rounded-3 p-3 text-center bg-light">
            <div class="fs-4 fw-bold text-primary" id="essayStatPre">-</div>
            <div class="text-muted small">ส่งก่อนเรียน</div>
          </div>
        </div>
        <div class="col">
          <div class="card border-0 rounded-3 p-3 text-center bg-light">
            <div class="fs-4 fw-bold text-success" id="essayStatT1">-</div>
            <div class="text-muted small">ส่งหน่วยที่ 1 (D2)</div>
          </div>
        </div>
        <div class="col">
          <div class="card border-0 rounded-3 p-3 text-center bg-light">
            <div class="fs-4 fw-bold text-warning" id="essayStatT2">-</div>
            <div class="text-muted small">ส่งหน่วยที่ 2 (D2)</div>
          </div>
        </div>
        <div class="col">
          <div class="card border-0 rounded-3 p-3 text-center bg-light">
            <div class="fs-4 fw-bold text-danger" id="essayStatPost">-</div>
            <div class="text-muted small">ส่งหลังเรียน</div>
          </div>
        </div>
      </div>

      <!-- ตารางสถานะการส่งเรียงความ -->
      <div id="essayViewerContainer" style="max-height:640px; overflow:auto;">
        <div class="text-center text-muted py-5">
          <i class="bi bi-hourglass-split fs-3 d-block mb-2"></i>กำลังโหลดเรียงความ...
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  // ========== Essay Viewer (ตารางสถานะการส่งเรียงความรายบุคคล) ==========
  let allEssaysCache = null;
  let currentEssayGroup = 'all'; // all | กลุ่มทดลอง | กลุ่มตัวอย่าง | __none__

  const IS_TEACHER = <?php echo ($_SESSION['user']['role'] === 'teacher') ? 'true' : 'false'; ?>;

  // คอลัมน์ในตาราง: ก่อนเรียน · หน่วยที่ 1 (D1,D2) · หน่วยที่ 2 (D1,D2) · หลังเรียน
  // ภาระงานแต่ละหน่วยแตกเป็น 2 ร่าง: D1 = ร่างที่ 1, D2 = ร่างที่ 2 (ให้คะแนนเฉพาะ D2)
  const ESSAY_PHASE_KEYS = ['pretest', 'task1_d1', 'task1_d2', 'task2_d1', 'task2_d2', 'posttest'];

  const essayPhaseLabels = {
    pretest:  'ก่อนเรียน (Pretest)',
    task1_d1: 'ภาระงาน หน่วยที่ 1 · ร่างที่ 1 (D1)',
    task1_d2: 'ภาระงาน หน่วยที่ 1 · ร่างที่ 2 (D2)',
    task2_d1: 'ภาระงาน หน่วยที่ 2 · ร่างที่ 1 (D1)',
    task2_d2: 'ภาระงาน หน่วยที่ 2 · ร่างที่ 2 (D2)',
    posttest: 'หลังเรียน (Posttest)'
  };

  // สลับแท็บกลุ่มทดลอง/กลุ่มตัวอย่าง
  function setEssayGroup(btn) {
    currentEssayGroup = btn.getAttribute('data-group') || 'all';
    // ครู: จำค่ากลุ่มไว้ให้ทุกหน้าครูใช้ร่วมกัน (ยกเว้น "ยังไม่ระบุกลุ่ม" ซึ่งเป็นมุมมองเฉพาะหน้านี้)
    if (IS_TEACHER && window.TEG && currentEssayGroup !== '__none__') TEG.set(currentEssayGroup);
    document.querySelectorAll('#essayGroupTab .nav-link').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    filterEssayViewer();
  }

  // ตั้งกลุ่มเริ่มต้นจากค่าที่จำไว้ร่วมกันทุกหน้า (เฉพาะครู; ค่าเริ่มต้น = กลุ่มตัวอย่าง)
  function initEssayGroupFromStore() {
    if (!IS_TEACHER || !window.TEG) return;
    const want = TEG.get();
    const btn = document.querySelector(`#essayGroupTab .nav-link[data-group="${want}"]`);
    if (btn) {
      currentEssayGroup = want;
      document.querySelectorAll('#essayGroupTab .nav-link').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
    }
  }

  // แปลงอักขระพิเศษของ HTML เพื่อกันสคริปต์ฝังในข้อมูลที่นักเรียนกรอก (ชื่อ/ห้อง ฯลฯ)
  function escapeHtml(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  async function loadEssayViewer() {
    const container = document.getElementById('essayViewerContainer');
    container.innerHTML = '<div class="text-center py-5 text-muted"><span class="spinner-border spinner-border-sm me-2"></span>กำลังโหลดเรียงความ...</div>';
    try {
      const res = await fetch('api.php?action=get_all_essays');
      const data = await res.json();
      if (data.success) {
        allEssaysCache = data.essays || [];
        populateClassroomFilter(allEssaysCache);
        renderEssayViewer(allEssaysCache);
      } else {
        container.innerHTML = `<div class="text-center py-5 text-danger fw-bold">เกิดข้อผิดพลาด: ${data.error || 'ไม่สามารถโหลดข้อมูลได้'}</div>`;
      }
    } catch(err) {
      container.innerHTML = '<div class="text-center py-5 text-danger fw-bold">ไม่สามารถโหลดข้อมูลได้</div>';
    }
  }

  // เติมรายการห้องเรียนที่มีอยู่จริงลงในตัวกรอง (เรียงตามชื่อห้อง)
  function populateClassroomFilter(essays) {
    const sel = document.getElementById('essayClassroomFilter');
    if (!sel) return;
    const rooms = [...new Set(essays.map(e => (e.classroom || '').trim()).filter(r => r !== ''))]
      .sort((a, b) => a.localeCompare(b, 'th', { numeric: true }));
    // เก็บค่าเดิมไว้ (ถ้ามี) แล้วสร้างรายการใหม่
    const current = sel.value;
    sel.innerHTML = '<option value="all">ทุกห้องเรียน</option>' +
      rooms.map(r => `<option value="${escapeHtml(r)}">ห้อง ${escapeHtml(r)}</option>`).join('');
    if (current && [...sel.options].some(o => o.value === current)) sel.value = current;
  }

  function filterEssayViewer() {
    if (!allEssaysCache) return;
    renderEssayViewer(allEssaysCache);
  }

  function getEssaySearchableText(e) {
    let searchableText = e.essay_content || '';
    try {
      const obj = JSON.parse(e.essay_content);
      if (obj && typeof obj === 'object' && obj.introduction !== undefined) {
        searchableText = (obj.introduction || '') + ' ' + (obj.body ? obj.body.join(' ') : '') + ' ' + (obj.conclusion || '');
      }
    } catch(err) {}
    return searchableText;
  }

  // กรองรายการเรียงความ (ระดับ "ชิ้นงาน") ตามกลุ่ม/ห้อง/คำค้น — ยังไม่ยุบรวมเป็นรายคน
  function applyEssayFilters(essays) {
    const classroomFilter = (document.getElementById('essayClassroomFilter') || {}).value || 'all';
    const query           = ((document.getElementById('essaySearchInput') || {}).value || '').toLowerCase().trim();

    return essays.filter(e => {
      const grp = (e.student_group || '').trim();
      if (currentEssayGroup === '__none__') { if (grp !== '') return false; }
      else if (currentEssayGroup !== 'all' && grp !== currentEssayGroup) return false;
      if (classroomFilter !== 'all' && (e.classroom || '').trim() !== classroomFilter) return false;
      if (query) {
        const combined = ((e.student_name || '') + ' ' + (e.student_id || '') + ' ' + (e.essay_title || '') + ' ' + getEssaySearchableText(e)).toLowerCase();
        if (!combined.includes(query)) return false;
      }
      return true;
    });
  }

  // ยุบรวมชิ้นงานเป็น "รายคน": รวมทุกรอบการประเมินของนักเรียนคนเดียวไว้ในระเบียนเดียว
  function pivotByStudent(essays) {
    const map = new Map();
    essays.forEach(e => {
      const sid = (e.student_id || '').trim();
      if (!map.has(sid)) {
        map.set(sid, {
          student_id:    sid,
          student_name:  e.student_name || '',
          classroom:     (e.classroom || '').trim(),
          student_group: (e.student_group || '').trim(),
          phases:        {}
        });
      }
      const rec = map.get(sid);
      rec.phases[e.essay_phase] = e;
      if (e.student_name) rec.student_name = e.student_name;
      if (e.classroom)    rec.classroom    = (e.classroom || '').trim();
    });
    return [...map.values()].sort((a, b) => {
      const r = (a.classroom || '').localeCompare(b.classroom || '', 'th', { numeric: true });
      return r !== 0 ? r : (a.student_id || '').localeCompare(b.student_id || '', 'th', { numeric: true });
    });
  }

  // ช่องรอบการประเมินหนึ่งช่อง: มีเรียงความ → ไอคอน PDF (กดเปิดเป็นเอกสาร) / ยังไม่มี → เว้นว่าง
  // graded = ร่างที่ให้คะแนน (D2) จะไฮไลต์พื้นหลังอ่อน ๆ
  function buildPhaseCell(rec, phaseKey, graded) {
    const cls = 'text-center' + (graded ? ' table-warning' : '');
    const e = rec.phases[phaseKey];
    if (!e) return `<td class="${cls} text-muted"></td>`;
    return `<td class="${cls}">
      <button class="btn btn-sm btn-outline-danger rounded-circle p-0 d-inline-flex align-items-center justify-content-center"
        style="width:36px;height:36px;" title="เปิด PDF เรียงความ (${escapeHtml(essayPhaseLabels[phaseKey] || phaseKey)})"
        onclick='openEssayPdf(${JSON.stringify(rec.student_id)}, ${JSON.stringify(phaseKey)})'>
        <i class="bi bi-file-earmark-pdf-fill fs-6"></i>
      </button>
    </td>`;
  }

  function renderEssayViewer(essays) {
    const container = document.getElementById('essayViewerContainer');
    const filtered  = applyEssayFilters(essays);
    const students  = pivotByStudent(filtered);
    const multiRoom = ((document.getElementById('essayClassroomFilter') || {}).value || 'all') === 'all';

    // แถบสรุป: จำนวนนักเรียน และจำนวนที่ส่ง (ภาระงานนับจากร่างที่ให้คะแนน = D2)
    const cnt = key => students.reduce((n, rec) => n + (rec.phases[key] ? 1 : 0), 0);
    const setEl = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = Number(val).toLocaleString('th-TH'); };
    setEl('essayStatStudents', students.length);
    setEl('essayStatPre',  cnt('pretest'));
    setEl('essayStatT1',   cnt('task1_d2'));
    setEl('essayStatT2',   cnt('task2_d2'));
    setEl('essayStatPost', cnt('posttest'));

    if (students.length === 0) {
      container.innerHTML = '<div class="text-center py-5 text-muted"><i class="bi bi-inbox fs-3 d-block mb-2"></i>ไม่พบเรียงความที่ตรงกับเงื่อนไข</div>';
      return;
    }

    const bodyRows = students.map(rec => {
      const roomBadge = (multiRoom && rec.classroom)
        ? ` <span class="badge bg-info-subtle text-info-emphasis small align-middle">ห้อง ${escapeHtml(rec.classroom)}</span>` : '';
      return `<tr>
        <td class="fw-semibold text-nowrap align-middle">${escapeHtml(rec.student_id)}</td>
        <td class="align-middle">${escapeHtml(rec.student_name)}${roomBadge}</td>
        ${buildPhaseCell(rec, 'pretest', false)}
        ${buildPhaseCell(rec, 'task1_d1', false)}
        ${buildPhaseCell(rec, 'task1_d2', true)}
        ${buildPhaseCell(rec, 'task2_d1', false)}
        ${buildPhaseCell(rec, 'task2_d2', true)}
        ${buildPhaseCell(rec, 'posttest', false)}
      </tr>`;
    }).join('');

    // หัวตาราง 2 ชั้น: ภาระงานแต่ละหน่วยแตกเป็นคอลัมน์ย่อย D1 / D2 (D2 = ร่างที่ให้คะแนน)
    const d2Head = 'text-center small table-warning text-nowrap';
    container.innerHTML = `
      <table class="table table-hover table-bordered align-middle mb-0 text-center">
        <thead class="table-light" style="position:sticky; top:0; z-index:1;">
          <tr>
            <th rowspan="2" class="align-middle text-nowrap text-start">รหัสนักเรียน</th>
            <th rowspan="2" class="align-middle text-nowrap text-start">ชื่อสกุล</th>
            <th rowspan="2" class="align-middle text-nowrap">ก่อนเรียน</th>
            <th colspan="2" class="text-nowrap">หน่วยที่ 1</th>
            <th colspan="2" class="text-nowrap">หน่วยที่ 2</th>
            <th rowspan="2" class="align-middle text-nowrap">หลังเรียน</th>
          </tr>
          <tr>
            <th class="text-center small text-nowrap">D1</th>
            <th class="${d2Head}" title="ร่างที่ให้คะแนน">D2 <i class="bi bi-star-fill text-warning"></i></th>
            <th class="text-center small text-nowrap">D1</th>
            <th class="${d2Head}" title="ร่างที่ให้คะแนน">D2 <i class="bi bi-star-fill text-warning"></i></th>
          </tr>
        </thead>
        <tbody class="text-start">${bodyRows}</tbody>
      </table>
      <div class="text-muted small mt-2"><i class="bi bi-star-fill text-warning me-1"></i>D2 = ร่างที่ 2 (ร่างที่คุณครูใช้ให้คะแนน) · D1 = ร่างที่ 1</div>`;
  }

  // เปิดเรียงความของนักเรียนคนเดียว (รอบที่ระบุ) เป็นเอกสาร PDF ฝั่งเซิร์ฟเวอร์
  function openEssayPdf(studentId, phase) {
    const params = new URLSearchParams();
    params.set('student_id', studentId);
    params.set('essay_phase', phase);
    window.open('essay_print.php?' + params.toString(), '_blank');
  }

  // รายงานสรุปทั้งห้อง (ตารางสถานะการส่ง) เป็น PDF อย่างเป็นทางการ — ตามกลุ่ม/ห้องที่กรองอยู่
  function openRoomReport() {
    if (!allEssaysCache) { showToast('กรุณาโหลดข้อมูลก่อน', 'error'); return; }
    const params = new URLSearchParams();
    params.set('mode', 'summary');
    params.set('group', currentEssayGroup);
    params.set('classroom', (document.getElementById('essayClassroomFilter') || {}).value || 'all');
    const q = ((document.getElementById('essaySearchInput') || {}).value || '').trim();
    if (q) params.set('q', q);
    window.open('essay_print.php?' + params.toString(), '_blank');
  }

  // เปิดเอกสารรวม "ตัวเรียงความ" ทุกชิ้นตามตัวกรองปัจจุบัน (แยกหน้า/คน) เป็น PDF
  function openPrintDoc() {
    if (!allEssaysCache) { showToast('กรุณาโหลดข้อมูลก่อน', 'error'); return; }
    if (applyEssayFilters(allEssaysCache).length === 0) {
      showToast('ไม่มีเรียงความที่ตรงกับเงื่อนไขให้จัดทำเอกสาร', 'error'); return;
    }
    const params = new URLSearchParams();
    params.set('group', currentEssayGroup);
    params.set('classroom', (document.getElementById('essayClassroomFilter') || {}).value || 'all');
    params.set('phase', 'all');
    const q = ((document.getElementById('essaySearchInput') || {}).value || '').trim();
    if (q) params.set('q', q);
    window.open('essay_print.php?' + params.toString(), '_blank');
  }

  function exportEssaysCSV() {
    if (!allEssaysCache) { showToast('กรุณาโหลดข้อมูลก่อน', 'error'); return; }
    const filtered = applyEssayFilters(allEssaysCache);
    if (filtered.length === 0) { showToast('ไม่มีเรียงความที่ตรงกับเงื่อนไขให้ส่งออก', 'error'); return; }

    // ป้องกัน CSV formula injection: ค่าที่ขึ้นต้นด้วย = + - @ (หรือ tab/CR) อาจถูกโปรแกรมตารางตีความเป็นสูตร
    // จึงเติมเครื่องหมาย ' นำหน้าเพื่อบังคับให้เป็นข้อความล้วนก่อนครอบด้วยเครื่องหมายคำพูด
    const esc = s => {
      let v = (s == null ? '' : String(s));
      if (/^[=+\-@\t\r]/.test(v)) v = "'" + v;
      return '"' + v.replace(/"/g,'""').replace(/\n/g,' ') + '"';
    };
    let csv = '﻿' + 'รหัสนักเรียน,ชื่อ-สกุล,ห้องเรียน,กลุ่ม,รอบการประเมิน,หัวข้อ (ครูกำหนด),ส่วนคำนำ (Introduction),ส่วนเนื้อเรื่อง (Body),ส่วนสรุป (Conclusion),วันที่บันทึก\n';
    filtered.forEach(e => {
      const dt = new Date(e.updated_at||e.created_at).toLocaleString('th-TH');
      let intro = '';
      let bodyText = '';
      let conc = '';
      try {
        const obj = JSON.parse(e.essay_content);
        if (obj && typeof obj === 'object' && obj.introduction !== undefined) {
          intro = obj.introduction || '';
          bodyText = obj.body ? obj.body.join('\n\n') : '';
          conc = obj.conclusion || '';
        } else {
          intro = e.essay_content || '';
        }
      } catch(err) {
        intro = e.essay_content || '';
      }
      csv += [esc(e.student_id), esc(e.student_name), esc(e.classroom), esc(e.student_group), esc(essayPhaseLabels[e.essay_phase]||e.essay_phase),
              esc(e.essay_title), esc(intro), esc(bodyText), esc(conc), esc(dt)].join(',') + '\n';
    });

    const blob = new Blob([csv], { type:'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'student_essays.csv';
    link.click();
  }

  // ปุ่มเลือกกลุ่มบน navbar เปลี่ยน → อัปเดตแท็บกลุ่มและกรองรายการทันที (เฉพาะครู)
  window.onTEGChange = function() {
    initEssayGroupFromStore();
    filterEssayViewer();
  };

  // ===== หัวข้อเรียงความที่ครูกำหนด =====
  const TOPIC_PHASES = ['pretest', 'task1', 'task2', 'posttest'];

  async function loadEssayTopics() {
    try {
      const res = await fetch('api.php?action=get_essay_topics');
      const data = await res.json();
      if (data.success && data.topics) {
        TOPIC_PHASES.forEach(ph => {
          const el = document.getElementById('topic_' + ph);
          if (el) el.value = data.topics[ph] || '';
        });
      }
    } catch (e) { /* เงียบไว้ */ }
  }

  async function saveEssayTopics() {
    const btn = document.getElementById('saveTopicsBtn');
    if (!btn) return;
    const orig = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>กำลังบันทึก...';
    btn.disabled = true;
    try {
      for (const ph of TOPIC_PHASES) {
        const el = document.getElementById('topic_' + ph);
        if (!el) continue;
        await fetch('api.php?action=save_essay_topic', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ phase: ph, topic: el.value.trim() })
        });
      }
      showToast('บันทึกหัวข้อเรียงความเรียบร้อยแล้ว', 'success');
    } catch (e) {
      showToast('บันทึกหัวข้อไม่สำเร็จ', 'error');
    } finally {
      btn.innerHTML = orig;
      btn.disabled = false;
    }
  }

  // --- เริ่มรันอัตโนมัติ ---
  loadEssayTopics();
  initEssayGroupFromStore();
  loadEssayViewer();
</script>

<?php
require_once 'footer.php';
?>
