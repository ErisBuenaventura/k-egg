<?php
session_start();
include 'db.php'; // Your DB connection
if (!isset($_SESSION['staff'])) {
    header('Location: staff.php');
    exit();
}

$staffName = $_SESSION['staff'];

// Sample default values – replace with actual logic if you use a branch system
$branch = $_SESSION['branch'] ?? 'Main Branch';
$today = date('Y-m-d');

// Fetch today's sales
$salesStmt = $conn->prepare("SELECT SUM(total_amount) AS total_sales FROM sales WHERE DATE(date) = ?");

if ($salesStmt) {
    $salesStmt->bind_param("s", $today);
    $salesStmt->execute();
    $salesStmt->bind_result($totalSales);
    if ($salesStmt->fetch()) {
        $sales = $totalSales ?: 0.00; // Ensure it doesn't stay null
    }
    $salesStmt->close();
} else {
    echo "Sales query failed: " . $conn->error;
}

// Fetch today's expenses
$expenses = 0.00; // Default value in case query fails
$expensesStmt = $conn->prepare("SELECT SUM(amount) AS total_expenses FROM expenses WHERE DATE(date) = ?");
if ($expensesStmt) {
    $expensesStmt->bind_param("s", $today);
    $expensesStmt->execute();
    $expensesStmt->bind_result($totalExpenses);
    if ($expensesStmt->fetch()) {
        $expenses = $totalExpenses ?: 0.00;
    }
    $expensesStmt->close();
} else {
    echo "Expenses query failed: " . $conn->error;
}

// Recent entries
$recentSales = $conn->prepare("SELECT date, total_amount, remarks FROM sales WHERE sold_by = ? ORDER BY date DESC LIMIT 5");
if (!$recentSales) {
    die("Prepare failed for recent sales: (" . $conn->errno . ") " . $conn->error);
}
$recentSales->bind_param("s", $staffName);
$recentSales->execute();
$salesResult = $recentSales->get_result();

$recentExpenses = $conn->prepare("SELECT date, amount, category FROM expenses WHERE recorded_by = ? ORDER BY created_at DESC LIMIT 5");
if (!$recentExpenses) {
    die("Prepare failed for recent expenses: (" . $conn->errno . ") " . $conn->error);
}
$recentExpenses->bind_param("s", $staffName);
$recentExpenses->execute();
$expensesResult = $recentExpenses->get_result();

// --- Prepare weekly sales & expenses data for Chart.js ---

// Last 7 days dates (Y-m-d)
$dates = [];
for ($i = 6; $i >= 0; $i--) {
    $dates[] = date('Y-m-d', strtotime("-$i days"));
}

// Initialize arrays with zero values
$salesData = array_fill_keys($dates, 0);
$expensesData = array_fill_keys($dates, 0);

// Fetch weekly sales grouped by date
$salesSql = "SELECT DATE(date) as date_only, SUM(total_amount) as total_sales 
                FROM sales 
                WHERE DATE(date) BETWEEN ? AND ?
                GROUP BY DATE(date)";

$salesStmt = $conn->prepare($salesSql); // ✅ prepare first

$salesStmt->bind_param("ss", $startDate, $endDate);
$startDate = $dates[0];
$endDate = $dates[6];
$salesStmt->bind_param("ss", $startDate, $endDate); // ✅
$salesStmt->execute();
$result = $salesStmt->get_result();
while ($row = $result->fetch_assoc()) {
    $salesData[$row['date_only']] = (float)$row['total_sales'];
}
$salesStmt->close();

// Fetch weekly expenses grouped by date
$expensesSql = "SELECT DATE(date) as date_only, SUM(amount) as total_expenses 
                FROM expenses 
                WHERE DATE(date) BETWEEN ? AND ?
                GROUP BY DATE(date)";

$expensesStmt = $conn->prepare($expensesSql); // ✅ prepare first

