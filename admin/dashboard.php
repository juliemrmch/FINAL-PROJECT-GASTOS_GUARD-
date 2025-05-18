<?php
require_once '../includes/db.php';
require_once '../includes/auth_functions.php';
requireAdmin();
$admin = getCurrentUser();

// Admin-wide statistics
$total_users = getRow("SELECT COUNT(*) as count FROM users WHERE user_type != 'admin'")['count'];
$active_users = getRow("SELECT COUNT(*) as count FROM users WHERE user_type != 'admin' AND active = 1")['count'];
$total_expenses = getRow("SELECT COUNT(*) as count FROM expenses")['count'];
$total_budgets = getRow("SELECT COUNT(*) as count FROM budgets")['count'];
$total_categories = getRow("SELECT COUNT(*) as count FROM expense_categories")['count'];

// Add at the top to handle range selection
$spending_range = $_GET['spending_range'] ?? 'month';
if ($spending_range === 'week') {
    $start = date('Y-m-d', strtotime('monday this week'));
    $end = date('Y-m-d', strtotime('sunday this week'));
    $label = 'This Week';
} elseif ($spending_range === 'year') {
    $start = date('Y-01-01');
    $end = date('Y-12-31');
    $label = 'This Year';
} else {
    $start = date('Y-m-01');
    $end = date('Y-m-t');
    $label = 'This Month';
}

$spending_sql = "SELECT ec.name, ec.color, ec.icon, COALESCE(SUM(e.amount),0) as amount
    FROM expense_categories ec
    LEFT JOIN expenses e ON e.category_id = ec.category_id AND e.date_spent BETWEEN '$start' AND '$end'
    GROUP BY ec.category_id, ec.name, ec.color, ec.icon
    HAVING amount > 0
    ORDER BY amount DESC";

$spending_categories = getRows($spending_sql);
$spending_labels = array_map(function($cat) { return $cat['name']; }, $spending_categories);
$spending_amounts = array_map(function($cat) { return (float)$cat['amount']; }, $spending_categories);
$spending_colors = array_map(function($cat) { return $cat['color'] ?: '#2a344a'; }, $spending_categories);

// Add at the top to handle month selection for Monthly Overview
$monthly_overview_month = $_GET['monthly_overview_month'] ?? date('Y-m');
$mo_start = date('Y-m-01', strtotime($monthly_overview_month));
$mo_end = date('Y-m-t', strtotime($monthly_overview_month));
$mo_sql = "SELECT ec.name, ec.color, COALESCE(SUM(e.amount),0) as amount
    FROM expense_categories ec
    LEFT JOIN expenses e ON e.category_id = ec.category_id AND e.date_spent BETWEEN '$mo_start' AND '$mo_end'
    GROUP BY ec.category_id, ec.name, ec.color
    HAVING amount > 0
    ORDER BY amount DESC";
$mo_data = getRows($mo_sql);
$mo_labels = array_map(function($cat) { return $cat['name']; }, $mo_data);
$mo_amounts = array_map(function($cat) { return (float)$cat['amount']; }, $mo_data);
$mo_colors = array_map(function($cat) { return $cat['color'] ?: '#2a344a'; }, $mo_data);

