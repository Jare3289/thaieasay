<?php
$page_title = 'เรียงความนักเรียน (Essay Viewer) - ระบบประเมินเรียงความอัจฉริยะ';
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
      <p class="mb-0 text-white-50 small">อ่านเนื้อหาเรียงความที่นักเรียนพิมพ์ส่งได้จากทุกห้องเรียนและทุกรอบการประเมิน เพื่อประกอบการตรวจให้คะแนนและการวิเคราะห์เชิงคุณภาพ</p>
    </div>
    <a href="dashboard.php" class="btn btn-light fw-bold px-3 rounded-pill text-nowrap shadow-sm">
      <i class="bi bi-speedometer2 me-1"></i> ไปแดชบอร์ด
    </a>
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
        <p class="text-muted small mb-0">แสดงเรียงความของนักเรียนทุกคน — เลือกกรองตามกลุ่ม ห้องเรียน หรือรอบการประเมินได้ตามต้องการ</p>
        <div class="d-flex gap-2 flex-wrap align-items-center">
          <select id="essayClassroomFilter" onchange="filterEssayViewer()" class="form-select form-select-sm border-2 rounded-pill" style="width:auto;">
            <option value="all">ทุกห้องเรียน</option>
          </select>
          <select id="essayPhaseFilter" onchange="filterEssayViewer()" class="form-select form-select-sm border-2 rounded-pill" style="width:auto;">
            <option value="all">ทุกรอบ</option>
            <option value="pretest">ก่อนเรียน</option>
            <option value="task1">ภารงาน หน่วยที่ 1</option>
            <option value="task2">ภารงาน หน่วยที่ 2</option>
            <option value="posttest">หลังเรียน</option>
          </select>
          <input type="text" id="essaySearchInput" onkeyup="filterEssayViewer()" class="form-control form-control-sm border-2 rounded-pill" placeholder="ค้นหาชื่อ รหัส หรือเนื้อหา..." style="width:220px;">
          <button class="btn btn-danger btn-sm rounded-pill px-3" onclick="exportEssaysPDF()">
            <i class="bi bi-file-earmark-pdf me-1"></i>จัดทำเอกสาร PDF
          </button>
          <button class="btn btn-outline-primary btn-sm rounded-pill px-3" onclick="exportEssaysCSV()">
            <i class="bi bi-download me-1"></i>ส่งออก CSV
          </button>
        </div>
      </div>

      <!-- แถบสรุปตัวเลข -->
      <div class="row g-3 mb-4" id="essaySummaryRow">
        <div class="col-md-3 col-6">
          <div class="card border-0 rounded-3 p-3 text-center bg-light">
            <div class="fs-4 fw-bold text-primary" id="essayStatTotal">-</div>
            <div class="text-muted small">ส่งเรียงความแล้ว</div>
          </div>
        </div>
        <div class="col-md-3 col-6">
          <div class="card border-0 rounded-3 p-3 text-center bg-light">
            <div class="fs-4 fw-bold text-success" id="essayStatAvgWords">-</div>
            <div class="text-muted small">เฉลี่ย คำ/ชิ้น</div>
          </div>
        </div>
        <div class="col-md-3 col-6">
          <div class="card border-0 rounded-3 p-3 text-center bg-light">
            <div class="fs-4 fw-bold text-warning" id="essayStatMaxWords">-</div>
            <div class="text-muted small">มากสุด (คำ)</div>
          </div>
        </div>
        <div class="col-md-3 col-6">
          <div class="card border-0 rounded-3 p-3 text-center bg-light">
            <div class="fs-4 fw-bold text-danger" id="essayStatMinWords">-</div>
            <div class="text-muted small">น้อยสุด (คำ)</div>
          </div>
        </div>
      </div>

      <!-- การ์ดเรียงความ -->
      <div id="essayViewerContainer" style="max-height:640px; overflow-y:auto;">
        <div class="text-center text-muted py-5">
          <i class="bi bi-hourglass-split fs-3 d-block mb-2"></i>กำลังโหลดเรียงความ...
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  // ========== Essay Viewer (หน้ารวมทุกห้องเรียน) ==========
  let allEssaysCache = null;
  let currentEssayGroup = 'all'; // all | กลุ่มทดลอง | กลุ่มตัวอย่าง | __none__

  // สลับแท็บกลุ่มทดลอง/กลุ่มตัวอย่าง
  function setEssayGroup(btn) {
    currentEssayGroup = btn.getAttribute('data-group') || 'all';
    document.querySelectorAll('#essayGroupTab .nav-link').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    filterEssayViewer();
  }

  const essayGroupBadge = {
    'กลุ่มทดลอง':   'bg-primary',
    'กลุ่มตัวอย่าง': 'bg-warning text-dark'
  };

  // แปลงอักขระพิเศษของ HTML เพื่อกันสคริปต์ฝังในข้อมูลที่นักเรียนกรอก (ชื่อเรื่อง/ชื่อ/ห้อง ฯลฯ)
  function escapeHtml(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  const essayPhaseLabels = {
    pretest:  'ก่อนเรียน (Pretest)',
    task1:    'ภารงาน หน่วยที่ 1',
    task2:    'ภารงาน หน่วยที่ 2',
    posttest: 'หลังเรียน (Posttest)'
  };
  const essayPhaseBadgeClass = {
    pretest: 'bg-primary',
    task1: 'bg-success',
    task2: 'bg-warning text-dark',
    posttest: 'bg-danger'
  };

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

  function formatEssayHTML(contentStr) {
    if (!contentStr) return '<em class="text-muted">ไม่มีเนื้อหาเรียงความ</em>';
    try {
      const obj = JSON.parse(contentStr);
      if (obj && typeof obj === 'object' && obj.introduction !== undefined) {
        let html = '';
        if (obj.introduction) {
          html += `
            <div class="mb-3">
              <span class="badge bg-primary bg-opacity-10 text-primary fw-bold mb-1"><i class="bi bi-pencil-fill me-1"></i>ส่วนคำนำ (Introduction)</span>
              <div class="p-3 bg-white rounded-3 border text-dark" style="white-space:pre-wrap; line-height:1.7;">${obj.introduction.replace(/</g,'&lt;').replace(/>/g,'&gt;')}</div>
            </div>`;
        }
        if (obj.body && Array.isArray(obj.body)) {
          obj.body.forEach((paraText, i) => {
            if (paraText) {
              html += `
                <div class="mb-3">
                  <span class="badge bg-success bg-opacity-10 text-success fw-bold mb-1"><i class="bi bi-book-fill me-1"></i>ส่วนเนื้อเรื่อง ย่อหน้าที่ ${i+1} (Body Paragraph)</span>
                  <div class="p-3 bg-white rounded-3 border text-dark" style="white-space:pre-wrap; line-height:1.7;">${paraText.replace(/</g,'&lt;').replace(/>/g,'&gt;')}</div>
                </div>`;
            }
          });
        }
        if (obj.conclusion) {
          html += `
            <div class="mb-0">
              <span class="badge bg-danger bg-opacity-10 text-danger fw-bold mb-1"><i class="bi bi-award-fill me-1"></i>ส่วนสรุป (Conclusion)</span>
              <div class="p-3 bg-white rounded-3 border text-dark" style="white-space:pre-wrap; line-height:1.7;">${obj.conclusion.replace(/</g,'&lt;').replace(/>/g,'&gt;')}</div>
            </div>`;
        }
        return html;
      }
    } catch(e) {}
    return `<div class="p-3 bg-white rounded-3 border text-dark" style="white-space:pre-wrap; line-height:1.7;">${contentStr.replace(/</g,'&lt;').replace(/>/g,'&gt;')}</div>`;
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

  function applyEssayFilters(essays) {
    const classroomFilter = (document.getElementById('essayClassroomFilter') || {}).value || 'all';
    const phaseFilter     = (document.getElementById('essayPhaseFilter') || {}).value || 'all';
    const query           = ((document.getElementById('essaySearchInput') || {}).value || '').toLowerCase().trim();

    return essays.filter(e => {
      const grp = (e.student_group || '').trim();
      if (currentEssayGroup === '__none__') { if (grp !== '') return false; }
      else if (currentEssayGroup !== 'all' && grp !== currentEssayGroup) return false;
      if (classroomFilter !== 'all' && (e.classroom || '').trim() !== classroomFilter) return false;
      if (phaseFilter !== 'all' && e.essay_phase !== phaseFilter) return false;
      if (query) {
        const combined = ((e.student_name || '') + ' ' + (e.student_id || '') + ' ' + (e.essay_title || '') + ' ' + getEssaySearchableText(e)).toLowerCase();
        if (!combined.includes(query)) return false;
      }
      return true;
    });
  }

  function renderEssayViewer(essays) {
    const container = document.getElementById('essayViewerContainer');
    const filtered  = applyEssayFilters(essays);

    // อัปเดตแถบสรุปตัวเลข
    const total    = filtered.length;
    const words    = filtered.map(e => parseInt(e.word_count || 0));
    const avgWords = total > 0 ? Math.round(words.reduce((a,b)=>a+b,0)/total) : 0;
    const maxWords = total > 0 ? Math.max(...words) : 0;
    const minWords = total > 0 ? Math.min(...words) : 0;

    const setEl = (id, val) => { const el = document.getElementById(id); if(el) el.textContent = val.toLocaleString('th-TH'); };
    setEl('essayStatTotal', total);
    setEl('essayStatAvgWords', avgWords);
    setEl('essayStatMaxWords', maxWords);
    setEl('essayStatMinWords', minWords);

    if (filtered.length === 0) {
      container.innerHTML = '<div class="text-center py-5 text-muted"><i class="bi bi-inbox fs-3 d-block mb-2"></i>ไม่พบเรียงความที่ตรงกับเงื่อนไข</div>';
      return;
    }

    container.innerHTML = filtered.map((e, idx) => {
      let previewText = '';
      try {
        const obj = JSON.parse(e.essay_content);
        if (obj && typeof obj === 'object' && obj.introduction !== undefined) {
          const firstBody = (obj.body && obj.body[0]) ? obj.body[0] : '';
          previewText = `[คำนำ] ${obj.introduction || ''}\n[เนื้อเรื่อง] ${firstBody}...`;
        } else {
          previewText = e.essay_content || '';
        }
      } catch(err) {
        previewText = e.essay_content || '';
      }

      const previewTrunc = previewText.substring(0, 300).trim();
      const hasMore      = previewText.length > 300;
      const dt           = new Date(e.updated_at || e.created_at);
      const dateStr      = dt.toLocaleString('th-TH', { year:'numeric', month:'short', day:'numeric', hour:'2-digit', minute:'2-digit' });
      const badgeClass   = essayPhaseBadgeClass[e.essay_phase] || 'bg-secondary';
      const phaseLabel   = escapeHtml(essayPhaseLabels[e.essay_phase] || e.essay_phase);
      const wordCount    = parseInt(e.word_count || 0).toLocaleString('th-TH');
      const room         = (e.classroom || '').trim();
      const grp          = (e.student_group || '').trim();
      const grpBadgeCls  = essayGroupBadge[grp] || 'bg-secondary';
      const studentName  = escapeHtml(e.student_name);
      const studentId    = escapeHtml(e.student_id);
      const essayTitle   = escapeHtml(e.essay_title);
      const roomSafe     = escapeHtml(room);
      const grpSafe      = escapeHtml(grp);
      // ใช้ลำดับที่ (index) ของรายการที่แสดง เป็น id — ปลอดภัยและไม่ชนกัน แม้รหัส/รอบจะมีอักขระพิเศษ
      const essayId      = `essay_${idx}`;
      const formattedHTML = formatEssayHTML(e.essay_content);

      return `
        <div class="card border-0 rounded-3 mb-3 shadow-sm" style="border-left: 4px solid #0d7377 !important;">
          <div class="card-body p-3">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-2">
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge ${badgeClass} px-2 py-1 small">${phaseLabel}</span>
                ${grp ? `<span class="badge ${grpBadgeCls} px-2 py-1 small">${grpSafe}</span>` : ''}
                ${room ? `<span class="badge bg-info-subtle text-info-emphasis px-2 py-1 small"><i class="bi bi-door-open me-1"></i>ห้อง ${roomSafe}</span>` : ''}
                <span class="fw-bold text-dark">${studentName} <span class="text-muted fw-normal small">(${studentId})</span></span>
                ${e.essay_title ? `<span class="text-secondary small fst-italic">— ${essayTitle}</span>` : ''}
              </div>
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary-subtle text-primary rounded-pill px-2 py-1 small">
                  <i class="bi bi-fonts me-1"></i>${wordCount} คำ
                </span>
                <button class="btn btn-outline-primary btn-sm rounded-pill px-3 py-1 small" style="font-size:0.75rem;"
                  onclick="document.getElementById('${essayId}').classList.toggle('d-none')">
                  <i class="bi bi-eye me-1"></i>เปิดดูเต็มยศ
                </button>
              </div>
            </div>

            <!-- ตัวอย่างเนื้อหา -->
            <div class="text-secondary small mb-2 text-start" style="white-space:pre-wrap; line-height:1.6; background:#f8f9fa; padding:10px; border-radius:6px; max-height:100px; overflow:hidden;">
              ${previewTrunc.replace(/</g,'&lt;').replace(/>/g,'&gt;')}${hasMore ? '...' : ''}
            </div>

            <!-- เนื้อหาเต็ม (ซ่อนไว้ก่อน) -->
            <div id="${essayId}" class="d-none text-dark small mt-3 p-3 bg-light rounded-3" style="font-family:'Sarabun',sans-serif; background-color: #fffdf9 !important; border: 1px solid #f0e6c8 !important;">
              ${formattedHTML}
            </div>

            <div class="text-muted text-start mt-2" style="font-size:0.72rem;">
              <i class="bi bi-clock me-1"></i>บันทึกล่าสุด: ${dateStr}
            </div>
          </div>
        </div>
      `;
    }).join('');
  }

  // สร้างบล็อกเนื้อหาเรียงความสำหรับเอกสาร PDF พร้อมป้ายกำกับ คำนำ/เนื้อเรื่อง/สรุป (ใช้ inline style เพราะเปิดในหน้าต่างแยก)
  function buildEssayPrintSections(contentStr) {
    const wrap = (cls, label, text) =>
      '<div class="sec ' + cls + '"><span class="lbl">' + label + '</span>' +
      '<div class="txt">' + escapeHtml(text) + '</div></div>';
    try {
      const obj = JSON.parse(contentStr);
      if (obj && typeof obj === 'object' && obj.introduction !== undefined) {
        let html = '';
        if (obj.introduction) html += wrap('intro', '✍️ ส่วนคำนำ (Introduction)', obj.introduction);
        if (Array.isArray(obj.body)) {
          obj.body.forEach((p, i) => { if (p) html += wrap('body', '📖 ส่วนเนื้อเรื่อง ย่อหน้าที่ ' + (i + 1) + ' (Body)', p); });
        }
        if (obj.conclusion) html += wrap('concl', '🏁 ส่วนสรุป (Conclusion)', obj.conclusion);
        return html || '<div class="no-content">ไม่มีเนื้อหาเรียงความ</div>';
      }
    } catch (e) {}
    if (contentStr && contentStr.trim()) return wrap('body', '📝 เนื้อหาเรียงความ', contentStr);
    return '<div class="no-content">ไม่มีเนื้อหาเรียงความ</div>';
  }

  // จัดทำเอกสาร PDF ของเรียงความที่กรองอยู่ โดยเปิดหน้าพร้อมพิมพ์ให้ผู้ใช้ "บันทึกเป็น PDF" ผ่านเบราว์เซอร์
  // (วิธีนี้รองรับฟอนต์ไทยได้สมบูรณ์และไม่ต้องพึ่งไลบรารีฝั่งเซิร์ฟเวอร์บนโฮสต์ฟรี)
  function exportEssaysPDF() {
    if (!allEssaysCache) { showToast('กรุณาโหลดข้อมูลก่อน', 'error'); return; }
    const filtered = applyEssayFilters(allEssaysCache);
    if (filtered.length === 0) { showToast('ไม่มีเรียงความที่ตรงกับเงื่อนไขให้จัดทำเอกสาร', 'error'); return; }

    // บริบทของตัวกรองปัจจุบัน สำหรับแสดงบนหน้าปก
    const groupLabel = currentEssayGroup === 'all' ? 'ทุกกลุ่ม'
                     : currentEssayGroup === '__none__' ? 'ยังไม่ระบุกลุ่ม'
                     : currentEssayGroup;
    const roomSel  = (document.getElementById('essayClassroomFilter') || {}).value || 'all';
    const roomLbl  = roomSel === 'all' ? 'ทุกห้องเรียน' : ('ห้อง ' + roomSel);
    const phaseSel = (document.getElementById('essayPhaseFilter') || {}).value || 'all';
    const phaseLbl = phaseSel === 'all' ? 'ทุกรอบการประเมิน' : (essayPhaseLabels[phaseSel] || phaseSel);
    const nowStr   = new Date().toLocaleString('th-TH', { dateStyle: 'long', timeStyle: 'short' });

    const cards = filtered.map((e, idx) => {
      const phaseLabel = escapeHtml(essayPhaseLabels[e.essay_phase] || e.essay_phase);
      const room = (e.classroom || '').trim();
      const grp  = (e.student_group || '').trim();
      const dt   = new Date(e.updated_at || e.created_at);
      const dateStr = isNaN(dt) ? '-' : dt.toLocaleString('th-TH', { dateStyle: 'medium', timeStyle: 'short' });
      const wordCount = parseInt(e.word_count || 0).toLocaleString('th-TH');
      const tags = [
        '<span class="tag t-phase">' + phaseLabel + '</span>',
        grp  ? '<span class="tag t-grp">' + escapeHtml(grp) + '</span>' : '',
        room ? '<span class="tag t-room">ห้อง ' + escapeHtml(room) + '</span>' : '',
        '<span class="tag t-word">' + wordCount + ' คำ</span>'
      ].join('');
      return (
        '<article class="essay">' +
          '<div class="essay-head">' +
            '<div class="name">' + (idx + 1) + '. ' + escapeHtml(e.student_name) +
              ' <span class="sid">(' + escapeHtml(e.student_id) + ')</span></div>' +
            (e.essay_title ? '<div class="title">เรื่อง: ' + escapeHtml(e.essay_title) + '</div>' : '') +
            '<div class="tags">' + tags + '</div>' +
          '</div>' +
          buildEssayPrintSections(e.essay_content) +
          '<div class="stamp">บันทึกล่าสุด: ' + dateStr + '</div>' +
        '</article>'
      );
    }).join('');

    const css =
      '@page { size: A4; margin: 18mm 15mm; }' +
      '* { box-sizing: border-box; }' +
      'html,body { margin:0; padding:0; }' +
      'body { font-family:"Sarabun","TH Sarabun New",sans-serif; color:#1f2937; line-height:1.75; font-size:15px; -webkit-print-color-adjust:exact; print-color-adjust:exact; }' +
      '.cover { text-align:center; padding:60px 20px 28px; border-bottom:4px double #0d3b66; margin-bottom:28px; page-break-after:always; }' +
      '.cover .emblem { font-size:52px; }' +
      '.cover h1 { font-size:28px; margin:12px 0 6px; color:#0d3b66; }' +
      '.cover .subtitle { color:#475569; font-size:15px; margin-bottom:18px; }' +
      '.cover .chips span { display:inline-block; margin:5px; padding:6px 16px; border-radius:999px; background:#eef2ff; color:#0d3b66; font-weight:700; font-size:13px; }' +
      '.cover .gen { margin-top:22px; color:#64748b; font-size:13px; }' +
      '.essay { page-break-before:always; page-break-inside:auto; margin-bottom:10px; }' +
      '.essay-head { background:linear-gradient(135deg,#0d3b66,#1d6fb8); color:#fff; padding:14px 18px; border-radius:10px; margin-bottom:16px; }' +
      '.essay-head .name { font-size:19px; font-weight:700; }' +
      '.essay-head .name .sid { font-weight:400; opacity:.85; font-size:15px; }' +
      '.essay-head .title { font-size:14px; opacity:.95; margin-top:3px; font-style:italic; }' +
      '.essay-head .tags { margin-top:10px; }' +
      '.tag { display:inline-block; padding:3px 12px; border-radius:999px; font-size:12px; font-weight:700; margin:3px 6px 0 0; background:rgba(255,255,255,.18); }' +
      '.sec { margin-bottom:14px; padding:12px 16px; border-radius:10px; border-left:6px solid #cbd5e1; background:#f8fafc; page-break-inside:avoid; }' +
      '.sec .lbl { display:block; font-weight:700; font-size:13px; margin-bottom:7px; letter-spacing:.3px; }' +
      '.sec .txt { white-space:pre-wrap; text-align:justify; }' +
      '.sec.intro { border-left-color:#2563eb; background:#eff6ff; } .sec.intro .lbl { color:#2563eb; }' +
      '.sec.body  { border-left-color:#059669; background:#ecfdf5; } .sec.body .lbl { color:#059669; }' +
      '.sec.concl { border-left-color:#dc2626; background:#fef2f2; } .sec.concl .lbl { color:#dc2626; }' +
      '.no-content { color:#94a3b8; font-style:italic; }' +
      '.stamp { text-align:right; color:#94a3b8; font-size:11px; margin-top:8px; }';

    const cover =
      '<div class="cover">' +
        '<div class="emblem">📝</div>' +
        '<h1>รวมเรียงความนักเรียน</h1>' +
        '<div class="subtitle">ระบบประเมินความสามารถในการเขียนเรียงความ</div>' +
        '<div class="chips">' +
          '<span>กลุ่ม: ' + escapeHtml(groupLabel) + '</span>' +
          '<span>' + escapeHtml(roomLbl) + '</span>' +
          '<span>' + escapeHtml(phaseLbl) + '</span>' +
          '<span>รวม ' + filtered.length.toLocaleString('th-TH') + ' ชิ้น</span>' +
        '</div>' +
        '<div class="gen">จัดทำเอกสารเมื่อ ' + escapeHtml(nowStr) + '</div>' +
      '</div>';

    const html =
      '<!DOCTYPE html><html lang="th"><head><meta charset="UTF-8">' +
      '<title>รวมเรียงความนักเรียน</title>' +
      // สคริปต์นี้ต้องอยู่ "ก่อน" <link rel="stylesheet"> เสมอ — ถ้าอยู่หลัง เบราว์เซอร์จะหน่วงการรัน
      // จนกว่า stylesheet ก่อนหน้าจะโหลดเสร็จ ทำให้ timer สำรองไม่เริ่มนับเมื่อฟอนต์โดนบล็อก/ค้าง
      '<script>var __printed=false;function __go(){if(__printed)return;__printed=true;window.focus();window.print();}' +
      // ตัวสำรองแบบมีเพดานเวลา: เริ่มนับทันที พิมพ์ให้เสมอแม้ Google Fonts จะโดนบล็อกหรือโหลดค้าง (ออฟไลน์/เน็ตจำกัด)
      'setTimeout(__go,1200);' +
      // เส้นทางปกติ: ถ้าฟอนต์พร้อมก่อนกำหนด ให้พิมพ์เร็วขึ้นเพื่อให้ตัวอักษรไทยคมชัด
      'if(document.fonts&&document.fonts.ready){document.fonts.ready.then(function(){setTimeout(__go,150);});}<\/script>' +
      '<link rel="preconnect" href="https://fonts.googleapis.com">' +
      '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' +
      '<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&display=swap" rel="stylesheet">' +
      '<style>' + css + '</style></head><body>' + cover + cards +
      '</body></html>';

    const w = window.open('', '_blank');
    if (!w) { showToast('เบราว์เซอร์บล็อกป๊อปอัป กรุณาอนุญาตป๊อปอัปของเว็บนี้แล้วลองใหม่', 'error'); return; }
    w.document.open();
    w.document.write(html);
    w.document.close();
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
    let csv = '﻿' + 'รหัสนักเรียน,ชื่อ-สกุล,ห้องเรียน,กลุ่ม,รอบการประเมิน,ชื่อเรื่อง,จำนวนคำ,ส่วนคำนำ (Introduction),ส่วนเนื้อเรื่อง (Body),ส่วนสรุป (Conclusion),วันที่บันทึก\n';
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
              esc(e.essay_title), e.word_count, esc(intro), esc(bodyText), esc(conc), esc(dt)].join(',') + '\n';
    });

    const blob = new Blob([csv], { type:'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'student_essays.csv';
    link.click();
  }

  // --- เริ่มรันอัตโนมัติ ---
  loadEssayViewer();
</script>

<?php
require_once 'footer.php';
?>
