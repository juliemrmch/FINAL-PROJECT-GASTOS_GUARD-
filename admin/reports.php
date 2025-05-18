<?php
include_once '../includes/db.php';
require_once '../includes/auth_functions.php';
requireAdmin();
$admin = getCurrentUser();

// --- FILTERS ---
$reportType = $_GET['report_type'] ?? 'all';
$dateRange = $_GET['date_range'] ?? 'this_month';
$userId = $_GET['user_id'] ?? 'all';
$categoryId = $_GET['category_id'] ?? 'all';

// Date range logic
if ($dateRange === 'this_month') {
    $dateWhere = "MONTH(date_spent) = MONTH(CURDATE()) AND YEAR(date_spent) = YEAR(CURDATE())";
} elseif ($dateRange === 'last_month') {
    $dateWhere = "MONTH(date_spent) = MONTH(CURDATE() - INTERVAL 1 MONTH) AND YEAR(date_spent) = YEAR(CURDATE() - INTERVAL 1 MONTH)";
} elseif ($dateRange === 'last_3_months') {
    $dateWhere = "date_spent >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)";
} else {
    $dateWhere = "1"; // no filter
}
// User filter
$userWhere = ($userId !== 'all') ? "AND e.user_id = " . intval($userId) : "";
// Category filter
$catWhere = ($categoryId !== 'all') ? "AND e.category_id = " . intval($categoryId) : "";

// --- Spending by Category ---
$categoryLabels = [];
$categoryData = [];
$categoryColors = [];
$sql = "SELECT ec.name, ec.color, SUM(e.amount) as total
        FROM expenses e
        JOIN expense_categories ec ON e.category_id = ec.category_id
        WHERE $dateWhere $userWhere $catWhere
        GROUP BY e.category_id
        HAVING total > 0"; // Ensure no zero totals
$result = executeQuery($sql);
while ($row = $result->fetch_assoc()) {
    $categoryLabels[] = $row['name'];
    $categoryData[] = (float)$row['total'];
    $categoryColors[] = $row['color'] ?: '#cccccc';
}
$hasCategoryData = !empty($categoryData); // Check if data exists

// --- Monthly Comparison (grouped by category, last N months) ---
$monthlyCount = isset($_GET['monthly_count']) && in_array($_GET['monthly_count'], ['3','5']) ? (int)$_GET['monthly_count'] : 3;

// Get last N months as YYYY-MM
$monthlyLabels = [];
$monthKeys = [];
for ($i = $monthlyCount - 1; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $monthlyLabels[] = date('F Y', strtotime($month . '-01'));
    $monthKeys[] = $month;
}

// Get all categories
$categories = getRows("SELECT category_id, name, color FROM expense_categories");

// Build a map: [category_id][month] = 0
$categoryDataMap = [];
foreach ($categories as $cat) {
    foreach ($monthKeys as $month) {
        $categoryDataMap[$cat['category_id']][$month] = 0;
    }
}

// Query: sum per category per month
$sql = "SELECT e.category_id, DATE_FORMAT(e.date_spent, '%Y-%m') as ym, SUM(e.amount) as total
        FROM expenses e
        WHERE e.date_spent >= '" . $monthKeys[0] . "-01'
        AND e.date_spent <= '" . $monthKeys[$monthlyCount-1] . "-31'
        $userWhere $catWhere
        GROUP BY e.category_id, ym
        HAVING total > 0"; // Ensure no zero totals
$result = executeQuery($sql);
while ($row = $result->fetch_assoc()) {
    $categoryDataMap[$row['category_id']][$row['ym']] = (float)$row['total'];
}

