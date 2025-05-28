<?php
    session_start();
    include 'database/config.php';

    $customer_id = intval($_GET['id'] ?? 0);
    if (!$customer_id) die("Customer ID is required.");

    // Delete logic
    if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
        $debt_id = intval($_GET['delete']);
        mysqli_query($conn, "DELETE FROM debts WHERE id = $debt_id AND debtor_id = $customer_id");
        header("Location: customer_debts.php?id=$customer_id&deleted=1");
        exit();
    }

    // Get customer info
    $customer_q = mysqli_query($conn, "SELECT * FROM debtor_info WHERE id = $customer_id");
    $customer = mysqli_fetch_assoc($customer_q);
    if (!$customer) die("Customer not found.");

    // Get debts
    $debts_q = mysqli_query($conn, "SELECT * FROM debts WHERE debtor_id = $customer_id ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Debts of <?= htmlspecialchars($customer['name']) ?></title>
    <link rel="stylesheet" type="text/css" href="css/customer_debts.css">
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
    <div class="page-header">
        <h1 class="page-title">Debts for <?= htmlspecialchars($customer['name']) ?></h1>
    </div>

    <div class="customer-info">
        <div class="customer-info-item">
            <i class='bx bx-phone'></i> <?= htmlspecialchars($customer['phone']) ?>
        </div>
    </div>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert success"><i class='bx bx-check-circle'></i> Debt deleted successfully!</div>
    <?php endif; ?>

    <?php if (mysqli_num_rows($debts_q) > 0): ?>
    <div class="debts-table-container">
        <table class="debts-table">
            <thead>
                <tr>
                    <th>Total Debt</th>
                    <th>Amount Paid</th>
                    <th>Balance</th>
                    <th>Status</th>
                    <th>Due Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($debt = mysqli_fetch_assoc($debts_q)): 
                    $amount_paid = floatval($debt['amount_paid']);
                    $debt_amount = floatval($debt['debt_amount']);
                    $balance = $debt_amount - $amount_paid;
                    $due_date = $debt['due_date'];
                    $today = date('Y-m-d');

                    // Determine status dynamically
                    if ($balance <= 0) {
                        $status = 'Paid';
                        $status_class = 'status-paid';
                    } elseif ($due_date < $today) {
                        $status = 'Overdue';
                        $status_class = 'status-overdue';
                    } else {
                        $status = 'Pending';
                        $status_class = 'status-pending';
                    }
                ?>
                <tr>
                    <td>₱<?= number_format($debt_amount, 2) ?></td>
                    <td>₱<?= number_format($amount_paid, 2) ?></td>
                    <td>₱<?= number_format($balance, 2) ?></td>
                    <td><span class="status <?= $status_class ?>"><?= $status ?></span></td>
                    <td><?= $due_date ?></td>
                    <td>
                        <a class="button" href="payment_form.php?id=<?= $debt['id'] ?>">Payments</a>
                        <a class="button delete-btn" href="?id=<?= $customer_id ?>&delete=<?= $debt['id'] ?>" onclick="return confirm('Are you sure you want to delete this debt?');">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>

            </tbody>
        </table>
    </div>
    <?php else: ?>
        <div class="empty-state">
            <i class='bx bx-info-circle'></i>
            <p>No debts found for this customer.</p>
        </div>
    <?php endif; ?>

    <p><a class="back-link" href="manage_payments.php"><i class='bx bx-arrow-back'></i> Back</a></p>
</div>
<script src="javascript/script.js"></script>
</body>
</html>
