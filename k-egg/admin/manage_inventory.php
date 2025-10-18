<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "capstone");

// Add inventory
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $product = $_POST['product'];
    $quantity = $_POST['quantity'];
    $unit_cost = $_POST['unit_cost'];

    $stmt = $conn->prepare("INSERT INTO inventory (product_name, quantity, unit_cost) VALUES (?, ?, ?)");
    $stmt->bind_param("sid", $product, $quantity, $unit_cost);
    $stmt->execute();
    $stmt->close();
}

// Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM inventory WHERE id = $id");
}

// Fetch all
$result = $conn->query("SELECT * FROM inventory ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-4">
    <h3 class="mb-4">📦 Inventory Management</h3>

    <form method="POST" class="row g-2 mb-4">
        <div class="col-md-4">
            <input type="text" name="product" class="form-control" placeholder="Product Name" required>
        </div>
        <div class="col-md-3">
            <input type="number" name="quantity" class="form-control" placeholder="Quantity" required>
        </div>
        <div class="col-md-3">
            <input type="number" step="0.01" name="unit_cost" class="form-control" placeholder="Unit Cost" required>
        </div>
        <div class="col-md-2">
            <button type="submit" name="add" class="btn btn-success w-100">Add</button>
        </div>
    </form>

    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>Product</th>
                <th>Qty</th>
                <th>Unit Cost</th>
                <th>Total Value</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['product_name']) ?></td>
                    <td><?= $row['quantity'] ?></td>
                    <td>₱<?= number_format($row['unit_cost'], 2) ?></td>
                    <td>₱<?= number_format($row['quantity'] * $row['unit_cost'], 2) ?></td>
                    <td><a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger">🗑 Delete</a></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>
