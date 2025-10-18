<?php
header('Content-Type: application/json');
$conn = new mysqli("localhost", "root", "", "capstone");
if ($conn->connect_error) {
    echo json_encode(['error' => 'Connection failed']);
    exit;
}

// Sum of sales
$sales = $conn->query("SELECT SUM(total_amount) AS total FROM sales");
$sales_total = $sales->fetch_assoc()['total'] ?? 0;

// Sum of expenses
$expenses = $conn->query("SELECT SUM(amount) AS total FROM expenses");
$expenses_total = $expenses->fetch_assoc()['total'] ?? 0;

// Profit
$profit = $sales_total - $expenses_total;

echo json_encode([
    'labels' => ['Sales', 'Expenses', 'Profit'],
    'data' => [
        round($sales_total, 2),
        round($expenses_total, 2),
        round($profit, 2)
    ]
]);
?>
