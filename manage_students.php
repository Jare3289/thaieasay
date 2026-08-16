<?php
$page_title = 'จัดการนักเรียน & จับคู่ประเมิน - ระบบประเมินเรียงความ';
require_once 'auth_helper.php';
require_login('teacher'); // ครูเท่านั้น
require_once 'header.php';
?>

<div class="mb-3">
  <h3 class="fw-extrabold text-dark mb-1">👨‍👩‍👧‍👦 จัดการนักเรียน &amp; จับคู่ประเมิน</h3>
  <p class="text-muted small mb-0">นำเข้า/แก้ไขรายชื่อนักเรียน แบ่งกลุ่มการวิจัย และกำหนดคู่ประเมินเพื่อนในหน้าเดียว</p>
</div>

<!-- แท็บสลับระหว่างจัดการนักเรียน / จับคู่ประเมิน -->
<ul class="nav nav-pills mb-4 p-1 bg-white rounded-pill shadow-sm d-inline-flex" id="msTabs" role="tablist" style="border:1px solid var(--border-gray);">
  <li class="nav-item" role="presentation">
    <button class="nav-link active rounded-pill fw-bold px-4" id="tab-manage" data-bs-toggle="pill" data-bs-target="#pane-manage" type="button" role="tab">
      <i class="bi bi-person-lines-fill me-1"></i> จัดการข้อมูลนักเรียน
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link rounded-pill fw-bold px-4" id="tab-pair" data-bs-toggle="pill" data-bs-target="#pane-pair" type="button" role="tab">
      <i class="bi bi-people-fill me-1"></i> จับคู่ประเมินเพื่อน
    </button>
  </li>
</ul>