// Prepare datasets for Chart.js
$defaultColors = ['#ff6384', '#36a2eb', '#ffce56', '#4bc0c0', '#9966ff', '#ff9f40', '#e7e9ed', '#2ecc71', '#e74c3c', '#8e44ad'];
$categoryDatasets = [];
$colorIndex = 0;
$hasMonthlyData = false; // Flag to check if any data exists
foreach ($categories as $cat) {
    $data = [];
    $hasNonZero = false;
    foreach ($monthKeys as $month) {
        $value = $categoryDataMap[$cat['category_id']][$month];
        $data[] = $value;
        if ($value > 0) {
            $hasNonZero = true;
        }
    }
    if ($hasNonZero) { // Only include categories with non-zero data
        $color = $cat['color'] ?: (isset($defaultColors[$colorIndex]) ? $defaultColors[$colorIndex] : '#cccccc');
        $categoryDatasets[] = [
            'label' => $cat['name'],
            'data' => $data,
            'backgroundColor' => $color
        ];
        $hasMonthlyData = true;
        $colorIndex++;
    }
}

// --- Daily Average Spending (selected month) ---
$das_month = $_GET['das_month'] ?? date('Y-m');
$das_start = date('Y-m-01', strtotime($das_month));
$das_end = date('Y-m-t', strtotime($das_month));
$sql = "SELECT SUM(amount) as total, COUNT(DISTINCT date_spent) as days
        FROM expenses e
        WHERE e.date_spent BETWEEN '$das_start' AND '$das_end' $userWhere $catWhere";
$row = getRow($sql);
$avgSpending = $row && $row['days'] > 0 ? round($row['total'] / $row['days'], 2) : 0;
$hasAvgSpending = $row && $row['total'] > 0; // Check if data exists

// Per-category daily average
$catAvg = [];
$catAvgPerc = [];
$sql = "SELECT ec.name, ec.color, SUM(e.amount)/COUNT(DISTINCT e.date_spent) as avg, SUM(e.amount) as total
        FROM expenses e
        JOIN expense_categories ec ON e.category_id = ec.category_id
        WHERE e.date_spent BETWEEN '$das_start' AND '$das_end' $userWhere $catWhere
        GROUP BY e.category_id
        HAVING total > 0"; // Ensure no zero totals
$result = executeQuery($sql);
$catTotal = 0;
while ($row = $result->fetch_assoc()) {
    $catAvg[$row['name']] = [
        'avg' => round($row['avg'], 2),
        'color' => $row['color'] ?: '#cccccc',
        'total' => (float)$row['total']
    ];
    $catTotal += $row['total'];
}
foreach ($catAvg as $cat => $data) {
    $catAvgPerc[$cat] = $catTotal > 0 ? round(($data['total'] / $catTotal) * 100, 1) : 0;
}
$hasCatAvgData = !empty($catAvg); // Check if data exists

// Get last 12 months for dropdown
$das_months = [];
for ($i = 0; $i < 12; $i++) {
    $das_months[] = date('Y-m', strtotime("-{$i} months"));
}

// --- Budget Efficiency (current month, all users) ---
$budgetWhere = '';
if ($userId !== 'all') $budgetWhere .= " AND b.user_id = " . intval($userId);
if ($categoryId !== 'all') $budgetWhere .= " AND b.category_id = " . intval($categoryId);
$sql = "SELECT SUM(b.amount) as budget, SUM(e.amount) as spent
        FROM budgets b
        LEFT JOIN expenses e ON b.category_id = e.category_id
            AND e.date_spent BETWEEN b.start_date AND b.end_date
        WHERE b.start_date <= CURDATE() AND b.end_date >= CURDATE() $budgetWhere";
$row = getRow($sql);
$budget = $row['budget'] ?: 0;
$spent = $row['spent'] ?: 0;
$utilization = $budget > 0 ? round(($spent / $budget) * 100, 1) : 0;
$hasBudgetData = $budget > 0 || $spent > 0; // Check if data exists

