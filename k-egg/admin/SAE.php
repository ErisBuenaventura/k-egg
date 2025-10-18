<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}

// Generate Transaction ID for expenses
$transactionId = uniqid('EXP-');

// Database connection
$conn = new mysqli("localhost", "root", "", "capstone");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle Sales Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_sale'])) {
    $date = $_POST['date'];
    $time = $_POST['time'];
    $product_name = $_POST['product_name'];
    $quantity = $_POST['quantity'];
    $unit_price = $_POST['unit_price'];
    $total_amount = $quantity * $unit_price;
    $payment_method = $_POST['payment_method'];
    $branch = $_POST['branch'];
    $sold_by = $_POST['sold_by'];
    $remarks = $_POST['remarks'];

    $stmt = $conn->prepare("INSERT INTO sales (date, time, product_name, quantity, unit_price, total_amount, payment_method, branch, sold_by, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssiddssss", $date, $time, $product_name, $quantity, $unit_price, $total_amount, $payment_method, $branch, $sold_by, $remarks);
    $stmt->execute();
    $stmt->close();
}

// Fetch recent sales and expenses
$sales = $conn->query("SELECT * FROM sales ORDER BY date DESC, time DESC LIMIT 20");
$result = $conn->query("SELECT * FROM expenses ORDER BY created_at DESC LIMIT 20");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sales and Expense Recording</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
                <i class="bi bi-box-arrow-right"></i>
                <span class="logout-text">Logout</span>
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
                    <i class="bi bi-file-earmark-text me-2 text-secondary fs-5"></i>Sales and Expense Recording
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

        <div class="container-fluid content-area flex-grow-1 px-4 mb-4">
            <div class="gap-4">
                <!-- LEFT: Sales Recording -->
                <div class>
                    <h4 class="mb-3">Sales Entry</h4>
                        <!-- SALES TABLE -->    
                        <div class="table-responsive">
                            <table class="table table-sm table-hover table-striped align-middle mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Transaction ID</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Product</th>
                                        <th>Qty</th>
                                        <th>Unit Price</th>
                                        <th>Total</th>
                                        <th>Payment</th>
                                        <th>Branch</th>
                                        <th>Sold By</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($sales->num_rows > 0): ?>
                                        <?php while ($row = $sales->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['transaction_id']) ?></td>
                                                <td><?= htmlspecialchars($row['date']) ?></td>
                                                <td><?= htmlspecialchars($row['time']) ?></td>
                                                <td><?= htmlspecialchars($row['product_name']) ?></td>
                                                <td><?= $row['quantity'] ?></td>
                                                <td>₱<?= number_format($row['unit_price'], 2) ?></td>
                                                <td>₱<?= number_format($row['total_amount'], 2) ?></td>
                                                <td><?= htmlspecialchars($row['payment_method']) ?></td>
                                                <td><?= htmlspecialchars($row['branch']) ?></td>
                                                <td><?= htmlspecialchars($row['sold_by']) ?></td>
                                                <td><?= nl2br(htmlspecialchars($row['remarks'])) ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="11">No sales recorded yet.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- Expense Recording Form & Table -->
                    <div class="mt-2">
                        <h4>Expense Recording</h4>

                        <!-- Expense Table -->
                        <div class="table-responsive">
                            <table class="table table-sm table-hover table-striped align-middle mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Category</th>
                                        <th>Description</th>
                                        <th>Amount</th>
                                        <th>Payment</th>
                                        <th>Vendor</th>
                                        <th>Recorded By</th>
                                        <th>Attachment</th>
                                        <th>Remarks</th>
                                        <th>Transaction ID</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result->num_rows > 0): ?>
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['date']) ?></td>
                                                <td><?= htmlspecialchars($row['time']) ?></td>
                                                <td><?= htmlspecialchars($row['category']) ?></td>
                                                <td><?= htmlspecialchars($row['description']) ?></td>
                                                <td>₱<?= number_format($row['amount'], 2) ?></td>
                                                <td><?= htmlspecialchars($row['payment_method']) ?></td>
                                                <td><?= htmlspecialchars($row['vendor']) ?></td>
                                                <td><?= htmlspecialchars($row['recorded_by']) ?></td>
                                                <td>
                                                    <?php if (!empty($row['attachment'])): ?>
                                                        <img src="<?= htmlspecialchars($row['attachment']) ?>" alt="Attachment" style="max-width: 50px; max-height: 50px;">
                                                    <?php else: ?>
                                                        N/A
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= nl2br(htmlspecialchars($row['remarks'])) ?></td>
                                                <td><?= htmlspecialchars($row['transaction_id']) ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="11">No expenses recorded yet.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
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

    const qtyInput = document.querySelector('input[name="quantity"]');
    const priceInput = document.querySelector('input[name="unit_price"]');
    const totalInput = document.getElementById('total_amount');

    function calculateTotal() {
        const qty = parseFloat(qtyInput.value) || 0;
        const price = parseFloat(priceInput.value) || 0;
        const total = qty * price;
        totalInput.value = total.toFixed(2);
    }

    qtyInput.addEventListener('input', calculateTotal);
    priceInput.addEventListener('input', calculateTotal);
</script>
</body>
</html>
