<?php
session_start();
include 'db.php';

// --- XỬ LÝ LOGIC GIỎ HÀNG ---
$action = isset($_GET['action']) ? $_GET['action'] : '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 1. Thêm sản phẩm vào giỏ
if ($action == 'add' && $id > 0) {
    $sql = "SELECT * FROM san_pham WHERE id = $id";
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $product = mysqli_fetch_assoc($result);
        
        // Xác định giá bán (Ưu tiên giá khuyến mãi nếu có)
        $price = ($product['gia_khuyen_mai'] > 0) ? $product['gia_khuyen_mai'] : $product['gia'];
        
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = array();
        }

        // Kiểm tra nếu sản phẩm đã có trong giỏ thì tăng số lượng, chưa có thì thêm mảng mới
        if (isset($_SESSION['cart'][$id]) && is_array($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]['quantity'] += 1;
        } else {
            $_SESSION['cart'][$id] = array(
                'name'     => $product['ten_san_pham'],
                'price'    => $price,
                'image'    => $product['hinh_anh'],
                'quantity' => 1
            );
        }
    }
    header("Location: cart.php");
    exit();
}

// 2. Xóa 1 sản phẩm
if ($action == 'delete' && $id > 0) {
    if (isset($_SESSION['cart'][$id])) {
        unset($_SESSION['cart'][$id]);
    }
    header("Location: cart.php");
    exit();
}

// 3. Xóa sạch giỏ hàng (Để reset nếu bị lỗi session)
if ($action == 'clear') {
    unset($_SESSION['cart']);
    header("Location: cart.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ Hàng - Gấu Bông Store</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #fff0f5; font-family: sans-serif; margin: 0; padding: 20px 10px; }
        .cart-container {
            max-width: 900px;
            margin: 0 auto;
            background: #fff;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .header-title { text-align: center; color: #9333EA; font-size: 22px; margin: 15px 0 25px; }
        
        /* Bảng hiển thị */
        .table-responsive { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 600px; }
        th { background: #f3e8ff; color: #7e22ce; padding: 12px; font-size: 14px; text-align: center; }
        td { padding: 12px; border-bottom: 1px solid #f3e8ff; text-align: center; vertical-align: middle; font-size: 14px; }

        .prod-info { display: flex; align-items: center; gap: 12px; text-align: left; }
        .prod-info img { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; flex-shrink: 0; }
        .prod-name { font-weight: bold; color: #333; line-height: 1.3; }

        .price { color: #d0011b; font-weight: bold; }
        .btn-delete {
            background: #ffe4e6;
            color: #e11d48;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            font-weight: bold;
            display: inline-block;
        }
        .btn-delete:hover { background: #fecdd3; }

        .cart-summary { margin-top: 25px; text-align: right; }
        .total-price { font-size: 22px; color: #d0011b; font-weight: bold; margin: 8px 0 15px; }
        .btn-checkout {
            display: inline-block;
            background: linear-gradient(90deg, #a855f7, #ec4899);
            color: #fff;
            padding: 12px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: bold;
        }

        @media screen and (max-width: 600px) {
            .cart-container { padding: 15px; }
            .cart-summary { text-align: center; }
            .btn-checkout { display: block; width: 100%; box-sizing: border-box; }
        }
    </style>
</head>
<body>

    <div class="cart-container">
        <a href="products.php" style="color: #9333EA; text-decoration: none; font-weight: bold;">
            <i class="fa-solid fa-arrow-left"></i> Tiếp tục mua hàng
        </a>

        <h2 class="header-title"><i class="fa-solid fa-cart-shopping"></i> GIỎ HÀNG CỦA BẠN</h2>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th style="width: 40%;">Sản phẩm</th>
                        <th>Giá</th>
                        <th>Số lượng</th>
                        <th>Tổng tiền</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total = 0;
                    if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])):
                        foreach ($_SESSION['cart'] as $cart_id => $item):
                            // Kiểm tra kĩ kiểu dữ liệu tránh lỗi lặp lại
                            if (!is_array($item)) continue;
                            
                            $subtotal = $item['price'] * $item['quantity'];
                            $total += $subtotal;
                    ?>
                        <tr>
                            <td>
                                <div class="prod-info">
                                    <img src="images/<?php echo htmlspecialchars($item['image']); ?>" alt="">
                                    <span class="prod-name"><?php echo htmlspecialchars($item['name']); ?></span>
                                </div>
                            </td>
                            <td><?php echo number_format($item['price']); ?> VNĐ</td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td class="price"><?php echo number_format($subtotal); ?> VNĐ</td>
                            <td>
                                <a href="cart.php?action=delete&id=<?php echo $cart_id; ?>" class="btn-delete">
                                    <i class="fa-solid fa-trash"></i> Xóa
                                </a>
                            </td>
                        </tr>
                    <?php 
                        endforeach;
                    else:
                    ?>
                        <tr>
                            <td colspan="5" style="padding: 30px; color: #666;">Giỏ hàng của bạn đang trống!</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="cart-summary">
            <div>Tổng tiền thanh toán:</div>
            <div class="total-price"><?php echo number_format($total); ?> VNĐ</div>
            <a href="checkout.php" class="btn-checkout">
                Đến trang thanh toán <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>

</body>
</html>
