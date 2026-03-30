<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (is_logged_in()) redirect('index.php');
if (!isset($_SESSION['reset_email'])) redirect('forgot_password.php');

$error_msg = '';
$email = $_SESSION['reset_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_code'])) {
    $code = clean_input($_POST['code']);

    $stmt = $pdo->prepare("SELECT id, reset_expires_at FROM users WHERE email = ? AND reset_code = ? LIMIT 1");
    $stmt->execute([$email, $code]);
    $user = $stmt->fetch();

    if ($user) {
        $now = date('Y-m-d H:i:s');
        if ($now <= $user['reset_expires_at']) {
            $_SESSION['code_verified'] = true;
            redirect('reset_password.php');
        } else {
            $error_msg = "Verification code has expired. Please request a new one.";
        }
    } else {
        $error_msg = "Invalid verification code. Please try again.";
    }
}

$page_title = "Verify Code";
include 'includes/header.php';
?>

<main class="auth-page">
    <div class="auth-box">
        <h2>Verify Code</h2>
        <p style="color: #888; text-align: center; margin-bottom: 25px;">We've sent a 6-digit code to <strong><?php echo htmlspecialchars($email); ?></strong>.</p>
        
        <?php if($error_msg): ?>
        <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Verification Code</label>
                <input type="text" name="code" required maxlength="6" placeholder="123456" 
                    style="letter-spacing: 5px; text-align: center; font-size: 1.5rem; font-weight: 700;">
            </div>
            <button type="submit" name="verify_code" class="btn-auth">Verify & Proceed</button>
        </form>
        <p class="auth-footer"><a href="forgot_password.php">Didn't get the code? Resend</a></p>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
