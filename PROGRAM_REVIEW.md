# รายงานตรวจสอบโครงสร้างและความซับซ้อนของโปรแกรม

ตรวจเมื่อ 2 กันยายน 2026 จากซอร์สโค้ด PHP และ JavaScript ที่ติดตามใน Git
ทั้งหมด 55 ไฟล์ (ประมาณ 36,817 บรรทัด) โดยตรวจ syntax, ขนาดไฟล์, ขนาดฟังก์ชัน,
เส้นทาง API, การเข้าถึงฐานข้อมูล และจุดที่นำข้อมูลผู้ใช้ไปสร้าง HTML

## สรุปสำหรับผู้ดูแล

โปรแกรมยังทำงานเป็นระบบเดียว (monolith) ที่เข้าใจได้จากหน้าจอแต่ละหน้า แต่มี 4 จุดที่
ซับซ้อนสูงและควรทยอยแก้ ไม่ควรรื้อทั้งระบบพร้อมกัน:

| ลำดับ | จุดที่พบ | ระดับ | เหตุผล |
|---|---|---|---|
| 1 | `api.php` | สูงมาก | 3,969 บรรทัด, 72 actions และตอบ JSON โดยตรงราว 250 จุด ทำให้สิทธิ์, validation และ transaction ไม่สม่ำเสมอ |
| 2 | `db_config.php` | สูง | การเปิดทุกหน้าจะโหลดไฟล์นี้ และไฟล์มีทั้งการเชื่อมต่อ, สร้างตาราง, ตรวจ schema และ migration แบบ runtime |
| 3 | `ai_config.php` / `chapter45_ai.php` | สูง | รวม config, prompt, HTTP client, parsing, scoring และ persistence ไว้ในไฟล์ใหญ่เดียว ฟังก์ชันสร้าง prompt บางตัวเกิน 290 บรรทัด |
| 4 | JavaScript ที่ฝังในหน้า PHP | กลาง-สูง | `research_analysis.php`, `dashboard.php`, `evaluation.php` และ `reflection_tools.php` รวม view, state, fetch, คำนวณ และ render ไว้ในไฟล์เดียว |

ไม่พบ syntax error ใน PHP หรือ JavaScript จากการตรวจรอบนี้ แต่ยังไม่มี test suite อัตโนมัติ
สำหรับ business flow; มีเพียง `verify_calculations.js` ที่ตรวจสูตรสถิติบางส่วน ดังนั้น
“ผ่าน syntax” ยังไม่เท่ากับ “ทุกกรณีใช้งานถูกต้อง”

## สิ่งที่แก้ทันทีในการตรวจรอบนี้

หน้าตรวจข้อมูลสะท้อนคิดรายบุคคลเคยนำข้อความที่นักเรียนกรอก (ปัญหา, วิธีแก้,
บันทึก, ชื่อและคำแนะนำจากเพื่อน) ต่อเข้า `innerHTML` โดยตรง ผู้กรอกจึงสามารถใส่
HTML ที่ทำงานใน browser ของครูได้ รอบนี้เพิ่ม `HtmlUtils.escapeHtml()` ที่ทดสอบแยกได้
และ escape ข้อมูลเหล่านั้นก่อน render โดยยังคง template HTML ของระบบตามเดิม
การ escape เกิดขึ้นเฉพาะตอนแสดงผล จึงไม่แก้ไขหรือลบข้อความต้นฉบับในฐานข้อมูล

## รายละเอียดจุดซับซ้อน

### 1. API เป็น “ไฟล์ศูนย์กลาง” ขนาดใหญ่

`api.php` รับทุกคำสั่งผ่าน switch เดียว ตั้งแต่ login, นักเรียน, แบบประเมิน,
งานสะท้อนคิด, เรียงความ, AI และบทที่ 4-5 ผลกระทบคือ:

- เปลี่ยนฟีเจอร์หนึ่งมีโอกาสกระทบฟีเจอร์อื่นและเกิด merge conflict สูง
- การตรวจ role เขียนซ้ำในแต่ละ case จึงมีโอกาสลืมตรวจสิทธิ์
- รูปแบบ error/status code และ validation ต่างกันในแต่ละ action
- ทดสอบแต่ละ action แยกจากฐานข้อมูลและ session ได้ยาก

**แนวทาง:** แยกทีละกลุ่มเป็น `api/auth.php`, `api/students.php`,
`api/reflections.php`, `api/essays.php`, `api/ai.php` และมี dispatcher บาง ๆ
ร่วมกับ helper `json_response()`, `require_api_role()` และ request validator กลาง
โดยเริ่มจากกลุ่ม reflection ซึ่งขนาดเล็กกว่ากลุ่ม AI

### 2. Migration ทำงานปะปนกับทุก request

`auth_helper.php` โหลด `db_config.php` ทุกครั้ง และ `db_config.php` มีคำสั่ง
`CREATE TABLE IF NOT EXISTS`, `SHOW TABLES`, `SHOW COLUMNS` และซ่อม index หลายชุด
แม้หลายคำสั่งจะ idempotent แต่ทำให้ request ปกติมี metadata query เพิ่ม,
ต้องให้บัญชีแอปมีสิทธิ์ DDL และ error หลายจุดถูกกลืนไว้จนวินิจฉัย production ยาก

