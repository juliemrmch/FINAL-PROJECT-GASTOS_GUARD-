<?php
// Include necessary files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth_functions.php';
require_once '../includes/expense_functions.php';
require_once '../includes/budget_functions.php';
require_once '../includes/dashboard_functions.php';

// Check if user is logged in
requireLogin();

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Fetch user data
$user = getUserById($user_id);

// Fetch expense and budget data
$total_spent = get_total_expenses($user_id);
$categories = get_expenses_by_category($user_id);
error_log(print_r($categories, true));
$recent_expenses = get_recent_expenses($user_id);
$average_daily = get_average_daily_spending($user_id);
$budget_progress = get_budget_progress($user_id);
$monthly_spending = get_monthly_spending($user_id);
$comparison = compare_with_last_month($user_id);

// Build a map of category_id => budget amount for quick lookup
$category_budgets = [];
if (!empty($budget_progress)) {
    foreach ($budget_progress as $budget) {
        $category_budgets[$budget['category_id']] = $budget['total'];
    }
}
// Expose category budgets to JS
?>
<script>
const categoryBudgets = <?php echo json_encode($category_budgets); ?>;
</script>

<?php
// Budget Notifications
$warning_budgets = [];
$exceeded_budgets = [];
if (!empty($budget_progress)) {
    foreach ($budget_progress as $budget) {
        if ($budget['progress_percentage'] >= 100) {
            $exceeded_budgets[] = $budget;
        } elseif ($budget['progress_percentage'] >= 80) {
            $warning_budgets[] = $budget;
        }
    }
}
// Debug log
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard | Gastos Guard</title>
    <link rel="stylesheet" href="../assets/css/normalize.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/user.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
    #toast-container {
        position: fixed;
        top: 30px;
        right: 30px;
        z-index: 9999;
    }
    .toast {
        min-width: 250px;
        margin-bottom: 15px;
        padding: 16px 24px;
        border-radius: 6px;
        color: #fff;
        font-size: 16px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        opacity: 0.95;
        display: flex;
        align-items: center;
        animation: slideIn 0.4s;
        transition: opacity 0.4s;
    }
    .toast-warning {
        background: #FF9800;
    }
    .toast-error {
        background: #f44336;
    }
    .toast .fas {
        margin-right: 10px;
    }
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 0.95; }
    }
    </style>
</head>

<body>
    <div class="app-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo">
                <a href="dashboard.php">
                    <img src="../assets/images/logo.png" alt="Gastos Guard Logo">
                    <h1>Gastos Guard</h1>
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
                        <a href="expenses.php">
                            <span class="fas fa-money-bill-wave"></span>
                            <span>Expenses</span>
                        </a>
                    </li>
                    <li>
                        <a href="budgets.php">
                            <span class="fas fa-wallet"></span>
                            <span>Budget</span>
                        </a>
                    </li>
                    <li>
                        <a href="reports.php">
                            <span class="fas fa-chart-line"></span>
                            <span>Reports</span>
                        </a>
                    </li>
                    <li>
                        <a href="profile.php">
                            <span class="fas fa-user"></span>
                            <span>Profile</span>
                        </a>
                    </li>
                </ul>
            </nav>
            <div class="logout">
            <a href="#" onclick="openModal('logout-modal'); return false;">
    <span class="fas fa-sign-out-alt"></span>
    <span>Logout</span>
