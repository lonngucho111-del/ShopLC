<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Đường dẫn file JSON
$dataFile = 'data/users.json';

// Kiểm tra và tạo file nếu chưa tồn tại
if (!file_exists('data')) {
    mkdir('data', 0777, true);
}
if (!file_exists($dataFile)) {
    file_put_contents($dataFile, json_encode([]));
}

// Hàm lấy dữ liệu
function getUsers($file) {
    $json = file_get_contents($file);
    return json_decode($json, true) ?? [];
}

// Lấy action từ request
$action = $_POST['action'] ?? '';
$users = getUsers($dataFile);

// --- XỬ LÝ ĐĂNG KÝ ---
if ($action === 'register') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate dữ liệu đầu vào
    if (empty($username) || empty($email) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Vui lòng điền đầy đủ thông tin.']);
        exit();
    }
    
    if (strlen($password) < 6) {
        echo json_encode(['status' => 'error', 'message' => 'Mật khẩu phải có ít nhất 6 ký tự.']);
        exit();
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Email không hợp lệ.']);
        exit();
    }

    // Kiểm tra trùng lặp
    foreach ($users as $user) {
        if ($user['username'] === $username) {
            echo json_encode(['status' => 'error', 'message' => 'Tên tài khoản đã tồn tại.']);
            exit();
        }
        if ($user['email'] === $email) {
            echo json_encode(['status' => 'error', 'message' => 'Email này đã được sử dụng.']);
            exit();
        }
    }

    // Tạo ID mới và Mã hóa mật khẩu
    $newId = count($users) > 0 ? max(array_column($users, 'id')) + 1 : 1;
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Cấu trúc dữ liệu người dùng (ĐÃ LOẠI BỎ device_lock)
    $newUser = [
        'id' => $newId,
        'username' => $username,
        'email' => $email,
        'password' => $hashed_password,
        'balance' => 0,
        'isActive' => false,
        'key' => null,
        'expiryDate' => null,
        'created_at' => date('Y-m-d H:i:s'),
        'last_login' => null,
        'history' => []
    ];

    // Lưu vào mảng và ghi file
    $users[] = $newUser;
    
    if (file_put_contents($dataFile, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
        echo json_encode(['status' => 'success', 'message' => 'Đăng ký thành công! Đang chuyển hướng...']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Lỗi ghi dữ liệu vào hệ thống.']);
    }
    exit();
}

// --- XỬ LÝ ĐĂNG NHẬP (ĐÃ LOẠI BỎ KIỂM TRA THIẾT BỊ) ---
if ($action === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate dữ liệu đầu vào
    if (empty($email) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Vui lòng nhập email và mật khẩu.']);
        exit();
    }
    
    $foundUser = false;
    $userFound = null;
    $userIndex = -1;
    
    // Tìm user theo email hoặc username
    foreach ($users as $index => $user) {
        if (($user['email'] === $email || $user['username'] === $email)) {
            $userFound = $user;
            $userIndex = $index;
            $foundUser = true;
            break;
        }
    }
    
    if (!$foundUser) {
        echo json_encode(['status' => 'error', 'message' => 'Tài khoản không tồn tại.']);
        exit();
    }
    
    // Kiểm tra mật khẩu
    if (!password_verify($password, $userFound['password'])) {
        echo json_encode(['status' => 'error', 'message' => 'Mật khẩu không chính xác.']);
        exit();
    }
    
    // Cập nhật thông tin đăng nhập
    $users[$userIndex]['last_login'] = date('Y-m-d H:i:s');
    
    // Lưu session
    $_SESSION['user_id'] = $users[$userIndex]['id'];
    $_SESSION['username'] = $users[$userIndex]['username'];
    $_SESSION['email'] = $users[$userIndex]['email'];
    $_SESSION['balance'] = $users[$userIndex]['balance'];
    $_SESSION['isActive'] = $users[$userIndex]['isActive'];
    $_SESSION['last_login'] = $users[$userIndex]['last_login'];
    
    // Ghi lại vào file JSON
    if (file_put_contents($dataFile, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
        echo json_encode([
            'status' => 'success', 
            'message' => 'Đăng nhập thành công!',
            'user' => [
                'username' => $users[$userIndex]['username'],
                'email' => $users[$userIndex]['email'],
                'balance' => $users[$userIndex]['balance'],
                'isActive' => $users[$userIndex]['isActive']
            ]
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Lỗi cập nhật dữ liệu.']);
    }
    exit();
}

// --- XỬ LÝ ĐĂNG XUẤT ---
if ($action === 'logout') {
    session_destroy();
    echo json_encode(['status' => 'success', 'message' => 'Đã đăng xuất!']);
    exit();
}

// --- XỬ LÝ KIỂM TRA SESSION ---
if ($action === 'check_session') {
    if (isset($_SESSION['user_id'])) {
        echo json_encode([
            'status' => 'success',
            'logged_in' => true,
            'user' => [
                'username' => $_SESSION['username'],
                'email' => $_SESSION['email'] ?? '',
                'balance' => $_SESSION['balance'] ?? 0
            ]
        ]);
    } else {
        echo json_encode(['status' => 'success', 'logged_in' => false]);
    }
    exit();
}

// Mặc định nếu không có action hợp lệ
echo json_encode(['status' => 'error', 'message' => 'Action không hợp lệ.']);
exit();
?>