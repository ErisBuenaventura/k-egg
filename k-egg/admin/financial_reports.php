<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}
include '../db.php'; // adjust path if needed

$start = $_GET['start_date'] ?? null;
$end = $_GET['end_date'] ?? null;
$type = $_GET['report_type'] ?? null;

// Add Inventory
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_inventory'])) {
    $product = $_POST['product_name'];
    $qty = $_POST['quantity'];
    $cost = $_POST['unit_cost'];
    $stmt = $conn->prepare("INSERT INTO inventory (product_name, quantity, unit_cost) VALUES (?, ?, ?)");
    $stmt->bind_param("sid", $product, $qty, $cost);
    $stmt->execute();
    $stmt->close();
}

// Add Liability
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_liability'])) {
    $desc = $_POST['liability_description'];
    $amount = $_POST['liability_amount'];
    $due = $_POST['liability_due'];
    $stmt = $conn->prepare("INSERT INTO liabilities (description, amount, due_date) VALUES (?, ?, ?)");
    $stmt->bind_param("sds", $desc, $amount, $due);
    $stmt->execute();
    $stmt->close();
}

// Edit inventory
if (isset($_POST['update_inventory'])) {
    $id = $_POST['edit_inventory_id'];
    $product = $_POST['edit_product_name'];
    $qty = $_POST['edit_quantity'];
    $cost = $_POST['edit_unit_cost'];
    $conn->query("UPDATE inventory SET product_name='$product', quantity=$qty, unit_cost=$cost WHERE id=$id");
}

// Delete inventory
if (isset($_POST['confirm_delete_inventory'])) {
    $id = $_POST['delete_inventory_id'];
    $conn->query("DELETE FROM inventory WHERE id=$id");
}

// Edit liability
if (isset($_POST['update_liability'])) {
    $id = $_POST['edit_liability_id'];
    $desc = $_POST['edit_liability_description'];
    $amount = $_POST['edit_liability_amount'];
    $due = $_POST['edit_liability_due'];

    $stmt = $conn->prepare("UPDATE liabilities SET description=?, amount=?, due_date=? WHERE id=?");
    $stmt->bind_param("sdsi", $desc, $amount, $due, $id);
    $stmt->execute();
    $stmt->close();
}