</a>
            </div>
        </div>

        <!-- Main content -->
        <div class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="page-title">
                    <h3>Dashboard</h3>
                </div>
                <div class="header-right">
                    <div class="user-info">
                        <div class="user-avatar">
                            <?php echo substr($user['full_name'], 0, 1); ?>
                        </div>
                        <div class="user-details">
                            <h3><?php echo htmlspecialchars($user['full_name']); ?></h3>
                            <p><?php echo htmlspecialchars($user['email']); ?></p>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content area -->
            <div class="content">
                <!-- Add New Expense Button -->
                <div class="action-button">
                    <button class="btn primary" id="add-expense-btn">
                        <span class="fas fa-plus"></span>
                        Add New Expense
                    </button>
                </div>

                <!-- Budget Notifications -->
                <?php if (!empty($exceeded_budgets)): ?>
                <div class="message error" style="margin-bottom: 20px;">
                    <span class="fas fa-exclamation-circle" style="margin-right: 8px;"></span>
                    <strong>Budget Exceeded:</strong>
                    <?php foreach ($exceeded_budgets as $b): ?>
                    <span style="display: inline-block; margin-right: 10px;">
                        <?php echo htmlspecialchars($b['title']); ?> (<?php echo htmlspecialchars($b['category']); ?>)
                    </span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($warning_budgets)): ?>
                <div class="message warning"
                    style="margin-bottom: 20px; background-color: rgba(255, 152, 0, 0.1); color: #FF9800;">
                    <span class="fas fa-exclamation-triangle" style="margin-right: 8px;"></span>
                    <strong>Warning:</strong> Nearing budget limit for:
                    <?php foreach ($warning_budgets as $b): ?>
                    <span style="display: inline-block; margin-right: 10px;">
                        <?php echo htmlspecialchars($b['title']); ?> (<?php echo htmlspecialchars($b['category']); ?>)
                        (<?php echo round($b['progress_percentage']); ?>%)
                    </span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Spending Overview -->
                <section class="card mb-4">
                    <div class="card-header">
                        <h2>Spending Overview</h2>
                        <a href="expenses.php" class="view-all">View All</a>
                    </div>
                    <div class="card-body">
                        <div class="total-spent">
                            <div class="total-spent-amount">
                                <h3>Total Spent</h3>
                                <h2 class="amount">₱<?php echo number_format($total_spent, 2, '.', ','); ?></h2>
                                <p class="balance">Balance:
                                    ₱<?php echo number_format($user['current_balance'], 2, '.', ','); ?></p>
                            </div>
                            <div class="comparison">
                                <?php if ($comparison['percentage'] > 0): ?>
                                <span class="trend-positive">
                                    <span class="fas fa-arrow-up"></span>
                                    <?php echo abs($comparison['percentage']); ?>% vs last month
                                </span>
                                <?php elseif ($comparison['percentage'] < 0): ?>
                                <span class="trend-negative">
                                    <span class="fas fa-arrow-down"></span>
                                    <?php echo abs($comparison['percentage']); ?>% vs last month
                                </span>
                                <?php else: ?>
                                <span>No change</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php error_log(print_r($categories, true)); ?>
                        <div class="spending-categories">
                            <?php if (empty($categories)): ?>
                            <div class="no-data">
                                <p>No expenses recorded for this period.</p>
                                <a href="expenses.php" class="btn secondary">Add Expense</a>
                            </div>
                            <?php else: ?>
                            <?php foreach ($categories as $category): ?>
                            <?php
                                $budget = isset($category_budgets[$category['category_id']]) ? $category_budgets[$category['category_id']] : null;
                                if ($budget && $budget > 0) {
                                    $progress = min(100, ($category['amount'] / $budget) * 100);
                                    if ($category['amount'] >= $budget) {
                                        $progress_color = '#f44336'; // red
                                    } elseif ($category['amount'] >= 0.8 * $budget) {
                                        $progress_color = '#FF9800'; // orange
                                    } else {
                                        $progress_color = $category['color'] ?? '#10b981'; // green
                                    }
                                } else {
                                    // fallback: show as share of total spent
                                    $progress = $total_spent > 0 ? ($category['amount'] / $total_spent) * 100 : 0;
                                    $progress_color = $category['color'] ?? '#10b981';
                                }
                            ?>
                            <div class="category-card">
                                <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                                <div class="category-amount">
                                    <span class="fas <?php echo htmlspecialchars($category['icon'] ?? 'fa-ellipsis-h'); ?>"></span>
                                    <h4>₱<?php echo number_format($category['amount'], 2, '.', ','); ?></h4>
                                    <?php if ($budget && $budget > 0): ?>
                                        <span style="font-size: 0.95em; color: #888; margin-left: 8px;">/ ₱<?php echo number_format($budget, 2, '.', ','); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress"
                                        style="width: <?php echo $progress; ?>%; background-color: <?php echo htmlspecialchars($progress_color); ?>;">
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <!-- Two Column Layout -->
                <div class="two-column">
                    <!-- Recent Expenses -->
                    <section class="card mb-4">
                        <div class="card-header">
                            <h2>Recent Expenses</h2>
                            <a href="expenses.php" class="view-all">View All</a>
                        </div>
                        <div class="card-body">
                            <div class="expense-list">
                                <?php foreach ($recent_expenses as $expense): ?>
                                <div class="expense-item">
                                    <div class="expense-icon"
                                        style="background-color: <?php echo htmlspecialchars($expense['color'] ?? '#10b981'); ?>;">
                                        <span
                                            class="fas <?php echo htmlspecialchars($expense['icon'] ?? 'fa-ellipsis-h'); ?>"></span>
                                    </div>
                                    <div class="expense-details">
                                        <h4><?php echo htmlspecialchars($expense['name']); ?></h4>
                                        <p><?php echo format_date($expense['date']); ?></p>
                                    </div>
                                    <div class="expense-amount">
                                        <h4>₱<?php echo number_format($expense['amount'], 2); ?></h4>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </section>

                    <!-- Average Daily Spending -->
                    <section class="card mb-4">
                        <div class="card-header">
                            <h2>Average Daily Spending</h2>
                        </div>
                        <div class="card-body">
                            <div class="daily-average" style="display: flex; justify-content: center; align-items: center;">
                                <div style="text-align: center;">
                                    <canvas id="dailyAveragePie" width="120" height="120"></canvas>
                                    <h2>₱<?php echo number_format($average_daily['average_daily'], 2); ?></h2>
                                    <p>Over 30 days</p>
                                </div>
                            </div>
                            <div class="daily-breakdown">
                                <?php foreach ($average_daily['daily_spending'] as $item): ?>
                                <div class="category-item">
                                    <div class="category-dot"
                                        style="background-color: <?php echo htmlspecialchars($item['color'] ?? '#10b981'); ?>;">
                                    </div>
                                    <div class="category-name"><?php echo htmlspecialchars($item['category']); ?></div>
                                    <div class="category-percentage"><?php echo $item['percentage']; ?>%</div>
                                    <div class="category-daily">₱<?php echo number_format($item['amount'], 2); ?></div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="info-note">
                                <span class="fas fa-info-circle"></span>
                                Based on the last 30 days
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Budget Progress -->
                <section class="card mb-4">
                    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                        <h2>Budget Progress</h2>
                        <div class="header-actions" style="margin-left: auto;">
                            <button class="icon-button" onclick="window.location.href='budgets.php'" style="background: none; border: none; cursor: pointer; padding: 8px; border-radius: 50%; transition: background-color 0.3s;">
                                <span class="fas fa-plus" style="color: #10b981; font-size: 1.2rem;"></span>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($budget_progress)): ?>
                        <div class="no-data">
                            <p>No budget data available.</p>
                            <a href="budgets.php" class="btn secondary">Set Budget</a>
                        </div>
                        <?php else: ?>
                        <?php foreach ($budget_progress as $budget): ?>
                        <div class="budget-item">
                            <div class="budget-info">
                                <h4><?php echo htmlspecialchars($budget['title']); ?>
                                    (<?php echo htmlspecialchars($budget['category']); ?>)</h4>
                                <div class="budget-amount">
                                    <span>₱<?php echo number_format($budget['current'], 2); ?></span>
                                    <span class="divider">/</span>
                                    <span>₱<?php echo number_format($budget['total'], 2); ?></span>
                                </div>
                            </div>
                            <div class="progress-bar">
                                <div class="progress budget-progress-<?php echo $budget['status']; ?>"
                                    style="width: <?php echo min(100, $budget['progress_percentage']); ?>%; 
                                                    background-color: <?php echo htmlspecialchars($budget['color'] ?? '#10b981'); ?>;">
                                </div>
                            </div>
                            <div class="budget-status">
                                <?php if ($budget['status'] === 'exceeded'): ?>
                                <span class="status-exceeded"><span class="fas fa-exclamation-circle"></span> Budget
                                    Exceeded</span>
                                <?php elseif ($budget['status'] === 'warning'): ?>
                                <span class="status-warning"><span class="fas fa-exclamation-triangle"></span> Nearing
                                    Limit (<?php echo round($budget['progress_percentage']); ?>%)</span>
                                <?php else: ?>
                                <span class="status-normal"><span class="fas fa-check-circle"></span> On Track
                                    (<?php echo round($budget['progress_percentage']); ?>%)</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Monthly Overview -->
                <section class="card mb-4">
                    <div class="card-header">
                        <h2>Monthly Overview</h2>
                    </div>
                    <div class="card-body">
                        <div class="month-total">
                            <div>
                                <canvas id="monthlyPie" width="120" height="120"></canvas>
                                <h2>₱<?php echo number_format($monthly_spending['total'], 2); ?></h2>
                                <p><?php echo $monthly_spending['month']; ?></p>
                            </div>
                        </div>
                        <div class="monthly-breakdown">
                            <?php foreach ($monthly_spending['categories'] as $category): ?>
                            <div class="category-item">
                                <div class="category-dot"
                                    style="background-color: <?php echo htmlspecialchars($category['color'] ?? '#10b981'); ?>;">
                                </div>
                                <div class="category-name"><?php echo htmlspecialchars($category['name']); ?></div>
                                <div class="category-amount">₱<?php echo number_format($category['amount'], 2); ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toast-container"></div>

    <!-- Logout Confirmation Modal -->
