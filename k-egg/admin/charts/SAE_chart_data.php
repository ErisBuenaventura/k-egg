<?php
header('Content-Type: application/json');
$conn = new mysqli("localhost", "root", "", "capstone");

if ($conn->connect_error) {
    echo json_encode(["error" => "Database connection failed."]);
    exit();
}

// Fetch combined daily sales and expenses
$query = "
    SELECT DATE(date) AS day,
        SUM(total_amount) AS sales
    FROM sales
    GROUP BY day
";

$expenseQuery = "
    SELECT DATE(created_at) AS day,
        SUM(amount) AS expenses
    FROM expenses
    GROUP BY day
";

$salesResult = $conn->query($query);
$expenseResult = $conn->query($expenseQuery);

// Organize sales and expenses by date
$salesData = [];
$expenseData = [];

while ($row = $salesResult->fetch_assoc()) {
    $salesData[$row['day']] = (float)$row['sales'];
}

while ($row = $expenseResult->fetch_assoc()) {
    $expenseData[$row['day']] = (float)$row['expenses'];
}

// Merge and align dates
$allDates = array_unique(array_merge(array_keys($salesData), array_keys($expenseData)));
sort($allDates);

$labels = [];
$sales = [];
$expenses = [];

foreach ($allDates as $date) {
    $labels[] = $date;
    $sales[] = $salesData[$date] ?? 0;
    $expenses[] = $expenseData[$date] ?? 0;
}

echo json_encode([
    "labels" => $labels,
    "sales" => $sales,
    "expenses" => $expenses
]);

$conn->close();