// Get last 12 months for dropdown
$mo_months = [];
for ($i = 0; $i < 12; $i++) {
    $mo_months[] = date('Y-m', strtotime("-{$i} months"));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Gastos Guard</title>
    <link rel="stylesheet" href="../assets/css/normalize.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/user.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <div class="app-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo">
    <a href="dashboard.php" style="display: flex; align-items: center; gap: 8px; text-decoration: none;">
        <img src="../assets/images/logo.png" alt="Gastos Guard Logo" style="width: 40px; height: 40px;">
        <h1 style="margin: 0; font-size: 20px; color: #ff6b3d;">Gastos Guard</h1>
        <span class="admin-badge" style="margin-left: 10px;">Admin</span>
    </a>
    <button class="hamburger" aria-label="Toggle navigation">
        <span class="fas fa-bars"></span>
    </button>
</div>
            <nav class="nav">
                <ul>
                    <li class="active">
                        <a href="dashboard.php">
                            <span class="fas fa-tachometer-alt"></span>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="reports.php">
                            <span class="fas fa-chart-line"></span>
                            <span>Reports & Analytics</span>
                        </a>
                    </li>
                    <li>
                        <a href="users.php">
                            <span class="fas fa-users"></span>
                            <span>User Management</span>
                        </a>
                    </li>
                    <li>
                        <a href="settings.php">
                            <span class="fas fa-user"></span>
                            <span>My Profile</span>
                        </a>
                    </li>
                </ul>
            </nav>
            <div class="logout">
                <a href="#" id="logoutLink">
                    <span class="fas fa-sign-out-alt"></span>
                    <span>Logout</span>
                </a>
            </div>
        </div>
        </header>

        <!-- Main content -->
        <div class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="page-title">
                    <h3>Dashboard</h3>
                </div>
                <div class="header-right">
                    <?php if ($admin): ?>
                    <div class="user-info">
                        <div class="user-avatar">
                            <?php echo substr($admin['full_name'], 0, 1); ?>
                        </div>
                        <div class="user-details">
                            <h3><?php echo htmlspecialchars($admin['full_name']); ?></h3>
                            <p><?php echo htmlspecialchars($admin['email']); ?></p>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="user-info">
                        <div class="user-avatar">?</div>
                        <div class="user-details">
                            <h3>Admin</h3>
                            <p>Not logged in</p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </header>


            <!-- Statistics Cards -->
            <div class="dashboard-cards-grid"
                style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 24px; margin-bottom: 32px; margin-top: 24px;">
                <div class="card">
                    <div class="card-header flex items-center gap-2">
                        <span class="fas fa-users" style="color: #3b82f6;"></span>
                        <h2>Total Users</h2>
                    </div>
                    <div class="card-body text-center">
                        <span class="amount"
                            style="font-size: 2.2rem; font-weight: 700; color: #fff;"><?php echo $total_users; ?></span>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header flex items-center gap-2">
                        <span class="fas fa-user-check" style="color: #10b981;"></span>
                        <h2>Active Users</h2>
                    </div>
                    <div class="card-body text-center">
                        <span class="amount"
                            style="font-size: 2.2rem; font-weight: 700; color: #fff;"><?php echo $active_users; ?></span>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header flex items-center gap-2">
                        <span class="fas fa-receipt" style="color: #ff6b3d;"></span>
                        <h2>Total Expenses</h2>
                    </div>
                    <div class="card-body text-center">
                        <span class="amount"
                            style="font-size: 2.2rem; font-weight: 700; color: #fff;"><?php echo $total_expenses; ?></span>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header flex items-center gap-2">
                        <span class="fas fa-wallet" style="color: #f59e0b;"></span>
                        <h2>Total Budgets</h2>
                    </div>
                    <div class="card-body text-center">
                        <span class="amount"
                            style="font-size: 2.2rem; font-weight: 700; color: #fff;"><?php echo $total_budgets; ?></span>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header flex items-center gap-2">
                        <span class="fas fa-th-list" style="color: #d946ef;"></span>
                        <h2>Categories</h2>
                    </div>
                    <div class="card-body text-center">
                        <span class="amount"
                            style="font-size: 2.2rem; font-weight: 700; color: #fff;"><?php echo $total_categories; ?></span>
                    </div>
                </div>
            </div>

            <!-- Spending Overview & Monthly Overview -->
            <div class="two-column"
                style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 32px;">
                <!-- Spending Overview -->
                <div class="card">
                    <div class="card-header flex items-center gap-2">
                        <span class="fas fa-chart-bar" style="color: #ff6b3d;"></span>
                        <h2>Spending Overview</h2>
                        <form method="get" style="margin-left:auto;">
                            <select name="spending_range" onchange="this.form.submit()" style="padding: 4px 10px; border-radius: 6px; border: 1px solid #2a344a; background: #232a36; color: #fff;">
                                <option value="week" <?= $spending_range==='week'?'selected':'' ?>>This Week</option>
                                <option value="month" <?= $spending_range==='month'?'selected':'' ?>>This Month</option>
                                <option value="year" <?= $spending_range==='year'?'selected':'' ?>>This Year</option>
                            </select>
                        </form>
                    </div>
                    <div class="card-body">
                        <div id="spending-overview-chart-container" style="margin-top: 24px;">
                            <canvas id="spendingOverviewChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Monthly Overview -->
                <div class="card">
                    <div class="card-header flex items-center gap-2">
                        <span class="fas fa-calendar-alt" style="color: #3b82f6;"></span>
                        <h2>Monthly Overview</h2>
                        <form method="get" style="margin-left:auto;">
                            <select name="monthly_overview_month" onchange="this.form.submit()" style="padding: 4px 10px; border-radius: 6px; border: 1px solid #2a344a; background: #232a36; color: #fff;">
                                <?php foreach ($mo_months as $m): ?>
                                <option value="<?= $m ?>" <?= $monthly_overview_month===$m?'selected':'' ?>><?= date('F Y', strtotime($m.'-01')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                    <div class="card-body">
                        <div id="monthly-overview-chart-container" style="margin-top: 24px;">
                            <canvas id="monthlyOverviewChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity & Budget Summary -->
            <div class="two-column"
                style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 32px;">
                <!-- Recent Activity -->
                <div class="card">
                    <div class="card-header flex items-center gap-2">
                        <span class="fas fa-history" style="color: #d946ef;"></span>
                        <h2>Recent Expenses</h2>
                    </div>
                    <div class="card-body">
                        <?php
                        $recent_sql = "SELECT e.expense_id, u.full_name, e.description, e.amount, e.date_spent, ec.icon, ec.color
                            FROM expenses e
                            JOIN users u ON e.user_id = u.user_id
                            JOIN expense_categories ec ON e.category_id = ec.category_id
                            ORDER BY e.date_spent DESC, e.expense_id DESC
                            LIMIT 7";
                        $recent_activities = getRows($recent_sql);
                        ?>
                        <ul style="list-style: none; padding: 0; margin: 0;">
                            <?php foreach ($recent_activities as $act): ?>
                            <li style="margin-bottom: 16px; display: flex; align-items: center; gap: 12px;">
                                <span class="fas <?= $act['icon'] ?>"
                                    style="color: <?= $act['color'] ?>; font-size: 1.2rem;"></span>
                                <div style="flex:1;">
                                    <strong><?= htmlspecialchars($act['full_name']) ?></strong> spent <span
                                        style="color: #ff6b3d; font-weight: 600;">₱<?= number_format($act['amount'], 2) ?></span>
                                    <span style="color: #8792a8;">on <?= htmlspecialchars($act['description']) ?></span>
                                    <div style="font-size: 12px; color: #8792a8;">
                                        <?= date('M d, Y', strtotime($act['date_spent'])) ?></div>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <!-- Budget Summary -->
                <div class="card">
                    <div class="card-header flex items-center gap-2">
                        <span class="fas fa-wallet" style="color: #f59e0b;"></span>
                        <h2>Budget Summary</h2>
                    </div>
                    <div class="card-body">
                        <?php
                        $budget_sql = "SELECT b.title, ec.name as category, b.amount, b.start_date, b.end_date,
                            COALESCE(SUM(e.amount),0) as spent, ec.color
                            FROM budgets b
                            LEFT JOIN expense_categories ec ON b.category_id = ec.category_id
                            LEFT JOIN expenses e ON e.category_id = b.category_id AND e.date_spent BETWEEN b.start_date AND b.end_date
                            GROUP BY b.budget_id, b.title, ec.name, b.amount, b.start_date, b.end_date, ec.color
                            ORDER BY b.start_date DESC
                            LIMIT 5";
                        $budgets = getRows($budget_sql);
                        ?>
                        <ul style="list-style: none; padding: 0; margin: 0;">
                            <?php foreach ($budgets as $b):
                                $progress = $b['amount'] > 0 ? min(100, round(($b['spent'] / $b['amount']) * 100, 1)) : 0;
                                $status = $progress >= 100 ? 'budget-progress-exceeded' : ($progress >= 80 ? 'budget-progress-warning' : 'budget-progress-normal');
                            ?>
                            <li style="margin-bottom: 18px;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <strong><?= htmlspecialchars($b['title']) ?></strong>
                                        <div style="font-size: 13px; color: #8792a8;">
                                            <?= htmlspecialchars($b['category']) ?> |
                                            <?= date('M d', strtotime($b['start_date'])) ?> -
                                            <?= date('M d', strtotime($b['end_date'])) ?>
                                        </div>
                                    </div>
                                    <div style="text-align: right;">
                                        <span style="font-weight: 600; color: #fff;">₱<?= number_format($b['spent'], 2) ?></span>
                                        <span style="color: #8792a8; font-size: 13px;">/
                                            ₱<?= number_format($b['amount'], 2) ?></span>
                                        <?php if ($progress >= 100): ?>
                                            <span title="Exceeded budget" style="color: #ea5455; margin-left: 8px;"><i class="fas fa-exclamation-triangle"></i></span>
                                        <?php elseif ($progress >= 80): ?>
                                            <span title="Near budget limit" style="color: #f59e0b; margin-left: 8px;"><i class="fas fa-exclamation-circle"></i></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="progress-bar" style="margin-top: 8px;">
                                    <div class="progress <?= $status ?>"
                                        style="width: <?= $progress ?>%; height: 8px; background: <?= $b['color'] ?: '#10b981' ?>;">
                                    </div>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Logout Confirmation Modal -->
            <div class="modal" id="logoutConfirmModal" style="
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                justify-content: center;
                align-items: center;
                z-index: 1000;
                display: none;
            ">
                <div style="
                    background-color: var(--bg-card);
                    padding: var(--space-md);
                    border-radius: var(--radius-lg);
                    width: 90%;
                    max-width: 350px;
                    box-shadow: var(--shadow-md);
                    position: relative;
                    text-align: center;
                ">
                    <button id="closeLogoutConfirmModal" style="
                        position: absolute; top: 16px; right: 16px;
                        background: none; border: none; color: #fff; font-size: 24px; cursor: pointer;
                    ">×</button>
                    <div class="modal-header">
                        <h2 style="margin-top:0;">Confirm Logout</h2>
                    </div>
                    <div style="margin: 20px 0; color: #fff;">Are you sure you want to logout?</div>
                    <div style="display: flex; gap: 12px; justify-content: center;">
                        <button id="confirmLogoutBtn" class="btn danger" style="background:#ea5455; color:#fff;">Logout</button>
                        <button id="cancelLogoutBtn" class="btn secondary">Cancel</button>
                    </div>
                </div>
            </div>

        </div>
    </div>
    </div>

<script>
const ctx = document.getElementById('spendingOverviewChart').getContext('2d');
if (ctx) {
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($spending_labels) ?>,
            datasets: [{
                label: 'Amount',
                data: <?= json_encode($spending_amounts) ?>,
                backgroundColor: <?= json_encode($spending_colors) ?>,
                borderRadius: 8
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, ticks: { color: '#fff' } },
                x: { ticks: { color: '#fff' } }
            }
        }
    });
}

