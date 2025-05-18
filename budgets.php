<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth_functions.php';
require_once '../includes/budget_functions.php';


if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user data
$user = getUserById($user_id);
$categories = get_expense_categories();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Budgets | Gastos Guard</title>
    <link rel="stylesheet" href="../assets/css/normalize.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/user.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
                    <li class="active">
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

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="page-title">
                    <h3>Budget</h3>
                </div>
                <div class="header-right">
                    <div class="search-box">
                        <span class="fas fa-search"></span>
                        <input type="text" placeholder="Search budgets...">
                    </div>
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

            <!-- Content Area -->
            <div class="content">
                <!-- Add New Budget Button -->
                <div class="action-button">
                    <button type="button" class="btn primary" onclick="openModal('add-budget-modal')">
                        <span class="fas fa-plus"></span>
                        Add New Budget
                    </button>
                </div>

                <!-- Filter Section -->
                <section class="card expense-filters mb-4">
                    <div class="card-header">
                        <h2>Filters</h2>
                    </div>
                    <div class="card-body">
                        <form id="budget-filter-form" class="filter-form">
                            <div class="filter-group">
                                <label for="budget-start-date">Start Date</label>
                                <input type="date" id="budget-start-date" name="start_date">
                            </div>
                            <div class="filter-group">
                                <label for="budget-end-date">End Date</label>
                                <input type="date" id="budget-end-date" name="end_date">
                            </div>
                            <div class="filter-group">
                                <label for="category">Category</label>
                                <select id="category" name="category_id">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['category_id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="filter-actions">
                                <button type="button" class="btn secondary">Reset</button>
                                <button type="button" class="btn primary">Apply Filters</button>
                            </div>
                        </form>
                    </div>
                </section>

                <!-- Budget List -->
                <section class="card expense-list">
                    <div class="card-header">
                        <h2>Budget List</h2>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="expense-table" id="budget-table">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Category</th>
                                        <th>Amount</th>
                                        <th>Spent</th>
                                        <th>Period</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Budgets will be populated via JavaScript -->
                                </tbody>
                            </table>
                        </div>
                        <div class="no-data" id="no-budgets-message">
                            <p>No budgets found.</p>
                        </div>
                    </div>
                </section>
            </div>

            <!-- System Message Container -->
            <div id="system-message" class="system-message" style="display: none;"></div>
        </div>
    </div>

    <!-- Add Budget Modal -->
    <div class="modal" id="add-budget-modal">
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2><span class="fas fa-wallet"></span> Add New Budget</h2>
                <span class="close-modal fas fa-times"></span>
            </div>
            <form id="add-budget-form">
                <div class="form-group">
                    <label for="budget-title">Budget Title</label>
                    <input type="text" id="budget-title" name="title" placeholder="E.g., Monthly Savings" required>
                </div>
                <div class="form-group">
                    <label for="budget-category">Category</label>
                    <select id="budget-category" name="category_id" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['category_id']; ?>">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="budget-amount">Amount (₱)</label>
                    <input type="number" id="budget-amount" name="amount" step="0.01" min="0" placeholder="0.00"
                        required>
                </div>
                <div class="form-group">
                    <label for="budget-start-date">Start Date</label>
                    <input type="date" id="budget-start-date" name="start_date" required>
                </div>
                <div class="form-group">
                    <label for="budget-end-date">End Date</label>
                    <input type="date" id="budget-end-date" name="end_date" required>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn secondary" onclick="closeModal('add-budget-modal')">Cancel</button>
                    <button type="submit" class="btn primary">Add Budget</button>
                </div>
                <div id="add-budget-message" class="message" style="display: none;"></div>
            </form>
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

    <!-- Edit Budget Modal -->
    <div class="modal" id="edit-budget-modal">
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2><span class="fas fa-edit"></span> Edit Budget</h2>
                <span class="close-modal fas fa-times"></span>
            </div>
            <form id="edit-budget-form">
                <input type="hidden" id="edit-budget-id" name="budget_id">
                <div class="form-group">
                    <label for="edit-budget-title">Budget Title</label>
                    <input type="text" id="edit-budget-title" name="title" placeholder="E.g., Monthly Savings" required>
                </div>
                <div class="form-group">
                    <label for="edit-budget-category">Category</label>
                    <select id="edit-budget-category" name="category_id" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['category_id']; ?>">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit-budget-amount">Amount (₱)</label>
                    <input type="number" id="edit-budget-amount" name="amount" step="0.01" min="0" placeholder="0.00"
                        required>
                </div>
                <div class="form-group">
                    <label for="edit-budget-start-date">Start Date</label>
                    <input type="date" id="edit-budget-start-date" name="start_date" required>
                </div>
                <div class="form-group">
                    <label for="edit-budget-end-date">End Date</label>
                    <input type="date" id="edit-budget-end-date" name="end_date" required>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn secondary"
                        onclick="closeModal('edit-budget-modal')">Cancel</button>
                    <button type="submit" class="btn primary">Save Changes</button>
                </div>
                <div id="edit-budget-message" class="message" style="display: none;"></div>
            </form>
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

    <!-- Delete Confirmation Modal -->
    <div class="modal confirmation-modal-wrapper" id="delete-budget-modal">
        <div class="modal-overlay"></div>
        <div class="modal-content confirmation-modal">
            <div class="modal-header">
                <h2>
                    <span class="fas fa-exclamation-triangle warning-icon"></span>
                    Confirm Deletion
                </h2>
                <span class="close-modal fas fa-times"></span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this budget? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button class="btn secondary" onclick="closeModal('delete-budget-modal')">Cancel</button>
                <button class="btn danger" id="confirm-delete-budget">Delete</button>
            </div>
        </div>
    </div>

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
    <script src="../assets/js/budget.js"></script>
</body>

</html>