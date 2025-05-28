<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    session_start();

    if (!isset($_SESSION['username'])) {
        header("Location: login.php");
        exit();
    }

    include 'database/config.php';

    if (!$conn) {
        die("Database connection failed: " . mysqli_connect_error());
    }

    // Query to get overdue debts with balance > 0
    $sql = "
        SELECT 
            d.id,
            d.debtor_id,
            d.debt_amount,
            d.due_date,
            d.amount_paid,
            d.status,
            di.name, 
            di.phone,
            DATEDIFF(CURDATE(), d.due_date) AS days_overdue,
            (d.debt_amount - IFNULL(d.amount_paid, 0)) AS remaining_balance
        FROM debts d
        INNER JOIN debtor_info di ON d.debtor_id = di.id
        WHERE d.due_date < CURDATE()
          AND (d.debt_amount - IFNULL(d.amount_paid, 0)) > 0
          AND d.status != 'Paid'
        ORDER BY d.due_date ASC, days_overdue DESC
    ";

    $query = mysqli_query($conn, $sql);

    if (!$query) {
        die("Error in query: " . mysqli_error($conn));
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Overdue Debts</title>
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.2/css/boxicons.min.css">
    <link rel="stylesheet" type="text/css" href="css/overdue.css">
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

<div class="dashboard-container">
    <div class="overdue-container">
        <div class="page-header">
            <h1><i class='bx bx-error'></i> Overdue Debts</h1>
        </div>

        <div class="table-responsive">
            <table class="debts-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Remaining Balance</th>
                        <th>Due Date</th>
                        <th>Days Overdue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($query) > 0): ?>
                        <?php while ($debt = mysqli_fetch_assoc($query)): 
                            $remaining = floatval($debt['debt_amount']) - floatval($debt['amount_paid']);
                            $days_overdue = intval($debt['days_overdue']);
                            $urgency_class = ($days_overdue > 30) ? 'urgent' : ($days_overdue > 15 ? 'warning' : '');
                        ?>
                        <tr class="<?= $urgency_class ?>">
                            <td data-label="Name"><?= htmlspecialchars($debt['name']) ?></td>
                            <td data-label="Phone"><?= htmlspecialchars($debt['phone']) ?></td>
                            <td data-label="Remaining Balance">₱<?= number_format($remaining, 2) ?></td>
                            <td data-label="Due Date"><?= date('M j, Y', strtotime($debt['due_date'])) ?></td>
                            <td data-label="Days Overdue">
                                <span class="days-overdue-badge"><?= $days_overdue ?> day(s)</span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">
                                <div class="no-data">
                                    <i class='bx bx-check-circle'></i>
                                    <p>No overdue debts found</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="javascript/script.js"></script>
</body>
</html>
