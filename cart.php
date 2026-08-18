<?php
session_start();
include 'db.php';

// Xử lý xóa sản phẩm khỏi giỏ hàng
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    unset($_SESSION['cart'][$id]);
    header('Location: cart.php');
    exit();
}

// Tính tổng số lượng hiển thị trên Header
$cart_count = 0;
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $cart_count = array_sum($_SESSION['cart']);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ Hàng - Gấu Bông Store</title>
    <!-- External CSS & Font Awesome -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            background-color: #FFF5F7;
            font-family: 'Montserrat', Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .cart-wrapper {
            background: #ffffff;
            border-radius: 16px;
            padding: 30px;
            margin-top: 30px;
            margin-bottom: 50px;
            box-shadow: 0 4px 20px rgba(244, 114, 182, 0.15);
            border: 1px solid #FCE7F3;
        }
        .cart-title {
            font-size: 22px;
            color: #9333EA;
            margin-bottom: 25px;
            border-bottom: 2px solid #F3E8FF;
            padding-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .cart-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            table-layout: fixed;
        }
        .cart-table th {
            background-color: #F3E8FF;
            color: #6B21A8;
            padding: 14px 10px;
            font-size: 15px;
            text-align: center;
            border: 1px solid #E9D5FF;
        }
        .cart-table td {
            padding: 12px 10px;
            border: 1px solid #FCE7F3;
            vertical-align: middle;
            text-align: center;
            font-size: 14px;
        }
        .cart-img-thumb {
            width: 70px !important;
            height: 70px !important;
            object-fit: cover !important;
            border-radius: 8px;
            border: 1px solid #FCE7F3;
            flex-shrink: 0;
        }
        .product-cell {
            display: flex;
            align-items: center;
            gap: 12px;
            text-align: left;
        }
        .product-title-text {
            font-weight: 600;
            color: #374151;
            line-height: 1.3;
        }
        .subtotal-text {
            color: #EC4899;
            font-weight: 700;
        }
        .btn-delete-item {
            color: #EF4444;
            background: #FEE2E2;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.2s;
        }
        .btn-delete-item:hover {
            background: #EF4444;
            color: #ffffff;
        }
        .cart-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 2px solid #F3E8FF;
            padding-top: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .total-price-box {
            font-size: 18px;
            color: #4B5563;
        }
        .total-price-number {
            color: #EC4899;
            font-size: 24px;
            font-weight: 800;
            margin-left: 8px;
        }
        .btn-checkout-action {
            background: linear-gradient(135deg, #A855F7, #EC4899);
            color: #ffffff !important;
            padding: 12px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 700;
            font-size: 15px;
            box-shadow: 0 4px 12px rgba(236, 72, 153, 0.3);
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-checkout-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(236, 72, 153, 0.4);
        }
        .empty-cart-box {
            text-align: center;
            padding: 50px 0;
        }
    </style>
</head>
<body>

    <!-- Header Top -->
    <header>
        <div class="container header-content">
            <a href="index.php" class="logo">
                <i class="fa-solid fa-store"></i> Gấu Bông Store
            </a>
            
            <a href="index.php" style="color: #9333EA; text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 6px;">
                <i class="fa-solid fa-arrow-left"></i> Tiếp tục mua hàng
            </a>
        </div>
    </header>

    <!-- Main Cart Content -->
    <div class="container">
        <div class="cart-wrapper">
            <h2 class="cart-title">
                <i class="fa-solid fa-cart-shopping"></i> GIỎ HÀNG CỦA BẠN
            </h2>

            <?php if (!empty($_SESSION['cart'])): ?>
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th style="width: 40%;">Sản phẩm</th>
                            <th style="width: 15%;">Giá</th>
                            <th style="width: 12%;">Số lượng</th>
                            <th style="width: 18%;">Tổng tiền</th>
                            <th style="width: 15%;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_all = 0;
                        $ids = implode(',', array_keys($_SESSION['cart']));
                        $sql = "SELECT * FROM san_pham WHERE id IN ($ids)";
                        $result = mysqli_query($conn, $sql);

                        while ($row = mysqli_fetch_assoc($result)):
                            $qty = $_SESSION['cart'][$row['id']];
                            $subtotal = $row['gia'] * $qty;
                            $total_all += $subtotal;
                        ?>
                            <tr>
                                <td>
                                    <div class="product-cell">
                                        <img src="images/<?php echo htmlspecialchars($row['hinh_anh']); ?>" class="cart-img-thumb" alt="product">
                                        <span class="product-title-text"><?php echo htmlspecialchars($row['ten_san_pham']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo number_format($row['gia']); ?> VNĐ</td>
                                <td><strong><?php echo $qty; ?></strong></td>
                                <td class="subtotal-text"><?php echo number_format($subtotal); ?> VNĐ</td>
                                <td>
                                    <a href="cart.php?action=delete&id=<?php echo $row['id']; ?>" class="btn-delete-item" onclick="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này?')">
                                        <i class="fa-solid fa-trash-can"></i> Xóa
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <div class="cart-footer">
                    <div class="total-price-box">
                        Tổng tiền thanh toán: <span class="total-price-number"><?php echo number_format($total_all); ?> VNĐ</span>
                    </div>
                    <a href="checkout.php" class="btn-checkout-action">
                        Đến trang thanh toán <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>

            <?php else: ?>
                <div class="empty-cart-box">
                    <i class="fa-solid fa-cart-flatbed-empty" style="font-size: 60px; color: #CBD5E1; margin-bottom: 15px;"></i>
                    <p style="font-size: 16px; color: #64748B; margin-bottom: 20px;">Giỏ hàng của bạn đang trống!</p>
                    <a href="index.php" class="btn-checkout-action">
                        <i class="fa-solid fa-bag-shopping"></i> Mua sắm ngay
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
