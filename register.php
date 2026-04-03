<?php
require_once __DIR__ . '/includes/db_connect.php';
require_once INCLUDES_PATH . '/functions.php';

if (is_logged_in()) redirect('index.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean_input($_POST['username']);
    $email = clean_input($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        if ($stmt->fetch()) {
            $error = "Username or Email already exists.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $verification_code = generate_verification_code();
            $expires_at = time() + (10 * 60); // 10 minutes from now

            $_SESSION['pending_user'] = [
                'username' => $username,
                'email' => $email,
                'password' => $hashed,
                'code' => $verification_code,
                'expires' => $expires_at
            ];

            $subject = "Verify Your Account - " . SITE_NAME;
            $message = "
            <div style='font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: auto; border: 1px solid #eee; border-radius: 10px; padding: 20px;'>
                <div style='text-align: center; margin-bottom: 30px;'>
                    <h1 style='color: #E50914; margin: 0;'>" . SITE_NAME . "</h1>
                </div>
                <p>Hello <strong>$username</strong>,</p>
                <p>Thank you for joining <strong>" . SITE_NAME . "</strong>! To complete your registration, please use the verification code below:</p>
                <div style='background: #f9f9f9; padding: 30px; text-align: center; border-radius: 8px; border: 1px solid #ddd; margin: 25px 0;'>
                    <span style='font-size: 36px; font-weight: bold; color: #E50914; letter-spacing: 10px;'>$verification_code</span>
                </div>
                <p style='font-size: 14px; color: #666;'>This code is valid for <strong>10 minutes</strong>. If you did not request this, please ignore this email.</p>
                <hr style='border: none; border-top: 1px solid #eee; margin: 30px 0;'>
                <p style='font-size: 12px; color: #999; text-align: center;'>&copy; " . date('Y') . " " . SITE_NAME . ". All rights reserved.</p>
            </div>";

            if (send_user_email($email, $subject, $message)) {
                redirect('verify_account.php');
            } else {
                $error = "Failed to send verification email. Please check your email or contact support.";
            }
        }
    }
}

$page_title = "Sign Up";
include INCLUDES_PATH . '/header.php';
?>

<main class="auth-page">
    <div class="auth-box">
        <h2>Sign Up</h2>
        <?php if($error): ?>
        <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required placeholder="Pick a username">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required placeholder="you@example.com">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="Create a password">
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" required placeholder="Confirm your password">
            </div>
            <button type="submit" class="btn-auth">Sign Up</button>
        </form>
        <p class="auth-footer">Already have an account? <a href="login.php">Sign in</a>.</p>
    </div>
</main>

<?php include INCLUDES_PATH . '/footer.php'; ?>
