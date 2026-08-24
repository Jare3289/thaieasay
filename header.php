<?php
// header.php
require_once 'auth_helper.php';
$sessionUser = isset($_SESSION['user']) ? $_SESSION['user'] : null;

// ---------------------------------------------------------------------------
// สร้างเมนูแถบข้าง (Sidebar) ตามบทบาทผู้ใช้ — โครงเดียวใช้ทั้งระบบ
// ---------------------------------------------------------------------------
$__self = basename($_SERVER['PHP_SELF']);
$__mode = isset($_GET['mode']) ? $_GET['mode'] : '';

/**
 * โครงสร้างเมนู: แต่ละรายการ = [ป้ายชื่อ, ลิงก์, ไอคอน bootstrap, key ไว้เช็ค active]
 * จัดกลุ่มเป็น section: 'main' (เมนูหลัก) และ 'general' (ทั่วไป)
 */
function teg_menu_for_role($role) {
  if ($role === 'student') {
    return [
      'main' => [
        ['หน้าหลัก',          'index.php',                 'bi-grid-1x2-fill',      'index'],
        ['บันทึกเรียงความ',    'essay_writer.php',          'bi-pencil-square',      'essay_writer'],
        ['ประเมินตนเอง',      'evaluation.php?mode=self',  'bi-person-check-fill',  'evaluation:self'],
        ['จับคู่เพื่อน',       'peer_matching.php',         'bi-people-fill',        'peer_matching'],
        ['ประเมินเพื่อน',      'evaluation.php?mode=peer',  'bi-clipboard-check',    'evaluation:peer'],
        ['สะท้อนคิด',         'reflection_tools.php',      'bi-lightbulb-fill',     'reflection_tools'],
      ],
      'general' => [
        ['รายงานของฉัน',      'dashboard.php',             'bi-pie-chart-fill',     'dashboard'],
      ],
    ];
  }
  if ($role === 'expert') {
    return [
      'main' => [
        ['หน้าหลัก',            'index.php',                   'bi-grid-1x2-fill',   'index'],
        ['ประเมินผลงาน',       'evaluation.php?mode=expert',  'bi-clipboard-check', 'evaluation:expert'],
        ['เรียงความนักเรียน',   'essay_viewer.php',            'bi-journal-text',    'essay_viewer'],
      ],
      'general' => [],
    ];
  }
  // teacher (ค่าเริ่มต้น)
  return [
    'main' => [
      ['แดชบอร์ดหลัก',        'index.php',                    'bi-grid-1x2-fill',      'index'],
      ['ประเมินให้คะแนน',     'evaluation.php?mode=teacher',  'bi-clipboard-check',    'evaluation:teacher'],
      ['แดชบอร์ดวิจัย',       'dashboard.php',                'bi-bar-chart-line-fill','dashboard'],
      ['รายงานการส่งงาน',     'submission_report.php',        'bi-table',              'submission_report'],
      ['เรียงความนักเรียน',   'essay_viewer.php',             'bi-journal-text',       'essay_viewer'],
    ],
    'general' => [
      ['รายงานสะท้อนคิด',      'reflection_tools.php',         'bi-lightbulb-fill',     'reflection_tools'],
      ['นักเรียน & จับคู่',    'manage_students.php',          'bi-person-lines-fill',  'manage_students'],
      ['วิเคราะห์สถิติวิจัย',  'research_analysis.php',        'bi-graph-up-arrow',     'research_analysis'],
    ],
  ];
}

// เช็คว่ารายการเมนูตรงกับหน้าปัจจุบันหรือไม่ (รองรับ key แบบ page:mode)
function teg_is_active($key, $self, $mode) {
  if (strpos($key, ':') !== false) {
    list($p, $m) = explode(':', $key, 2);
    return ($self === $p . '.php') && ($mode === $m);
  }
  return $self === $key . '.php';
}

// หาป้ายชื่อหน้าปัจจุบัน ไว้แสดงบนแถบบน (Topbar)
function teg_active_label($menu, $self, $mode) {
  foreach ($menu as $section) {
    foreach ($section as $it) {
      if (teg_is_active($it[3], $self, $mode)) return $it[0];
    }
  }
  return '';
}

