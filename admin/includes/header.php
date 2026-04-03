<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Admin Panel'; ?> - MovieFizz</title>
    <?php
    $settings = get_all_settings();
    $site_name = $settings['site_name'] ?? SITE_NAME;
    $site_logo = $settings['site_logo'] ?? '';
    $site_icon = $settings['site_icon'] ?? '';
    ?>
    <?php if(!empty($site_icon)): ?>
    <link rel="icon" href="<?php echo $site_icon; ?>" type="image/x-icon">
    <?php endif; ?>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/admin/assets/css/admin_style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Custom Deletion Modal */
        #deleteModal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(5px);
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: #252525;
            padding: 30px;
            border-radius: 15px;
            width: 400px;
            text-align: center;
            border: 1px solid #333;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            animation: modalPop 0.3s ease-out;
        }
        @keyframes modalPop { 0% { transform: scale(0.9); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }
        .modal-icon {
            font-size: 50px;
            color: #ff3e3e;
            margin-bottom: 20px;
        }
        .modal-title { font-size: 22px; color: #fff; margin-bottom: 15px; }
        .modal-msg { color: #ccc; margin-bottom: 25px; line-height: 1.5; }
        .modal-btns { display: flex; gap: 15px; justify-content: center; }
        .btn-modal { padding: 10px 25px; border-radius: 8px; cursor: pointer; border: none; font-weight: 600; transition: 0.2s; }
        .btn-cancel { background: #444; color: #fff; }
        .btn-cancel:hover { background: #555; }
        .btn-confirm { background: #ff3e3e; color: #fff; }
        .btn-confirm:hover { background: #ff5252; }

        /* Global TMDB Modal - Ultra High Priority Full-Screen Fix */
        .modal { 
            display: none; 
            position: fixed !important; 
            z-index: 20000000 !important; 
            left: 0 !important; 
            top: 0 !important; 
            width: 100% !important; 
            height: 100% !important; 
            background: rgba(0,0,0,0.92) !important; 
            backdrop-filter: blur(10px); 
            overflow-y: auto; 
            margin:0 !important; 
            padding:0 !important;
            align-items: center !important;
            justify-content: center !important;
        }
        .modal.active { display: flex !important; }
        .modal-content { 
            background: #111; 
            margin: auto !important; 
            padding: 0; 
            width: 95%; 
            max-width: 1200px; 
            border-radius: 15px; 
            border: 1px solid #444; 
            overflow: hidden; 
            box-shadow: 0 50px 100px rgba(0,0,0,0.8); 
            position: relative; 
            z-index: 20000001 !important; 
            animation: modalSlide 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        @keyframes modalSlide { from { transform: translateY(-30px) scale(0.95); opacity: 0; } to { transform: translateY(0) scale(1); opacity: 1; } }
        .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #333; padding: 25px 35px; background: #1a1a1a; }
        .modal-header h3 { font-size: 22px; margin: 0; color: #fff; display: flex; align-items: center; gap: 15px; font-weight: 700; }
        .modal-header h3 i { color: #e50914; font-size: 28px; }
        .close-modal { font-size: 36px; cursor: pointer; color: #888; transition: 0.3s; line-height: 1; }
        .close-modal:hover { color: #fff; transform: rotate(90deg); }

        .tmdb-results-grid { display: grid !important; grid-template-columns: repeat(5, 1fr) !important; gap: 25px !important; padding: 35px !important; background: #0a0a0a; }
        .tmdb-item { background: #161616; border-radius: 12px; overflow: hidden; cursor: pointer; transition: all 0.4s ease; border: 1px solid #222; position: relative; }
        .tmdb-item:hover { transform: translateY(-10px); border-color: #e50914; box-shadow: 0 20px 40px rgba(229, 9, 20, 0.4); }
        .tmdb-item img { width: 100%; height: 280px; object-fit: cover; border-bottom: 1px solid #222; }
        .tmdb-info { padding: 18px; text-align: center; }
        .tmdb-info h4 { font-size: 14px; margin: 0 0 10px 0; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-weight: 600; }
        .tmdb-info p { font-size: 13px; color: #e50914; font-weight: bold; margin: 0; background: rgba(229, 9, 20, 0.15); display: inline-block; padding: 3px 12px; border-radius: 25px; }
    </style>
</head>
<body>
    <!-- Global Deletion Modal -->
    <div id="deleteModal" style="display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.85); backdrop-filter: blur(5px); align-items: center; justify-content: center;">
        <div class="modal-content" style="background: #252525; padding: 30px; border-radius: 15px; width: 400px; text-align: center; border: 1px solid #333; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
            <div class="modal-icon" style="font-size: 50px; color: #ff3e3e; margin-bottom: 20px;"><i class="fas fa-trash-alt"></i></div>
            <div class="modal-title" style="font-size: 22px; color: #fff; margin-bottom: 15px;">Confirm Deletion</div>
            <div class="modal-msg" style="color: #ccc; margin-bottom: 25px; line-height: 1.5;">Are you sure you want to permanently delete this? This action cannot be undone.</div>
            <div class="modal-btns" style="display: flex; gap: 15px; justify-content: center;">
                <button type="button" class="btn-modal btn-cancel" style="padding: 10px 25px; border-radius: 8px; cursor: pointer; border: none; font-weight: 600; background: #444; color: #fff;" onclick="closeDeleteModal()">Cancel</button>
                <button type="button" class="btn-modal btn-confirm" style="padding: 10px 25px; border-radius: 8px; cursor: pointer; border: none; font-weight: 600; background: #ff3e3e; color: #fff;" id="confirmDeleteBtn">Delete Now</button>
            </div>
        </div>
    </div>

    <!-- Global TMDB Modal -->
    <div id="tmdb_modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fab fa-imdb"></i> Select Correct Item</h3>
                <span class="close-modal" onclick="document.getElementById('tmdb_modal').style.display='none';">&times;</span>
            </div>
            <div id="tmdb_results_list" class="tmdb-results-grid">
                <!-- Results injected here -->
            </div>
        </div>
    </div>
    
    <script>
        let deleteCallback = null;
        function openDeleteModal(msg, callback) {
            if(msg) document.querySelector('.modal-msg').innerText = msg;
            deleteCallback = callback;
            document.getElementById('deleteModal').style.display = 'flex';
        }
        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }
        document.getElementById('confirmDeleteBtn').addEventListener('click', () => {
            if(deleteCallback) deleteCallback();
            closeDeleteModal();
        });

        // Mobile Sidebar Toggle Logic
        function toggleSidebar() {
            document.body.classList.toggle('sidebar-open');
        }

        document.addEventListener('DOMContentLoaded', () => {
            // Add hamburger to top-nav if exists
            const topNavLeft = document.querySelector('.top-nav .nav-left');
            if (topNavLeft) {
                const burger = document.createElement('div');
                burger.className = 'mobile-hamburger';
                burger.style.display = 'none'; // Controlled by CSS media queries
                burger.innerHTML = '<i class="fas fa-bars"></i>';
                burger.onclick = toggleSidebar;
                topNavLeft.prepend(burger);
            }
        });
    </script>

    <div class="admin-wrapper">
        <?php include INCLUDES_PATH . '/sidebar.php'; ?>
        <main class="main-content">