$expensesStmt->bind_param("ss", $startDate, $endDate);
$startDate = $dates[0];
$endDate = $dates[6];
$expensesStmt->bind_param("ss", $startDate, $endDate); // ✅
$expensesStmt->execute();
$result = $expensesStmt->get_result();
while ($row = $result->fetch_assoc()) {
    $expensesData[$row['date_only']] = (float)$row['total_expenses'];
}
$expensesStmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Staff Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="staff_dashboard.css">
</head>
<body>
    <div class="d-flex flex-nowrap overflow-hidden">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <span class="brand">K-EGG</span>
                <button class="toggle-btn" onclick="toggleSidebar()">
                    <i class="bi bi-chevron-double-left"></i>
                </button>
            </div>
            <ul class="nav flex-column nav-items">
                <li class="nav-item" title="Dashboard">
                    <a href="staff_dashboard.php" class="nav-link"><i class="bi bi-house-door"></i><span class="link-text">Dashboard</span></a>
                </li>
                <li class="nav-item" title="Sales Entry">
                    <a href="sales_entry.php" class="nav-link"><i class="bi bi-clipboard-data"></i><span class="link-text">Sales Entry</span></a>
                </li>
                <li class="nav-item" title="Expense Recording">
                    <a href="expense_recording.php" class="nav-link"><i class="bi bi-cash-coin"></i><span class="link-text">Expense Recording</span></a>
                </li>
            </ul>
            <div class="sidebar-footer">
                <div class="user-profile">
                    <img src="https://cdn-icons-png.flaticon.com/512/149/149071.png" alt="staff Icon">
                    <div class="user-info">
                        <strong><?= htmlspecialchars($staffName) ?></strong>
                        <small>Staff</small>
                    </div>
                </div>
                <a href="logout_staff.php" class="btn btn-sm btn-danger w-100 mt-2 logout-btn">
                    <i class="bi bi-box-arrow-right"></i>
                    <span class="logout-text">Logout</span>
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content d-flex flex-column" style="height: 100vh; overflow: hidden;">
            <!-- Navbar -->
            <nav class="navbar navbar-light bg-white shadow-sm rounded mb-3 py-2 px-3 sticky-top w-100">
            <div class="container-fluid">
                <a class="navbar-brand mb-0 h6 text-dark fw-semibold" href="#"> Welcome, 
                <span class="text-primary"><?= htmlspecialchars($staffName) ?>!</span>
                </a>
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-person-circle fs-3 text-secondary me-2"></i>
                    <span class="small text-secondary">Staff</span>
                </div>
            </div>
            </nav>

            <div class="container-fluid row g-3">
                <!-- Cards Snapshot (Smaller Version) -->
                <div class="col-md-4">
                    <div class="card shadow-sm p-2">
                        <div class="card-body py-2 px-3">
                            <h6 class="mb-1">Today's Sales Total</h6>
                            <p class="card-text fs-6 mb-0">₱<?= number_format($sales, 2) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm p-2">
                        <div class="card-body py-2 px-3">
                            <h6 class="mb-1">Today's Expenses</h6>
                            <p class="card-text fs-6 mb-0">₱<?= number_format($expenses, 2) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm p-2">
                        <div class="card-body py-2 px-3">
                            <h6 class="mb-1">Current Branch</h6>
                            <p class="card-text fs-6 mb-0"><?= htmlspecialchars($branch) ?></p>
                        </div>
                    </div>
                </div>

                <!-- Recent Entries (Smaller) -->
                <div class="row mt-2">
                    <div class="col-md-6">
                        <h6 class="mb-2">Recent Sales</h6>
                        <ul class="list-group list-group-sm">
                            <?php while ($sale = $salesResult->fetch_assoc()): ?>
                                <li class="list-group-item py-1 px-2 small">
                                    <?= htmlspecialchars($sale['date']) ?> — ₱<?= number_format($sale['total_amount'], 2) ?> — <?= htmlspecialchars($sale['remarks']) ?>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6 class="mb-2">Recent Expenses</h6>
                        <ul class="list-group list-group-sm">
                            <?php while ($expense = $expensesResult->fetch_assoc()): ?>
                                <li class="list-group-item py-1 px-2 small">
                                    <?= htmlspecialchars($expense['date']) ?> — ₱<?= number_format($expense['amount'], 2) ?> — <?= htmlspecialchars($expense['category']) ?>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    </div>
                </div>

                <!-- Smaller Graph Section -->
                <div class="mt-3">
                    <h6 class="mb-2">Sales vs Expenses (Last 7 Days)</h6>
                    <canvas id="weeklyChart" height="75"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Prepare data for Chart.js from PHP
        const labels = <?= json_encode(array_map(function($d) { return date("M d", strtotime($d)); }, $dates)) ?>;
        const salesData = <?= json_encode(array_values($salesData)) ?>;
        const expensesData = <?= json_encode(array_values($expensesData)) ?>;

        const ctx = document.getElementById('weeklyChart').getContext('2d');
        const weeklyChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Sales',
                        data: salesData,
                        backgroundColor: 'rgba(54, 162, 235, 0.7)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Expenses',
                        data: expensesData,
                        backgroundColor: 'rgba(255, 99, 132, 0.7)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            // Include a peso sign in the ticks
                            callback: function(value) {
                                return '₱' + value.toFixed(2);
                            }
                        }
                    }
                },
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ₱' + context.parsed.y.toFixed(2);
                            }
                        }
                    }
                }
            }
        });

        // Sidebar toggle function
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('collapsed');
        }
    </script>
</body>
</html>