$__menu = $sessionUser ? teg_menu_for_role($sessionUser['role']) : ['main' => [], 'general' => []];
$__activeLabel = $sessionUser ? teg_active_label($__menu, $__self, $__mode) : '';
if ($__activeLabel === '') $__activeLabel = 'ระบบประเมินเรียงความ';

$__roleLabel = 'ผู้ใช้ระบบ';
if ($sessionUser) {
  $__roleLabel = ($sessionUser['role'] === 'teacher') ? 'ครูผู้สอน'
    : (($sessionUser['role'] === 'expert') ? 'ผู้เชี่ยวชาญ' : 'นักเรียน');
}
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
  <link href="index.css?v=2.0" rel="stylesheet">

  <!-- ========== Progressive Web App (PWA) — ไอคอนดินสอสีน้ำเงิน ========== -->
  <link rel="manifest" href="manifest.json">
  <meta name="theme-color" content="#2563eb">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <meta name="apple-mobile-web-app-title" content="ประเมินเรียงความ">
  <link rel="icon" type="image/png" sizes="32x32" href="icon.php?f=favicon-32.png">
  <link rel="icon" type="image/png" sizes="192x192" href="icon.php?f=icon-192.png">
  <link rel="apple-touch-icon" href="icon.php?f=icon-180.png">
</head>
<body class="<?php echo $sessionUser ? 'has-sidebar' : 'auth-body'; ?>">

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

    // ทาสีปุ่มกลุ่มบน topbar ให้ตรงกับกลุ่มที่เลือกอยู่ (ปุ่มที่ active = พื้นทึบ)
    function tegPaintNavButtons() {
      var cur = window.TEG ? TEG.get() : 'all';
      document.querySelectorAll('#navGroupFilter [data-group]').forEach(function (b) {
        var on = (b.getAttribute('data-group') === cur);
        b.classList.toggle('active', on);
      });
    }

    // กดปุ่มเลือกกลุ่มบน topbar → จำค่า + สั่งหน้าปัจจุบันวาดใหม่ (ถ้าไม่มี hook ให้รีเฟรช)
    function tegNavSelect(btn) {
      var g = btn.getAttribute('data-group') || 'all';
      if (window.TEG) TEG.set(g);
      tegPaintNavButtons();
      if (typeof window.onTEGChange === 'function') {
        window.onTEGChange();
      } else {
        location.reload();
      }
    }

    // ปุ่มเมนู: จอใหญ่ = ยุบ/ขยายแถบข้างถาวร (จำค่าไว้ให้คืนพื้นที่หน้าจอ) · จอเล็ก = เปิด/ปิดแบบชั่วคราว
    var SIDEBAR_COLLAPSE_KEY = 'thaieasay_sidebar_collapsed';
    function tegIsDesktop() {
      return window.matchMedia('(min-width: 992px)').matches;
    }
    function tegToggleSidebar() {
      if (tegIsDesktop()) {
        var collapsed = document.body.classList.toggle('sidebar-collapsed');
        try { localStorage.setItem(SIDEBAR_COLLAPSE_KEY, collapsed ? '1' : '0'); } catch (e) {}
      } else {
        document.body.classList.toggle('sidebar-open');
      }
    }
    function tegCloseSidebar() {
      document.body.classList.remove('sidebar-open');
    }
    // คืนค่าสถานะยุบแถบข้าง (เฉพาะจอใหญ่) จากครั้งก่อน — ทำทันทีเพื่อลดการกระพริบ
    (function () {
      try {
        if (localStorage.getItem(SIDEBAR_COLLAPSE_KEY) === '1' && tegIsDesktop()) {
          document.body.classList.add('sidebar-collapsed');
        }
      } catch (e) {}
    })();

    document.addEventListener('DOMContentLoaded', tegPaintNavButtons);
  </script>

