<?php
$host = "localhost";
$user = "root"; // ปกติของ XAMPP
$password = ""; // ปกติของ XAMPP เป็นค่าว่าง
$dbname = "qr_gen"; // เปลี่ยนเป็นชื่อฐานข้อมูลที่ใช้

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("เชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}
?>
