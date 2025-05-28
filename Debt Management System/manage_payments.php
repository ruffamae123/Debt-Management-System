<?php
    session_start();
    if (!isset($_SESSION['username'])) {
        header("Location: login.php");
        exit();
    }
    include 'database/config.php';

    // Get and sanitize search input
    $search = trim($_GET['search'] ?? '');
    $search_safe = mysqli_real_escape_string($conn, $search);

    // Query to fetch customers with remaining balance
    $query = "
        SELECT d.id, d.name, 
               SUM(db.debt_amount - COALESCE(db.amount_paid, 0)) AS total_balance
        FROM debtor_info d
        INNER JOIN debts db ON d.id = db.debtor_id
        WHERE d.name LIKE '%$search_safe%'
        GROUP BY d.id, d.name
        HAVING total_balance > 0
        ORDER BY d.name
    ";

    $result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Payments - Select Customer</title>
    <link rel="stylesheet" type="text/css" href="css/manage_payments.css">
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.2/css/boxicons.min.css">
</head>
<body>
    <div class="sidebar">
    <div class="sidebar-header">
        <h2>Debt Manager</h2>
        <div class="sidebar-subtitle">Small Sari-sari Store System</div>
    </div>
    <nav class="sidebar-nav">
    <ul>
        <li class="nav-item">
            <a href="index.php" class="nav-link">
                <div class="nav-icon"><i class='bx bx-pie-chart-alt'></i></div>
                <span class="nav-text">Dashboard</span>
                <div class="nav-indicator"></div>
            </a>
        </li>
        <li class="nav-item">
            <a href="add_debt.php" class="nav-link">
                <div class="nav-icon"><i class='bx bx-credit-card-front'></i></div>
                <span class="nav-text">Add New Debt</span>
                <div class="nav-indicator"></div>
            </a>
        </li>
        <li>
            <a href="manage_payments.php" class="nav-link">
                <div class="nav-icon"><i class='bx bx-credit-card'></i></div>
                <span class="nav-text">Manage Payments</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="due_soon.php" class="nav-link">
                <div class="nav-icon"><i class='bx bx-time-five'></i></div>
                <span class="nav-text">Due Soon</span>
                <div class="nav-indicator"></div>
            </a>
        </li>
        <li class="nav-item">
            <a href="overdue.php" class="nav-link">
                <div class="nav-icon"><i class='bx bx-error-circle'></i></div>
                <span class="nav-text">Overdue</span>
                <div class="nav-indicator"></div>
            </a>
        </li>
        <li class="nav-item">
            <a href="paid_debts.php" class="nav-link">
                <div class="nav-icon"><i class='bx bx-check-square'></i></div>
                <span class="nav-text">Paid Debts</span>
                <div class="nav-indicator"></div>
            </a>
        </li>
        <li class="nav-item">
            <a href="#" class="nav-link" onclick="toggleSubmenu('settings-submenu')">
                <div class="nav-icon"><i class='bx bx-cog'></i></div>
                <span class="nav-text">Settings</span>
                <div class="nav-indicator"></div>
                <i class='bx bx-chevron-down submenu-arrow'></i>
            </a>
            <ul id="settings-submenu" class="submenu">
                <li>
                    <a href="change_pass.php" class="nav-link">
                        <div class="nav-icon"><i class='bx bx-lock-alt'></i></div>
                        <span class="nav-text">Change Password</span>
                    </a>
                </li>
            </ul>
        </li>
    </ul>
</nav>
    <div class="sidebar-footer">
            <a href="#" onclick="confirmLogout()" class="logout-btn">
                <div class="nav-icon"><i class='bx bx-log-out'></i></div>
                <span>Logout</span>
            </a>
    </div>
</div>
<div class="main-content">
    <div class="header-bar">
        <h1><i class='bx bx-credit-card'></i> Manage Payments</h1>
        <p>Select a customer to view or update their debts.</p>
    </div>

    <?php if (mysqli_num_rows($result) > 0): ?>
        <div class="table-wrapper">
            <table class="responsive-table">
                <thead>
                    <tr><th>Customer Name</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php while ($customer = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td data-label="Customer Name"><?= htmlspecialchars($customer['name']) ?></td>
                            <td data-label="Action">
                                <a href="customer_debts.php?id=<?= $customer['id'] ?>" class="action-btn">View Debts</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="no-results">
            <i class='bx bx-user-x'></i>
            <p>No customers with outstanding debts found.</p>
        </div>
    <?php endif; ?> 
</div>
<script src="javascript/script.js"></script>
</body>
</html>