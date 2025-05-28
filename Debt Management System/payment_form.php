<?php
    session_start();
    include 'database/config.php';

    $debt_id = intval($_GET['id'] ?? 0);
    if (!$debt_id) die("Debt ID is missing.");

    // Fetch debt and debtor details
    $query = "
        SELECT d.*, c.name, c.id AS debtor_id
        FROM debts d
        JOIN debtor_info c ON d.debtor_id = c.id
        WHERE d.id = $debt_id
    ";
    $result = mysqli_query($conn, $query);
    $debt = mysqli_fetch_assoc($result);
    if (!$debt) die("Debt not found.");

    $balance = $debt['debt_amount'] - ($debt['amount_paid'] ?? 0);
    $error = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $amount_paid = isset($_POST['amount_paid']) ? floatval($_POST['amount_paid']) : 0;
        $interest = isset($_POST['interest']) ? floatval($_POST['interest']) : 0;
        $new_notes_input = trim($_POST['notes'] ?? '');
        $plain_notes = mysqli_real_escape_string($conn, $new_notes_input);

        // Prepare system logs
        $system_logs = '';
        if ($interest > 0) {
            $interest_note = "[" . date('Y-m-d H:i:s') . "] Interest added: ₱" . number_format($interest, 2);
            $system_logs .= "\n$interest_note";

            $debt['debt_amount'] += $interest;
            $balance += $interest;

            // Update debt amount with interest
            $update_interest = "
                UPDATE debts SET
                    debt_amount = {$debt['debt_amount']}
                WHERE id = $debt_id
            ";
            mysqli_query($conn, $update_interest);
        }

        if ($amount_paid > 0) {
            if ($amount_paid > $balance) {
                $error = "Invalid payment. It must not exceed the current balance (₱" . number_format($balance, 2) . ").";
            } else {
                $new_paid = $debt['amount_paid'] + $amount_paid;
                $status = ($new_paid >= $debt['debt_amount']) ? 'Paid' : 'Pending';

                $payment_note = "[" . date('Y-m-d H:i:s') . "] Payment made: ₱" . number_format($amount_paid, 2);
                $system_logs .= "\n$payment_note";

                // Update payment info
                $update_payment = "
                    UPDATE debts SET
                        amount_paid = $new_paid,
                        status = '$status',
                        notes = '$plain_notes$system_logs',
                        payment_date = NOW()
                    WHERE id = $debt_id
                ";
                mysqli_query($conn, $update_payment);

                // Insert to payments table
                $insert_payment = "
                    INSERT INTO payments (debtor_id, amount_paid, payment_date)
                    VALUES ({$debt['debtor_id']}, $amount_paid, NOW())
                ";
                mysqli_query($conn, $insert_payment);

                header("Location: payment_form.php?id={$debt['debtor_id']}");
                exit();
            }
        } elseif ($interest > 0) {
            // Only interest added
            $update_notes = "
                UPDATE debts SET
                    notes = '$plain_notes$system_logs',
                    payment_date = NOW()
                WHERE id = $debt_id
            ";
            mysqli_query($conn, $update_notes);

            header("Location: payment_form.php?id={$debt['debtor_id']}");
            exit();
        } else {
            $error = "Please enter a payment or interest amount.";
        }
    }

    // Display only the plain user notes (strip out system logs)
    $clean_notes = trim(preg_replace('/\[\d{4}-\d{2}-\d{2}.*?\].*/', '', $debt['notes']));
?>

<!DOCTYPE html>
<html>
<head>
    <title>Make Payment - <?= htmlspecialchars($debt['name']) ?></title>
    <link rel="stylesheet" href="css/payment_form.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" type="text/css" href="css/payment_form.css">
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
    <div class="form-container">
        <h2>Make a Payment for <?= htmlspecialchars($debt['name']) ?></h2>

        <?php if ($error): ?>
            <p class="error"><?= $error ?></p>
        <?php endif; ?>

        <div class="debt-info">
            <p><strong>Total Debt:</strong> ₱<?= number_format($debt['debt_amount'], 2) ?></p>
            <p><strong>Amount Paid:</strong> ₱<?= number_format($debt['amount_paid'], 2) ?></p>
            <p><strong>Remaining Balance:</strong> ₱<?= number_format($balance, 2) ?></p>
        </div>

        <form method="post" class="payment-form">
            <div class="form-group">
                <label for="notes">Add Note</label>
                <textarea name="notes" id="notes" rows="4"><?= htmlspecialchars($clean_notes) ?></textarea>
            </div>

            <div class="form-group">
                <label for="interest">Add Interest (optional)</label>
                <input type="number" name="interest" id="interest" step="0.01" min="0" placeholder="₱0.00">
            </div>

            <div class="form-group">
                <label for="amount_paid">Place Amount</label>
                <input type="number" name="amount_paid" id="amount_paid" step="0.01" min="0" placeholder="₱0.00">
            </div>

            <button type="submit" class="btn-submit">Enter</button>
        </form>

        <a href="customer_debts.php?id=<?= $debt['debtor_id'] ?>" class="back-link">← Back to Debtor Details</a>
    </div>
</div>
<script src="javascript/script.js"></script>
</body>
</html>