<div class="tab-content">
  <!-- ============================ แท็บ 1: จัดการข้อมูลนักเรียน ============================ -->
  <div class="tab-pane fade show active" id="pane-manage" role="tabpanel" aria-labelledby="tab-manage">
    <div class="row g-4">
      <!-- นำเข้า CSV -->
      <div class="col-lg-7 col-12">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div class="card-header bg-primary text-white fw-bold rounded-top-4 py-3">📄 นำเข้าจากไฟล์ CSV</div>
          <div class="card-body p-4">
            <p class="small text-muted">ไฟล์ CSV ต้องมี 4 คอลัมน์เรียงตามนี้: <b>รหัส, ชื่อ, ห้อง, กลุ่ม</b> (บรรทัดแรกเป็นหัวตารางได้ ระบบจะข้ามให้)</p>
            <div class="mb-3">
              <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill" onclick="downloadTemplate()">
                ⬇️ ดาวน์โหลดไฟล์ตัวอย่าง (Template)
              </button>
            </div>
            <div class="mb-3">
              <input type="file" id="csvFile" accept=".csv" class="form-control">
            </div>
            <button type="button" class="btn btn-primary fw-bold rounded-pill px-4" onclick="uploadCsv()">
              อัปโหลดและนำเข้า
            </button>
            <div id="importResult" class="mt-3"></div>
          </div>
        </div>
      </div>

      <!-- เพิ่มทีละคน -->
      <div class="col-lg-5 col-12">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div class="card-header bg-success text-white fw-bold rounded-top-4 py-3" id="addStudentHeader">➕ เพิ่ม/แก้ไขทีละคน</div>
          <div class="card-body p-4">
            <form id="addStudentForm">
              <div class="mb-2">
                <label class="form-label small fw-bold text-secondary mb-1">รหัสนักเรียน</label>
                <input type="text" id="fSid" required class="form-control" placeholder="เช่น 34317">
                <div class="form-text" id="editHint" style="display:none;">กำลังแก้ไขข้อมูลของนักเรียนคนนี้ — ไม่ควรเปลี่ยนรหัส (ถ้าเปลี่ยนจะกลายเป็นนักเรียนคนใหม่)</div>
              </div>
              <div class="mb-2">
                <label class="form-label small fw-bold text-secondary mb-1">ชื่อ-นามสกุล</label>
                <input type="text" id="fName" required class="form-control" placeholder="เช่น นางสาว กชพร จันทร์พิลา">
              </div>
              <div class="row g-2 mb-3">
                <div class="col-6">
                  <label class="form-label small fw-bold text-secondary mb-1">ห้อง</label>
                  <input type="text" id="fRoom" class="form-control" placeholder="เช่น 6/1">
                </div>
                <div class="col-6">
                  <label class="form-label small fw-bold text-secondary mb-1">กลุ่ม</label>
                  <select id="fGroup" class="form-select">
                    <option value="">- ไม่ระบุ -</option>
                    <option value="กลุ่มทดลอง">กลุ่มทดลอง</option>
                    <option value="กลุ่มตัวอย่าง">กลุ่มตัวอย่าง</option>
                  </select>
                </div>
              </div>
              <div class="d-flex gap-2">
                <button type="submit" id="saveStudentBtn" class="btn btn-success fw-bold flex-grow-1 rounded-pill">บันทึกนักเรียน</button>
                <button type="button" id="cancelEditBtn" class="btn btn-outline-secondary fw-bold rounded-pill px-3" style="display:none;" onclick="cancelEdit()">ยกเลิก</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- รายชื่อปัจจุบัน -->
      <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
          <div class="card-header bg-white fw-bold rounded-top-4 py-3 d-flex justify-content-between align-items-center">
            <span>📋 รายชื่อนักเรียนในระบบ (<span id="studentCount">-</span> คน)</span>
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <button type="button" class="btn btn-outline-primary btn-sm rounded-pill" onclick="setUngroupedExperimental()" title="ตั้งนักเรียนที่ยังไม่ระบุกลุ่มทั้งหมดให้เป็นกลุ่มทดลอง">
                ตั้งคนที่ยังไม่มีกลุ่ม = กลุ่มทดลอง
              </button>
              <select id="groupFilter" class="form-select form-select-sm" style="width:auto" onchange="renderStudentTable()">
                <option value="">ทุกกลุ่ม</option>
                <option value="กลุ่มทดลอง">กลุ่มทดลอง</option>
                <option value="กลุ่มตัวอย่าง">กลุ่มตัวอย่าง</option>
                <option value="__none__">ยังไม่ระบุกลุ่ม</option>
              </select>
            </div>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                  <tr>
                    <th class="px-3 py-2">รหัส</th>
                    <th class="px-3 py-2">ชื่อ-นามสกุล</th>
                    <th class="px-3 py-2 text-center">ห้อง</th>
                    <th class="px-3 py-2 text-center">กลุ่ม</th>
                    <th class="px-3 py-2 text-center">จัดการ</th>
                  </tr>
                </thead>
                <tbody id="studentTableBody">
                  <tr><td colspan="5" class="text-center text-muted py-4">กำลังโหลด...</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ============================ แท็บ 2: จับคู่ประเมินเพื่อน ============================ -->
  <div class="tab-pane fade" id="pane-pair" role="tabpanel" aria-labelledby="tab-pair">
    <div class="mb-3">
      <p class="text-muted small mb-0">กำหนดคู่นักเรียนสำหรับการประเมินเพื่อนแบบไป-กลับ (A↔B) ภายในห้องเดียวกัน ระบบจะล็อกคู่ให้นักเรียนอัตโนมัติในหน้าประเมินเพื่อน</p>
    </div>

    <!-- แถบเลือกรอบ + ตัวกรอง + ปุ่มการทำงาน -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
      <div class="card-body p-4">
        <div class="row g-3 align-items-end">
          <div class="col-lg-3 col-md-6 col-12">
            <label class="form-label small fw-bold text-secondary mb-1">รอบการประเมิน</label>
            <select id="roundSelect" class="form-select">
              <option value="task1">ภาระงาน หน่วยที่ 1 (Task 1)</option>
              <option value="task2">ภาระงาน หน่วยที่ 2 (Task 2)</option>
            </select>
          </div>
          <div class="col-lg-3 col-md-6 col-12">
            <label class="form-label small fw-bold text-secondary mb-1">ห้องเรียน</label>
            <select id="classroomSelect" class="form-select">
              <option value="">ทุกห้อง</option>
            </select>
          </div>
          <div class="col-lg-3 col-md-6 col-12">
            <label class="form-label small fw-bold text-secondary mb-1">กลุ่ม</label>
            <select id="groupSelect" class="form-select">
              <option value="">ทุกกลุ่ม</option>
              <option value="กลุ่มทดลอง">กลุ่มทดลอง</option>
              <option value="กลุ่มตัวอย่าง">กลุ่มตัวอย่าง</option>
            </select>
          </div>
          <div class="col-lg-3 col-12 d-flex flex-wrap gap-2 justify-content-lg-end">
            <button type="button" id="btnSave" class="btn btn-success fw-bold rounded-pill px-4">
              💾 บันทึกการจับคู่
            </button>
          </div>
        </div>
        <div class="d-flex flex-wrap gap-2 mt-3">
          <button type="button" id="btnAutoPair" class="btn btn-warning fw-bold rounded-pill px-3">
            🎲 สุ่มจับคู่ไป-กลับ (ภายในห้อง)
          </button>
          <button type="button" id="btnClear" class="btn btn-outline-secondary fw-bold rounded-pill px-3">
            ล้างคู่ทั้งหมดของรอบนี้
          </button>
        </div>
        <div class="alert alert-info d-flex align-items-center gap-2 py-2 px-3 mt-3 mb-0 rounded-3" role="status">
          <i class="bi bi-info-circle-fill"></i>
          <span class="small">การสุ่มจะจับคู่แบบไป-กลับภายในห้องเดียวกันให้ทุกห้อง (เฉพาะกลุ่มที่เลือก) แล้วปรับแก้เองได้ — แสดงในมุมมองนี้ <b><span id="pairedCount">0</span> / <span id="totalCount">0</span></b> คน</span>
        </div>
      </div>
    </div>

    <!-- ตารางจับคู่ -->
    <div class="card border-0 shadow-sm rounded-4">
      <div class="card-header bg-white fw-bold rounded-top-4 py-3">📋 ตารางจับคู่นักเรียน</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
              <tr>
                <th class="px-3 py-2" style="width:8%;">รหัส</th>
                <th class="px-3 py-2" style="width:30%;">ผู้ประเมิน (นักเรียน)</th>
                <th class="px-3 py-2 text-center" style="width:7%;">ห้อง</th>
                <th class="px-3 py-2 text-center" style="width:5%;"></th>
                <th class="px-3 py-2" style="width:50%;">ผู้ถูกประเมิน (คู่ที่ถูกจับ)</th>
              </tr>
            </thead>
            <tbody id="pairTableBody">
              <tr><td colspan="5" class="text-center text-muted py-4">กำลังโหลด...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  // ยูทิลิตี้ร่วม
  function escapeHtml(str) {
    return String(str == null ? '' : str).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
  }

  // =========================================================================
  // ส่วนที่ 1: จัดการข้อมูลนักเรียน
  // =========================================================================
  let allStudents = [];

  async function loadStudents() {
    try {
      const res = await (await fetch('api.php?action=get_students_full&_t=' + Date.now())).json();
      if (res.success) {
        allStudents = res.students;
        const gf = document.getElementById('groupFilter');
        if (gf && window.TEG) gf.value = TEG.filterValue();
        renderStudentTable();
      }
    } catch (e) { console.error(e); }
  }

  function renderStudentTable() {
    const filter = document.getElementById('groupFilter').value;
    if (window.TEG) TEG.set((filter === 'กลุ่มทดลอง' || filter === 'กลุ่มตัวอย่าง') ? filter : 'all');
    const tbody = document.getElementById('studentTableBody');
    let list = allStudents;
    if (filter === '__none__') list = allStudents.filter(s => !s.student_group);
    else if (filter) list = allStudents.filter(s => s.student_group === filter);

    document.getElementById('studentCount').textContent = list.length;
    if (list.length === 0) {
      tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">ไม่มีข้อมูล</td></tr>';
      return;
    }
    tbody.innerHTML = list.map(s => {
      const grpBadge = s.student_group
        ? `<span class="badge ${s.student_group === 'กลุ่มทดลอง' ? 'bg-primary' : 'bg-warning text-dark'} rounded-pill">${s.student_group}</span>`
        : '<span class="text-muted small">-</span>';
      const sidAttr = encodeURIComponent(s.student_id);
      return `<tr>
        <td class="px-3 font-monospace">${s.student_id}</td>
        <td class="px-3">${escapeHtml(s.student_name)}</td>
        <td class="px-3 text-center">${s.classroom || '<span class="text-muted">-</span>'}</td>
        <td class="px-3 text-center">${grpBadge}</td>
        <td class="px-3 text-center text-nowrap">
          <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-2 me-1" title="แก้ไข" onclick="editStudent('${sidAttr}')"><i class="bi bi-pencil-fill"></i></button>
          <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2" title="ลบ" onclick="deleteStudent('${sidAttr}')"><i class="bi bi-trash-fill"></i></button>
        </td>
      </tr>`;
    }).join('');
  }

  // เติมข้อมูลนักเรียนลงในฟอร์มด้านบนเพื่อแก้ไข
  function editStudent(encId) {
    const id = decodeURIComponent(encId);
    const s = allStudents.find(x => String(x.student_id) === String(id));
    if (!s) { showToast('ไม่พบข้อมูลนักเรียน', 'error'); return; }
    document.getElementById('fSid').value = s.student_id;
    document.getElementById('fName').value = s.student_name || '';
    document.getElementById('fRoom').value = s.classroom || '';
    document.getElementById('fGroup').value = s.student_group || '';
    document.getElementById('addStudentHeader').innerHTML = '✏️ กำลังแก้ไข: ' + escapeHtml(String(s.student_id));
    document.getElementById('saveStudentBtn').textContent = 'บันทึกการแก้ไข';
    document.getElementById('cancelEditBtn').style.display = '';
    document.getElementById('editHint').style.display = '';
    document.getElementById('addStudentForm').scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  // ยกเลิกโหมดแก้ไข กลับเป็นเพิ่มใหม่
  function cancelEdit() {
    document.getElementById('addStudentForm').reset();
    document.getElementById('addStudentHeader').innerHTML = '➕ เพิ่ม/แก้ไขทีละคน';
    document.getElementById('saveStudentBtn').textContent = 'บันทึกนักเรียน';
    document.getElementById('cancelEditBtn').style.display = 'none';
    document.getElementById('editHint').style.display = 'none';
  }

  // ลบนักเรียนออกจากระบบ (พร้อมข้อมูลที่เกี่ยวข้องทั้งหมด)
  async function deleteStudent(encId) {
    const id = decodeURIComponent(encId);
    const s = allStudents.find(x => String(x.student_id) === String(id));
    const label = s ? `${s.student_id} - ${s.student_name}` : id;
    if (!confirm(`ยืนยันการลบนักเรียน "${label}" ?\n\nคำเตือน: ข้อมูลเรียงความ ผลการประเมิน และบันทึกสะท้อนคิดของนักเรียนคนนี้จะถูกลบทั้งหมด และไม่สามารถกู้คืนได้`)) return;
    try {
      const res = await (await fetch('api.php?action=delete_student', {
        method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ student_id: id })
      })).json();
      if (res.success) {
        showToast('ลบนักเรียนเรียบร้อยแล้ว', 'success');
        cancelEdit();
        loadStudents();
        pairLoadStudents();
      } else { showToast(res.error || 'ลบไม่สำเร็จ', 'error'); }
    } catch (err) { showToast('เกิดข้อผิดพลาด: ' + err.message, 'error'); }
  }

  function downloadTemplate() {
    const content = '﻿รหัส,ชื่อ,ห้อง,กลุ่ม\n34317,นางสาว กชพร จันทร์พิลา,6/1,กลุ่มทดลอง\n34318,นางสาว กรวรรณ เรืองกาญจนชัย,6/1,กลุ่มตัวอย่าง\n';
    const blob = new Blob([content], { type: 'text/csv;charset=utf-8;' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'template_students.csv';
    a.click();
  }

  function uploadCsv() {
    const fileInput = document.getElementById('csvFile');
    const resultBox = document.getElementById('importResult');
    if (!fileInput.files.length) { showToast('กรุณาเลือกไฟล์ CSV ก่อน', 'error'); return; }

    const reader = new FileReader();
    reader.onload = async (e) => {
      const bytes = new Uint8Array(e.target.result);
      let binary = '';
      for (let i = 0; i < bytes.length; i++) binary += String.fromCharCode(bytes[i]);
      const b64 = btoa(binary);
      resultBox.innerHTML = '<div class="text-muted small">กำลังนำเข้า...</div>';
      try {
        const res = await (await fetch('api.php?action=import_students_csv', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ csv_base64: b64 })
        })).json();
        if (res.success) {
          resultBox.innerHTML = `<div class="alert alert-success py-2 mb-0 small">นำเข้าสำเร็จ <b>${res.imported}</b> คน${res.skipped ? ` (ข้าม ${res.skipped} แถว เช่น หัวตาราง/ข้อมูลไม่ครบ)` : ''}</div>`;
          showToast('นำเข้ารายชื่อสำเร็จ', 'success');
          fileInput.value = '';
          loadStudents();
        } else {
          resultBox.innerHTML = `<div class="alert alert-danger py-2 mb-0 small">${res.error || 'นำเข้าไม่สำเร็จ'}</div>`;
        }
      } catch (err) {
        resultBox.innerHTML = `<div class="alert alert-danger py-2 mb-0 small">เกิดข้อผิดพลาด: ${err.message}</div>`;
      }
    };
    reader.readAsArrayBuffer(fileInput.files[0]);
  }

  document.getElementById('addStudentForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const payload = {
      student_id: document.getElementById('fSid').value.trim(),
      student_name: document.getElementById('fName').value.trim(),
      classroom: document.getElementById('fRoom').value.trim(),
      student_group: document.getElementById('fGroup').value
    };
    try {
      const res = await (await fetch('api.php?action=save_student', {
        method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload)
      })).json();
      if (res.success) {
        showToast('บันทึกนักเรียนแล้ว', 'success');
        cancelEdit(); // รีเซ็ตฟอร์มและออกจากโหมดแก้ไข
        loadStudents();
        pairLoadStudents(); // อัปเดตรายชื่อในแท็บจับคู่ด้วย
      } else { showToast(res.error || 'บันทึกไม่สำเร็จ', 'error'); }
    } catch (err) { showToast('เกิดข้อผิดพลาด: ' + err.message, 'error'); }
  });

  async function setUngroupedExperimental() {
    if (!confirm('ตั้งนักเรียนทุกคนที่ยังไม่ระบุกลุ่ม ให้เป็น "กลุ่มทดลอง" ใช่หรือไม่?')) return;
    try {
      const res = await (await fetch('api.php?action=set_ungrouped_experimental', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: '{}' })).json();
      if (res.success) {
        showToast(`ตั้งกลุ่มทดลองให้ ${res.updated} คนแล้ว`, 'success');
        loadStudents();
      } else { showToast(res.error || 'ทำรายการไม่สำเร็จ', 'error'); }
    } catch (err) { showToast('เกิดข้อผิดพลาด: ' + err.message, 'error'); }
  }

  // =========================================================================
  // ส่วนที่ 2: จับคู่ประเมินเพื่อน
  // =========================================================================
  let students = {};        // { id: name }
  let studentRoom = {};     // { id: classroom }
  let studentGroup = {};    // { id: student_group }
  let sortedIds = [];
  let currentPairs = {};    // { student_code: partner_code }

  async function pairLoadStudents() {
    const res = await (await fetch('api.php?action=get_students_full&_t=' + Date.now())).json();
    if (!res.success) { showToast(res.error || 'โหลดรายชื่อไม่สำเร็จ', 'error'); return; }
    students = {}; studentRoom = {}; studentGroup = {};
    res.students.forEach(s => {
      students[s.student_id] = s.student_name;
      studentRoom[s.student_id] = s.classroom || '';
      studentGroup[s.student_id] = s.student_group || '';
    });
    sortedIds = Object.keys(students).sort();

    const rooms = [...new Set(Object.values(studentRoom).filter(r => r))].sort();
    const roomSel = document.getElementById('classroomSelect');
    roomSel.innerHTML = '<option value="">ทุกห้อง</option>' +
      rooms.map(r => `<option value="${escapeHtml(r)}">${escapeHtml(r)}</option>`).join('');
  }

  async function loadPairs() {
    const round = document.getElementById('roundSelect').value;
    const res = await (await fetch(`api.php?action=get_peer_pairs&round=${round}&_t=${Date.now()}`)).json();
    currentPairs = (res.success && res.pairs) ? res.pairs : {};
    renderPairTable();
  }

  function visibleIds() {
    const room = document.getElementById('classroomSelect').value;
    const grp = document.getElementById('groupSelect').value;
    return sortedIds.filter(id => {
      if (room && studentRoom[id] !== room) return false;
      if (grp && studentGroup[id] !== grp) return false;
      return true;
    });
  }

  function partnerOptions(selfId) {
    const selected = currentPairs[selfId] || '';
    const myRoom = studentRoom[selfId];
    const grp = document.getElementById('groupSelect').value;
    let html = '<option value="">— ยังไม่กำหนด —</option>';
    sortedIds.forEach(id => {
      if (id === selfId) return;
      if (!myRoom || studentRoom[id] !== myRoom) return;
      if (grp && studentGroup[id] !== grp) return;
      const sel = (id === selected) ? ' selected' : '';
      html += `<option value="${id}"${sel}>${id} - ${escapeHtml(students[id])}</option>`;
    });
    return html;
  }

  function renderPairTable() {
    const tbody = document.getElementById('pairTableBody');
    const ids = visibleIds();
    if (ids.length === 0) {
      tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">ไม่มีนักเรียนตามตัวกรองที่เลือก</td></tr>';
      updateCounts();
      return;
    }
    tbody.innerHTML = ids.map(id => {
      const noRoom = !studentRoom[id];
      return `<tr>
        <td class="px-3 font-monospace">${id}</td>
        <td class="px-3">${escapeHtml(students[id])}</td>
        <td class="px-3 text-center small">${studentRoom[id] ? escapeHtml(studentRoom[id]) : '<span class="text-danger">—</span>'}</td>
        <td class="px-3 text-center text-muted">&rarr;</td>
        <td class="px-3">
          <select class="form-select form-select-sm partner-select" data-self="${id}" ${noRoom ? 'disabled title="ยังไม่ได้กำหนดห้องเรียนให้นักเรียนคนนี้"' : ''}>
            ${partnerOptions(id)}
          </select>
        </td>
      </tr>`;
    }).join('');

    tbody.querySelectorAll('.partner-select').forEach(sel => {
      sel.addEventListener('change', () => {
        const self = sel.dataset.self;
        if (sel.value) currentPairs[self] = sel.value;
        else delete currentPairs[self];
        updateCounts();
      });
    });
    updateCounts();
  }

  function updateCounts() {
    const ids = visibleIds();
    const paired = ids.filter(id => currentPairs[id]).length;
    document.getElementById('pairedCount').textContent = paired;
    document.getElementById('totalCount').textContent = ids.length;
  }

  document.getElementById('roundSelect').addEventListener('change', loadPairs);
  document.getElementById('classroomSelect').addEventListener('change', renderPairTable);
  document.getElementById('groupSelect').addEventListener('change', function () {
    if (window.TEG) TEG.set(this.value === '' ? 'all' : this.value);
    renderPairTable();
  });

  document.getElementById('btnClear').addEventListener('click', () => {
    if (!confirm('ล้างการจับคู่ทั้งหมดของรอบนี้ในตาราง (ยังไม่บันทึกจนกว่าจะกดปุ่มบันทึก) ใช่หรือไม่?')) return;
    currentPairs = {};
    renderPairTable();
  });

  document.getElementById('btnAutoPair').addEventListener('click', async () => {
    const round = document.getElementById('roundSelect').value;
    const group = document.getElementById('groupSelect').value;
    if (!confirm('สุ่มจับคู่ไป-กลับภายในห้องเดียวกันสำหรับทุกห้อง (เฉพาะกลุ่มที่เลือก) และบันทึกทับข้อมูลเดิมของรอบนี้ ใช่หรือไม่?')) return;
    try {
      const res = await (await fetch('api.php?action=auto_pair_students', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ round, group })
      })).json();
      if (res.success) {
        currentPairs = res.pairs || {};
        renderPairTable();
        showToast(`สุ่มจับคู่ไป-กลับและบันทึกแล้ว ${res.count} คน`, 'success');
      } else {
        showToast(res.error || 'สุ่มจับคู่ไม่สำเร็จ', 'error');
      }
    } catch (err) { showToast('เกิดข้อผิดพลาด: ' + err.message, 'error'); }
  });

  document.getElementById('btnSave').addEventListener('click', async () => {
    const round = document.getElementById('roundSelect').value;
    const pairs = Object.keys(currentPairs)
      .filter(k => currentPairs[k])
      .map(k => ({ student_code: k, partner_code: currentPairs[k] }));
    try {
      const res = await (await fetch('api.php?action=save_peer_pairs', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ round, pairs })
      })).json();
      if (res.success) {
        showToast(`บันทึกการจับคู่แล้ว ${res.saved} คู่`, 'success');
      } else {
        showToast(res.error || 'บันทึกไม่สำเร็จ', 'error');
      }
    } catch (err) { showToast('เกิดข้อผิดพลาด: ' + err.message, 'error'); }
  });

  // ปุ่มเลือกกลุ่มบนแถบบนเปลี่ยน → อัปเดตทั้งสองแท็บให้ตรงกัน
  window.onTEGChange = function() {
    const gf = document.getElementById('groupFilter');
    if (gf && window.TEG) gf.value = TEG.filterValue();
    renderStudentTable();

    const gs = document.getElementById('groupSelect');
    if (gs && window.TEG) gs.value = TEG.filterValue();
    renderPairTable();
  };

  // เริ่มทำงาน — โหลดทั้งสองแท็บ
  (async function init() {
    const gs = document.getElementById('groupSelect');
    if (gs && window.TEG) gs.value = TEG.filterValue();
    await loadStudents();
    await pairLoadStudents();
    await loadPairs();
  })();
</script>

<?php require_once 'footer.php'; ?>
