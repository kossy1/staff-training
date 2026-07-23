<?php
// export-cost-report.php - Export Cost Report in Naira
require_once 'includes/config.php';
require_once 'includes/session.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: login.php');
    exit();
}

// Get cost data
$cost_data = $conn->query("
    SELECT 
        id, title, type, cost, status,
        DATE_FORMAT(start_date, '%Y-%m') as month,
        current_participants
    FROM training_programs
    WHERE cost > 0
    ORDER BY cost DESC
");

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="training_cost_report_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Training ID', 'Title', 'Type', 'Cost (₦)', 'Status', 'Month', 'Participants']);

while ($row = $cost_data->fetch_assoc()) {
    fputcsv($output, [
        $row['id'],
        $row['title'],
        $row['type'],
        number_format($row['cost'], 2),
        $row['status'],
        $row['month'],
        $row['current_participants']
    ]);
}

fclose($output);
exit();
?>