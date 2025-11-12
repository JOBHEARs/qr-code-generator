<?php
session_start();
include 'db.php'; // รวมไฟล์เชื่อมต่อฐานข้อมูล

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับข้อมูลจากฟอร์ม
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // ตรวจสอบว่าข้อมูลมีอยู่หรือไม่
    if (empty($username) || empty($password)) {
        $_SESSION['error'] = 'กรุณากรอกข้อมูลให้ครบทุกช่อง!';
        header('Location: register.php');
        exit;
    }

    // ตรวจสอบชื่อผู้ใช้ซ้ำในฐานข้อมูล
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['error'] = 'ชื่อผู้ใช้นี้ถูกใช้งานแล้ว!';
        header('Location: register.php');
        exit;
    }

    // แฮชรหัสผ่าน
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // บันทึกข้อมูลผู้ใช้ใหม่ในฐานข้อมูล
    $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $username, $hashedPassword);

    if ($stmt->execute()) {
        $_SESSION['success'] = 'สมัครสมาชิกสำเร็จ! กรุณาล็อกอิน.';
        header('Location: login.php');
        exit;
    } else {
        $_SESSION['error'] = 'เกิดข้อผิดพลาดในการสมัครสมาชิก!';
        header('Location: register.php');
        exit;
    }

}
$conn->close();
?>
