<?php
$conn = new mysqli("localhost", "root", "", "capstone");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Count ledger entries
$ledgerCountResult = $conn->query("SELECT COUNT(*) AS total_ledger FROM ledger_entries");
$ledgerCount = $ledgerCountResult->fetch_assoc()['total_ledger'] ?? 0;

// Count journal entries
$journalCountResult = $conn->query("SELECT COUNT(*) AS total_journal FROM journal_entries");
$journalCount = $journalCountResult->fetch_assoc()['total_journal'] ?? 0;

return ['ledger' => $ledgerCount, 'journal' => $journalCount];
