<?php
require_once __DIR__ . '/includes/db_connect.php';
require_once INCLUDES_PATH . '/functions.php';

if (!is_logged_in()) redirect('login.php');

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Fetch User Data
$user = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
} catch (Exception $e) {
    // If SELECT * fails, fallback to basic columns
    $stmt = $pdo->prepare("SELECT id, username, email, password, role, created_at FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
}

if (!$user) {
    session_destroy();
    redirect('login.php');
}

// Handle Profile Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $username = clean_input($_POST['username']);
        $bio = clean_input($_POST['bio']);
        
        // Validate username
        if (empty($username)) {
            $error = "Username cannot be empty.";
        } else {
            // Check if username taken by another user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $user_id]);
            if ($stmt->fetch()) {
                $error = "Username is already taken.";
            } else {
                // Update basic info
                $stmt = $pdo->prepare("UPDATE users SET username = ?, bio = ? WHERE id = ?");
                if ($stmt->execute([$username, $bio, $user_id])) {
                    $_SESSION['username'] = $username;
                    $success = "Profile updated successfully!";
                }
            }
        }
        
        // Handle Avatar Upload
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $filename = $_FILES['avatar']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $ext;
                $upload_folder = 'uploads/profiles/';
                
                if (!is_dir($upload_folder)) {
                    mkdir($upload_folder, 0755, true);
                }
                
                $upload_path = $upload_folder . $new_filename;
                
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_path)) {
                    // Delete old avatar if exists
                    if (!empty($user['profile_pic']) && file_exists($user['profile_pic'])) {
                        @unlink($user['profile_pic']);
                    }
                    
                    $stmt = $pdo->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
                    $stmt->execute([$upload_path, $user_id]);
                    $success = "Profile picture updated!";
                    
                    // Update current user data for the rest of the page
                    $user['profile_pic'] = $upload_path;
                } else {
                    $error = "Failed to save the uploaded image. Please check directory permissions.";
                }
            } else {
                $error = "Invalid image format. Allowed: " . implode(', ', $allowed);
            }
        }
    }
    
    // Handle Password Change
    if (isset($_POST['change_password'])) {
        $current = $_POST['current_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];
        
        if (password_verify($current, $user['password'])) {
            if ($new === $confirm) {
                $hashed = password_hash($new, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed, $user_id]);
                $success = "Password changed successfully!";
            } else {
                $error = "New passwords do not match.";
            }
        } else {
            $error = "Incorrect current password.";
        }
    }
    
    // Handle Account Deletion
    if (isset($_POST['delete_account'])) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        if ($stmt->execute([$user_id])) {
            session_destroy();
            redirect('index.php?deleted=1');
        }
    }

    // Refresh user data after updates
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
}

// Fetch Watchlist Count
$watchlist_count = 0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM watchlist WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $watchlist_count = $stmt->fetchColumn();
} catch (Exception $e) {}