// --- Most/Least Efficient Categories ---
$efficiency = [];
$sql = "SELECT ec.name, SUM(b.amount) as budget, SUM(e.amount) as spent
        FROM budgets b
        LEFT JOIN expenses e ON b.category_id = e.category_id
            AND e.date_spent BETWEEN b.start_date AND b.end_date
        JOIN expense_categories ec ON b.category_id = ec.category_id
        WHERE b.start_date <= CURDATE() AND b.end_date >= CURDATE() $budgetWhere
        GROUP BY b.category_id
        HAVING budget > 0"; // Ensure budgets exist
$result = executeQuery($sql);
while ($row = $result->fetch_assoc()) {
    $used = $row['budget'] > 0 ? round(($row['spent'] / $row['budget']) * 100, 1) : 0;
    $efficiency[] = [
        'category' => $row['name'],
        'used' => $used,
        'saved' => $row['budget'] - $row['spent'],
        'over' => $row['spent'] - $row['budget']
    ];
}
$mostEff = null;
$leastEff = null;
foreach ($efficiency as $e) {
    if ($e['used'] <= 100 && (!$mostEff || $e['used'] < $mostEff['used'])) $mostEff = $e;
    if ($e['used'] > 100 && (!$leastEff || $e['used'] > $leastEff['used'])) $leastEff = $e;
}
$hasEfficiencyData = !empty($efficiency); // Check if data exists

