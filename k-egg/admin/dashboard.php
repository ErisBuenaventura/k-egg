<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}

// Get totals
$data = include 'SAE_fetch.php';
$totalSales = $data['total_sales'];
$totalExpenses = $data['total_expenses'];

// Fetch total staff directly
$pdo = new PDO('mysql:host=localhost;dbname=capstone', 'root', '');
$stmt = $pdo->query("SELECT COUNT(*) AS total_staff FROM staff");
$totalStaff = $stmt->fetch(PDO::FETCH_ASSOC)['total_staff'];

$transactionData = include 'LJ_count.php';
$ledgerTotal = $transactionData['ledger'];
$journalTotal = $transactionData['journal'];


// Total Revenue (Sales)
$salesResult = $conn->query("SELECT SUM(total_amount) AS total_sales FROM sales");
$salesRow = $salesResult->fetch_assoc();
$totalSales = $salesRow['total_sales'] ?? 0;

// Total Expenses
$expenseResult = $conn->query("SELECT SUM(amount) AS total_expenses FROM expenses");
$expenseRow = $expenseResult->fetch_assoc();
$totalExpenses = $expenseRow['total_expenses'] ?? 0;

// Net Profit
$netProfit = $totalSales - $totalExpenses;

// Assets = Inventory Value
$inventoryResult = $conn->query("SELECT SUM(quantity * unit_cost) AS total_inventory FROM inventory");
$inventoryRow = $inventoryResult->fetch_assoc();
$totalAssets = $inventoryRow['total_inventory'] ?? 0;

// Liabilities
$liabilitiesResult = $conn->query("SELECT SUM(amount) AS total_liabilities FROM liabilities");
$liabilitiesRow = $liabilitiesResult->fetch_assoc();
$totalLiabilities = $liabilitiesRow['total_liabilities'] ?? 0;

// Equity = Assets - Liabilities
$equity = $totalAssets - $totalLiabilities;

// Cash Inflows = Sales
$cashInflows = $totalSales;

// Cash Outflows = Expenses
$cashOutflows = $totalExpenses;

// Net Cash Flow
$netCashFlow = $cashInflows - $cashOutflows;

include 'tax_data.php';
// This assumes tax_computation.php is included before the HTML
$taxLabels = ['Percentage Tax (3%)', 'VAT Payable (12%)', 'Flat Tax (8%)'];
$taxValues = [$percentage_tax, $vat_payable, $flat_tax];

