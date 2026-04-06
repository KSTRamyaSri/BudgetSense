<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once 'db.php';

$user_id = (int)$_SESSION['user_id'];
$month = isset($_GET['month']) ? sanitize($conn, $_GET['month']) : date('Y-m');

$expenses = $conn->query("SELECT title, category, amount, date, note FROM expenses WHERE user_id=$user_id AND DATE_FORMAT(date,'%Y-%m')='$month' ORDER BY date DESC");
$income = $conn->query("SELECT source as title, 'Income' as category, amount, date, note FROM income WHERE user_id=$user_id AND DATE_FORMAT(date,'%Y-%m')='$month' ORDER BY date DESC");

$filename = "budget_export_$month.csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Pragma: no-cache');

$out = fopen('php://output', 'w');
fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

fputcsv($out, ['Type','Title/Source','Category','Amount (₹)','Date','Note']);

while($row = $income->fetch_assoc()) {
    fputcsv($out, ['Income', $row['title'], $row['category'], $row['amount'], $row['date'], $row['note']]);
}
while($row = $expenses->fetch_assoc()) {
    fputcsv($out, ['Expense', $row['title'], $row['category'], $row['amount'], $row['date'], $row['note']]);
}

fclose($out);
exit;
