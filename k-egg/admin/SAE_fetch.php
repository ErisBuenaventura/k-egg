<?php
// SAE_fetch.php — return total sales and expenses in the last 7 days
$conn = new mysqli("localhost", "root", "", "capstone");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sevenDaysAgo = date('Y-m-d', strtotime('-6 days'));
$today = date('Y-m-d');

// Total Sales
$salesStmt = $conn->prepare("SELECT SUM(total_amount) AS total_sales FROM sales WHERE date BETWEEN ? AND ?");
$salesStmt->bind_param("ss", $sevenDaysAgo, $today);
$salesStmt->execute();
$salesResult = $salesStmt->get_result()->fetch_assoc();
$totalSales = $salesResult['total_sales'] ?? 0;

// Total Expenses
$expensesStmt = $conn->prepare("SELECT SUM(amount) AS total_expenses FROM expenses WHERE DATE(created_at) BETWEEN ? AND ?");
$expensesStmt->bind_param("ss", $sevenDaysAgo, $today);
$expensesStmt->execute();
$expensesResult = $expensesStmt->get_result()->fetch_assoc();
$totalExpenses = $expensesResult['total_expenses'] ?? 0;

// Return totals
return ['total_sales' => $totalSales, 'total_expenses' => $totalExpenses];
