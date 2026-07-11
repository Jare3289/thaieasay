<?php
require_once 'auth_helper.php';

// หากเข้าสู่ระบบอยู่แล้ว ให้ย้ายหน้าไปหน้าหลักทันที
if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$page_title = 'ลงชื่อเข้าใช้งาน - ระบบประเมินเรียงความอัจฉริยะ';
require_once 'header.php';
?>

<div class="row justify-content-center my-5">
  <div class="col-md-5 col-sm-12">
    <div class="card border-0 shadow-lg rounded-4 p-4 bg-white">
      <div class="text-center mb-4">
        <div class="display-4 mb-2">🔒</div>
        <h3 class="fw-bold text-dark">ลงชื่อเข้าใช้งาน</h3>
        <p class="text-muted small">ระบุบทบาทและรหัสประจำตัวเพื่อเข้าทำการประเมิน</p>
      </div>

      <form id="loginForm">
        <div class="mb-3">
          <label class="form-label fw-bold text-secondary">บทบาทผู้เข้าใช้งาน</label>
          <div class="row g-2">
            <div class="col-6">
              <input type="radio" name="loginRole" value="student" id="roleStudent" class="btn-check" checked onchange="toggleLoginType()">
              <label class="btn btn-outline-primary w-100 py-2.5 rounded-3 fw-bold" for="roleStudent">👨‍🎓 นักเรียน</label>
            </div>
            <div class="col-6">
              <input type="radio" name="loginRole" value="teacher" id="roleTeacher" class="btn-check" onchange="toggleLoginType()">
              <label class="btn btn-outline-success w-100 py-2.5 rounded-3 fw-bold" for="roleTeacher">👩‍🏫 ครูผู้สอน</label>
            </div>
          </div>
        </div>

        <div class="mb-3" id="loginIdContainer">
          <label id="loginIdLabel" for="loginId" class="form-label fw-bold text-secondary">รหัสประจำตัวนักเรียน</label>
          <input type="text" id="loginId" required class="form-control form-control-lg rounded-3 border-2" placeholder="ใส่รหัสประจำตัว 5 หลัก">
        </div>

        <div class="mb-4">
          <div class="form-check text-start">
            <input class="form-check-input" type="checkbox" id="rememberMe" checked>
            <label class="form-check-label small fw-semibold text-secondary" for="rememberMe">
              จดจำการเข้าสู่ระบบในเครื่องนี้ (Remember Me)
            </label>
          </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-3 rounded-3 fw-bold fs-6 shadow">
          เข้าสู่ระบบบริหารการเขียน
        </button>
      </form>
    </div>
  </div>
</div>

<script>
  // สลับการระบุประเภทช่องป้อนข้อมูล
  function toggleLoginType() {
    const isTeacher = document.querySelector('input[name="loginRole"]:checked').value === 'teacher';
    const label = document.getElementById('loginIdLabel');
    const input = document.getElementById('loginId');
    
    if (isTeacher) {
      label.textContent = "รหัสผ่านครูผู้สอน";
      input.placeholder = "ใส่รหัสผ่านเพื่อเข้าใช้งาน";
      input.type = "password";
    } else {
      label.textContent = "รหัสประจำตัวนักเรียน";
      input.placeholder = "ใส่รหัสประจำตัว 5 หลัก";
      input.type = "text";
    }
    input.value = "";
  }

  // ส่งฟอร์มและจัดการ Auto-login
  document.getElementById('loginForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const role = document.querySelector('input[name="loginRole"]:checked').value;
    const loginId = document.getElementById('loginId').value.trim();
    const remember = document.getElementById('rememberMe').checked;

    try {
      const response = await fetch('api.php?action=check_login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ role, loginId, remember })
      });
      const res = await response.json();
      
      if (res.success) {
        if (remember) {
          localStorage.setItem('remember_user', JSON.stringify({ role, loginId }));
        } else {
          localStorage.removeItem('remember_user');
        }
        showToast('เข้าสู่ระบบเสร็จสิ้นแล้ว', 'success');
        // รีไดเรกต์ไปหน้าหลัก
        setTimeout(() => {
          window.location.href = 'index.php';
        }, 500);
      } else {
        showToast(res.error, 'error');
      }
    } catch (err) {
      showToast('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์', 'error');
    }
  });
</script>

<?php
require_once 'footer.php';
?>
