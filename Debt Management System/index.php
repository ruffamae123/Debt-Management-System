<?php
    session_start();
    if (!isset($_SESSION['username'])) {
        header("Location: login.php");
        exit();
    }

    error_reporting(1);
    include('database/config.php');

    // Dashboard statistics
   $stats = [
    'total_debts' => 0,
    'pending_debts' => 0,
    'due_soon' => 0,
    'overdue' => 0,
    'recent_payments' => 0,
    'total_paid' => 0
];

    // Total unpaid debts
    $result = mysqli_query($conn, "SELECT SUM(debt_amount - amount_paid) AS total FROM debts WHERE status != 'Paid'");

    $stats['total_debts'] = mysqli_fetch_assoc($result)['total'] ?? 0;

    // Pending debts
    $result = mysqli_query($conn, "SELECT COUNT(*) AS count FROM debts WHERE status = 'Pending'");
    $stats['pending_debts'] = mysqli_fetch_assoc($result)['count'] ?? 0;

    // Due soon (within 7 days)
    $today = date('Y-m-d');
    $next_week = date('Y-m-d', strtotime('+7 days'));
    $result = mysqli_query($conn, "SELECT COUNT(*) AS count FROM debts WHERE status = 'Pending' AND due_date BETWEEN '$today' AND '$next_week'");
    $stats['due_soon'] = mysqli_fetch_assoc($result)['count'] ?? 0;

    // Overdue debts
    $result = mysqli_query($conn, "SELECT COUNT(*) AS count FROM debts WHERE status = 'Overdue'");
    $stats['overdue'] = mysqli_fetch_assoc($result)['count'] ?? 0;

    // Recent payments (last 7 days)
    $last_week = date('Y-m-d', strtotime('-7 days'));
    $result = mysqli_query($conn, "SELECT COUNT(*) AS count, SUM(amount_paid) AS total FROM payments WHERE payment_date >= '$last_week'");
    $recent = mysqli_fetch_assoc($result);
    $stats['recent_payments'] = $recent['count'] ?? 0;
    $stats['total_paid'] = $recent['total'] ?? 0;

    // Fetch recent payments (last 10)
    $recent_payments = mysqli_query($conn, "
        SELECT p.payment_date, p.amount_paid, di.name 
        FROM payments p 
        JOIN debtor_info di ON p.debtor_id = di.id 
        ORDER BY p.payment_date DESC 
        LIMIT 10
    ");

    // Recent Activities: Combine payments and new debts
    $activities = mysqli_query($conn, "
        (SELECT 'payment' AS type, p.amount_paid AS amount, p.payment_date AS date, di.name 
         FROM payments p
         JOIN debtor_info di ON p.debtor_id = di.id
         ORDER BY p.payment_date DESC LIMIT 3)
        UNION
        (SELECT 'debt' AS type, d.debt_amount AS amount, d.created_at AS date, di.name 
         FROM debts d
         JOIN debtor_info di ON d.debtor_id = di.id
         ORDER BY d.created_at DESC LIMIT 3)
        ORDER BY date DESC LIMIT 5
    ");
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.2/css/boxicons.min.css">
    <link rel="stylesheet" type="text/css" href="css/index.css">
    
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
        <div class="dashboard-header">
            <h1><i class='bx bx-home'></i> Dashboard Overview</h1>
            <div class="date"><?= date('F j, Y') ?></div>
        </div>
        
        <div class="stats-container">
            <!-- Total Debts Card -->
            <div class="stat-card">
                <div class="card-header">
                    <div>
                        <div class="card-title">Total Outstanding</div>
                        <div class="card-value">₱<?= number_format($stats['total_debts'], 2) ?></div>
                        <div class="card-footer">
                            <i class='bx bx-trending-up'></i> All unpaid debts
                        </div>
                    </div>
                    <div class="card-icon card-1">
                        <i class='bx bx-money'></i>
                    </div>
                </div>
            </div>
            
            <!-- Pending Debts Card -->
            <div class="stat-card">
                <div class="card-header">
                    <div>
                        <div class="card-title">Pending Debts</div>
                        <div class="card-value"><?= $stats['pending_debts'] ?></div>
                        <div class="card-footer">
                            <i class='bx bx-time'></i> Active debts
                        </div>
                    </div>
                    <div class="card-icon card-2">
                        <i class='bx bx-credit-card'></i>
                    </div>
                </div>
            </div>
            
            <!-- Due Soon Card -->
            <div class="stat-card">
                <div class="card-header">
                    <div>
                        <div class="card-title">Due Soon</div>
                        <div class="card-value"><?= $stats['due_soon'] ?></div>
                        <div class="card-footer">
                            <i class='bx bx-calendar-exclamation'></i> Next 7 days
                        </div>
                    </div>
                    <div class="card-icon card-3">
                        <i class='bx bx-alarm'></i>
                    </div>
                </div>
            </div>
            
            <!-- Overdue Card -->
            <div class="stat-card">
                <div class="card-header">
                    <div>
                        <div class="card-title">Overdue Debts</div>
                        <div class="card-value"><?= $stats['overdue'] ?></div>
                        <div class="card-footer">
                            <i class='bx bx-error-circle'></i> Requires attention
                        </div>
                    </div>
                    <div class="card-icon card-4">
                        <i class='bx bx-error'></i>
                    </div>
                </div>
            </div>
            
            <!-- Recent Payments Card -->
            <div class="stat-card">
                <div class="card-header">
                    <div>
                        <div class="card-title">Recent Payments</div>
                        <div class="card-value"><?= $stats['recent_payments'] ?></div>
                        <div class="card-footer">
                            <i class='bx bx-wallet'></i> ₱<?= number_format($stats['total_paid'], 2) ?> collected
                        </div>
                    </div>
                    <div class="card-icon card-5">
                        <i class='bx bx-check-circle'></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="sections-container">
            <!-- Recent Activities Section -->
            <div class="section">
                <div class="section-header">
                    <h2 class="section-title"><i class='bx bx-history'></i> Recent Activities</h2>
                </div>
                
                <ul class="activity-list">
                    <?php if (mysqli_num_rows($activities) > 0): ?>
                        <?php while ($activity = mysqli_fetch_assoc($activities)): ?>
                            <li class="activity-item">
                                <div class="activity-icon <?= $activity['type'] ?>">
                                    <i class='bx bx-<?= $activity['type'] == 'payment' ? 'check-circle' : 'money' ?>'></i>
                                </div>
                                <div class="activity-details">
                                    <div class="activity-title">
                                        <?= $activity['type'] == 'payment' ? 'Payment Received' : 'New Debt Added' ?>
                                    </div>
                                    <div class="activity-meta">
                                        <span><?= htmlspecialchars($activity['name']) ?></span>
                                        <span><?= date('M j, g:i a', strtotime($activity['date'])) ?></span>
                                        <span class="activity-amount">₱<?= number_format($activity['amount'], 2) ?></span>
                                    </div>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <li class="activity-item">
                            <div class="activity-details">
                                No recent activities found
                            </div>
                        </li>
                    <?php endif; ?>
                </ul>
                
            </div>
            
            <!-- Quick Actions Section -->
            <div class="section">
                <div class="sections-container">
            <!-- Recent Payments Section -->
            <div class="section">
                <div class="section-header">
                    <h2 class="section-title"><i class='bx bx-wallet'></i> Recent Payments</h2>
                </div>
                <div class="recent-payments">
                    <?php if (mysqli_num_rows($recent_payments) > 0): ?>
                        <ul class="payment-list">
                            <?php while ($row = mysqli_fetch_assoc($recent_payments)): ?>
                                <li class="payment-item">
                                    <div class="payer-name"><?= htmlspecialchars($row['name']) ?></div>
                                    <div class="payment-meta">
                                        <span class="payment-amount">₱<?= number_format($row['amount_paid'], 2) ?></span>
                                        <span class="payment-date"><?= date('M j, Y - g:i A', strtotime($row['payment_date'])) ?></span>
                                    </div>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <p>No recent payments found.</p>
                    <?php endif; ?>
                </div>
            </div>
                <div class="section-header">
                    <h2 class="section-title"><i class='bx bx-zap'></i> Quick Actions</h2>
                </div>
               
                <div style="display: grid; gap: 12px;">
                    <a href="add_debt.php" class="stat-card" style="text-decoration: none; color: inherit;">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div style="background: #e3f2fd; width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <i class='bx bx-plus' style="font-size: 24px; color: #1976d2;"></i>
                            </div>
                            <div>
                                <div style="font-weight: 600;">Add New Debt</div>
                                <div style="font-size: 13px; color: #7f8c8d;">Record a new customer debt</div>
                            </div>
                        </div>
                    </a>
                    
                    <a href="due_soon.php" class="stat-card" style="text-decoration: none; color: inherit;">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div style="background: #fff8e1; width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <i class='bx bx-alarm' style="font-size: 24px; color: #ffa000;"></i>
                            </div>
                            <div>
                                <div style="font-weight: 600;">Handle Pending Debts</div>
                                <div style="font-size: 13px; color: #7f8c8d;">View due soon customers</div>
                            </div>
                        </div>
                    </a>
                    
                    <a href="overdue.php" class="stat-card" style="text-decoration: none; color: inherit;">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div style="background: #ffebee; width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <i class='bx bx-error' style="font-size: 24px; color: #d32f2f;"></i>
                            </div>
                            <div>
                                <div style="font-weight: 600;">Handle Overdue Debts</div>
                                <div style="font-size: 13px; color: #7f8c8d;">View overdue payments</div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <script src="javascript/script.js"></script>
</body>


</html>