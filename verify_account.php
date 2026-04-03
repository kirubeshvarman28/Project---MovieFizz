<?php
require_once __DIR__ . '/includes/db_connect.php';
require_once INCLUDES_PATH . '/functions.php';

if (is_logged_in()) redirect('index.php');

$pending = $_SESSION['pending_user'] ?? null;
$pending_email = $_SESSION['pending_email'] ?? null;

if (!$pending && !$pending_email) redirect('register.php');

$email = $pending ? $pending['email'] : $pending_email;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify'])) {
    $code = isset($_POST['code']) ? implode('', $_POST['code']) : '';
    
    if ($pending) {
        // Handle new registration
        if ($code === $pending['code']) {
            if (time() < $pending['expires']) {
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, is_verified) VALUES (?, ?, ?, 1)");
                if ($stmt->execute([$pending['username'], $pending['email'], $pending['password']])) {
                    $user_id = $pdo->lastInsertId();
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['username'] = $pending['username'];
                    $_SESSION['email'] = $pending['email'];
                    $_SESSION['user_role'] = 'user';
                    unset($_SESSION['pending_user']);
                    redirect('index.php?verified=1');
                } else {
                    $error = "Registration failed. Please try again.";
                }
            } else {
                $error = "Verification code has expired. Please register again.";
            }
        } else {
            $error = "Invalid verification code.";
        }
    } else if ($pending_email) {
        // Handle existing unverified user login
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_verified = 0");
        $stmt->execute([$pending_email]);
        $user = $stmt->fetch();

        if ($user) {
            if ($code === $user['verification_code']) {
                if (time() < strtotime($user['verification_expires_at'])) {
                    $stmt = $pdo->prepare("UPDATE users SET is_verified = 1, verification_code = NULL, verification_expires_at = NULL WHERE id = ?");
                    if ($stmt->execute([$user['id']])) {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['user_role'] = $user['role'];
                        unset($_SESSION['pending_email']);
                        redirect('index.php?verified=1');
                    }
                } else {
                    $error = "Verification code has expired. Please contact support or register again.";
                }
            } else {
                $error = "Invalid code.";
            }
        } else {
            redirect('login.php');
        }
    }
}

$page_title = "Verify Your Account";
include INCLUDES_PATH . '/header.php';
?>

<main class="auth-page">
    <div class="auth-box verification-box" style="max-width: 450px;">
        <h2>Verify Your Email</h2>
        <p style="color: var(--text-secondary); text-align: center; margin-bottom: 25px;">
            We've sent a 6-digit code to <br><strong><?php echo htmlspecialchars($email); ?></strong><br>
            <small style="display: block; margin-top: 10px; color: #888;">If you don't see it, please check your <strong>spam folder</strong>.</small>
        </p>
        
        <?php if($error): ?>
        <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" id="verifyForm">
            <div class="code-inputs" style="display: flex; gap: 10px; justify-content: center; margin-bottom: 30px;">
                <input type="text" name="code[]" maxlength="1" required class="code-input" autofocus>
                <input type="text" name="code[]" maxlength="1" required class="code-input">
                <input type="text" name="code[]" maxlength="1" required class="code-input">
                <input type="text" name="code[]" maxlength="1" required class="code-input">
                <input type="text" name="code[]" maxlength="1" required class="code-input">
                <input type="text" name="code[]" maxlength="1" required class="code-input">
            </div>
            <button type="submit" name="verify" class="btn-auth">Verify Account</button>
        </form>
        
        <p class="auth-footer" style="margin-top: 25px;">
            Didn't receive the code? <a href="register.php" style="color: var(--primary);">Try registering again</a>.
        </p>
    </div>
</main>

<style>
.code-input {
    width: 50px;
    height: 60px;
    background: #1a1a1a;
    border: 2px solid #333;
    border-radius: 8px;
    color: #fff;
    font-size: 24px;
    font-weight: bold;
    text-align: center;
    transition: all 0.3s ease;
}
.code-input:focus {
    border-color: var(--primary);
    box-shadow: 0 0 10px rgba(229, 9, 20, 0.3);
    outline: none;
    background: #222;
}
</style>

<script>
const inputs = document.querySelectorAll('.code-input');
inputs.forEach((input, index) => {
    input.addEventListener('input', (e) => {
        if (e.target.value.length === 1 && index < inputs.length - 1) {
            inputs[index + 1].focus();
        }
    });

    input.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && !e.target.value && index > 0) {
            inputs[index - 1].focus();
        }
    });
});
</script>

<?php include INCLUDES_PATH . '/footer.php'; ?>
