<?php
require_once 'includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['is_admin'] = $user['is_admin'];
        
        if ($user['is_admin']) {
            header('Location: admin.php');
        } else {
            header('Location: dashboard.php');
        }
        exit();
    } else {
        $error = "Invalid email or password";
    }
}

include 'includes/header.php';
?>

<div class="form-container">
    <h2 class="form-title">Login to Your Account</h2>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn">Login</button>
    </form>
    <p class="text-center mt-3">Don't have an account? <a href="signup.php">Sign Up</a></p>
    <p class="text-center mt-1" style="font-size: 12px; color: #666;">
        Admin Login: admin@cryptotrade.com / password
    </p>
</div>

<?php include 'includes/footer.php'; ?>