<?php
$page_title = 'รายงานการส่งงาน - ระบบประเมินเรียงความ';
require_once 'auth_helper.php';
require_login('teacher');
require_once 'header.php';
?>

<div id="view-submission-report" class="text-start">

  <!-- แถบสรุปด้านบน (Stat cards) -->
  <div class="row g-3 mb-3 no-print">
    <div class="col-md-3 col-6">
      <div class="stat-card">
        <div class="stat-label"><span class="stat-icon badge-blue">👥</span> นักเรียนทั้งหมด</div>
        <div class="stat-value" id="statTotal">-</div>
        <div class="stat-foot" id="statGroup">ทุกกลุ่มการวิจัย</div>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="stat-card">
        <div class="stat-label"><span class="stat-icon badge-blue">✅</span> ส่งครบทุกชิ้น</div>
        <div class="stat-value" id="statComplete">-</div>
        <div class="stat-foot">ครบ 16 ชิ้นงาน</div>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="stat-card">
        <div class="stat-label"><span class="stat-icon badge-coral">⏳</span> ยังส่งไม่ครบ</div>
        <div class="stat-value" id="statPartial">-</div>
        <div class="stat-foot">มีบางชิ้นค้างส่ง</div>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="stat-card">
        <div class="stat-label"><span class="stat-icon badge-blue">📊</span> อัตราการส่งงาน</div>
        <div class="stat-value" id="statRate">-</div>
        <div class="stat-foot">เฉลี่ยทั้งชั้น</div>
      </div>
    </div>
  </div>

  <!-- ตารางรายงานการส่งงาน -->
  <div class="content-card" id="reportPrintArea">
   <div id="reportPrintInner">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
      <div>
        <h5 class="fw-bold mb-1" style="color:var(--primary-navy)"><i class="bi bi-table me-2"></i>รายงานการส่งงานรายบุคคล</h5>
        <p class="text-muted small mb-0 report-desc">ติดตามสถานะการส่งเรียงความ การประเมินตนเอง–ประเมินเพื่อน และเครื่องมือสะท้อนคิดของนักเรียนแต่ละคน (ใช้ตัวเลือกกลุ่มการวิจัยที่แถบด้านบน)</p>
        <div class="print-only print-meta" id="printMeta"></div>
      </div>
      <div class="d-flex gap-2 no-print flex-wrap">
        <button class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-bold" onclick="exportSubmissionCSV()">
          <i class="bi bi-filetype-csv me-1"></i> ส่งออก CSV
        </button>
        <button class="btn btn-primary btn-sm rounded-pill px-3 fw-bold shadow-sm" onclick="exportSubmissionPDF()">
          <i class="bi bi-filetype-pdf me-1"></i> ส่งออก PDF (ดูตัวอย่างก่อนพิมพ์)
        </button>
        <button class="btn btn-outline-success btn-sm rounded-pill px-3 fw-bold" onclick="openClassReport()">
          <i class="bi bi-clipboard-data me-1"></i> รายงานภาพรวมชั้นเรียน
        </button>
        <button class="btn btn-success btn-sm rounded-pill px-3 fw-bold shadow-sm" onclick="openStudentReport()">
          <i class="bi bi-person-vcard me-1"></i> รายงานรายบุคคลทั้งห้อง
        </button>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 report-table" style="min-width: 1350px;">
        <thead>
          <tr class="report-head-group">
            <th rowspan="2" class="sticky-col text-start align-middle">รหัสนักเรียน</th>
            <th rowspan="2" class="sticky-col-2 text-start align-middle">ชื่อ-สกุลผู้เรียน</th>
            <th rowspan="2" class="align-middle">ก่อนเรียน</th>
            <th colspan="7" class="grp-unit1">หน่วยการเรียนที่ 1</th>
            <th colspan="7" class="grp-unit2">หน่วยการเรียนที่ 2</th>
            <th rowspan="2" class="align-middle">หลังเรียน</th>
          </tr>
          <tr class="report-head-sub">
            <th class="grp-unit1">D1.1</th>
            <th class="grp-unit1">D1.2</th>
            <th class="grp-unit1">ประเมินตนเอง</th>
            <th class="grp-unit1">ประเมินเพื่อน</th>
            <th class="grp-unit1">ปัญหาการเขียน</th>
            <th class="grp-unit1">ตรวจสอบตนเอง</th>
            <th class="grp-unit1">สะท้อนการเรียนรู้</th>
            <th class="grp-unit2">D2.1</th>
            <th class="grp-unit2">D2.2</th>
            <th class="grp-unit2">ประเมินตนเอง</th>
            <th class="grp-unit2">ประเมินเพื่อน</th>
            <th class="grp-unit2">ปัญหาการเขียน</th>
            <th class="grp-unit2">ตรวจสอบตนเอง</th>
            <th class="grp-unit2">สะท้อนการเรียนรู้</th>
          </tr>
        </thead>
        <tbody id="reportBody">
          <tr><td colspan="18" class="text-center text-muted py-5">
            <div class="spinner-border spinner-border-sm me-2" role="status"></div> กำลังโหลดข้อมูล...
          </td></tr>
        </tbody>
      </table>
    </div>
   </div><!-- /#reportPrintInner -->
  </div>
