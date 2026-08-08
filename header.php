<?php
// header.php
require_once 'auth_helper.php';
$sessionUser = isset($_SESSION['user']) ? $_SESSION['user'] : null;
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo isset($page_title) ? $page_title : 'ระบบประเมินความสามารถในการเขียนเรียงความ'; ?></title>
  
  <!-- โหลด Bootstrap 5 (CSS) และ Bootstrap Icons ผ่าน CDN -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  
  <!-- โหลดฟอนต์ Google Sans -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Google+Sans:ital,opsz,wght@0,17..18,400..700;1,17..18,400..700&display=swap" rel="stylesheet">
  
  <!-- โหลด Chart.js สำหรับการสร้างกราฟเชิงลึกรายด้าน -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  
  <!-- โหลดไฟล์ CSS สไตล์หลัก -->
  <link href="index.css?v=1.0" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">

  <!-- ตัวจัดการกลุ่มการวิจัยแบบใช้ร่วมกันทุกหน้าครู (จำค่าที่เลือกไว้ข้ามหน้าไว้ที่ localStorage) -->
  <script>
    window.TEG = (function () {
      var KEY = 'thaieasay_teacher_group';
      var DEFAULT_GROUP = 'กลุ่มตัวอย่าง'; // ค่าเริ่มต้นของทั้งระบบตามที่กำหนด
      var GROUPS = ['กลุ่มทดลอง', 'กลุ่มตัวอย่าง']; // กลุ่มจริงที่มีในระบบ ('all' = ทุกกลุ่มรวมกัน)
      function get() {
        try {
          var v = localStorage.getItem(KEY);
          if (v === null || v === undefined || v === '') return DEFAULT_GROUP;
          return v;
        } catch (e) { return DEFAULT_GROUP; }
      }
      function set(v) {
        try { localStorage.setItem(KEY, (v === null || v === undefined) ? 'all' : v); } catch (e) {}
      }
      // แปลงค่าที่เก็บ → ค่าที่หน้าเว็บใช้เป็น "ตัวกรองกลุ่ม" (all → '' หมายถึงไม่กรอง)
      function filterValue() { var g = get(); return (g === 'all') ? '' : g; }
      // พารามิเตอร์ต่อ URL สำหรับ API (ส่งเฉพาะเมื่อเจาะจงกลุ่ม)
      function param() { var g = filterValue(); return g ? ('&group=' + encodeURIComponent(g)) : ''; }
      return { get: get, set: set, filterValue: filterValue, param: param, KEY: KEY, DEFAULT_GROUP: DEFAULT_GROUP, GROUPS: GROUPS };
    })();
  </script>


  <!-- Navbar ส่วนหัวหลักของสถาบัน (น้ำเงินกรมท่า-หลวงเป็นทางการ) -->
  <nav class="navbar navbar-dark navbar-academic sticky-top shadow">
    <div class="container py-1">
      <a class="navbar-brand fw-bold fs-5 d-flex align-items-center gap-2" href="index.php">
        <span>📝</span> ระบบประเมินเรียงความอัจฉริยะ
      </a>
      <div id="navUserArea" class="<?php echo $sessionUser ? 'd-flex' : 'd-none'; ?> align-items-center gap-3">
        <?php if ($sessionUser && in_array($sessionUser['role'], ['teacher', 'expert'])): ?>
        <a href="essay_viewer.php" class="btn btn-light btn-sm fw-bold px-3 d-flex align-items-center gap-1" title="ดูเรียงความนักเรียนทุกคน แยกกลุ่มทดลอง/กลุ่มตัวอย่าง">
          <i class="bi bi-pencil-square"></i> <span class="d-none d-md-inline">เรียงความนักเรียน</span>
        </a>
        <?php endif; ?>
        <div class="bg-white bg-opacity-10 text-white border border-white border-opacity-10 px-3 py-1.5 rounded text-sm">
          <span class="text-white-50">ผู้ใช้ระบบ:</span> <span id="navUserName" class="fw-bold"><?php echo $sessionUser ? htmlspecialchars($sessionUser['name']) : ''; ?></span>
        </div>
        <button onclick="logout()" class="btn btn-secondary-custom btn-sm fw-bold px-3">
          ออกจากระบบ
        </button>
      </div>
    </div>
  </nav>

  <div class="container my-4 flex-grow-1">
