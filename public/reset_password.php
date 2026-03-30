<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (is_logged_in()) redirect('index.php');
if (!isset($_SESSION['reset_email']) || !isset($_SESSION['code_verified'])) {
    redirect('forgot_password.php');
}

$error_msg = '';
$success_msg = '';
$email = $_SESSION['reset_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (strlen($password) < 6) {
        $error_msg = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error_msg = "Passwords do not match.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $update = $pdo->prepare("UPDATE users SET password = ?, reset_code = NULL, reset_expires_at = NULL WHERE email = ?");
        if ($update->execute([$hashed_password, $email])) {
            unset($_SESSION['reset_email']);
            unset($_SESSION['code_verified']);
            $success_msg = "Password updated successfully! You can now sign in.";
        } else {
            $error_msg = "Failed to update password. Please try again.";
        }
    }
}

$page_title = "Reset Password";
include 'includes/header.php';
?>

<main class="auth-page">
    <div class="auth-box">
        <h2>Reset Password</h2>
        <p style="color: #888; text-align: center; margin-bottom: 25px;">Create a new strong password for your account.</p>
        
        <?php if($error_msg): ?>
        <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?></div>
        <?php endif; ?>

        <?php if($success_msg): ?>
        <div style="background: rgba(46, 213, 115, 0.1); border: 1px solid #2ed573; color: #2ed573; padding: 15px; border-radius: 8px; margin-bottom: 25px; text-align: center;">
            <i class="fas fa-check-circle"></i> <?php echo $success_msg; ?>
        </div>
        <p class="auth-footer"><a href="login.php" class="btn-auth" style="display: block; text-decoration: none;">Sign In Now</a></p>
        <?php else: ?>
        <form method="POST">
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="password" required placeholder="Min 6 characters">
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" required placeholder="Repeat new password">
            </div>
            <button type="submit" name="reset_password" class="btn-auth">Reset Password</button>
        </form>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