// --- User and Category options for filters ---
$userOptions = getRows('SELECT user_id, username FROM users');
$catOptions = getRows('SELECT category_id, name FROM expense_categories');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Reports | Gastos Guard</title>
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
                    <li>
                        <a href="dashboard.php">
                            <span class="fas fa-tachometer-alt"></span>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="active">
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

        <!-- Main content -->
        <div class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="page-title">
                    <h3>Reports & Analytics</h3>
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

            <div class="content" style="width:100%;">
                <!-- Filter Card (solo row) -->
                <div class="card mb-4">
                    <div class="card-header"
                        style="display: flex; align-items: center; justify-content: space-between; gap: 16px;">
                        <h2 style="margin: 0;">Filters</h2>
                        <form method="get" id="resetFiltersForm" style="margin:0;">
                            <button type="submit" class="btn btn-secondary" style="padding: 6px 16px; font-size: 0.95rem;">Reset</button>
                        </form>
                    </div>
                    <div class="card-body">
                        <form method="get" id="reportFilters">
                            <div class="flex gap-4">
                                <div class="filter-group" style="flex:1;">
                                    <label>Date Range</label>
                                    <select class="form-control" name="date_range"
                                        onchange="document.getElementById('reportFilters').submit();">
                                        <option value="this_month"
                                            <?php if ($dateRange === 'this_month') echo 'selected'; ?>>This Month
                                        </option>
                                        <option value="last_month"
                                            <?php if ($dateRange === 'last_month') echo 'selected'; ?>>Last Month
                                        </option>
                                        <option value="last_3_months"
                                            <?php if ($dateRange === 'last_3_months') echo 'selected'; ?>>Last 3 Months
                                        </option>
                                    </select>
                                </div>
                                <div class="filter-group" style="flex:1;">
                                    <label>User</label>
                                    <select class="form-control" name="user_id"
                                        onchange="document.getElementById('reportFilters').submit();">
                                        <option value="all" <?php if ($userId === 'all') echo 'selected'; ?>>All Users
                                        </option>
                                        <?php foreach ($userOptions as $user): ?>
                                        <option value="<?php echo $user['user_id']; ?>"
                                            <?php if ($userId == $user['user_id']) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($user['username']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="filter-group" style="flex:2;">
                                    <label>Categories</label>
                                    <select class="form-control" name="category_id"
                                        onchange="document.getElementById('reportFilters').submit();">
                                        <option value="all" <?php if ($categoryId === 'all') echo 'selected'; ?>>All
                                            Categories</option>
                                        <?php foreach ($catOptions as $cat): ?>
                                        <option value="<?php echo $cat['category_id']; ?>"
                                            <?php if ($categoryId == $cat['category_id']) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($cat['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- First row: Spending by Category & Monthly Comparison -->
                <div class="flex gap-4 mb-4" style="width:100%; padding-right: 15px">
                    <!-- Spending by Category Card -->
                    <div class="card" style="min-height: 400px; min-width: 50%;">
                        <div class="card-header justify-between flex items-center">
                            <span>Spending by Category</span>
                            <a href="#" class="more-details" onclick="showCategoryDetails(); return false;">More Details <span class="fas fa-chevron-down"></span></a>
                        </div>
                        <div class="card-body flex flex-col items-center">
                            <?php if ($hasCategoryData): ?>
                            <div style="width: 260px; max-width: 100%;">
                                <canvas id="categoryPieChart" width="260" height="260"></canvas>
                            </div>
                            <div class="flex gap-4 mt-4">
                                <div class="legend-row">
                                    <?php foreach ($categoryLabels as $i => $cat): ?>
                                    <span style="display: inline-flex; align-items: center; gap: 6px; margin-right: 12px;">
                                        <span class="legend-dot" style="display:inline-block;width:12px;height:12px;border-radius:50%;background:<?php echo $categoryColors[$i]; ?>;"></span>
                                        <?php echo $cat; ?>
                                        <?php echo ($categoryData[$i] > 0 && array_sum($categoryData) > 0) ? round(($categoryData[$i]/array_sum($categoryData))*100,1) : 0; ?>%
                                    </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="no-data">
                                <p>No spending data available for the selected filters.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- Monthly Comparison Card -->
                    <div class="card" style="min-height: 400px; min-width: 50%;">
    <div class="card-header justify-between flex items-center">
        <span>Monthly Comparison</span>
        <form method="get" style="margin-left:auto;">
            <select name="monthly_count" onchange="this.form.submit()" style="padding: 4px 10px; border-radius: 6px; border: 1px solid #2a344a; background: #232a36; color: #fff;">
                <option value="3" <?= $monthlyCount==3?'selected':'' ?>>Last 3 Months</option>
                <option value="5" <?= $monthlyCount==5?'selected':'' ?>>Last 5 Months</option>
            </select>
            <?php foreach ($_GET as $k=>$v) { if ($k!=='monthly_count') echo "<input type='hidden' name='".htmlspecialchars($k)."' value='".htmlspecialchars($v)."'>"; } ?>
        </form>
    </div>
    <div class="card-body" style="height:340px;display:flex;align-items:center;justify-content:center;">
    <?php if ($hasMonthlyData): ?>
    <canvas id="monthlyBarChart" style="width:100% !important; height:320px !important; max-width:100%;" height="320"></canvas>
    <?php else: ?>
    <div class="no-data">
        <p>No monthly comparison data available for the selected filters.</p>
    </div>
    <?php endif; ?>
</div>
</div>
                </div>
                <div class="flex gap-4 mb-4" style="width:100%; padding-right: 15px">
                    <!-- Budget Efficiency Card -->
                    <div class="card"
                        style="min-height: 400px; min-width: 50%; display: flex; flex-direction: column; justify-content: space-between;">
                        <div class="card-header justify-between flex items-center">
                            <span>Budget Efficiency</span>
                        </div>
                        <div class="card-body" style="display: flex; flex-direction: column; gap: 1.5rem; ">
                            <?php if ($hasBudgetData): ?>
                            <div style="margin-bottom: 10%;">
                                <span style="font-weight: 500;">Overall Budget Utilization</span>
                                <div class="progress-bar mt-2"
                                    style="height: 18px; background: #f3f4f6; border-radius: 8px; overflow: hidden; margin-top: 8px;">
                                    <div class="progress"
                                        style="width:<?php echo $utilization; ?>%;background:#ff7f50; height: 100%; border-radius: 8px;">
                                    </div>
                                </div>
                                <span class="utilization-label"
                                    style="font-size: 1.2rem; font-weight: 600; color: #ff7f50; display: block; margin-top: 6px; letter-spacing: 1px;">
                                    <?php echo $utilization; ?>%
                                </span>
                            </div>
                            <?php if ($hasEfficiencyData): ?>
                            <div class="flex gap-4 mb-2"
                                style="display: flex; gap: 2rem; justify-content: space-between;">
                                <?php if ($mostEff): ?>
                                <div class="flex flex-col items-center"
                                    style="flex:1; background: #f0fdf4; border-radius: 8px; padding: 1rem; align-items: center;">
                                    <span class="eff-label text-light"
                                        style="font-size: 0.95rem; color: #10b981; font-weight: 500;">Most
                                        Efficient</span>
                                    <span class="eff-category"
                                        style="font-size: 1.1rem; color: #374151; font-weight: 600; margin: 0.2rem 0 0.3rem 0;"><i
                                            class="fas fa-arrow-up" style="color:#10b981;"></i>
                                        <?php echo $mostEff['category']; ?></span>
                                    <span class="eff-value"
                                        style="color:#10b981; font-size: 1.1rem; font-weight: 500;"><?php echo $mostEff['used']; ?>%
                                        used</span>
                                    <span class="eff-saved text-light"
                                        style="font-size: 0.95rem; color: #374151; margin-top: 0.2rem;">₱<?php echo max(0, $mostEff['saved']); ?>
                                        saved</span>
                                </div>
                                <?php endif; ?>
                                <?php if ($leastEff): ?>
                                <div class="flex flex-col items-center"
                                    style="flex:1; background: #fef2f2; border-radius: 8px; padding: 1rem; align-items: center;">
                                    <span class="eff-label text-light"
                                        style="font-size: 0.95rem; color: #ef4444; font-weight: 500;">Least
                                        Efficient</span>
                                    <span class="eff-category"
                                        style="font-size: 1.1rem; font-weight: 600; margin: 0.2rem 0 0.3rem 0;"><i
                                            class="fas fa-arrow-down" style="color:#ef4444;"></i>
                                        <?php echo $leastEff['category']; ?></span>
                                    <span class="eff-value"
                                        style="color:#ef4444; font-size: 1.1rem; font-weight: 500;"><?php echo $leastEff['used']; ?>%
                                        used</span>
                                    <span class="eff-over text-light"
                                        style="font-size: 0.95rem; color: #374151; margin-top: 0.2rem;">₱<?php echo max(0, $leastEff['over']); ?>
                                        over budget</span>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                            <div class="no-data">
                                <p>No efficiency data available for the selected filters.</p>
                            </div>
                            <?php endif; ?>
                            <?php else: ?>
                            <div class="no-data">
                                <p>No budget data available for the selected filters.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- Daily Average Spending Card -->
                    <div class="card" style="min-height: 400px; min-width: 50%;">
                        <div class="card-header justify-between flex items-center">
                            <span>Daily Average Spending</span>
                            <form method="get" style="margin-left:auto;">
                                <select name="das_month" onchange="this.form.submit()" style="padding: 4px 10px; border-radius: 6px; border: 1px solid #2a344a; background: #232a36; color: #fff;">
                                    <?php foreach ($das_months as $m): ?>
                                    <option value="<?= $m ?>" <?= $das_month===$m?'selected':'' ?>><?= date('F Y', strtotime($m.'-01')) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <!-- Keep other filters in the form -->
                                <?php foreach ($_GET as $k=>$v) { if ($k!=='das_month') echo "<input type='hidden' name='".htmlspecialchars($k)."' value='".htmlspecialchars($v)."'>"; } ?>
                            </form>
                        </div>
                        <div class="card-body">
                            <?php if ($hasAvgSpending && $hasCatAvgData): ?>
                            <div class="avg-spending text-center mb-4">
                                <span class="avg-amount"
                                    style="font-size:2rem;font-weight:600;">₱<?php echo $avgSpending; ?></span><br>
                                <span class="avg-label text-light">Average daily spending</span>
                            </div>
                            <div class="flex flex-col gap-2">
                                <?php foreach ($catAvg as $cat => $data): ?>
                                <div class="flex justify-between">
                                    <span><?php echo $cat; ?></span><span>₱<?php echo $data['avg']; ?></span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress"
                                        style="width:<?php echo $catAvgPerc[$cat]; ?>%;background:<?php echo $data['color']; ?>">
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <div class="no-data">
                                <p>No daily spending data available for the selected filters.</p>
                            </div>
                            <?php endif; ?>
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
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
        if (document.getElementById('categoryPieChart') && <?php echo json_encode($hasCategoryData); ?>) {
            new Chart(document.getElementById('categoryPieChart'), {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode($categoryLabels); ?>,
                    datasets: [{
                        data: <?php echo json_encode($categoryData); ?>,
                        backgroundColor: <?php echo json_encode($categoryColors); ?>,
                        borderWidth: 0
                    }]
                },
                options: {
                    cutout: '70%',
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }
        if (document.getElementById('monthlyBarChart') && <?php echo json_encode($hasMonthlyData); ?>) {
            new Chart(document.getElementById('monthlyBarChart'), {
                type: 'bar',
                data: {
                    labels: <?= json_encode($monthlyLabels) ?>,
                    datasets: <?= json_encode($categoryDatasets) ?>
                },
                options: {
                    indexAxis: 'x',
                    plugins: {
                        legend: { display: true }
                    },
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { color: '#fff' }
                        },
                        x: {
                            ticks: { color: '#fff' }
                        }
                    }
                }
            });
        }
        function showCategoryDetails() {
            document.getElementById('categoryDetailsModal').style.display = 'flex';
            // Build the table of all categories and their expenses for the current filters
            let html = `<table style="width:100%;border-collapse:collapse;color:#fff;">
                <thead>
                    <tr style='background:#232a36;'>
                        <th style='padding:8px 6px;border-bottom:1px solid #2a344a;text-align:left;'>Category</th>
                        <th style='padding:8px 6px;border-bottom:1px solid #2a344a;text-align:right;'>Amount</th>
                    </tr>
                </thead>
                <tbody>`;
            <?php if ($hasCategoryData): ?>
                <?php foreach ($categoryLabels as $i => $cat): ?>
                    html += `<tr>
                        <td style='padding:8px 6px;'>
                            <span style='display:inline-block;width:12px;height:12px;border-radius:50%;background:<?= $categoryColors[$i] ?>;margin-right:8px;'></span>
                            <?= addslashes($cat) ?>
                        </td>
                        <td style='padding:8px 6px;text-align:right;'>₱<?= number_format($categoryData[$i], 2) ?></td>
                    </tr>`;
                <?php endforeach; ?>
            <?php else: ?>
                html += `<tr><td colspan="2" style='padding:8px 6px;text-align:center;'>No data available</td></tr>`;
            <?php endif; ?>
            html += `</tbody></table>`;
            document.getElementById('categoryDetailsContent').innerHTML = html;
        }
        function closeCategoryDetails() {
            document.getElementById('categoryDetailsModal').style.display = 'none';
        }
        // Reset button clears filters
        const resetForm = document.getElementById('resetFiltersForm');
        if (resetForm) {
            resetForm.addEventListener('submit', function(e) {
                e.preventDefault();
                window.location.href = window.location.pathname;
            });
        }
        </script>
        <!-- Modal for More Details -->
        <div id="categoryDetailsModal" class="modal" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.5);justify-content:center;align-items:center;z-index:2000;">
            <div style="background:#232a36;padding:2rem;border-radius:12px;max-width:600px;width:90%;position:relative;">
                <button onclick="closeCategoryDetails();" style="position:absolute;top:12px;right:12px;background:none;border:none;color:#fff;font-size:1.5rem;">&times;</button>
                <h3 style="margin-top:0;">Spending by Category Details</h3>
                <div id="categoryDetailsContent">Loading...</div>
            </div>
        </div>
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
    </div>
</body>

</html>