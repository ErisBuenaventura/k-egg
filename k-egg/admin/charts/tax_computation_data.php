<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['admin'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$conn = new mysqli("localhost", "root", "", "capstone");
if ($conn->connect_error) {
    echo json_encode(['error' => 'DB connection failed']);
    exit;
}

// TAX COMPUTATION LOGIC
$tax_type = $_GET['type'] ?? 'percentage'; // default to percentage

$conn = new mysqli("localhost", "root", "", "capstone");

// Fetch total sales
$sales_result = $conn->query("SELECT SUM(total_amount) AS total_sales FROM sales");
$sales_row = $sales_result->fetch_assoc();
$total_sales = $sales_row['total_sales'] ?? 0;

// Fetch total expenses
$expense_result = $conn->query("SELECT SUM(amount) AS total_expenses FROM expenses");
$expense_row = $expense_result->fetch_assoc();
$total_expenses = $expense_row['total_expenses'] ?? 0;

$output_tax = $input_tax = $tax_payable = 0;
$tax_description = '';

if ($tax_type == 'vat') {
    $output_tax = $total_sales * 0.12;
    $input_tax = $total_expenses * 0.12;
    $tax_payable = $output_tax - $input_tax;
    $tax_description = "VAT Computation (12%)";
} elseif ($tax_type == 'flat8') {
    $taxable = max(0, $total_sales - 250000);
    $tax_payable = $taxable * 0.08;
    $tax_description = "8% Flat Income Tax (above ₱250,000)";
} else {
    $tax_payable = $total_sales * 0.03;
    $tax_description = "3% Percentage Tax";
}

echo json_encode([
    'type' => $tax_type,
    'description' => $description,
    'sales' => number_format($total_sales, 2),
    'expenses' => number_format($total_expenses, 2),
    'output_tax' => number_format($output_tax, 2),
    'input_tax' => number_format($input_tax, 2),
    'tax_payable' => number_format($tax_payable, 2),
    'taxable_amount' => isset($taxable) ? number_format($taxable, 2) : null
]);
?>
