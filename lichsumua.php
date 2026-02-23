<?php
session_start();
// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

$currentUsername = $_SESSION['username'];
$userFile = 'data/users.json';
$history = [];

// 2. Load dữ liệu lịch sử từ JSON
if (file_exists($userFile)) {
    $users = json_decode(file_get_contents($userFile), true);
    
    if (is_array($users)) {
        // Tìm user trong mảng
        $foundUser = null;
        
        // Trường hợp 1: JSON dạng Object {"username": {...}}
        if (isset($users[$currentUsername])) {
            $foundUser = $users[$currentUsername];
        } 
        // Trường hợp 2: JSON dạng Mảng [{...}, {...}]
        else {
            foreach ($users as $user) {
                if (isset($user['username']) && $user['username'] === $currentUsername) {
                    $foundUser = $user;
                    break;
                }
            }
        }

        // Nếu tìm thấy user và có lịch sử mua
        if ($foundUser && isset($foundUser['history']) && is_array($foundUser['history'])) {
            // Lấy mảng history (đã được sắp xếp mới nhất lên trên từ purchase.php)
            $history = $foundUser['history'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Lịch Sử Mua Hàng</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
  <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Poppins', sans-serif;
    }

    body {
        background: linear-gradient(180deg, #050a2a, #0b1c45);
        color: #fff;
        min-height: 100vh;
        padding: 20px;
    }
    
    /* HEADER giống muakey.php */
    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        background: rgba(0, 0, 0, 0.3);
        backdrop-filter: blur(10px);
        position: sticky;
        top: 0;
        z-index: 100;
        border-radius: 10px;
        margin-bottom: 20px;
    }

    .left {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .logo {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        background: #111;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        cursor: pointer;
    }

    .menu-icon {
        font-size: 24px;
        cursor: pointer;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        transition: 0.3s;
    }

    .menu-icon:hover {
        background: rgba(255, 255, 255, 0.1);
    }

    .right {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .bell {
        position: relative;
        font-size: 22px;
        cursor: pointer;
    }

    .bell::after {
        content: "";
        position: absolute;
        top: 2px;
        right: 0;
        width: 8px;
        height: 8px;
        background: red;
        border-radius: 50%;
    }

    .avatar {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: linear-gradient(135deg, #00ff9d, #00c3ff);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 18px;
        color: #050a2a;
        cursor: pointer;
    }

    .login {
        text-align: right;
        cursor: pointer;
        padding: 5px 10px;
        border-radius: 10px;
        transition: 0.3s;
    }

    .login:hover {
        background: rgba(255, 255, 255, 0.1);
    }

    .login h3 {
        color: #ffd700;
        font-size: 15px;
    }

    .login span {
        color: #00ff9d;
        font-weight: 600;
    }

    a {
        color: #fff;
        text-decoration: none;
    }
    
    /* Back button */
    .back {
        margin-bottom: 20px;
    }
    
    .back a {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        background: rgba(0, 0, 0, 0.3);
        border-radius: 10px;
        border: 1px solid #00c3ff;
        transition: 0.3s;
    }
    
    .back a:hover {
        transform: translateY(-2px);
        box-shadow: 0 0 15px #00c3ff;
    }
    
    /* Header container */
    .header-container {
        text-align: center;
        margin-bottom: 30px;
    }
    
    h1 {
        font-size: 2.5rem;
        font-weight: 700;
        margin: 20px 0 15px;
        position: relative;
        display: inline-block;
    }
    
    h1::after {
        content: '';
        position: absolute;
        bottom: -8px;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 3px;
        background: linear-gradient(90deg, transparent, #00ff9d, transparent);
        border-radius: 2px;
    }
    
    .accent {
        color: #00ff9d;
        text-shadow: 0 0 10px rgba(0, 255, 157, 0.3);
    }
    
    .subtitle {
        font-size: 1rem;
        color: #8aa6ad;
        margin-bottom: 15px;
        font-weight: 400;
        letter-spacing: 0.5px;
    }
    
    /* Container chính */
    .container {
        width: 95%;
        max-width: 1200px;
        margin: 0 auto;
        background: rgba(0, 0, 0, 0.4);
        border-radius: 20px;
        padding: 30px;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow: 0 0 30px rgba(0, 195, 255, 0.2);
    }
    
    /* Bảng */
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }
    
    thead {
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    th, td {
        padding: 18px 12px;
        text-align: left;
        font-size: 14px;
    }
    
    th {
        color: #ffd700;
        font-weight: 600;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        background: rgba(0, 0, 0, 0.3);
    }
    
    th::after {
        content: '';
        position: absolute;
        bottom: -1px;
        left: 0;
        width: 100%;
        height: 1px;
        background: linear-gradient(90deg, #00ff9d, transparent);
    }
    
    tr {
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        transition: 0.3s;
    }
    
    tr:hover {
        background: rgba(0, 195, 255, 0.1);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 195, 255, 0.2);
    }
    
    td {
        color: #fff;
    }
    
    td.plan {
        color: #00ff9d;
        font-weight: 600;
    }
    
    td.price {
        color: #ffae00;
        font-weight: 600;
    }
    
    td.quantity {
        color: #fff;
    }
    
    td.status {
        font-weight: 600;
        position: relative;
        padding-left: 24px;
    }
    
    td.status::before {
        content: '';
        position: absolute;
        left: 8px;
        top: 50%;
        transform: translateY(-50%);
        width: 10px;
        height: 10px;
        border-radius: 50%;
        box-shadow: 0 0 10px currentColor;
    }
    
    .status-success {
        color: #00ff9d;
    }
    .status-success::before {
        background: #00ff9d;
    }
    
    /* Nút hành động */
    td.action button {
        width: 100%;
        background: linear-gradient(90deg, #00c853, #00e676);
        border: none;
        color: white;
        font-weight: 600;
        padding: 10px 16px;
        border-radius: 8px;
        cursor: pointer;
        transition: 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        font-family: 'Poppins', sans-serif;
        font-size: 0.9rem;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    td.action button:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(0, 230, 118, 0.4);
    }
    
    td.action button:active {
        transform: translateY(1px);
    }
    
    /* Empty state */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #8aa6ad;
    }
    
    .empty-state h3 {
        font-size: 1.5rem;
        margin-bottom: 15px;
        color: #00ff9d;
    }
    
    .empty-state p {
        font-size: 1rem;
        margin-bottom: 25px;
    }
    
    .empty-state a {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 24px;
        background: linear-gradient(90deg, #00c853, #00e676);
        border: none;
        color: white;
        font-weight: 600;
        border-radius: 8px;
        cursor: pointer;
        transition: 0.3s;
        text-decoration: none;
    }
    
    .empty-state a:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(0, 230, 118, 0.4);
    }
    
    /* Hiệu ứng loading */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 1000;
        backdrop-filter: blur(5px);
    }

    .loading-spinner {
        width: 50px;
        height: 50px;
        border: 5px solid #f3f3f3;
        border-top: 5px solid #00ff9d;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    /* Animation cho bảng */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    tr {
        animation: fadeInUp 0.5s ease forwards;
        opacity: 0;
    }
    
    tr:nth-child(1) { animation-delay: 0.1s; }
    tr:nth-child(2) { animation-delay: 0.2s; }
    tr:nth-child(3) { animation-delay: 0.3s; }
    tr:nth-child(4) { animation-delay: 0.4s; }
    tr:nth-child(5) { animation-delay: 0.5s; }
    tr:nth-child(6) { animation-delay: 0.6s; }
    tr:nth-child(7) { animation-delay: 0.7s; }
    tr:nth-child(8) { animation-delay: 0.8s; }
    tr:nth-child(9) { animation-delay: 0.9s; }
    tr:nth-child(10) { animation-delay: 1s; }
    
    /* Responsive */
    @media (max-width: 768px) {
        .container {
            padding: 20px;
            overflow-x: auto;
        }
        
        table {
            min-width: 800px;
        }
        
        th, td {
            padding: 14px 8px;
            font-size: 13px;
        }
        
        h1 {
            font-size: 2rem;
        }
        
        .subtitle {
            font-size: 0.9rem;
        }
        
        .back a {
            padding: 8px 16px;
            font-size: 14px;
        }
        
        .header {
            padding: 10px;
        }
        
        .login h3 {
            font-size: 13px;
        }
        
        .login span {
            font-size: 14px;
        }
    }
    
    @media (max-width: 480px) {
        body {
            padding: 10px;
        }
        
        .container {
            padding: 15px;
        }
        
        th, td {
            padding: 12px 6px;
            font-size: 12px;
        }
        
        td.action button {
            padding: 8px 12px;
            font-size: 12px;
        }
    }
  </style>
</head>
<body>

<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner"></div>
</div>

<!-- Header giống muakey.php -->
<div class="header">
    <div class="left">
        <div class="menu-icon" onclick="toggleSidebar()">☰</div>
        <div class="logo" onclick="scrollToTop()">🔥</div>
    </div>

    <div class="right">
        <div class="bell">🔔</div>
        <div class="avatar" onclick="showProfile()"><?php echo strtoupper(substr($currentUsername, 0, 1)); ?></div>
        <div class="login" onclick="window.location.href='nap.php'">
            <h3><?php echo htmlspecialchars($currentUsername); ?></h3>
            <span id="userBalance"><?php echo number_format($balance ?? 0); ?>đ</span>
        </div>
    </div>
</div>

<!-- Sidebar Menu giống muakey.php -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-avatar"><?php echo strtoupper(substr($currentUsername, 0, 1)); ?></div>
        <div class="sidebar-username"><?php echo htmlspecialchars($currentUsername); ?></div>
        <div class="sidebar-email"><?php echo isset($userData['email']) ? htmlspecialchars($userData['email']) : 'Chưa cập nhật'; ?></div>
        <div class="sidebar-balance">
            <div class="sidebar-balance-label">Số dư hiện tại</div>
            <div class="sidebar-balance-value"><?php echo number_format($balance ?? 0); ?>đ</div>
        </div>
    </div>
    
    <div class="sidebar-menu">
        <div class="sidebar-item" onclick="window.location.href='key.php'">
            <i class="bx bx-home"></i>
            <span>Trang Chủ</span>
        </div>
        
        <div class="sidebar-item" onclick="window.location.href='lichsumua.php'">
            <i class="bx bx-shopping-bag"></i>
            <span>Đơn hàng</span>
            <span class="badge">Mới</span>
        </div>
        
        <div class="sidebar-item" onclick="window.location.href='nap.php'">
            <i class="bx bx-university"></i>
            <span>Nạp qua Ngân hàng</span>
        </div>
    </div>
</div>

<div class="overlay" id="overlay" onclick="closeSidebar()"></div>

<div class="back">
    <a href="key.php">
        <i class='bx bx-arrow-back'></i>
        Quay lại Trang Chủ
    </a>
</div>

<div class="header-container">
    <h1>LỊCH SỬ <span class="accent">MUA HÀNG</span></h1>
    <div class="subtitle">Theo dõi lịch sử mua hàng của bạn</div>
</div>

<div class="container">
    <?php if (empty($history)): ?>
        <div id="empty-state" class="empty-state">
            <i class='bx bx-package' style="font-size: 3rem; margin-bottom: 20px; color: #00ff9d;"></i>
            <h3>Chưa có giao dịch nào</h3>
            <p>Bạn chưa mua sản phẩm nào.</p>
            <a href="muakey.php">
                <i class='bx bx-cart'></i>
                Mua hàng ngay
            </a>
        </div>
    <?php else: ?>
        <table id="purchase-history">
            <thead>
                <tr>
                    <th>THỜI GIAN</th>
                    <th>SẢN PHẨM</th>
                    <th>SỐ LƯỢNG</th>
                    <th>TỔNG TIỀN</th>
                    <th>TRẠNG THÁI</th>
                    <th>HÀNH ĐỘNG</th>
                </tr>
            </thead>
            <tbody id="history-body">
                <?php foreach ($history as $item): ?>
                    <tr>
                        <td><?php echo date('d/m/Y H:i', strtotime($item['date'])); ?></td>
                        <td class="plan"><?php echo htmlspecialchars($item['plan']); ?></td>
                        <td class="quantity"><?php echo isset($item['quantity']) ? $item['quantity'] : 1; ?></td>
                        <td class="price"><?php echo number_format($item['price']); ?>đ</td>
                        <td class="status status-success">Thành công</td>
                        <td class="action">
                            <button onclick="contactAdmin()">
                                <i class='bx bx-message-detail'></i> Liên hệ Admin
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<style>
/* Thêm CSS cho sidebar và overlay giống muakey.php */
.sidebar {
    position: fixed;
    top: 0;
    left: -320px;
    width: 300px;
    height: 100%;
    background: linear-gradient(180deg, #0f0f1a 0%, #1a1a2f 100%);
    z-index: 1002;
    transition: 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    padding: 20px 0;
    border-right: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 5px 0 30px rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(10px);
}

.sidebar.active {
    left: 0;
}

.sidebar-header {
    padding: 25px 20px 20px;
    text-align: center;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    margin-bottom: 20px;
}

.sidebar-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    font-weight: 700;
    margin: 0 auto 15px;
    color: #fff;
    border: 3px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 0 20px rgba(102, 126, 234, 0.5);
}

.sidebar-username {
    font-size: 20px;
    font-weight: 600;
    color: #fff;
    margin-bottom: 5px;
}

.sidebar-email {
    font-size: 13px;
    color: #a0a0c0;
    margin-bottom: 15px;
    word-break: break-all;
}

.sidebar-balance {
    background: rgba(255, 255, 255, 0.1);
    padding: 12px;
    border-radius: 12px;
    margin-top: 15px;
    border: 1px solid rgba(255, 255, 255, 0.05);
}

.sidebar-balance-label {
    font-size: 12px;
    color: #a0a0c0;
    margin-bottom: 5px;
}

.sidebar-balance-value {
    font-size: 24px;
    font-weight: 700;
    color: #00ff9d;
    text-shadow: 0 0 10px rgba(0, 255, 157, 0.3);
}

.sidebar-menu {
    padding: 10px 0;
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.sidebar-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 18px 25px;
    color: #e0e0e0;
    text-decoration: none;
    transition: 0.3s;
    cursor: pointer;
    border-left: 4px solid transparent;
    margin: 2px 10px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.03);
}

.sidebar-item:hover {
    background: linear-gradient(90deg, rgba(102, 126, 234, 0.2), rgba(102, 126, 234, 0.1));
    border-left-color: #667eea;
    color: #fff;
    transform: translateX(5px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
}

.sidebar-item i {
    width: 28px;
    color: #667eea;
    font-size: 20px;
    text-align: center;
}

.sidebar-item span {
    flex: 1;
    font-weight: 600;
    font-size: 16px;
    letter-spacing: 0.5px;
}

.sidebar-item .badge {
    background: #ff4757;
    color: white;
    font-size: 11px;
    padding: 4px 10px;
    border-radius: 20px;
    font-weight: 600;
    box-shadow: 0 2px 5px rgba(255, 71, 87, 0.3);
}

.overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    z-index: 1001;
    display: none;
    backdrop-filter: blur(3px);
}

.overlay.active {
    display: block;
}
</style>

<script>
// Hàm toggle sidebar
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('active');
    document.getElementById('overlay').classList.toggle('active');
}

// Close sidebar
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('active');
    document.getElementById('overlay').classList.remove('active');
}

// Show profile (có thể thêm modal profile sau)
function showProfile() {
    // Tạm thời chưa có modal profile
    console.log('Show profile');
}

// Scroll to top
function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Hàm liên hệ Admin
function contactAdmin() {
    window.open('https://t.me/livechu2004', '_blank');
}

// Đóng modal khi click overlay
document.getElementById('overlay').addEventListener('click', function() {
    closeSidebar();
});
</script>

</body>
</html>