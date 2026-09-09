<?php
/**
 * settings.php — ศูนย์รวมการตั้งค่าทั้งระบบ (เฉพาะคุณครู)
 *
 * เดิมการตั้งค่ากระจายอยู่ตามหน้าที่ใช้งาน ทำให้หน้าจอทำงานประจำวันรก
 * และหาที่ตั้งค่าไม่เจอ หน้านี้จึงรวบไว้ที่เดียว แบ่งเป็น 5 กลุ่ม
 *   1) ระบบตรวจอัตโนมัติ   — ผู้ให้บริการโมเดลภาษา / โมเดล / API key / เปิด-ปิดการใช้งาน
 *      (ย้ายมาจากหน้า writing_feedback.php)
 *   2) ข้อมูลประจำงานวิจัย  — ปีการศึกษา ประชากร รอบงานที่ใช้วิเคราะห์ ฯลฯ
 *      (ย้ายมาจากหน้า chapter45.php)
 *   3) ส่งออกเข้า SPSS     — ข้อมูลดิบ ไฟล์คำสั่ง .sps และพจนานุกรมตัวแปร สำหรับรันสถิติซ้ำใน SPSS
 *   4) เชื่อมต่อ Google    — สถานะบัญชีที่ใช้ส่งรายงานเข้า Google Docs
 *   5) การแสดงผลในเครื่องนี้ — ค่าที่จำไว้เฉพาะเบราว์เซอร์เครื่องนี้ เช่นกลุ่มการวิจัยเริ่มต้น
 *
 * ค่าในกลุ่ม 1-2 เก็บที่เซิร์ฟเวอร์ (ใช้ร่วมกันทุกเครื่อง) ส่วนกลุ่ม 5 เก็บใน localStorage
 */
$page_title = 'ตั้งค่าระบบ - ระบบประเมินเรียงความ';
require_once 'auth_helper.php';
require_login('teacher'); // ครูเท่านั้น
require_once 'header.php';
?>

<div class="mb-3">
  <h3 class="fw-extrabold text-dark mb-1"><i class="bi bi-gear-fill text-secondary me-2"></i>ตั้งค่าระบบ</h3>
  <p class="text-muted small mb-0">
    รวมการตั้งค่าทุกอย่างไว้ที่เดียว — หน้าทำงานอื่นจะได้เหลือแต่เครื่องมือที่ใช้จริงทุกวัน
  </p>
</div>

<!-- แท็บกลุ่มการตั้งค่า -->
<ul class="nav nav-pills mb-4 p-1 bg-white rounded-pill shadow-sm d-inline-flex flex-wrap" id="stTabs" role="tablist"
    style="border:1px solid var(--border-gray);">
  <li class="nav-item" role="presentation">
    <button class="nav-link active rounded-pill fw-bold px-4" id="tab-ai" data-bs-toggle="pill"
            data-bs-target="#pane-ai" type="button" role="tab">
      <i class="bi bi-sliders me-1"></i> ระบบตรวจอัตโนมัติ
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link rounded-pill fw-bold px-4" id="tab-research" data-bs-toggle="pill"
            data-bs-target="#pane-research" type="button" role="tab">
      <i class="bi bi-journal-richtext me-1"></i> ข้อมูลประจำงานวิจัย
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link rounded-pill fw-bold px-4" id="tab-spss" data-bs-toggle="pill"
            data-bs-target="#pane-spss" type="button" role="tab">
      <i class="bi bi-bar-chart-steps me-1"></i> ส่งออกเข้า SPSS
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link rounded-pill fw-bold px-4" id="tab-google" data-bs-toggle="pill"
            data-bs-target="#pane-google" type="button" role="tab">
      <i class="bi bi-google me-1"></i> เชื่อมต่อ Google
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link rounded-pill fw-bold px-4" id="tab-display" data-bs-toggle="pill"
            data-bs-target="#pane-display" type="button" role="tab">
      <i class="bi bi-display me-1"></i> การแสดงผลในเครื่องนี้
    </button>
  </li>
</ul>

