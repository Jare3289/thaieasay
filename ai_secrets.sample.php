<?php
/**
 * ai_secrets.sample.php — ตัวอย่างไฟล์เก็บค่า API ของ AI
 * ---------------------------------------------------------------------------
 * ใช้เมื่อต้องการตั้งค่า AI จาก "ไฟล์บนเซิร์ฟเวอร์" แทนการกรอกในหน้าเว็บ
 * (ปลอดภัยกว่า เพราะคีย์ไม่ถูกเก็บในฐานข้อมูล)
 *
 * วิธีใช้:
 *   1. คัดลอกไฟล์นี้เป็นชื่อ  ai_secrets.php  (โฟลเดอร์เดียวกับ ai_config.php)
 *   2. แก้ค่าข้างล่างให้ตรงกับผู้ให้บริการที่เลือก
 *   3. ไฟล์ ai_secrets.php อยู่ใน .gitignore แล้ว จึงไม่ถูกอัปขึ้น git
 *
 * ถ้าไม่สร้างไฟล์นี้ ระบบจะใช้ค่าที่คุณครูกรอกในหน้า "ตั้งค่า AI" แทน
 * (ค่าจากไฟล์นี้มีลำดับความสำคัญเหนือกว่าค่าที่กรอกในเว็บเสมอ)
 *
 * ตัวเลือกที่ "มีโควตาให้ใช้ฟรี" — ดูรายละเอียดใน AI_SETUP.md
 *   gemini     : https://aistudio.google.com/apikey        โมเดล gemini-3.6-flash
 *   typhoon    : https://opentyphoon.ai/                   โมเดล typhoon-v2.1-12b-instruct
 *   openrouter : https://openrouter.ai/keys                โมเดลที่ลงท้ายด้วย :free
 *   groq       : https://console.groq.com/keys             โมเดล llama-3.3-70b-versatile
 */

$ai_provider = 'gemini';              // gemini | typhoon | openrouter | groq | custom
$ai_model    = 'gemini-3.6-flash';    // เว้นว่างไว้ = ใช้โมเดลเริ่มต้นของผู้ให้บริการ
$ai_api_key  = 'ใส่ API key ของคุณที่นี่';
$ai_base_url = '';                    // เว้นว่างไว้ = ใช้ค่าเริ่มต้น (ระบุเฉพาะกรณี provider = custom)
