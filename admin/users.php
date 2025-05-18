<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth_functions.php';
requireAdmin();
$admin = getCurrentUser();

$name = $_GET['name'] ?? '';
$email = $_GET['email'] ?? '';
$status = $_GET['status'] ?? '';
$join_date = $_GET['join_date'] ?? '';
$sort = $_GET['sort'] ?? 'full_name';
$order = $_GET['order'] ?? 'asc';

// Fetch all users, excluding the System Administrator with explicit conditions
$sql = "SELECT * FROM users WHERE email != ? AND user_type != ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $admin_email, $admin_type);
$admin_email = 'admin@gastosguard.com';
$admin_type = 'admin';
$stmt->execute();
$result = $stmt->get_result();
$users = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}
$stmt->close();

// Apply filters in PHP with strict admin exclusion
$filtered_users = array_filter($users, function($user) use ($name, $email, $status, $join_date) {
    if ($user['email'] === 'admin@gastosguard.com' || (isset($user['user_type']) && $user['user_type'] === 'admin')) {
        return false;
    }
    $match = true;
    if ($name && stripos($user['full_name'], $name) === false) $match = false;
    if ($email && stripos($user['email'], $email) === false) $match = false;
    if ($status && $status !== 'all') {
        if ($status === 'active' && $user['active'] != 1) $match = false;
        if ($status === 'inactive' && $user['active'] != 0) $match = false;
        if ($status === 'pending' && $user['active'] != 2) $match = false;
    }
    if ($join_date && date('Y-m-d', strtotime($user['created_at'])) !== $join_date) $match = false;
    return $match;
});

// Sort in PHP
$sort = in_array($sort, ['full_name','email','created_at','active']) ? $sort : 'full_name';
$order = strtolower($order) === 'desc' ? 'desc' : 'asc';

usort($filtered_users, function($a, $b) use ($sort, $order) {
    if ($a[$sort] == $b[$sort]) return 0;
    if ($order === 'asc') {
        return ($a[$sort] < $b[$sort]) ? -1 : 1;
    } else {
        return ($a[$sort] > $b[$sort]) ? -1 : 1;
    }
});

// Recalculate user statistics with strict admin exclusion
$total_users = count(array_filter($users, function($user) {
    return $user['email'] !== 'admin@gastosguard.com' && (!isset($user['user_type']) || $user['user_type'] !== 'admin');
}));
$active_users = count(array_filter($users, function($user) {
    return $user['active'] == 1 && $user['email'] !== 'admin@gastosguard.com' && (!isset($user['user_type']) || $user['user_type'] !== 'admin');
}));
$new_users_this_month = count(array_filter($users, function($user) {
    return date('Y-m', strtotime($user['created_at'])) === date('Y-m') && $user['email'] !== 'admin@gastosguard.com' && (!isset($user['user_type']) || $user['user_type'] !== 'admin');
}));

// Debug: Log fetched users to verify exclusion
error_log("Fetched users: " . print_r($users, true));

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Management | Gastos Guard</title>
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
                    <li>
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
                    <li class="active">
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
                    <h3>User Management</h3>
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
            <!-- Main Content -->
            <div class="content">
                <div class="card">
                    <div class="card-header"
                        style="display: flex; align-items: center; justify-content: space-between; gap: 16px;">
                        <button type="button" class="btn primary" id="addUserBtn"><span class="fas fa-plus"></span> Add
                            New User</button>
                    </div>
                    <div class="card-body">
                        <form method="get" id="filterForm" style="margin-bottom: 18px; display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end;">
    <div>
        <label for="filter-name">Name</label>
        <input type="text" name="name" id="filter-name" value="<?= htmlspecialchars($name) ?>" class="form-control" placeholder="Name">
    </div>
    <div>
        <label for="filter-email">Email</label>
        <input type="text" name="email" id="filter-email" value="<?= htmlspecialchars($email) ?>" class="form-control" placeholder="Email">
    </div>
    <div>
        <label for="filter-status">Status</label>
        <select name="status" id="filter-status" class="form-control">
            <option value="all" <?= $status==='all'||$status===''?'selected':'' ?>>All</option>
            <option value="active" <?= $status==='active'?'selected':'' ?>>Active</option>
            <option value="inactive" <?= $status==='inactive'?'selected':'' ?>>Inactive</option>
            <option value="pending" <?= $status==='pending'?'selected':'' ?>>Pending</option>
        </select>
    </div>
    <div>
        <label for="filter-join-date">Join Date</label>
        <input type="date" name="join_date" id="filter-join-date" value="<?= htmlspecialchars($join_date) ?>" class="form-control">
    </div>
    <div>
        <label for="filter-sort">Sort By</label>
        <select name="sort" id="filter-sort" class="form-control">
            <option value="full_name" <?= $sort==='full_name'?'selected':'' ?>>Name</option>
            <option value="email" <?= $sort==='email'?'selected':'' ?>>Email</option>
            <option value="created_at" <?= $sort==='created_at'?'selected':'' ?>>Join Date</option>
            <option value="active" <?= $sort==='active'?'selected':'' ?>>Status</option>
        </select>
    </div>
    <div>
        <label for="filter-order">Order</label>
        <select name="order" id="filter-order" class="form-control">
            <option value="asc" <?= $order==='asc'?'selected':'' ?>>Ascending</option>
            <option value="desc" <?= $order==='desc'?'selected':'' ?>>Descending</option>
        </select>
    </div>
    <div style="display:flex;gap:6px;align-items:center;">
        <button type="submit" class="btn btn-primary">Apply</button>
        <a href="users.php" class="btn btn-secondary" id="resetFilters">Reset</a>
    </div>