**แนวทาง:** ย้าย DDL ไป migration command ที่รันตอน deploy, เก็บเฉพาะการสร้าง PDO
ใน `db_config.php`, บันทึก schema version และให้ startup ตรวจเพียง version เดียว

### 3. ชั้น AI มีหลายหน้าที่ในโมดูลเดียว

`ai_config.php` มีทั้ง provider settings, rubric, prompt generation, การเรียก HTTP,
การ parse/normalize ผล และ mapping แถวฐานข้อมูล ส่วน `chapter45_ai.php` ทำรูปแบบเดียวกัน
สำหรับรายงานอีกชุด การแก้ prompt จึงเสี่ยงแตะ logic คะแนนหรือ persistence โดยไม่ตั้งใจ

**แนวทาง:** แยก provider client, prompt builder, parser และ repository พร้อม fixture
ผลตอบกลับของ AI เพื่อทดสอบ parser โดยไม่เรียกเครือข่าย ก่อนแยกควรล็อกผลลัพธ์เดิมด้วย
characterization tests

### 4. หน้าใหญ่รวม presentation กับ business logic

`research_analysis.php` มีฟังก์ชันสร้างรายงาน HTML ขนาดหลายร้อยบรรทัดและคำนวณสถิติ
ใน browser ขณะที่ `dashboard.php` คำนวณและวาดกราฟในไฟล์เดียวกัน ทำให้สูตรเดียวกัน
มีโอกาสได้ผลต่างกันระหว่างหน้ารายงาน, dashboard และ PHP ฝั่ง server

**แนวทาง:** ย้ายสูตรที่เป็นแหล่งจริงหนึ่งเดียวไปโมดูลที่ทดสอบได้; แยก JavaScript
ออกเป็นไฟล์ตามหน้าที่ (`data`, `statistics`, `render`) และให้ PHP เหลือ markup/config

## ประเด็นความปลอดภัยที่ควรทำต่อก่อน refactor ใหญ่

1. **ระบบจำการเข้าสู่ระบบ:** `remember_user` เป็น JSON ที่ client แก้ได้และฝั่ง server
   เชื่อค่า `role/loginId` เพื่อสร้าง session ใหม่ ควรเปลี่ยนเป็น token สุ่มแบบ hash ใน DB,
   มีวันหมดอายุ/เพิกถอนได้ และตั้ง `Secure`, `HttpOnly`, `SameSite`
2. **ข้อมูลที่ render ด้วย `innerHTML`:** จุดที่แก้รอบนี้เป็นเพียงหน้าสะท้อนคิด ควรตรวจ
   sink อื่นทั้งหมดและใช้ `textContent` เป็นค่าเริ่มต้น หรือ escape ที่ boundary เดียว
3. **CSRF:** action ที่แก้ข้อมูลอาศัย session cookie แต่ไม่เห็น token กลาง ควรเพิ่ม
   CSRF token และจำกัด method ของ write action เป็น POST
4. **การยืนยันครู/ผู้เชี่ยวชาญ:** รหัสคงที่ในโค้ดไม่ใช่รหัสผ่านที่ปลอดภัย ควรใช้
   account store และ `password_hash()` / `password_verify()`

## แผนลดความซับซ้อนแบบไม่เสี่ยงรื้อระบบ

### ระยะ 1 — ป้องกันและวัดผล

- เพิ่ม integration tests สำหรับ login/role, บันทึกคะแนน, บันทึก reflection และ essay
- เพิ่ม fixture test สำหรับสูตรสถิติและ AI parser
- เพิ่ม JSON/authorization/CSRF helpers กลางโดยยังไม่ย้าย route

### ระยะ 2 — แยกส่วนที่เปลี่ยนบ่อย

- แยก API กลุ่ม reflection และ student management ก่อน
- ย้าย JavaScript ฝังหน้าเป็นไฟล์ภายนอกทีละหน้า
- ย้าย migration ออกจาก runtime และเพิ่มคำสั่ง deploy/health check

### ระยะ 3 — แยกโดเมนใหญ่

- แยก essay/AI/report services หลังมี characterization tests
- รวมสูตรสถิติให้มี implementation หลักและทดสอบค่าขอบเขต/ข้อมูลว่าง
- ตั้งเกณฑ์ review เช่นไฟล์ใหม่ไม่เกินประมาณ 500 บรรทัด และฟังก์ชันใหม่ไม่เกิน
  ประมาณ 50 บรรทัด เว้นแต่มีเหตุผลบันทึกไว้

## ขอบเขตของผลตรวจ

รอบนี้เป็น static review และ syntax check ใน environment ของ repository ยังไม่ได้ต่อ
MySQL จริง, เรียกผู้ให้บริการ AI/Google หรือทดสอบ flow ผ่าน browser ครบทุกบทบาท
จึงควรทดสอบ staging ด้วยข้อมูลสำรองก่อนเปลี่ยน authentication หรือ migration
