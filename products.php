<?php
session_start();
include 'db.php';

// 1. Cấu hình phân trang
$limit = 6;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// 2. Lấy tham số lọc
$category = isset($_GET['category']) ? trim($_GET['category']) : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// 3. Xây dựng điều kiện lọc
$where_clauses = array();

if (!empty($search)) {
    $search_clean = mysqli_real_escape_string($conn, $search);
    $where_clauses[] = "ten_san_pham LIKE '%$search_clean%'";
}

if ($category != 'all' && !empty($category)) {
    $category_clean = mysqli_real_escape_string($conn, $category);
    $where_clauses[] = "danh_muc = '$category_clean'";
}

$where_sql = "";
if (count($where_clauses) > 0) {
    $where_sql = " WHERE " . implode(' AND ', $where_clauses);
}

// 4. Đếm tổng số sản phẩm để tính $total_pages
$sql_count = "SELECT COUNT(*) as total FROM san_pham" . $where_sql;
$result_count = mysqli_query($conn, $sql_count);
$row_count = mysqli_fetch_assoc($result_count);
$total_products = $row_count['total'];

$total_pages = ceil($total_products / $limit);
if ($total_pages < 1) {
    $total_pages = 1;
}

// 5. Truy vấn danh sách sản phẩm theo trang
$sql = "SELECT * FROM san_pham" . $where_sql . " ORDER BY id DESC LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cửa Hàng - Gấu Bông Store</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .badge-sale {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #EE4D2D;
            color: #fff;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
        }
        
        /* Hiển thị giá khuyến mãi */
        .price-box {
            margin: 8px 0;
            display: flex;
            align-items: baseline;
            gap: 6px;
            flex-wrap: wrap;
        }
        .price-sale {
            color: #EE4D2D;
            font-size: 18px;
            font-weight: bold;
        }
        .price-old {
            color: #929292;
            font-size: 13px;
            text-decoration: line-through;
        }
        .discount-percent {
            color: #EE4D2D;
            background: #FFEAE6;
            font-size: 11px;
            font-weight: 600;
            padding: 1px 4px;
            border-radius: 2px;
        }

        /* Nút thêm giỏ hàng */
        .btn-add-cart {
            display: block;
            width: 100%;
            background: #B197FC;
            color: #fff;
            text-align: center;
            padding: 8px 0;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
            margin-top: 10px;
            transition: 0.2s;
        }
        .btn-add-cart:hover {
            background: #9775FA;
        }
    </style>
</head>
<body>

    <div class="container hero-section">
        <!-- Sidebar Danh mục -->
        <aside class="sidebar">
            <div class="sidebar-title">DANH MỤC</div>
            <ul class="category-list">
                <li>
                    <a href="index.php" style="color: #9333EA; font-weight: bold;">
                        <i class="fa-solid fa-house"></i> Trang chủ
                    </a>
                </li>
                <hr style="border: 0; border-top: 1px dashed #E9D5FF; margin: 8px 0;">
                <li>
                    <a href="products.php?category=all" class="<?php echo ($category == 'all') ? 'active' : ''; ?>">
                        Tất cả sản phẩm <i class="fa-solid fa-chevron-right"></i>
                    </a>
                </li>
                <li>
                    <a href="products.php?category=teddy" class="<?php echo ($category == 'teddy') ? 'active' : ''; ?>">
                        Gấu Teddy <i class="fa-solid fa-chevron-right"></i>
                    </a>
                </li>
                <li>
                    <a href="products.php?category=capybara" class="<?php echo ($category == 'capybara') ? 'active' : ''; ?>">
                        Capybara <i class="fa-solid fa-chevron-right"></i>
                    </a>
                </li>
                <li>
                    <a href="products.php?category=tho" class="<?php echo ($category == 'tho') ? 'active' : ''; ?>">
                        Thỏ Bông <i class="fa-solid fa-chevron-right"></i>
                    </a>
                </li>
                <li>
                    <a href="products.php?category=phukien" class="<?php echo ($category == 'phukien') ? 'active' : ''; ?>">
                        Phụ kiện <i class="fa-solid fa-chevron-right"></i>
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Danh sách Sản phẩm -->
        <div class="products-section" style="flex: 1; margin-top: 0;">
            <div class="product-grid">
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($result)): 
                        // Kiểm tra sản phẩm có KM hay không (dựa vào gia_khuyen_mai hoặc % giảm giá trong CSDL)
                        // Nếu database có cột `gia_khuyen_mai` > 0 thì xem là sản phẩm khuyến mãi
                        $has_sale = isset($row['gia_khuyen_mai']) && $row['gia_khuyen_mai'] > 0;
                        
                        if ($has_sale) {
                            $gia_goc = $row['gia'];
                            $gia_ban = $row['gia_khuyen_mai'];
                            // Tính phần trăm giảm
                            $percent = round((($gia_goc - $gia_ban) / $gia_goc) * 100);
                        } else {
                            $gia_ban = $row['gia'];
                        }
                    ?>
                        <div class="product-card" style="position: relative;">
                            
                            <?php if ($has_sale): ?>
                                <!-- CHỈ SẢN PHẨM KHUYẾN MÃI MỚI HẠN HUY HIỆU SALE -->
                                <span class="badge-sale">-<?php echo $percent; ?>%</span>
                            <?php endif; ?>

                            <img src="images/<?php echo htmlspecialchars($row['hinh_anh']); ?>" alt="<?php echo htmlspecialchars($row['ten_san_pham']); ?>">
                            <div class="product-title"><?php echo htmlspecialchars($row['ten_san_pham']); ?></div>
                            
                            <?php if ($has_sale): ?>
                                <!-- GIAO DIỆN DÀNH RIÊNG CHO SẢN PHẨM KHUYẾN MÃI -->
                                <div class="price-box">
                                    <span class="price-sale"><?php echo number_format($gia_ban); ?> VNĐ</span>
                                    <span class="price-old"><?php echo number_format($gia_goc); ?> VNĐ</span>
                                    <span class="discount-percent">-<?php echo $percent; ?>%</span>
                                </div>
                            <?php else: ?>
                                <!-- GIAO DIỆN CHO SẢN PHẨM THƯỜNG -->
                                <div class="product-price"><?php echo number_format($gia_ban); ?> VNĐ</div>
                            <?php endif; ?>

                            <!-- Nút Thêm giỏ hàng -->
                            <a href="cart.php?action=add&id=<?php echo $row['id']; ?>" class="btn-add-cart">
                                <i class="fa-solid fa-cart-shopping"></i> Thêm giỏ hàng
                            </a>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="grid-column: 1/-1; text-align: center; color: #666; padding: 40px 0;">Không tìm thấy sản phẩm nào trong danh mục này.</p>
                <?php endif; ?>
            </div>

            <!-- Phân trang -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <a href="products.php?page=<?php echo max(1, $page - 1); ?>&category=<?php echo urlencode($category); ?>">&laquo; Trang trước</a>
                    
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="products.php?page=<?php echo $i; ?>&category=<?php echo urlencode($category); ?>" class="<?php echo ($i == $page) ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <a href="products.php?page=<?php echo min($total_pages, $page + 1); ?>&category=<?php echo urlencode($category); ?>">Trang sau &raquo;</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
