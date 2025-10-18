<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}
$conn = new mysqli("localhost", "root", "", "capstone");

$ledger_result = $conn->query("SELECT * FROM ledger_entries ORDER BY date DESC");
$journal_result = $conn->query("SELECT * FROM journal_entries ORDER BY date DESC");

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Ledger and Journal - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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

        <div class="main-content d-flex flex-column" style="height: 100vh; overflow: hidden;">
            <!-- Navbar -->
            <nav class="navbar navbar-expand-lg bg-white shadow-sm rounded mb-3 py-3 px-4 sticky-top w-100 border-bottom">
                <div class="container-fluid d-flex justify-content-between align-items-center">

                    <!-- left: Page Title -->
                    <h4 class="mb-0 fw-bold text-dark d-none d-md-flex align-items-center">
                        <i class="bi bi-journal-bookmark me-2 text-secondary fs-5"></i>Ledger and Journal
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
            
            <div class="container-fluid h-100">
                <!-- LEDGER ENTRIES DISPLAY -->
                <div class="card shadow-sm flex-grow-1 overflow-auto" style="height: 41%">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-book"></i> Ledger Entries</h5>
                        <span class="badge bg-light text-dark">Auto-generated</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover table-striped align-middle mb-0">
                                <thead class="table-primary text-center">
                                    <tr>
                                        <th>Date</th>
                                        <th>Account (Debit)</th>
                                        <th>Account (Credit)</th>
                                        <th>Amount (₱)</th>
                                        <th>Description</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody class="text-center">
                                    <?php if ($ledger_result->num_rows > 0): ?>
                                        <?php while($row = $ledger_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= date('Y-m-d', strtotime($row['date'])) ?></td>
                                                <td class="text-success fw-semibold"><?= htmlspecialchars($row['account_debit']) ?></td>
                                                <td class="text-danger fw-semibold"><?= htmlspecialchars($row['account_credit']) ?></td>
                                                <td class="fw-bold">₱<?= number_format($row['amount'], 2) ?></td>
                                                <td class="text-start"><?= nl2br(htmlspecialchars($row['description'])) ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-warning"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#editLedgerModal"
                                                            onclick='loadLedgerData(<?= json_encode($row) ?>)'>
                                                        Edit
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="6" class="text-muted text-center">No ledger entries found.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- JOURNAL ENTRIES DISPLAY -->
                <div class="card shadow-sm flex-grow-1 overflow-auto" style="height: 41%">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-journal-text"></i> Journal Entries</h5>
                        <span class="badge bg-light text-dark">Double-entry</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover table-striped align-middle mb-0">
                                <thead class="table-success text-center">
                                    <tr>
                                        <th>Date</th>
                                        <th>Reference No</th>
                                        <th>Account Name</th>
                                        <th>Description</th>
                                        <th>Debit (₱)</th>
                                        <th>Credit (₱)</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody class="text-center">
                                    <?php if ($journal_result->num_rows > 0): ?>
                                        <?php while($row = $journal_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= date('Y-m-d', strtotime($row['date'])) ?></td>
                                                <td><?= htmlspecialchars($row['reference_no']) ?></td>
                                                <td><?= htmlspecialchars($row['account_name']) ?></td>
                                                <td class="text-start"><?= nl2br(htmlspecialchars($row['description'])) ?></td>
                                                <td class="text-success fw-semibold">
                                                    <?= $row['debit'] != 0 ? '₱' . number_format($row['debit'], 2) : '' ?>
                                                </td>
                                                <td class="text-danger fw-semibold">
                                                    <?= $row['credit'] != 0 ? '₱' . number_format($row['credit'], 2) : '' ?>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-warning"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#editJournalModal"
                                                            onclick='loadJournalData(<?= json_encode($row) ?>)'>
                                                        Edit
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="7" class="text-muted text-center">No journal entries available.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- LEDGER EDIT MODAL -->
            <div class="modal fade" id="editLedgerModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <form method="POST" action="update_ledger.php" class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Edit Ledger Entry</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body row g-3">
                        <input type="hidden" name="id" id="ledgerId">
                        <div class="col-md-6">
                        <label>Date</label>
                        <input type="date" name="date" id="ledgerDate" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                        <label>Amount</label>
                        <input type="number" step="0.01" name="amount" id="ledgerAmount" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                        <label>Account (Debit)</label>
                        <input type="text" name="account_debit" id="ledgerDebit" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                        <label>Account (Credit)</label>
                        <input type="text" name="account_credit" id="ledgerCredit" class="form-control" required>
                        </div>
                        <div class="col-12">
                        <label>Description</label>
                        <textarea name="description" id="ledgerDescription" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-success">Save Changes</button>
                        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                    </form>
                </div>
            </div>

            <!-- JOURNAL EDIT MODAL -->
            <div class="modal fade" id="editJournalModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <form method="POST" action="update_journal.php" class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">Edit Journal Entry</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body row g-3">
                        <input type="hidden" name="id" id="journalId">
                        <div class="col-md-6">
                        <label>Date</label>
                        <input type="date" name="date" id="journalDate" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                        <label>Reference No</label>
                        <input type="text" name="reference_no" id="journalRef" class="form-control" readonly>
                        </div>
                        <div class="col-md-6">
                        <label>Account Name</label>
                        <input type="text" name="account_name" id="journalAccount" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                        <label>Debit</label>
                        <input type="number" step="0.01" name="debit" id="journalDebit" class="form-control">
                        </div>
                        <div class="col-md-6">
                        <label>Credit</label>
                        <input type="number" step="0.01" name="credit" id="journalCredit" class="form-control">
                        </div>
                        <div class="col-12">
                        <label>Description</label>
                        <textarea name="description" id="journalDescription" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-success">Save Changes</button>
                        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('collapsed');
        }
    </script>

    <!-- SCRIPT TO LOAD MODAL DATA -->
    <script>
    function loadLedgerData(data) {
        document.getElementById('ledgerId').value = data.id;
        document.getElementById('ledgerDate').value = data.date;
        document.getElementById('ledgerAmount').value = data.amount;
        document.getElementById('ledgerDebit').value = data.account_debit;
        document.getElementById('ledgerCredit').value = data.account_credit;
        document.getElementById('ledgerDescription').value = data.description;
    }

    function loadJournalData(data) {
        document.getElementById('journalId').value = data.id;
        document.getElementById('journalDate').value = data.date;
        document.getElementById('journalRef').value = data.reference_no;
        document.getElementById('journalAccount').value = data.account_name;
        document.getElementById('journalDebit').value = data.debit;
        document.getElementById('journalCredit').value = data.credit;
        document.getElementById('journalDescription').value = data.description;
    }
    </script>

</body>
</html>