// Delete liability
if (isset($_POST['confirm_delete_liability'])) {
    $id = $_POST['delete_liability_id'];

    $stmt = $conn->prepare("DELETE FROM liabilities WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

// Calculate values
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

// Assets (Cash + Inventory)
$cash = $net_profit; // Adjust if you track cash differently
$inventory = $conn->query("SELECT SUM(quantity * unit_cost) AS value FROM inventory")->fetch_assoc()['value'] ?? 0;
$assets = $cash + $inventory;

// Liabilities
$liabilities = $conn->query("SELECT SUM(amount) AS total FROM liabilities")->fetch_assoc()['total'] ?? 0;

// Equity
$equity = $assets - $liabilities;

// Cash Flows
$cash_inflows = $total_sales;
$cash_outflows = $total_expenses;
$net_cash_flow = $cash_inflows - $cash_outflows;

// Return associative array
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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Financial Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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

                <!-- left: Page Title -->
                <h4 class="mb-0 fw-bold text-dark d-none d-md-flex align-items-center">
                    <i class="bi bi-clipboard-data me-2 text-secondary fs-5"></i>Financial Reports
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

        <!-- Report Section -->
        <div class="container-fluid">
            <div class="row g-2">
                <!-- LEFT SIDE: 50% Width Filter Form -->
                <div class="col-12 col-md-6" style="width: 50%;">
                    <div class="card shadow-sm h-55 small">
                        <div class="card-header bg-primary text-white">
                            <strong><i class="bi bi-funnel-fill"></i> Filter</strong>
                        </div>
                        <div class="card-body py-3 px-2">
                            <form method="GET">
                                <div class="col-12">
                                    <label for="start_date" class="form-label mb-1">Start</label>
                                    <input type="date" id="start_date" name="start_date" class="form-control form-control-sm" value="<?= htmlspecialchars($start) ?>" required>
                                </div>
                                <div class="col-12">
                                    <label for="end_date" class="form-label mb-1">End</label>
                                    <input type="date" id="end_date" name="end_date" class="form-control form-control-sm" value="<?= htmlspecialchars($end) ?>" required>
                                </div>
                                <div class="col-12">
                                    <label for="report_type" class="form-label mb-1">Type</label>
                                    <select name="report_type" class="form-select form-select-sm" required>
                                        <option value="">-- Select --</option>
                                        <option value="pl" <?= $type == 'pl' ? 'selected' : '' ?>>Profit & Loss</option>
                                        <option value="bs" <?= $type == 'bs' ? 'selected' : '' ?>>Balance Sheet</option>
                                        <option value="cf" <?= $type == 'cf' ? 'selected' : '' ?>>Cash Flow</option>
                                    </select>
                                </div>
                                <div class="col-12 d-grid mt-4">
                                    <button type="submit" class="btn btn-sm btn-primary">Generate</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- RIGHT SIDE: Report Output -->
                <div class="col-md-8" style="width: 50%;">
                    <div class="card shadow-sm h-55 small" >
                        <div class="card-header bg-dark text-white">
                            <strong><i class="bi bi-clipboard-data"></i> Report Result</strong>
                        </div>
                        <div class="card-body">
                            <?php if ($type && $start && $end): ?>
                                <p class="mb-3"><strong>Period:</strong> <?= date('M d, Y', strtotime($start)) ?> to <?= date('M d, Y', strtotime($end)) ?></p>
                                <?php
                                    switch ($type) {
                                        case 'pl':
                                            generateProfitLoss($conn, $start, $end);
                                            break;
                                        case 'bs':
                                            generateBalanceSheet($conn);
                                            break;
                                        case 'cf':
                                            generateCashFlow($conn, $start, $end);
                                            break;
                                        default:
                                            echo "<p class='text-danger'>Invalid report type selected.</p>";
                                    }
                                ?>
                            <?php else: ?>
                                <p class="text-muted">Please select a date range and report type.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
             
            <div class="row g-2">
                <div class="col-md-6" style="width: 50%;">
                    <div class="card shadow-sm small">
                        <div class="card-header bg-success text-white py-2 px-3">
                            <strong><i class="bi bi-box-seam"></i> Inventory (Assets)</strong>
                        </div>

                        <!-- Add Inventory Form -->
                        <div class="card-body pt-3 pb-1 px-2">
                            <form method="POST" class="row g-1 align-items-end">
                                <div class="col-6">
                                    <input type="text" name="product_name" class="form-control form-control-sm" placeholder="Product" required>
                                </div>
                                <div class="col-3">
                                    <input type="number" name="quantity" class="form-control form-control-sm" placeholder="Qty" required>
                                </div>
                                <div class="col-3">
                                    <input type="number" step="0.01" name="unit_cost" class="form-control form-control-sm" placeholder="₱/Unit" required>
                                </div>
                                <div class="col-12 d-grid mt-2">
                                    <button type="submit" name="add_inventory" class="btn btn-sm btn-success">Add</button>
                                </div>
                            </form>
                        </div>

                        <!-- Inventory Table -->
                        <div class="card-body pt-2 px-2">
                            <table class="table table-sm table-bordered table-hover mb-0">
                                <thead class="table-light small">
                                    <tr>
                                        <th>Product</th>
                                        <th>Qty</th>
                                        <th>₱/Unit</th>
                                        <th>Total</th>
                                        <th>Added</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $inv = $conn->query("SELECT * FROM inventory ORDER BY created_at DESC");
                                    while ($row = $inv->fetch_assoc()):
                                        $total = $row['quantity'] * $row['unit_cost'];
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['product_name']) ?></td>
                                        <td><?= $row['quantity'] ?></td>
                                        <td><?= number_format($row['unit_cost'], 2) ?></td>
                                        <td><?= number_format($total, 2) ?></td>
                                        <td><?= date('M d', strtotime($row['created_at'])) ?></td>
                                        <td class="text-nowrap">
                                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editInventoryModal<?= $row['id'] ?>"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteInventoryModal<?= $row['id'] ?>"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>

                                    <!-- Edit Modal -->
                                    <div class="modal fade" id="editInventoryModal<?= $row['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <form method="POST">
                                                <input type="hidden" name="edit_inventory_id" value="<?= $row['id'] ?>">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-warning">
                                                        <h5 class="modal-title">Edit Inventory</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body row g-2">
                                                        <div class="col-12">
                                                            <label>Product</label>
                                                            <input type="text" name="edit_product_name" class="form-control" value="<?= htmlspecialchars($row['product_name']) ?>" required>
                                                        </div>
                                                        <div class="col-6">
                                                            <label>Quantity</label>
                                                            <input type="number" name="edit_quantity" class="form-control" value="<?= $row['quantity'] ?>" required>
                                                        </div>
                                                        <div class="col-6">
                                                            <label>Unit Cost</label>
                                                            <input type="number" step="0.01" name="edit_unit_cost" class="form-control" value="<?= $row['unit_cost'] ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="submit" name="update_inventory" class="btn btn-warning">Update</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>

                                    <!-- Delete Modal -->
                                    <div class="modal fade" id="deleteInventoryModal<?= $row['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <form method="POST">
                                                <input type="hidden" name="delete_inventory_id" value="<?= $row['id'] ?>">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-danger text-white">
                                                        <h5 class="modal-title">Delete Inventory</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        Are you sure you want to delete <strong><?= htmlspecialchars($row['product_name']) ?></strong>?
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="submit" name="confirm_delete_inventory" class="btn btn-danger">Yes, Delete</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-6" style="width: 50%;">
                    <div class="card shadow-sm small">
                        <div class="card-header bg-danger text-white py-2 px-3">
                            <strong><i class="bi bi-receipt"></i> Liabilities</strong>
                        </div>

                        <!-- Add Liability Form -->
                        <div class="card-body pt-3 pb-2 px-2">
                            <form method="POST" class="row g-1 align-items-end">
                                <div class="col-6">
                                    <input type="text" name="liability_description" class="form-control form-control-sm" placeholder="Description" required>
                                </div>
                                <div class="col-3">
                                    <input type="number" step="0.01" name="liability_amount" class="form-control form-control-sm" placeholder="₱ Amount" required>
                                </div>
                                <div class="col-3">
                                    <input type="date" name="liability_due" class="form-control form-control-sm" required>
                                </div>
                                <div class="col-12 d-grid mt-2">
                                    <button type="submit" name="add_liability" class="btn btn-sm btn-danger">Add</button>
                                </div>
                            </form>
                        </div>

                        <!-- Liabilities Table -->
                        <div class="card-body pt-2 px-2">
                            <table class="table table-sm table-bordered table-hover mb-0">
                                <thead class="table-light small">
                                    <tr>
                                        <th>Description</th>
                                        <th>₱ Amount</th>
                                        <th>Due</th>
                                        <th>Status</th>
                                        <th>Recorded</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $liab = $conn->query("SELECT * FROM liabilities ORDER BY created_at DESC");
                                    while ($row = $liab->fetch_assoc()):
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['description']) ?></td>
                                        <td><?= number_format($row['amount'], 2) ?></td>
                                        <td><?= date('M d', strtotime($row['due_date'])) ?></td>
                                        <td>
                                            <span class="badge <?= $row['status'] === 'Paid' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                                <?= $row['status'] ?>
                                            </span>
                                        </td>
                                        <td><?= date('M d', strtotime($row['created_at'])) ?></td>
                                        <td class="text-nowrap">
                                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editLiabilityModal<?= $row['id'] ?>"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteLiabilityModal<?= $row['id'] ?>"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>

                                    <!-- Edit Liability Modal -->
                                    <div class="modal fade" id="editLiabilityModal<?= $row['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <form method="POST">
                                                <input type="hidden" name="edit_liability_id" value="<?= $row['id'] ?>">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-warning">
                                                        <h5 class="modal-title">Edit Liability</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body row g-2">
                                                        <div class="col-12">
                                                            <label>Description</label>
                                                            <input type="text" name="edit_liability_description" class="form-control" value="<?= htmlspecialchars($row['description']) ?>" required>
                                                        </div>
                                                        <div class="col-6">
                                                            <label>Amount</label>
                                                            <input type="number" step="0.01" name="edit_liability_amount" class="form-control" value="<?= $row['amount'] ?>" required>
                                                        </div>
                                                        <div class="col-6">
                                                            <label>Due Date</label>
                                                            <input type="date" name="edit_liability_due" class="form-control" value="<?= $row['due_date'] ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="submit" name="update_liability" class="btn btn-warning">Update</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>

                                    <!-- Delete Liability Modal -->
                                    <div class="modal fade" id="deleteLiabilityModal<?= $row['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <form method="POST">
                                                <input type="hidden" name="delete_liability_id" value="<?= $row['id'] ?>">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-danger text-white">
                                                        <h5 class="modal-title">Delete Liability</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        Are you sure you want to delete <strong><?= htmlspecialchars($row['description']) ?></strong>?
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="submit" name="confirm_delete_liability" class="btn btn-danger">Yes, Delete</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

    <!-- Bootstrap 5 JS (with Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('collapsed');
    }
    </script>