?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
       <!-- Sleek Admin Navbar -->
        <nav class="navbar navbar-expand-lg bg-white shadow-sm rounded mb-2 py-3 px-4 sticky-top w-100 border-bottom">
            <div class="container-fluid d-flex justify-content-between align-items-center">

                <!-- left: Page Title -->
                <h4 class="mb-0 fw-bold text-dark d-none d-md-flex align-items-center">
                <i class="bi bi-speedometer2 me-2 text-secondary fs-5"></i> Admin Dashboard
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
            <!-- First Row: KPI Cards -->
            <div class="row g-3 mb-2">
                <!-- Sales -->
                <div class="col-md-4">
                    <div class="card bg-light shadow-sm" style="min-height: 100px;">
                        <div class="card-body d-flex align-items-center justify-content-center gap-3 py-0">
                            <div class="bg-success text-white rounded-circle d-flex justify-content-center align-items-center" style="width: 60px; height: 60px;">
                                <i class="bi bi-cart-fill fs-4"></i>
                            </div>
                            <div class="text-start">
                                <div class="text-muted fw-bold fs-4">Sales</div>
                                <div class="text-success fw-bold fs-5">₱<?= number_format($totalSales, 2) ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Expenses -->
                <div class="col-md-4">
                    <div class="card bg-light shadow-sm" style="min-height: 100px;">
                        <div class="card-body d-flex align-items-center justify-content-center gap-3 py-0">
                            <div class="bg-danger text-white rounded-circle d-flex justify-content-center align-items-center" style="width: 60px; height: 60px;">
                                <i class="bi bi-cash-stack fs-4"></i>
                            </div>
                            <div class="text-start">
                                <div class="text-muted fw-bold fs-4">Expenses</div>
                                <div class="text-danger fw-bold fs-5">₱<?= number_format($totalExpenses, 2) ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Staff -->
                <div class="col-md-4">
                    <div class="card bg-light shadow-sm" style="min-height: 100px;">
                        <div class="card-body d-flex align-items-center justify-content-center gap-4 py-0">
                            <div class="bg-info text-white rounded-circle d-flex justify-content-center align-items-center" style="width: 60px; height: 60px;">
                                <i class="bi bi-people-fill fs-4"></i>
                            </div>
                            <div class="text-start">
                                <div class="text-muted fw-bold fs-4">Staff</div>
                                <div class="text-info fw-bold fs-5"><?= $totalStaff ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <!-- LEFT SIDE -->
                <div class="col-md-6 d-flex flex-column gap-2">
                    <!-- Pie Charts -->
                    <div class="d-flex gap-1">

                        <!-- Cash Flow -->
                        <div class="col-12 col-sm-6 col-lg-4 card summary-card bg-white shadow-sm border-0">
                            <div class="card-body d-flex flex-column justify-content-center align-items-center p-2 h-100">
                                <div class="text-primary mb-2 fw-bold">Cash Flow</div>
                                <canvas id="cashflowChart" width="100"></canvas>
                            </div>
                        </div>

                        <!-- Income -->
                        <div class="col-12 col-sm-6 col-lg-4 card summary-card bg-white shadow-sm border-0">
                            <div class="card-body d-flex flex-column justify-content-center align-items-center p-2 h-100">
                                <div class="text-success mb-2 fw-bold">Income</div>
                                <canvas id="incomeChart" width="100"></canvas>
                            </div>
                        </div>

                        <!-- Balance -->
                        <div class="col-12 col-sm-6 col-lg-4 card summary-card bg-white shadow-sm border-0">
                            <div class="card-body d-flex flex-column justify-content-center align-items-center p-2 h-100">
                                <div class="text-warning mb-2 fw-bold">Balance</div>
                                <canvas id="balanceChart" width="100"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Ledger & Journal Table -->
                    <div class="card shadow-sm border-0" style="max-height: 140px;">
                        <div class="card-header bg-secondary text-white py-1">
                                <h6 class="bi bi-journal-bookmark mb-0"> Ledger & Journal Overview</h6>
                            </div>
                            <div class="card-body p-2">
                                <table class="table table-sm table-striped table-bordered mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Transaction Type</th>
                                            <th>Total Entries</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><i class="bi bi-journal-text me-2 text-primary"></i>Ledger Entries</td>
                                            <td><strong><?= $ledgerTotal ?></strong></td>
                                        </tr>
                                        <tr>
                                            <td><i class="bi bi-journal-bookmark me-2 text-warning"></i>Journal Entries</td>
                                            <td><strong><?= $journalTotal ?></strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT SIDE -->
                    <div class="col-12 col-md-6">
                        <div class="bg-white p-5 rounded shadow-sm" style="min-height: 365px; height: 60vh;">
                            <h6 class="bi bi-calculator mb-3 fw-semibold">Tax Computation Summary</h6>
                            <canvas id="taxSummaryChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const totalSales = <?= $totalSales ?>;
        const totalExpenses = <?= $totalExpenses ?>;
        const netProfit = <?= $netProfit ?>;
        const totalAssets = <?= $totalAssets ?>;
        const totalLiabilities = <?= $totalLiabilities ?>;
        const equity = <?= $equity ?>;
        const cashInflows = <?= $cashInflows ?>;
        const cashOutflows = <?= $cashOutflows ?>;
        const netCashFlow = <?= $netCashFlow ?>;
    </script>

    <script>
        Chart.register(ChartDataLabels); // Register the plugin globally

        const commonOptions = {
            responsive: true,
            aspectRatio: 1, // Ensures a stable, square chart
            plugins: {
                legend: { display: false },
                tooltip: { enabled: true },
                datalabels: {
                    color: '#fff',
                    font: { weight: 'bold', size: 10 },
                    formatter: (value, context) => {
                        const sum = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                        const percentage = ((value / sum) * 100).toFixed(1) + '%';
                        return percentage;
                    }
                }
            }
        };

        // Income Statement Pie
        new Chart(document.getElementById("incomeChart"), {
            type: "pie",
            data: {
                labels: ["Sales", "Expenses", "Net Profit"], // still needed internally
                datasets: [{
                    data: [totalSales, totalExpenses, netProfit],
                    backgroundColor: ["#4ade80", "#f87171", "#60a5fa"]
                }]
            },
            options: commonOptions
        });

        // Balance Sheet Pie
        new Chart(document.getElementById("balanceChart"), {
            type: "pie",
            data: {
                labels: ["Assets", "Liabilities", "Equity"],
                datasets: [{
                    data: [totalAssets, totalLiabilities, equity],
                    backgroundColor: ["#facc15", "#f97316", "#34d399"]
                }]
            },
            options: commonOptions
        });

        // Cash Flow Summary Pie
        new Chart(document.getElementById("cashflowChart"), {
            type: "pie",
            data: {
                labels: ["Cash Inflows", "Cash Outflows", "Net Cash Flow"],
                datasets: [{
                    data: [cashInflows, cashOutflows, netCashFlow],
                    backgroundColor: ["#38bdf8", "#fb7185", "#a78bfa"]
                }]
            },
            options: commonOptions
        });
    </script>

    <script>
    const taxSummaryCtx = document.getElementById('taxSummaryChart').getContext('2d');

        new Chart(taxSummaryCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($taxLabels) ?>,
                datasets: [{
                    label: 'Tax Payable',
                    data: <?= json_encode($taxValues) ?>,
                    backgroundColor: ['#60a5fa', '#fbbf24', '#34d399'],
                    borderRadius: 8,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false, // ✅ Add this line
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return '₱' + context.raw.toLocaleString(undefined, {minimumFractionDigits: 2});
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    </script>

    <script>
        const adminChartCtx = document.getElementById('adminChart').getContext('2d');
        const adminChart = new Chart(adminChartCtx, {
            type: 'bar',
            data: {
                labels: ['June 1', 'June 2', 'June 3', 'June 4', 'June 5'],
                datasets: [{
                    label: 'System Logs',
                    data: [12, 19, 7, 14, 20],
                    backgroundColor: 'rgba(75, 192, 192, 0.7)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('collapsed');
        }
    </script>

</body>
</html>
