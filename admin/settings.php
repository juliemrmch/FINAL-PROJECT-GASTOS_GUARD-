<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth_functions.php';
requireAdmin();
$admin = getCurrentUser();

// Redirect if not logged in or not admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$profile_message = '';
$password_message = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['profile_update'])) {
        $full_name = db_sanitize_input($_POST['full_name'] ?? '');
        $email = db_sanitize_input($_POST['email'] ?? '');
        $update_sql = "UPDATE users SET full_name='$full_name', email='$email' WHERE user_id=$user_id AND user_type='admin'";
        if (executeQuery($update_sql)) {
            $profile_message = '<div class="message success">Profile updated successfully.</div>';
        } else {
            $profile_message = '<div class="message error">Failed to update profile.</div>';
        }
    }
    if (isset($_POST['password_change'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $user = getRow("SELECT password FROM users WHERE user_id=$user_id");
        if ($user && password_verify($current_password, $user['password'])) {
            if ($new_password === $confirm_password) {
                if (strlen($new_password) < 6) {
                    $password_message = '<div class="message error">New password must be at least 6 characters.</div>';
                } else {
                    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                    if (executeQuery("UPDATE users SET password='$hashed' WHERE user_id=$user_id")) {
                        $password_message = '<div class="message success">Password updated successfully.</div>';
                    } else {
                        $password_message = '<div class="message error">Failed to update password.</div>';
                    }
                }
            } else {
                $password_message = '<div class="message error">New passwords do not match.</div>';
            }
        } else {
            $password_message = '<div class="message error">Current password is incorrect.</div>';
        }
    }
}

// Fetch admin profile data
$data = getRow("SELECT * FROM users WHERE user_id=$user_id AND user_type='admin'");
if (!$data) {
    die('Admin profile not found.');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile | Gastos Guard</title>
    <link rel="stylesheet" href="../assets/css/normalize.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/user.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        /* Style to adjust eye icon color to match the first image */
        .toggle-password i {
            color: #8792a8;
        }
        /* Style for the validation message */
        .password-validation-message {
            color: #ff4444;
            font-size: 0.9rem;
            margin-top: 0.5rem;
            display: none;
            grid-column: 1 / -1;
        }
    </style>
</head>

<body>
    <div class="app-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo" style="display: flex; align-items: center; gap: 10px;">
    <a href="dashboard.php" style="display: flex; align-items: center; gap: 8px; text-decoration: none;">
        <img src="../assets/images/logo.png" alt="Gastos Guard Logo" style="width: 40px; height: 40px;">
        <h1 style="margin: 0; font-size: 20px; color: #ff6b3d; margin-left: -4px">Gastos Guard</h1>
        <span class="admin-badge" style="margin-left:8px;">Admin</span>
    </a>
    <button class="hamburger" aria-label="Toggle navigation">
        <span class="fas fa-bars"></span>
    </button>
</div>
            <nav class="nav">
                <ul>
                    <li><a href="dashboard.php"><span class="fas fa-tachometer-alt"></span><span>Dashboard</span></a>
                    </li>
                    <li><a href="reports.php"><span class="fas fa-chart-line"></span><span>Reports &
                                Analytics</span></a></li>
                    <li><a href="users.php"><span class="fas fa-users"></span><span>User Management</span></a></li>
                    <li class="active"><a href="settings.php"><span class="fas fa-user"></span><span>My
                                Profile</span></a></li>
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
            <header class="header" style="display: flex; justify-content: space-between; align-items: center;">
                <div class="page-title">
                    <h3>User Profile</h3>
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
            <?php if ($profile_message) echo $profile_message; ?>
            <?php if ($password_message) echo $password_message; ?>
            <!-- Profile Content -->
            <div class="profile-container" style="display: flex; gap: 2rem; margin-top: 2rem; flex-wrap: wrap;">
                <!-- Left: Profile Summary Card -->
                <div class="card profile-summary" style="flex: 1 1 300px; max-width: 350px; min-width: 280px;">
                    <div style="display: flex; flex-direction: column; align-items: center; padding: 2rem 1rem;">
                        <div class="profile-avatar"
                            style="background: #ff8c32; color: #fff; width: 100px; height: 100px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; font-weight: 600; margin-bottom: 1rem;">
                            <?php echo htmlspecialchars(get_initials($data['full_name'])); ?>
                        </div>
                        <h2 style="margin: 0;"><?php echo htmlspecialchars($data['full_name']); ?></h2>
                        <div style="color: #b0b0b0; margin-bottom: 1rem;">
                            <?php echo htmlspecialchars($data['email']); ?></div>
                        <div style="width: 100%; border-top: 1px solid #222; margin-bottom: 1rem;"></div>
                        <div style="width: 100%; margin-bottom: 1rem;">
                            <div style="font-size: 0.95rem; color: #b0b0b0;">Account Status</div>
                            <span style="color: #4ade80; font-weight: 600;"><span
                                    style="font-size: 1.2em; vertical-align: middle;">●</span>
                                <?php echo ($data['active'] ? 'Active' : 'Inactive'); ?></span>
                        </div>
                        <div style="width: 100%; margin-bottom: 1rem;">
                            <div style="font-size: 0.95rem; color: #b0b0b0;">Member Since</div>
                            <span style="font-weight: 500;">
                                <?php echo date('F j, Y', strtotime($data['created_at'])); ?>
                            </span>
                        </div>
                        <div style="width: 100%;">
                            <div style="font-size: 0.95rem; color: #b0b0b0;">Last Login</div>
                            <span style="font-weight: 500;">
                                <?php echo $data['last_login'] ? date('F j, Y, g:i A', strtotime($data['last_login'])) : 'Never'; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Right: Profile Details -->
                <div style="flex: 2 1 500px; display: flex; flex-direction: column; gap: 2rem; min-width: 320px;">
                    <!-- Personal Information Card -->
                    <div class="card" style="padding: 2rem;">
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                            <h3 style="margin: 0;">Personal Information</h3>
                            <span class="fas fa-edit" style="color: #b0b0b0; cursor: pointer;"></span>
                        </div>
                        <form id="profileForm" method="POST"
                            style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                            <input type="hidden" name="profile_update" value="1">
                            <div class="form-group">
                                <label for="full_name">Full Name</label>
                                <input type="text" id="full_name" name="full_name"
                                    value="<?php echo htmlspecialchars($data['full_name']); ?>" autocomplete="off">
                            </div>
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email"
                                    value="<?php echo htmlspecialchars($data['email']); ?>" autocomplete="off">
                            </div>
                            <div style="grid-column: 1 / -1; text-align: right;">
                                <button type="button" id="showProfileConfirm" class="btn btn-primary"><span
                                        class="fas fa-save"></span> Save Changes</button>
                            </div>
                        </form>
                        <!-- Confirmation Modal -->
                        <div id="profileConfirmModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(10,10,10,0.85); z-index:1000; align-items:center; justify-content:center;">
                            <div style="background:#222; color:#fff; padding:2rem; border-radius:8px; max-width:350px; margin:auto; box-shadow:0 2px 16px rgba(0,0,0,0.2); text-align:center;">
                                <h3 style="margin-bottom:1rem;">Confirm Update</h3>
                                <p style="margin-bottom:2rem;">Are you sure you want to update your personal information?</p>
                                <button id="confirmProfileUpdate" class="btn btn-primary" style="margin-right:1rem;">Yes, Update</button>
                                <button id="cancelProfileUpdate" class="btn btn-secondary">Cancel</button>
                            </div>
                        </div>
                    </div>
                    <!-- Security Card -->
                    <div class="card" style="padding: 2rem;">
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                            <h3 style="margin: 0;">Security</h3>
                            <span class="fas fa-key" style="color: #b0b0b0;"></span>
                        </div>
                        <form id="passwordForm" method="POST"
                            style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                            <input type="hidden" name="password_change" value="1">
                            <div class="form-group" style="grid-column: 1 / -1; position: relative;">
                                <label for="current_password">Current Password</label>
                                <input type="password" id="current_password" name="current_password" autocomplete="off">
                                <button type="button" class="toggle-password" style="position: absolute; right: 10px; top: 70%; transform: translateY(-50%); background: none; border: none; cursor: pointer;">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="form-group" style="position: relative;">
                                <label for="new_password">New Password</label>
                                <input type="password" id="new_password" name="new_password" autocomplete="off">
                                <button type="button" class="toggle-password" style="position: absolute; right: 10px; top: 70%; transform: translateY(-50%); background: none; border: none; cursor: pointer;">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="form-group" style="position: relative;">
                                <label for="confirm_password">Confirm Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" autocomplete="off">
                                <button type="button" class="toggle-password" style="position: absolute; right: 10px; top: 70%; transform: translateY(-50%); background: none; border: none; cursor: pointer;">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="password-validation-message" id="passwordValidationMessage">
                                Please fill in all password fields before proceeding.
                            </div>
                            <div style="grid-column: 1 / -1; text-align: right;">
                                <button type="button" id="showPasswordConfirm" class="btn btn-secondary"><span
                                        class="fas fa-key"></span> Change Password</button>
                            </div>
                        </form>
                        <!-- Password Confirmation Modal -->
                        <div id="passwordConfirmModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(10,10,10,0.85); z-index:1000; align-items:center; justify-content:center;">
                            <div style="background:#222; color:#fff; padding:2rem; border-radius:8px; max-width:350px; margin:auto; box-shadow:0 2px 16px rgba(0,0,0,0.2); text-align:center;">
                                <h3 style="margin-bottom:1rem;">Confirm Password Change</h3>
                                <p style="margin-bottom:2rem;">Are you sure you want to change your password?</p>
                                <button id="confirmPasswordUpdate" class="btn btn-primary" style="margin-right:1rem;">Yes, Change</button>
                                <button id="cancelPasswordUpdate" class="btn btn-secondary">Cancel</button>
                            </div>
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
// Initialize password visibility toggles
function initPasswordToggles() {
    const toggleButtons = document.querySelectorAll(".toggle-password");
    toggleButtons.forEach((button) => {
        button.addEventListener("click", function () {
            const input = this.parentNode.querySelector("input");
            const type = input.getAttribute("type") === "password" ? "text" : "password";
            input.setAttribute("type", type);

            // Toggle icon
            const icon = this.querySelector("i");
            icon.classList.toggle("fa-eye");
            icon.classList.toggle("fa-eye-slash");
        });
    });
}

// Validate password form fields and show/hide UI message
function validatePasswordForm() {
    const currentPassword = document.getElementById('current_password').value.trim();
    const newPassword = document.getElementById('new_password').value.trim();
    const confirmPassword = document.getElementById('confirm_password').value.trim();
    const validationMessage = document.getElementById('passwordValidationMessage');

    if (!currentPassword || !newPassword || !confirmPassword) {
        validationMessage.style.display = 'block';
        return false;
    } else {
        validationMessage.style.display = 'none';
        return true;
    }
}

// Call the function when the DOM is fully loaded
document.addEventListener("DOMContentLoaded", function () {
    initPasswordToggles();
});

// Confirmation logic for profile update
const showProfileConfirm = document.getElementById('showProfileConfirm');
const profileConfirmModal = document.getElementById('profileConfirmModal');
const confirmProfileUpdate = document.getElementById('confirmProfileUpdate');
const cancelProfileUpdate = document.getElementById('cancelProfileUpdate');
const profileForm = document.getElementById('profileForm');

showProfileConfirm.addEventListener('click', function(e) {
    profileConfirmModal.style.display = 'flex';
});

confirmProfileUpdate.addEventListener('click', function() {
    profileConfirmModal.style.display = 'none';
    profileForm.submit();
});

cancelProfileUpdate.addEventListener('click', function() {
    profileConfirmModal.style.display = 'none';
});

// Confirmation logic for password change
const showPasswordConfirm = document.getElementById('showPasswordConfirm');
const passwordConfirmModal = document.getElementById('passwordConfirmModal');
const confirmPasswordUpdate = document.getElementById('confirmPasswordUpdate');
const cancelPasswordUpdate = document.getElementById('cancelPasswordUpdate');
const passwordForm = document.getElementById('passwordForm');

showPasswordConfirm.addEventListener('click', function(e) {
    // Validate form before showing the confirmation modal
    if (validatePasswordForm()) {
        passwordConfirmModal.style.display = 'flex';
    }
});

confirmPasswordUpdate.addEventListener('click', function() {
    passwordConfirmModal.style.display = 'none';
    passwordForm.submit();
});

cancelPasswordUpdate.addEventListener('click', function() {
    passwordConfirmModal.style.display = 'none';
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
    // Hamburger Menu Toggle for Admin Settings
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