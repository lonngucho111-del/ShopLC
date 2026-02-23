<?php
session_start();

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

$currentUsername = trim($_SESSION['username']);
$userFile = 'data/users.json';
$balance = 0;
$userData = [];

// 2. Load dữ liệu thông minh
if (file_exists($userFile)) {
    $users = json_decode(file_get_contents($userFile), true);
    
    if (is_array($users)) {
        // Tìm thông tin user
        foreach ($users as $user) {
            if (is_array($user) && isset($user['username']) && $user['username'] === $currentUsername) {
                $userData = $user;
                $balance = isset($user['balance']) ? intval($user['balance']) : 0;
                break;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ANH TUẤN Shop</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        padding-bottom: 30px;
    }

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
    }

    .left {
        display: flex;
        align-items: center;
        gap: 12px;
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

    .category {
        margin: 15px;
        background: linear-gradient(90deg, #1abc9c, #2e71);
        padding: 16px;
        border-radius: 16px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    }

    .list {
        padding: 10px 15px 40px;
    }

    .item {
        background: rgba(0, 0, 0, 0.6);
        border: 2px solid #00c3ff;
        border-radius: 20px;
        padding: 18px;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 15px;
        transition: 0.3s;
        cursor: pointer;
    }

    .item:hover {
        transform: translateY(-3px);
        box-shadow: 0 0 15px #00c3ff;
    }

    .item img {
        width: 48px;
        height: 48px;
        border-radius: 12px;
    }

    .item span {
        font-weight: 600;
        letter-spacing: 1px;
    }

    .products-20 {
        padding: 0 15px 30px;
    }

    .product-card {
        margin-bottom: 20px;
        padding: 20px;
        border-radius: 25px;
        border: 2px solid #ffffff;
        background: rgba(0, 0, 0, 0.55);
        box-shadow: 0 0 30px rgba(255, 255, 255, 0.5);
        transition: 0.3s;
        position: relative;
        overflow: hidden;
    }

    .product-card:hover {
        box-shadow: 0 0 30px rgba(255, 255, 255, 0.8);
        border-color: #ffffff;
        transform: translateY(-3px);
    }

    .product-card::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
        opacity: 0;
        transition: 0.5s;
        pointer-events: none;
    }

    .product-card:hover::before {
        opacity: 1;
    }

    .product-banner img {
        width: 100%;
        border-radius: 15px;
        border: 2px solid rgba(255, 255, 255, 0.3);
    }

    .product-title {
        text-align: center;
        font-size: 22px;
        font-weight: 700;
        color: #d4ff00;
        margin: 20px 0;
        text-shadow: 0 0 10px rgba(212, 255, 0, 0.3);
    }

    .features {
        list-style: none;
        margin-bottom: 25px;
    }

    .features li {
        margin-bottom: 10px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .features li::before {
        content: "✔ ";
        color: #00ff9d;
        font-weight: bold;
    }

    .info {
        display: flex;
        justify-content: center;
        margin-bottom: 20px;
        font-weight: 600;
    }

    .price {
        color: #ffae00;
        font-size: 20px;
        text-align: center;
        background: rgba(0, 0, 0, 0.3);
        padding: 8px 20px;
        border-radius: 30px;
        display: inline-block;
    }

    .buy-btn {
        width: 100%;
        padding: 15px;
        border: none;
        border-radius: 15px;
        background: linear-gradient(90deg, #00c853, #00e676);
        color: white;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: 0.3s;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .buy-btn:hover {
        transform: scale(1.03);
        box-shadow: 0 5px 20px rgba(0, 230, 118, 0.4);
    }

    .buy-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

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

    .modal {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) scale(0.9);
        background: rgba(10, 20, 40, 0.95);
        border: 2px solid #00ff9d;
        border-radius: 20px;
        padding: 30px;
        width: 90%;
        max-width: 400px;
        z-index: 1001;
        opacity: 0;
        visibility: hidden;
        transition: 0.3s;
        backdrop-filter: blur(10px);
        box-shadow: 0 0 30px rgba(0, 255, 157, 0.3);
    }

    .modal.active {
        opacity: 1;
        visibility: visible;
        transform: translate(-50%, -50%) scale(1);
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        padding-bottom: 15px;
    }

    .modal-header h2 {
        color: #00ff9d;
        font-size: 24px;
    }

    .close-btn {
        font-size: 30px;
        cursor: pointer;
        color: #ff6b6b;
        transition: 0.3s;
    }

    .close-btn:hover {
        transform: scale(1.2);
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        margin: 15px 0;
        padding: 10px;
        background: rgba(0, 0, 0, 0.3);
        border-radius: 10px;
    }

    .info-label {
        color: #8aa6ad;
        font-weight: 400;
    }

    .info-value {
        font-weight: 600;
        color: #00ff9d;
    }

    .info-value.active {
        color: #00ff9d;
    }

    .info-value.inactive {
        color: #ff6b6b;
    }

    .payment-modal {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) scale(0.9);
        background: #1a1f3a;
        border-radius: 30px;
        padding: 25px;
        width: 90%;
        max-width: 400px;
        z-index: 1002;
        opacity: 0;
        visibility: hidden;
        transition: 0.3s;
        border: 2px solid #ffd700;
        box-shadow: 0 0 30px rgba(255, 215, 0, 0.3);
        color: #fff;
    }

    .payment-modal.active {
        opacity: 1;
        visibility: visible;
        transform: translate(-50%, -50%) scale(1);
    }

    .payment-header {
        text-align: center;
        margin-bottom: 20px;
        position: relative;
    }

    .payment-header h2 {
        color: #ffd700;
        font-size: 28px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 2px;
    }

    .payment-header .close-payment {
        position: absolute;
        right: 0;
        top: 0;
        font-size: 30px;
        cursor: pointer;
        color: #ff6b6b;
        transition: 0.3s;
    }

    .payment-header .close-payment:hover {
        transform: scale(1.2);
    }

    .product-info {
        background: rgba(0, 0, 0, 0.4);
        border-radius: 20px;
        padding: 15px;
        margin-bottom: 20px;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .product-name {
        font-size: 18px;
        font-weight: 600;
        color: #00ff9d;
        margin-bottom: 10px;
        text-align: center;
    }

    .quantity-selector {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 15px;
        padding: 10px 15px;
        margin: 15px 0;
    }

    .quantity-label {
        font-weight: 500;
        color: #a0a0c0;
    }

    .quantity-controls {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .quantity-btn {
        width: 35px;
        height: 35px;
        border-radius: 10px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        border: none;
        color: white;
        font-size: 20px;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: 0.3s;
    }

    .quantity-btn:hover {
        transform: scale(1.1);
    }

    .quantity-btn:active {
        transform: scale(0.95);
    }

    .quantity-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .quantity-number {
        font-size: 20px;
        font-weight: 700;
        color: #ffd700;
        min-width: 40px;
        text-align: center;
    }

    .price-detail {
        background: rgba(0, 0, 0, 0.3);
        border-radius: 15px;
        padding: 15px;
        margin: 15px 0;
    }

    .price-row {
        display: flex;
        justify-content: space-between;
        margin: 8px 0;
        font-size: 16px;
    }

    .price-row.total {
        margin-top: 15px;
        padding-top: 15px;
        border-top: 2px dashed rgba(255, 215, 0, 0.3);
        font-size: 18px;
        font-weight: 700;
        color: #ffd700;
    }

    .price-label {
        color: #a0a0c0;
    }

    .price-value {
        font-weight: 600;
    }

    .price-value.total {
        color: #ffd700;
        font-size: 20px;
    }

    .payment-button {
        width: 100%;
        padding: 18px;
        border: none;
        border-radius: 15px;
        background: linear-gradient(90deg, #00c853, #00e676);
        color: white;
        font-size: 18px;
        font-weight: 700;
        cursor: pointer;
        transition: 0.3s;
        text-transform: uppercase;
        letter-spacing: 2px;
        margin-top: 10px;
    }

    .payment-button:hover {
        transform: scale(1.02);
        box-shadow: 0 5px 20px rgba(0, 230, 118, 0.4);
    }

    .payment-button:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .current-balance {
        text-align: center;
        margin-top: 15px;
        font-size: 14px;
        color: #a0a0c0;
    }

    .current-balance span {
        color: #00ff9d;
        font-weight: 700;
        font-size: 16px;
    }

    .sakura {
        position: fixed;
        top: -10px;
        width: 12px;
        height: 12px;
        background: pink;
        border-radius: 50%;
        opacity: 0.7;
        animation: fall linear infinite;
        z-index: 999;
        pointer-events: none;
    }

    @keyframes fall {
        to {
            transform: translateY(110vh) rotate(360deg);
        }
    }

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

    /* Popup thông báo */
    .notice-popup {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) scale(0.9);
        width: 90%;
        max-width: 420px;
        background: rgba(0, 0, 0, 0.85);
        backdrop-filter: blur(15px);
        border-radius: 25px;
        padding: 30px 25px;
        color: #fff;
        box-shadow: 0 0 50px rgba(0, 234, 255, 0.4);
        border: 2px solid rgba(0, 234, 255, 0.5);
        text-align: center;
        z-index: 2000;
        opacity: 0;
        visibility: hidden;
        transition: 0.4s ease;
    }

    .notice-popup.active {
        opacity: 1;
        visibility: visible;
        transform: translate(-50%, -50%) scale(1);
    }

    .notice-popup h2 {
        color: #00eaff;
        font-size: 24px;
        margin-bottom: 15px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        text-shadow: 0 0 15px rgba(0, 234, 255, 0.5);
    }

    .notice-popup p {
        font-size: 15px;
        line-height: 1.7;
        color: #e0e0e0;
        margin-bottom: 20px;
    }

    .admin-box {
        margin: 20px 0;
    }

    .admin-box span {
        display: block;
        margin-bottom: 10px;
        font-weight: 600;
        color: #ffd700;
    }

    .phone {
        background: linear-gradient(45deg, #00eaff, #0072ff);
        padding: 15px;
        border-radius: 50px;
        font-weight: 700;
        letter-spacing: 2px;
        font-size: 20px;
        color: #fff;
        box-shadow: 0 5px 20px rgba(0, 234, 255, 0.4);
        margin-bottom: 15px;
    }

    .links {
        margin: 20px 0;
        text-align: left;
        background: rgba(255, 255, 255, 0.05);
        padding: 15px;
        border-radius: 15px;
    }

    .links div {
        margin: 12px 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .links i {
        color: #00eaff;
        font-size: 18px;
        width: 25px;
    }

    .links a {
        color: #00eaff;
        text-decoration: none;
        font-weight: 600;
        transition: 0.3s;
        padding: 5px 10px;
        border-radius: 8px;
        background: rgba(0, 234, 255, 0.1);
    }

    .links a:hover {
        background: rgba(0, 234, 255, 0.2);
        text-decoration: none;
        transform: translateX(5px);
    }

    .footer {
        margin-top: 20px;
        font-size: 14px;
        color: #aaa;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        padding-top: 20px;
    }

    .notice-close {
        position: absolute;
        top: 15px;
        right: 20px;
        font-size: 30px;
        cursor: pointer;
        color: #ff6b6b;
        transition: 0.3s;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.1);
    }

    .notice-close:hover {
        transform: rotate(90deg);
        background: rgba(255, 107, 107, 0.2);
    }

    .show-notice-btn {
        background: linear-gradient(135deg, #00eaff, #0072ff);
        border: none;
        color: white;
        padding: 8px 15px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        margin-left: 10px;
        transition: 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .show-notice-btn:hover {
        transform: scale(1.05);
        box-shadow: 0 0 15px rgba(0, 234, 255, 0.5);
    }

    @media (max-width: 768px) {
        .header {
            padding: 10px;
        }
        
        .login h3 {
            font-size: 13px;
        }
        
        .login span {
            font-size: 14px;
        }
        
        .product-title {
            font-size: 18px;
        }
        
        .features li {
            font-size: 14px;
        }

        .sidebar {
            width: 280px;
        }

        .payment-modal {
            padding: 20px;
        }

        .payment-header h2 {
            font-size: 24px;
        }

        .notice-popup {
            padding: 20px 15px;
        }

        .notice-popup h2 {
            font-size: 20px;
        }

        .phone {
            font-size: 18px;
            padding: 12px;
        }
    }
    </style>
</head>
<body>

<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner"></div>
</div>

<!-- Popup thông báo quan trọng -->
<div class="notice-popup" id="noticePopup">
    <div class="notice-close" onclick="closeNoticePopup()">&times;</div>
    <h2>⚠ THÔNG BÁO QUAN TRỌNG ⚠</h2>
    <p>
        Những trường hợp bị khóa tài khoản vui lòng liên hệ admin
        để được hoàn file khác.
    </p>
    <div class="admin-box">
        <span>📱 ZALO ADMIN:</span>
        <div class="phone">0342031354</div>
    </div>
    <div class="links">
        <div>
            <i class="fab fa-telegram"></i>
            <span>LIÊN HỆ ADMIN:</span>
            <a href="https://t.me/anhtuaniosvvip1" target="_blank">Nhấn vào đây</a>
        </div>
        <div>
            <i class="fas fa-users"></i>
            <span>Box của admin:</span>
            <a href="https://t.me/trumhackvip36" target="_blank">Nhấn vào đây</a>
        </div>
    </div>
    <div class="footer">
        Shop tự động 100% nạp bank auto 24/7
    </div>
</div>

<!-- Payment Modal -->
<div class="payment-modal" id="paymentModal">
    <div class="payment-header">
        <h2>THANH TOÁN</h2>
        <div class="close-payment" onclick="closePaymentModal()">&times;</div>
    </div>
    
    <div class="product-info">
        <div class="product-name" id="paymentProductName">IPA TRẮNG + AIM CỔ</div>
        
        <div class="quantity-selector">
            <span class="quantity-label">Số lượng mua :</span>
            <div class="quantity-controls">
                <button class="quantity-btn" onclick="changeQuantity(-1)" id="decreaseQty">−</button>
                <span class="quantity-number" id="quantityDisplay">1</span>
                <button class="quantity-btn" onclick="changeQuantity(1)" id="increaseQty">+</button>
            </div>
        </div>
    </div>
    
    <div class="price-detail">
        <div class="price-row">
            <span class="price-label">Đơn giá:</span>
            <span class="price-value" id="unitPriceDisplay">30.000₫</span>
        </div>
        <div class="price-row">
            <span class="price-label">Số lượng:</span>
            <span class="price-value" id="quantityPriceDisplay">1</span>
        </div>
        <div class="price-row total">
            <span class="price-label">Thanh toán:</span>
            <span class="price-value total" id="finalPriceDisplay">30.000₫</span>
        </div>
    </div>
    
    <div class="current-balance">
        Số dư: <span id="modalCurrentBalance"><?php echo number_format($balance); ?>đ</span>
    </div>
    
    <button class="payment-button" id="confirmPaymentBtn" onclick="confirmPayment()">THANH TOÁN</button>
</div>

<!-- Profile Modal -->
<div class="modal" id="profileModal">
    <div class="modal-header">
        <h2>THÔNG TIN TÀI KHOẢN</h2>
        <div class="close-btn" onclick="closeProfile()">&times;</div>
    </div>
    <div class="info-row">
        <span class="info-label">Tên đăng nhập:</span>
        <span class="info-value"><?php echo htmlspecialchars($currentUsername); ?></span>
    </div>
    <div class="info-row">
        <span class="info-label">Email:</span>
        <span class="info-value"><?php echo isset($userData['email']) ? htmlspecialchars($userData['email']) : 'Chưa cập nhật'; ?></span>
    </div>
    <div class="info-row">
        <span class="info-label">Số dư:</span>
        <span class="info-value"><?php echo number_format($balance); ?>đ</span>
    </div>
    <div class="info-row">
        <span class="info-label">Trạng thái:</span>
        <span class="info-value <?php echo (isset($userData['isActive']) && $userData['isActive'] == true) ? 'active' : 'inactive'; ?>">
            <?php echo (isset($userData['isActive']) && $userData['isActive'] == true) ? 'ĐÃ KÍCH HOẠT' : 'CHƯA KÍCH HOẠT'; ?>
        </span>
    </div>
    <?php if(isset($userData['expiryDate']) && $userData['expiryDate']): ?>
    <div class="info-row">
        <span class="info-label">Hết hạn:</span>
        <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($userData['expiryDate'])); ?></span>
    </div>
    <?php endif; ?>
    <div class="info-row">
        <span class="info-label">IP:</span>
        <span class="info-value" id="userIP">Đang tải...</span>
    </div>
    <div class="info-row">
        <span class="info-label">Thiết bị:</span>
        <span class="info-value" id="userDevice">Đang tải...</span>
    </div>
</div>

<!-- Sidebar Menu -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-avatar"><?php echo strtoupper(substr($currentUsername, 0, 1)); ?></div>
        <div class="sidebar-username"><?php echo htmlspecialchars($currentUsername); ?></div>
        <div class="sidebar-email"><?php echo isset($userData['email']) ? htmlspecialchars($userData['email']) : 'Chưa cập nhật'; ?></div>
        <div class="sidebar-balance">
            <div class="sidebar-balance-label">Số dư hiện tại</div>
            <div class="sidebar-balance-value"><?php echo number_format($balance); ?>đ</div>
        </div>
    </div>
    
    <div class="sidebar-menu">
        <div class="sidebar-item" onclick="window.location.href='key.php'">
            <i class="fas fa-home"></i>
            <span>Trang Chủ</span>
        </div>
        
        <div class="sidebar-item" onclick="window.location.href='lichsumua.php'">
            <i class="fas fa-shopping-bag"></i>
            <span>Đơn hàng</span>
            <span class="badge">Mới</span>
        </div>
        
        <div class="sidebar-item" onclick="window.location.href='nap.php'">
            <i class="fas fa-university"></i>
            <span>Nạp qua Ngân hàng</span>
        </div>
    </div>
</div>

<div class="overlay" id="overlay" onclick="closeSidebar()"></div>

<div class="header">
    <div class="left">
        <div class="menu-icon" onclick="toggleSidebar()">☰</div>
    </div>

    <div class="right">
        <div class="avatar" onclick="showProfile()"><?php echo strtoupper(substr($currentUsername, 0, 1)); ?></div>
        <div class="login" onclick="window.location.href='nap.php'">
            <h3><?php echo htmlspecialchars($currentUsername); ?></h3>
            <span id="userBalance"><?php echo number_format($balance); ?>đ</span>
        </div>
    </div>
</div>

<div class="category">
    🛒 Tất cả sản phẩm
    <button class="show-notice-btn" onclick="showNoticePopup()">
        <i class="fas fa-bell"></i> Thông báo
    </button>
</div>

<div class="list">
    <div class="item" onclick="scrollToProducts()"><img src="https://i.postimg.cc/kGTcRk01/IMG-2429.jpg"><span>IPA BYPASS + CÂN CHECK</span></div>
    <div class="item" onclick="scrollToProducts()"><img src="https://i.postimg.cc/3NxqqCY0/IMG-2430.png"><span>IPA BYPASS + CỔ</span></div>
    <div class="item" onclick="scrollToProducts()"><img src="https://i.postimg.cc/fbg5LnNR/IMG-2431.jpg"><span>MENU  CRACK THEO TÊN</span></div>
    <div class="item" onclick="scrollToProducts()"><img src="https://i.postimg.cc/RVV3P3tQ/IMG-2432.jpg"><span>FILE NHẸ TÂM ADR</span></div>
    <div class="item" onclick="scrollToProducts()"><img src="https://i.postimg.cc/Kz8Pt9mR/IMG-2433.jpg"><span>CẤU HÌNH IOS NHẸ TÂM</span></div>
    <div class="item" onclick="scrollToProducts()"><img src="https://i.postimg.cc/Kz8Pt9mR/IMG-2433.jpg"><span>CẤU HÌNH IOS TRICK LOCKS</span></div>

<!-- 20+ SẢN PHẨM -->
<div class="products-20" id="productsContainer">
    <!-- Sản phẩm 1 -->
    <div class="product-card" data-plan="IPA + NHẸ TÂM" data-price="120000">
        <div class="product-banner">
            <img src="https://i.postimg.cc/kGTcRk01/IMG-2429.jpg">
        </div>
        <div class="product-title">IPA BYPASS ANTIBAN</div>
        <ul class="features">
            <li>CÂN RANK</li>
            <li>NHẸ TÂM CHECK VAI + CỔ</li>
            <li>ĐẠN THẲNG, BUFF 120 FPS</li>
            <li>Cài được tất cả IOS</li>
        </ul>
        <div class="info">
            <div class="price">120.000vnđ</div>
        </div>
        <button class="buy-btn" onclick="openPaymentModal(this)">🛒 Mua ngay</button>
    </div>

    <!-- Sản phẩm 2 -->
    <div class="product-card" data-plan="IPA + CỔ" data-price="120.000">
        <div class="product-banner">
            <img src="https://i.postimg.cc/3NxqqCY0/IMG-2430.png">
        </div>
        <div class="product-title">IPA + CỔ</div>
        <ul class="features">
            <li>ANTIBAN</li>
            <li>CHECK PHẦN CỔ</li>
            <li>ĐẠN THẲNG, BUFF 120 FPS</li>
        </ul>
        <div class="info">
            <div class="price">120.000</div>
        </div>
        <button class="buy-btn" onclick="openPaymentModal(this)">🛒 Mua ngay</button>
    </div>

    <!-- Sản phẩm 3 -->
    <div class="product-card" data-plan="FILE NHẸ TÂM ADR" data-price="100000">
        <div class="product-banner">
            <img src="https://i.postimg.cc/RVV3P3tQ/IMG-2432.jpg">
        </div>
        <div class="product-title">NHẸ TÂM</div>
        <ul class="features">
            <li>ANTIBAN</li>
        </ul>
        <div class="info">
            <div class="price">100.000</div>
        </div>
        <button class="buy-btn" onclick="openPaymentModal(this)">🛒 Mua ngay</button>
    </div>

    <!-- Sản phẩm 4 -->
    <div class="product-card" data-plan="CẤU HÌNH + NHẸ TÂM IOS" data-price="50000">
        <div class="product-banner">
            <img src="https://i.postimg.cc/Kz8Pt9mR/IMG-2433.jpg">
        </div>
        <div class="product-title">CẤU HÌNH + NHẸ TÂM IOS</div>
        <ul class="features">
            <li>CÁCH CÀI LIÊN HỆ TELE</li>
        </ul>
        <div class="info">
            <div class="price">50.000đ</div>
        </div>
        <button class="buy-btn" onclick="openPaymentModal(this)">🛒 Mua ngay</button>
    </div>

    <!-- Sản phẩm 5 -->
    <div class="product-card" data-plan="CRACK MENU THEO TÊN" data-price="40000">
        <div class="product-banner">
            <img src="https://i.postimg.cc/fbg5LnNR/IMG-2431.jpg">
        </div>
        <div class="product-title">CRACK MENU THEO TÊN</div>
        <ul class="features">
            <li> LIÊN HỆ TELE ĐỂ CHỌN LÀM MAKE CRACK</li>
        </ul>
        <div class="info">
            <div class="price">40.000đ</div>
        </div>
        <button class="buy-btn" onclick="openPaymentModal(this)">🛒 Mua ngay</button>
    </div>
<!-- Sản phẩm 5 -->
    <div class="product-card" data-plan="CẤU HÌNH + TRICK LOCKS" data-price="100000">
        <div class="product-banner">
            <img src="https://i.postimg.cc/Kz8Pt9mR/IMG-2433.jpg">
        </div>
        <div class="product-title">CẤU HÌNH + TRICK LOCKS</div>
        <ul class="features">
            <li>CÁCH CÀI LIÊN HỆ TELE</li>
        </ul>
        <div class="info">
            <div class="price">100.000đ</div>
        </div>
        <button class="buy-btn" onclick="openPaymentModal(this)">🛒 Mua ngay</button>
    </div>
</div>

<script>
// Biến toàn cục cho payment modal
let currentProduct = {
    plan: '',
    price: 0,
    quantity: 1
};

// Tạo hiệu ứng hoa rơi
function createSakura(){
    const sakura = document.createElement("div");
    sakura.classList.add("sakura");
    sakura.style.left = Math.random() * window.innerWidth + "px";
    sakura.style.animationDuration = (3 + Math.random() * 5) + "s";
    sakura.style.background = `hsl(${Math.random() * 20 + 340}, 70%, 80%)`;
    document.body.appendChild(sakura);
    setTimeout(()=>{ sakura.remove(); },8000);
}
setInterval(createSakura, 300);

// Hàm cuộn lên đầu trang
function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Hàm cuộn đến sản phẩm
function scrollToProducts() {
    document.getElementById('productsContainer').scrollIntoView({ 
        behavior: 'smooth' 
    });
}

// Hiển thị loading
function showLoading(show = true) {
    document.getElementById('loadingOverlay').style.display = show ? 'flex' : 'none';
}

// Toggle sidebar
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('active');
    document.getElementById('overlay').classList.toggle('active');
}

// Close sidebar
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('active');
    document.getElementById('overlay').classList.remove('active');
    document.getElementById('profileModal').classList.remove('active');
    document.getElementById('paymentModal').classList.remove('active');
    document.getElementById('noticePopup').classList.remove('active');
}

// Show profile
function showProfile() {
    document.getElementById('profileModal').classList.add('active');
    document.getElementById('overlay').classList.add('active');
    document.getElementById('sidebar').classList.remove('active');
    document.getElementById('paymentModal').classList.remove('active');
    document.getElementById('noticePopup').classList.remove('active');
}

// Close profile
function closeProfile() {
    document.getElementById('profileModal').classList.remove('active');
    document.getElementById('overlay').classList.remove('active');
}

// Mở payment modal
function openPaymentModal(button) {
    const card = button.closest('.product-card');
    currentProduct.plan = card.dataset.plan;
    currentProduct.price = parseInt(card.dataset.price);
    currentProduct.quantity = 1;
    
    // Cập nhật modal
    document.getElementById('paymentProductName').textContent = currentProduct.plan;
    document.getElementById('quantityDisplay').textContent = currentProduct.quantity;
    
    // Cập nhật số dư hiện tại
    const currentBalance = document.getElementById('userBalance').textContent;
    document.getElementById('modalCurrentBalance').textContent = currentBalance;
    
    updatePriceDisplay();
    
    // Hiển thị modal
    document.getElementById('paymentModal').classList.add('active');
    document.getElementById('overlay').classList.add('active');
    document.getElementById('noticePopup').classList.remove('active');
}

// Đóng payment modal
function closePaymentModal() {
    document.getElementById('paymentModal').classList.remove('active');
    document.getElementById('overlay').classList.remove('active');
}

// Thay đổi số lượng
function changeQuantity(delta) {
    const newQuantity = currentProduct.quantity + delta;
    if (newQuantity >= 1 && newQuantity <= 100) {
        currentProduct.quantity = newQuantity;
        document.getElementById('quantityDisplay').textContent = newQuantity;
        updatePriceDisplay();
    }
}

// Cập nhật hiển thị giá
function updatePriceDisplay() {
    const totalPrice = currentProduct.price * currentProduct.quantity;
    
    document.getElementById('unitPriceDisplay').textContent = formatMoney(currentProduct.price);
    document.getElementById('quantityPriceDisplay').textContent = currentProduct.quantity;
    document.getElementById('finalPriceDisplay').textContent = formatMoney(totalPrice);
}

// Format tiền
function formatMoney(amount) {
    return amount.toLocaleString('vi-VN') + '₫';
}

// Xác nhận thanh toán
async function confirmPayment() {
    const finalPrice = currentProduct.price * currentProduct.quantity;
    const currentBalance = parseInt(document.getElementById('userBalance').textContent.replace(/[^\d]/g, '') || '0');
    
    // Kiểm tra số dư
    if (currentBalance < finalPrice) {
        const need = finalPrice - currentBalance;
        Swal.fire({
            title: 'Số dư không đủ!',
            html: `Bạn cần <b style="color:#ffae00">${formatMoney(need)}</b> nữa để thanh toán.<br>Vui lòng nạp thêm tiền.`,
            icon: 'error',
            confirmButtonText: 'Nạp tiền ngay',
            confirmButtonColor: '#00ff9d',
            showCancelButton: true,
            cancelButtonText: 'Đóng',
            background: '#0b1c45',
            color: '#fff'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'nap.php';
            }
        });
        return;
    }
    
    // Xác nhận thanh toán
    const result = await Swal.fire({
        title: 'Xác nhận thanh toán?',
        html: `
            <div style="text-align: left;">
                <p>Sản phẩm: <b>${currentProduct.plan}</b></p>
                <p>Số lượng: <b>${currentProduct.quantity}</b></p>
                <p>Đơn giá: <b style="color:#ffae00">${formatMoney(currentProduct.price)}</b></p>
                <p>Tổng thanh toán: <b style="color:#00ff9d">${formatMoney(finalPrice)}</b></p>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Thanh toán',
        cancelButtonText: 'Hủy',
        confirmButtonColor: '#00ff9d',
        background: '#0b1c45',
        color: '#fff'
    });
    
    if (!result.isConfirmed) return;
    
    // Disable nút và hiện loading
    document.getElementById('confirmPaymentBtn').disabled = true;
    showLoading(true);
    
    // Tạo form data
    const formData = new FormData();
    formData.append('action', 'purchase');
    formData.append('plan', currentProduct.plan);
    formData.append('price', currentProduct.price);
    formData.append('quantity', currentProduct.quantity);
    
    try {
        // Gửi request mua hàng đến file purchase.php
        const response = await fetch('purchase.php', {
            method: 'POST',
            body: formData
        });
        
        // Kiểm tra response
        const responseText = await response.text();
        console.log('Response:', responseText);
        
        try {
            const data = JSON.parse(responseText);
            
            if (data.status === 'success') {
                // Cập nhật số dư mới
                document.getElementById('userBalance').innerText = formatMoney(data.newBalance);
                
                // Cập nhật số dư trong sidebar
                const sidebarBalance = document.querySelector('.sidebar-balance-value');
                if (sidebarBalance) {
                    sidebarBalance.innerText = formatMoney(data.newBalance);
                }
                
                // Đóng modal thanh toán
                closePaymentModal();
                
                // Hiển thị thông báo thành công
                Swal.fire({
                    title: 'Thanh toán thành công!',
                    html: `
                        <div style="text-align: center;">
                            <i class="fas fa-check-circle" style="font-size: 60px; color: #00ff9d; margin-bottom: 15px;"></i>
                            <p style="font-size: 18px; margin-bottom: 10px;">Cảm ơn bạn đã mua hàng!</p>
                            <p style="color: #00ff9d; font-size: 16px;">Sản phẩm: ${data.plan}</p>
                            <p style="color: #ffae00; font-size: 16px;">Số lượng: ${data.quantity}</p>
                            <p style="color: #00ffcc; font-size: 16px;">Tổng tiền: ${formatMoney(data.total)}</p>
                        </div>
                    `,
                    icon: 'success',
                    showConfirmButton: true,
                    confirmButtonText: 'Xem chi tiết',
                    confirmButtonColor: '#00ff9d',
                    showCancelButton: true,
                    cancelButtonText: 'Đóng',
                    background: '#0b1c45',
                    color: '#fff',
                    allowOutsideClick: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Chuyển sang trang thành công
                        window.location.href = 'muahangthanhcong.php';
                    } else {
                        showLoading(false);
                    }
                });
                
            } else {
                Swal.fire({
                    title: 'Thất bại!',
                    text: data.message || 'Có lỗi xảy ra, vui lòng thử lại sau.',
                    icon: 'error',
                    confirmButtonText: 'Đóng',
                    confirmButtonColor: '#ff4444',
                    background: '#0b1c45',
                    color: '#fff'
                });
            }
        } catch (e) {
            console.error('JSON Parse Error:', e);
            console.log('Response Text:', responseText);
            Swal.fire({
                title: 'Lỗi!',
                text: 'Server trả về dữ liệu không hợp lệ',
                icon: 'error',
                confirmButtonText: 'Đóng',
                background: '#0b1c45',
                color: '#fff'
            });
        }
    } catch (error) {
        console.error('Fetch Error:', error);
        Swal.fire({
            title: 'Lỗi!',
            text: 'Không thể kết nối đến server',
            icon: 'error',
            confirmButtonText: 'Đóng',
            confirmButtonColor: '#ff4444',
            background: '#0b1c45',
            color: '#fff'
        });
    } finally {
        document.getElementById('confirmPaymentBtn').disabled = false;
        showLoading(false);
    }
}

// Lấy IP và thiết bị
fetch('https://api.ipify.org?format=json')
    .then(r => r.json())
    .then(d => document.getElementById('userIP').innerText = d.ip)
    .catch(() => document.getElementById('userIP').innerText = 'Không xác định');

document.getElementById('userDevice').innerText = navigator.userAgent.includes('Mobile') ? 'Điện thoại' : 'Máy tính';

// Đóng modal khi click overlay
document.getElementById('overlay').addEventListener('click', function() {
    closeSidebar();
});

// Hàm hiển thị popup thông báo
function showNoticePopup() {
    document.getElementById('noticePopup').classList.add('active');
    document.getElementById('overlay').classList.add('active');
    document.getElementById('sidebar').classList.remove('active');
    document.getElementById('profileModal').classList.remove('active');
    document.getElementById('paymentModal').classList.remove('active');
}

// Hàm đóng popup thông báo
function closeNoticePopup() {
    document.getElementById('noticePopup').classList.remove('active');
    document.getElementById('overlay').classList.remove('active');
}

// Tự động hiển thị popup khi vào trang (có thể bật/tắt)
window.addEventListener('load', function() {
    // Bỏ comment dòng dưới nếu muốn tự động hiện popup khi vào trang
    // setTimeout(showNoticePopup, 1000);
});
</script>

</body>
</html>