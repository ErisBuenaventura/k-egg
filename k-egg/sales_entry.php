<?php
session_start();
if (!isset($_SESSION['staff'])) {
    header('Location: staff.php');
    exit();
}

$staffName = $_SESSION['staff'];
$branch = "Las Piñas";
$transactionId = uniqid('TXN-');

// Connect to DB
$conn = new mysqli("localhost", "root", "", "capstone");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $transaction_id = $_POST['transaction_id'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $product_name = $_POST['product_name'];
    $quantity = (int) $_POST['quantity'];
    $unit_price = (float) $_POST['unit_price'];
    $total_amount = $quantity * $unit_price;
    $payment_method = $_POST['payment_method'];
    $branch = $_POST['branch'];
    $sold_by = $_POST['sold_by'];
    $remarks = $_POST['remarks'];

    // Insert into sales
    $stmt = $conn->prepare("INSERT INTO sales (transaction_id, date, time, product_name, quantity, unit_price, total_amount, payment_method, branch, sold_by, remarks)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssiddssss", $transaction_id, $date, $time, $product_name, $quantity, $unit_price, $total_amount, $payment_method, $branch, $sold_by, $remarks);

    if ($stmt->execute()) {
        $sale_id = $stmt->insert_id;

        // Prepare data for ledger & journal
        $transaction_type = "Sales";
        $debit_account = ($payment_method === 'Cash') ? 'Cash' : 'Accounts Receivable';
        $credit_account = 'Sales Revenue';
        $description = "Sale of $product_name ($quantity units @ ₱$unit_price)";
        $amount = $total_amount;
        $desc = $description;
        $zero = 0.0;

        // Insert into ledger_entries
        $ledger_stmt = $conn->prepare("INSERT INTO ledger_entries 
            (transaction_type, transaction_id, date, account_debit, account_credit, amount, description) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $ledger_stmt->bind_param("sisssds", $transaction_type, $sale_id, $date, $debit_account, $credit_account, $amount, $desc);
        $ledger_success = $ledger_stmt->execute();
        $ledger_stmt->close();

        // Insert journal entry (Debit side)
        $journal_stmt1 = $conn->prepare("INSERT INTO journal_entries 
            (date, reference_no, account_name, description, debit, credit) 
            VALUES (?, ?, ?, ?, ?, ?)");
        $journal_stmt1->bind_param("ssssdd", $date, $transaction_id, $debit_account, $desc, $amount, $zero);
        $journal_stmt1->execute();
        $journal_stmt1->close();

        // Insert journal entry (Credit side)
        $journal_stmt2 = $conn->prepare("INSERT INTO journal_entries 
            (date, reference_no, account_name, description, debit, credit) 
            VALUES (?, ?, ?, ?, ?, ?)");
        $journal_stmt2->bind_param("ssssdd", $date, $transaction_id, $credit_account, $desc, $zero, $amount);
        $journal_stmt2->execute();
        $journal_stmt2->close();

        echo "<script>alert('Sales, ledger, and journal entries saved successfully!'); window.location.href='sales_entry.php';</script>";
        exit();
    } else {
        echo "<script>alert('Error saving sales record: " . $stmt->error . "'); history.back();</script>";
        exit();
    }

    $stmt->close();
}

// Fetch latest 20 sales
$sales = $conn->query("SELECT * FROM sales ORDER BY date DESC, time DESC LIMIT 20");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sales Entry & Records</title>
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

            <div class="container-fluid row g-3 mb-4">
                <!-- Form -->
                <form method="POST" class="row no-print sales-form">
                    <h4 class="mt-2">Sales Entry</h4>
                    <div class="col-md-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="date" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Time</label>
                        <input type="time" name="time" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Product Name</label>
                        <input type="text" name="product_name" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Quantity Sold</label>
                        <input type="number" name="quantity" id="quantity" class="form-control" required min="1">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Unit Price</label>
                        <input type="number" name="unit_price" id="unit_price" class="form-control" required step="0.01">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Total Amount</label>
                        <input type="text" name="total_amount" id="total_amount" class="form-control" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-select" required>
                            <option value="">Select Method</option>
                            <option>Cash</option>
                            <option>GCash</option>
                            <option>Bank Transfer</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Branch</label>
                        <input type="text" name="branch" class="form-control" value="<?= htmlspecialchars($branch) ?>" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Record By</label>
                        <input type="text" name="sold_by" class="form-control" value="<?= htmlspecialchars($staffName) ?>" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Transaction ID</label>
                        <input type="text" name="transaction_id" class="form-control" value="<?= $transactionId ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Remarks / Notes</label>
                        <textarea name="remarks" class="form-control" rows="3" placeholder="Optional..."></textarea>
                    </div>
                    <div class="col-12 mt-2">
                        <button type="submit" class="btn btn-success">Save Sale</button>
                    </div>
                </form>

                <!-- Sales Records -->
                <div class="sales-table">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4>Recent Sales Records</h4>
                        <button onclick="printSalesReport()" class="btn btn-primary no-print">
                            <i class="bi bi-printer"></i> Print Report
                        </button>
                    </div>
                    <div id="printable-area">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-sm align-middle text-center">
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
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const quantity = document.getElementById('quantity');
    const unitPrice = document.getElementById('unit_price');
    const totalAmount = document.getElementById('total_amount');

    function updateTotal() {
        const qty = parseFloat(quantity.value) || 0;
        const price = parseFloat(unitPrice.value) || 0;
        totalAmount.value = (qty * price).toFixed(2);
    }
    quantity.addEventListener('input', updateTotal);
    unitPrice.addEventListener('input', updateTotal);

    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('collapsed');
    }

    function printSalesReport() {
        const tableHTML = document.getElementById('printable-area').innerHTML;
        const style = `
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                table { width: 100%; border-collapse: collapse; word-wrap: break-word; }
                th, td { border: 1px solid #333; padding: 8px; text-align: center; }
                th { background-color: #f2f2f2; }
                h2 { text-align: center; margin-bottom: 20px; }
            </style>
        `;
        const printWindow = window.open('', '', 'height=800,width=1000');
        printWindow.document.write(`<html><head><title>Sales Report</title>${style}</head><body>`);
        printWindow.document.write('<h2>Sales Report - K-Egg Business</h2>');
        printWindow.document.write(tableHTML);
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
        printWindow.close();
    }
</script>
</body>
</html>
<?php $conn->close(); ?>
