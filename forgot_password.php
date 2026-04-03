<?php
require_once __DIR__ . '/includes/db_connect.php';
require_once INCLUDES_PATH . '/functions.php';

if (is_logged_in()) redirect('index.php');

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_reset'])) {
    $email = clean_input($_POST['email']);

    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $code = generate_verification_code();
        $expires = date('Y-m-d H:i:s', strtotime('+5 minutes'));

        $update = $pdo->prepare("UPDATE users SET reset_code = ?, reset_expires_at = ? WHERE id = ?");
        if ($update->execute([$code, $expires, $user['id']])) {
            $subject = "Password Reset Verification Code";
            $message = "
                <p>Hello <strong>" . $user['username'] . "</strong>,</p>
                <p>You requested to reset your password. Use the following 6-digit verification code to proceed:</p>
                <div style='background: #E50914; color: #fff; font-size: 2rem; font-weight: 800; padding: 15px; text-align: center; border-radius: 8px; margin: 20px 0; letter-spacing: 5px;'>
                    $code
                </div>
                <p style='color: #ff4757;'><strong>Important:</strong> This code will expire in 10 minutes.</p>
                <p>If you didn't request this, you can safely ignore this email.</p>
            ";

            if (send_user_email($email, $subject, $message)) {
                $_SESSION['reset_email'] = $email;
                redirect('verify_code.php');
            } else {
                $error_msg = "Failed to send verification email. Please try again.";
            }
        } else {
            $error_msg = "Something went wrong. Please try again.";
        }
    } else {
        $error_msg = "No account found with that email address.";
    }
}

$page_title = "Forgot Password";
include INCLUDES_PATH . '/header.php';
?>

<main class="auth-page">
    <div class="auth-box">
        <h2>Forgot Password</h2>
        <p style="color: #888; text-align: center; margin-bottom: 25px;">Enter your email to receive a 6-digit verification code.</p>
        
        <?php if($error_msg): ?>
        <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" required placeholder="you@example.com">
            </div>
            <button type="submit" name="request_reset" class="btn-auth">Send Verification Code</button>
        </form>
        <p class="auth-footer"><a href="login.php"><i class="fas fa-arrow-left"></i> Back to Sign In</a></p>
    </div>
</main>

<?php include INCLUDES_PATH . '/footer.php'; ?>
