<?php
require_once __DIR__ . '/includes/db_connect.php';
require_once INCLUDES_PATH . '/functions.php';

if (is_logged_in()) redirect('index.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = clean_input($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if ($user['status'] === 'active') {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            redirect('index.php');
        } else {
            $error = "Your account has been suspended.";
        }
    } else {
        $error = "Invalid email or password.";
    }
}

$page_title = "Sign In";
include INCLUDES_PATH . '/header.php';
?>

<main class="auth-page">
    <div class="auth-box">
        <h2>Sign In</h2>
        <?php if($error): ?>
        <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required placeholder="you@example.com">
            </div>
            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <label style="margin-bottom: 0;">Password</label>
                    <a href="forgot_password.php" style="font-size: 0.85rem; color: #E50914; text-decoration: none;">Forgot Password?</a>
                </div>
                <input type="password" name="password" required placeholder="Enter your password">
            </div>
            <button type="submit" class="btn-auth">Sign In</button>
        </form>
        <p class="auth-footer">New to <?php echo SITE_NAME; ?>? <a href="register.php">Sign up now</a>.</p>
    </div>
</main>

<?php include INCLUDES_PATH . '/footer.php'; ?>
