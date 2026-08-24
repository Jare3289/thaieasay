<?php if (isset($sessionUser) && $sessionUser): ?>
      </main><!-- /.app-content -->
    </div><!-- /.app-main -->
  </div><!-- /.app-shell -->
<?php else: ?>
    </div><!-- /.auth-content -->
  </div><!-- /.auth-shell -->
<?php endif; ?>

  <!-- โทสต์แจ้งเตือน (Custom UI Toast Alerts) -->
  <div class="toast-container-custom" id="toastContainer"></div>

  <!-- โหลด Bootstrap 5 JS Bundle ผ่าน CDN -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <!-- ลงทะเบียน Service Worker เพื่อรองรับการติดตั้งเป็นแอป (PWA) -->
  <!-- แถบแจ้งเตือนเมื่อมีเวอร์ชันใหม่ของแอปพร้อมใช้งาน -->
  <div id="pwaUpdateBar" style="display:none; position:fixed; left:0; right:0; bottom:0; z-index:2000; padding:10px 16px; background:#1e3a8a; color:#fff; text-align:center; font-size:0.9rem; box-shadow:0 -2px 10px rgba(0,0,0,.2);">
    มีเวอร์ชันใหม่ของแอปพร้อมใช้งาน
    <button type="button" onclick="location.reload()" style="margin-left:10px; border:none; border-radius:999px; padding:4px 16px; font-weight:700; background:#fff; color:#1e3a8a; cursor:pointer;">รีเฟรชตอนนี้</button>
  </div>
  <script>
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', function () {
        navigator.serviceWorker.register('sw.js').then(function (reg) {
          // ถ้ามี worker ใหม่รออยู่แล้วตั้งแต่ก่อนโหลดหน้านี้
          if (reg.waiting && navigator.serviceWorker.controller) {
            document.getElementById('pwaUpdateBar').style.display = 'block';
          }
          reg.addEventListener('updatefound', function () {
            var newWorker = reg.installing;
            if (!newWorker) return;
            newWorker.addEventListener('statechange', function () {
              // ติดตั้งเสร็จ + มี controller เดิมอยู่แล้ว = นี่คืออัปเดต ไม่ใช่การติดตั้งครั้งแรก
              if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                document.getElementById('pwaUpdateBar').style.display = 'block';
              }
            });
          });
        }).catch(function (err) {
          console.warn('SW register failed:', err);
        });
      });
    }
  </script>

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
