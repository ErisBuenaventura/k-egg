<?php
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $staff = $_POST['staff_name'];
    $date = $_POST['declaration_date'];
    $amount = floatval($_POST['declared_amount']);
    $remarks = $_POST['remarks'];

    // Insert or replace the single row
    $stmt = $conn->prepare("
        INSERT INTO cash_declaration (staff_name, declaration_date, declared_amount, remarks)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            declared_amount = VALUES(declared_amount),
            remarks = VALUES(remarks)
    ");
    $stmt->bind_param("ssds", $staff, $date, $amount, $remarks);
    $stmt->execute();

    header("Location: staff_balances.php?date=" . urlencode($date));
    exit();

    echo "Form submitted"; exit;

}
?>
