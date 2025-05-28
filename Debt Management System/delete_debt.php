<?php
session_start();
include('database/config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = $_POST['customer_id'] ?? 0;
    $customer_id = intval($customer_id);

    if ($customer_id > 0) {
        // Start transaction to keep consistency
        mysqli_begin_transaction($conn);

        try {
            // Delete payments for this customer
            $stmt = mysqli_prepare($conn, "DELETE FROM payments WHERE debtor_id = ?");
            mysqli_stmt_bind_param($stmt, 'i', $customer_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // Delete debts for this customer
            $stmt = mysqli_prepare($conn, "DELETE FROM debts WHERE debtor_id = ?");
            mysqli_stmt_bind_param($stmt, 'i', $customer_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // Delete debtor info (customer)
            $stmt = mysqli_prepare($conn, "DELETE FROM debtor_info WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'i', $customer_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // Commit transaction
            mysqli_commit($conn);

            header("Location: paid_debts.php?msg=deleted");
            exit();
        } catch (Exception $e) {
            mysqli_rollback($conn);
            header("Location: paid_debts.php?error=delete_failed");
            exit();
        }
    } else {
        header("Location: paid_debts.php?error=invalid_id");
        exit();
    }
} else {
    // Not a POST request, deny access
    header("Location: paid_debts.php");
    exit();
}
?>