// Fetch Recent Watchlist Activity
$recent_watchlist = [];
try {
    $stmt = $pdo->prepare("
        SELECT w.*, m.title, m.poster, m.type as media_type, m.id as media_id 
        FROM watchlist w 
        JOIN movies m ON w.movie_id = m.id 
        WHERE w.user_id = ? 
        ORDER BY w.id DESC LIMIT 3
    ");
    $stmt->execute([$user_id]);
    $recent_watchlist = $stmt->fetchAll();
} catch (Exception $e) {
    // Fallback for media_id if movie_id column doesn't exist
    try {
        $stmt = $pdo->prepare("
            SELECT w.*, m.title, m.poster, m.type as media_type, m.id as media_id 
            FROM watchlist w 
            JOIN movies m ON w.media_id = m.id 
            WHERE w.user_id = ? 
            ORDER BY w.id DESC LIMIT 3
        ");
        $stmt->execute([$user_id]);
        $recent_watchlist = $stmt->fetchAll();
    } catch (Exception $e2) {}
}

function get_profile_img_path($p) {
    if(empty($p)) return 'assets/img/placeholder.jpg';
    return (strpos($p,'http')===0 || strpos($p,'uploads')===0) ? $p : $p;
}

$page_title = "My Profile";
include INCLUDES_PATH . '/header.php';
?>

<main class="profile-page container">
    <div class="profile-layout">
        <!-- Sidebar Navigation -->
        <aside class="profile-sidebar">
            <div class="sidebar-user-card">
                <div class="avatar-preview-container">
                    <?php if(!empty($user['profile_pic'])): ?>
                        <div class="avatar-circle" style="background-image: url('<?php echo $user['profile_pic']; ?>');"></div>
                    <?php else: ?>
                        <div class="avatar-circle no-img"><?php echo strtoupper(substr($user['username'], 0, 1)); ?></div>
                    <?php endif; ?>
                </div>
                <h3><?php echo htmlspecialchars($user['username']); ?></h3>
                <p class="joined-date">Member since <?php echo date('M Y', strtotime($user['created_at'])); ?></p>
            </div>
            
            <nav class="sidebar-nav">
                <a href="#general" class="nav-item active" data-target="general"><i class="fas fa-user-edit"></i> General Settings</a>
                <a href="#security" class="nav-item" data-target="security"><i class="fas fa-shield-alt"></i> Security</a>
                <a href="#watchlist" class="nav-item" data-target="watchlist"><i class="fas fa-list"></i> Activity & List</a>
                <a href="#danger" class="nav-item danger" data-target="danger"><i class="fas fa-exclamation-triangle"></i> Danger Zone</a>
            </nav>
        </aside>
        
        <!-- Main Content Area -->
        <section class="profile-content">
            <?php if ($success): ?>
                <div class="msg-box success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="msg-box error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <!-- General Settings Section -->
            <div id="general" class="settings-section active">
                <div class="section-header">
                    <h2>General Settings</h2>
                    <p>Update your basic account information and avatar.</p>
                </div>
                
                <form method="POST" enctype="multipart/form-data" class="premium-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email Address <small>(Contact admin to change)</small></label>
                            <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly style="background: rgba(255,255,255,0.03); cursor: not-allowed;">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Bio / About Me</label>
                        <textarea name="bio" rows="3" placeholder="Tell us about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Profile Picture</label>
                        <div class="avatar-upload-wrapper">
                            <div class="avatar-preview">
                                <img id="avatar-preview-img" src="<?php echo !empty($user['profile_pic']) ? $user['profile_pic'] : 'assets/images/logo.png'; ?>" alt="Preview" onerror="this.src='assets/images/logo.png'">
                            </div>
                            <div class="upload-info">
                                <input type="file" name="avatar" id="avatarInput" hidden accept="image/*">
                                <button type="button" class="btn-upload" onclick="document.getElementById('avatarInput').click()">
                                    <i class="fas fa-cloud-upload-alt"></i> Choose Image
                                </button>
                                <p class="upload-tip">JPG, PNG or WebP. Max 2MB.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="update_profile" class="btn-save">Save Changes</button>
                    </div>
                </form>
            </div>

            <!-- Security Section -->
            <div id="security" class="settings-section">
                <div class="section-header">
                    <h2>Account Security</h2>
                    <p>Manage your password and security preferences.</p>
                </div>
                
                <form method="POST" class="premium-form">
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" name="current_password" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" name="new_password" required>
                        </div>
                        <div class="form-group">
                            <label>Confirm New Password</label>
                            <input type="password" name="confirm_password" required>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="change_password" class="btn-save">Update Password</button>
                    </div>
                </form>
            </div>

            <!-- Watchlist Section -->
            <div id="watchlist" class="settings-section">
                <div class="section-header">
                    <h2>My Activity</h2>
                    <p>You have <strong><?php echo $watchlist_count; ?></strong> items in your watchlist.</p>
                </div>
                
                <div class="activity-grid">
                    <?php if (empty($recent_watchlist)): ?>
                        <div class="empty-state">
                            <i class="fas fa-list"></i>
                            <p>No items in your watchlist yet.</p>
                            <a href="index.php" class="btn-pill">Browse Content</a>
                        </div>
                    <?php else: ?>
                        <?php foreach($recent_watchlist as $item): ?>
                        <div class="activity-card">
                            <img src="<?php echo get_profile_img_path($item['poster']); ?>" alt="<?php echo $item['title']; ?>">
                            <div class="activity-info">
                                <h4><?php echo $item['title']; ?></h4>
                                <span class="tag"><?php echo strtoupper($item['media_type'] ?? 'MOVIE'); ?></span>
                            </div>
                            <a href="<?php echo ($item['media_type'] ?? 'movie') == 'movie' ? 'movie.php?id=' : 'show.php?id='; echo $item['media_id']; ?>" class="view-btn">View</a>
                        </div>
                        <?php endforeach; ?>
                        <a href="watchlist.php" class="view-all">View All Watchlist <i class="fas fa-arrow-right"></i></a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Danger Zone -->
            <div id="danger" class="settings-section">
                <div class="section-header danger">
                    <h2>Danger Zone</h2>
                    <p>Deleting your account is permanent. All your data will be lost.</p>
                </div>
                
                <div class="danger-box">
                    <div class="danger-info">
                        <h3>Delete Account</h3>
                        <p>Once you delete your account, there is no going back. Please be certain.</p>
                    </div>
                    <button type="button" class="btn-delete" onclick="openDeleteModal()">Delete My Account</button>
                </div>
            </div>
        </section>
    </div>
</main>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal-overlay" style="display:none; z-index: 2000;">
    <div class="modal-content" style="max-width: 400px; text-align: center;">
        <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: #ff3e3e; margin-bottom: 20px;"></i>
        <h2>Are you absolutely sure?</h2>
        <p style="margin: 15px 0; color: #aaa;">This action cannot be undone. All your watchlist items and preferences will be permanently wiped.</p>
        <form method="POST">
            <button type="submit" name="delete_account" class="btn-delete" style="width: 100%; margin-bottom: 10px;">Yes, Delete My Account</button>
            <button type="button" class="btn-cancel" onclick="closeDeleteModal()" style="width: 100%; background: #333; color: #fff; border: none; padding: 12px; border-radius: 4px; cursor: pointer;">Cancel</button>
        </form>
    </div>
</div>

<style>
/* Profile Page Styles */
.profile-page { padding-top: 120px; padding-bottom: 80px; }
.profile-layout { display: grid; grid-template-columns: 300px 1fr; gap: 40px; align-items: start; }

/* Sidebar */
.profile-sidebar { background: var(--bg-card); border-radius: var(--radius-lg); padding: 30px; position: sticky; top: 120px; border: 1px solid var(--border-subtle); }
.sidebar-user-card { text-align: center; margin-bottom: 30px; padding-bottom: 30px; border-bottom: 1px solid var(--border-subtle); }
.avatar-circle { width: 100px; height: 100px; border-radius: 50%; margin: 0 auto 15px; background-size: cover; background-position: center; border: 3px solid var(--primary); }
.avatar-circle.no-img { background: #333; display: flex; align-items: center; justify-content: center; font-size: 40px; font-weight: 700; color: var(--primary); }
.joined-date { font-size: 13px; color: var(--text-muted); margin-top: 5px; }

.sidebar-nav { display: flex; flex-direction: column; gap: 8px; }
.nav-item { padding: 12px 18px; border-radius: 8px; color: var(--text-secondary); transition: all 0.3s ease; display: flex; align-items: center; gap: 12px; font-weight: 500; }
.nav-item i { width: 20px; text-align: center; font-size: 14px; }
.nav-item:hover, .nav-item.active { background: rgba(255,255,255,0.05); color: #fff; }
.nav-item.active { border-left: 3px solid var(--primary); }
.nav-item.danger:hover { color: #ff3e3e; }

/* Content Area */
.profile-content { background: var(--bg-card); border-radius: var(--radius-lg); padding: 40px; border: 1px solid var(--border-subtle); min-height: 600px; }
.settings-section { display: none; }
.settings-section.active { display: block; animation: fadeIn 0.4s ease; }

@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

.section-header { margin-bottom: 35px; }
.section-header h2 { font-size: 24px; margin-bottom: 8px; }
.section-header p { color: var(--text-secondary); font-size: 15px; }
.section-header.danger h2 { color: #ff3e3e; }

/* Forms */
.premium-form { display: flex; flex-direction: column; gap: 25px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.form-group { display: flex; flex-direction: column; gap: 10px; }
.form-group label { font-size: 14px; font-weight: 600; color: var(--text-secondary); }
.form-group input, .form-group textarea { background: #000; border: 1px solid var(--border-light); border-radius: 8px; padding: 14px; color: #fff; width: 100%; transition: border 0.3s; }
.form-group input:focus, .form-group textarea:focus { border-color: var(--primary); outline: none; }

.avatar-upload-wrapper { display: flex; align-items: center; gap: 25px; background: rgba(0,0,0,0.2); padding: 20px; border-radius: 12px; }
.avatar-preview { width: 80px; height: 80px; border-radius: 50%; overflow: hidden; }
.avatar-preview img { width: 100%; height: 100%; object-fit: cover; }
.btn-upload { background: #333; color: #fff; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; transition: background 0.3s; }
.btn-upload:hover { background: #444; }
.upload-tip { font-size: 12px; color: var(--text-muted); margin-top: 8px; }

.btn-save { background: var(--primary); border: none; padding: 15px 30px; color: #fff; font-weight: 700; border-radius: 8px; cursor: pointer; transition: 0.3s; width: fit-content; }
.btn-save:hover { background: var(--primary-dark); transform: translateY(-2px); }

/* Activity */
.activity-grid { display: flex; flex-direction: column; gap: 15px; }
.activity-card { display: flex; align-items: center; gap: 20px; background: #000; padding: 15px; border-radius: 10px; border: 1px solid var(--border-subtle); }
.activity-card img { width: 45px; height: 65px; border-radius: 4px; object-fit: cover; }
.activity-info { flex: 1; }
.activity-info h4 { font-size: 16px; margin-bottom: 4px; }
.tag { font-size: 10px; background: var(--primary); padding: 2px 8px; border-radius: 10px; font-weight: 800; }
.view-btn { padding: 6px 15px; border: 1px solid #333; border-radius: 20px; font-size: 12px; font-weight: 600; }
.view-btn:hover { background: #fff; color: #000; }
.view-all { text-align: center; margin-top: 20px; color: var(--text-secondary); font-size: 14px; font-weight: 600; }

/* Danger Zone */
.danger-box { display: flex; justify-content: space-between; align-items: center; background: rgba(255, 62, 62, 0.05); border: 1px solid rgba(255, 62, 62, 0.2); padding: 30px; border-radius: 12px; }
.danger-info h3 { font-size: 18px; margin-bottom: 5px; }
.danger-info p { color: var(--text-secondary); font-size: 14px; }
.btn-delete { background: #ff3e3e; color: #fff; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 700; cursor: pointer; }
.btn-delete:hover { background: #d32f2f; }

/* Messages */
.msg-box { padding: 15px 20px; border-radius: 8px; margin-bottom: 30px; display: flex; align-items: center; gap: 12px; font-weight: 500; }
.msg-box.success { background: rgba(46, 204, 113, 0.1); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.2); }
.msg-box.error { background: rgba(231, 76, 60, 0.1); color: #e74c3c; border: 1px solid rgba(231, 76, 60, 0.2); }

@media (max-width: 992px) {
    .profile-layout { grid-template-columns: 1fr; }
    .profile-sidebar { position: static; }
}
</style>

<script>
// Tab Switching
document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', (e) => {
        e.preventDefault();
        const target = item.getAttribute('data-target');
        
        // Update Nav
        document.querySelectorAll('.nav-item').forEach(nav => nav.classList.remove('active'));
        item.classList.add('active');
        
        // Update Section
        document.querySelectorAll('.settings-section').forEach(sec => sec.classList.remove('active'));
        document.getElementById(target).classList.add('active');
    });
});

// Profile Pic Preview
document.getElementById('avatarInput').addEventListener('change', function(e) {
    if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('avatar-preview-img').src = e.target.result;
        };
        reader.readAsDataURL(this.files[0]);
    }
});

// Modal Logic
function openDeleteModal() { document.getElementById('deleteModal').style.display = 'flex'; }
function closeDeleteModal() { document.getElementById('deleteModal').style.display = 'none'; }
</script>

<?php include INCLUDES_PATH . '/footer.php'; ?>
