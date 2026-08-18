<?php
session_start();
include 'db.php';

// Nếu giỏ hàng trống thì chuyển về trang giỏ hàng
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit();
}

$error = '';
$success = '';

// Xử lý khi nhấn nút Xác nhận đặt hàng
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btn_order'])) {
    $ho_ten = trim($_POST['ho_ten']);
    $so_dien_thoai = trim($_POST['so_dien_thoai']);
    $dia_chi = trim($_POST['dia_chi']);
    $ghi_chu = trim($_POST['ghi_chu']);

    if (empty($ho_ten) || empty($so_dien_thoai) || empty($dia_chi)) {
        $error = "Vui lòng nhập đầy đủ các thông tin bắt buộc (*)!";
    } else {
        // 1. Tính tổng tiền đơn hàng
        $tong_tien = 0;
        foreach ($_SESSION['cart'] as $item) {
            if (is_array($item)) {
                $gia = intval($item['price']);
                $so_luong = intval($item['quantity']);
                $tong_tien += $gia * $so_luong;
            }
        }

        // 2. Lưu đơn hàng vào database
        $sql_order = "INSERT INTO don_hang (ho_ten, so_dien_thoai, dia_chi, ghi_chu, tong_tien, ngay_dat) 
                      VALUES ('$ho_ten', '$so_dien_thoai', '$dia_chi', '$ghi_chu', '$tong_tien', NOW())";
        
        if (mysqli_query($conn, $sql_order)) {
            $don_hang_id = mysqli_insert_id($conn);

            // 3. Lưu chi tiết từng sản phẩm
            foreach ($_SESSION['cart'] as $san_pham_id => $item) {
                if (is_array($item)) {
                    $ten_sp = mysqli_real_escape_string($conn, $item['name']);
                    $gia = intval($item['price']);
                    $so_luong = intval($item['quantity']);
                    
                    $sql_detail = "INSERT INTO chi_tiet_don_hang (don_hang_id, san_pham_id, ten_san_pham, gia, so_luong) 
                                   VALUES ('$don_hang_id', '$san_pham_id', '$ten_sp', '$gia', '$so_luong')";
                    mysqli_query($conn, $sql_detail);
                }
            }

            // 4. Xóa giỏ hàng sau khi đặt thành công
            unset($_SESSION['cart']);
            echo "<script>alert('Đặt hàng thành công!'); window.location.href='products.php';</script>";
            exit();
        } else {
            $error = "Có lỗi xảy ra khi tạo đơn hàng. Vui lòng thử lại!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Thanh Toán - Gấu Bông Store</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #fdf2f8; font-family: sans-serif; margin: 0; padding: 20px 10px; }
        
        .top-bar { max-width: 1000px; margin: 0 auto 15px; display: flex; justify-content: space-between; align-items: center; }
        .top-bar a { color: #9333EA; text-decoration: none; font-weight: bold; font-size: 14px; }
        
        .checkout-container {
            max-width: 1000px;
            margin: 0 auto;
            background: #fff;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            display: flex;
            gap: 30px;
        }

        .checkout-form { flex: 1.2; }
        .order-summary { flex: 0.8; background: #faf5ff; padding: 20px; border-radius: 12px; border: 1px solid #f3e8ff; height: fit-content; }

        .section-title { font-size: 16px; font-weight: bold; color: #7e22ce; margin-bottom: 15px; display: flex; align-items: center; gap: 8px; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 13px; font-weight: bold; margin-bottom: 6px; color: #333; }
        .form-group label span { color: #e11d48; }
        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            box-sizing: border-box;
            outline: none;
        }
        .form-control:focus { border-color: #a855f7; }
        textarea.form-control { resize: vertical; height: 80px; }

        .order-item { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px dashed #e9d5ff; }
        .order-item-name { font-weight: bold; font-size: 13px; color: #333; }
        .order-item-qty { font-size: 12px; color: #666; }
        .order-item-price { font-weight: bold; color: #d0011b; font-size: 13px; }

        .total-row { display: flex; justify-content: space-between; align-items: center; margin-top: 15px; font-weight: bold; font-size: 16px; }
        .total-price { color: #d0011b; font-size: 20px; }

        .btn-order {
            width: 100%;
            background: linear-gradient(90deg, #a855f7, #ec4899);
            color: #fff;
            border: none;
            padding: 12px 0;
            border-radius: 25px;
            font-weight: bold;
            font-size: 15px;
            cursor: pointer;
            margin-top: 15px;
        }
        .alert-error { background: #ffe4e6; color: #e11d48; padding: 10px; border-radius: 8px; font-size: 13px; margin-bottom: 15px; }

        /* Mobile Responsive */
        @media screen and (max-width: 768px) {
            .checkout-container { flex-direction: column-reverse; padding: 15px; gap: 20px; }
        }
    </style>
</head>
<body>

    <div class="top-bar">
        <a href="products.php" style="font-size: 18px;"><i class="fa-solid fa-store"></i> Gấu Bông Store</a>
        <a href="cart.php"><i class="fa-solid fa-arrow-left"></i> Quay lại giỏ hàng</a>
    </div>

    <div class="checkout-container">
        <!-- Cột nhập thông tin -->
        <div class="checkout-form">
            <div class="section-title"><i class="fa-solid fa-truck"></i> THÔNG TIN GIAO HÀNG</div>
            
            <?php if (!empty($error)): ?>
                <div class="alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form action="checkout.php" method="POST">
                <div class="form-group">
                    <label>Họ và tên <span>(*)</span></label>
                    <input type="text" name="ho_ten" class="form-control" placeholder="Nhập họ và tên..." required>
                </div>

                <div class="form-group">
                    <label>Số điện thoại <span>(*)</span></label>
                    <input type="tel" name="so_dien_thoai" class="form-control" placeholder="Nhập số điện thoại..." required>
                </div>

                <div class="form-group">
                    <label>Địa chỉ nhận hàng <span>(*)</span></label>
                    <textarea name="dia_chi" class="form-control" placeholder="Nhập địa chỉ chi tiết (Số nhà, đường, phường/xã, quận/huyện...)" required></textarea>
                </div>

                <div class="form-group">
                    <label>Ghi chú đơn hàng</label>
                    <textarea name="ghi_chu" class="form-control" placeholder="Nhập ghi chú thêm (nếu có)..."></textarea>
                </div>

                <button type="submit" name="btn_order" class="btn-order">
                    <i class="fa-solid fa-check"></i> XÁC NHẬN ĐẶT HÀNG
                </button>
            </form>
        </div>

        <!-- Cột tóm tắt đơn hàng -->
        <div class="order-summary">
            <div class="section-title"><i class="fa-solid fa-receipt"></i> ĐƠN HÀNG CỦA BẠN</div>
            
            <?php 
            $tong_tien = 0;
            if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])):
                foreach ($_SESSION['cart'] as $item):
                    if (!is_array($item)) continue; // Bỏ qua nếu dữ liệu không chuẩn
                    
                    $gia = intval($item['price']);
                    $so_luong = intval($item['quantity']);
                    $thanh_tien = $gia * $so_luong;
                    $tong_tien += $thanh_tien;
            ?>
                <div class="order-item">
                    <div>
                        <div class="order-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                        <div class="order-item-qty">x <?php echo $so_luong; ?></div>
                    </div>
                    <div class="order-item-price"><?php echo number_format($thanh_tien); ?> VNĐ</div>
                </div>
            <?php 
                endforeach;
            endif; 
            ?>

            <div class="total-row">
                <span>Tổng tiền:</span>
                <span class="total-price"><?php echo number_format($tong_tien); ?> VNĐ</span>
            </div>
        </div>
    </div>

</body>
</html>
