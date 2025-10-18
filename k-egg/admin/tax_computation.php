<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "capstone");

$tax_type = $_GET['type'] ?? 'percentage';

$sales_result = $conn->query("SELECT SUM(total_amount) AS total_sales FROM sales");
$sales_row = $sales_result->fetch_assoc();
$total_sales = $sales_row['total_sales'] ?? 0;

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

// Return as JSON if requested
if (isset($_GET['format']) && $_GET['format'] === 'json') {
    header('Content-Type: application/json');
    echo json_encode([
        'tax_type' => $tax_type,
        'tax_description' => $tax_description,
        'total_sales' => $total_sales,
        'total_expenses' => $total_expenses,
        'tax_payable' => $tax_payable
    ]);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tax Computation - K-Egg</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body class="bg-light">

<div class="dashboard-container d-flex flex-nowrap overflow-hidden">
    <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <span class="brand">K-EGG Admin</span>
                <button class="toggle-btn" onclick="toggleSidebar()">
                    <i class="bi bi-chevron-double-left"></i>
                </button>
            </div>
            <ul class="nav flex-column nav-items">
                <li class="nav-item" title="Dashboard">
                    <a href="dashboard.php" class="nav-link d-flex align-items-center gap-2"><i class="bi bi-speedometer2"></i><span class="link-text">Dashboard</span></a>
                </li>
                <li class="nav-item" title="Manage Staff">
                    <a href="manage_staff.php" class="nav-link d-flex align-items-center gap-2"><i class="bi bi-people"></i><span class="link-text">Manage Staff</span></a>
                </li>
                <li class="nav-item" title="Reports">
                    <a href="SAE.php" class="nav-link d-flex align-items-center gap-2"><i class="bi bi-file-earmark-text"></i><span class="link-text">Sales and Expense Recording</span></a>
                </li>
                <li class="nav-item" title="Ledger and Journal">
                    <a href="L&j.php" class="nav-link d-flex align-items-center gap-2"><i class="bi bi-journal-bookmark"></i><span class="link-text">Ledger and Journal</span></a>
                </li>
                <li class="nav-item" title="Financial Reports">
                    <a href="financial_reports.php" class="nav-link d-flex align-items-center gap-2 active"><i class="bi bi-clipboard-data"></i><span class="link-text">Financial Reports</span></a>
                </li>
                <li class="nav-item" title="Tax Computation">
                    <a href="tax_computation.php" class="nav-link d-flex align-items-center gap-2">
                    <i class="bi bi-calculator"></i><span class="link-text">Tax Computation</span></a>
                </li>
                <li class="nav-item" title="Staff Balances">
                    <a href="staff_balances.php" class="nav-link d-flex align-items-center gap-2">
                    <i class="bi bi-wallet2"></i>
                    <span class="link-text">Staff Balances</span></a>
                </li>
                <li class="nav-item" title="Data Storage">
                    <a href="data_storage.php" class="nav-link d-flex align-items-center gap-2">
                    <i class="bi bi-hdd-stack"></i>
                    <span class="link-text">Data Storage</span></a>
                </li>
            </ul>
            <div class="sidebar-footer">
                <div class="user-profile">
                    <img src="https://cdn-icons-png.flaticon.com/512/149/149071.png" alt="Admin Icon">
                    <div class="user-info">
                        <strong><?= htmlspecialchars($_SESSION['admin']) ?></strong>
                        <small>Admin</small>
                    </div>
                </div>
                <a href="logout.php" class="btn btn-sm btn-danger w-100 mt-2 logout-btn">
                    <i class="bi bi-box-arrow-right"></i> <span class="logout-text">Logout</span>
                </a>
            </div>
        </div>

    <!-- Main Content -->
    <div class="main-content d-flex flex-column" style="height: 100vh; overflow: hidden;">
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg bg-white shadow-sm rounded mb-3 py-3 px-4 sticky-top w-100 border-bottom">
            <div class="container-fluid d-flex justify-content-between align-items-center">

                <!-- left: Page Title -->
                <h4 class="mb-0 fw-bold text-dark d-none d-md-flex align-items-center">
                    <i class="bi bi-calculator me-2 text-secondary fs-5"></i>Tax Computation
                </h4>

                <!-- Center: Page Title -->
                <div class="d-flex align-items-center gap-2">
                <span class="h4 mb-0 fw-bold text-dark">Accounting System</span>
                </div>

                <!-- Right: Admin Label -->
                <div class="d-flex align-items-center gap-2 text-secondary small">
                <i class="bi bi-person-circle fs-5 text-primary"></i>
                <span class="fw-semibold">Admin</span>
                </div>
            </div>
        </nav>

        <div class="container-fluid">
            <div class="card shadow">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Tax</h4>
                    <form method="get" class="d-flex align-items-center">
                        <label class="text-white me-2">Select Tax Type:</label>
                        <select name="type" class="form-select form-select-sm me-2" onchange="this.form.submit()">
                            <option value="percentage" <?= $tax_type == 'percentage' ? 'selected' : '' ?>>3% Percentage Tax</option>
                            <option value="vat" <?= $tax_type == 'vat' ? 'selected' : '' ?>>12% VAT</option>
                            <option value="flat8" <?= $tax_type == 'flat8' ? 'selected' : '' ?>>8% Flat Income Tax</option>
                        </select>
                    </form>
                </div>
                <div class="card-body">
                    <p><strong>Tax Type:</strong> <?= $tax_description ?></p>
                    <p><strong>Total Sales:</strong> ₱<?= number_format($total_sales, 2) ?></p>
                    <p><strong>Total Expenses:</strong> ₱<?= number_format($total_expenses, 2) ?></p>

                    <?php if ($tax_type == 'vat'): ?>
                        <p><strong>Output Tax (12% Sales):</strong> ₱<?= number_format($output_tax, 2) ?></p>
                        <p><strong>Input Tax (12% Expenses):</strong> ₱<?= number_format($input_tax, 2) ?></p>
                        <p><strong><u>VAT Payable:</u></strong> ₱<?= number_format($tax_payable, 2) ?></p>
                    <?php else: ?>
                        <p><strong><u>Tax Payable:</u></strong> ₱<?= number_format($tax_payable, 2) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('collapsed');
    }
    </script>
</body>
</html>
