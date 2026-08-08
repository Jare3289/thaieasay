<?php
$page_title = 'จัดการข้อมูลนักเรียน - ระบบประเมินเรียงความอัจฉริยะ';
require_once 'auth_helper.php';
require_login('teacher'); // ครูเท่านั้น
require_once 'header.php';
?>

<div class="mb-4">
  <a href="index.php" class="btn btn-link text-decoration-none ps-0 fw-bold text-secondary">&larr; กลับหน้าเมนูหลัก</a>
  <h3 class="fw-extrabold text-dark mb-1">👨‍👩‍👧‍👦 จัดการข้อมูลนักเรียน</h3>
  <p class="text-muted small mb-0">นำเข้ารายชื่อจากไฟล์ CSV เพิ่มทีละคน และแบ่งกลุ่มทดลอง/กลุ่มตัวอย่าง</p>
</div>

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
      <div class="card-header bg-success text-white fw-bold rounded-top-4 py-3">➕ เพิ่ม/แก้ไขทีละคน</div>
      <div class="card-body p-4">
        <form id="addStudentForm">
          <div class="mb-2">
            <label class="form-label small fw-bold text-secondary mb-1">รหัสนักเรียน</label>
            <input type="text" id="fSid" required class="form-control" placeholder="เช่น 34317">
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
          <button type="submit" class="btn btn-success fw-bold w-100 rounded-pill">บันทึกนักเรียน</button>
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
              </tr>
            </thead>
            <tbody id="studentTableBody">
              <tr><td colspan="4" class="text-center text-muted py-4">กำลังโหลด...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  let allStudents = [];

  async function loadStudents() {
    try {
      const res = await (await fetch('api.php?action=get_students_full&_t=' + Date.now())).json();
      if (res.success) {
        allStudents = res.students;
        // ตั้งตัวกรองกลุ่มจากค่าที่จำไว้ร่วมกันทุกหน้า (ค่าเริ่มต้น = กลุ่มตัวอย่าง)
        const gf = document.getElementById('groupFilter');
        if (gf && window.TEG) gf.value = TEG.filterValue();
        renderStudentTable();
      }
    } catch (e) { console.error(e); }
  }

  function renderStudentTable() {
    const filter = document.getElementById('groupFilter').value;
    // จำค่ากลุ่มที่เลือกไว้ให้หน้าอื่นใช้ต่อ (เว้น "ยังไม่ระบุกลุ่ม" ซึ่งเป็นมุมมองเฉพาะหน้านี้)
    if (window.TEG) TEG.set((filter === 'กลุ่มทดลอง' || filter === 'กลุ่มตัวอย่าง') ? filter : 'all');
    const tbody = document.getElementById('studentTableBody');
    let list = allStudents;
    if (filter === '__none__') list = allStudents.filter(s => !s.student_group);
    else if (filter) list = allStudents.filter(s => s.student_group === filter);

    document.getElementById('studentCount').textContent = list.length;
    if (list.length === 0) {
      tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">ไม่มีข้อมูล</td></tr>';
      return;
    }
    tbody.innerHTML = list.map(s => {
      const grpBadge = s.student_group
        ? `<span class="badge ${s.student_group === 'กลุ่มทดลอง' ? 'bg-primary' : 'bg-warning text-dark'} rounded-pill">${s.student_group}</span>`
        : '<span class="text-muted small">-</span>';
      return `<tr>
        <td class="px-3 font-monospace">${s.student_id}</td>
        <td class="px-3">${escapeHtml(s.student_name)}</td>
        <td class="px-3 text-center">${s.classroom || '<span class="text-muted">-</span>'}</td>
        <td class="px-3 text-center">${grpBadge}</td>
      </tr>`;
    }).join('');
  }

  function escapeHtml(str) {
    return String(str == null ? '' : str).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
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
      // แปลง bytes ดิบ → base64 เพื่อส่งให้ PHP จัดการ encoding (รองรับไฟล์ Excel ภาษาไทย)
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
        document.getElementById('addStudentForm').reset();
        loadStudents();
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

  document.addEventListener('DOMContentLoaded', loadStudents);
</script>

<?php require_once 'footer.php'; ?>
