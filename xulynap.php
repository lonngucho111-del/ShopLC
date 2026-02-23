<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// --- CẤU HÌNH TELEGRAM ---
$TELEGRAM_BOT_TOKEN = "8306342444:AAH0Elt2JV-c-flhY6fp0nU7pnkVFHQFtbg";
$ADMIN_CHAT_ID = "7560849341";

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['username'])) {
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng đăng nhập lại.']);
    exit;
}

$username = $_SESSION['username'];
$input = json_decode(file_get_contents('php://input'), true);

$amount = isset($input['amount']) ? (int)$input['amount'] : 0;
$transId = isset($input['transId']) ? trim($input['transId']) : '';

// 2. Validate dữ liệu
if ($amount < 10000) {
    echo json_encode(['status' => 'error', 'message' => 'Số tiền nạp tối thiểu là 10.000đ']);
    exit;
}

if (empty($transId)) {
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng nhập mã giao dịch hoặc nội dung chuyển khoản.']);
    exit;
}

// 3. Xử lý lưu file deposits.json
if (!file_exists('data')) {
    mkdir('data', 0777, true); // Tạo thư mục data nếu chưa có
}

$file = 'data/deposits.json';
$deposits = file_exists($file) ? json_decode(file_get_contents($file), true) : [];

// Kiểm tra spam (Nếu mã giao dịch này đang chờ duyệt thì không cho gửi tiếp)
foreach ($deposits as $d) {
    if ($d['trans_id'] === $transId && $d['status'] === 'pending') {
        echo json_encode(['status' => 'error', 'message' => 'Yêu cầu này đang chờ duyệt, vui lòng không spam!']);
        exit;
    }
}

// Tạo yêu cầu mới
$newRequest = [
    'username' => $username,
    'amount' => $amount,
    'trans_id' => $transId,
    'status' => 'pending', // Trạng thái: pending (chờ), approved (duyệt), rejected (hủy)
    'time' => date('Y-m-d H:i:s')
];

// Thêm vào danh sách
$deposits[] = $newRequest;

// Lưu lại file (Thêm JSON_UNESCAPED_UNICODE để không lỗi tiếng Việt)
if (file_put_contents($file, json_encode($deposits, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    
    // 4. Gửi thông báo Telegram cho Admin
    $msg_admin = "
🔔 <b>YÊU CẦU NẠP TIỀN MỚI</b>
👤 User: <code>$username</code>
💰 Số tiền: <b>" . number_format($amount) . " VNĐ</b>
📝 Nội dung: <code>$transId</code>
⏳ Trạng thái: <b>Chờ duyệt</b>
⏰ Thời gian: " . date('d/m/Y H:i:s');

    $url = "https://api.telegram.org/bot$TELEGRAM_BOT_TOKEN/sendMessage";
    $data_admin = ['chat_id' => $ADMIN_CHAT_ID, 'text' => $msg_admin, 'parse_mode' => 'HTML'];

    $options_admin = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data_admin),
        ],
    ];
    $context_admin = stream_context_create($options_admin);
    @file_get_contents($url, false, $context_admin);

    // 5. Trả về kết quả thành công
    echo json_encode(['status' => 'success', 'message' => 'Gửi yêu cầu thành công, vui lòng chờ Admin duyệt!']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống, không thể lưu yêu cầu.']);
}
?>