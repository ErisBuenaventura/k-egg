<?php
include '../db.php'; // path to your database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $date = $_POST['date'];
    $reference = $_POST['reference_no'];
    $account = $_POST['account_name'];
    $debit = $_POST['debit'] ?: 0;
    $credit = $_POST['credit'] ?: 0;
    $desc = $_POST['description'];

    $stmt = $conn->prepare("UPDATE journal_entries SET date=?, reference_no=?, account_name=?, description=?, debit=?, credit=? WHERE id=?");
    $stmt->bind_param("ssssddi", $date, $reference, $account, $desc, $debit, $credit, $id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: L&j.php");
    exit();
}
?>
