<?php
// Include necessary files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth_functions.php';
require_once '../includes/expense_functions.php';

// Check if user is logged in
requireLogin();

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Fetch user data
$user = getUserById($user_id);

// Fetch all categories for the add expense form
$categories = getRows("SELECT category_id, name FROM expense_categories ORDER BY name ASC");

// Handle filters (if any)
$filter_date_start = isset($_GET['date_start']) ? sanitize_input($_GET['date_start']) : null;
$filter_date_end = isset($_GET['date_end']) ? sanitize_input($_GET['date_end']) : null;
$filter_category = isset($_GET['category']) ? (int)$_GET['category'] : null;

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> User Expenses | Gastos Guard</title>
    <link rel="stylesheet" href="../assets/css/normalize.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/user.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
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
                    <li class="active">
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
                    <h3>Expenses</h3>
                </div>
                <div class="header-right">
                    <div class="search-box">
                        <span class="fas fa-search"></span>
                        <input type="text" placeholder="Search expenses...">
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

            <!-- Content area -->
            <div class="content">
                <!-- Add New Expense Button -->
                <div class="action-button">
                    <button type="button" class="btn primary" id="add-expense-btn">
                        <span class="fas fa-plus"></span>
                        Add New Expense
                    </button>
                </div>

                <!-- Filter Section -->
                <section class="card expense-filters mb-4">
                    <div class="card-header">
                        <h2>Filters</h2>
                    </div>
                    <div class="card-body">
                        <form id="expense-filter-form" class="filter-form">
                            <div class="filter-group">
                                <label for="date_start">Start Date</label>
                                <input type="date" id="date_start" name="date_start"
                                    value="<?php echo $filter_date_start; ?>">
                            </div>
                            <div class="filter-group">
                                <label for="date_end">End Date</label>
                                <input type="date" id="date_end" name="date_end"
                                    value="<?php echo $filter_date_end; ?>">
                            </div>
                            <div class="filter-group">
                                <label for="category">Category</label>
                                <select id="category" name="category">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['category_id']; ?>"
                                        <?php echo $filter_category == $category['category_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="filter-actions">
                                <button type="button" class="btn secondary" onclick="resetFilters()">Reset</button>
                                <button type="button" class="btn primary" onclick="applyFilters()">Apply
                                    Filters</button>
                            </div>
                        </form>
                    </div>
                </section>

                <!-- Expenses List -->
                <section class="card expense-list">
                    <div class="card-header">
                        <h2>Expense List</h2>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="expense-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Description</th>
                                        <th>Category</th>
                                        <th>Amount</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="expense-table-body">
                                    <!-- Expenses will be loaded dynamically via JavaScript -->
                                </tbody>
                            </table>
                        </div>
                        <div class="no-data" id="no-expenses-message" style="display: none;">
                            <p>No expenses found.</p>
                        </div>
                    </div>
                </section>
            </div>

            <!-- System message container for feedback messages -->
            <div id="system-message" class="system-message" style="display: none;"></div>
        </div>
    </div>

    <!-- Add Expense Modal -->
    <div class="modal" id="add-expense-modal" style="display: none;">
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Expense</h2>
                <span class="close-modal fas fa-times"></span>
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
                        <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['category_id']; ?>">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="amount">Amount (₱)</label>
                    <input type="number" id="expense-amount" name="amount" step="0.01" min="0" placeholder="0.00"
                        required>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn secondary" onclick="closeModal('add-expense-modal')">Cancel</button>
                    <button type="submit" class="btn primary">Add Expense</button>
                </div>
                <div id="add-expense-message" class="message" style="display: none;"></div>
            </form>
        </div>
    </div>

    <!-- Edit Expense Modal -->
    <div class="modal" id="edit-expense-modal" style="display: none;">
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2><span class="fas fa-edit"></span> Edit Expense</h2>
                <span class="close-modal fas fa-times"></span>
            </div>
            <form id="edit-expense-form">
                <input type="hidden" id="edit-expense-id" name="expense_id">
                <div class="form-group">
                    <label for="edit-expense-date">Date</label>
                    <input type="date" id="edit-expense-date" name="date" required>
                </div>
                <div class="form-group">
                    <label for="edit-expense-description">Description</label>
                    <input type="text" id="edit-expense-description" name="description"
                        placeholder="E.g., Grocery Shopping" required>
                </div>
                <div class="form-group">
                    <label for="edit-expense-category">Category</label>
                    <select id="edit-expense-category" name="category" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['category_id']; ?>">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit-expense-amount">Amount (₱)</label>
                    <input type="number" id="edit-expense-amount" name="amount" step="0.01" min="0" placeholder="0.00"
                        required>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn secondary" onclick="closeModal('edit-expense-modal')">Cancel</button>
                    <button type="submit" class="btn primary">Save Changes</button>
                </div>
                <div id="edit-expense-message" class="message" style="display: none;"></div>
            </form>
        </div>
    </div>

    <!-- Logout Confirmation Modal -->
    <div class="modal confirmation-modal-wrapper" id="logout-modal" style="display: none;">
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

    <!-- JavaScript -->
     <script>
    document.addEventListener('DOMContentLoaded', function() {
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
    <script src="../assets/js/expense.js"></script>
</body>

</html>