</form>
                        <div class="table-responsive">
                            <table id="usersTable" class="dark-table">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Email</th>
                                        <th>Join Date</th>
                                        <th>Status</th>
                                        <th>Expenses</th>
                                        <th>Total Spent</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="usersTableBody">
                                    <?php foreach ($filtered_users as $user): ?>
                                    <tr data-name="<?= htmlspecialchars(strtolower($user['full_name'])) ?>" data-email="<?= htmlspecialchars(strtolower($user['email'])) ?>" data-status="<?= $user['active'] ?>" data-join="<?= htmlspecialchars(date('Y-m-d', strtotime($user['created_at']))) ?>">
    <td class="user-cell" data-label="User">
        <div class="user-avatar"><?= htmlspecialchars(substr($user['full_name'],0,2)) ?></div>
        <span class="user-name"><?= htmlspecialchars($user['full_name']) ?></span>
    </td>
    <td data-label="Email"><?= htmlspecialchars($user['email']) ?></td>
    <td data-label="Join Date"><?= htmlspecialchars(date('Y-m-d', strtotime($user['created_at']))) ?></td>
    <td data-label="Status">
        <?php if ($user['active'] == 1): ?>
            <span class="badge badge-active">Active</span>
        <?php elseif ($user['active'] == 0): ?>
            <span class="badge badge-inactive">Inactive</span>
        <?php else: ?>
            <span class="badge badge-pending">Pending</span>
        <?php endif; ?>
    </td>
    <td data-label="Expenses"><!-- Expenses count here if needed --></td>
    <td data-label="Total Spent"><!-- Total spent here if needed --></td>
    <td data-label="Actions">
        <div class="action-buttons">
            <button class="action-btn"><i class="fas fa-eye"></i></button>
            <button class="action-btn"><i class="fas fa-pen"></i></button>
            <button class="action-btn"><i class="fas fa-trash"></i></button>
        </div>
    </td>
</tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="pagination-container">
                            <!-- Pagination will be handled by DataTables or custom JS -->
                        </div>
                    </div>
                </div>

                <!-- Dashboard Row: User Statistics, Top Spenders, Recent Activity -->
                <div class="dashboard-row" style="display: flex; gap: 32px; margin-top: 32px;">
                    <!-- User Statistics Section -->
                    <div class="card" style="flex:1; margin-top: 0;">
                        <div class="card-header">
                            <h2>User Statistics</h2>
                        </div>
                        <div class="card-body user-stats-section"
                            style="display: flex; flex-direction: column; gap: 18px;">
                            <div class="stat-card" style="background: #232a36; border-radius: 10px; margin-bottom: 0;">
                                <div style="color: #bfc9da; font-size: 15px;">Total Users</div>
                                <div id="total-users" style="font-size: 2rem; font-weight: 700; color: #fff;"><?php echo $total_users; ?></div>
                            </div>
                            <div class="stat-card" style="background: #232a36; border-radius: 10px; margin-bottom: 0;">
                                <div style="color: #bfc9da; font-size: 15px;">Active Users</div>
                                <div id="active-users" style="font-size: 2rem; font-weight: 700; color: #2ecc71;"><?php echo $active_users; ?></div>
                            </div>
                            <div class="stat-card" style="background: #232a36; border-radius: 10px; margin-bottom: 0;">
                                <div style="color: #bfc9da; font-size: 15px;">New Users (This Month)</div>
                                <div id="new-users" style="font-size: 2rem; font-weight: 700; color: #ff7a3d;"><?php echo $new_users_this_month; ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Spenders Section -->
                    <div class="card" style="flex:1; margin-top: 0;">
                        <div class="card-header">
                            <h2>Top Spenders</h2>
                        </div>
                        <div class="card-body">
                            <ul id="topSpendersList" style="list-style: none; padding: 0; margin: 0;"></ul>
                        </div>
                    </div>

                    <!-- Recent Activity Section -->
                    <div class="card" style="flex:1; margin-top: 0;">
                        <div class="card-header">
                            <h2>Recent Activity</h2>
                        </div>
                        <div class="card-body">
                            <ul id="recentActivityList" style="list-style: none; padding: 0; margin: 0;"></ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Minimal Add User Modal -->
            <div class="modal" id="addUserModal" style="
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                justify-content: center;
                align-items: center;
                z-index: 1000;
                    ">
                <div style="
                    background-color: var(--bg-card);
                    padding: var(--space-md);
                    border-radius: var(--radius-lg);
                    width: 90%;
                    max-width: 400px;
                    box-shadow: var(--shadow-md);
                    position: relative;
                        ">
                    <button id="closeAddUserModal" style="
                        position: absolute; top: 16px; right: 16px;
                        background: none; border: none; color: #fff; font-size: 24px; cursor: pointer;
                        ">×</button>
                    <div class="modal-header">
                        <h2 style="margin-top:0;">Add New User</h2>
                    </div>
                    <form id="addUserForm">
                        <div style="margin-bottom: 12px;">
                            <label>Username</label>
                            <input type="text" id="username" name="username" required
                                style="width:100%;padding:8px;margin-top:4px;">
                        </div>
                        <div style="margin-bottom: 12px;">
                            <label>Email</label>
                            <input type="email" id="email" name="email" required
                                style="width:100%;padding:8px;margin-top:4px;">
                        </div>
                        <div style="margin-bottom: 12px;">
                            <label>Password</label>
                            <input type="password" id="password" name="password" required
                                style="width:100%;padding:8px;margin-top:4px;">
                        </div>
                        <div style="margin-bottom: 12px;">
                            <label>Full Name</label>
                            <input type="text" id="full_name" name="full_name" required
                                style="width:100%;padding:8px;margin-top:4px;">
                        </div>
                        <div id="addUserError" style="color:#ea5455; margin-bottom:10px; display:none;"></div>
                        <button type="submit"
                            style="width:100%;background:#ff7a3d;color:#fff;padding:10px 0;border:none;border-radius:6px;font-size:16px;cursor:pointer;">Add
                            User</button>
                    </form>
                </div>
            </div>

            <!-- View User Modal -->
            <div class="modal" id="viewUserModal" style="
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                justify-content: center;
                align-items: center;
                z-index: 1000;
            ">
                <div style="
                    background-color: var(--bg-card);
                    padding: var(--space-md);
                    border-radius: var(--radius-lg);
                    width: 90%;
                    max-width: 400px;
                    box-shadow: var(--shadow-md);
                    position: relative;
                ">
                    <button id="closeViewUserModal" style="
                        position: absolute; top: 16px; right: 16px;
                        background: none; border: none; color: #fff; font-size: 24px; cursor: pointer;
                    ">×</button>
                    <div class="modal-header">
                        <h2 style="margin-top:0;">User Details</h2>
                    </div>
                    <div id="viewUserDetails">
                        <!-- User details will be populated by JS -->
                    </div>
                </div>
            </div>

            <!-- Edit User Modal -->
            <div class="modal" id="editUserModal" style="
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                justify-content: center;
                align-items: center;
                z-index: 1000;
            ">
                <div style="
                    background-color: var(--bg-card);
                    padding: var(--space-md);
                    border-radius: var(--radius-lg);
                    width: 90%;
                    max-width: 400px;
                    box-shadow: var(--shadow-md);
                    position: relative;
                ">
                    <button id="closeEditUserModal" style="
                        position: absolute; top: 16px; right: 16px;
                        background: none; border: none; color: #fff; font-size: 24px; cursor: pointer;
                    ">×</button>
                    <div class="modal-header">
                        <h2 style="margin-top:0;">Edit User</h2>
                    </div>
                    <form id="editUserForm">
                        <div style="margin-bottom: 12px;">
                            <label>Username</label>
                            <input type="text" id="edit_username" name="username" required
                                style="width:100%;padding:8px;margin-top:4px;">
                        </div>
                        <div style="margin-bottom: 12px;">
                            <label>Email</label>
                            <input type="email" id="edit_email" name="email" required
                                style="width:100%;padding:8px;margin-top:4px;">
                        </div>
                        <div style="margin-bottom: 12px;">
                            <label>Full Name</label>
                            <input type="text" id="edit_full_name" name="full_name" required
                                style="width:100%;padding:8px;margin-top:4px;">
                        </div>
                        <div style="margin-bottom: 12px;">
                            <label>Status</label>
                            <select id="edit_status" name="status" style="width:100%;padding:8px;margin-top:4px;">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                        <div id="editUserError" style="color:#ea5455; margin-bottom:10px; display:none;"></div>
                        <button type="submit"
                            style="width:100%;background:#ff7a3d;color:#fff;padding:10px 0;border:none;border-radius:6px;font-size:16px;cursor:pointer;">Save
                            Changes</button>
                    </form>
                </div>
            </div>

            <!-- Delete User Confirmation Modal -->
            <div class="modal" id="deleteUserModal" style="
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                justify-content: center;
                align-items: center;
                z-index: 1000;
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
                    <button id="closeDeleteUserModal" style="
                        position: absolute; top: 16px; right: 16px;
                        background: none; border: none; color: #fff; font-size: 24px; cursor: pointer;
                    ">×</button>
                    <div class="modal-header">
                        <h2 style="margin-top:0;">Delete User</h2>
                    </div>
                    <div id="deleteUserMessage" style="margin: 20px 0; color: #fff;">Are you sure you want to delete
                        this user?</div>
                    <div style="display: flex; gap: 12px; justify-content: center;">
                        <button id="confirmDeleteUserBtn" class="btn danger"
                            style="background:#ea5455; color:#fff;">Delete</button>
                        <button id="cancelDeleteUserBtn" class="btn secondary">Cancel</button>
                    </div>
                </div>
            </div>

            <!-- Add/Edit/Delete User Confirmation Modal -->
            <div class="modal" id="addUserConfirmModal" style="
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                justify-content: center;
                align-items: center;
                z-index: 1000;
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
                    <button id="closeAddUserConfirmModal" style="
                        position: absolute; top: 16px; right: 16px;
                        background: none; border: none; color: #fff; font-size: 24px; cursor: pointer;
                    ">×</button>
                    <div class="modal-header">
                        <h2 style="margin-top:0;" id="actionConfirmHeader">User Added</h2>
                    </div>
                    <div id="addUserConfirmMessage" style="margin: 20px 0; color: #fff;">User has been added
                        successfully!</div>
                    <button id="closeAddUserConfirmBtn" class="btn primary"
                        style="background:#ff7a3d; color:#fff;">OK</button>
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

            <script>
            $('#addUserBtn').on('click', function() {
                $('#addUserModal').css('display', 'flex');
                $('#addUserForm')[0].reset();
                $('#addUserError').hide().text('');
            });
            $('#closeAddUserModal').on('click', function() {
                $('#addUserModal').hide();
            });
            $('#addUserModal').on('click', function(e) {
                if (e.target === this) $(this).hide();
            });
            $('#addUserForm').on('submit', function(e) {
                e.preventDefault();
                // Your AJAX logic here, or just close for demo:
                $('#addUserModal').hide();
            });
            </script>

            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
            <script src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
            <link rel="stylesheet" href="https://cdn.datatables.net/1.10.25/css/jquery.dataTables.min.css" />
        </div> <!-- end .app-container -->
        <script>
        $('#testModalBtn').on('click', function() {
            $('#testModal').show();
        });
        </script>
        <script src="../assets/js/admin_users.js"></script>
        <style>
        .table-responsive {
            overflow-x: auto;
            scrollbar-width: none; /* Firefox */
        }
        .table-responsive::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Opera */
        }
        </style>
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
    // Hamburger Menu Toggle for Admin Users
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