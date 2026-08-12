<?php
// peer_pairing.php — รวมเข้ากับหน้า "จัดการนักเรียน & จับคู่ประเมิน" แล้ว
// คงไฟล์นี้ไว้เพื่อไม่ให้ลิงก์เดิมเสีย โดยพาไปที่แท็บจับคู่ของหน้าจัดการนักเรียน
require_once 'auth_helper.php';
require_login('teacher');
header('Location: manage_students.php#pane-pair');
exit;
