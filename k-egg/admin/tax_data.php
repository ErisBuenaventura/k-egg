<?php
$conn = new mysqli("localhost", "root", "", "capstone");

// Total Sales & Expenses
$sales_result = $conn->query("SELECT SUM(total_amount) AS total_sales FROM sales");
$sales_row = $sales_result->fetch_assoc();
$total_sales = $sales_row['total_sales'] ?? 0;

$expense_result = $conn->query("SELECT SUM(amount) AS total_expenses FROM expenses");
$expense_row = $expense_result->fetch_assoc();
$total_expenses = $expense_row['total_expenses'] ?? 0;

// Tax Computation
$percentage_tax = $total_sales * 0.03;

$output_tax = $total_sales * 0.12;
$input_tax = $total_expenses * 0.12;
$vat_payable = $output_tax - $input_tax;

$flat_taxable = max(0, $total_sales - 250000);
$flat_tax = $flat_taxable * 0.08;
?>
