<?php
$page_title = 'จับคู่ประเมินเพื่อน - ระบบประเมินเรียงความอัจฉริยะ';
require_once 'auth_helper.php';
require_login('teacher'); // ครูเท่านั้น
require_once 'header.php';
?>

<div class="mb-4">
  <a href="index.php" class="btn btn-link text-decoration-none ps-0 fw-bold text-secondary">&larr; กลับหน้าเมนูหลัก</a>
  <h3 class="fw-extrabold text-dark mb-1">🤝 จับคู่ประเมินเพื่อน (Peer Pairing)</h3>
  <p class="text-muted small mb-0">กำหนดคู่ประเมินล่วงหน้าต่อรอบการประเมิน เพื่อให้นักเรียนไม่ต้องค้นหาชื่อเพื่อนจากรายชื่อทั้งหมดเอง</p>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
  <div class="card-body p-4">
    <label class="form-label small fw-bold text-secondary text-uppercase mb-1">เลือกรอบการประเมิน</label>
    <select id="roundSelect" class="form-select form-select-lg border-2 rounded-3" style="max-width: 420px;" onchange="loadPairs()">
      <option value="pretest">ก่อนเรียน (Pretest - T1)</option>
      <option value="task1" selected>ภารงาน หน่วยที่ 1 (Task 1)</option>
      <option value="task2">ภารงาน หน่วยที่ 2 (Task 2)</option>
      <option value="posttest">หลังเรียน (Posttest - T2)</option>
    </select>
  </div>
</div>

<div class="row g-4">
  <!-- จับคู่ทีละคู่ / สุ่มจับคู่ -->
  <div class="col-lg-5 col-12">
    <div class="card border-0 shadow-sm rounded-4 h-100">
      <div class="card-header bg-primary text-white fw-bold rounded-top-4 py-3">➕ จับคู่นักเรียน</div>
      <div class="card-body p-4">
        <form id="pairForm">
          <div class="mb-2">
            <label class="form-label small fw-bold text-secondary mb-1">นักเรียนคนที่ 1</label>
            <select id="fStudentA" required class="form-select">
              <option value="" disabled selected>-- เลือกนักเรียน --</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold text-secondary mb-1">นักเรียนคนที่ 2</label>
            <select id="fStudentB" required class="form-select">
              <option value="" disabled selected>-- เลือกนักเรียน --</option>
            </select>
          </div>
          <button type="submit" class="btn btn-primary fw-bold w-100 rounded-pill">
            <i class="bi bi-link-45deg"></i> จับคู่
          </button>
          <p class="text-muted small mt-2 mb-0">หากนักเรียนคนใดมีคู่อยู่แล้วในรอบนี้ คู่เดิมจะถูกยกเลิกและแทนที่ด้วยคู่ใหม่</p>
        </form>

        <hr class="my-4">

        <button type="button" class="btn btn-outline-success fw-bold w-100 rounded-pill" onclick="randomPairRemaining()">
          <i class="bi bi-shuffle"></i> สุ่มจับคู่คนที่เหลือทั้งหมด
        </button>
        <p class="text-muted small mt-2 mb-0">สุ่มจับคู่เฉพาะนักเรียนที่ <b>ยังไม่มีคู่</b> ในรอบนี้ ไม่กระทบคู่ที่จับไว้แล้ว ครูสามารถปรับแก้เป็นรายคู่ได้ภายหลัง</p>
      </div>
    </div>
  </div>

  <!-- รายการคู่ + คนที่ยังไม่มีคู่ -->
  <div class="col-lg-7 col-12">
    <div class="card border-0 shadow-sm rounded-4 mb-4">
      <div class="card-header bg-white fw-bold rounded-top-4 py-3 d-flex justify-content-between align-items-center">
        <span>📋 คู่ที่จับไว้แล้ว (<span id="pairCount">-</span> คู่)</span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
              <tr>
                <th class="px-3 py-2">นักเรียนคนที่ 1</th>
                <th class="px-3 py-2">นักเรียนคนที่ 2</th>
                <th class="px-3 py-2 text-end">การจัดการ</th>
              </tr>
            </thead>
            <tbody id="pairsTableBody">
              <tr><td colspan="3" class="text-center text-muted py-4">กำลังโหลด...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
      <div class="card-header bg-white fw-bold rounded-top-4 py-3">
        <i class="bi bi-exclamation-triangle text-warning"></i> นักเรียนที่ยังไม่มีคู่ (<span id="unpairedCount">-</span> คน)
      </div>
      <div class="card-body p-3" id="unpairedList">
        <span class="text-muted small">กำลังโหลด...</span>
      </div>
    </div>
  </div>
</div>

