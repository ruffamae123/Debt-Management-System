<?php
    session_start();
    include 'database/config.php';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = "Please enter both username and password.";
        } else {
            $sql = "SELECT * FROM users WHERE username=? AND password=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $username, $password);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $_SESSION['username'] = $username;
                header("Location: index.php");
                exit();
            } else {
                $error = "Invalid username or password.";
            }
        }
    }
    $message = $_GET['message'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Login | Debt Management System - Sari-sari Store</title>
    <link rel="stylesheet" type="text/css" href="css/login.css">
    <link rel="stylesheet" type="text/css" href="css/notification.css">

</head>
<body>

<div class="login-box">
    <h2>Login</h2>

    <?php if (!empty($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!empty($message)): ?>
        <div class="success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="post" action="">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" autocomplete="off" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>

        <input type="checkbox" id="show-password" onclick="togglePassword()">
        <label for="show-password" class="show-password-label">Show Password</label>

        <input type="submit" value="Login">
    </form>
</div>

<script>
    function togglePassword() {
        const pwField = document.getElementById("password");
        pwField.type = pwField.type === "password" ? "text" : "password";
    }

    // Auto-hide notifications after 3 seconds
    setTimeout(() => {
        const errorMsg = document.querySelector('.error');
        const successMsg = document.querySelector('.success');
        if (errorMsg) errorMsg.style.display = 'none';
        if (successMsg) successMsg.style.display = 'none';
    }, 3000);
</script>
</body>
</html>
