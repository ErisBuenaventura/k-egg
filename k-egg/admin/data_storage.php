<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "capstone");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$page = $_GET['section'] ?? 'sae'; // Default to SAE (sales and expenses)

$conn = new mysqli("localhost", "root", "", "capstone");

$total_sales = $total_expenses = 0;
$output_tax = $input_tax = $vat_payable = $flat_taxable = $flat_tax = $percentage_tax = 0;

// Fetch Sales and Expenses
$sales_result = $conn->query("SELECT SUM(total_amount) AS total_sales FROM sales");
$total_sales = $sales_result->fetch_assoc()['total_sales'] ?? 0;

$expense_result = $conn->query("SELECT SUM(amount) AS total_expenses FROM expenses");
$total_expenses = $expense_result->fetch_assoc()['total_expenses'] ?? 0;

// Percentage Tax
$percentage_tax = $total_sales * 0.03;

// VAT Computation
$output_tax = $total_sales * 0.12;
$input_tax = $total_expenses * 0.12;
$vat_payable = $output_tax - $input_tax;

// Flat 8% Income Tax
$flat_taxable = max(0, $total_sales - 250000);
$flat_tax = $flat_taxable * 0.08;

// Fetch staff sales and expenses
$query = "
    SELECT s.sold_by AS staff_name,
           COALESCE(SUM(s.total_amount), 0) AS total_sales,
           COALESCE(e.total_expenses, 0) AS total_expenses
    FROM sales s
    LEFT JOIN (
        SELECT recorded_by, SUM(amount) AS total_expenses
        FROM expenses
        GROUP BY recorded_by
    ) e ON s.sold_by = e.recorded_by
    GROUP BY s.sold_by
";

$result = $conn->query($query);

$startDate = date('Y-m-d', strtotime('-6 days'));
$endDate = date('Y-m-d');

// Revenue (Sales)
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

// Assets
$cash = $net_profit;
$inventoryResult = $conn->query("SELECT SUM(quantity * unit_cost) AS value FROM inventory");
$inventory = $inventoryResult->fetch_assoc()['value'] ?? 0;
$assets = $cash + $inventory;

// Liabilities
$liabilitiesResult = $conn->query("SELECT SUM(amount) AS total FROM liabilities");
$liabilities = $liabilitiesResult->fetch_assoc()['total'] ?? 0;

// Equity
$equity = $assets - $liabilities;

// Cash Flow
$cash_inflows = $total_sales;
$cash_outflows = $total_expenses;
$net_cash_flow = $cash_inflows - $cash_outflows;

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

