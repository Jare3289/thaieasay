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

  <!-- Navbar ส่วนหัวหลักของสถาบัน (น้ำเงินกรมท่า-หลวงเป็นทางการ) -->
  <nav class="navbar navbar-dark navbar-academic sticky-top shadow">
    <div class="container py-1">
      <a class="navbar-brand fw-bold fs-5 d-flex align-items-center gap-2" href="index.php">
        <span>📝</span> ระบบประเมินเรียงความอัจฉริยะ
      </a>
      <div id="navUserArea" class="<?php echo $sessionUser ? 'd-flex' : 'd-none'; ?> align-items-center gap-3">
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