const moCtx = document.getElementById('monthlyOverviewChart').getContext('2d');
if (moCtx) {
    new Chart(moCtx, {
        type: 'pie',
        data: {
            labels: <?= json_encode($mo_labels) ?>,
            datasets: [{
                data: <?= json_encode($mo_amounts) ?>,
                backgroundColor: <?= json_encode($mo_colors) ?>
            }]
        },
        options: {
            plugins: { legend: { display: true, labels: { color: '#fff' } } },
            responsive: true,
            maintainAspectRatio: false
        }
    });
}
</script>
<script>
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
}
function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Optional: Close modal when clicking overlay or close icon
document.querySelectorAll('.close-modal, .modal-overlay').forEach(function(el) {
    el.addEventListener('click', function() {
        el.closest('.modal').style.display = 'none';
    });
});
</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// Logout confirmation modal logic
$(document).ready(function() {
    $('#logoutLink').on('click', function(e) {
        e.preventDefault();
        $('#logoutConfirmModal').css('display', 'flex');
    });
    $('#closeLogoutConfirmModal, #cancelLogoutBtn').on('click', function() {
        $('#logoutConfirmModal').hide();
    });
    $('#logoutConfirmModal').on('click', function(e) {
        if (e.target === this) $(this).hide();
    });
    $('#confirmLogoutBtn').on('click', function() {
        window.location.href = '../logout.php';
    });
});

document.addEventListener('DOMContentLoaded', function() {
    // Hamburger Menu Toggle for Admin Dashboard
    const hamburger = document.querySelector('.hamburger');
    const nav = document.querySelector('.nav');
    const logout = document.querySelector('.logout');
    if (hamburger && nav && logout) {
        hamburger.addEventListener('click', function() {
            this.classList.toggle('active');
            nav.classList.toggle('open');
            logout.classList.toggle('open');
        });
    }
});
</script>
</body>

</html>