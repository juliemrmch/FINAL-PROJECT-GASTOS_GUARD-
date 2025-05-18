<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth_functions.php';
require_once '../includes/user_functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user = getCurrentUser(); // Use getCurrentUser() from auth_functions.php
if (!$user) {
    header("Location: ../login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile | Gastos Guard</title>
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
                    <li><a href="dashboard.php"><span class="fas fa-tachometer-alt"></span><span>Dashboard</span></a>
                    </li>
                    <li><a href="expenses.php"><span class="fas fa-money-bill-wave"></span><span>Expenses</span></a>
                    </li>
                    <li><a href="budgets.php"><span class="fas fa-wallet"></span><span>Budget</span></a></li>
                    <li><a href="reports.php"><span class="fas fa-chart-line"></span><span>Reports</span></a></li>
                    <li class="active"><a href="profile.php"><span class="fas fa-user"></span><span>Profile</span></a>
                    </li>
                </ul>
            </nav>
            <div class="logout">
                <a href="#" onclick="openModal('logout-modal'); return false;"><span class="fas fa-sign-out-alt"></span><span>Logout</span></a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <header class="header">
                <div class="page-title">
                    <h3>User Profile</h3>
                </div>
                <div class="header-right">
                    <div class="user-info">
                        <div class="user-avatar"><?php echo substr($user['full_name'], 0, 1); ?></div>
                        <div class="user-details">
                            <h3><?php echo htmlspecialchars($user['full_name']); ?></h3>
                            <p><?php echo htmlspecialchars($user['email']); ?></p>
                        </div>
                    </div>
                </div>
            </header>

            <div class="content">
                <!-- Account Information -->
                <section class="card mb-4">
                    <div class="card-header">
                        <h2>Account Information</h2>
                    </div>
                    <div class="card-body">
                        <form id="account-info-form">
                            <div class="form-group">
                                <div class="two-column">
                                    <div>
                                        <label for="first-name">First Name</label>
                                        <input type="text" id="first-name" name="first_name"
                                            value="<?php echo htmlspecialchars(explode(' ', $user['full_name'])[0]); ?>"
                                            required>
                                    </div>
                                    <div>
                                        <label for="last-name">Last Name</label>
                                        <input type="text" id="last-name" name="last_name"
                                            value="<?php echo htmlspecialchars(implode(' ', array_slice(explode(' ', $user['full_name']), 1))); ?>"
                                            required>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email"
                                    value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn primary" id="save-account-btn">
                                    <span class="fas fa-save"></span> Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </section>

                <!-- Balance Section -->
                <section class="card mb-4">
                    <div class="card-header">
                        <h2>Balance</h2>
                    </div>
                    <div class="card-body">
                        <div class="two-column">
                            <div class="total-spent">
                                <div class="total-spent-amount">
                                    <h3>Current Balance</h3>
                                    <p class="amount">₱<?php echo number_format($user['current_balance'], 2); ?></p>
                                </div>
                            </div>
                            <div class="action-button">
                                <button type="button" class="btn primary" onclick="openModal('edit-balance-modal')">
                                    Edit Balance
                                </button>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Security Settings -->
                <section class="card mb-4">
                    <div class="card-header">
                        <h2>Security Settings</h2>
                    </div>
                    <div class="card-body">
                        <form id="password-form">
                            <div class="form-group">
                                <label for="current-password">Current Password</label>
                                <div class="password-wrapper" style="position:relative;">
                                    <input type="password" id="current-password" name="current_password" required>
                                    <span class="toggle-password" toggle="#current-password" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); cursor:pointer;">
                                        <i class="fas fa-eye"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="two-column">
                                    <div>
                                        <label for="new-password">New Password</label>
                                        <div class="password-wrapper" style="position:relative;">
                                            <input type="password" id="new-password" name="new_password" required>
                                            <span class="toggle-password" toggle="#new-password" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); cursor:pointer;">
                                                <i class="fas fa-eye"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <div>
                                        <label for="confirm-password">Confirm New Password</label>
                                        <div class="password-wrapper" style="position:relative;">
                                            <input type="password" id="confirm-password" name="confirm_password" required>
                                            <span class="toggle-password" toggle="#confirm-password" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); cursor:pointer;">
                                                <i class="fas fa-eye"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn primary" id="save-password-btn">
                                    <span class="fas fa-lock"></span> Update Password
                                </button>
                            </div>
                        </form>
                    </div>
                </section>
            </div>

            <!-- System Message Container -->
            <div id="system-message" class="system-message" style="display: none;"></div>

            <!-- Result Modal -->
            <div class="modal" id="result-modal" style="display:none;">
                <div class="modal-overlay"></div>
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 id="result-modal-title"><span class="fas fa-info-circle"></span> Result</h2>
                        <span class="close-modal fas fa-times" onclick="closeModal('result-modal')"></span>
                    </div>
                    <div class="modal-body">
                        <p id="result-modal-message"></p>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn primary" onclick="closeModal('result-modal')">OK</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal for Account Info -->
    <div class="modal" id="confirm-account-modal">
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2><span class="fas fa-exclamation-circle"></span> Confirm Changes</h2>
                <span class="close-modal fas fa-times" onclick="closeModal('confirm-account-modal')"></span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to update your account information?</p>
            </div>
            <div class="form-actions">
                <button type="button" class="btn secondary"
                    onclick="closeModal('confirm-account-modal')">Cancel</button>
                <button type="button" class="btn primary" id="confirm-account-update">Confirm</button>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal for Balance -->
    <div class="modal" id="confirm-balance-modal">
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2><span class="fas fa-exclamation-circle"></span> Confirm Balance Update</h2>
                <span class="close-modal fas fa-times" onclick="closeModal('confirm-balance-modal')"></span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to update your balance?</p>
            </div>
            <div class="form-actions">
                <button type="button" class="btn secondary"
                    onclick="closeModal('confirm-balance-modal')">Cancel</button>
                <button type="button" class="btn primary" id="confirm-balance-update">Confirm</button>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal for Password -->
    <div class="modal" id="confirm-password-modal">
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2><span class="fas fa-exclamation-circle"></span> Confirm Password Change</h2>
                <span class="close-modal fas fa-times" onclick="closeModal('confirm-password-modal')"></span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to change your password?</p>
            </div>
            <div class="form-actions">
                <button type="button" class="btn secondary"
                    onclick="closeModal('confirm-password-modal')">Cancel</button>
                <button type="button" class="btn primary" id="confirm-password-update">Confirm</button>
            </div>
        </div>
    </div>

    <!-- Edit Balance Modal -->
    <div class="modal" id="edit-balance-modal">
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2><span class="fas fa-wallet"></span> Edit Balance</h2>
                <span class="close-modal fas fa-times" onclick="closeModal('edit-balance-modal')"></span>
            </div>
            <form id="edit-balance-form">
                <div class="form-group">
                    <label for="new-balance">New Balance (₱)</label>
                    <input type="number" id="new-balance" name="new_balance" step="0.01" min="0"
                        value="<?php echo $user['current_balance']; ?>" required>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn secondary"
                        onclick="closeModal('edit-balance-modal')">Cancel</button>
                    <button type="button" class="btn primary" id="submit-balance-btn">Update Balance</button>
                </div>
                <div id="edit-balance-message" class="message" style="display: none;"></div>
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

    <script>
    // Modal control functions
    function openModal(modalId) {
        document.getElementById(modalId).style.display = 'flex';
        document.body.classList.add('modal-open');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
        document.body.classList.remove('modal-open');
    }

    // Close modal on overlay or close button click
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal-overlay') || e.target.classList.contains('close-modal')) {
                closeModal(modal.id);
            }
        });
    });

    // Show result modal for success or error messages
    function showResultModal(message, type) {
        const modal = document.getElementById('result-modal');
        const msg = document.getElementById('result-modal-message');
        const title = document.getElementById('result-modal-title');
        msg.textContent = message;
        if (type === 'success') {
            title.innerHTML = '<span class="fas fa-check-circle" style="color:green;"></span> Success';
        } else {
            title.innerHTML = '<span class="fas fa-times-circle" style="color:red;"></span> Error';
        }
        modal.style.display = 'flex';
        document.body.classList.add('modal-open');
    }

    // Account Info Update
    document.getElementById('account-info-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const firstName = document.getElementById('first-name').value.trim();
        const lastName = document.getElementById('last-name').value.trim();
        const email = document.getElementById('email').value.trim();

        // Client-side validation
        if (!firstName || !lastName || !email) {
            showResultModal('All fields are required.', 'error');
            return;
        }
        if (!/^[a-zA-Z\s]+$/.test(firstName + ' ' + lastName)) {
            showResultModal('Name can only contain letters and spaces.', 'error');
            return;
        }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showResultModal('Invalid email format.', 'error');
            return;
        }
        openModal('confirm-account-modal');
    });

    document.getElementById('confirm-account-update').addEventListener('click', function() {
        const form = document.getElementById('account-info-form');
        const formData = new FormData(form);
        const firstName = formData.get('first_name').trim();
        const lastName = formData.get('last_name').trim();
        const email = formData.get('email').trim();

        fetch('/GASTOS_GUARD/api/update_profile.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                first_name: firstName,
                last_name: lastName,
                email: email
            })
        })
        .then(response => response.json())
        .then(data => {
            closeModal('confirm-account-modal');
            if (data.success) {
                document.querySelector('.user-details h3').textContent = `${firstName} ${lastName}`;
                document.querySelector('.user-details p').textContent = email;
                showResultModal('Account information updated successfully!', 'success');
            } else {
                showResultModal(data.message || 'Failed to update account information.', 'error');
            }
        })
        .catch(error => {
            closeModal('confirm-account-modal');
            showResultModal('An error occurred while updating your profile.', 'error');
            console.error('Error:', error);
        });
    });

    // Balance Update
    document.getElementById('submit-balance-btn').addEventListener('click', function() {
        const newBalance = parseFloat(document.getElementById('new-balance').value);
        if (isNaN(newBalance) || newBalance < 0) {
            document.getElementById('edit-balance-message').style.display = 'block';
            document.getElementById('edit-balance-message').className = 'message error';
            document.getElementById('edit-balance-message').textContent = 'Balance must be a non-negative number.';
            return;
        }
        closeModal('edit-balance-modal');
        openModal('confirm-balance-modal');
    });

    document.getElementById('confirm-balance-update').addEventListener('click', function() {
        const newBalance = parseFloat(document.getElementById('new-balance').value);

        fetch('/GASTOS_GUARD/api/update_balance.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                new_balance: newBalance
            })
        })
        .then(response => response.json())
        .then(data => {
            closeModal('confirm-balance-modal');
            if (data.success) {
                document.querySelector('.total-spent-amount .amount').textContent =
                    `₱${newBalance.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",")}`;
                showResultModal('Balance updated successfully!', 'success');
            } else {
                showResultModal(data.message || 'Failed to update balance.', 'error');
            }
        })
        .catch(error => {
            closeModal('confirm-balance-modal');
            showResultModal('An error occurred while updating your balance.', 'error');
            console.error('Error:', error);
        });
    });

    // Password Update
    document.getElementById('password-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const currentPassword = document.getElementById('current-password').value;
        const newPassword = document.getElementById('new-password').value;
        const confirmPassword = document.getElementById('confirm-password').value;

        // Client-side validation
        if (!currentPassword || !newPassword || !confirmPassword) {
            showResultModal('All fields are required.', 'error');
            return;
        }
        if (newPassword !== confirmPassword) {
            showResultModal('New password and confirmation do not match.', 'error');
            return;
        }
        if (newPassword.length < 8 || !/[A-Z]/.test(newPassword) || !/[a-z]/.test(newPassword) || !/[0-9]/.test(newPassword)) {
            showResultModal('New password must be at least 8 characters long and contain uppercase, lowercase, and numbers.', 'error');
            return;
        }
        openModal('confirm-password-modal');
    });

    document.getElementById('confirm-password-update').addEventListener('click', function() {
        const currentPassword = document.getElementById('current-password').value;
        const newPassword = document.getElementById('new-password').value;

        fetch('/GASTOS_GUARD/api/update_password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                current_password: currentPassword,
                new_password: newPassword
            })
        })
        .then(response => response.json())
        .then(data => {
            closeModal('confirm-password-modal');
            if (data.success) {
                document.getElementById('password-form').reset();
                showResultModal('Password updated successfully!', 'success');
            } else {
                showResultModal(data.message || 'Failed to update password.', 'error');
            }
        })
        .catch(error => {
            closeModal('confirm-password-modal');
            showResultModal('An error occurred while updating your password.', 'error');
            console.error('Error:', error);
        });
    });

    // Password visibility toggle
    document.querySelectorAll('.toggle-password').forEach(function(eyeIcon) {
        eyeIcon.addEventListener('click', function() {
            const input = document.querySelector(this.getAttribute('toggle'));
            const icon = this.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });

    // Hamburger Menu Toggle
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
</body>

</html>