<div class="modal confirmation-modal-wrapper" id="logout-modal">
    <div class="modal-overlay"></div>
    <div class="modal-content confirmation-modal">
        <div class="modal-header">
            <h2>
                <span class="fas fa-sign-out-alt warning-icon"></span>
                Confirm Logout
            </h2>
            <span class="close-modal fas fa-times"></span>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to logout?</p>
        </div>
        <div class="modal-footer">
            <button class="btn secondary" onclick="closeModal('logout-modal')">Cancel</button>
            <a class="btn danger" id="confirm-logout" href="../logout.php">Logout</a>
        </div>
    </div>
</div>

    <!-- Add Expense Modal -->
    <div class="modal" id="add-expense-modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Expense</h2>
                <span class="close-modal fas fa-times" onclick="closeAddExpenseModal()"></span>
            </div>
            <form id="add-expense-form">
                <div class="form-group">
                    <label for="expense-date">Date</label>
                    <input type="date" id="expense-date" name="date" required>
                </div>
                <div class="form-group">
                    <label for="expense-description">Description</label>
                    <input type="text" id="expense-description" name="description" placeholder="E.g., Grocery Shopping"
                        required>
                </div>
                <div class="form-group">
                    <label for="expense-category">Category</label>
                    <select id="expense-category" name="category" required>
                        <option value="">Select Category</option>
                        <?php
                        $modal_categories = getRows("SELECT category_id, name FROM expense_categories WHERE is_default = 1 ORDER BY name ASC");
                        foreach ($modal_categories as $category): ?>
                        <option value="<?php echo $category['category_id']; ?>">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="expense-amount">Amount (₱)</label>
                    <input type="number" id="expense-amount" name="amount" step="0.01" min="0" placeholder="0.00"
                        required>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn secondary" onclick="closeAddExpenseModal()">Cancel</button>
                    <button type="submit" class="btn primary">Add Expense</button>
                </div>
                <div id="add-expense-message" class="message" style="display: none; margin-top: 10px;"></div>
            </form>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../assets/js/expense.js"></script>
    <script src="../assets/js/budget.js"></script>
    <script>
    const dailySpendingData = <?php echo json_encode($average_daily['daily_spending']); ?>;
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('dailyAveragePie').getContext('2d');
        const data = {
            labels: dailySpendingData.map(item => item.category),
            datasets: [{
                data: dailySpendingData.map(item => item.amount),
                backgroundColor: dailySpendingData.map(item => item.color || '#10b981'),
            }]
        };
        new Chart(ctx, {
            type: 'pie',
            data: data,
            options: {
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    });
    </script>
    <script>
    const monthlyCategories = <?php echo json_encode($monthly_spending['categories']); ?>;
    </script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('monthlyPie').getContext('2d');
        const data = {
            labels: monthlyCategories.map(cat => cat.name),
            datasets: [{
                data: monthlyCategories.map(cat => cat.amount),
                backgroundColor: monthlyCategories.map(cat => cat.color || '#10b981'),
            }]
        };
        new Chart(ctx, {
            type: 'pie',
            data: data,
            options: {
                plugins: {
                    legend: { display: false }
                }
            }
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        // [Original event listeners unchanged]
        // Hamburger Menu Toggle
        const hamburger = document.querySelector('.hamburger');
        const nav = document.querySelector('.nav');
        const logout = document.querySelector('.logout');
        hamburger.addEventListener('click', function() {
            this.classList.toggle('active');
            nav.classList.toggle('open');
            logout.classList.toggle('open');
        });
    });
    
    </script>
    <script>
    // Toast Notification Function
    function showToast(message, type = 'warning') {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');
        toast.className = 'toast toast-' + type;
        toast.innerHTML = message;
        container.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 400);
        }, 4000);
    }
    </script>
    <?php if (!empty($exceeded_budgets)): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        showToast(
            '<span class="fas fa-exclamation-circle"></span> <strong>Budget Exceeded:</strong> <?php echo implode(", ", array_map(function($b) { return htmlspecialchars($b["title"]) . " (" . htmlspecialchars($b["category"]) . ")"; }, $exceeded_budgets)); ?>',
            'error'
        );
    });
    </script>
    <?php endif; ?>
    <?php if (!empty($warning_budgets)): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        showToast(
            '<span class="fas fa-exclamation-triangle"></span> <strong>Warning:</strong> Nearing budget limit for: <?php echo implode(", ", array_map(function($b) { return htmlspecialchars($b["title"]) . " (" . htmlspecialchars($b["category"]) . ") (" . round($b["progress_percentage"]) . "%)"; }, $warning_budgets)); ?>',
            'warning'
        );
    });
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
    <?php endif; ?>
</body>

</html>