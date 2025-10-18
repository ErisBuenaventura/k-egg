<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}

$pdo = new PDO('mysql:host=localhost;dbname=capstone', 'root', '');

// Fetch all staff
$stmt = $pdo->query("SELECT id, name, email, created_at FROM staff ORDER BY created_at DESC");
$staffList = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                    <i class="bi bi-people me-2 text-secondary fs-5"></i>Manage Staff
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

        <!-- Page Content -->
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4>Registered Staff</h4>
                <button onclick="window.print()" class="btn btn-primary no-print">
                    <i class="bi bi-printer"></i> Print
                </button>
            </div>

            <div class="table-container table-responsive">
                <table class="table table-striped table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Date Registered</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($staffList): ?>
                            <?php foreach ($staffList as $index => $staff): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($staff['name']) ?></td>
                                    <td><?= htmlspecialchars($staff['email']) ?></td>
                                    <td><?= htmlspecialchars($staff['created_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center">No staff registered yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('collapsed');
    }
</script>
</body>
</html>
