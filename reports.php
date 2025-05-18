<?php
// Include necessary files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth_functions.php';
require_once '../includes/expense_functions.php';
require_once '../includes/report_functions.php';

// Check if user is logged in
requireLogin();

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Fetch user data
$user = getUserById($user_id);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> User Reports | Gastos Guard</title>
    <link rel="stylesheet" href="../assets/css/normalize.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/user.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
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
                    <li>
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
                    <li class="active">
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
                    <h3>Reports</h3>
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

                <div class="month-selector">
                        <div class="filter-group custom-month-selector">
                            <label for="report-month">Select Month</label>
                                <span class="calendar-icon">
                                     <i class="fas fa-calendar-alt"></i>
                                 </span>
                                     <input type="month" id="report-month" name="report-month" class="month-input"
                                        value="<?php echo date('Y-m'); ?>">
                        </div>
                </div>

                <!-- Charts Section -->
                <div class="two-column">
                    <section class="card mb-4">
                        <div class="card-header">
                            <h2>Spending by Category</h2>
                        </div>
                        <div class="card-body" id="spending-chart-container" style="width: 400px; height: 300px; min-width: 400px; min-height: 300px; max-width: 400px; max-height: 300px; margin: 0 auto; display: flex; flex-direction: column; align-items: center;">
                            <canvas id="spendingChart" width="400" height="300"></canvas>
                            <div id="spending-category-description" class="category-description"></div>
                        </div>
                    </section>
                    <section class="card mb-4">
                        <div class="card-header">
                            <h2>Monthly Spending Trends</h2>
                        </div>
                        <div class="card-body" id="trends-chart-container">
                            <canvas id="trendsChart" width="400" height="300"></canvas>
                        </div>
                    </section>
                </div>

                <!-- Top Categories and Expense Growth -->
                <div class="two-column">
                    <section class="card">
                        <div class="card-header">
                            <h2>Top Spending Categories</h2>
                        </div>
                        <div class="card-body" id="top-categories-container">
                            <div class="no-data" id="no-categories-message" style="display: none;">
                                <p>No categories found.</p>
                            </div>
                        </div>
                    </section>
                    <section class="card">
                        <div class="card-header">
                            <h2>Expense Growth</h2>
                        </div>
                        <div class="card-body">
                            <div class="budget-progress-item">
                                <div class="budget-progress-header">
                                    <h4>Week-over-week</h4>
                                </div>
                                <div class="budget-progress-stats">
                                    <span>0.00%</span>
                                </div>
                                <div class="budget-progress-bar-container">
                                    <div class="budget-progress-bar" style="width: 0%;"></div>
                                </div>
                            </div>
                            <div class="budget-progress-item">
                                <div class="budget-progress-header">
                                    <h4>Month-over-month</h4>
                                </div>
                                <div class="budget-progress-stats">
                                    <span>0.00%</span>
                                </div>
                                <div class="budget-progress-bar-container">
                                    <div class="budget-progress-bar" style="width: 0%;"></div>
                                </div>
                            </div>
                            <div class="budget-progress-item">
                                <div class="budget-progress-header">
                                    <h4>Year-over-year</h4>
                                </div>
                                <div class="budget-progress-stats">
                                    <span>0.00%</span>
                                </div>
                                <div class="budget-progress-bar-container">
                                    <div class="budget-progress-bar" style="width: 0%;"></div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>

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

    <script>
    let spendingChart, trendsChart;

    function updateCharts(data) {
        if (spendingChart) spendingChart.destroy();
        if (trendsChart) trendsChart.destroy();

        // Handle Spending by Category (Pie Chart)
        const spendingContainer = document.getElementById('spending-chart-container');
        if (!data.spending_by_category || data.spending_by_category.length === 0) {
            spendingContainer.innerHTML = '<div class="no-data"><p>No data available for this period.</p></div>';
        } else {
            spendingContainer.innerHTML = '<canvas id="spendingChart" width="400" height="300"></canvas>' +
                '<div id="spending-category-description" class="category-description"></div>';
            const spendingCtx = document.getElementById('spendingChart').getContext('2d');
            spendingChart = new Chart(spendingCtx, {
                type: 'pie',
                data: {
                    labels: data.spending_by_category.map(item => item.name),
                    datasets: [{
                        data: data.spending_by_category.map(item => item.amount),
                        backgroundColor: data.spending_by_category.map(item => item.color)
                    }]
                },
                options: {
                    responsive: false,
                    maintainAspectRatio: false,
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const percent = data.spending_by_category[context.dataIndex].percent;
                                    const value = context.parsed;
                                    return `${context.label}: ₱${value} (${percent}%)`;
                                }
                            }
                        },
                        datalabels: {
                            color: '#fff',
                            font: {
                                weight: 'bold',
                                size: 14
                            },
                            formatter: function(value, context) {
                                const percent = data.spending_by_category[context.dataIndex].percent;
                                return percent > 0 ? percent + '%' : '';
                            }
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });
            // Add category description below the chart
            const descContainer = document.getElementById('spending-category-description');
            if (!data.spending_by_category || data.spending_by_category.length === 0) {
                descContainer.innerHTML = '';
            } else {
                descContainer.innerHTML = data.spending_by_category.map(item => `
                    <div class="category-desc-item" style="display: flex; align-items: center; margin-bottom: 6px;">
                        <span style="display: inline-block; width: 16px; height: 16px; background: ${item.color}; border-radius: 50%; margin-right: 8px;"></span>
                        <span style="margin-right: 8px;">${item.name}</span>
                        <span style="color: #888;">${item.percent}%</span>
                    </div>
                `).join('');
            }
        }

        // Handle Monthly Spending Trends (Line Chart)
        const trendsContainer = document.getElementById('trends-chart-container');
        const trendsData = data.monthly_spending_trends ? Object.values(data.monthly_spending_trends) : [];
        if (!trendsData || trendsData.length === 0 || trendsData.every(v => v === 0)) {
            trendsContainer.innerHTML = '<div class="no-data"><p>No data available for this period.</p></div>';
        } else {
            trendsContainer.innerHTML = '<canvas id="trendsChart" width="400" height="300"></canvas>';
            const trendsCtx = document.getElementById('trendsChart').getContext('2d');
            trendsChart = new Chart(trendsCtx, {
                type: 'line',
                data: {
                    labels: Object.keys(data.monthly_spending_trends),
                    datasets: [{
                        label: 'Spending (₱)',
                        data: Object.values(data.monthly_spending_trends),
                        borderColor: '#ff6b3d',
                        fill: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        // Handle Top Categories
        const totalSpending = data.spending_by_category && data.spending_by_category.length > 0 ? data
            .spending_by_category.reduce((sum, item) => sum + item.amount, 0) : 0;
        const categoriesContainer = document.getElementById('top-categories-container');
        if (!data.top_categories || data.top_categories.length === 0) {
            categoriesContainer.innerHTML = '<div class="no-data"><p>No categories found for this period.</p></div>';
        } else {
            categoriesContainer.innerHTML = data.top_categories.map(category => {
                return `
                        <div class="category-item">
                            <div class="category-dot" style="background-color: ${category.color}"></div>
                            <div class="category-name">${category.name}</div>
                            <div class="category-amount">₱${category.amount.toFixed(2)} (${category.percent}%)</div>
                            <div class="category-change ${category.change < 0 ? 'negative' : ''}">
                                ${category.change >= 0 ? '+' : ''}${category.change.toFixed(2)}% from last month
                            </div>
                            <div class="category-progress-bar-container">
                                <div class="category-progress-bar" style="width: ${category.percent}%; background-color: ${category.color};"></div>
                            </div>
                        </div>
                    `;
            }).join('');
        }

        // Handle Expense Growth (Progress Bars)
        const growthElements = document.querySelectorAll('.budget-progress-bar');
        const growthValues = document.querySelectorAll('.budget-progress-stats span');
        if (data.expense_growth) {
            growthElements.forEach((el, index) => {
                const keys = ['week_over_week', 'month_over_month', 'year_over_year'];
                el.style.width = `${Math.abs(data.expense_growth[keys[index]])}%`;
                el.style.backgroundColor = data.expense_growth[keys[index]] >= 0 ? '#10b981' : '#ea5455';
                growthValues[index].textContent = `${data.expense_growth[keys[index]].toFixed(2)}%`;
            });
        } else {
            growthElements.forEach((el, index) => {
                el.style.width = '0%';
                el.style.backgroundColor = '#ccc';
                growthValues[index].textContent = '0.00%';
            });
        }
    }

    function fetchReportData() {
        const monthInput = document.getElementById('report-month').value;
        const [year, month] = monthInput.split('-');

        fetch('../api/generate_report.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    year: parseInt(year),
                    month: parseInt(month)
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateCharts(data);
                } else {
                    console.error('Failed to load report data:', data.message);
                }
            })
            .catch(error => console.error('Error fetching report data:', error));
    }

    // Initial fetch and event listener for month change
    document.addEventListener('DOMContentLoaded', fetchReportData);
    document.getElementById('report-month').addEventListener('change', fetchReportData);
    
    function openModal(modalId) {
        document.getElementById(modalId).style.display = 'flex';
    }
    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    // Close modal when clicking overlay or close icon
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.close-modal, .modal-overlay').forEach(function(el) {
            el.addEventListener('click', function() {
                el.closest('.modal').style.display = 'none';
            });
        });
    });

        document.addEventListener('DOMContentLoaded', function() {
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
    <style>
    #spending-chart-container {
        width: 400px;
        height: 300px;
        min-width: 400px;
        min-height: 300px;
        max-width: 400px;
        max-height: 300px;
        margin: 0 auto;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    </style>

</body>

</html>