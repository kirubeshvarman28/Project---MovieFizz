<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$page_title = "Plans & Pricing";
include 'includes/header.php';

$plans = [];
try {
    $stmt = $pdo->query("SELECT * FROM subscription_plans WHERE status = 'active' ORDER BY price ASC");
    $plans = $stmt->fetchAll();
} catch(Exception $e) {}
?>

<main class="container">
    <div style="text-align:center; max-width:600px; margin:0 auto 20px;">
        <h1 class="section-title" style="margin-bottom:12px;">Choose Your Plan</h1>
        <p style="color:var(--text-muted); font-size:16px;">Unlimited movies, TV shows, and more. Watch anywhere. Cancel at any time.</p>
    </div>

    <?php if (!empty($plans)): ?>
    <div class="plans-grid">
        <?php foreach ($plans as $i => $plan): ?>
        <div class="plan-card <?php echo $i===1?'featured':''; ?>">
            <h3><?php echo $plan['name']; ?></h3>
            <div class="plan-price">
                $<?php echo number_format($plan['price'], 0); ?>
                <span>/<?php echo $plan['duration']; ?> days</span>
            </div>
            <?php if (!empty($plan['features'])): ?>
            <ul class="plan-features">
                <?php foreach (explode("\n", $plan['features']) as $feature): ?>
                <?php if(trim($feature)): ?>
                <li><i class="fas fa-check"></i> <?php echo trim($feature); ?></li>
                <?php endif; ?>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
            <button class="btn-plan">Get Started</button>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <i class="fas fa-crown"></i>
        <p>Subscription plans coming soon.</p>
    </div>
    <?php endif; ?>
</main>

<?php include 'includes/footer.php'; ?>