</div>

<style>
  .report-table th, .report-table td { white-space: nowrap; text-align: center; font-size: 0.86rem; }
  .report-table thead th {
    background: var(--light-slate);
    color: var(--primary-navy);
    font-weight: 700;
    border-bottom: 2px solid var(--border-gray);
    vertical-align: middle;
  }
  .report-table thead .grp-unit1 { background: #eff6ff; color: #1d4ed8; }
  .report-table thead .grp-unit2 { background: #eef2ff; color: #3730a3; }
  .report-table tbody td { border-bottom: 1px solid var(--border-gray); }
  .report-table .sticky-col, .report-table .sticky-col-2 { text-align: left !important; }
  .report-table tbody .stu-id { font-family: monospace; color: #64748b; font-weight: 600; }
  .report-table tbody .stu-name { font-weight: 600; color: var(--primary-navy); text-align: left; }
  .sub-yes { color: #2563eb; font-size: 1.05rem; }
  .sub-no  { color: #cbd5e1; font-size: 1.05rem; }
  .report-table tbody tr:hover td { background: var(--light-blue); }

  /* องค์ประกอบที่แสดงเฉพาะตอนพิมพ์ PDF */
  .print-only { display: none; }
  .print-meta { font-size: 0.9rem; color: #334155; margin-top: 4px; }

  /* ===================== โหมดพิมพ์ PDF (แนวนอน 1 หน้า) ===================== */
  @media print {
    @page { size: A4 landscape; margin: 8mm; }

    /* ซ่อนเปลือกระบบทั้งหมด เหลือเฉพาะตารางรายงาน */
    .app-sidebar, .app-topbar, .sidebar-backdrop, .no-print, .report-desc { display: none !important; }
    .app-shell { display: block !important; padding: 0 !important; }
    .app-main { gap: 0 !important; }
    .app-content { padding: 0 !important; }
    body { background: #fff !important; }

    .content-card {
      box-shadow: none !important;
      border: none !important;
      padding: 0 !important;
      border-radius: 0 !important;
    }
    .print-only { display: block; }

    /* คลายกล่องเลื่อนแนวนอนให้พิมพ์เห็นทุกคอลัมน์ */
    .table-responsive { overflow: visible !important; border: none !important; }
    .report-table { min-width: 0 !important; width: 100% !important; }
    .report-table th, .report-table td {
      font-size: 8pt !important;
      padding: 2px 4px !important;
    }
    .sub-yes, .sub-no { font-size: 9pt !important; }
    /* พิมพ์สีพื้นหัวตาราง/สถานะให้ครบ */
    .report-table thead th, .report-table thead .grp-unit1, .report-table thead .grp-unit2 {
      -webkit-print-color-adjust: exact; print-color-adjust: exact;
    }
    .sub-yes { -webkit-print-color-adjust: exact; print-color-adjust: exact; }

    /* กล่องย่อ-ขยายให้พอดี 1 หน้า (กำหนดขนาดจริงด้วย JS ก่อนพิมพ์) */
    #reportPrintInner { transform-origin: top left; }
  }
</style>

<script>
  let submissionData = [];

  const REPORT_COLS = [
    { key: 'pretest',     label: 'ก่อนเรียน' },
    { key: 'd1_1',        label: 'D1.1' },
    { key: 'd1_2',        label: 'D1.2' },
    { key: 'self1',       label: 'ประเมินตนเอง (หน่วย 1)' },
    { key: 'peer1',       label: 'ประเมินเพื่อน (หน่วย 1)' },
    { key: 'problems1',   label: 'ปัญหาการเขียน (หน่วย 1)' },
    { key: 'checklist1',  label: 'ตรวจสอบตนเอง (หน่วย 1)' },
    { key: 'reflection1', label: 'สะท้อนการเรียนรู้ (หน่วย 1)' },
    { key: 'd2_1',        label: 'D2.1' },
    { key: 'd2_2',        label: 'D2.2' },
    { key: 'self2',       label: 'ประเมินตนเอง (หน่วย 2)' },
    { key: 'peer2',       label: 'ประเมินเพื่อน (หน่วย 2)' },
    { key: 'problems2',   label: 'ปัญหาการเขียน (หน่วย 2)' },
    { key: 'checklist2',  label: 'ตรวจสอบตนเอง (หน่วย 2)' },
    { key: 'reflection2', label: 'สะท้อนการเรียนรู้ (หน่วย 2)' },
    { key: 'posttest',    label: 'หลังเรียน' },
  ];

  function cellIcon(on) {
    return on
      ? '<i class="bi bi-check-circle-fill sub-yes" title="ส่งแล้ว"></i>'
      : '<i class="bi bi-dash-circle sub-no" title="ยังไม่ส่ง"></i>';
  }

  async function loadSubmissionReport() {
    const body = document.getElementById('reportBody');
    const group = window.TEG ? TEG.param() : '';
    try {
      const res = await fetch('api.php?action=get_submission_report' + group);
      const data = await res.json();
      if (!data.success) {
        body.innerHTML = '<tr><td colspan="18" class="text-center text-danger py-4">' + (data.error || 'โหลดข้อมูลไม่สำเร็จ') + '</td></tr>';
        return;
      }
      submissionData = data.report || [];
      renderReport();
    } catch (err) {
      body.innerHTML = '<tr><td colspan="18" class="text-center text-danger py-4">เกิดข้อผิดพลาดในการเชื่อมต่อ</td></tr>';
    }
  }

  function renderReport() {
    const body = document.getElementById('reportBody');
    if (!submissionData.length) {
      body.innerHTML = '<tr><td colspan="18" class="text-center text-muted py-5">ไม่พบข้อมูลนักเรียนในกลุ่มนี้</td></tr>';
      updateStats(0, 0, 0, 0);
      return;
    }

    let complete = 0, totalSubmitted = 0;
    const totalCells = submissionData.length * REPORT_COLS.length;

    const rows = submissionData.map(s => {
      let done = 0;
      const cells = REPORT_COLS.map(c => {
        const on = !!s[c.key];
        if (on) { done++; totalSubmitted++; }
        return '<td>' + cellIcon(on) + '</td>';
      }).join('');
      if (done === REPORT_COLS.length) complete++;
      return '<tr>' +
        '<td class="stu-id text-start">' + s.student_id + '</td>' +
        '<td class="stu-name">' + (s.student_name || '-') +
          ' <a class="btn btn-link btn-sm p-0 align-baseline no-print" title="เปิดรายงานฉบับเต็มของนักเรียนคนนี้"' +
          ' href="student_report.php?student_id=' + encodeURIComponent(s.student_id) + '"><i class="bi bi-person-vcard"></i></a>' +
          ' <button class="btn btn-link btn-sm p-0 align-baseline no-print" title="พิมพ์รายงานผลการเรียนรู้ของนักเรียนคนนี้"' +
          ' onclick="openStudentReport(\'' + s.student_id + '\')"><i class="bi bi-printer"></i></button>' +
        '</td>' +
        cells +
      '</tr>';
    }).join('');

    body.innerHTML = rows;

    const partial = submissionData.length - complete;
    const rate = totalCells ? Math.round((totalSubmitted / totalCells) * 100) : 0;
    updateStats(submissionData.length, complete, partial, rate);
  }

  function updateStats(total, complete, partial, rate) {
    document.getElementById('statTotal').textContent = total;
    document.getElementById('statComplete').textContent = complete;
    document.getElementById('statPartial').textContent = partial;
    document.getElementById('statRate').textContent = rate + '%';
    const g = (window.TEG ? TEG.get() : 'all');
    document.getElementById('statGroup').textContent = (g === 'all') ? 'ทุกกลุ่มการวิจัย' : g;
  }

  // ส่งออกเป็นไฟล์ CSV (รองรับภาษาไทยด้วย BOM)
  function exportSubmissionCSV() {
    if (!submissionData.length) { showToast('ยังไม่มีข้อมูลให้ส่งออก', 'error'); return; }
    const header = ['รหัสนักเรียน', 'ชื่อ-สกุลผู้เรียน'].concat(REPORT_COLS.map(c => c.label));
    const lines = [header.join(',')];
    submissionData.forEach(s => {
      const row = [s.student_id, '"' + (s.student_name || '') + '"']
        .concat(REPORT_COLS.map(c => s[c.key] ? 'ส่งแล้ว' : 'ยังไม่ส่ง'));
      lines.push(row.join(','));
    });
    const blob = new Blob(['﻿' + lines.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'รายงานการส่งงาน.csv';
    a.click();
    URL.revokeObjectURL(url);
  }

  // รายงานผลการเรียนรู้ — เอกสารพร้อมพิมพ์/บันทึกเป็น PDF (ผลสัมฤทธิ์ ผลงาน และสถิติ)
  // ระบุรหัสนักเรียน = ฉบับของคนนั้นคนเดียว, ไม่ระบุ = ทั้งห้องตามกลุ่มที่เลือกอยู่ (ขึ้นหน้าใหม่ทุกคน)
  function reportQuery(extra) {
    const g = (window.TEG ? TEG.filterValue() : '');
    const qs = new URLSearchParams();
    if (g) qs.set('group', g);
    if (extra) qs.set('student_id', extra);
    const str = qs.toString();
    return str ? ('?' + str) : '';
  }

  function openReportWindow(url) {
    const win = window.open(url, '_blank');
    if (!win) showToast('เบราว์เซอร์บล็อกป๊อปอัป — โปรดอนุญาตป๊อปอัปเพื่อเปิดหน้ารายงาน', 'error');
  }

  function openStudentReport(studentId) {
    if (!studentId && !submissionData.length) { showToast('ยังไม่มีข้อมูลให้ออกรายงาน', 'error'); return; }
    openReportWindow('student_report_print.php' + reportQuery(studentId));
  }

  function openClassReport() {
    if (!submissionData.length) { showToast('ยังไม่มีข้อมูลให้ออกรายงาน', 'error'); return; }
    openReportWindow('class_report_print.php' + reportQuery());
  }

  // ส่งออก PDF — เปิดหน้าตัวอย่างพร้อมพิมพ์ (submission_print.php) ให้ดูก่อน
  // เอกสารถูกย่อให้พอดีกระดาษแผ่นเดียว หน้าเดียว (A4 แนวนอน) แล้วผู้ใช้เลือก "บันทึกเป็น PDF" ได้เอง
  function exportSubmissionPDF() {
    if (!submissionData.length) { showToast('ยังไม่มีข้อมูลให้ส่งออก', 'error'); return; }
    const g = (window.TEG ? TEG.param() : ''); // เช่น '&group=กลุ่มตัวอย่าง' หรือ '' = ทุกกลุ่ม
    const win = window.open('submission_print.php?_t=' + Date.now() + g, '_blank');
    if (!win) { showToast('เบราว์เซอร์บล็อกป๊อปอัป — โปรดอนุญาตป๊อปอัปเพื่อเปิดหน้าตัวอย่างพิมพ์', 'error'); }
  }

  // ให้ตัวกรองกลุ่มบนแถบบนสั่งโหลดใหม่โดยไม่รีเฟรชทั้งหน้า
  window.onTEGChange = loadSubmissionReport;

  document.addEventListener('DOMContentLoaded', loadSubmissionReport);
</script>

<?php require_once 'footer.php'; ?>
