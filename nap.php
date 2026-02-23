<?php
session_start();
// Kiểm tra đăng nhập
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}
$username = $_SESSION['username'];

// Lấy số dư hiện tại
$userFile = 'data/users.json';
$balance = 0;
if (file_exists($userFile)) {
    $users = json_decode(file_get_contents($userFile), true);
    if (is_array($users)) {
        foreach ($users as $user) {
            if (isset($user['username']) && $user['username'] === $username) {
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
    <title>Nạp Tiền - SHOP LC</title>
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
            min-height: 100vh;
            padding: 20px;
        }

        /* HEADER giống .php */
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
            color: #fff;
            text-decoration: none;
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
        .main-container {
            width: 100%;
            max-width: 650px;
            margin: 0 auto;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Card */
        .card {
            background: rgba(0, 0, 0, 0.4);
            border-radius: 20px;
            padding: 30px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 0 30px rgba(0, 195, 255, 0.2);
            transition: 0.3s;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 0 30px rgba(0, 255, 157, 0.3);
            border-color: #00ff9d;
        }

        .card h3 {
            font-size: 1.5rem;
            margin-bottom: 25px;
            color: #ffd700;
            text-align: center;
            font-weight: 600;
            position: relative;
        }

        .card h3::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 2px;
            background: linear-gradient(90deg, transparent, #00ff9d, transparent);
        }

        /* Balance display */
        .balance-info {
            background: rgba(0, 195, 255, 0.1);
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 25px;
            text-align: center;
            border: 1px solid #00c3ff;
        }

        .balance-label {
            font-size: 14px;
            color: #8aa6ad;
            margin-bottom: 5px;
        }

        .balance-value {
            font-size: 28px;
            font-weight: 700;
            color: #00ff9d;
            text-shadow: 0 0 10px rgba(0, 255, 157, 0.3);
        }

        /* Input group */
        .input-group {
            position: relative;
            margin-bottom: 20px;
        }

        .input-group label {
            display: block;
            text-align: left;
            margin-bottom: 8px;
            font-size: 1rem;
            color: #ffd700;
            font-weight: 500;
        }

        .input-field {
            width: 100%;
            padding: 15px;
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            font-size: 1rem;
            outline: none;
            background: rgba(0, 0, 0, 0.3);
            color: #fff;
            transition: 0.3s;
            font-family: 'Poppins', sans-serif;
        }

        .input-field:focus {
            border-color: #00c3ff;
            box-shadow: 0 0 15px rgba(0, 195, 255, 0.3);
        }

        .input-field::placeholder {
            color: rgba(255, 255, 255, 0.3);
        }

        /* Buttons */
        .btn {
            width: 100%;
            margin-top: 12px;
            background: linear-gradient(90deg, #00c853, #00e676);
            border: none;
            color: white;
            font-weight: 600;
            padding: 15px;
            border-radius: 10px;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0, 230, 118, 0.4);
        }

        .btn:active {
            transform: translateY(1px);
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .success-btn {
            background: linear-gradient(90deg, #00c853, #00e676);
        }

        /* Step 2 */
        #step2-transfer-details {
            display: none;
        }

        .qr-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 25px;
        }

        .qr-image {
            max-width: 220px;
            width: 100%;
            border-radius: 15px;
            margin: 10px auto 15px;
            border: 2px solid #00ff9d;
            background: white;
            box-shadow: 0 0 20px rgba(0, 255, 157, 0.3);
            transition: 0.3s;
        }

        .qr-image:hover {
            transform: scale(1.05);
            box-shadow: 0 0 30px rgba(0, 255, 157, 0.5);
        }

        .qr-label {
            font-size: 0.9rem;
            color: #8aa6ad;
            margin-top: 5px;
            text-align: center;
        }

        .info-grid {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 20px;
        }

        .info-line {
            background: rgba(0, 0, 0, 0.3);
            padding: 15px 18px;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: 0.3s;
            position: relative;
            overflow: hidden;
        }

        .info-line::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(to bottom, #00ff9d, #00c3ff);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .info-line:hover::before {
            opacity: 1;
        }

        .info-line:hover {
            border-color: #00c3ff;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 195, 255, 0.2);
        }

        .info-line-content {
            flex-grow: 1;
        }

        .info-line-content span {
            color: #8aa6ad;
            font-size: 0.85rem;
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .info-line-content strong {
            color: #00ff9d;
            font-weight: 600;
            font-size: 1.1em;
            word-break: break-all;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-line-content strong i {
            color: #00ff9d;
        }

        .copy-btn {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            cursor: pointer;
            font-size: 0.9rem;
            padding: 8px 12px;
            border-radius: 6px;
            transition: 0.3s;
            margin-left: 10px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .copy-btn:hover {
            background: rgba(0, 195, 255, 0.2);
            color: #00ff9d;
            transform: translateY(-1px);
            border-color: #00c3ff;
        }

        .instruction-text {
            font-size: 0.9rem;
            color: #8aa6ad;
            margin-bottom: 20px;
            text-align: center;
            line-height: 1.5;
        }

        .divider {
            height: 1px;
            background: linear-gradient(to right, transparent, rgba(255, 255, 255, 0.1), transparent);
            margin: 20px 0;
        }

        #loading-info {
            color: #8aa6ad;
            text-align: center;
            padding: 20px;
        }

        /* Sidebar */
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

        /* Loading overlay */
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

        /* Hiệu ứng hạt trắng */
        .white-particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }

        .white-particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 50%;
            opacity: 0;
            animation: fall linear infinite;
            box-shadow: 0 0 6px rgba(255, 255, 255, 0.7);
        }

        .white-particle:nth-child(5n) { width: 2px; height: 2px; }
        .white-particle:nth-child(5n+1) { width: 3px; height: 3px; }
        .white-particle:nth-child(5n+2) { width: 1px; height: 1px; }
        .white-particle:nth-child(5n+3) { width: 4px; height: 4px; }
        .white-particle:nth-child(5n+4) { width: 2.5px; height: 2.5px; }

        @keyframes fall {
            0% { transform: translateY(-100px) translateX(0) rotate(0deg); opacity: 0; }
            10% { opacity: 0.8; }
            90% { opacity: 0.6; }
            100% { transform: translateY(100vh) translateX(20px) rotate(180deg); opacity: 0; }
        }

        /* Responsive */
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
            
            h1 {
                font-size: 2rem;
            }
            
            .card {
                padding: 20px;
            }
            
            .info-line {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .copy-btn {
                align-self: flex-end;
                margin-left: 0;
            }
        }
    </style>
</head>
<body>

<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner"></div>
</div>

<div class="white-particles" id="whiteParticles"></div>

<!-- Sidebar Menu -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-avatar"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
        <div class="sidebar-username"><?php echo htmlspecialchars($username); ?></div>
        <div class="sidebar-email"><?php echo isset($user['email']) ? htmlspecialchars($user['email']) : 'Chưa cập nhật'; ?></div>
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

<!-- Header -->
<div class="header">
    <div class="left">
        <div class="menu-icon" onclick="toggleSidebar()">☰</div>
        <div class="logo" onclick="scrollToTop()">🔥</div>
    </div>

    <div class="right">
        <div class="bell">🔔</div>
        <div class="avatar" onclick="showProfile()"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
        <div class="login" onclick="window.location.href='nap.php'">
            <h3><?php echo htmlspecialchars($username); ?></h3>
            <span id="userBalance"><?php echo number_format($balance); ?>đ</span>
        </div>
    </div>
</div>

<div class="back">
    <a href="key.php">
        <i class="fas fa-arrow-left"></i>
        Quay lại Trang Chủ
    </a>
</div>

<div class="header-container">
    <h1>NẠP <span class="accent">TIỀN</span></h1>
    <div class="subtitle">Nạp tiền vào tài khoản để mua key</div>
</div>

<div class="main-container">
    <div class="card">
        <div class="balance-info">
            <div class="balance-label">Số dư hiện tại của bạn</div>
            <div class="balance-value"><?php echo number_format($balance); ?>đ</div>
        </div>

        <div id="loading-info" style="display:none;">
            <i class="fas fa-spinner fa-spin"></i> Đang tải thông tin thanh toán...
        </div>

        <div id="step1-amount-input">
            <h3>Nhập Số Tiền Cần Nạp</h3>
            <div class="input-group">
                <label for="amount"><i class="fas fa-money-bill-wave"></i> Số Tiền (VNĐ)</label>
                <input type="number" id="amount" class="input-field" placeholder="Ví dụ: 50000" min="10000" step="1000">
            </div>
            <button class="btn" id="continue-btn">
                <i class="fas fa-credit-card"></i> Tiếp Tục
            </button>
        </div>
        
        <div id="step2-transfer-details">
            <h3>Thông Tin Chuyển Khoản</h3>
            
            <p class="instruction-text">Vui lòng chuyển khoản chính xác số tiền và nội dung dưới đây để hệ thống tự động xử lý giao dịch của bạn.</p>
            
            <div class="qr-container">
                <img src="https://i.postimg.cc/jdtwyYDW/IMG-3241.jpg" id="qr-image" alt="Mã QR Ngân hàng" class="qr-image">
                <div class="qr-label">Quét mã QR để chuyển khoản nhanh</div>
            </div>
            
            <div class="divider"></div>
            
            <div class="info-grid">
                <div class="info-line">
                    <div class="info-line-content">
                        <span><i class="fas fa-money-bill-wave"></i> Số tiền</span>
                        <strong id="display-amount">0 VNĐ</strong>
                    </div>
                </div>
                
                <div class="info-line">
                    <div class="info-line-content">
                        <span><i class="fas fa-university"></i> Số tài khoản</span>
                        <strong id="account-number">0935742761</strong>
                    </div>
                    <button class="copy-btn" onclick="copyToClipboard('account-number')" title="Sao chép số tài khoản">
                        <i class="fas fa-copy"></i> Sao chép
                    </button>
                </div>
                  
                <div class="info-line">
                    <div class="info-line-content">
                        <span><i class="fas fa-user-circle"></i> Chủ tài khoản</span>
                        <strong id="account-name">NGUYỄN THỊ ÚT</strong>
                    </div>
                    <button class="copy-btn" onclick="copyToClipboard('account-name')" title="Sao chép tên chủ tài khoản">
                        <i class="fas fa-copy"></i> Sao chép
                    </button>
                </div>
                
                <div class="info-line" id="transaction-content-line">
                    <div class="info-line-content">
                        <span><i class="fas fa-comment-dots"></i> Nội dung chuyển khoản</span>
                        <strong id="transaction-content-text">...</strong>
                    </div>
                    <button class="copy-btn" onclick="copyToClipboard('transaction-content-text')" title="Sao chép nội dung chuyển khoản">
                        <i class="fas fa-copy"></i> Sao chép
                    </button>
                </div>
            </div>
            
            <button id="confirm-btn" class="btn success-btn">
                <i class="fas fa-check-circle"></i> Tôi Đã Chuyển Khoản
            </button>
        </div>
    </div>
</div>

<script>
    // Tạo hiệu ứng hạt trắng
    function createWhiteParticles() {
        const container = document.getElementById('whiteParticles');
        const particleCount = 50;

        for (let i = 0; i < particleCount; i++) {
            const particle = document.createElement('div');
            particle.classList.add('white-particle');
            const left = Math.random() * 100;
            const duration = Math.random() * 10 + 5;
            const delay = Math.random() * 20;
            particle.style.left = `${left}%`;
            particle.style.animationDuration = `${duration}s`;
            particle.style.animationDelay = `${delay}s`;
            container.appendChild(particle);
        }
    }

    const bankInfo = {
        accountNumber: "0935742761",
        accountName: "NGUYỄN THỊ ÚT",
    };

    let transactionId = '';
    let transactionAmount = 0;
    let currentUser = "<?php echo $username; ?>";

    // Generate Transaction ID
    function generateTransactionId() {
        const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        let result = '';
        for (let i = 0; i < 8; i++) {
            result += characters.charAt(Math.floor(Math.random() * characters.length));
        }
        return 'NAP' + result;
    }

    // Copy to clipboard
    function copyToClipboard(elementId) {
        let textToCopy;
        if (elementId === 'display-amount') {
            textToCopy = transactionAmount.toString();
        } else if (elementId === 'account-number') {
            textToCopy = bankInfo.accountNumber;
        } else if (elementId === 'account-name') {
            textToCopy = bankInfo.accountName;
        } else if (elementId === 'transaction-content-text') {
            textToCopy = transactionId;
        }
        
        navigator.clipboard.writeText(textToCopy.trim()).then(() => {
            Swal.fire({
                icon: 'success',
                title: 'Đã sao chép!',
                text: 'Nội dung đã được copy vào clipboard',
                showConfirmButton: false,
                timer: 1500,
                background: '#0b1c45',
                color: '#fff'
            });
        }).catch(err => {
            console.error(err);
        });
    }

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

    // Show profile
    function showProfile() {
        Swal.fire({
            title: 'Thông tin tài khoản',
            html: `
                <div style="text-align: left;">
                    <p><strong>Tên đăng nhập:</strong> ${currentUser}</p>
                    <p><strong>Số dư:</strong> <?php echo number_format($balance); ?>đ</p>
                </div>
            `,
            icon: 'info',
            confirmButtonText: 'Đóng',
            background: '#0b1c45',
            color: '#fff'
        });
    }

    // Scroll to top
    function scrollToTop() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    document.addEventListener('DOMContentLoaded', function() {
        createWhiteParticles();

        const step1 = document.getElementById('step1-amount-input');
        const step2 = document.getElementById('step2-transfer-details');
        const amountInput = document.getElementById('amount');
        const continueBtn = document.getElementById('continue-btn');
        const confirmBtn = document.getElementById('confirm-btn');
        const loadingInfo = document.getElementById('loading-info');
        
        // Xử lý nút Tiếp Tục
        continueBtn.addEventListener('click', function() {
            const amountValue = parseInt(amountInput.value, 10);
            
            if (isNaN(amountValue) || amountValue < 10000) { 
                Swal.fire({
                    title: 'Số tiền không hợp lệ!',
                    text: 'Vui lòng nhập số tiền tối thiểu là 10,000 VNĐ.',
                    icon: 'warning',
                    confirmButtonText: 'Đóng',
                    background: '#0b1c45',
                    color: '#fff'
                }); 
                return; 
            }

            transactionId = generateTransactionId();
            transactionAmount = amountValue;

            document.getElementById('display-amount').textContent = transactionAmount.toLocaleString('vi-VN') + ' VNĐ';
            document.getElementById('transaction-content-text').textContent = transactionId;
            
            // Show QR code dynamically (VietQR API)
const qrUrl = https://img.vietqr.io/image/MB-0941928807-compact2.png?amount=${transactionAmount}&addInfo=${transactionId}&accountName=DOAN%20THI%20NHUNG;
            document.getElementById('qr-image').src = qrUrl;

            step1.style.display = 'none';
            step2.style.display = 'block';
        });

        // Xử lý nút Xác nhận đã chuyển khoản
        confirmBtn.addEventListener('click', async function() {
            // Disable nút để tránh spam
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
            loadingInfo.style.display = 'block';

            try {
                // Gửi request về xulynap.php
                const response = await fetch('xulynap.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        amount: transactionAmount,
                        transId: transactionId
                    })
                });

                const data = await response.json();

                if (data.status === 'success') {
                    Swal.fire({
                        title: 'Yêu Cầu Đã Được Gửi!',
                        text: 'Vui lòng chờ Admin duyệt giao dịch. Số dư của bạn sẽ được cộng sau ít phút.',
                        icon: 'success',
                        confirmButtonText: 'Về trang chủ',
                        background: '#0b1c45',
                        color: '#fff'
                    }).then(() => {
                        window.location.href = 'key.php';
                    });
                } else {
                    Swal.fire({
                        title: 'Lỗi',
                        text: data.message,
                        icon: 'error',
                        confirmButtonText: 'Đóng',
                        background: '#0b1c45',
                        color: '#fff'
                    });
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = '<i class="fas fa-check-circle"></i> Tôi Đã Chuyển Khoản';
                }
            } catch (error) {
                console.error(error);
                Swal.fire({
                    title: 'Lỗi kết nối',
                    text: 'Không thể gửi yêu cầu đến server.',
                    icon: 'error',
                    confirmButtonText: 'Đóng',
                    background: '#0b1c45',
                    color: '#fff'
                });
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = '<i class="fas fa-check-circle"></i> Tôi Đã Chuyển Khoản';
            } finally {
                loadingInfo.style.display = 'none';
            }
        });

        // Đóng modal khi click overlay
        document.getElementById('overlay').addEventListener('click', function() {
            closeSidebar();
        });
    });
</script>
</body>
</html>