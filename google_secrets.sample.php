<?php
/**
 * google_secrets.sample.php — ตัวอย่างไฟล์เก็บค่า Client ID/Secret ของ Google API
 * ---------------------------------------------------------------------------
 * ใช้เมื่อต้องการเปิดฟีเจอร์ "ส่งรายงานเข้า Google Docs โดยตรง"
 * (หน้าวิเคราะห์สถิติงานวิจัย, หน้าตรวจเรียงความอัตโนมัติ, หน้าวิเคราะห์บทที่ 4-5)
 *
 * วิธีใช้:
 *   1. เข้า https://console.cloud.google.com/ → สร้างโปรเจกต์ใหม่ (หรือใช้โปรเจกต์เดิม)
 *   2. เมนู "APIs & Services" → "Enabled APIs & services" → เปิดใช้ "Google Drive API"
 *   3. เมนู "OAuth consent screen" → เลือก External → กรอกข้อมูลแอป → เพิ่มอีเมลคุณครูใน Test users
 *   4. เมนู "Credentials" → Create Credentials → OAuth client ID → ประเภท "Web application"
 *      - Authorized redirect URIs: ใส่ค่าที่แสดงในหน้า google_auth.php?action=status
 *        โดยปกติคือ  https://<โดเมนของคุณ>/google_auth.php?action=callback
 *   5. คัดลอกไฟล์นี้เป็นชื่อ  google_secrets.php  (โฟลเดอร์เดียวกับ google_config.php)
 *   6. แก้ค่า Client ID/Secret ด้านล่างให้ตรงกับที่ได้จาก Google Cloud Console
 *   7. ไฟล์ google_secrets.php อยู่ใน .gitignore แล้ว จึงไม่ถูกอัปขึ้น git
 *
 * ถ้าไม่สร้างไฟล์นี้ ระบบจะแจ้งว่า "ยังไม่ได้ตั้งค่า Google API" และปุ่มส่งเข้า Google Docs จะใช้งานไม่ได้
 */

define('GOOGLE_CLIENT_ID',     'ใส่ Client ID ของคุณที่นี่ เช่น xxxx.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', 'ใส่ Client Secret ของคุณที่นี่');
