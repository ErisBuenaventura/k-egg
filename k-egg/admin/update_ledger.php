<?php
include '../db.php';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $date = $_POST['date'];
    $amount = $_POST['amount'];
    $debit = $_POST['account_debit'];
    $credit = $_POST['account_credit'];
    $desc = $_POST['description'];

    $stmt = $conn->prepare("UPDATE ledger_entries SET date=?, amount=?, account_debit=?, account_credit=?, description=? WHERE id=?");
    $stmt->bind_param("sdsssi", $date, $amount, $debit, $credit, $desc, $id);
    $stmt->execute();
    $stmt->close();
    header("Location: L&j.php");
    exit();
}
?>
