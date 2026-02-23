<?php
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

// Lấy thông tin từ session
$productName = 'Không xác định';
$price = 0;
$quantity = 1;

if (isset($_SESSION['purchase_success'])) {
    $productName = $_SESSION['purchase_success']['product'] ?? 'Không xác định';
    $price = $_SESSION['purchase_success']['price'] ?? 0;
    $quantity = $_SESSION['purchase_success']['quantity'] ?? 1;
    
    // Xóa session sau khi lấy
    unset($_SESSION['purchase_success']);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mua Hàng Thành Công</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Poppins',sans-serif;
}

body{
    background:#071423;
    height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
}

.popup{
    width:90%;
    max-width:420px;
    background:#08192d;
    border-radius:20px;
    padding:50px 25px;
    text-align:center;
    border:2px solid #00ff99;
    box-shadow:0 0 20px #00ff99,
               0 0 40px rgba(0,255,153,0.5);
    animation:fadeIn .5s ease;
}

.popup h1{
    font-size:26px;
    color:#00ffcc;
    margin-bottom:20px;
    font-weight:700;
}

.info{
    text-align:left;
    margin-bottom:20px;
}

.info p{
    font-size:16px;
    color:#ffffff;
    margin-bottom:8px;
}

.highlight{
    color:#00ff99;
    font-weight:600;
}

.notice{
    font-size:15px;
    color:#00ffcc;
    margin-top:15px;
    font-weight:600;
}

.btn-tg{
    display:inline-block;
    margin-top:15px;
    padding:12px 25px;
    border-radius:30px;
    background:#0088cc;
    color:#fff;
    font-weight:700;
    text-decoration:none;
    font-size:16px;
    transition:.3s;
}

.btn-tg:hover{
    background:#00aaff;
    box-shadow:0 0 12px #0088cc;
}

.btn-home{
    display:inline-block;
    margin-top:15px;
    margin-left:10px;
    padding:12px 25px;
    border-radius:30px;
    background:#00c853;
    color:#fff;
    font-weight:700;
    text-decoration:none;
    font-size:16px;
    transition:.3s;
}

.btn-home:hover{
    background:#00e676;
    box-shadow:0 0 12px #00e676;
}

.loader{
    width:40px;
    height:40px;
    border-radius:50%;
    border:4px solid transparent;
    border-top:4px solid #00ff99;
    border-bottom:4px solid #00ff99;
    margin:25px auto 0;
    animation:spin 1s linear infinite;
}

@keyframes spin{
    100%{ transform:rotate(360deg); }
}

@keyframes fadeIn{
    from{ transform:scale(.8); opacity:0;}
    to{ transform:scale(1); opacity:1;}
}
</style>
</head>
<body>

<div class="popup">
    <h1>Mua Hàng Thành Công!</h1>

    <div class="info">
        <p><span class="highlight">Sản Phẩm :</span> <?php echo htmlspecialchars($productName); ?></p>
        <p><span class="highlight">Số Lượng :</span> <?php echo $quantity; ?></p>
        <p><span class="highlight">Tổng Tiền :</span> <?php echo number_format($price); ?>đ</p>
    </div>

    <div class="notice">
        Vui lòng liên hệ ADMIN để nhận KEY
    </div>

    <a href="https://t.me/anhtuaniosvvip1" class="btn-tg" target="_blank">
        Liên hệ ADMIN
    </a>
    
    <a href="key.php" class="btn-home">
        Trang chủ
    </a>

    <div class="loader"></div>
</div>

<script>
// Tự động chuyển về trang chủ sau 10 giây
setTimeout(function() {
    window.location.href = 'muakey.php';
}, 10000);
</script>

</body>
</html>