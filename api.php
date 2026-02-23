<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Định nghĩa đường dẫn file dữ liệu
$usersFile = 'data/users.json';
$keysFile = 'data/keys.json';

// Tạo thư mục data nếu chưa có
if (!file_exists('data')) {
    mkdir('data', 0777, true);
}

// Tạo file users.json nếu chưa có
if (!file_exists($usersFile)) {
    file_put_contents($usersFile, json_encode([]));
}

// Hàm đọc dữ liệu JSON
function getData($file) {
    if (!file_exists($file)) return [];
    return json_decode(file_get_contents($file), true) ?? [];
}

// Hàm lưu dữ liệu JSON
function saveData($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ============ PHẦN 1: XỬ LÝ ĐĂNG KÝ ============
if ($action === 'register') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate dữ liệu
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

    $users = getData($usersFile);
    
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

    // Tạo user mới
    $newId = count($users) > 0 ? max(array_column($users, 'id')) + 1 : 1;
    
    $newUser = [
        'id' => $newId,
        'username' => $username,
        'email' => $email,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'balance' => 0,
        'is_active' => false,
        'expiry_date' => null,
        'current_key' => null,
        'created_at' => date('Y-m-d H:i:s'),
        'last_login' => null,
        'history' => []
    ];

    $users[] = $newUser;
    saveData($usersFile, $users);
    
    echo json_encode(['status' => 'success', 'message' => 'Đăng ký thành công!']);
    exit();
}

// ============ PHẦN 2: XỬ LÝ ĐĂNG NHẬP ============
if ($action === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Vui lòng nhập email và mật khẩu.']);
        exit();
    }
    
    $users = getData($usersFile);
    $foundUser = null;
    $userIndex = -1;
    
    // Tìm user theo email hoặc username
    foreach ($users as $index => $user) {
        if ($user['email'] === $email || $user['username'] === $email) {
            if (password_verify($password, $user['password'])) {
                $foundUser = $user;
                $userIndex = $index;
                break;
            }
        }
    }
    
    if (!$foundUser) {
        echo json_encode(['status' => 'error', 'message' => 'Email hoặc mật khẩu không chính xác.']);
        exit();
    }
    
    // Cập nhật thông tin đăng nhập
    $users[$userIndex]['last_login'] = date('Y-m-d H:i:s');
    saveData($usersFile, $users);
    
    // Lưu session
    $_SESSION['user_id'] = $foundUser['id'];
    $_SESSION['username'] = $foundUser['username'];
    $_SESSION['email'] = $foundUser['email'];
    $_SESSION['balance'] = $foundUser['balance'] ?? 0;
    $_SESSION['is_active'] = $foundUser['is_active'] ?? false;
    
    echo json_encode([
        'status' => 'success', 
        'message' => 'Đăng nhập thành công!',
        'user' => [
            'username' => $foundUser['username'],
            'email' => $foundUser['email'],
            'balance' => $foundUser['balance'] ?? 0,
            'is_active' => $foundUser['is_active'] ?? false
        ]
    ]);
    exit();
}

// ============ PHẦN 3: LẤY THÔNG TIN USER ============
if ($action === 'get_user_info') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Chưa đăng nhập']);
        exit();
    }

    $users = getData($usersFile);
    $currentUser = null;

    foreach ($users as $user) {
        if ($user['id'] == $_SESSION['user_id']) {
            $currentUser = $user;
            break;
        }
    }

    if ($currentUser) {
        unset($currentUser['password']);
        echo json_encode(['status' => 'success', 'user' => $currentUser]);
    } else {
        session_destroy();
        echo json_encode(['status' => 'error', 'message' => 'User không tồn tại']);
    }
    exit();
}

// ============ PHẦN 4: KÍCH HOẠT KEY ============
if ($action === 'activate_key') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Vui lòng đăng nhập lại']);
        exit();
    }

    $keyInput = trim($_POST['key'] ?? '');
    if (empty($keyInput)) {
        echo json_encode(['status' => 'error', 'message' => 'Vui lòng nhập Key']);
        exit();
    }

    // Tạo file keys.json mẫu nếu chưa có
    if (!file_exists($keysFile)) {
        $sampleKeys = [
            ['code' => 'TEST-KEY-1NGAY', 'days' => 1, 'is_used' => false],
            ['code' => 'TEST-KEY-7NGAY', 'days' => 7, 'is_used' => false],
            ['code' => 'TEST-KEY-30NGAY', 'days' => 30, 'is_used' => false]
        ];
        saveData($keysFile, $sampleKeys);
    }

    $keys = getData($keysFile);
    $users = getData($usersFile);
    
    $validKeyIndex = -1;
    $keyDays = 0;

    // Tìm Key
    foreach ($keys as $index => $k) {
        if ($k['code'] === $keyInput) {
            if ($k['is_used']) {
                echo json_encode(['status' => 'error', 'message' => 'Key này đã được sử dụng!']);
                exit();
            }
            $validKeyIndex = $index;
            $keyDays = $k['days'];
            break;
        }
    }

    if ($validKeyIndex === -1) {
        echo json_encode(['status' => 'error', 'message' => 'Key không tồn tại!']);
        exit();
    }

    // Cập nhật User
    $updatedUser = null;
    foreach ($users as &$user) {
        if ($user['id'] == $_SESSION['user_id']) {
            $currentExpiry = isset($user['expiry_date']) ? strtotime($user['expiry_date']) : time();
            if ($currentExpiry < time()) $currentExpiry = time();

            $newExpiry = $currentExpiry + ($keyDays * 24 * 60 * 60);
            
            $user['expiry_date'] = date('Y-m-d H:i:s', $newExpiry);
            $user['is_active'] = true;
            $user['current_key'] = $keyInput;
            
            // Cập nhật session
            $_SESSION['is_active'] = true;
            $_SESSION['expiry_date'] = $user['expiry_date'];
            
            $updatedUser = $user;
            break;
        }
    }

    if ($updatedUser) {
        // Đánh dấu key đã dùng
        $keys[$validKeyIndex]['is_used'] = true;
        $keys[$validKeyIndex]['used_by'] = $_SESSION['username'];
        $keys[$validKeyIndex]['used_at'] = date('Y-m-d H:i:s');

        saveData($usersFile, $users);
        saveData($keysFile, $keys);

        echo json_encode([
            'status' => 'success', 
            'message' => "Kích hoạt thành công! Thêm $keyDays ngày sử dụng.",
            'expiry_date' => $updatedUser['expiry_date']
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Lỗi cập nhật người dùng']);
    }
    exit();
}

// ============ PHẦN 5: ĐĂNG XUẤT ============
if ($action === 'logout') {
    session_destroy();
    echo json_encode(['status' => 'success', 'message' => 'Đã đăng xuất']);
    exit();
}

// Mặc định nếu không có action hợp lệ
echo json_encode(['status' => 'error', 'message' => 'Action không hợp lệ.']);
exit();
?>