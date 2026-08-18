<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_shop_gau_bong"; 

// Khởi tạo kết nối
$conn = mysqli_connect($host, $user, $pass, $db);

// Kiểm tra kết nối
if (!$conn) {
    die("Kết nối MySQL thất bại: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8mb4");
?>
