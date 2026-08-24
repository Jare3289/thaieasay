# คู่มือย้ายระบบ "ประเมินเรียงความ (easay)" ไปเซิร์ฟเวอร์ Plesk

คู่มือนี้พาย้ายเว็บจากโฮสต์ฟรี `easay.gt.tc` (InfinityFree) ไปยังเซิร์ฟเวอร์ Plesk
ที่เช่าไว้ ทำตามทีละขั้น ไม่ต้องเขียนโค้ดเพิ่ม

> **ทำไมต้องย้าย?** โฮสต์ฟรี gt.tc มีด่านตรวจ JavaScript กันบอทคั่นก่อนเข้าเว็บจริง
> มือถือหลายเครื่อง (เปิดผ่านแอป LINE/FB, โหมดประหยัดเน็ต, บล็อกคุกกี้) ผ่านด่านนี้ไม่ได้
> จึง "เข้าไม่ได้" ทั้งที่คอมเข้าได้ ย้ายมา Plesk แล้วปัญหานี้หายไปเลย

---

## ภาพรวมสิ่งที่ต้องทำ
1. สร้าง Subdomain ใน Plesk + เลือก PHP 8.x
2. สร้างฐานข้อมูล MySQL ใน Plesk
3. ย้ายข้อมูลเดิมออกจาก InfinityFree (ถ้าต้องการเก็บข้อมูลนักเรียน)
4. อัปโหลดไฟล์เว็บ
5. สร้างไฟล์ `db_secrets.php` ใส่รหัสฐานข้อมูล
6. Import โครงสร้าง/ข้อมูลฐานข้อมูล
7. เปิด SSL (Let's Encrypt)
8. อัปเดต Google OAuth (ถ้าใช้ฟีเจอร์ Google Drive/Docs)
9. ทดสอบ

---

## ขั้นที่ 1 — สร้าง Subdomain ใน Plesk
1. เข้า Plesk → **Websites & Domains** → **Add Subdomain**
2. ตั้งชื่อ เช่น `easay` (จะได้ `easay.โดเมนของคุณ`)
   - ถ้ามีโดเมนโรงเรียน แนะนำ `easay.chainatpit.ac.th` (ขอแอดมินชี้ DNS มาที่เซิร์ฟเวอร์นี้)
3. หลังสร้างเสร็จ ไปที่ **PHP Settings** ของ subdomain นั้น → เลือก **PHP 8.0 ขึ้นไป**

---

## ขั้นที่ 2 — สร้างฐานข้อมูล MySQL
1. Plesk → **Databases** → **Add Database**
2. ตั้งชื่อฐานข้อมูล เช่น `easay_db`
3. สร้าง **Database user** ใหม่ + ตั้งรหัสผ่านที่แข็งแรง (อย่าใช้รหัสเดิมของ InfinityFree)
4. **จดค่า 4 อย่างนี้ไว้** จะใช้ในขั้นที่ 5:
   - host (ปกติ `localhost`)
   - ชื่อฐานข้อมูล
   - ชื่อ user
   - รหัสผ่าน

---

## ขั้นที่ 3 — ย้ายข้อมูลเดิมออกจาก InfinityFree (ถ้าต้องการเก็บข้อมูล)
> ข้ามขั้นนี้ได้ถ้าจะเริ่มระบบใหม่โดยไม่เก็บข้อมูลเก่า

1. เข้า control panel ของ InfinityFree → เปิด **phpMyAdmin** ของฐานข้อมูลเดิม
2. เลือกฐานข้อมูล → แท็บ **Export** → เลือกแบบ **Custom**
3. เลือก format **SQL**, ติ๊ก **Add DROP TABLE** (ตามสะดวก), กด **Export**
4. จะได้ไฟล์ `.sql` เก็บไว้ในเครื่อง (เช่น `easay_backup.sql`) — ใช้ในขั้นที่ 6

---

## ขั้นที่ 4 — อัปโหลดไฟล์เว็บ
อัปโหลดไฟล์ทั้งหมดของโปรเจกต์เข้าไปที่ document root ของ subdomain
(ปกติคือ `.../easay.โดเมน/httpdocs/` หรือโฟลเดอร์ที่ Plesk กำหนด)

วิธีที่แนะนำ (เลือกทางใดทางหนึ่ง):
- **Git**: ถ้าเซิร์ฟเวอร์มี git → `git clone` repo นี้ลงไปได้เลย
- **File Manager / FTP**: อัปโหลดทุกไฟล์ `.php`, `index.css`, `sw.js`, `manifest.json`
  และโฟลเดอร์ `icons/`

> ไม่ต้องอัปโหลด `db_secrets.php` (ยังไม่มี จะสร้างในขั้นถัดไป)
> และ `google_secrets.php` (สร้างเฉพาะถ้าใช้ Google)

---

## ขั้นที่ 5 — ตั้งค่ารหัสฐานข้อมูล (สำคัญ)
1. ในโฟลเดอร์เว็บ **คัดลอก** `db_secrets.sample.php` → เปลี่ยนชื่อเป็น `db_secrets.php`
2. เปิด `db_secrets.php` แก้ค่าให้ตรงกับที่จดไว้ในขั้นที่ 2:
   ```php
   <?php
   $db_host = 'localhost';
   $db_name = 'easay_db';        // ชื่อจริงจาก Plesk
   $db_user = 'easay_user';      // user จริงจาก Plesk
   $db_pass = 'รหัสผ่านจริง';
   ```
3. บันทึกไฟล์

> โค้ดจะอ่านรหัสจากไฟล์นี้อัตโนมัติ ไฟล์นี้ไม่ถูกอัปขึ้น git (อยู่ใน .gitignore)
> ถ้ายังไม่สร้างไฟล์นี้ เปิดเว็บจะขึ้นข้อความ "ยังไม่ได้ตั้งค่าฐานข้อมูล"

---

## ขั้นที่ 6 — สร้างตาราง / นำเข้าข้อมูล
เปิด **phpMyAdmin** ใน Plesk → เลือกฐานข้อมูล `easay_db` → แท็บ **Import**

**กรณี A: เริ่มระบบใหม่ (ไม่เก็บข้อมูลเก่า)** — import ตามลำดับ:
1. `schema.sql`
2. `migration_essay_split.sql`
3. `migration_reflection_units.sql`

**กรณี B: ย้ายข้อมูลเดิมมาด้วย** — import ไฟล์ที่ export จากขั้นที่ 3:
1. `easay_backup.sql` (มีทั้งโครงสร้าง+ข้อมูลอยู่แล้ว)
2. จากนั้น import migration ที่ยังไม่มี (ถ้า backup เก่ากว่าโครงสร้างล่าสุด):
   `migration_essay_split.sql`, `migration_reflection_units.sql`
   - ถ้า import แล้วขึ้น error ว่าคอลัมน์/ตารางมีอยู่แล้ว = ปกติ ข้ามได้

---

## ขั้นที่ 7 — เปิด SSL (https)
1. Plesk → เลือก subdomain → **SSL/TLS Certificates**
2. กด **Install** ที่ **Let's Encrypt** (ฟรี) → ใส่อีเมล → Get it free
3. เปิด **Redirect from HTTP to HTTPS** ให้ด้วย

> ระบบตรวจ https อัตโนมัติ (`google_config.php`) ไม่ต้องแก้โค้ด

---

## ขั้นที่ 8 — อัปเดต Google OAuth (เฉพาะถ้าใช้ Google Drive/Docs)
ถ้าไม่ได้ใช้ฟีเจอร์เชื่อม Google ข้ามขั้นนี้ได้

1. เข้า https://console.cloud.google.com/ → โปรเจกต์เดิม → **APIs & Services → Credentials**
2. เปิด OAuth client เดิม → เพิ่ม **Authorized redirect URI** ใหม่:
   ```
   https://easay.โดเมนของคุณ/google_auth.php?action=callback
   ```
   (ดูค่าที่ถูกต้องได้จากหน้า `google_auth.php?action=status` บนเว็บใหม่)
3. บนเซิร์ฟเวอร์ สร้างไฟล์ `google_secrets.php` (คัดจากที่ตั้งไว้เดิม) ใส่ Client ID/Secret:
   ```php
   <?php
   define('GOOGLE_CLIENT_ID',     'xxxx.apps.googleusercontent.com');
   define('GOOGLE_CLIENT_SECRET', 'xxxx');
   ```

---

## ขั้นที่ 9 — ทดสอบ
1. เปิด `https://easay.โดเมนของคุณ/` — ควรเข้าหน้าแรกได้ทันที **ไม่มีหน้าโหลด/ด่าน JS**
2. ทดสอบด้วย **มือถือจริง** (เปิดผ่าน Chrome/Safari) — ต้องเข้าได้แล้ว ✅
3. ลอง login / ดู dashboard / เปิดเครื่องมือประเมิน ว่าต่อฐานข้อมูลได้
4. ถ้าใช้ Google — ลองเชื่อมบัญชีที่ `google_auth.php?action=status`

---

## แก้ปัญหาที่พบบ่อย
| อาการ | สาเหตุ / วิธีแก้ |
|---|---|
| ขึ้น "ยังไม่ได้ตั้งค่าฐานข้อมูล" | ยังไม่มี `db_secrets.php` หรือกรอกค่าไม่ครบ → ทำขั้นที่ 5 |
| "ระบบเชื่อมต่อฐานข้อมูลไม่ได้ชั่วคราว" | ค่าใน `db_secrets.php` ผิด (user/pass/ชื่อ DB) → ตรวจกับ Plesk |
| หน้าเว็บภาษาไทยเพี้ยน | ฐานข้อมูลไม่ใช่ utf8mb4 → ตอน import ให้ใช้ collation `utf8mb4_unicode_ci` |
| เปิดแล้วเป็นหน้าดาวน์โหลดไฟล์ .php | subdomain ยังไม่รัน PHP → ตั้ง PHP handler ใน Plesk (ขั้นที่ 1) |
| Google เชื่อมไม่ได้ (redirect_uri_mismatch) | redirect URI ใน Google Console ไม่ตรงกับโดเมนใหม่ → ขั้นที่ 8 |

---

## หมายเหตุความปลอดภัย
- รหัสฐานข้อมูลย้ายไปอยู่ใน `db_secrets.php` แล้ว (ไม่อยู่ใน git) — ถ้าเคยใช้รหัสเดิมของ
  InfinityFree ที่โผล่ในโค้ดเก่า ควร **เปลี่ยนรหัสใหม่ทั้งหมด** บนเซิร์ฟเวอร์ใหม่
- หลังย้ายเสร็จและใช้งานได้แน่นอนแล้ว ค่อยปิด/ลบเว็บเดิมบน gt.tc