<script>
  let allStudentsMap = {};

  async function loadStudentsForPairing() {
    try {
      const res = await (await fetch('api.php?action=get_students_list&_t=' + Date.now())).json();
      if (res.success) {
        allStudentsMap = res.students;
        populateStudentSelects();
      }
    } catch (e) { console.error(e); }
  }

  function populateStudentSelects() {
    const a = document.getElementById('fStudentA');
    const b = document.getElementById('fStudentB');
    a.innerHTML = '<option value="" disabled selected>-- เลือกนักเรียน --</option>';
    b.innerHTML = '<option value="" disabled selected>-- เลือกนักเรียน --</option>';
    Object.keys(allStudentsMap).sort().forEach(id => {
      const label = `${id} - ${allStudentsMap[id]}`;
      a.appendChild(new Option(label, id));
      b.appendChild(new Option(label, id));
    });
  }

  function escapeHtml(str) {
    return String(str == null ? '' : str).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
  }

  async function loadPairs() {
    const round = document.getElementById('roundSelect').value;
    const tbody = document.getElementById('pairsTableBody');
    const unpairedBox = document.getElementById('unpairedList');
    tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-4">กำลังโหลด...</td></tr>';
    unpairedBox.innerHTML = '<span class="text-muted small">กำลังโหลด...</span>';

    try {
      const res = await (await fetch(`api.php?action=get_peer_pairs&round=${encodeURIComponent(round)}&_t=${Date.now()}`)).json();
      if (!res.success) {
        tbody.innerHTML = `<tr><td colspan="3" class="text-center text-danger py-4">${escapeHtml(res.error || 'โหลดข้อมูลไม่สำเร็จ')}</td></tr>`;
        return;
      }

      document.getElementById('pairCount').textContent = res.pairs.length;
      if (res.pairs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-4">ยังไม่มีคู่ที่จับไว้ในรอบนี้</td></tr>';
      } else {
        tbody.innerHTML = res.pairs.map(p => `
          <tr>
            <td class="px-3">${escapeHtml(p.student_code)} - ${escapeHtml(p.student_name)}</td>
            <td class="px-3">${escapeHtml(p.partner_code)} - ${escapeHtml(p.partner_name)}</td>
            <td class="px-3 text-end">
              <button type="button" class="btn btn-outline-danger btn-sm rounded-pill" onclick="unpair('${p.student_code}')">
                <i class="bi bi-x-lg"></i> ยกเลิกคู่
              </button>
            </td>
          </tr>
        `).join('');
      }

      document.getElementById('unpairedCount').textContent = res.unpaired.length;
      if (res.unpaired.length === 0) {
        unpairedBox.innerHTML = '<span class="text-success small fw-bold"><i class="bi bi-check-circle-fill"></i> จับคู่ครบทุกคนแล้วในรอบนี้</span>';
      } else {
        unpairedBox.innerHTML = res.unpaired.map(s =>
          `<span class="badge bg-warning text-dark me-1 mb-1">${escapeHtml(s.student_id)} - ${escapeHtml(s.student_name)}</span>`
        ).join('');
      }
    } catch (e) {
      tbody.innerHTML = '<tr><td colspan="3" class="text-center text-danger py-4">เกิดข้อผิดพลาดในการเชื่อมต่อ</td></tr>';
    }
  }

  document.getElementById('pairForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const round = document.getElementById('roundSelect').value;
    const studentA = document.getElementById('fStudentA').value;
    const studentB = document.getElementById('fStudentB').value;
    if (!studentA || !studentB) { showToast('กรุณาเลือกนักเรียนทั้งสองคน', 'error'); return; }
    if (studentA === studentB) { showToast('ไม่สามารถจับคู่นักเรียนคนเดียวกันได้', 'error'); return; }

    try {
      const res = await (await fetch('api.php?action=save_peer_pair', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ round, studentA, studentB })
      })).json();
      if (res.success) {
        showToast('จับคู่สำเร็จ', 'success');
        document.getElementById('pairForm').reset();
        loadPairs();
      } else {
        showToast(res.error || 'จับคู่ไม่สำเร็จ', 'error');
      }
    } catch (err) {
      showToast('เกิดข้อผิดพลาด: ' + err.message, 'error');
    }
  });

  async function unpair(studentCode) {
    if (!confirm('ยืนยันการยกเลิกคู่ของนักเรียนคนนี้ในรอบนี้?')) return;
    const round = document.getElementById('roundSelect').value;
    try {
      const res = await (await fetch('api.php?action=delete_peer_pair', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ round, studentCode })
      })).json();
      if (res.success) {
        showToast('ยกเลิกคู่แล้ว', 'success');
        loadPairs();
      } else {
        showToast(res.error || 'ยกเลิกไม่สำเร็จ', 'error');
      }
    } catch (err) {
      showToast('เกิดข้อผิดพลาด: ' + err.message, 'error');
    }
  }

  async function randomPairRemaining() {
    const round = document.getElementById('roundSelect').value;
    if (!confirm('สุ่มจับคู่นักเรียนที่ยังไม่มีคู่ทั้งหมดในรอบนี้ใช่หรือไม่? (จะไม่กระทบคู่ที่จับไว้แล้ว)')) return;
    try {
      const res = await (await fetch('api.php?action=random_peer_pairs', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ round })
      })).json();
      if (res.success) {
        let msg = `สุ่มจับคู่สำเร็จ ${res.paired} คู่`;
        if (res.leftover) msg += ` (เหลือ ${res.leftover} ที่จับคู่ไม่ลงตัว กรุณาจับคู่ให้ด้วยตนเอง)`;
        showToast(msg, 'success');
        loadPairs();
      } else {
        showToast(res.error || 'สุ่มจับคู่ไม่สำเร็จ', 'error');
      }
    } catch (err) {
      showToast('เกิดข้อผิดพลาด: ' + err.message, 'error');
    }
  }

  document.addEventListener('DOMContentLoaded', async () => {
    await loadStudentsForPairing();
    await loadPairs();
  });
</script>

<?php require_once 'footer.php'; ?>
