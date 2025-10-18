<?php
session_start();
$pdo = new PDO('mysql:host=localhost;dbname=capstone', 'root', ''); // Make sure DB name is correct

$message = '';
$showRegister = false;

// Registration
if (isset($_POST['register'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['reg_password'];
    $confirm = $_POST['confirm_password'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email address.";
        $showRegister = true;
    } elseif ($password !== $confirm) {
        $message = "Passwords do not match.";
        $showRegister = true;
    } else {
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO staff (name, email, password) VALUES (?, ?, ?)");
        if ($stmt->execute([$name, $email, $hashed])) {
            header("Location: staff.php?registered=1");
            exit();
        } else {
            $message = "Registration failed. Email may already exist.";
            $showRegister = true;
        }
    }
}

// Login
if (isset($_POST['login'])) {
    $email = $_POST['login_email'];
    $password = $_POST['login_password'];

    $stmt = $pdo->prepare("SELECT * FROM staff WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['staff'] = $user['name'];
        header("Location: staff_dashboard.php");
        exit();
    } else {
        $message = "Invalid login credentials.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Staff Portal</title>
    <link rel="stylesheet" href="staff.css">
</head>
<body>
<div class="container">
    <div class="left">
        <div class="icon-circle">
            <img src="https://cdn-icons-png.flaticon.com/512/149/149071.png" alt="Staff Icon">
        </div>
        <h2>Staff Panel</h2>
    </div>

    <div class="right">
        <div class="form-box">
            <img src="images/kegg.jpg" alt="K-EGG Logo" class="logo">
            <h2 class="form-title"><?= $showRegister ? "Register" : "Staff Login" ?></h2>

            <?php if ($message): ?>
                <div class="message" id="msgBox"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <!-- Login Form -->
            <form id="loginForm" method="POST" style="<?= $showRegister ? 'display:none;' : 'display:block;' ?>">
                <input type="email" name="login_email" placeholder="Email" required>
                <div class="password-wrapper">
                    <input type="password" name="login_password" id="login_password" placeholder="Password" required>
                    <span class="toggle-password" onclick="togglePassword('login_password')">👁️</span>
                </div>
                <button type="submit" name="login">Login</button>
            </form>

            <!-- Register Form -->
            <form id="registerForm" method="POST" style="<?= $showRegister ? 'display:block;' : 'display:none;' ?>">
                <input type="text" name="name" placeholder="Full Name" required>
                <input type="email" name="email" placeholder="Email" required>
                <div class="password-wrapper">
                    <input type="password" name="reg_password" id="reg_password" placeholder="Password" required>
                    <span class="toggle-password" onclick="togglePassword('reg_password')">👁️</span>
                </div>
                <div class="password-wrapper">
                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required>
                    <span class="toggle-password" onclick="togglePassword('confirm_password')">👁️</span>
                </div>
                <button type="submit" name="register">Register</button>
            </form>

            <!-- Tabs -->
            <div class="tab">
                <button type="button" onclick="toggleForm('login')">Login</button>
                <button type="button" onclick="toggleForm('register')">Register</button>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleForm(type) {
        document.getElementById('loginForm').style.display = type === 'login' ? 'block' : 'none';
        document.getElementById('registerForm').style.display = type === 'register' ? 'block' : 'none';
        document.querySelector('.form-title').textContent = type === 'login' ? 'Staff Login' : 'Register';
    }

    function togglePassword(id) {
        const input = document.getElementById(id);
        input.type = input.type === 'password' ? 'text' : 'password';
    }
</script>

<script>
    // Hide message after 5 seconds
    setTimeout(() => {
        const msgBox = document.getElementById('msgBox');
        if (msgBox) {
            msgBox.style.opacity = '0';
            msgBox.style.transition = 'opacity 0.5s ease';
            setTimeout(() => msgBox.style.display = 'none', 500);
        }
    }, 5000);
</script>

</body>
</html>