$selectedDate = $_GET['date'] ?? date('Y-m-d');
include 'get_staff_balances.php'; // $staffBalances is now available
?>
<!DOCTYPE html>
<html>
<head>
    <title>Data Storage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
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
    <div class="main-content d-flex flex-column">
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg bg-white shadow-sm rounded mb-3 py-3 px-4 sticky-top w-100 border-bottom">
            <div class="container-fluid d-flex justify-content-between align-items-center">
                
                <!-- Left: System Title -->
                 <h4 class="mb-0 fw-bold text-dark d-none d-md-flex align-items-center">
                    <i class="bi bi-hdd-stack me-2 text-secondary fs-5"></i>Data Storage
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

        <div class="container-fluid mb-2">

            <div class="row row-cols-1 row-cols-md-2 g-4">
                <div class="col-12">
                    <div class="bg-white p-4 rounded shadow-sm h-150" style="max-height: 300px; overflow-y: auto;">
                        <h5 class="mb-3">Recent Sales & Expenses</h5>
                        <table class="table table-bordered table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Description</th>
                                    <th>Amount (₱)</th>
                                    <th>Type</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Fetch latest 10 sales
                                $salesResult = $conn->query("SELECT date, product_name, total_amount FROM sales ORDER BY date DESC LIMIT 5");
                                if ($salesResult->num_rows > 0):
                                    while ($row = $salesResult->fetch_assoc()):
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['date']) ?></td>
                                    <td>Sale: <?= htmlspecialchars($row['product_name']) ?></td>
                                    <td>₱<?= number_format($row['total_amount'], 2) ?></td>
                                    <td><span class="badge bg-success">Sales</span></td>
                                </tr>
                                <?php endwhile; endif; ?>

                                <?php
                                // Fetch latest 10 expenses
                                $expensesResult = $conn->query("SELECT created_at, description, amount FROM expenses ORDER BY created_at DESC LIMIT 5");
                                if ($expensesResult->num_rows > 0):
                                    while ($row = $expensesResult->fetch_assoc()):
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['created_at']) ?></td>
                                    <td>Expense: <?= htmlspecialchars($row['description']) ?></td>
                                    <td>₱<?= number_format($row['amount'], 2) ?></td>
                                    <td><span class="badge bg-danger">Expense</span></td>
                                </tr>
                                <?php endwhile; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Ledger Entries Table -->
                <div class="col-12">
                    <div class="bg-white p-4 rounded shadow-sm h-150" style="max-height: 300px; overflow-y: auto;">
                        <h5 class="mb-3">Ledger Entries</h5>
                        <table class="table table-bordered table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Account</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Reference</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $ledgerResult = $conn->query("SELECT * FROM ledger_entries ORDER BY date DESC");
                                if ($ledgerResult->num_rows > 0):
                                    while ($row = $ledgerResult->fetch_assoc()):
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['date']) ?></td>
                                    <td><?= htmlspecialchars($row['account_debit']) ?></td>
                                    <td><?= htmlspecialchars($row['transaction_type']) ?></td>
                                    <td>₱<?= number_format($row['amount'], 2) ?></td>
                                    <td><?= htmlspecialchars($row['id']) ?></td>
                                </tr>
                                <?php endwhile; else: ?>
                                <tr><td colspan="5" class="text-center text-muted">No ledger entries found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Journal Entries Table -->
                <div class="col-12">
                    <div class="bg-white p-4 rounded shadow-sm h-150" style="max-height: 300px; overflow-y: auto;">
                        <h5 class="mb-3">Journal Entries</h5>
                        <table class="table table-bordered table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Description</th>
                                    <th>Account</th>
                                    <th>Debit</th>
                                    <th>Credit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $journalResult = $conn->query("SELECT * FROM journal_entries ORDER BY date DESC");
                                if ($journalResult->num_rows > 0):
                                    while ($row = $journalResult->fetch_assoc()):
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['date']) ?></td>
                                    <td><?= htmlspecialchars($row['description']) ?></td>
                                    <td><?= htmlspecialchars($row['account_name']) ?></td>
                                    <td>₱<?= $row['debit'] ? number_format($row['debit'], 2) : '-' ?></td>
                                    <td>₱<?= $row['credit'] ? number_format($row['credit'], 2) : '-' ?></td>
                                </tr>
                                <?php endwhile; else: ?>
                                <tr><td colspan="5" class="text-center text-muted">No journal entries found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="col-12">
                    <div class="bg-white p-4 rounded shadow-sm" style="max-height: 300px; overflow-y: auto;">
                        <h5 class="mb-3">Financial Summary Overview</h5>
                        <table class="table table-bordered table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Category</th>
                                    <th>Amount (₱)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($financial_summary as $label => $amount): ?>
                                    <tr>
                                        <td><strong><?= $label ?></strong></td>
                                        <td class="<?= $amount < 0 ? 'text-danger' : 'text-success' ?>">₱<?= number_format($amount, 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-12">
                    <div class="bg-white p-4 rounded shadow-sm h-150" style="max-height: 300px; overflow-y: auto;">
                        <h5 class="mb-3">Tax Computation Summary</h5>
                        <table class="table table-bordered table-sm">
                            <thead class="table-light">
                                <tr><th colspan="2">3% Percentage Tax</th></tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Total Sales</td>
                                    <td>₱<?= number_format($total_sales, 2) ?></td>
                                </tr>
                                <tr>
                                    <td>Total Expenses</td>
                                    <td>₱<?= number_format($total_expenses, 2) ?></td>
                                </tr>
                                <tr>
                                    <td>Tax Payable (3%)</td>
                                    <td>₱<?= number_format($percentage_tax, 2) ?></td>
                                </tr>
                            </tbody>

                            <thead class="table-light mt-4">
                                <tr><th colspan="2">VAT Computation (12%)</th></tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Total Sales</td>
                                    <td>₱<?= number_format($total_sales, 2) ?></td>
                                </tr>
                                <tr>
                                    <td>Total Expenses</td>
                                    <td>₱<?= number_format($total_expenses, 2) ?></td>
                                </tr>
                                <tr>
                                    <td>Output Tax (12% of Sales)</td>
                                    <td>₱<?= number_format($output_tax, 2) ?></td>
                                </tr>
                                <tr>
                                    <td>Input Tax (12% of Expenses)</td>
                                    <td>₱<?= number_format($input_tax, 2) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>VAT Payable</strong></td>
                                    <td class="<?= $vat_payable < 0 ? 'text-danger' : 'text-success' ?>">₱<?= number_format($vat_payable, 2) ?></td>
                                </tr>
                            </tbody>

                            <thead class="table-light mt-4">
                                <tr><th colspan="2">8% Flat Income Tax (above ₱250,000)</th></tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Total Sales</td>
                                    <td>₱<?= number_format($total_sales, 2) ?></td>
                                </tr>
                                <tr>
                                    <td>Taxable Amount (Sales - ₱250,000)</td>
                                    <td>₱<?= number_format($flat_taxable, 2) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Tax Payable (8%)</strong></td>
                                    <td class="<?= $flat_tax < 0 ? 'text-danger' : 'text-success' ?>">₱<?= number_format($flat_tax, 2) ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-12">
    <div class="bg-white p-4 rounded shadow-sm h-150" style="max-height: 300px; overflow-y: auto;">
        <h5 class="mb-3">Staff Balances</h5>
        <table class="table table-bordered table-sm">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Sold By</th>
                    <th>Total Sales</th>
                    <th>Declared</th>
                    <th>Balance</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($staffBalances)): ?>
                    <?php foreach ($staffBalances as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['date']); ?></td>
                            <td><?php echo htmlspecialchars($row['sold_by']); ?></td>
                            <td>₱<?php echo number_format($row['total_sales'], 2); ?></td>
                            <td>₱<?php echo number_format($row['declared'], 2); ?></td>
                            <td class="<?= $row['balance'] < 0 ? 'text-danger' : 'text-success' ?>">
                                ₱<?php echo number_format($row['balance'], 2); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">No staff balances found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

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

<?php $conn->close(); ?>
