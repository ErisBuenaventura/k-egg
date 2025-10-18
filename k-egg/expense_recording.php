<?php
session_start();
if (!isset($_SESSION['staff'])) {
    header('Location: staff.php');
    exit();
}

$staffName = $_SESSION['staff'];
$branch = "Las Piñas";
$transactionId = uniqid('EXP-');

$conn = new mysqli("localhost", "root", "", "capstone");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $transaction_id = uniqid('EXP-');
    $date = $_POST['date'];
    $time = $_POST['time'];
    $category = $_POST['category'];
    $amount = (float) $_POST['amount'];
    $payment_method = $_POST['payment_method'];
    $vendor = $_POST['vendor'];
    $remarks = $_POST['remarks'];

    // Auto-generate the description
    $description = "Expense for $category paid to $vendor via $payment_method (₱" . number_format($amount, 2) . ")";

    // Handle file upload if provided
    if (!empty($_FILES['attachment']['name'])) {
        $upload_dir = "uploads/";
        if (!is_dir($upload_dir)) mkdir($upload_dir);
        $attachment_path = $upload_dir . basename($_FILES['attachment']['name']);
        move_uploaded_file($_FILES['attachment']['tmp_name'], $attachment_path);
    }

    // Insert into expenses
    $stmt = $conn->prepare("INSERT INTO expenses (transaction_id, date, time, category, description, amount, payment_method, vendor, branch, recorded_by, attachment, remarks)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssdssssss", $transaction_id, $date, $time, $category, $description, $amount, $payment_method, $vendor, $branch, $staffName, $attachment_path, $remarks);
    $stmt->execute();
    $expense_id = $stmt->insert_id;
    $stmt->close();

    // For ledger & journal
    $debit_account = $category;
    $credit_account = ($payment_method === 'Cash') ? 'Cash' : 'Accounts Payable';
    $desc = $description;
    $zero = 0.00;

    // Ledger entry
    $ledger_stmt = $conn->prepare("INSERT INTO ledger_entries (transaction_type, transaction_id, date, account_debit, account_credit, amount, description)
                                   VALUES ('Expense', ?, ?, ?, ?, ?, ?)");
    $ledger_stmt->bind_param("isssds", $expense_id, $date, $debit_account, $credit_account, $amount, $desc);
    $ledger_stmt->execute();
    $ledger_stmt->close();

    // Journal - Debit
    $journal_stmt1 = $conn->prepare("INSERT INTO journal_entries (date, reference_no, account_name, description, debit, credit)
                                     VALUES (?, ?, ?, ?, ?, ?)");
    $journal_stmt1->bind_param("ssssdd", $date, $transaction_id, $debit_account, $desc, $amount, $zero);
    $journal_stmt1->execute();
    $journal_stmt1->close();

    // Journal - Credit
    $journal_stmt2 = $conn->prepare("INSERT INTO journal_entries (date, reference_no, account_name, description, debit, credit)
                                     VALUES (?, ?, ?, ?, ?, ?)");
    $journal_stmt2->bind_param("ssssdd", $date, $transaction_id, $credit_account, $desc, $zero, $amount);
    $journal_stmt2->execute();
    $journal_stmt2->close();

    echo "<script>alert('Expense saved successfully!'); window.location.href='expense_recording.php';</script>";
    exit();
}

// ✅ Fetch recent expenses
$result = $conn->query("SELECT * FROM expenses ORDER BY created_at DESC LIMIT 20");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Expense Recording</title>
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
                <!-- Expense Form -->
                <form method="POST" enctype="multipart/form-data" class="row expense-form no-print">
                    <h4 class="mt-2">Expense Recording</h4>
                    <div class="col-md-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="date" class="form-control" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Time</label>
                        <input type="time" name="time" class="form-control" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Expense Category</label>
                        <select name="category" class="form-select" required>
                            <option value="">Select Category</option>
                            <option>Utilities</option>
                            <option>Rent</option>
                            <option>Supplies</option>
                            <option>Delivery</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Amount</label>
                        <input type="number" name="amount" class="form-control" required step="0.01">
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
                        <label class="form-label">Vendor / Supplier (Optional)</label>
                        <input type="text" name="vendor" class="form-control">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Attachment (Optional)</label>
                        <input type="file" name="attachment" class="form-control">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Remarks / Notes</label>
                        <input type="text" name="remarks" class="form-control">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Transaction ID</label>
                        <input type="text" name="transaction_id" class="form-control" value="<?= $transactionId ?>" readonly>
                    </div>

                    <div class="col-12 mt-2">
                        <button type="submit" class="btn btn-success">Save Expense</button>
                    </div>
                </form>

                <!-- Expense Table -->
                <div class="expense-table">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3>Recent Expense Records</h3>
                        <button onclick="printExpenses()" class="btn btn-primary no-print">
                            <i class="bi bi-printer"></i> Print Expenses
                        </button>
                    </div>
                    <div id="printable-area" class="table-responsive">
                        <table class="table table-bordered table-striped align-middle">
                            <thead class="table-dark text-center">
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                    <th>Payment</th>
                                    <th>Vendor</th>
                                    <th>Branch</th>
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
                                            <td><?= htmlspecialchars($row['branch']) ?></td>
                                            <td><?= htmlspecialchars($row['recorded_by']) ?></td>
                                            <td>
                                                <?php if (!empty($row['attachment'])): ?>
                                                    <img src="<?= htmlspecialchars($row['attachment']) ?>" 
                                                        alt="Attachment" 
                                                        class="clickable-image" 
                                                        style="max-width: 100px; max-height: 100px; cursor: pointer;">
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                            <td><?= nl2br(htmlspecialchars($row['remarks'])) ?></td>
                                            <td><?= htmlspecialchars($row['transaction_id']) ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="12" class="text-center">No expenses recorded yet.</td></tr>
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

    function printExpenses() {
        const tableHTML = document.getElementById('printable-area').innerHTML;
        const style = `
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                table { width: 100%; border-collapse: collapse; table-layout: auto; word-wrap: break-word; }
                th, td { border: 1px solid #333; padding: 8px; text-align: center; word-break: break-word; }
                th { background-color: #f2f2f2; }
                h2 { text-align: center; margin-bottom: 20px; }
            </style>
        `;

        const win = window.open('', '', 'height=800,width=1000');
        win.document.write(`<html><head><title>Expense Report</title>${style}</head><body>`);
        win.document.write('<h2>Expense Report - K-Egg Business</h2>');
        win.document.write(tableHTML);
        win.document.write('</body></html>');
        win.document.close();
        win.focus();
        win.print();
        win.close();
    }
</script>

</body>
</html>