<div class="tab-content">

  <!-- ============================ 1) ระบบตรวจอัตโนมัติ ============================ -->
  <div class="tab-pane fade show active" id="pane-ai" role="tabpanel" aria-labelledby="tab-ai">
    <div class="card border-0 shadow-sm rounded-4 mb-4">
      <div class="card-header bg-white border-bottom py-3 px-4 rounded-top-4">
        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-sliders text-primary me-2"></i>ระบบตรวจงานเขียนอัตโนมัติ</h6>
        <div class="text-muted small mt-1">
          ค่าชุดนี้ใช้ร่วมกันทั้งระบบ — ทั้งการตรวจเรียงความ การจัดเว้นวรรค และการเรียบเรียงบทที่ 4-5
        </div>
      </div>
      <div class="card-body p-4">
        <div class="alert alert-primary border-0 rounded-3 small">
          <i class="bi bi-key-fill me-1"></i>
          ต้องมี <strong>API key</strong> ของผู้ให้บริการโมเดลภาษาก่อนจึงจะใช้งานได้ —
          มีหลายเจ้าที่<strong>ให้ใช้ฟรี</strong> ดูวิธีขอทีละขั้นได้ในไฟล์ <code>AUTOCHECK_SETUP.md</code>
        </div>

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-bold small">ผู้ให้บริการโมเดลภาษา</label>
            <select id="aiProvider" class="form-select border-2 rounded-3" onchange="onProviderChange()"></select>
            <div class="form-text small" id="aiProviderHint"></div>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold small">ชื่อโมเดล</label>
            <div class="input-group">
              <input type="text" id="aiModel" class="form-control border-2 rounded-start-3" placeholder="เช่น gemini-3.6-flash">
              <button class="btn btn-outline-secondary rounded-end-3" type="button" onclick="useDefaultModel()"
                      title="ล้างช่องนี้เพื่อกลับไปใช้โมเดลเริ่มต้นของผู้ให้บริการ">
                <i class="bi bi-arrow-counterclockwise"></i> ใช้ค่าเริ่มต้น
              </button>
            </div>
            <div class="form-text small">
              เว้นว่างไว้เพื่อใช้โมเดลเริ่มต้นของผู้ให้บริการ (แนะนำ — ระบบจะตามรุ่นใหม่ให้เองเมื่อผู้ให้บริการเลิกใช้รุ่นเก่า)
            </div>
          </div>
          <div class="col-md-8">
            <label class="form-label fw-bold small">API key</label>
            <input type="password" id="aiApiKey" class="form-control border-2 rounded-3" autocomplete="off"
                   placeholder="วาง API key ที่นี่ (เว้นว่างไว้ = ใช้คีย์เดิม)">
            <div class="form-text small" id="aiKeyHint"></div>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-bold small">เว็บที่ให้บริการ (Base URL)</label>
            <input type="text" id="aiBaseUrl" class="form-control border-2 rounded-3" placeholder="ใช้ค่าเริ่มต้น">
          </div>
        </div>

        <hr class="my-4">

        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" id="aiEnabled">
          <label class="form-check-label fw-bold small" for="aiEnabled">เปิดใช้งานระบบตรวจอัตโนมัติ</label>
        </div>
        <div class="form-text small">
          ปิดสวิตช์นี้เมื่อไม่ต้องการให้สั่งตรวจเพิ่ม — ผลตรวจที่บันทึกไว้แล้วยังแสดงให้นักเรียนดูได้ตามปกติ
        </div>

        <div class="alert alert-light border rounded-3 small mt-3 mb-0">
          <i class="bi bi-infinity me-1"></i>
          <strong>ไม่มีเพดานการเรียกใช้รายวันของระบบเรา</strong> — สั่งตรวจได้เท่าที่ต้องการ
          ระบบยังนับจำนวนครั้งที่ใช้ในแต่ละวันไว้ให้ดูย้อนหลัง (นับเฉพาะครั้งที่สำเร็จ)
          เพดานที่เหลืออยู่จริงคือของ<strong>ผู้ให้บริการโมเดลภาษาเอง</strong> ซึ่งถ้าเต็มจะตอบกลับมาเป็นข้อผิดพลาด 429
          และระบบจะแสดงข้อความนั้นให้เห็นตรง ๆ
        </div>
        <div class="alert alert-secondary border-0 rounded-3 small mt-3 mb-0">
          <i class="bi bi-person-lock me-1"></i>
          <strong>คุณครูเป็นผู้สั่งตรวจเพียงผู้เดียว</strong> — นักเรียนกดให้ระบบตรวจเองไม่ได้
          เห็นได้เฉพาะผลที่คุณครูตรวจให้แล้วเท่านั้น
        </div>

        <div class="d-flex justify-content-end gap-2 mt-4">
          <button class="btn btn-outline-danger rounded-pill px-3" onclick="clearApiKey()">
            <i class="bi bi-trash me-1"></i>ลบ API key
          </button>
          <button class="btn btn-primary rounded-pill px-4 fw-bold" id="aiSaveSettingsBtn" onclick="saveAiSettings()">
            <i class="bi bi-check2-circle me-1"></i>บันทึกการตั้งค่า
          </button>
        </div>

        <div id="aiUsageBox" class="mt-4 small text-muted"></div>
      </div>
    </div>
  </div>

  <!-- ============================ 2) ข้อมูลประจำงานวิจัย ============================ -->
  <div class="tab-pane fade" id="pane-research" role="tabpanel" aria-labelledby="tab-research">
    <div class="card border-0 shadow-sm rounded-4 mb-4">
      <div class="card-header bg-white border-bottom py-3 px-4 rounded-top-4">
        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-journal-richtext text-secondary me-2"></i>ข้อมูลประจำงานวิจัย</h6>
        <div class="text-muted small mt-1">
          ค่าที่กรอกที่นี่ถูกนำไปใช้ในย่อหน้าเปิดบทที่ 5 และเป็นตัวกำหนดว่าจะใช้ผลงานรอบใดเป็น
          &quot;ผลงานครั้งที่ 1 และครั้งที่ 2&quot; ในการวิเคราะห์เชิงคุณภาพ
        </div>
      </div>
      <div class="card-body p-4">
        <div id="stMetaForm" class="row g-3">
          <div class="col-12 text-muted small"><span class="spinner-border spinner-border-sm me-2"></span>กำลังโหลด...</div>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4">
          <a href="chapter45.php" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-box-arrow-up-right me-1"></i>ไปหน้าวิเคราะห์บทที่ 4-5
          </a>
          <button class="btn btn-primary rounded-pill px-4 fw-bold" id="stMetaSaveBtn" onclick="saveMeta()">
            <i class="bi bi-save me-1"></i>บันทึกข้อมูลประจำงานวิจัย
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- ============================ 3) ส่งออกเข้า SPSS ============================ -->
  <div class="tab-pane fade" id="pane-spss" role="tabpanel" aria-labelledby="tab-spss">
    <div class="card border-0 shadow-sm rounded-4 mb-4">
      <div class="card-header bg-white border-bottom py-3 px-4 rounded-top-4">
        <h6 class="fw-bold text-dark mb-0">
          <i class="bi bi-bar-chart-steps text-success me-2"></i>ส่งออกข้อมูลดิบเข้า IBM SPSS Statistics
        </h6>
        <div class="text-muted small mt-1">
          ระบบคำนวณสถิติทุกตัวให้อยู่แล้ว หน้านี้เตรียม<strong>ข้อมูลดิบและไฟล์คำสั่ง</strong>ให้เอาไปรันซ้ำใน SPSS
          เพื่อแนบผลจาก SPSS เป็นหลักฐานของตัวเลขในวิทยานิพนธ์
        </div>
      </div>
      <div class="card-body p-4">

        <div class="alert alert-success border-0 rounded-3 small">
          <i class="bi bi-box-seam me-1"></i>
          ปุ่มเดียวได้ครบ <strong>5 ไฟล์</strong> — ข้อมูลดิบ · คะแนนรายผู้ประเมิน · ไฟล์คำสั่ง <code>.sps</code> ·
          พจนานุกรมตัวแปร · คู่มือย่อ ทั้งหมดพร้อมใช้กับ <strong>SPSS รุ่น 27 ขึ้นไป (รวมรุ่น 32)</strong>
          เปิดไฟล์คำสั่งแล้วสั่ง Run ได้ทันที ไม่ต้องพิมพ์คำสั่งเอง
        </div>

        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label fw-bold small">กลุ่มการวิจัยที่ส่งออก</label>
            <select id="spssGroup" class="form-select border-2 rounded-3">
              <option value="">ทุกกลุ่มรวมกัน</option>
              <option value="กลุ่มทดลอง">กลุ่มทดลอง</option>
              <option value="กลุ่มตัวอย่าง">กลุ่มตัวอย่าง</option>
            </select>
            <div class="form-text small">ควรตรงกับกลุ่มที่ใช้เขียนบทที่ 4 เพื่อให้ตัวเลขตรงกับหน้าจอ</div>
          </div>
          <div class="col-md-3">
            <label class="form-label fw-bold small">ห้องเรียน (ไม่ระบุ = ทุกห้อง)</label>
            <input type="text" id="spssRoom" class="form-control border-2 rounded-3" placeholder="เช่น 606">
          </div>
          <div class="col-md-5">
            <label class="form-label fw-bold small">โฟลเดอร์ที่จะเก็บไฟล์บนเครื่องที่ลง SPSS</label>
            <input type="text" id="spssDir" class="form-control border-2 rounded-3" value="C:\thaieasay_spss">
            <div class="form-text small">
              ระบบเขียน path นี้ลงในไฟล์คำสั่งให้เลย — แตกไฟล์ไว้ตรงนี้แล้วกด Run ได้ทันที
            </div>
          </div>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-4">
          <button class="btn btn-success rounded-pill px-4 fw-bold" onclick="spssDownload('zip')">
            <i class="bi bi-download me-1"></i>ดาวน์โหลดชุดข้อมูลทั้งชุด (.zip)
          </button>
          <button class="btn btn-outline-secondary rounded-pill px-3" onclick="spssDownload('data')">
            <i class="bi bi-filetype-csv me-1"></i>เฉพาะข้อมูลดิบ
          </button>
          <button class="btn btn-outline-secondary rounded-pill px-3" onclick="spssDownload('syntax')">
            <i class="bi bi-code-square me-1"></i>เฉพาะไฟล์คำสั่ง
          </button>
          <button class="btn btn-outline-secondary rounded-pill px-3" onclick="spssDownload('codebook')">
            <i class="bi bi-journal-text me-1"></i>เฉพาะพจนานุกรมตัวแปร
          </button>
        </div>

        <hr class="my-4">

        <h6 class="fw-bold small mb-3"><i class="bi bi-list-ol text-primary me-1"></i>วิธีนำไปใช้ (5 ขั้น)</h6>
        <ol class="small ps-3 mb-4" style="line-height:1.9;">
          <li>กดปุ่มสีเขียวด้านบน แล้ว<strong>แตกไฟล์ zip ทั้งหมดไว้ในโฟลเดอร์เดียวกัน</strong>
              (แนะนำให้ใช้โฟลเดอร์ตามที่กรอกไว้ในช่องด้านบน จะได้ไม่ต้องแก้อะไรเลย)</li>
          <li>เปิด SPSS → เมนู <strong>File → Open → Syntax…</strong> → เลือกไฟล์ <code>spss_syntax.sps</code></li>
          <li>ถ้าเก็บไฟล์ไว้โฟลเดอร์อื่น ให้กด Ctrl+H แทนที่ path เดิมด้วยโฟลเดอร์จริง (มี 4 แห่ง ระบบบอกไว้ในไฟล์แล้ว)</li>
          <li>กด <strong>Ctrl+A</strong> เลือกทั้งหมด แล้วกด <strong>Ctrl+R</strong> เพื่อสั่งรัน — ผลจะขึ้นในหน้าต่าง Output</li>
          <li>บันทึก Output เป็นไฟล์ <code>.spv</code> หรือส่งออกเป็น Word/PDF เพื่อแนบเป็นหลักฐานของตัวเลขในบทที่ 4</li>
        </ol>

        <h6 class="fw-bold small mb-2"><i class="bi bi-table text-primary me-1"></i>ผลจาก SPSS ตรงกับส่วนใดของวิทยานิพนธ์</h6>
        <div class="table-responsive mb-4">
          <table class="table table-sm table-bordered align-middle small mb-0">
            <thead class="table-light">
              <tr><th style="width:38%;">ตารางที่ SPSS พิมพ์ออกมา</th><th>ใช้เติมช่องใดในวิทยานิพนธ์</th></tr>
            </thead>
            <tbody>
              <tr><td>Descriptives</td><td>ตาราง 12 — ค่าเฉลี่ยและส่วนเบี่ยงเบนมาตรฐาน ก่อน/หลังเรียน</td></tr>
              <tr><td>Paired-Samples T Test</td><td>ตาราง 12 — ค่า t, df, p และขนาดอิทธิพล (Cohen's d)</td></tr>
              <tr><td>Tests of Normality</td><td>ข้อตกลงเบื้องต้นก่อนใช้ t-test (Shapiro-Wilk)</td></tr>
              <tr><td>Wilcoxon Signed Ranks Test</td><td>ใช้แทน t-test เมื่อคะแนนผลต่างไม่เป็นการแจกแจงปกติ</td></tr>
              <tr><td>Frequencies (ตัวแปร w1_def / w2_def)</td><td>ตาราง 14 — จำนวนและร้อยละของผู้ปรากฏข้อบกพร่อง 11 ตัวบ่งชี้</td></tr>
              <tr><td>McNemar Test</td><td>ทดสอบว่าสัดส่วนผู้มีข้อบกพร่องเปลี่ยนอย่างมีนัยสำคัญหรือไม่</td></tr>
              <tr><td>Intraclass Correlation Coefficient</td><td>ความเที่ยงระหว่างผู้ประเมิน ICC(3,1) และ ICC(3,k)</td></tr>
              <tr><td>Correlations</td><td>ค่าสหสัมพันธ์ระหว่างผู้ประเมินรายคู่ (Pearson r) ในตาราง 12</td></tr>
            </tbody>
          </table>
        </div>

        <div class="alert alert-light border rounded-3 small mb-3">
          <i class="bi bi-check2-square me-1"></i>
          <strong>ตรวจว่าตรงกับระบบได้ทันที</strong> — ตัวเลขที่ระบบคำนวณไว้แล้ว (M, SD, t, p, d, ICC และตาราง 14
          ทุกแถว) แนบไว้เป็นคอมเมนต์ในไฟล์คำสั่งด้วย จึงเทียบกับผลที่ SPSS พิมพ์ออกมาได้ทีละบรรทัด
          ถ้าไม่ตรงกัน มักเป็นเพราะเลือกกลุ่มการวิจัยคนละกลุ่มกับที่ดูอยู่บนหน้าจอ
        </div>

        <div class="alert alert-warning border-0 rounded-3 small mb-0">
          <i class="bi bi-shield-exclamation me-1"></i>
          <strong>ก่อนแนบชุดข้อมูลเป็นภาคผนวก ให้ลบคอลัมน์ <code>stu_name</code> ทิ้งก่อนเสมอ</strong> —
          บทที่ 4 อ้างถึงนักเรียนด้วยเลขนิรนามในคอลัมน์ <code>stu_no</code> เท่านั้น ·
          ส่วนคอลัมน์จำนวนคำสะกดผิดเป็นค่าประมาณจากพจนานุกรมอัตโนมัติ ควรสุ่มตรวจก่อนรายงานเป็นตัวเลข
        </div>

      </div>
    </div>
  </div>

  <!-- ============================ 4) เชื่อมต่อ Google ============================ -->
  <div class="tab-pane fade" id="pane-google" role="tabpanel" aria-labelledby="tab-google">
    <div class="card border-0 shadow-sm rounded-4 mb-4">
      <div class="card-header bg-white border-bottom py-3 px-4 rounded-top-4">
        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-google text-danger me-2"></i>บัญชี Google สำหรับส่งรายงานเข้า Google Docs</h6>
        <div class="text-muted small mt-1">
          ใช้กับปุ่ม &quot;ส่งเข้า Google Doc&quot; ในหน้าผลตรวจอัตโนมัติและหน้าวิเคราะห์งานวิจัย บทที่ 4-5
        </div>
      </div>
      <div class="card-body p-4">
        <div id="stGoogleStatus" class="mb-3">
          <span class="text-muted small"><span class="spinner-border spinner-border-sm me-2"></span>กำลังตรวจสอบสถานะ...</span>
        </div>
        <div id="stGoogleActions" class="d-flex flex-wrap gap-2"></div>
        <div id="stGoogleRedirect" class="mt-4"></div>
      </div>
    </div>
  </div>

  <!-- ============================ 5) การแสดงผลในเครื่องนี้ ============================ -->
  <div class="tab-pane fade" id="pane-display" role="tabpanel" aria-labelledby="tab-display">
    <div class="card border-0 shadow-sm rounded-4 mb-4">
      <div class="card-header bg-white border-bottom py-3 px-4 rounded-top-4">
        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-display text-primary me-2"></i>การแสดงผลในเครื่องนี้</h6>
        <div class="text-muted small mt-1">
          ค่าสองข้อนี้จำไว้เฉพาะเบราว์เซอร์เครื่องนี้ ไม่ได้บันทึกลงฐานข้อมูล จึงไม่กระทบเครื่องอื่น
        </div>
      </div>
      <div class="card-body p-4">
        <label class="form-label fw-bold small">กลุ่มการวิจัยที่ใช้เป็นค่าเริ่มต้น</label>
        <div class="form-text small mb-2">
          ค่าเดียวกับปุ่มเลือกกลุ่มบนแถบด้านบน — ทุกหน้าของคุณครูใช้ค่านี้ร่วมกัน
        </div>
        <select id="stGroupSelect" class="form-select border-2 rounded-3" style="max-width:340px;" onchange="saveGroupPref()">
          <option value="all">ทุกกลุ่มรวมกัน</option>
          <option value="กลุ่มทดลอง">🧪 กลุ่มทดลอง</option>
          <option value="กลุ่มตัวอย่าง">📋 กลุ่มตัวอย่าง (ค่าเริ่มต้นของระบบ)</option>
        </select>

        <hr class="my-4">

        <label class="form-label fw-bold small">แถบเมนูด้านซ้าย</label>
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" id="stSidebarCollapsed" onchange="saveSidebarPref()">
          <label class="form-check-label small" for="stSidebarCollapsed">ยุบแถบเมนูไว้เสมอ (คืนพื้นที่หน้าจอ)</label>
        </div>
      </div>
    </div>
  </div>

