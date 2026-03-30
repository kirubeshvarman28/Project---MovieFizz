<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$settings = get_all_settings();
$site_name = $settings['site_name'] ?? SITE_NAME;
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $name = clean_input($_POST['name']);
    $email = clean_input($_POST['email']);
    $subject = clean_input($_POST['subject']);
    $message = clean_input($_POST['message']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Invalid email format.";
    } else {
        $mail_subject = "Contact Form: $subject";
        $mail_body = "
            <strong>Name:</strong> $name<br>
            <strong>Email:</strong> $email<br>
            <strong>Subject:</strong> $subject<br><br>
            <strong>Message:</strong><br>".nl2br($message);
        
        if (send_admin_notification($mail_subject, $mail_body, $email)) {
            $success_msg = "Your message has been sent successfully! We'll get back to you soon.";
        } else {
            $error_msg = "Failed to send message. Please try again later.";
        }
    }
}

$page_title = "Contact Us";
include 'includes/header.php';
?>

<div class="container" style="padding-top: 120px; padding-bottom: 80px; max-width: 800px;">
    <div style="text-align: center; margin-bottom: 40px;">
        <h1 style="font-size: 3rem; font-weight: 800; margin-bottom: 10px;">Get in <span style="color: #E50914;">Touch</span></h1>
        <p style="color: #888; font-size: 1.1rem;">Have questions or feedback? We'd love to hear from you.</p>
    </div>

    <div style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; padding: 40px; backdrop-filter: blur(10px);">
        <?php if($success_msg): ?>
            <div style="background: rgba(46, 213, 115, 0.1); border: 1px solid #2ed573; color: #2ed573; padding: 15px; border-radius: 10px; margin-bottom: 25px; text-align: center;">
                <i class="fas fa-check-circle"></i> <?php echo $success_msg; ?>
            </div>
        <?php endif; ?>

        <?php if($error_msg): ?>
            <div style="background: rgba(255, 71, 87, 0.1); border: 1px solid #ff4757; color: #ff4757; padding: 15px; border-radius: 10px; margin-bottom: 25px; text-align: center;">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <form action="contact.php" method="POST">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div class="form-group">
                    <label style="display: block; margin-bottom: 8px; color: #ccc;">Full Name</label>
                    <input type="text" name="name" required placeholder="John Doe" 
                        style="width: 100%; background: #1a1a1a; border: 1px solid #333; color: #fff; padding: 12px 15px; border-radius: 8px; outline: none; transition: 0.3s;"
                        onfocus="this.style.borderColor='#E50914'" onblur="this.style.borderColor='#333'">
                </div>
                <div class="form-group">
                    <label style="display: block; margin-bottom: 8px; color: #ccc;">Email Address</label>
                    <input type="email" name="email" required placeholder="john@example.com" 
                        style="width: 100%; background: #1a1a1a; border: 1px solid #333; color: #fff; padding: 12px 15px; border-radius: 8px; outline: none; transition: 0.3s;"
                        onfocus="this.style.borderColor='#E50914'" onblur="this.style.borderColor='#333'">
                </div>
            </div>
            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; color: #ccc;">Subject</label>
                <input type="text" name="subject" required placeholder="How can we help?" 
                    style="width: 100%; background: #1a1a1a; border: 1px solid #333; color: #fff; padding: 12px 15px; border-radius: 8px; outline: none; transition: 0.3s;"
                    onfocus="this.style.borderColor='#E50914'" onblur="this.style.borderColor='#333'">
            </div>
            <div class="form-group" style="margin-bottom: 30px;">
                <label style="display: block; margin-bottom: 8px; color: #ccc;">Message</label>
                <textarea name="message" rows="5" required placeholder="Your message here..." 
                    style="width: 100%; background: #1a1a1a; border: 1px solid #333; color: #fff; padding: 12px 15px; border-radius: 8px; outline: none; transition: 0.3s; resize: vertical;"
                    onfocus="this.style.borderColor='#E50914'" onblur="this.style.borderColor='#333'"></textarea>
            </div>
            <button type="submit" name="send_message" 
                style="width: 100%; background: #E50914; color: #fff; border: none; padding: 15px; border-radius: 8px; font-weight: 700; font-size: 1.1rem; cursor: pointer; transition: 0.3s;"
                onmouseover="this.style.background='#ff0a16'" onmouseout="this.style.background='#E50914'">
                <i class="fas fa-paper-plane" style="margin-right: 10px;"></i> Send Message
            </button>
        </form>
    </div>

    <div style="margin-top: 50px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; text-align: center;">
        <div>
            <i class="fas fa-envelope" style="color: #E50914; font-size: 1.5rem; margin-bottom: 10px;"></i>
            <h4 style="margin-bottom: 5px;">Email Us</h4>
            <p style="color: #888; font-size: 0.9rem;"><?php echo htmlspecialchars($settings['email']); ?></p>
        </div>
        <div>
            <i class="fas fa-headset" style="color: #E50914; font-size: 1.5rem; margin-bottom: 10px;"></i>
            <h4 style="margin-bottom: 5px;">Support</h4>
            <p style="color: #888; font-size: 0.9rem;">24/7 Online Help</p>
        </div>
        <div>
            <i class="fas fa-share-alt" style="color: #E50914; font-size: 1.5rem; margin-bottom: 10px;"></i>
            <h4 style="margin-bottom: 5px;">Social</h4>
            <p style="color: #888; font-size: 0.9rem;">Follow our updates</p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
