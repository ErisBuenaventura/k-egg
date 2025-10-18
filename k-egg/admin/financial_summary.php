<?php
// --- Financial Summary Calculation ---

$startDate = date('Y-m-d', strtotime('-6 days'));
$endDate = date('Y-m-d');

// Sales
$salesStmt = $conn->prepare("SELECT SUM(total_amount) AS total_sales FROM sales WHERE date BETWEEN ? AND ?");
$salesStmt->bind_param("ss", $startDate, $endDate);
$salesStmt->execute();
$total_sales = $salesStmt->get_result()->fetch_assoc()['total_sales'] ?? 0;

// Expenses
$expensesStmt = $conn->prepare("SELECT SUM(amount) AS total_expenses FROM expenses WHERE DATE(created_at) BETWEEN ? AND ?");
$expensesStmt->bind_param("ss", $startDate, $endDate);
$expensesStmt->execute();
$total_expenses = $expensesStmt->get_result()->fetch_assoc()['total_expenses'] ?? 0;

// Net Profit
$net_profit = $total_sales - $total_expenses;

// Assets (cash = net profit here, unless you track separately)
$cash = $net_profit;
$inventory = $conn->query("SELECT SUM(quantity * unit_cost) AS value FROM inventory")->fetch_assoc()['value'] ?? 0;
$assets = $cash + $inventory;

// Liabilities
$liabilities = $conn->query("SELECT SUM(amount) AS total FROM liabilities")->fetch_assoc()['total'] ?? 0;

// Equity
$equity = $assets - $liabilities;

// Cash Flow
$cash_inflows = $total_sales;
$cash_outflows = $total_expenses;
$net_cash_flow = $cash_inflows - $cash_outflows;

// Summary Array
$financial_summary = [
    'Revenue (Sales)' => $total_sales,
    'Expenses' => $total_expenses,
    'Net Profit' => $net_profit,
    'Assets' => $assets,
    'Liabilities' => $liabilities,
    'Equity' => $equity,
    'Cash Inflows' => $cash_inflows,
    'Cash Outflows' => $cash_outflows,
    'Net Cash Flow' => $net_cash_flow
];
?>
