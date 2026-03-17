<?php
session_start();

// Check for feedback messages
$feedback_message = isset($_SESSION['register_error']) ? $_SESSION['register_error'] : null;
$feedback_type = 'error';
$form_data = isset($_SESSION['register_form_data']) ? $_SESSION['register_form_data'] : [];

unset($_SESSION['register_error']);
unset($_SESSION['register_form_data']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Registration</title>
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
            padding: 24px 14px;
            background:
                radial-gradient(circle at 14% 18%, rgba(15,118,110,0.18), transparent 34%),
                radial-gradient(circle at 84% 82%, rgba(249,115,22,0.15), transparent 34%),
                linear-gradient(132deg, #f8f5ee, #edf6ff 65%);
        }

        .register-container {
            width: 520px;
            max-width: 96%;
            padding: 30px 26px;
            border-radius: 18px;
            border: 1px solid rgba(23, 32, 51, 0.1);
            box-shadow: 0 24px 44px rgba(17, 34, 64, 0.18);
            background: rgba(255, 255, 255, 0.94);
            animation: rise-in .55s ease-out;
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

        .register-container h2 {
            margin: 0 0 8px;
            font-size: 1.55rem;
            color: var(--ink);
        }

        .helper-text {
            margin: 0 0 20px;
            color: var(--muted);
            text-align: left;
            font-size: .95rem;
        }

        .form-group { margin-bottom: 14px; }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            color: #33435f;
            font-weight: 700;
            font-size: .9rem;
        }

        .form-group input[type="text"],
        .form-group input[type="password"],
        .form-group input[type="date"],
        .form-group input[type="number"],
        .form-group input[type="tel"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: #fff;
            font: inherit;
            color: var(--ink);
            transition: border-color .2s ease, box-shadow .2s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: rgba(15,118,110,.7);
            box-shadow: 0 0 0 4px rgba(15,118,110,.12);
        }

        .form-group textarea { min-height: 72px; }

        .form-group button {
            width: 100%;
            padding: 11px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font: inherit;
            font-weight: 700;
            color: white;
            background: linear-gradient(135deg, var(--brand), #0ea5a2);
            box-shadow: 0 12px 20px rgba(15,118,110,.25);
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .form-group button:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 26px rgba(15,118,110,.3);
        }

        .error-message {
            color: #842029;
            background: #f9e9ec;
            border: 1px solid #f2c7cd;
            padding: 10px;
            border-radius: 10px;
            margin-bottom: 15px;
            font-size: 0.9em;
        }

        .login-link {
            text-align: center;
            margin-top: 16px;
            font-size: 0.92em;
            color: var(--muted);
        }

        .login-link a {
            color: var(--brand-alt);
            font-weight: 700;
            text-decoration: none;
        }

        .login-link a:hover { text-decoration: underline; }

        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
        input[type=number] { -moz-appearance: textfield; }

        @media (max-width: 520px) {
            .register-container {
                padding: 22px 16px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <span class="eyebrow">New Patient</span>
        <h2>Patient Registration</h2>
        <p class="helper-text">Create your account to manage appointments and medical records.</p>

        <?php if ($feedback_message): ?>
            <div class="error-message">
                <?php
                    if (is_array($feedback_message)) {
                        echo implode("<br>", array_map('htmlspecialchars', $feedback_message));
                    } else {
                        echo htmlspecialchars($feedback_message);
                    }
                ?>
            </div>
        <?php endif; ?>

        <form action="process_registration.php" method="post" id="registration-form">
            <div class="form-group">
                <label for="tc_kimlik_no">TC Kimlik No (Patient ID):</label>
                <input type="number" id="tc_kimlik_no" name="patient_id_tc" required pattern="\d{11}" title="Please enter exactly 11 digits" maxlength="11" oninput="javascript: if (this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);" value="<?php echo htmlspecialchars($form_data['patient_id_tc'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="first_name">First Name:</label>
                <input type="text" id="first_name" name="first_name" required value="<?php echo htmlspecialchars($form_data['first_name'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="last_name">Last Name:</label>
                <input type="text" id="last_name" name="last_name" required value="<?php echo htmlspecialchars($form_data['last_name'] ?? ''); ?>">
            </div>
             <div class="form-group">
                <label for="phone">Phone:</label>
                <input type="tel" id="phone" name="phone" required placeholder="e.g., 5xxxxxxxxx" value="<?php echo htmlspecialchars($form_data['phone'] ?? ''); ?>">
            </div>
             <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <div class="form-group">
                <label for="gender">Gender:</label>
                <select id="gender" name="gender" required> <option value="" disabled selected>-- Select Gender --</option>
                    <option value="Male" <?php echo (($form_data['gender'] ?? '') === 'Male') ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?php echo (($form_data['gender'] ?? '') === 'Female') ? 'selected' : ''; ?>>Female</option>
                    <option value="Other" <?php echo (($form_data['gender'] ?? '') === 'Other') ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
             <div class="form-group">
                <label for="dob">Date of Birth:</label>
                <input type="date" id="dob" name="dob" required max="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($form_data['dob'] ?? ''); ?>"> </div>
             <div class="form-group">
                <label for="blood_type">Blood Type:</label>
                 <select id="blood_type" name="blood_type" required> <option value="" disabled selected>-- Select Blood Type --</option>
                    <option value="A +" <?php echo (($form_data['blood_type'] ?? '') === 'A +') ? 'selected' : ''; ?>>A +</option>
                    <option value="A -" <?php echo (($form_data['blood_type'] ?? '') === 'A -') ? 'selected' : ''; ?>>A -</option>
                    <option value="B +" <?php echo (($form_data['blood_type'] ?? '') === 'B +') ? 'selected' : ''; ?>>B +</option>
                    <option value="B -" <?php echo (($form_data['blood_type'] ?? '') === 'B -') ? 'selected' : ''; ?>>B -</option>
                    <option value="AB +" <?php echo (($form_data['blood_type'] ?? '') === 'AB +') ? 'selected' : ''; ?>>AB +</option>
                    <option value="AB -" <?php echo (($form_data['blood_type'] ?? '') === 'AB -') ? 'selected' : ''; ?>>AB -</option>
                    <option value="O +" <?php echo (($form_data['blood_type'] ?? '') === 'O +') ? 'selected' : ''; ?>>O +</option>
                    <option value="O -" <?php echo (($form_data['blood_type'] ?? '') === 'O -') ? 'selected' : ''; ?>>O -</option>
                    </select>
            </div>
             <div class="form-group">
                <label for="address">Address:</label>
                <textarea id="address" name="address" required><?php echo htmlspecialchars($form_data['address'] ?? ''); ?></textarea> </div>

            <div class="form-group">
                <button type="submit">Register</button>
            </div>
        </form>
         <div class="login-link">
            Already have an account? <a href="index.php">Login here</a>
        </div>
    </div>
    <script>
        const form = document.getElementById('registration-form');
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        if(form && password && confirmPassword) {
            form.addEventListener('submit', function(event) {
                if (password.value !== confirmPassword.value) {
                    alert("Passwords do not match!");
                    event.preventDefault();
                }
            });
        }
    </script>
</body>
</html>