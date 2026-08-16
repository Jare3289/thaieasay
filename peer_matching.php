<?php
$page_title = 'จับคู่ประเมินเพื่อน (นักเรียนจับคู่กันเอง) - ระบบประเมินเรียงความ';
require_once 'auth_helper.php';
require_login('student'); // นักเรียนเท่านั้น
require_once 'header.php';
$me = $_SESSION['user'];
?>

<div class="mb-4">
  <a href="index.php" class="btn btn-link text-decoration-none ps-0 fw-bold text-secondary">&larr; กลับหน้าเมนูหลัก</a>
  <h3 class="fw-extrabold text-dark mb-1">🤝 จับคู่ประเมินเพื่อนกันเอง (Peer Matching)</h3>
  <p class="text-muted small mb-0">
    เลือกเพื่อนในห้องเดียวกันแล้วส่งคำขอจับคู่ เมื่อเพื่อนกด "รับคำขอ" ระบบจะจับคู่แบบไป-กลับให้อัตโนมัติ
    (คุณตรวจให้เพื่อน และเพื่อนตรวจให้คุณ) — เมื่อขึ้นหน่วยใหม่ต้องส่งคำขอจับคู่กันใหม่
  </p>
</div>

<!-- เลือกรอบ/หน่วย -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
  <div class="card-body p-4">
    <label class="form-label small fw-bold text-secondary mb-2">เลือกหน่วยการเรียน (รอบการประเมิน)</label>
    <div id="roundButtons" class="d-flex flex-wrap gap-2">
      <button type="button" class="btn btn-outline-primary fw-bold rounded-3 round-btn active" data-round="task1">📚 ภาระงาน หน่วยที่ 1</button>
      <button type="button" class="btn btn-outline-primary fw-bold rounded-3 round-btn" data-round="task2">📗 ภาระงาน หน่วยที่ 2</button>
    </div>
  </div>
</div>

<!-- สถานะการจับคู่ปัจจุบัน -->
<div id="statusCard" class="card border-0 shadow-sm rounded-4 mb-4 d-none">
  <div class="card-body p-4" id="statusBody"><!-- สร้างด้วย JS --></div>
</div>

<!-- คำขอที่เข้ามา (รอเราตอบรับ) -->
<div id="incomingCard" class="card border-0 shadow-sm rounded-4 mb-4 d-none">
  <div class="card-header bg-white fw-bold rounded-top-4 py-3">
    📥 คำขอจับคู่ที่ส่งมาถึงคุณ <span class="badge bg-danger rounded-pill ms-1" id="incomingBadge">0</span>
  </div>
  <div class="card-body p-3" id="incomingBody"><!-- สร้างด้วย JS --></div>
</div>

<!-- รายชื่อเพื่อนในห้อง -->
<div class="card border-0 shadow-sm rounded-4">
  <div class="card-header bg-white fw-bold rounded-top-4 py-3 d-flex justify-content-between align-items-center">
    <span>👥 เพื่อนร่วมห้องของคุณ</span>
    <button type="button" id="btnRefresh" class="btn btn-sm btn-outline-secondary rounded-pill">
      <i class="bi bi-arrow-clockwise"></i> รีเฟรช
    </button>
  </div>
  <div class="card-body p-0">
    <div id="classmatesBody">
      <div class="text-center text-muted py-5">กำลังโหลด...</div>
    </div>
  </div>
</div>