<?php if ($sessionUser): ?>
  <!-- ===================== เลย์เอาต์แอปพลิเคชันแบบแถบข้าง ===================== -->
  <div class="app-shell">

    <!-- ฉากหลังทึบเมื่อเปิดแถบข้างบนมือถือ -->
    <div class="sidebar-backdrop" onclick="tegCloseSidebar()"></div>

    <!-- แถบข้างซ้าย (Sidebar) -->
    <aside class="app-sidebar">
      <div class="sidebar-brand">
        <span class="sidebar-logo">📝</span>
        <span class="sidebar-brand-text">ระบบประเมิน<br><small>เรียงความ</small></span>
      </div>

      <nav class="sidebar-nav">
        <div class="sidebar-section-label">เมนูหลัก</div>
        <?php foreach ($__menu['main'] as $it): ?>
          <a href="<?php echo $it[1]; ?>" class="sidebar-link<?php echo teg_is_active($it[3], $__self, $__mode) ? ' active' : ''; ?>">
            <i class="bi <?php echo $it[2]; ?>"></i>
            <span><?php echo $it[0]; ?></span>
          </a>
        <?php endforeach; ?>

        <?php if (!empty($__menu['general'])): ?>
          <div class="sidebar-section-label mt-3">ทั่วไป</div>
          <?php foreach ($__menu['general'] as $it): ?>
            <a href="<?php echo $it[1]; ?>" class="sidebar-link<?php echo teg_is_active($it[3], $__self, $__mode) ? ' active' : ''; ?>">
              <i class="bi <?php echo $it[2]; ?>"></i>
              <span><?php echo $it[0]; ?></span>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </nav>

      <!-- การ์ดผู้ใช้ด้านล่างแถบข้าง -->
      <div class="sidebar-user">
        <div class="sidebar-user-avatar"><?php echo mb_substr($sessionUser['name'], 0, 1, 'UTF-8'); ?></div>
        <div class="sidebar-user-info">
          <div class="sidebar-user-name"><?php echo htmlspecialchars($sessionUser['name']); ?></div>
          <div class="sidebar-user-role"><?php echo $__roleLabel; ?></div>
        </div>
        <button onclick="logout()" class="sidebar-user-logout" title="ออกจากระบบ">
          <i class="bi bi-box-arrow-right"></i>
        </button>
      </div>
    </aside>

    <!-- พื้นที่เนื้อหาหลัก -->
    <div class="app-main">

      <!-- แถบบน (Topbar) -->
      <header class="app-topbar">
        <div class="topbar-left">
          <button class="topbar-menu-btn" onclick="tegToggleSidebar()" aria-label="ยุบ/ขยายแถบเมนู" title="ยุบ/ขยายแถบเมนู">
            <i class="bi bi-list"></i>
          </button>
          <div>
            <div class="topbar-title"><?php echo htmlspecialchars($__activeLabel); ?></div>
            <div class="topbar-subtitle">ระบบประเมินความสามารถในการเขียนเรียงความ</div>
          </div>
        </div>

        <div class="topbar-right">
          <?php if ($sessionUser['role'] === 'teacher'): ?>
          <!-- ตัวเลือกกลุ่มการวิจัยแบบปุ่ม (จุดควบคุมเดียวของทั้งระบบ ใช้ร่วมทุกหน้าครู) -->
          <div id="navGroupFilter" class="teg-group-filter" role="group" aria-label="เลือกกลุ่มการวิจัย" title="เลือกกลุ่มการวิจัย — ใช้ร่วมกันทุกหน้า">
            <button type="button" data-group="all" onclick="tegNavSelect(this)">ทุกกลุ่ม</button>
            <button type="button" data-group="กลุ่มทดลอง" onclick="tegNavSelect(this)">🧪 ทดลอง</button>
            <button type="button" data-group="กลุ่มตัวอย่าง" onclick="tegNavSelect(this)">📋 ตัวอย่าง</button>
          </div>
          <?php endif; ?>

          <div class="topbar-user">
            <div class="topbar-user-avatar"><?php echo mb_substr($sessionUser['name'], 0, 1, 'UTF-8'); ?></div>
            <div class="topbar-user-meta d-none d-sm-block">
              <div class="topbar-user-name" id="navUserName"><?php echo htmlspecialchars($sessionUser['name']); ?></div>
              <div class="topbar-user-role"><?php echo $__roleLabel; ?></div>
            </div>
          </div>
        </div>
      </header>

      <!-- เนื้อหาของแต่ละหน้า -->
      <main class="app-content">
<?php else: ?>
  <!-- ===================== เลย์เอาต์สำหรับหน้าที่ยังไม่ล็อกอิน ===================== -->
  <div class="auth-shell">
    <div class="auth-content container">
<?php endif; ?>
