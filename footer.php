  </div>

  <!-- โทสต์แจ้งเตือน (Custom UI Toast Alerts) -->
  <div class="toast-container-custom" id="toastContainer"></div>

  <!-- โหลด Bootstrap 5 JS Bundle ผ่าน CDN -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    // ข้อมูลผู้ใช้งานที่ล็อกอินปัจจุบัน (แชร์ค่าผ่าน PHP session)
    let currentUser = <?php echo isset($_SESSION['user']) ? json_encode($_SESSION['user']) : 'null'; ?>;
    
    // แสดง Toast แจ้งเตือนความคืบหน้า
    function showToast(message, type = 'success') {
      const container = document.getElementById('toastContainer');
      if (!container) return;
      const toastId = 'toast_' + Date.now();
      
      const themeClass = type === 'error' ? 'bg-danger text-white' : 'bg-success text-white';
      const icon = type === 'error' ? '<i class="bi bi-x-circle-fill"></i>' : '<i class="bi bi-check-circle-fill"></i>';

      const toastHTML = `
        <div id="${toastId}" class="toast align-items-center ${themeClass} border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3000">
          <div class="d-flex">
            <div class="toast-body d-flex align-items-center gap-2">
              ${icon} <span>${message}</span>
            </div>
            <button type="button" class="btn-close btn-close-white m-auto me-2" data-bs-dismiss="toast" aria-label="Close"></button>
          </div>
        </div>
      `;
      
      container.insertAdjacentHTML('beforeend', toastHTML);
      const toastEl = document.getElementById(toastId);
      
      const bsToast = new bootstrap.Toast(toastEl);
      bsToast.show();
      
      toastEl.addEventListener('hidden.bs.toast', () => {
        toastEl.remove();
      });
    }

    // ออกจากระบบ
    async function logout() {
      try {
        await fetch('api.php?action=logout');
      } catch (err) {
        console.error(err);
      }
      // ลบข้อมูลจดจำสิทธิ์การล็อกอิน
      localStorage.removeItem('remember_user');
      currentUser = null;
      window.location.href = 'login.php';
    }

    // ระบบ Client-side Auto-login fallback สำหรับกรณี Session หลุดบน Mobile
    (async function checkClientAutoLogin() {
      const isLoginPage = window.location.pathname.endsWith('login.php');
      
      if (!currentUser) {
        // หากไม่มี session ตรวจสอบว่ามีข้อมูล Remember Me ใน localStorage หรือไม่
        const savedUser = localStorage.getItem('remember_user');
        if (savedUser) {
          try {
            const userObj = JSON.parse(savedUser);
            if (userObj && userObj.role && userObj.loginId) {
              // ทำการล็อกอินเงียบผ่าน API
              const response = await fetch('api.php?action=check_login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                  role: userObj.role, 
                  loginId: userObj.loginId,
                  remember: true 
                })
              });
              const res = await response.json();
              if (res.success) {
                // บันทึกสำเร็จ ให้ทำการโหลดหน้านี้ใหม่เพื่อให้ PHP session มีผลทำงานต่อ
                window.location.reload();
                return;
              } else {
                localStorage.removeItem('remember_user');
              }
            }
          } catch (err) {
            console.error("Auto login failed:", err);
            localStorage.removeItem('remember_user');
          }
        }
        
        // หากไม่มีประวัติล็อกอิน และไม่ได้อยู่ที่หน้าล็อกอิน ให้บังคับไปที่หน้าล็อกอิน
        if (!isLoginPage) {
          window.location.href = 'login.php';
        }
      } else {
        // หากมี session ล็อกอินอยู่แล้ว แต่อยู่ที่หน้า login.php ให้พาไปที่หน้าเมนูหลัก
        if (isLoginPage) {
          window.location.href = 'index.php';
        }
      }
    })();
  </script>
</body>
</html>
