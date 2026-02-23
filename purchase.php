<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// --- CẤU HÌNH TELEGRAM ---
$TELEGRAM_BOT_TOKEN = "8226633451:AAHI92QpsP6m294ebEqPXRjX7j6tfsLQK8I";
$ADMIN_CHAT_ID = "6902698316";

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['username'])) {
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng đăng nhập lại.']);
    exit;
}

$username = trim($_SESSION['username']);

// 2. Lấy dữ liệu từ POST
$plan = isset($_POST['plan']) ? $_POST['plan'] : '';
$price = isset($_POST['price']) ? intval($_POST['price']) : 0;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

// 3. Kiểm tra dữ liệu
if (empty($plan) || $price <= 0 || $quantity <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Dữ liệu không hợp lệ.']);
    exit;
}

$totalPrice = $price * $quantity;

// 4. Load dữ liệu users
$usersFile = 'data/users.json';

if (!file_exists($usersFile)) {
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: Không tìm thấy dữ liệu người dùng.']);
    exit;
}

$usersData = json_decode(file_get_contents($usersFile), true);

if (!is_array($usersData)) {
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: Dữ liệu người dùng bị hỏng.']);
    exit;
}

// 5. Tìm User
$userIndex = null;
$currentBalance = 0;

foreach ($usersData as $index => $user) {
    if (isset($user['username']) && $user['username'] === $username) {
        $currentBalance = isset($user['balance']) ? (int)$user['balance'] : 0;
        $userIndex = $index;
        break;
    }
}

// 6. Kiểm tra số dư
if ($userIndex === null) {
    echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy thông tin tài khoản.']);
    exit;
}

if ($currentBalance < $totalPrice) {
    $need = $totalPrice - $currentBalance;
    echo json_encode([
        'status' => 'error', 
        'message' => "Số dư không đủ. Bạn cần nạp thêm " . number_format($need) . "đ."
    ]);
    exit;
}

// 7. Xử lý giao dịch
// 7.1 Trừ tiền
$newBalance = $currentBalance - $totalPrice;
$usersData[$userIndex]['balance'] = $newBalance;

// 7.2 Lưu lịch sử mua hàng (CHỈ lưu thông tin sản phẩm, KHÔNG tạo key)
if (!isset($usersData[$userIndex]['history']) || !is_array($usersData[$userIndex]['history'])) {
    $usersData[$userIndex]['history'] = [];
}

$historyItem = [
    'date' => date('Y-m-d H:i:s'),
    'plan' => $plan,
    'price' => $totalPrice,
    'quantity' => $quantity,
    'status' => 'success'
];

// Thêm vào đầu mảng để hiển thị mới nhất lên trên
array_unshift($usersData[$userIndex]['history'], $historyItem);

// 8. Ghi file
$saveUsers = file_put_contents($usersFile, json_encode($usersData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

if ($saveUsers) {
    
    // Cập nhật session
    $_SESSION['balance'] = $newBalance;
    
    // Lưu thông tin vào session để hiển thị trang thành công
    $_SESSION['purchase_success'] = [
        'product' => $plan,
        'price' => $totalPrice,
        'quantity' => $quantity
    ];

    // 9. Gửi thông báo Telegram (KHÔNG gửi key)
    $maGD = "#" . strtoupper(substr(md5(uniqid() . rand()), 0, 10));
    $timeNow = date('Y/m/d H:i:s');
    
    $msg = "
✦━─━─━✦ ✦━─━─━✦
     <b>THÔNG BÁO MUA HÀNG</b>
✦━─━─━✦ ✦━─━─━✦

👤 Khách hàng : <b>$username</b>  
📦 Sản phẩm   : $plan  
🔢 Số lượng   : $quantity
💰 Thanh toán : " . number_format($totalPrice) . "đ  
⏰ Thời gian  : [$timeNow]  
🆔 Mã GD      : $maGD

⚠️ <b>Vui lòng liên hệ ADMIN để nhận KEY</b>
    ";

    $url = "https://api.telegram.org/bot$TELEGRAM_BOT_TOKEN/sendMessage";
    $data = ['chat_id' => $ADMIN_CHAT_ID, 'text' => $msg, 'parse_mode' => 'HTML'];
    
    $options = ['http' => [
        'header' => "Content-type: application/x-www-form-urlencoded\r\n",
        'method' => 'POST',
        'content' => http_build_query($data)
    ]];
    
    $context = stream_context_create($options);
    @file_get_contents($url, false, $context);

    // 10. Phản hồi thành công
    echo json_encode([
        'status' => 'success',
        'message' => 'Mua hàng thành công!',
        'newBalance' => $newBalance,
        'plan' => $plan,
        'total' => $totalPrice,
        'quantity' => $quantity
    ]);
    exit;

} else {
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: Không thể lưu dữ liệu.']);
    exit;
}
?>