</body>
</html>

<?php
// Functions

function getTotal($conn, $table, $column, $start, $end) {
    $stmt = $conn->prepare("SELECT SUM($column) FROM $table WHERE date BETWEEN ? AND ?");
    $stmt->bind_param("ss", $start, $end);
    $stmt->execute();
    $total = 0;
    $stmt->bind_result($total);
    $stmt->fetch();
    $stmt->close();
    return $total ?? 0;
}

function generateProfitLoss($conn, $start, $end) {
    $sales = getTotal($conn, "sales", "total_amount", $start, $end);
    $expenses = getTotal($conn, "expenses", "amount", $start, $end);
    $net = $sales - $expenses;

    echo "<h5 class='mb-3'><i class='bi bi-currency-exchange'></i> Profit & Loss Statement</h5>
          <table class='table table-bordered'>
            <tr><th>Revenue (Sales)</th><td>₱" . number_format($sales, 2) . "</td></tr>
            <tr><th>Expenses</th><td>₱" . number_format($expenses, 2) . "</td></tr>
            <tr class='table-success fw-bold'><th>Net Profit</th><td>₱" . number_format($net, 2) . "</td></tr>
          </table>";
}

function generateBalanceSheet($conn) {
    // Assets: sum of inventory values
    $res1 = $conn->query("SELECT SUM(quantity * unit_cost) AS assets FROM inventory");
    $assets = $res1->fetch_assoc()['assets'] ?? 0;

    // Liabilities: sum of unpaid liabilities
    $res2 = $conn->query("SELECT SUM(amount) AS liabilities FROM liabilities WHERE status = 'Unpaid'");
    $liabilities = $res2->fetch_assoc()['liabilities'] ?? 0;

    // Equity: Assets - Liabilities
    $equity = $assets - $liabilities;

    echo "<h5 class='mb-3'><i class='bi bi-building'></i> Balance Sheet</h5>
          <table class='table table-bordered'>
            <tr><th>Assets</th><td>₱" . number_format($assets, 2) . "</td></tr>
            <tr><th>Liabilities</th><td>₱" . number_format($liabilities, 2) . "</td></tr>
            <tr class='table-warning fw-bold'><th>Equity</th><td>₱" . number_format($equity, 2) . "</td></tr>
          </table>";
}


function generateCashFlow($conn, $start, $end) {
    $cash_in = getTotal($conn, "sales", "total_amount", $start, $end);
    $cash_out = getTotal($conn, "expenses", "amount", $start, $end);
    $net = $cash_in - $cash_out;

    echo "<h5 class='mb-3'><i class='bi bi-cash-coin'></i> Cash Flow Statement</h5>
          <table class='table table-bordered'>
            <tr><th>Cash Inflows</th><td>₱" . number_format($cash_in, 2) . "</td></tr>
            <tr><th>Cash Outflows</th><td>₱" . number_format($cash_out, 2) . "</td></tr>
            <tr class='table-info fw-bold'><th>Net Cash Flow</th><td>₱" . number_format($net, 2) . "</td></tr>
          </table>";
}
?>
