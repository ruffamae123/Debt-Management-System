<?php
    error_reporting(E_ALL);
    session_start();
    include('database/config.php');

    if (!isset($_SESSION['username'])) {
        header("Location: login.php");
        exit();
    }

    // Auto-delete fully paid debts older than 3 days
    $autoDelete = mysqli_query($conn, "
        SELECT 
            d.debtor_id AS id,
            MAX(p.payment_date) AS last_payment,
            SUM(d.debt_amount + d.interest_amount + d.penalty_fee) AS total_due,
            SUM(d.amount_paid) AS total_paid
        FROM debts d
        LEFT JOIN payments p ON d.debtor_id = p.debtor_id
        GROUP BY d.debtor_id
        HAVING total_paid >= total_due AND total_due > 0 AND DATEDIFF(NOW(), last_payment) > 3
    ");

    while ($row = mysqli_fetch_assoc($autoDelete)) {
        $id = (int)$row['id'];
        mysqli_query($conn, "DELETE FROM payments WHERE debtor_id = $id");
        mysqli_query($conn, "DELETE FROM debts WHERE debtor_id = $id");
        mysqli_query($conn, "DELETE FROM debtor_info WHERE id = $id");
    }

    // Fetch paid customers WITHOUT address column
    $sql = "
        SELECT 
            di.id,
            di.name AS customer_name,
            di.phone AS contact_number,
            SUM(d.debt_amount + d.penalty_fee + d.interest_amount) AS total_due,
            SUM(d.amount_paid) AS total_paid,
            MAX(d.payment_date) AS last_payment_date
        FROM debtor_info di
        JOIN debts d ON di.id = d.debtor_id
        GROUP BY di.id, di.name, di.phone
        HAVING total_paid >= total_due AND total_due > 0
    ";

    $result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Paid Debts</title>
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.2/css/boxicons.min.css" />
    <link rel="stylesheet" type="text/css" href="css/paid_debts.css" />
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
    <div class="paid-debts-container">
        <div class="page-header">
            <h1><i class='bx bx-check-circle'></i> Fully Paid Debts</h1>
            
        </div>

        <div class="table-responsive">
            <table class="paid-debts-table">
                <thead>
                    <tr>
                        <th>Customer Name</th>
                        <th>Contact</th>
                        <th>Total Due (₱)</th>
                        <th>Total Paid (₱)</th>
                        <th>Last Payment</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td data-label="Customer Name"><?= htmlspecialchars($row['customer_name']) ?></td>
                            <td data-label="Contact"><?= htmlspecialchars($row['contact_number']) ?></td>
                            <td data-label="Total Due"><?= number_format($row['total_due'], 2) ?></td>
                            <td data-label="Total Paid" class="paid-amount"><?= number_format($row['total_paid'], 2) ?></td>
                            <td data-label="Last Payment"><?= !empty($row['last_payment_date']) ? date('M j, Y', strtotime($row['last_payment_date'])) : 'N/A' ?></td>
                            <td data-label="Actions">
                                <form action="delete_debt.php" method="POST" class="delete-form">
                                    <input type="hidden" name="customer_id" value="<?= $row['id'] ?>">
                                    <button type="button" class="btn btn-sm btn-danger delete-btn" onclick="confirmDeletion(this)">
                                        <i class='bx bx-trash'></i> 
                                        <span class="btn-text">Delete</span>
                                        <span class="loading-dots">
                                            <span class="dot"></span>
                                            <span class="dot"></span>
                                            <span class="dot"></span>
                                        </span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">
                            <div class="no-data">
                                <i class='bx bx-info-circle'></i>
                                <p>No fully paid debts found</p>
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
<script>
async function confirmDeletion(button) {
    const form = button.closest('form');
    
    try {
        const confirmed = await showConfirmModal(
            'Delete Record', 
            'Are you sure you want to delete this record? This action cannot be undone.',
            'Delete',
            'Cancel'
        );
        
        if (confirmed) {
            // Show loading state
            button.classList.add('loading');
            
            // Submit the form
            form.submit();
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

function showConfirmModal(title, message, confirmText, cancelText) {
    return new Promise((resolve) => {
        // Create modal elements
        const modal = document.createElement('div');
        modal.className = 'confirm-modal';
        
        modal.innerHTML = `
            <div class="confirm-modal-content">
                <h3>${title}</h3>
                <p>${message}</p>
                <div class="confirm-modal-buttons">
                    <button class="btn btn-cancel">${cancelText}</button>
                    <button class="btn btn-confirm">${confirmText}</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Handle confirm button
        modal.querySelector('.btn-confirm').addEventListener('click', () => {
            document.body.removeChild(modal);
            resolve(true);
        });
        
        // Handle cancel button
        modal.querySelector('.btn-cancel').addEventListener('click', () => {
            document.body.removeChild(modal);
            resolve(false);
        });
        
        // Close modal when clicking outside
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                document.body.removeChild(modal);
                resolve(false);
            }
        });
    });
}
</script>
</body>
</html>