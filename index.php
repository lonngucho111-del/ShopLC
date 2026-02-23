<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập & Đăng ký</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-dark: #050a2a;
            --surface: #0b1c45;
            --accent: #00ff9d;
            --accent-blue: #00c3ff;
            --primary-gold: #ffd700;
            --text-main: #ffffff;
            --text-dim: #a0a0c0;
            --glass: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.08);
            --grad: linear-gradient(135deg, #00ff9d 0%, #00c3ff 100%);
            --shadow-glow: 0 0 30px rgba(0, 255, 157, 0.2);
            --danger: #ff6b6b;
            --warning: #ffae00;
            --success: #00ff9d;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: linear-gradient(180deg, #050a2a, #0b1c45);
            color: var(--text-main);
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        /* Hiệu ứng quầng sáng nền */
        #cursor-glow {
            position: fixed;
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(0, 255, 157, 0.12) 0%, transparent 70%);
            border-radius: 50%; pointer-events: none; z-index: -1;
            filter: blur(60px); transition: transform 0.1s ease-out;
        }

        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }

        /* --- HEADER (giống key.php) --- */
        header {
            position: sticky; top: 0; z-index: 1000;
            backdrop-filter: blur(20px); 
            border-bottom: 1px solid var(--glass-border);
            padding: 15px 0; 
            background: rgba(0, 0, 0, 0.3);
        }
        .nav-wrapper { display: flex; justify-content: space-between; align-items: center; }
        .logo {
            font-size: 1.5rem; font-weight: 700; text-decoration: none; color: #fff;
            display: flex; align-items: center; gap: 10px;
        }
        .logo img { width: 42px; height: 42px; border-radius: 12px; border: 2px solid var(--accent); }
        .logo span { 
            background: linear-gradient(135deg, #00ff9d, #00c3ff); 
            -webkit-background-clip: text; 
            -webkit-text-fill-color: transparent;
            font-weight: 700;
        }

        /* --- AUTH CENTER CARD --- */
        .auth-wrapper { flex: 1; display: flex; align-items: center; justify-content: center; padding: 60px 20px; }
        .auth-card {
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(35px);
            border: 2px solid var(--accent-blue);
            border-radius: 32px;
            width: 100%; max-width: 450px;
            padding: 45px;
            box-shadow: 0 0 30px rgba(0, 195, 255, 0.3);
            position: relative;
            overflow: hidden;
        }
        .auth-card::after {
            content: ''; 
            position: absolute; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 2px; 
            background: var(--grad);
        }

        .auth-header { text-align: center; margin-bottom: 35px; }
        .auth-header h2 { 
            font-size: 2rem; 
            font-weight: 700; 
            letter-spacing: -1px; 
            margin-bottom: 10px; 
            background: var(--grad); 
            -webkit-background-clip: text; 
            -webkit-text-fill-color: transparent; 
        }
        .auth-header p { color: var(--text-dim); font-size: 0.95rem; }

        .form-container { display: none; }
        .form-container.active { display: block; animation: authFade 0.5s cubic-bezier(0.165, 0.84, 0.44, 1); }
        @keyframes authFade { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        .form-group { margin-bottom: 22px; }
        .form-group label { 
            display: block; 
            margin-bottom: 10px; 
            font-size: 0.85rem; 
            font-weight: 600; 
            color: var(--text-dim); 
            text-transform: uppercase; 
            letter-spacing: 1px; 
        }
        
        .input-wrapper { position: relative; }
        .input-wrapper i.field-icon { 
            position: absolute; 
            left: 18px; 
            top: 50%; 
            transform: translateY(-50%); 
            color: var(--accent); 
            transition: 0.3s; 
        }
        
        input {
            width: 100%; 
            padding: 15px 15px 15px 48px;
            background: rgba(255,255,255,0.02); 
            border: 1px solid var(--glass-border);
            border-radius: 16px; 
            color: #fff; 
            font-size: 1rem; 
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }
        input:focus { 
            outline: none; 
            border-color: var(--accent); 
            background: rgba(255,255,255,0.06); 
            box-shadow: 0 0 20px rgba(0, 255, 157, 0.1); 
        }
        input:focus + i.field-icon { color: #fff; }

        .password-toggle { 
            position: absolute; 
            right: 18px; 
            top: 50%; 
            transform: translateY(-50%); 
            cursor: pointer; 
            color: var(--text-dim); 
            transition: 0.3s; 
        }
        .password-toggle:hover { color: #fff; }

        /* MẬT KHẨU MẠNH/YẾU */
        .strength-meter { 
            height: 6px; 
            background: rgba(255,255,255,0.05); 
            border-radius: 10px; 
            margin-top: 12px; 
            overflow: hidden; 
        }
        .strength-bar { 
            height: 100%; 
            width: 0; 
            transition: 0.5s cubic-bezier(0.4, 0, 0.2, 1); 
        }
        .status-text { 
            font-size: 0.75rem; 
            margin-top: 8px; 
            font-weight: 600; 
            display: block; 
        }

        .submit-btn {
            width: 100%; 
            padding: 16px; 
            margin-top: 15px;
            background: linear-gradient(90deg, #00c853, #00e676);
            border: none; 
            border-radius: 16px;
            color: white; 
            font-weight: 700; 
            font-size: 1rem; 
            cursor: pointer;
            transition: 0.4s; 
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .submit-btn:hover { 
            transform: translateY(-4px); 
            filter: brightness(1.1); 
            box-shadow: 0 10px 30px rgba(0, 230, 118, 0.4); 
        }

        .switch-link { 
            text-align: center; 
            margin-top: 30px; 
            font-size: 0.9rem; 
            color: var(--text-dim); 
        }
        .switch-link a { 
            color: var(--accent); 
            text-decoration: none; 
            font-weight: 700; 
            border-bottom: 1px solid transparent; 
            transition: 0.3s; 
        }
        .switch-link a:hover { 
            border-color: var(--accent); 
            text-shadow: 0 0 10px var(--accent); 
        }

        /* --- FOOTER (giống key.php) --- */
        footer {
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            border-top: 1px solid var(--glass-border);
            padding: 30px 0;
            position: relative;
        }
        .footer-glow-line {
            position: absolute; 
            top: 0; 
            left: 50%; 
            transform: translateX(-50%);
            width: 40%; 
            height: 2px; 
            background: var(--grad);
            box-shadow: 0 0 40px 2px var(--accent);
        }
        .footer-grid { 
            display: flex;
            justify-content: center;
            margin-bottom: 0; 
        }
        
        .footer-col h4 { 
            margin-bottom: 25px; 
            font-size: 0.9rem; 
            text-transform: uppercase; 
            letter-spacing: 2px; 
            color: #ffd700; 
            font-weight: 700; 
            text-align: center; 
        }
        .footer-col p { 
            color: var(--text-dim); 
            font-size: 0.95rem; 
            line-height: 1.8; 
            text-align: center; 
        }
        
        /* Cột trạng thái hệ thống */
        .footer-col {
            max-width: 500px;
            width: 100%;
        }

        /* Status box */
        .status-box {
            background: rgba(0, 0, 0, 0.4);
            border: 2px solid #00ff9d;
            padding: 15px;
            border-radius: 16px;
            box-shadow: 0 0 15px rgba(0, 255, 157, 0.2);
        }
        
        .status-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #00ff9d;
            font-weight: 700;
            font-size: 0.8rem;
            margin-bottom: 5px;
            justify-content: center;
        }
        
        .status-dot {
            width: 8px; 
            height: 8px; 
            background: #00ff9d; 
            border-radius: 50%; 
            display: inline-block; 
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.2); }
            100% { opacity: 1; transform: scale(1); }
        }

        .status-text-small {
            font-size: 0.8rem; 
            color: var(--text-dim); 
            text-align: center;
        }

        #toast {
            position: fixed; 
            top: 25px; 
            right: 25px; 
            padding: 18px 30px; 
            border-radius: 18px;
            color: #fff; 
            font-weight: 700; 
            z-index: 10001; 
            transform: translateX(150%); 
            transition: 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            backdrop-filter: blur(15px); 
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
        }
        #toast.show { transform: translateX(0); }
        #toast.success { background: rgba(0, 255, 157, 0.95); border-left: 6px solid #00ff9d; }
        #toast.error { background: rgba(255, 107, 107, 0.95); border-left: 6px solid #ff6b6b; }

        @media (max-width: 600px) {
            .footer-col { padding: 0 20px; }
        }
    </style>
</head>
<body>

<div id="cursor-glow"></div>

<header>
    <div class="container nav-wrapper">
        <a href="index.php" class="logo">
            <img src="https://i.postimg.cc/ZnnjVvRR/IMG-3239.png" alt="Logo">
            <span>SHOP LC </span>
        </a>
    </div>
</header>

<main class="auth-wrapper">
    <div class="auth-card">
        <div class="form-container active" id="loginBox">
            <div class="auth-header">
                <h2>Đăng Nhập</h2>
                <p>Đăng nhập vào trang chủ</p>
            </div>
            <form id="loginForm">
                <div class="form-group">
                    <label>Email</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope field-icon"></i>
                        <input type="email" id="login-email" placeholder="example@gmail.com" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Mật khẩu</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock field-icon"></i>
                        <input type="password" id="login-password" placeholder="••••••••" required>
                        <span class="password-toggle" onclick="togglePass('login-password', this)"><i class="fas fa-eye"></i></span>
                    </div>
                </div>
                <button type="submit" class="submit-btn" id="login-btn">ĐĂNG NHẬP</button>
            </form>
            <div class="switch-link">Chưa có tài khoản? <a href="javascript:void(0)" onclick="switchForm('registerBox')">Đăng ký ngay</a></div>
        </div>

        <div class="form-container" id="registerBox">
            <div class="auth-header">
                <h2>Đăng Ký</h2>
                <p>Tạo tài khoản mới</p>
            </div>
            <form id="registerForm">
                <div class="form-group">
                    <label>Tên đăng nhập</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user field-icon"></i>
                        <input type="text" id="reg-user" placeholder="Nhập username" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope field-icon"></i>
                        <input type="email" id="reg-email" placeholder="example@gmail.com" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Mật khẩu</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock field-icon"></i>
                        <input type="password" id="reg-password" placeholder="Tối thiểu 6 ký tự" required oninput="checkStrength(this.value)">
                        <span class="password-toggle" onclick="togglePass('reg-password', this)"><i class="fas fa-eye"></i></span>
                    </div>
                    <div class="strength-meter"><div class="strength-bar" id="strength-bar"></div></div>
                    <span class="status-text" id="strength-text"></span>
                </div>
                <div class="form-group">
                    <label>Xác nhận mật khẩu</label>
                    <div class="input-wrapper">
                        <i class="fas fa-check-double field-icon"></i>
                        <input type="password" id="reg-confirm" placeholder="Nhập lại mật khẩu" required oninput="checkMatch()">
                        <span class="password-toggle" onclick="togglePass('reg-confirm', this)"><i class="fas fa-eye"></i></span>
                    </div>
                    <span class="status-text" id="match-msg"></span>
                </div>
                <button type="submit" class="submit-btn" id="reg-btn">ĐĂNG KÝ</button>
            </form>
            <div class="switch-link">Đã có tài khoản? <a href="javascript:void(0)" onclick="switchForm('loginBox')">Đăng nhập</a></div>
        </div>
    </div>
</main>

<footer>
    <div class="footer-glow-line"></div>
    <div class="container">
        <div class="footer-grid">
            <div class="footer-col">
                <h4>TRẠNG THÁI HỆ THỐNG</h4>
                <div class="status-box">
                    <div class="status-indicator">
                        <span class="status-dot"></span>
                        SERVER ONLINE
                    </div>
                    <p class="status-text-small">Hệ thống đang hoạt động 100% | Độ trễ: 12ms</p>
                </div>
            </div>
        </div>
    </div>
</footer>

<div id="toast"></div>

<script>
    // 1. Ánh sáng theo chuột
    const glow = document.getElementById('cursor-glow');
    document.addEventListener('mousemove', (e) => {
        glow.style.transform = `translate(${e.clientX - 250}px, ${e.clientY - 250}px)`;
    });

    // 2. Chuyển Form
    function switchForm(id) {
        document.querySelectorAll('.form-container').forEach(f => f.classList.remove('active'));
        document.getElementById(id).classList.add('active');
    }

    // 3. Hiện/Ẩn mật khẩu
    function togglePass(id, el) {
        const input = document.getElementById(id);
        const icon = el.querySelector('i');
        input.type = input.type === "password" ? "text" : "password";
        icon.className = input.type === "password" ? "fas fa-eye" : "fas fa-eye-slash";
    }

    // 4. Độ mạnh mật khẩu
    function checkStrength(pass) {
        const bar = document.getElementById('strength-bar');
        const text = document.getElementById('strength-text');
        let score = 0;
        if (pass.length >= 1) score = 1;
        if (pass.length >= 6) score = 2;
        if (pass.length >= 8 && /[A-Z]/.test(pass) && /\d/.test(pass)) score = 3;

        if (score === 0) { bar.style.width = '0%'; text.textContent = ''; }
        else if (score === 1) { bar.style.width = '30%'; bar.style.background = '#ff6b6b'; text.textContent = 'Bảo mật: Yếu'; text.style.color = '#ff6b6b'; }
        else if (score === 2) { bar.style.width = '60%'; bar.style.background = '#ffae00'; text.textContent = 'Bảo mật: Trung bình'; text.style.color = '#ffae00'; }
        else if (score === 3) { bar.style.width = '100%'; bar.style.background = '#00ff9d'; text.textContent = 'Bảo mật: Mạnh'; text.style.color = '#00ff9d'; }
    }

    // 5. Kiểm tra mật khẩu khớp
    function checkMatch() {
        const p1 = document.getElementById('reg-password').value;
        const p2 = document.getElementById('reg-confirm').value;
        const msg = document.getElementById('match-msg');
        if (!p2) { msg.style.display = 'none'; return; }
        msg.style.display = 'block';
        if (p1 === p2) { msg.textContent = '✓ Mật khẩu khớp!'; msg.style.color = '#00ff9d'; }
        else { msg.textContent = '✗ Mật khẩu không khớp!'; msg.style.color = '#ff6b6b'; }
    }

    function showToast(msg, type = 'success') {
        const t = document.getElementById('toast');
        t.textContent = msg;
        t.className = `show ${type}`;
        setTimeout(() => t.classList.remove('show'), 3500);
    }

    // 6. Xử lý Register (AJAX)
    document.getElementById('registerForm').onsubmit = async (e) => {
        e.preventDefault();
        const p1 = document.getElementById('reg-password').value;
        const p2 = document.getElementById('reg-confirm').value;
        if (p1 !== p2) return showToast('Mật khẩu không khớp!', 'error');

        const btn = document.getElementById('reg-btn');
        btn.disabled = true; btn.textContent = "ĐANG XỬ LÝ...";

        try {
            const fd = new FormData();
            fd.append('action', 'register');
            fd.append('username', document.getElementById('reg-user').value);
            fd.append('email', document.getElementById('reg-email').value);
            fd.append('password', p1);

            const res = await fetch('api.php', { method: 'POST', body: fd });
            const data = await res.json();

            if (data.status === 'success') {
                showToast('Đăng ký thành công! Đang chuyển sang đăng nhập...', 'success');
                setTimeout(() => {
                    switchForm('loginBox');
                    document.getElementById('login-email').value = document.getElementById('reg-email').value;
                }, 2000);
            } else { showToast(data.message, 'error'); }
        } catch { showToast('Lỗi kết nối máy chủ', 'error'); }
        finally { btn.disabled = false; btn.textContent = "ĐĂNG KÝ"; }
    };

    // Đăng nhập (AJAX)
    document.getElementById('loginForm').onsubmit = async (e) => {
        e.preventDefault();
        const btn = document.getElementById('login-btn');
        btn.disabled = true; btn.textContent = "ĐANG XÁC THỰC...";
        
        const fd = new FormData();
        fd.append('action', 'login');
        fd.append('email', document.getElementById('login-email').value);
        fd.append('password', document.getElementById('login-password').value);

        try {
            const res = await fetch('api.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.status === 'success') {
                showToast('Đăng nhập thành công!', 'success');
                setTimeout(() => window.location.href = "key.php", 1000);
            } else { showToast(data.message, 'error'); }
        } catch { showToast('Lỗi kết nối', 'error'); }
        finally { btn.disabled = false; btn.textContent = "ĐĂNG NHẬP"; }
    };
</script>

</body>
</html>