<script>
  const MY_ID = <?php echo json_encode($me['id']); ?>;
  let currentRound = 'task1';
  let state = null; // ผลจาก get_peer_match_status

  function escapeHtml(str) {
    return String(str == null ? '' : str).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
  }

  async function loadStatus() {
    const body = document.getElementById('classmatesBody');
    try {
      const res = await (await fetch(`api.php?action=get_peer_match_status&round=${currentRound}&_t=${Date.now()}`)).json();
      if (!res.success) { showToast(res.error || 'โหลดสถานะไม่สำเร็จ', 'error'); return; }
      state = res;
      render();
    } catch (err) {
      showToast('เกิดข้อผิดพลาด: ' + err.message, 'error');
    }
  }

  function render() {
    renderStatus();
    renderIncoming();
    renderClassmates();
  }

  // สถานะคู่ปัจจุบัน
  function renderStatus() {
    const card = document.getElementById('statusCard');
    const el = document.getElementById('statusBody');
    if (state.partner) {
      const name = partnerName(state.partner);
      card.classList.remove('d-none');
      el.innerHTML = `
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
          <div>
            <div class="text-success fw-bold mb-1"><i class="bi bi-check-circle-fill"></i> จับคู่สำเร็จแล้วในหน่วยนี้</div>
            <div class="fs-5 fw-bold text-dark">คู่ของคุณคือ ${escapeHtml(state.partner)} - ${escapeHtml(name)}</div>
            <div class="small text-muted mt-1">คุณจะตรวจงานให้ ${escapeHtml(name)} และ ${escapeHtml(name)} จะตรวจงานให้คุณเช่นกัน (ไป-กลับ)</div>
          </div>
          <div class="d-flex flex-column gap-2">
            <a href="evaluation.php?mode=peer" class="btn btn-success fw-bold rounded-pill px-4">ไปหน้าประเมินเพื่อน &rarr;</a>
            <button type="button" id="btnUnpair" class="btn btn-sm btn-outline-danger rounded-pill">ยกเลิกคู่ (ขอใหม่)</button>
          </div>
        </div>`;
      document.getElementById('btnUnpair').addEventListener('click', unpair);
    } else {
      card.classList.add('d-none');
    }
  }

  // คำขอที่เข้ามา
  function renderIncoming() {
    const card = document.getElementById('incomingCard');
    const el = document.getElementById('incomingBody');
    const list = (state.partner ? [] : (state.incoming || []));
    document.getElementById('incomingBadge').textContent = list.length;
    if (list.length === 0) { card.classList.add('d-none'); return; }
    card.classList.remove('d-none');
    el.innerHTML = list.map(r => `
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 border rounded-3 p-3 mb-2">
        <div>
          <span class="badge bg-warning text-dark rounded-pill me-2">รอคุณตอบรับ</span>
          <span class="fw-bold text-dark">${escapeHtml(r.code)} - ${escapeHtml(r.name)}</span>
          <span class="text-muted small ms-1">อยากจับคู่ประเมินกับคุณ</span>
        </div>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-success btn-sm fw-bold rounded-pill px-3 btn-accept" data-code="${escapeHtml(r.code)}">
            <i class="bi bi-check-lg"></i> รับคำขอ (จับคู่)
          </button>
          <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3 btn-decline" data-code="${escapeHtml(r.code)}">
            ปฏิเสธ
          </button>
        </div>
      </div>`).join('');
    el.querySelectorAll('.btn-accept').forEach(b => b.addEventListener('click', () => respond(b.dataset.code, 'accept')));
    el.querySelectorAll('.btn-decline').forEach(b => b.addEventListener('click', () => respond(b.dataset.code, 'decline')));
  }

  // รายชื่อเพื่อนร่วมห้อง
  function renderClassmates() {
    const el = document.getElementById('classmatesBody');
    const list = state.classmates || [];
    if (!state.myRoom) {
      el.innerHTML = '<div class="text-center text-danger py-5 fw-bold">ยังไม่ได้กำหนดห้องเรียนให้คุณ กรุณาติดต่อคุณครู</div>';
      return;
    }
    if (list.length === 0) {
      el.innerHTML = '<div class="text-center text-muted py-5">ไม่พบเพื่อนร่วมห้อง</div>';
      return;
    }
    const rows = list.map(c => {
      let right = '';
      switch (c.state) {
        case 'paired_with_me':
          right = '<span class="badge bg-success rounded-pill px-3 py-2">✓ คู่ของคุณ</span>';
          break;
        case 'outgoing_pending':
          right = `<span class="badge bg-info text-dark rounded-pill px-3 py-2 me-2">ส่งคำขอแล้ว รอตอบรับ</span>
                   <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3 btn-cancel" data-code="${escapeHtml(c.code)}">ยกเลิกคำขอ</button>`;
          break;
        case 'incoming_pending':
          right = `<button type="button" class="btn btn-success btn-sm fw-bold rounded-pill px-3 btn-accept2" data-code="${escapeHtml(c.code)}"><i class="bi bi-check-lg"></i> รับคำขอ (จับคู่)</button>`;
          break;
        case 'paired_other':
          right = '<span class="badge bg-secondary rounded-pill px-3 py-2">จับคู่กับคนอื่นแล้ว</span>';
          break;
        case 'unavailable':
          right = '<span class="badge bg-light text-muted rounded-pill px-3 py-2">—</span>';
          break;
        default: // available
          right = state.partner
            ? '<span class="badge bg-light text-muted rounded-pill px-3 py-2">—</span>'
            : `<button type="button" class="btn btn-primary btn-sm fw-bold rounded-pill px-3 btn-send" data-code="${escapeHtml(c.code)}"><i class="bi bi-send"></i> ส่งคำขอจับคู่</button>`;
      }
      return `<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 px-4 py-3 border-bottom">
        <div><span class="font-monospace text-muted me-2">${escapeHtml(c.code)}</span><span class="fw-semibold text-dark">${escapeHtml(c.name)}</span></div>
        <div class="d-flex align-items-center gap-1">${right}</div>
      </div>`;
    }).join('');
    el.innerHTML = rows;
    el.querySelectorAll('.btn-send').forEach(b => b.addEventListener('click', () => sendRequest(b.dataset.code)));
    el.querySelectorAll('.btn-cancel').forEach(b => b.addEventListener('click', () => cancelRequest(b.dataset.code)));
    el.querySelectorAll('.btn-accept2').forEach(b => b.addEventListener('click', () => respond(b.dataset.code, 'accept')));
  }

  function partnerName(code) {
    const c = (state.classmates || []).find(x => x.code === code);
    return c ? c.name : '';
  }

  async function sendRequest(code) {
    try {
      const res = await (await fetch('api.php?action=send_peer_request', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ round: currentRound, target: code })
      })).json();
      if (res.success) {
        showToast(res.matched ? 'จับคู่สำเร็จ! เพื่อนคนนี้เคยส่งคำขอหาคุณอยู่แล้ว' : 'ส่งคำขอจับคู่แล้ว รอเพื่อนกดรับ', 'success');
        loadStatus();
      } else {
        showToast(res.error || 'ส่งคำขอไม่สำเร็จ', 'error');
        loadStatus();
      }
    } catch (err) { showToast('เกิดข้อผิดพลาด: ' + err.message, 'error'); }
  }

  async function cancelRequest(code) {
    try {
      const res = await (await fetch('api.php?action=cancel_peer_request', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ round: currentRound, target: code })
      })).json();
      if (res.success) { showToast('ยกเลิกคำขอแล้ว', 'success'); loadStatus(); }
      else { showToast(res.error || 'ยกเลิกไม่สำเร็จ', 'error'); }
    } catch (err) { showToast('เกิดข้อผิดพลาด: ' + err.message, 'error'); }
  }

  async function respond(code, decision) {
    if (decision === 'decline' && !confirm('ปฏิเสธคำขอจับคู่จากเพื่อนคนนี้ใช่หรือไม่?')) return;
    try {
      const res = await (await fetch('api.php?action=respond_peer_request', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ round: currentRound, requester: code, decision })
      })).json();
      if (res.success) {
        showToast(res.matched ? 'จับคู่สำเร็จแล้ว! คุณกับเพื่อนจะตรวจงานให้กันและกัน' : 'ปฏิเสธคำขอแล้ว', res.matched ? 'success' : 'info');
        loadStatus();
      } else {
        showToast(res.error || 'ดำเนินการไม่สำเร็จ', 'error');
        loadStatus();
      }
    } catch (err) { showToast('เกิดข้อผิดพลาด: ' + err.message, 'error'); }
  }

  async function unpair() {
    if (!confirm('ยกเลิกการจับคู่ในหน่วยนี้ทั้งสองฝ่าย เพื่อขอจับคู่ใหม่ ใช่หรือไม่?')) return;
    try {
      const res = await (await fetch('api.php?action=unpair_peer_match', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ round: currentRound })
      })).json();
      if (res.success) { showToast('ยกเลิกคู่แล้ว คุณสามารถส่งคำขอใหม่ได้', 'success'); loadStatus(); }
      else { showToast(res.error || 'ยกเลิกไม่สำเร็จ', 'error'); }
    } catch (err) { showToast('เกิดข้อผิดพลาด: ' + err.message, 'error'); }
  }

  // เปลี่ยนหน่วย/รอบ
  document.querySelectorAll('.round-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.round-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      currentRound = btn.dataset.round;
      loadStatus();
    });
  });
  document.getElementById('btnRefresh').addEventListener('click', loadStatus);

  loadStatus();
</script>

<?php require_once 'footer.php'; ?>
