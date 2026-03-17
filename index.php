<?php
session_start();
require_once 'includes/functions.php';

// *** YENİ: Kayıt başarı mesajını kontrol et ***
$register_success_message = null;
if (isset($_SESSION['login_success_message'])) {
    $register_success_message = $_SESSION['login_success_message'];
    unset($_SESSION['login_success_message']); // Mesajı gösterdikten sonra sil
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Management System - Login</title>
    <style>
        :root {
            --ink: #1c2538;
            --muted: #5d6b82;
            --line: #dae3f2;
            --brand: #0f766e;
            --brand-alt: #f97316;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Trebuchet MS", "Segoe UI", sans-serif;
            color: var(--ink);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 22px;
            background:
                radial-gradient(circle at 15% 20%, rgba(15,118,110,0.18), transparent 34%),
                radial-gradient(circle at 82% 78%, rgba(249,115,22,0.16), transparent 36%),
                linear-gradient(135deg, #f8f5ee, #edf6ff 64%);
        }

        .login-container {
            width: 370px;
            max-width: 100%;
            padding: 30px 26px;
            border-radius: 18px;
            border: 1px solid rgba(23, 32, 51, 0.1);
            background: rgba(255, 255, 255, 0.94);
            box-shadow: 0 24px 44px rgba(17, 34, 64, 0.18);
            animation: rise-in .5s ease-out;
        }

        @keyframes rise-in {
            from { transform: translateY(10px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .eyebrow {
            display: inline-block;
            padding: 6px 10px;
            margin-bottom: 12px;
            border-radius: 999px;
            font-size: .75rem;
            font-weight: 700;
            letter-spacing: .06em;
            color: #0b4f4a;
            background: #ddf3f0;
            text-transform: uppercase;
        }

        .login-container h2 {
            margin: 0 0 8px;
            font-size: 1.55rem;
        }

        .subtitle {
            margin: 0 0 20px;
            color: var(--muted);
            font-size: .95rem;
        }

        .login-container label {
            display: block;
            margin: 0 0 6px;
            color: #33435f;
            font-weight: 700;
            font-size: .9rem;
        }

        .login-container input[type="text"],
        .login-container input[type="password"],
        .login-container select {
            width: 100%;
            padding: 11px 12px;
            margin-bottom: 14px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: #fff;
            font: inherit;
            color: var(--ink);
            transition: border-color .2s ease, box-shadow .2s ease;
        }

        .login-container input:focus,
        .login-container select:focus {
            outline: none;
            border-color: rgba(15,118,110,.7);
            box-shadow: 0 0 0 4px rgba(15,118,110,.12);
        }

        .login-container button {
            width: 100%;
            padding: 11px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font: inherit;
            font-weight: 700;
            color: #fff;
            background: linear-gradient(135deg, var(--brand), #0ea5a2);
            box-shadow: 0 12px 20px rgba(15,118,110,.25);
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .login-container button:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 26px rgba(15,118,110,.3);
        }

        .error-message {
            color: #842029;
            text-align: center;
            margin-bottom: 15px;
            font-size: 0.9em;
            border: 1px solid #f2c7cd;
            background: #f9e9ec;
            padding: 10px;
            border-radius: 10px;
        }

        .success-message {
            color: #0f5132;
            border: 1px solid #c8ead9;
            background: #e9f8f0;
            padding: 10px;
            border-radius: 10px;
            margin-bottom: 15px;
            font-size: 0.9em;
            text-align: center;
        }

        .register-link {
            text-align: center;
            margin-top: 18px;
            font-size: 0.92em;
            color: var(--muted);
        }

        .register-link a {
            color: var(--brand-alt);
            font-weight: 700;
            text-decoration: none;
        }

        .register-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <span class="eyebrow">Hospital Access</span>
        <h2>Login</h2>
        <p class="subtitle">Sign in to continue to your patient, doctor, or admin dashboard.</p>

         <?php if ($register_success_message): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($register_success_message); ?>
            </div>
         <?php endif; ?>

        <?php
        // Mevcut giriş hata mesajı gösterme alanı
        if (isset($_SESSION['login_error'])) {
            echo '<p class="error-message">' . htmlspecialchars($_SESSION['login_error']) . '</p>';
            unset($_SESSION['login_error']);
        }
        ?>

        <form action="login_process.php" method="post">
            <?php echo csrf_input_field(); ?>
            <div>
                <label for="identifier">User ID / Email:</label>
                <input type="text" id="identifier" name="identifier" placeholder="Enter Patient ID or Doctor/Admin Email" required>
            </div>
            <div>
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>
            <div>
                <label for="role">Role:</label>
                <select id="role" name="role" required>
                    <option value="" disabled selected>Select your role</option>
                    <option value="patient">Patient</option>
                    <option value="doctor">Doctor</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <button type="submit">Login</button>
        </form>
         <p class="register-link">
            Don't have an account? <a href="register.php">Register here</a>
        </p>
    </div>
</body>
</html>