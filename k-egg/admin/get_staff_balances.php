<?php
if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit();
}

include '../db.php';

// Modified query to fetch all staff balances (across all dates)
$query = "
SELECT 
    s.sold_by,
    s.date,
    SUM(s.total_amount) AS total_sales,
    COALESCE(cd.total_declared, 0) AS declared,
    (SUM(s.total_amount) - COALESCE(cd.total_declared, 0)) AS balance
FROM sales s
LEFT JOIN (
    SELECT staff_name, declaration_date, SUM(declared_amount) AS total_declared
    FROM cash_declaration
    GROUP BY staff_name, declaration_date
) cd ON cd.staff_name = s.sold_by AND cd.declaration_date = s.date
GROUP BY s.sold_by, s.date
ORDER BY s.date DESC, s.sold_by ASC
";

$result = $conn->query($query);

$staffBalances = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $staffBalances[] = $row;
    }
}
?>
