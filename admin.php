<?php
session_start();
error_reporting(0); 

$ADMIN_KEY_SECRET = "LCDZ123"; 
$files = [
    'users'    => 'data/users.json',
    'keys'     => 'data/keys.json',
    'deposits' => 'data/deposits.json',
    'settings' => 'data/settings.json'
];

// --- HÀM HỖ TRỢ ---
function loadJson($file) { return file_exists($file) ? json_decode(file_get_contents($file), true) : []; }
function saveJson($file, $data) { file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); }

// --- API BACKEND ---
if (isset($_GET['api']) && isset($_SESSION['is_admin'])) {
    header('Content-Type: application/json');
    $action = $_GET['api'];
    $input = json_decode(file_get_contents('php://input'), true);
    
    $users = loadJson($files['users']);
    $keys = loadJson($files['keys']);
    $deposits = loadJson($files['deposits']);
    $settings = loadJson($files['settings']);
    
    $response = ['status' => 'error', 'message' => 'Hành động không hợp lệ'];

    switch ($action) {
        // 1. DASHBOARD DATA
        case 'get_dashboard_data':
            $totalMoney = 0; 
            if(is_array($users)) {
                foreach ($users as $u) $totalMoney += isset($u['balance']) ? (int)$u['balance'] : 0;
            }

            $pending = 0; $revenue = 0; $chartData = [];
            $today = date('Y-m-d'); $todayRevenue = 0;
            
            // Xử lý dữ liệu nạp tiền và biểu đồ
            if(is_array($deposits)) {
                foreach ($deposits as $d) {
                    if($d['status'] === 'pending') $pending++;
                    if($d['status'] === 'approved') {
                        $revenue += (int)$d['amount'];
                        // Lấy ngày từ chuỗi thời gian (Y-m-d H:i:s)
                        $datePart = explode(' ', $d['time'])[0]; 
                        
                        if($datePart === $today) $todayRevenue += (int)$d['amount'];
                        
                        if(!isset($chartData[$datePart])) $chartData[$datePart] = 0;
                        $chartData[$datePart] += (int)$d['amount'];
                    }
                }
            }
            ksort($chartData); // Sắp xếp ngày tăng dần
            
            // Thống kê key đã bán
            $soldKeys = 0;
            if(is_array($keys)) {
                foreach($keys as $k) { 
                    if((isset($k['used']) && $k['used'] === true) || (isset($k['trang_thai']) && $k['trang_thai'] === 'used')) {
                        $soldKeys++; 
                    }
                }
            }

            $response = ['status' => 'success', 'data' => [
                'total_users' => count($users),
                'total_balance_user' => $totalMoney,
                'total_keys' => count($keys),
                'sold_keys' => $soldKeys,
                'pending_deposits' => $pending,
                'total_revenue' => $revenue,
                'chart_labels' => array_keys($chartData),
                'chart_values' => array_values($chartData)
            ]];
            break;

        // 2. GET LIST DATA
        case 'get_data_list':
            $type = $input['type'];
            if($type === 'deposits') $data = is_array($deposits) ? array_values(array_reverse($deposits)) : [];
            elseif($type === 'users') $data = is_array($users) ? array_values($users) : [];
            elseif($type === 'keys') $data = is_array($keys) ? array_values(array_reverse($keys)) : [];
            $response = ['status' => 'success', 'data' => $data];
            break;

        // 3. PROCESS DEPOSIT
        case 'process_deposit':
            $transId = $input['trans_id']; $decision = $input['decision']; $found = false;
            foreach ($deposits as &$d) {
                if ($d['trans_id'] === $transId && $d['status'] === 'pending') {
                    $found = true;
                    if ($decision === 'approve') {
                        $username = $d['username'];
                        $userFound = false;
                        foreach($users as &$u) {
                            if($u['username'] === $username || $u['email'] === $username) {
                                $u['balance'] += (int)$d['amount'];
                                $userFound = true; break;
                            }
                        }
                        if(!$userFound) { 
                             $users[] = ['id'=>count($users)+1, 'username'=>$username, 'email'=>$username, 'balance'=>(int)$d['amount'], 'isActive'=>false];
                        }
                        $d['status'] = 'approved';
                        saveJson($files['users'], $users);
                        
                        $botToken = $settings['bot_token'] ?? ''; $chatId = $settings['chat_id'] ?? '';
                        if($botToken && $chatId) {
                            $amt = number_format($d['amount']); $timeNow = date('Y/m/d H:i:s');
                          $msg = "
✧═════• ༺༻ •═════✧
<b>Thông Báo Nạp Tiền</b>
✧═════• ༺༻ •═════✧
- Username: $username
- Số Tiền Nạp: $amt VNĐ
- Phương Thức Nạp: MBbank
- Thực Nhận: $amt VNĐ
- Time: $timeNow
- Truy Cập Tool: <a href='https://toolgamephuxuan.site/'>toolgamephuxuan.site</a>
✧═════• ༺༻ •═════✧";
                            // ---------------------

                            @file_get_contents("https://api.telegram.org/bot$botToken/sendMessage?chat_id=$chatId&parse_mode=HTML&text=".urlencode($msg));
                        }
                    } else { $d['status'] = 'rejected'; }
                }
            }
            if($found) { saveJson($files['deposits'], $deposits); $response = ['status' => 'success', 'message' => 'Đã xử lý!']; }
            break;

        // 4. MANAGE USER (Add/Sub Money, Ban, Delete, Reset Device)
        case 'manage_user':
            $uName = $input['username']; $act = $input['act']; $val = (int)$input['value'];
            $foundIdx = -1;
            
            foreach($users as $idx => &$u) {
                if($u['username'] === $uName) {
                    $foundIdx = $idx;
                    
                    if($act === 'add_money') {
                        $u['balance'] += $val;
                        $response = ['status' => 'success', 'message' => "Đã cộng ".number_format($val)."đ"];
                    }
                    if($act === 'sub_money') {
                        $u['balance'] = max(0, $u['balance'] - $val);
                        $response = ['status' => 'success', 'message' => "Đã trừ ".number_format($val)."đ"];
                    }
                    if($act === 'ban_user') {
                        $u['isBanned'] = !($u['isBanned'] ?? false);
                        $msg = $u['isBanned'] ? "Đã KHÓA tài khoản" : "Đã MỞ KHÓA tài khoản";
                        $response = ['status' => 'success', 'message' => $msg];
                    }
                    if($act === 'reset_device') {
                        $u['device_lock'] = null; // Xóa khóa thiết bị user
                        $response = ['status' => 'success', 'message' => "Đã reset thiết bị cho User này!"];
                    }
                    break;
                }
            }
            
            if($foundIdx > -1) {
                if($act === 'delete_user') {
                    array_splice($users, $foundIdx, 1);
                    $response = ['status' => 'success', 'message' => 'Đã xóa người dùng'];
                }
                saveJson($files['users'], $users);
            } else {
                $response = ['status' => 'error', 'message' => 'Không tìm thấy user'];
            }
            break;

        // 5. MANAGE KEY (Create, Delete, Reset Device)
        case 'create_key':
            $plan = $input['plan']; $days = (int)$input['days'];
            $newKey = 'KEY-' . strtoupper(substr(md5(uniqid()), 0, 4) . '-' . substr(md5(time()), 0, 4));
            $keys[$newKey] = ['key' => $newKey, 'plan' => $plan, 'so_ngay' => $days, 'trang_thai' => 'active', 'ngay_tao' => date('Y-m-d H:i:s'), 'used' => false, 'device_lock' => null];
            saveJson($files['keys'], $keys); 
            $response = ['status' => 'success', 'message' => 'Tạo Key thành công', 'key' => $newKey];
            break;
            
        case 'reset_key_device':
            $k = $input['key'];
            if(isset($keys[$k])) {
                $keys[$k]['device_lock'] = null; // Xóa khóa thiết bị key
                saveJson($files['keys'], $keys);
                $response = ['status' => 'success', 'message' => 'Đã reset thiết bị cho Key này (0/1)'];
            }
            break;

        case 'delete_key':
            $k = $input['key'];
            if(isset($keys[$k])) { unset($keys[$k]); saveJson($files['keys'], $keys); $response = ['status' => 'success', 'message' => 'Đã xóa Key']; }
            break;

        // 6. SETTINGS
        case 'save_settings':
            $settings['bot_token'] = $input['bot_token']; $settings['chat_id'] = $input['chat_id'];
            saveJson($files['settings'], $settings); $response = ['status' => 'success', 'message' => 'Đã lưu cấu hình'];
            break;
        case 'get_settings': $response = ['status' => 'success', 'data' => $settings]; break;
    }
    echo json_encode($response); exit;
}
if (isset($_GET['logout'])) { session_destroy(); header("Location: admin.php"); exit; }
if (isset($_POST['login_pass'])) {
    if ($_POST['login_pass'] === $ADMIN_KEY_SECRET) { $_SESSION['is_admin'] = true; header("Location: admin.php"); exit; } else { $error = "Sai Key Admin!"; }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ADMIN PANEL SUPER VIP</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        :root {
            --bg-deep: #050509; --bg-card: rgba(20, 20, 35, 0.6);
            --primary: #6366f1; --accent: #d946ef; --success: #10b981; --warning: #f59e0b; --danger: #f43f5e;
            --text-main: #f8fafc; --text-sub: #94a3b8;
            --border-glass: 1px solid rgba(255, 255, 255, 0.08); --glass-blur: blur(20px);
        }
        body { background-color: var(--bg-deep); color: var(--text-main); font-family: 'Outfit', sans-serif; overflow-x: hidden; background-image: radial-gradient(circle at 10% 10%, rgba(99, 102, 241, 0.15), transparent 40%), radial-gradient(circle at 90% 90%, rgba(217, 70, 239, 0.15), transparent 40%); min-height: 100vh; }
        ::-webkit-scrollbar { width: 6px; } ::-webkit-scrollbar-track { background: var(--bg-deep); } ::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }

        .glass-panel { background: var(--bg-card); backdrop-filter: var(--glass-blur); border: var(--border-glass); border-radius: 24px; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4); padding: 30px; transition: all 0.3s ease; }
        .glass-panel:hover { border-color: rgba(255,255,255,0.15); }

        .sidebar { width: 280px; height: 96vh; position: fixed; left: 15px; top: 2vh; background: rgba(15, 15, 25, 0.85); backdrop-filter: blur(20px); border: var(--border-glass); border-radius: 24px; padding: 30px 20px; z-index: 1000; display: flex; flex-direction: column; box-shadow: 0 0 50px rgba(0,0,0,0.5); transition: 0.4s cubic-bezier(0.2, 0.8, 0.2, 1); }
        .logo-text { font-size: 32px; font-weight: 700; background: linear-gradient(135deg, #fff, #a5b4fc); -webkit-background-clip: text; -webkit-text-fill-color: transparent; letter-spacing: 2px; }
        .nav-item { padding: 16px 20px; margin-bottom: 10px; border-radius: 16px; color: var(--text-sub); font-weight: 500; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; gap: 15px; border: 1px solid transparent; }
        .nav-item:hover { background: rgba(255,255,255,0.03); color: #fff; transform: translateX(5px); }
        .nav-item.active { background: linear-gradient(90deg, rgba(99, 102, 241, 0.15), transparent); color: #fff; border: 1px solid rgba(99, 102, 241, 0.3); box-shadow: 0 0 15px rgba(99, 102, 241, 0.1); }
        
        .main-content { margin-left: 310px; padding: 40px; width: calc(100% - 310px); }
        .stat-card { display: flex; align-items: center; gap: 20px; padding: 30px; border-radius: 20px; background: linear-gradient(160deg, rgba(255,255,255,0.03), rgba(255,255,255,0.01)); border: var(--border-glass); position: relative; overflow: hidden; }
        .stat-card::after { content: ''; position: absolute; top: 0; right: 0; width: 100px; height: 100px; background: radial-gradient(circle, rgba(255,255,255,0.05), transparent 70%); border-radius: 50%; transform: translate(30%, -30%); }
        .icon-box { width: 60px; height: 60px; border-radius: 18px; display: flex; align-items: center; justify-content: center; font-size: 24px; box-shadow: 0 10px 20px rgba(0,0,0,0.2); }
        
        .bg-indigo { background: linear-gradient(135deg, #6366f1, #4f46e5); color: #fff; }
        .bg-green { background: linear-gradient(135deg, #10b981, #059669); color: #fff; }
        .bg-orange { background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; }
        .bg-pink { background: linear-gradient(135deg, #d946ef, #c026d3); color: #fff; }

        .table-responsive { border-radius: 20px; overflow-x: auto; }
        .table-custom { width: 100%; border-collapse: separate; border-spacing: 0 10px; min-width: 900px; } 
        .table-custom th { color: #94a3b8; font-size: 12px; text-transform: uppercase; padding: 15px 25px; border: none; font-weight: 700; white-space: nowrap; }
        .table-custom tr.row-data { background: rgba(30, 41, 59, 0.4); transition: 0.3s; }
        .table-custom tr.row-data:hover { transform: scale(1.01) translateX(5px); background: rgba(40, 50, 75, 0.7); box-shadow: 0 10px 30px rgba(0,0,0,0.3); z-index: 10; position: relative; }
        .table-custom td { padding: 20px 25px; border: none; vertical-align: middle; color: #cbd5e1; font-size: 14px; white-space: nowrap; }
        .table-custom td:first-child { border-top-left-radius: 16px; border-bottom-left-radius: 16px; }
        .table-custom td:last-child { border-top-right-radius: 16px; border-bottom-right-radius: 16px; }

        .badge-soft { padding: 8px 12px; border-radius: 10px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .badge-green { background: rgba(16, 185, 129, 0.1); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.2); }
        .badge-red { background: rgba(244, 63, 94, 0.1); color: #fb7185; border: 1px solid rgba(244, 63, 94, 0.2); }
        .badge-yellow { background: rgba(245, 158, 11, 0.1); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.2); }

        .btn-glow { border: none; outline: none; padding: 10px 18px; border-radius: 12px; font-size: 13px; font-weight: 600; cursor: pointer; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-glow:hover { transform: translateY(-2px); filter: brightness(1.2); }
        .btn-primary-glow { background: linear-gradient(135deg, #6366f1, #4f46e5); color: #fff; box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3); }
        .btn-success-glow { background: linear-gradient(135deg, #10b981, #059669); color: #fff; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3); }
        .btn-danger-glow { background: linear-gradient(135deg, #f43f5e, #e11d48); color: #fff; box-shadow: 0 4px 15px rgba(244, 63, 94, 0.3); }
        .btn-warning-glow { background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3); }
        .btn-icon-only { width: 36px; height: 36px; padding: 0; justify-content: center; border-radius: 10px; }

        .login-wrap { height: 100vh; display: flex; align-items: center; justify-content: center; background: radial-gradient(circle, #1e1b4b 0%, #020617 100%); }
        .login-card { width: 420px; padding: 50px; text-align: center; border: 1px solid rgba(255,255,255,0.1); }
        .swal2-popup { background: rgba(30, 41, 59, 0.95) !important; backdrop-filter: blur(20px) !important; color: #fff !important; border: 1px solid rgba(255,255,255,0.1); border-radius: 24px !important; box-shadow: 0 20px 60px rgba(0,0,0,0.5); }
        .swal2-title { font-family: 'Outfit', sans-serif !important; font-weight: 700 !important; }
        .swal2-input, .swal2-select { background: #0f172a !important; color: #fff !important; border: 1px solid #334155 !important; border-radius: 12px !important; }
        
        .section { display: none; animation: slideUp 0.5s cubic-bezier(0.2, 0.8, 0.2, 1); } .section.active { display: block; }
        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        
        /* RESPONSIVE MOBILE */
        @media(max-width: 992px) { 
            .sidebar { transform: translateX(-120%); } 
            .sidebar.active { transform: translateX(0); } 
            .main-content { margin-left: 0; padding: 20px; width: 100%; } 
            .mobile-toggle { display: block !important; } 
            .page-title { font-size: 22px; margin-top: 50px; } 
            .stat-card { padding: 20px; }
            .glass-panel { padding: 20px; }
            .btn-glow { padding: 8px 12px; font-size: 12px; }
        }
        .mobile-toggle { display: none; position: fixed; top: 20px; left: 20px; z-index: 2000; box-shadow: 0 4px 15px rgba(0,0,0,0.5); }
    </style>
</head>
<body>

    <?php if (!isset($_SESSION['is_admin'])): ?>
        <div class="login-wrap">
            <div class="glass-panel login-card">
                <div class="mb-4">
                    <div style="width:80px; height:80px; border-radius:50%; background: linear-gradient(135deg, #6366f1, #d946ef); display:inline-flex; align-items:center; justify-content:center; box-shadow: 0 0 30px rgba(99,102,241,0.5)">
                        <i class="fas fa-fingerprint fa-2x text-white"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-2">ADMIN LOGIN</h3>
                <p class="text-muted mb-4 small">Vui lòng nhập khóa bảo mật để truy cập</p>
                <?php if(isset($error)) echo "<p class='text-danger bg-danger-soft p-2 rounded small'>$error</p>"; ?>
                <form method="POST">
                    <input type="password" name="login_pass" class="form-control bg-dark border-secondary text-white py-3 mb-3 text-center rounded-3" placeholder="••••••••" required>
                    <button class="btn-glow btn-primary-glow w-100 py-3 fw-bold rounded-3">XÁC THỰC</button>
                </form>
            </div>
        </div>
    <?php else: ?>

    <button class="btn btn-dark mobile-toggle rounded-circle p-3" onclick="document.querySelector('.sidebar').classList.toggle('active')"><i class="fas fa-bars"></i></button>

    <div class="sidebar">
        <div class="logo-area">
            <div class="logo-text">ADMIN PANEL</div>
            <div class="logo-sub">NXP</div>
        </div>
        
        <div class="nav-item active" onclick="tab('dashboard', this)"><i class="fas fa-chart-pie"></i> Dashboard</div>
        <div class="nav-item" onclick="tab('deposits', this)"><i class="fas fa-wallet"></i> Duyệt Nạp Tiền</div>
        <div class="nav-item" onclick="tab('users', this)"><i class="fas fa-users"></i> Người Dùng</div>
        <div class="nav-item" onclick="tab('keys', this)"><i class="fas fa-key"></i> Kho License</div>
        <div class="nav-item" onclick="tab('settings', this)"><i class="fab fa-telegram"></i> Cấu Hình Bot</div>
        
        <div style="margin-top: auto">
            <a href="?logout=true" class="nav-item text-danger"><i class="fas fa-sign-out-alt"></i> Đăng Xuất</a>
        </div>
    </div>

    <div class="main-content">
        
        <div id="dashboard" class="section active">
            <h2 class="page-title">Tổng Quan Hệ Thống</h2>
            <div class="row g-4 mb-5">
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card">
                        <div class="icon-box bg-indigo"><i class="fas fa-users"></i></div>
                        <div class="stat-info"><h5><span id="s-users">0</span></h5><p>Thành Viên</p></div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card">
                        <div class="icon-box bg-green"><i class="fas fa-money-bill-wave"></i></div>
                        <div class="stat-info"><h5 class="text-success" id="s-money">0 đ</h5><p>Tổng Dư User</p></div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card">
                        <div class="icon-box bg-orange"><i class="fas fa-hourglass-half"></i></div>
                        <div class="stat-info"><h5 class="text-warning" id="s-pend">0</h5><p>Chờ Duyệt</p></div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card">
                        <div class="icon-box bg-pink"><i class="fas fa-key"></i></div>
                        <div class="stat-info"><h5 id="s-sold">0</h5><p>Key Đã Bán / <span id="s-total-keys">0</span></p></div>
                    </div>
                </div>
            </div>
            
            <div class="glass-panel">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold m-0"><i class="fas fa-chart-line me-2 text-primary"></i>Biểu Đồ Doanh Thu</h5>
                    <span class="badge-soft badge-green">Tháng Này</span>
                </div>
                <canvas id="mainChart" style="max-height: 350px"></canvas>
            </div>
        </div>

        <div id="deposits" class="section">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="page-title mb-0">Yêu Cầu Nạp Tiền</h2>
                <button class="btn-glow btn-primary-glow" onclick="loadList('deposits')"><i class="fas fa-sync-alt"></i> Làm Mới</button>
            </div>
            <div class="glass-panel p-0">
                <div class="table-responsive">
                    <table class="table-custom">
                        <thead><tr><th>Thời gian</th><th>Người dùng</th><th>Số tiền</th><th>Mã GD</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
                        <tbody id="list-deposits"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="users" class="section">
             <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="page-title mb-0">Quản Lý Thành Viên</h2>
                <button class="btn-glow btn-primary-glow" onclick="loadList('users')"><i class="fas fa-sync-alt"></i> Làm Mới</button>
            </div>
            <div class="glass-panel p-0">
                <div class="table-responsive">
                    <table class="table-custom">
                        <thead><tr><th>Username</th><th>Email</th><th>Số dư ví</th><th>Thiết Bị</th><th>Trạng thái</th><th>Điều chỉnh</th></tr></thead>
                        <tbody id="list-users"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="keys" class="section">
             <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="page-title mb-0">Kho License Key</h2>
                <div>
                    <button class="btn-glow btn-success-glow me-2" onclick="modalKey()"><i class="fas fa-plus-circle"></i> Tạo Key</button>
                    <button class="btn-glow btn-primary-glow" onclick="loadList('keys')"><i class="fas fa-sync-alt"></i></button>
                </div>
            </div>
            <div class="glass-panel p-0">
                <div class="table-responsive">
                    <table class="table-custom">
                        <thead><tr><th>Mã Key</th><th>Gói</th><th>Thiết Bị</th><th>Ngày tạo</th><th>Tình trạng</th><th>Thao tác</th></tr></thead>
                        <tbody id="list-keys"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="settings" class="section">
            <h2 class="page-title">Cấu Hình Hệ Thống</h2>
            <div class="row">
                <div class="col-md-7">
                    <div class="glass-panel">
                        <h5 class="fw-bold mb-4 text-primary">Bot Telegram</h5>
                        <div class="mb-4">
                            <label class="text-sub mb-2 small fw-bold text-uppercase">Telegram Bot Token</label>
                            <input type="text" id="cfg-token" class="form-control bg-dark border-secondary text-white py-3" placeholder="123456:ABC-DEF...">
                        </div>
                        <div class="mb-4">
                            <label class="text-sub mb-2 small fw-bold text-uppercase">Chat ID (Group/Channel)</label>
                            <input type="text" id="cfg-chatid" class="form-control bg-dark border-secondary text-white py-3" placeholder="-100xxxxxxx">
                        </div>
                        <button class="btn-glow btn-primary-glow w-100 py-3" onclick="saveSet()">Lưu Cấu Hình</button>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="glass-panel text-center h-100 d-flex flex-column justify-content-center align-items-center">
                        <div style="width:100px; height:100px; background:rgba(34,158,217,0.1); border-radius:50%; display:flex; align-items:center; justify-content:center; margin-bottom:20px;">
                            <i class="fab fa-telegram fa-4x" style="color: #229ED9"></i>
                        </div>
                        <h5 class="text-white fw-bold">Thông Báo Tự Động</h5>
                        <p class="text-sub px-3">Bot sẽ tự động gửi thông báo về nhóm khi có giao dịch nạp tiền được duyệt hoặc có đơn hàng mới.</p>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>
        // --- LOGIC ---
        let chartInstance = null;
        const fmtMoney = (n) => new Intl.NumberFormat('vi-VN').format(n) + ' đ';

        async function api(act, data = {}) {
            try { return await (await fetch('?api=' + act, {method: 'POST', body: JSON.stringify(data)})).json(); } 
            catch { return {status:'error'}; }
        }

        function tab(id, el) {
            document.querySelectorAll('.section').forEach(e => e.classList.remove('active'));
            document.getElementById(id).classList.add('active');
            if(el) {
                document.querySelectorAll('.nav-item').forEach(e => e.classList.remove('active'));
                el.classList.add('active');
            }
            document.querySelector('.sidebar').classList.remove('active');
            if(id === 'dashboard') loadDash();
            else if(id !== 'settings') loadList(id);
            else loadSet();
        }

        async function loadDash() {
            const res = await api('get_dashboard_data');
            if(res.status === 'success') {
                const d = res.data;
                document.getElementById('s-users').innerText = d.total_users;
                document.getElementById('s-rev').innerText = fmtMoney(d.total_revenue);
                document.getElementById('s-pend').innerText = d.pending_deposits;
                document.getElementById('s-sold').innerText = d.sold_keys;
                document.getElementById('s-total-keys').innerText = d.total_keys;

                const ctx = document.getElementById('mainChart').getContext('2d');
                if(chartInstance) chartInstance.destroy();
                
                let gradient = ctx.createLinearGradient(0, 0, 0, 400);
                gradient.addColorStop(0, 'rgba(99, 102, 241, 0.5)');
                gradient.addColorStop(1, 'rgba(99, 102, 241, 0.0)');

                chartInstance = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: d.chart_labels,
                        datasets: [{
                            label: 'Doanh Thu (VNĐ)', data: d.chart_values,
                            borderColor: '#6366f1', backgroundColor: gradient, borderWidth: 3, fill: true, tension: 0.4,
                            pointBackgroundColor: '#fff', pointRadius: 5, pointHoverRadius: 7
                        }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { x: { grid: { display: false }, ticks: { color: '#94a3b8'} }, y: { grid: { color: 'rgba(255,255,255,0.05)'}, ticks: { color: '#94a3b8'} } }
                    }
                });
            }
        }

        async function loadList(type) {
            const res = await api('get_data_list', {type});
            const tbody = document.getElementById('list-' + type); tbody.innerHTML = '';
            if(!res.data || res.data.length === 0) { tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-5">Không có dữ liệu</td></tr>'; return; }
            
            res.data.forEach(i => {
                let row = `<tr class="row-data">`;
                
                if(type === 'deposits') {
                    let stt = i.status === 'approved' ? '<span class="badge-soft badge-green">Thành công</span>' : (i.status === 'rejected' ? '<span class="badge-soft badge-red">Đã hủy</span>' : '<span class="badge-soft badge-yellow">Chờ duyệt</span>');
                    let btn = i.status === 'pending' ? `<button class="btn-glow btn-success-glow btn-icon-only me-2" onclick="procDep('${i.trans_id}','approve')" title="Duyệt"><i class="fas fa-check"></i></button><button class="btn-glow btn-danger-glow btn-icon-only" onclick="procDep('${i.trans_id}','reject')" title="Hủy"><i class="fas fa-times"></i></button>` : '';
                    row += `<td>${i.time}</td><td class="fw-bold">${i.username}</td><td class="text-warning fw-bold">${fmtMoney(i.amount)}</td><td class="text-sub small">${i.trans_id}</td><td>${stt}</td><td>${btn}</td>`;
                
                } else if(type === 'users') {
                    let isBanned = i.isBanned ? true : false;
                    let banBadge = isBanned ? '<span class="badge-soft badge-red">Bị Khóa</span>' : '<span class="badge-soft badge-green">Hoạt Động</span>';
                    // Hiển thị thiết bị user
                    let userDev = i.device_lock ? `<span class="text-danger fw-bold" title="Locked IP: ${i.ip_address}">Locked</span>` : '<span class="text-success fw-bold">Free</span>';

                    row += `
                        <td class="fw-bold">${i.username}</td>
                        <td class="text-sub">${i.email}</td>
                        <td class="text-success fw-bold">${fmtMoney(i.balance)}</td>
                        <td>${userDev}</td>
                        <td>${banBadge}</td>
                        <td>
                            <button class="btn-glow btn-primary-glow btn-icon-only me-1" onclick="modUser('${i.username}','add_money')" title="Cộng tiền"><i class="fas fa-plus"></i></button>
                            <button class="btn-glow btn-warning-glow btn-icon-only me-1" onclick="modUser('${i.username}','sub_money')" title="Trừ tiền"><i class="fas fa-minus"></i></button>
                            <button class="btn-glow btn-warning-glow btn-icon-only me-1" onclick="modUser('${i.username}','reset_device')" title="Reset Thiết Bị User"><i class="fas fa-mobile-alt"></i></button>
                            <button class="btn-glow btn-danger-glow btn-icon-only me-1" onclick="modUser('${i.username}','ban_user')" title="${isBanned ? 'Mở Khóa' : 'Khóa User'}"><i class="fas fa-ban"></i></button>
                            <button class="btn-glow btn-danger-glow btn-icon-only" onclick="modUser('${i.username}','delete_user')" title="Xóa User"><i class="fas fa-trash"></i></button>
                        </td>`;
                
                } else if(type === 'keys') {
                    let stt = i.used ? '<span class="badge-soft badge-red">Used</span>' : '<span class="badge-soft badge-green">Live</span>';
                    let dev = i.device_lock ? '<span class="text-danger fw-bold">1/1</span>' : '<span class="text-success fw-bold">0/1</span>';
                    
                    row += `
                        <td><code class="text-info bg-dark px-2 py-1 rounded border border-secondary">${i.key}</code></td>
                        <td>${i.plan} (${i.so_ngay} ngày)</td>
                        <td>${dev}</td>
                        <td class="text-sub small">${i.ngay_tao}</td>
                        <td>${stt}</td>
                        <td>
                            <button class="btn-glow btn-primary-glow btn-icon-only me-1" onclick="copy('${i.key}')" title="Copy"><i class="fas fa-copy"></i></button>
                            <button class="btn-glow btn-warning-glow btn-icon-only me-1" onclick="resetDev('${i.key}')" title="Reset Thiết Bị Key"><i class="fas fa-mobile-alt"></i></button>
                            <button class="btn-glow btn-danger-glow btn-icon-only" onclick="delKey('${i.key}')" title="Xóa Key"><i class="fas fa-trash"></i></button>
                        </td>`;
                }
                row += `</tr>`; tbody.innerHTML += row;
            });
        }

        // --- ACTIONS ---
        function procDep(id, act) {
            Swal.fire({
                title: act === 'approve' ? 'Duyệt Nạp Tiền?' : 'Hủy Đơn?',
                text: act === 'approve' ? 'Xác nhận cộng tiền cho khách.' : 'Xác nhận hủy đơn này.',
                icon: act === 'approve' ? 'question' : 'warning',
                showCancelButton: true, confirmButtonText: 'Xác Nhận', confirmButtonColor: '#6366f1', background: '#1e293b', color: '#fff'
            }).then(async r => {
                if(r.isConfirmed) {
                    Swal.showLoading();
                    const res = await api('process_deposit', {trans_id: id, decision: act});
                    Swal.fire({title: res.status==='success'?'Thành công':'Lỗi', icon: res.status, timer: 1500, showConfirmButton: false});
                    loadList('deposits'); loadDash();
                }
            })
        }

        function modUser(u, act) {
            if(act === 'delete_user') {
                Swal.fire({ title: 'Xóa vĩnh viễn user này?', text: 'Không thể khôi phục!', icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', background: '#1e293b', color:'#fff' }).then(async r => {
                    if(r.isConfirmed) { await api('manage_user', {username: u, act: act, value: 0}); loadList('users'); }
                });
                return;
            }
            if(act === 'ban_user') {
                Swal.fire({ title: 'Khóa/Mở khóa user?', icon: 'warning', showCancelButton: true, background: '#1e293b', color:'#fff' }).then(async r => {
                    if(r.isConfirmed) { 
                        const res = await api('manage_user', {username: u, act: act, value: 0}); 
                        Swal.fire(res.message, '', 'success');
                        loadList('users'); 
                    }
                });
                return;
            }
            if(act === 'reset_device') {
                Swal.fire({ title: 'Reset thiết bị cho User?', text: 'User sẽ có thể đăng nhập trên thiết bị mới.', icon: 'warning', showCancelButton: true, background: '#1e293b', color:'#fff' }).then(async r => {
                    if(r.isConfirmed) { 
                        const res = await api('manage_user', {username: u, act: act, value: 0}); 
                        Swal.fire(res.message, '', 'success');
                        loadList('users'); 
                    }
                });
                return;
            }
            Swal.fire({ title: 'Nhập số tiền', input: 'number', showCancelButton: true, confirmButtonColor: '#6366f1', background: '#1e293b', color:'#fff' }).then(async r => {
                if(r.isConfirmed && r.value) {
                    await api('manage_user', {username: u, act: act, value: r.value});
                    loadList('users');
                }
            });
        }

        function modalKey() {
            Swal.fire({
                title: 'Tạo License Key',
                html: `
                    <div class="mb-3 text-start"><label class="small text-muted mb-1">Gói Cước</label>
                    <select id="p" class="swal2-select w-100 m-0"><option>VIP 1 Ngày</option><option>VIP 1 Tuần</option><option>VIP 1 Tháng</option><option>Vĩnh Viễn</option></select></div>
                    <div class="text-start"><label class="small text-muted mb-1">Số Ngày</label>
                    <input id="d" type="number" class="swal2-input w-100 m-0" placeholder="VD: 30" value="1"></div>
                `,
                showCancelButton: true, confirmButtonText: 'Tạo Ngay', confirmButtonColor: '#10b981', background: '#1e293b', color: '#fff',
                preConfirm: () => ({plan: document.getElementById('p').value, days: document.getElementById('d').value})
            }).then(async r => {
                if(r.isConfirmed) {
                    const res = await api('create_key', r.value);
                    if(res.status==='success') {
                        copy(res.key);
                        Swal.fire({title: 'Key Đã Tạo', text: res.key, icon: 'success', footer: 'Đã copy vào bộ nhớ tạm'});
                        loadList('keys');
                    }
                }
            })
        }
        
        async function resetDev(k) {
            Swal.fire({ title: 'Reset thiết bị cho Key?', text: "Key sẽ được dùng trên máy mới (0/1).", icon: 'warning', showCancelButton: true, confirmButtonText: 'Reset', confirmButtonColor: '#f59e0b', background: '#1e293b', color:'#fff' }).then(async r => { 
                if(r.isConfirmed) { 
                    const res = await api('reset_key_device', {key:k});
                    Swal.fire({title: 'Thành công', icon: 'success', timer: 1500, showConfirmButton: false});
                    loadList('keys'); 
                } 
            });
        }

        async function delKey(k) { 
            Swal.fire({ title: 'Xóa vĩnh viễn key này?', icon: 'error', showCancelButton: true, confirmButtonText: 'Xóa', confirmButtonColor: '#ef4444', background: '#1e293b', color:'#fff' }).then(async r => { 
                if(r.isConfirmed) { await api('delete_key', {key:k}); loadList('keys'); } 
            });
        }
        
        async function saveSet() { await api('save_settings', {bot_token: document.getElementById('cfg-token').value, chat_id: document.getElementById('cfg-chatid').value}); Swal.fire({title: 'Đã lưu', icon: 'success', timer: 1500, showConfirmButton: false, background: '#1e293b', color:'#fff'}); }
        async function loadSet() { const res = await api('get_settings'); if(res.status==='success') { document.getElementById('cfg-token').value = res.data.bot_token||''; document.getElementById('cfg-chatid').value = res.data.chat_id||''; } }
        function copy(t) { navigator.clipboard.writeText(t); Swal.fire({toast:true, position:'top-end', icon:'success', title:'Copied!', timer:1500, showConfirmButton:false, background: '#10b981', color:'#fff'}); }

        document.addEventListener('DOMContentLoaded', () => loadDash());
    </script>
    <?php endif; ?>
</body>
</html>