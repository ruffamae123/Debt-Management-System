<?php
    error_reporting(1);
    session_start();
    include('database/config.php');
    if (!isset($_SESSION['username'])) {
        header("Location: login.php");
        exit();
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Due Soon</title>
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.2/css/boxicons.min.css">
    <link rel="stylesheet" type="text/css" href="css/due_soon.css">
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
    <div class="dashboard-container">
        <div class="due-soon-container">
            <div class="page-header">
                <h1><i class='bx bx-time'></i> Due Soon (Next 7 Days)</h1>
            </div>

            <div class="table-responsive">
                <table class="debts-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Remaining Balance</th>
                            <th>Due Date</th>
                            <th>Days Left</th>
                    </thead>
                    <tbody>
                    <?php
                    $today = date('Y-m-d');
                    $next_week = date('Y-m-d', strtotime('+7 days'));

                    $query = mysqli_query($conn, "
                        SELECT d.*, di.name, di.phone, DATEDIFF(d.due_date, '$today') AS days_left
                        FROM debts d
                        JOIN debtor_info di ON d.debtor_id = di.id
                        WHERE d.status = 'Pending'
                          AND d.due_date BETWEEN '$today' AND '$next_week'
                        ORDER BY d.due_date ASC
                    ");

                    if (mysqli_num_rows($query) > 0) {
                        while ($debt = mysqli_fetch_assoc($query)) {
                            $days_left = $debt['days_left'];
                            $remaining = $debt['debt_amount'] - $debt['amount_paid'];
                            $due_class = ($days_left <= 3) ? 'due-soon' : '';
                            $urgency_class = ($days_left <= 1) ? 'urgent' : ($days_left <= 3 ? 'warning' : '');

                            echo "<tr class='$urgency_class'>
                                <td data-label='Name'>{$debt['name']}</td>
                                <td data-label='Phone'>{$debt['phone']}</td>
                                <td data-label='Remaining Balance'>₱" . number_format($remaining, 2) . "</td>
                                <td data-label='Due Date' class='$due_class'>" . date('M j, Y', strtotime($debt['due_date'])) . "</td>
                                <td data-label='Days Left'>
                                    <span class='days-left-badge $due_class'>$days_left day(s)</span>
                                </td>
                              </tr>";
                        }
                    } else {
                        echo "<tr>
                                <td colspan='6'>
                                    <div class='no-data'>
                                        <i class='bx bx-check-circle'></i>
                                        <p>No debts due in the next 7 days</p>
                                    </div>
                                </td>
                              </tr>";
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script src="javascript/script.js"></script>
</body>
</html>