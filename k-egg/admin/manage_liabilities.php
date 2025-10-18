<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "capstone");

// Add liability
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $desc = $_POST['description'];
    $amount = $_POST['amount'];
    $due = $_POST['due_date'];
    $stmt = $conn->prepare("INSERT INTO liabilities (description, amount, due_date) VALUES (?, ?, ?)");
    $stmt->bind_param("sds", $desc, $amount, $due);
    $stmt->execute();
    $stmt->close();
}

// Mark as paid
if (isset($_GET['pay'])) {
    $id = $_GET['pay'];
    $conn->query("UPDATE liabilities SET status = 'Paid' WHERE id = $id");
}

// Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM liabilities WHERE id = $id");
}

// Fetch
$result = $conn->query("SELECT * FROM liabilities ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Liabilities</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-4">
    <h3 class="mb-4">📄 Liabilities Management</h3>

    <form method="POST" class="row g-2 mb-4">
        <div class="col-md-4">
            <input type="text" name="description" class="form-control" placeholder="Description" required>
        </div>
        <div class="col-md-3">
            <input type="number" step="0.01" name="amount" class="form-control" placeholder="Amount" required>
        </div>
        <div class="col-md-3">
            <input type="date" name="due_date" class="form-control" required>
        </div>
        <div class="col-md-2">
            <button type="submit" name="add" class="btn btn-primary w-100">Add</button>
        </div>
    </form>

    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>Description</th>
                <th>Amount</th>
                <th>Due Date</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['description']) ?></td>
                    <td>₱<?= number_format($row['amount'], 2) ?></td>
                    <td><?= $row['due_date'] ?></td>
                    <td><?= $row['status'] ?></td>
                    <td>
                        <?php if ($row['status'] == 'Unpaid'): ?>
                            <a href="?pay=<?= $row['id'] ?>" class="btn btn-sm btn-success">✅ Mark as Paid</a>
                        <?php endif; ?>
                        <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger">🗑 Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>
<?php