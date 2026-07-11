<?php
$page_title = 'บันทึกสำเร็จ - ระบบประเมินเรียงความอัจฉริยะ';
require_once 'auth_helper.php';
require_login();

require_once 'header.php';
?>

<div class="row justify-content-center my-5">
  <div class="col-md-5 col-sm-12">
    <div class="card border-0 shadow-lg rounded-4 p-5 text-center bg-white" style="border-top: 6px solid var(--accent-teal) !important;">
      <div class="display-3 text-success mb-3"><i class="bi bi-check2-circle-fill"></i></div>
      <h2 class="fw-bold text-dark">บันทึกข้อมูลสำเร็จ!</h2>
      <p class="text-muted">ข้อมูลคะแนนประเมินเรียงความตามเกณฑ์คุณภาพได้รับการอัปเดตเรียบร้อยแล้ว</p>
      <div class="mt-4">
        <a href="index.php" class="btn btn-primary btn-lg w-100 rounded-3 py-3 fw-bold shadow text-decoration-none">
          กลับสู่หน้าหลัก
        </a>
      </div>
    </div>
  </div>
</div>

<?php
require_once 'footer.php';
?>
