<?php
$page_title = 'จับคู่ประเมินเพื่อน - ระบบประเมินเรียงความอัจฉริยะ';
require_once 'auth_helper.php';
require_login('teacher'); // ครูเท่านั้น
require_once 'header.php';
?>

<div class="mb-4">
  <a href="index.php" class="btn btn-link text-decoration-none ps-0 fw-bold text-secondary">&larr; กลับหน้าเมนูหลัก</a>
  <h3 class="fw-extrabold text-dark mb-1">🤝 จับคู่ประเมินเพื่อน (Peer Pairing)</h3>
  <p class="text-muted small mb-0">กำหนดคู่นักเรียนสำหรับการประเมินเพื่อนในแต่ละรอบ ระบบจะล็อกคู่ให้นักเรียนอัตโนมัติในหน้าประเมินเพื่อน</p>
</div>

<!-- แถบเลือกรอบ + ปุ่มการทำงาน -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
  <div class="card-body p-4">
    <div class="row g-3 align-items-end">
      <div class="col-lg-4 col-md-6 col-12">
        <label class="form-label small fw-bold text-secondary mb-1">เลือกรอบการประเมิน</label>
        <select id="roundSelect" class="form-select">
          <option value="pretest">ก่อนเรียน (Pretest - T1)</option>
          <option value="task1">ภาระงาน หน่วยที่ 1 (Task 1)</option>
          <option value="task2">ภาระงาน หน่วยที่ 2 (Task 2)</option>
          <option value="posttest">หลังเรียน (Posttest - T2)</option>
        </select>
      </div>
      <div class="col-lg-3 col-md-6 col-12">
        <label class="form-label small fw-bold text-secondary mb-1">จับคู่เฉพาะกลุ่ม (สำหรับปุ่มสุ่ม)</label>
        <select id="groupSelect" class="form-select">
          <option value="">ทุกคน</option>
          <option value="กลุ่มทดลอง">กลุ่มทดลอง</option>
          <option value="กลุ่มตัวอย่าง">กลุ่มตัวอย่าง</option>
        </select>
      </div>
      <div class="col-lg-5 col-12 d-flex flex-wrap gap-2 justify-content-lg-end">
        <button type="button" id="btnAutoPair" class="btn btn-warning fw-bold rounded-pill px-3">
          🎲 สุ่มจับคู่อัตโนมัติ
        </button>
        <button type="button" id="btnClear" class="btn btn-outline-secondary fw-bold rounded-pill px-3">
          ล้างคู่ทั้งหมด
        </button>
        <button type="button" id="btnSave" class="btn btn-success fw-bold rounded-pill px-4">
          💾 บันทึกการจับคู่
        </button>
      </div>
    </div>
    <div class="alert alert-info d-flex align-items-center gap-2 py-2 px-3 mt-3 mb-0 rounded-3" role="status">
      <i class="bi bi-info-circle-fill"></i>
      <span class="small">กำหนดคู่ได้ทีละคน โดยเลือก "ผู้ถูกประเมิน" ในแต่ละแถว หรือกดปุ่มสุ่มเพื่อจับคู่ให้อัตโนมัติแล้วปรับแก้เองภายหลัง — จับคู่แล้ว <b><span id="pairedCount">0</span> / <span id="totalCount">0</span></b> คน</span>
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
            <th class="px-3 py-2" style="width:34%;">ผู้ประเมิน (นักเรียน)</th>
            <th class="px-3 py-2 text-center" style="width:5%;"></th>
            <th class="px-3 py-2" style="width:53%;">ผู้ถูกประเมิน (คู่ที่ถูกจับ)</th>
          </tr>
        </thead>
        <tbody id="pairTableBody">
          <tr><td colspan="4" class="text-center text-muted py-4">กำลังโหลด...</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
  let students = {};       // { student_id: student_name }
  let studentFull = [];    // [{student_id, student_name, student_group}]
  let sortedIds = [];
  let currentPairs = {};   // { student_code: partner_code }

  async function loadStudents() {
    const res = await (await fetch('api.php?action=get_students_full&_t=' + Date.now())).json();
    if (res.success) {
      studentFull = res.students;
      students = {};
      studentFull.forEach(s => { students[s.student_id] = s.student_name; });
      sortedIds = Object.keys(students).sort();
    } else {
      showToast(res.error || 'โหลดรายชื่อไม่สำเร็จ', 'error');
    }
  }

  async function loadPairs() {
    const round = document.getElementById('roundSelect').value;
    const res = await (await fetch(`api.php?action=get_peer_pairs&round=${round}&_t=${Date.now()}`)).json();
    currentPairs = (res.success && res.pairs) ? res.pairs : {};
    renderTable();
  }

  function escapeHtml(str) {
    return String(str == null ? '' : str).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
  }

  function partnerOptions(selfId) {
    const selected = currentPairs[selfId] || '';
    let html = '<option value="">— ยังไม่กำหนด —</option>';
    sortedIds.forEach(id => {
      if (id === selfId) return; // ห้ามจับคู่กับตนเอง
      const sel = (id === selected) ? ' selected' : '';
      html += `<option value="${id}"${sel}>${id} - ${escapeHtml(students[id])}</option>`;
    });
    return html;
  }

  function renderTable() {
    const tbody = document.getElementById('pairTableBody');
    if (sortedIds.length === 0) {
      tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">ยังไม่มีข้อมูลนักเรียน</td></tr>';
      updateCounts();
      return;
    }
    tbody.innerHTML = sortedIds.map(id => {
      return `<tr>
        <td class="px-3 font-monospace">${id}</td>
        <td class="px-3">${escapeHtml(students[id])}</td>
        <td class="px-3 text-center text-muted">&rarr;</td>
        <td class="px-3">
          <select class="form-select form-select-sm partner-select" data-self="${id}">
            ${partnerOptions(id)}
          </select>
        </td>
      </tr>`;
    }).join('');

    // ผูก event ให้ dropdown ทุกตัว
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
    const paired = Object.keys(currentPairs).filter(k => currentPairs[k]).length;
    document.getElementById('pairedCount').textContent = paired;
    document.getElementById('totalCount').textContent = sortedIds.length;
  }

  document.getElementById('roundSelect').addEventListener('change', loadPairs);

  document.getElementById('btnClear').addEventListener('click', () => {
    if (!confirm('ล้างการจับคู่ทั้งหมดในตาราง (ยังไม่บันทึกจนกว่าจะกดปุ่มบันทึก) ใช่หรือไม่?')) return;
    currentPairs = {};
    renderTable();
  });

  document.getElementById('btnAutoPair').addEventListener('click', async () => {
    const round = document.getElementById('roundSelect').value;
    const group = document.getElementById('groupSelect').value;
    if (!confirm('สุ่มจับคู่อัตโนมัติสำหรับรอบนี้ และบันทึกทับข้อมูลเดิม ใช่หรือไม่?')) return;
    try {
      const res = await (await fetch('api.php?action=auto_pair_students', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ round, group })
      })).json();
      if (res.success) {
        currentPairs = res.pairs || {};
        renderTable();
        showToast(`สุ่มจับคู่และบันทึกแล้ว ${res.count} คน`, 'success');
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

  (async function init() {
    await loadStudents();
    await loadPairs();
  })();
</script>

<?php require_once 'footer.php'; ?>
