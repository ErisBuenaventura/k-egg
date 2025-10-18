<?php
session_start();
if (isset($_SESSION['admin'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <div class="left">
        <div class="icon-circle">
            <img src="https://cdn-icons-png.flaticon.com/512/149/149071.png" alt="Admin Icon">
        </div>
        <h2>Admin Panel</h2>
    </div>
    <div class="right">
        <div class="form-box">
            <img src="../images/kegg.jpg" alt="K-EGG Logo" class="logo">
            <h2 class="form-title">Admin Login</h2>
            <form action="login.php" method="POST" class="form">
                <input type="text" name="username" placeholder="Username" required>
                <div class="password-wrapper">
                    <input type="password" name="password" id="password" placeholder="Password" required>
                    <span class="toggle-password" onclick="togglePassword()">👁️</span>
                </div>
                <button type="submit">Login</button>
            </form>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const passwordField = document.getElementById("password");
    passwordField.type = passwordField.type === "password" ? "text" : "password";
}
</script>
</body>
</html>