</div>

<script>
/* =======================================================================
   หน้าตั้งค่า — โหลด/บันทึกค่าแต่ละกลุ่ม
   ======================================================================= */
function esc(s) {
  return String(s == null ? '' : s)
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

async function stPost(payload) {
  const res = await fetch('api.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  });
  return res.json();
}

/* ---------------------------------------------------- 1) ระบบตรวจอัตโนมัติ */
let aiProviders = [];

async function loadAiSettings() {
  try {
    const res  = await fetch('api.php?action=get_ai_settings');
    const data = await res.json();
    if (!data.success) { showToast(data.error || 'โหลดการตั้งค่าไม่สำเร็จ', 'error'); return; }
    aiProviders = data.providers;

    const sel = document.getElementById('aiProvider');
    sel.innerHTML = data.providers.map(p =>
      `<option value="${esc(p.key)}">${esc(p.label)}</option>`).join('');
    sel.value = data.settings.provider;

    document.getElementById('aiModel').value      = data.settings.model || '';
    document.getElementById('aiBaseUrl').value    = data.settings.base_url || '';
    document.getElementById('aiEnabled').checked  = !!data.settings.enabled;

    const hint = document.getElementById('aiKeyHint');
    if (data.settings.locked_by_file) {
      hint.innerHTML = '<span class="text-success"><i class="bi bi-shield-lock me-1"></i>ใช้คีย์จากไฟล์ writing_check_secrets.php บนเซิร์ฟเวอร์ '
        + '(' + esc(data.settings.api_key_masked) + ') — ค่าที่กรอกในหน้านี้จะไม่ถูกใช้</span>';
    } else if (data.settings.has_key) {
      hint.innerHTML = 'มีคีย์บันทึกไว้แล้ว: <code>' + esc(data.settings.api_key_masked) + '</code> · เว้นว่างไว้ = ใช้คีย์เดิม';
    } else {
      hint.textContent = 'ยังไม่มี API key ในระบบ';
    }

    onProviderChange(true);
    renderUsage(data.usage);
  } catch (err) {
    console.error(err);
    showToast('เชื่อมต่อไม่สำเร็จ', 'error');
  }
}

function onProviderChange(initial) {
  const key = document.getElementById('aiProvider').value;
  const p   = aiProviders.find(x => x.key === key);
  const hint = document.getElementById('aiProviderHint');
  if (!p) { hint.textContent = ''; return; }
  hint.innerHTML = p.key_url
    ? `ขอ API key ฟรีได้ที่ <a href="${esc(p.key_url)}" target="_blank" rel="noopener">${esc(p.key_url)}</a>`
    : 'กรอก Base URL ของเซิร์ฟเวอร์เองในช่องด้านขวา';
  // เปลี่ยนผู้ให้บริการ = เสนอโมเดล/URL เริ่มต้นของเจ้านั้นให้ (ไม่ทับตอนโหลดหน้าครั้งแรก)
  if (!initial) {
    document.getElementById('aiModel').value   = p.default_model || '';
    document.getElementById('aiBaseUrl').value = p.default_base_url || '';
  }
}

// ล้างชื่อโมเดลที่บันทึกไว้ เพื่อกลับไปใช้ค่าเริ่มต้นของผู้ให้บริการที่กำหนดไว้ในโค้ด
// (ใช้เมื่อผู้ให้บริการเลิกให้บริการโมเดลรุ่นเดิม แล้วระบบขึ้นว่า "ไม่พบโมเดลที่ตั้งค่าไว้")
function useDefaultModel() {
  document.getElementById('aiModel').value = '';
  showToast('ล้างชื่อโมเดลแล้ว — กด "บันทึกการตั้งค่า" เพื่อใช้โมเดลเริ่มต้นของผู้ให้บริการ');
}

function renderUsage(usage) {
  const box = document.getElementById('aiUsageBox');
  if (!usage || !usage.length) { box.innerHTML = '<i class="bi bi-graph-up me-1"></i>ยังไม่มีการเรียกใช้ระบบ'; return; }
  const rows = usage.map(u =>
    `<tr><td>${esc(u.d)}</td><td class="text-center">${u.total}</td><td class="text-center text-success">${u.ok}</td>
     <td class="text-center text-danger">${u.total - u.ok}</td></tr>`).join('');
  box.innerHTML = `<div class="fw-bold mb-2"><i class="bi bi-graph-up me-1"></i>การเรียกใช้ระบบย้อนหลัง 7 วัน</div>
    <table class="table table-sm table-bordered mb-0" style="max-width:420px;">
      <thead class="table-light"><tr><th>วันที่</th><th class="text-center">รวม</th><th class="text-center">สำเร็จ</th><th class="text-center">ไม่สำเร็จ</th></tr></thead>
      <tbody>${rows}</tbody></table>`;
}

async function saveAiSettings() {
  const btn = document.getElementById('aiSaveSettingsBtn');
  btn.disabled = true;
  try {
    const data = await stPost({
      action: 'save_ai_settings',
      provider: document.getElementById('aiProvider').value,
      model: document.getElementById('aiModel').value.trim(),
      base_url: document.getElementById('aiBaseUrl').value.trim(),
      api_key: document.getElementById('aiApiKey').value.trim(),
      enabled: document.getElementById('aiEnabled').checked
    });
    if (!data.success) { showToast(data.error || 'บันทึกไม่สำเร็จ', 'error'); return; }
    document.getElementById('aiApiKey').value = '';
    showToast('บันทึกการตั้งค่าเรียบร้อยแล้ว');
    await loadAiSettings();
  } catch (err) {
    showToast('เชื่อมต่อไม่สำเร็จ', 'error');
  } finally {
    btn.disabled = false;
  }
}

async function clearApiKey() {
  if (!confirm('ยืนยันลบ API key ออกจากระบบ? ระบบตรวจอัตโนมัติจะใช้งานไม่ได้จนกว่าจะใส่คีย์ใหม่')) return;
  try {
    const data = await stPost({ action: 'save_ai_settings', api_key: '__CLEAR__' });
    if (!data.success) { showToast(data.error || 'ลบไม่สำเร็จ', 'error'); return; }
    showToast('ลบ API key เรียบร้อยแล้ว');
    await loadAiSettings();
  } catch (err) {
    showToast('เชื่อมต่อไม่สำเร็จ', 'error');
  }
}

/* ---------------------------------------------------- 2) ข้อมูลประจำงานวิจัย */
let stMetaFields = null, stMetaPhases = null, stMetaLevels = null, stMetaIndicators = null, stMetaDomains = null;

async function loadMeta() {
  try {
    const res  = await fetch('api.php?action=ch45_get_meta');
    const data = await res.json();
    if (!data.success) {
      document.getElementById('stMetaForm').innerHTML =
        '<div class="col-12"><div class="alert alert-warning border-0 rounded-3 small mb-0">'
        + esc(data.error || 'โหลดข้อมูลประจำงานวิจัยไม่สำเร็จ') + '</div></div>';
      return;
    }
    stMetaFields = data.meta_fields;
    stMetaPhases = data.phases;
    stMetaLevels = data.levels;
    stMetaIndicators = data.indicators;
    stMetaDomains = data.domains;
    paintMeta(data.meta);
  } catch (err) {
    console.error(err);
    document.getElementById('stMetaForm').innerHTML =
      '<div class="col-12"><div class="alert alert-danger border-0 rounded-3 small mb-0">เชื่อมต่อไม่สำเร็จ</div></div>';
  }
}

/** ตารางเลือกระดับ "ปรากฏข้อบกพร่อง" แยกรายตัวบ่งชี้ — ใช้กับฟิลด์ type: 'level_per_indicator' */
function renderDefectCutTable(f, value) {
  // value = object {รหัสตัวบ่งชี้: ระดับ} ที่ได้จาก ch45_meta() แล้ว (แปลงจาก JSON ให้แล้วฝั่งเซิร์ฟเวอร์)
  const val = (value && typeof value === 'object') ? value : {};
  const doms = stMetaDomains || {};
  const inds = stMetaIndicators || {};
  const levelOpts = Object.keys(stMetaLevels || {});
  let rows = '';
  Object.keys(doms).forEach(function (dk) {
    const dom = doms[dk];
    rows += '<tr class="table-light"><td colspan="2" class="fw-bold small">ด้าน' + esc(dom.name) + '</td></tr>';
    (dom.indicators || []).forEach(function (id) {
      const ind = inds[id] || {};
      const v = val[id] !== undefined ? String(val[id]) : '2';
      rows += '<tr><td class="small">' + esc(ind.sub || '') + ' ' + esc(ind.name || id) + '</td>'
        + '<td><select class="form-select form-select-sm st-defect-cut" data-indicator="' + esc(id) + '">'
        + levelOpts.map(function (lv) {
            return '<option value="' + lv + '"' + (v === lv ? ' selected' : '') + '>'
              + esc(stMetaLevels[lv]) + ' (' + lv + ')</option>';
          }).join('') + '</select></td></tr>';
    });
  });
  return '<div class="col-12"><label class="form-label small fw-bold mb-1">' + esc(f.label) + '</label>'
    + (f.hint ? '<div class="form-text small mb-2">' + esc(f.hint) + '</div>' : '')
    + '<div class="table-responsive"><table class="table table-sm align-middle mb-0">'
    + '<tbody>' + rows + '</tbody></table></div></div>';
}

function paintMeta(meta) {
  let h = '';
  Object.keys(stMetaFields).forEach(function (k) {
    const f = stMetaFields[k];
    const v = meta[k] === undefined ? '' : meta[k];
    if (f.type === 'level_per_indicator') { h += renderDefectCutTable(f, v); return; }
    let input;
    if (f.type === 'source') {
      const opts = { mean: 'คะแนนเฉลี่ยจากผู้ประเมินทุกคน (ตรงกับที่ระบุในบทที่ 4)',
                     teacher: 'คะแนนของครูผู้สอนอย่างเดียว',
                     expert: 'คะแนนของผู้เชี่ยวชาญอย่างเดียว' };
      input = '<select class="form-select form-select-sm st-meta" data-key="' + k + '">'
        + Object.keys(opts).map(function (o) {
            return '<option value="' + o + '"' + (o === v ? ' selected' : '') + '>' + esc(opts[o]) + '</option>';
          }).join('') + '</select>';
    } else if (f.type === 'phase') {
      input = '<select class="form-select form-select-sm st-meta" data-key="' + k + '">'
        + Object.keys(stMetaPhases).map(function (p) {
            return '<option value="' + esc(p) + '"' + (p === v ? ' selected' : '') + '>'
              + esc(stMetaPhases[p]) + '</option>';
          }).join('') + '</select>';
    } else if (f.type === 'level') {
      // ตัวเลือกเรียงจากน้อยไปมาก (JS จัดคีย์ตัวเลขให้เองอัตโนมัติ) — ปรับปรุง(0) ... ดีมาก(4)
      input = '<select class="form-select form-select-sm st-meta" data-key="' + k + '">'
        + Object.keys(stMetaLevels || {}).map(function (lv) {
            return '<option value="' + lv + '"' + (String(v) === lv ? ' selected' : '') + '>'
              + esc(stMetaLevels[lv]) + ' (คะแนนดิบ ' + lv + ')</option>';
          }).join('') + '</select>';
    } else {
      input = '<input class="form-control form-control-sm st-meta" data-key="' + k + '"'
        + (f.type === 'number' ? ' type="number" step="any"' : '')
        + ' value="' + esc(v) + '">';
    }
    h += '<div class="col-md-6 col-lg-4"><label class="form-label small fw-bold mb-1">'
      + esc(f.label) + '</label>' + input
      + (f.hint ? '<div class="form-text small">' + esc(f.hint) + '</div>' : '')
      + '</div>';
  });
  document.getElementById('stMetaForm').innerHTML = h;
}

async function saveMeta() {
  if (!stMetaFields) { showToast('ยังโหลดข้อมูลไม่เสร็จ', 'error'); return; }
  const btn = document.getElementById('stMetaSaveBtn');
  btn.disabled = true;
  try {
    const payload = {};
    document.querySelectorAll('.st-meta').forEach(function (el) { payload[el.dataset.key] = el.value; });
    // ตารางเกณฑ์ข้อบกพร่องรายตัวบ่งชี้ประกอบเป็น JSON ก้อนเดียวเก็บที่คีย์ defect_cut
    const defectCut = {};
    document.querySelectorAll('.st-defect-cut').forEach(function (el) { defectCut[el.dataset.indicator] = el.value; });
    if (Object.keys(defectCut).length) payload.defect_cut = JSON.stringify(defectCut);
    const data = await stPost({ action: 'ch45_save_meta', meta: payload });
    if (!data.success) { showToast(data.error || 'บันทึกไม่สำเร็จ', 'error'); return; }
    showToast('บันทึกข้อมูลประจำงานวิจัยแล้ว — หน้าบทที่ 4-5 จะคำนวณใหม่ตามค่านี้');
    await loadMeta();
  } catch (err) {
    showToast('เชื่อมต่อไม่สำเร็จ', 'error');
  } finally {
    btn.disabled = false;
  }
}

/* ---------------------------------------------------- 3) ส่งออกเข้า SPSS */
function spssDownload(which) {
  const p = new URLSearchParams({
    file: which,
    group: document.getElementById('spssGroup').value,
    classroom: document.getElementById('spssRoom').value.trim(),
    dir: document.getElementById('spssDir').value.trim()
  });
  // เปิดเป็นการดาวน์โหลดตรง ๆ เพราะไฟล์ zip อาจใหญ่กว่าที่ควรถือไว้ในหน่วยความจำของหน้าเว็บ
  window.location.href = 'spss_export.php?' + p.toString();
  showToast('กำลังเตรียมไฟล์ — ถ้าข้อมูลมาก อาจใช้เวลาสักครู่');
}

/** ตั้งกลุ่มเริ่มต้นให้ตรงกับกลุ่มที่คุณครูเลือกดูอยู่ ตัวเลขที่ส่งออกจะได้ตรงกับหน้าจอ */
function loadSpssDefaults() {
  const sel = document.getElementById('spssGroup');
  if (!sel || !window.TEG) return;
  const g = TEG.get();
  sel.value = (g && g !== 'all') ? g : '';
}

/* ---------------------------------------------------- 4) เชื่อมต่อ Google */
async function loadGoogleStatus() {
  const box = document.getElementById('stGoogleStatus');
  const act = document.getElementById('stGoogleActions');
  const red = document.getElementById('stGoogleRedirect');
  try {
    const res = await fetch('google_auth.php?action=status');
    const s   = await res.json();

    if (!s.configured) {
      box.innerHTML = '<span class="badge bg-warning text-dark py-2 px-3"><i class="bi bi-exclamation-triangle me-1"></i>ยังไม่ได้ตั้งค่า Google API</span>'
        + '<div class="alert alert-warning border-0 rounded-3 small mt-3 mb-0">'
        + 'ต้องกรอก Client ID / Client Secret ไว้ในไฟล์ <code>google_secrets.php</code> บนเซิร์ฟเวอร์ก่อน '
        + '(ดูตัวอย่างการกรอกได้ที่ไฟล์ <code>google_secrets.sample.php</code>) — ขั้นตอนนี้ทำที่เซิร์ฟเวอร์ ไม่สามารถกรอกผ่านหน้าเว็บได้'
        + '</div>';
      act.innerHTML = '';
    } else if (s.connected) {
      box.innerHTML = '<span class="badge bg-success py-2 px-3"><i class="bi bi-check-circle me-1"></i>เชื่อมต่อบัญชี Google แล้ว</span>'
        + '<div class="text-muted small mt-2">ส่งรายงานเข้า Google Docs ได้ทันทีจากหน้าที่มีปุ่มส่งออก</div>';
      act.innerHTML = '<button class="btn btn-outline-danger rounded-pill px-3" onclick="googleDisconnect()">'
        + '<i class="bi bi-plug me-1"></i>ยกเลิกการเชื่อมต่อ</button>';
    } else {
      box.innerHTML = '<span class="badge bg-light text-dark border py-2 px-3"><i class="bi bi-plug me-1"></i>ยังไม่ได้เชื่อมต่อบัญชี Google</span>'
        + '<div class="text-muted small mt-2">กดปุ่มด้านล่างเพื่ออนุญาตให้ระบบสร้างไฟล์รายงานในไดรฟ์ของคุณครู</div>';
      act.innerHTML = '<button class="btn btn-primary rounded-pill px-4 fw-bold" onclick="googleConnect()">'
        + '<i class="bi bi-google me-1"></i>เชื่อมต่อบัญชี Google</button>';
    }

    red.innerHTML = s.redirect_uri
      ? '<label class="form-label small fw-bold mb-1">Redirect URI ที่ต้องใส่ใน Google Cloud Console</label>'
        + '<input type="text" class="form-control form-control-sm rounded-3 bg-light" readonly value="' + esc(s.redirect_uri) + '">'
      : '';
  } catch (err) {
    box.innerHTML = '<span class="text-danger small">ตรวจสอบสถานะไม่สำเร็จ</span>';
    act.innerHTML = '';
    red.innerHTML = '';
  }
}

function googleConnect() {
  const ret = encodeURIComponent(location.pathname + '#google');
  window.location.href = 'google_auth.php?action=connect&return=' + ret;
}

async function googleDisconnect() {
  if (!confirm('ยกเลิกการเชื่อมต่อบัญชี Google? ปุ่มส่งรายงานเข้า Google Docs จะใช้ไม่ได้จนกว่าจะเชื่อมต่อใหม่')) return;
  try {
    await fetch('google_auth.php?action=disconnect');
    showToast('ยกเลิกการเชื่อมต่อแล้ว');
  } catch (err) {
    showToast('เชื่อมต่อไม่สำเร็จ', 'error');
  }
  loadGoogleStatus();
}

/* ---------------------------------------------------- 5) การแสดงผลในเครื่องนี้ */
function loadDisplayPrefs() {
  const sel = document.getElementById('stGroupSelect');
  if (sel && window.TEG) sel.value = TEG.get();
  const sw = document.getElementById('stSidebarCollapsed');
  // อ่านจาก localStorage ตรง ๆ เพราะ header.php จะใส่คลาสให้เฉพาะตอนเปิดบนจอใหญ่
  let collapsed = false;
  try { collapsed = localStorage.getItem('thaieasay_sidebar_collapsed') === '1'; } catch (e) {}
  if (sw) sw.checked = collapsed;
}

function saveGroupPref() {
  const v = document.getElementById('stGroupSelect').value;
  if (window.TEG) TEG.set(v);
  if (typeof tegPaintNavButtons === 'function') tegPaintNavButtons();
  showToast('จำกลุ่มการวิจัยเริ่มต้นไว้แล้ว');
}

function saveSidebarPref() {
  const on = document.getElementById('stSidebarCollapsed').checked;
  document.body.classList.toggle('sidebar-collapsed', on);
  try { localStorage.setItem('thaieasay_sidebar_collapsed', on ? '1' : '0'); } catch (e) {}
}

/* ---------------------------------------------------- เปิดแท็บตามลิงก์ที่เข้ามา */
function openTabFromHash() {
  const map = { '#ai': 'tab-ai', '#research': 'tab-research', '#spss': 'tab-spss',
                '#google': 'tab-google', '#display': 'tab-display' };
  const id = map[location.hash];
  if (!id) return;
  const btn = document.getElementById(id);
  if (btn) bootstrap.Tab.getOrCreateInstance(btn).show();
}
window.addEventListener('hashchange', openTabFromHash);

/* ---------------------------------------------------- เริ่มทำงาน
   รอให้ทั้งหน้าโหลดเสร็จก่อน เพราะ Bootstrap JS และ showToast() อยู่ใน footer.php */
document.addEventListener('DOMContentLoaded', async function () {
  openTabFromHash();
  loadDisplayPrefs();
  loadSpssDefaults();
  loadGoogleStatus();
  await loadAiSettings();
  await loadMeta();
});
</script>

<?php require_once 'footer.php'; ?>
