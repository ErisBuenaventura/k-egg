<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit();
}

include '../db.php';

$selectedDate = $_GET['date'] ?? date('Y-m-d');

// Query using sold_by
$query = "
SELECT 
    s.sold_by,
    SUM(s.total_amount) AS total_sales,
    COALESCE(cd.total_declared, 0) AS declared,
    (SUM(s.total_amount) - COALESCE(cd.total_declared, 0)) AS balance
FROM sales s
LEFT JOIN (
    SELECT staff_name, SUM(declared_amount) AS total_declared
    FROM cash_declaration
    WHERE declaration_date = ?
    GROUP BY staff_name
) cd ON cd.staff_name = s.sold_by
WHERE s.date = ?
GROUP BY s.sold_by
ORDER BY s.sold_by ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $selectedDate, $selectedDate);
$stmt->execute();
$result = $stmt->get_result();
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
                    <i class="bi bi-wallet2 me-2 text-secondary fs-5"></i>Staff Balances
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

          <!-- Date Filter -->
          <div class="container">
            <form method="get" class="row g-3 align-items-end">
              <div class="col-auto">
                <label for="date" class="form-label">Select Date:</label>
                <input type="date" id="date" name="date" class="form-control" value="<?php echo htmlspecialchars($selectedDate); ?>" required>
              </div>
              <div class="col-auto">
                <button type="submit" class="btn btn-primary">
                  <i class="bi bi-eye"></i> View
                </button>
              </div>
            </form>
          </div>
          
          <!-- Table -->
          <div class="table-responsive mt-2">
            <table class="table table-bordered table-sm align-middle">
              <thead class="table-light">
                <tr>
                  <th>Staff</th>
                  <th>Total Sales</th>
                  <th>Declared</th>
                  <th>Balance</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
                <tbody>
                  <?php
                  if ($result->num_rows > 0):
                    while ($row = $result->fetch_assoc()):
                      $balance = $row['balance'];
                      $status = '✅ OK';
                      $statusClass = 'text-success';

                      if ($balance > 0) {
                          $status = '⚠️ Short';
                          $statusClass = 'text-danger';
                      } elseif ($balance < 0) {
                          $status = '🔼 Over';
                          $statusClass = 'text-warning';
                      }
                  ?>
                  <tr>
                    <td><?= htmlspecialchars($row['sold_by']) ?></td>
                    <td>₱<?= number_format($row['total_sales'], 2) ?></td>
                    <td>₱<?= number_format($row['declared'], 2) ?></td>
                    <td>₱<?= number_format($balance, 2) ?></td>
                    <td class="<?= $statusClass ?>"><?= $status ?></td>
                    <td>
                      <?php if ($row['declared'] >= 0): ?>
                        <button 
                          class="btn btn-sm btn-warning"
                          data-bs-toggle="modal"
                          data-bs-target="#editModal"
                          onclick="fillModal('<?= htmlspecialchars($row['sold_by']) ?>', <?= $row['declared'] ?>, '<?= $selectedDate ?>')"
                        >Edit</button>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <?php endwhile; else: ?>
                  <tr>
                    <td colspan="6" class="text-center text-muted">No records for <?= $selectedDate ?>.</td>
                  </tr>
                  <?php endif; ?>
                </tbody>
              </thead>
            </table>
          </div>
        </div>

        <!-- 📝 Edit Modal -->
        <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
          <div class="modal-dialog">
            <form method="POST" action="update_declaration.php" class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Edit Declaration</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>

              <div class="modal-body">
                <input type="hidden" name="staff_name" id="modal_staff_name">
                <input type="hidden" name="declaration_date" id="modal_date">

                <div class="mb-3">
                  <label for="modal_amount" class="form-label">Declared Amount</label>
                  <input type="number" step="0.01" name="declared_amount" id="modal_amount" class="form-control" required>
                </div>

                <div class="mb-3">
                  <label for="modal_remarks" class="form-label">Remarks</label>
                  <textarea name="remarks" id="modal_remarks" class="form-control" rows="2"></textarea>
                </div>
              </div>

              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function fillModal(staff, amount, date) {
      document.getElementById('modal_staff_name').value = staff;
      document.getElementById('modal_amount').value = amount;
      document.getElementById('modal_date').value = date;
      document.getElementById('modal_remarks').value = '';
    }
    </script>

    <script>
        function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('collapsed');
    }
    </script>

</body>
</html>
