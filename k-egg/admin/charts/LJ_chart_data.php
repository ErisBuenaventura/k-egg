<?php
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "capstone");
if ($conn->connect_error) {
    echo json_encode(["error" => "Connection failed"]);
    exit;
}

$debit = 0;
$credit = 0;

$sql = "SELECT type, amount FROM ledger_entries";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    if (strtolower($row['type']) === 'debit') {
        $debit += (float)$row['amount'];
    } elseif (strtolower($row['type']) === 'credit') {
        $credit += (float)$row['amount'];
    }
}

echo json_encode([
    'labels' => ['Debit', 'Credit'],
    'data' => [$debit, $credit]
]);
?>
