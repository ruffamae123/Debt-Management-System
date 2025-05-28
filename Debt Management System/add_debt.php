<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Debt Form</title>
    <<link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.2/css/boxicons.min.css">
    <link rel="stylesheet" type="text/css" href="css/add_debt.css">
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
            <h2 class="form-title"><i class='bx bx-wallet-alt'></i> Add New Debt</h2>

<?php
    session_start();
    if (!isset($_SESSION['username'])) {
        header("Location: login.php");
        exit();
    }
    include('database/config.php');

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $amount = floatval($_POST['amount']);
        $due_date = $_POST['due_date'];
        $notes = mysqli_real_escape_string($conn, $_POST['notes']);

        $debtor_sql = "INSERT INTO debtor_info (name, phone) VALUES ('$name', '$phone')";
        // Validate Philippine phone number format
        if (!empty($phone) && !preg_match('/^(09\d{9}|\+639\d{9})$/', $phone)) {
            echo "<div class='alert error'><i class='bx bx-error-circle'></i> Invalid phone number format. Must be 09XXXXXXXXX or +639XXXXXXXXX.</div>";
            exit(); // Stop the script if phone is invalid
        }

        if ($conn->query($debtor_sql) === TRUE) {
            $debtor_id = $conn->insert_id;

            $debt_sql = "INSERT INTO debts (debtor_id, debt_amount, due_date, notes, status)
                            VALUES ($debtor_id, $amount, '$due_date', '$notes', 'Pending')";
            if ($conn->query($debt_sql) === TRUE) {
                if (isset($_POST['product_name']) && is_array($_POST['product_name'])) {
                    $product_names = $_POST['product_name'];
                    $quantities = $_POST['quantity'];
                    $prices = $_POST['price'];

                    for ($i = 0; $i < count($product_names); $i++) {
                        $pname = mysqli_real_escape_string($conn, $product_names[$i]);
                        $qty = intval($quantities[$i]);
                        $price = floatval($prices[$i]);

                        if (!empty($pname) && $qty > 0 && $price >= 0) {
                            $product_sql = "INSERT INTO borrowed_products (debtor_id, product_name, quantity, price)
                                            VALUES ($debtor_id, '$pname', $qty, $price)";
                            $conn->query($product_sql);
                        }
                    }
                }
                echo "<div class='alert success'><i class='bx bx-check-circle'></i> New Debt added successfully!</div>";
            } else {
                echo "<div class='alert error'><i class='bx bx-error-circle'></i> Error adding debt: " . $conn->error . "</div>";
            }
        } else {
            echo "<div class='alert error'><i class='bx bx-error-circle'></i> Error adding debtor info: " . $conn->error . "</div>";
        }

        $conn->close();
    }
?>


<form method="POST" action="" class="debt-form" id="debtForm">
    <div class="input-group">
        <i class='bx bx-user'></i>
        <input type=        "text" name="name" placeholder="Customer Name" required>
    </div>

    <div class="input-group">
        <i class='bx bx-phone'></i>
        <input type="text" name="phone" id="phone" placeholder="Phone Number (optional)" 
            pattern="^(09\d{9}|\+639\d{9})$" title="Must be a valid PH number: 09XXXXXXXXX or +639XXXXXXXXX">
    </div>


        <div class="input-group">
        <i class='bx bx-money'></i>
        <input type="number" name="amount" placeholder="Amount Owed" min="1" required>
    </div>

    <div class="input-group">
        <i class='bx bx-calendar'></i>
        <input type="date" name="due_date" requireded>
    </div>

    <div class="form-group">
        <label class="form-label">Add Note</label>
        <textarea name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($debt['notes'] ?? ''); ?></textarea>
    </div>

        <input type="submit" value="💾 Add">
     </form>

    </div>
</div>
<script src="javascript/script.js"></script>
</body>
</html>