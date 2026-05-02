<?php
require '../vendor/autoload.php';
include('../config/db.php');

date_default_timezone_set('Asia/Manila');

use Dompdf\Dompdf;

// Get report type from URL param; default to 'daily'
$type = $_GET['type'] ?? 'daily';

/* ─── DATE FILTERS ───────────────────────────── */
// Set SQL WHERE clauses based on selected report type
switch ($type) {
  case 'daily':
    $logWhere = "rl.log_time >= CURDATE() AND rl.log_time < CURDATE() + INTERVAL 1 DAY";
    $visitorWhere = "visit_date >= CURDATE() AND visit_date < CURDATE() + INTERVAL 1 DAY";
    break;

  case 'weekly':
    $logWhere = "YEARWEEK(rl.log_time, 1) = YEARWEEK(CURDATE(), 1)";
    $visitorWhere = "YEARWEEK(visit_date, 1) = YEARWEEK(CURDATE(), 1)";
    break;

  case 'monthly':
    $logWhere = "MONTH(rl.log_time) = MONTH(CURDATE()) AND YEAR(rl.log_time) = YEAR(CURDATE())";
    $visitorWhere = "MONTH(visit_date) = MONTH(CURDATE()) AND YEAR(visit_date) = YEAR(CURDATE())";
    break;

  case 'yearly':
    $logWhere = "YEAR(rl.log_time) = YEAR(CURDATE())";
    $visitorWhere = "YEAR(visit_date) = YEAR(CURDATE())";
    break;

  default:
    $logWhere = "1";
    $visitorWhere = "1";
}

/* ─── FETCH RESIDENT LOGS ───────────────────── */
// Get resident movement logs filtered by date range
$stmt = $pdo->prepare("
  SELECT r.first_name, r.last_name, rm.room_number, rl.log_type, rl.log_time
  FROM resident_log rl
  JOIN resident r ON rl.resident_id = r.resident_id
  JOIN room rm ON rl.room_id = rm.room_id
  WHERE $logWhere
  ORDER BY rl.log_time DESC
");
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ─── FETCH VISITOR LOGS ───────────────────── */
// Get visitor logs with their visited resident filtered by date range
$stmt = $pdo->prepare("
  SELECT v.visitor_name, r.first_name, r.last_name, v.visit_date
  FROM visitor_log v
  JOIN resident r ON v.resident_id = r.resident_id
  WHERE $visitorWhere
  ORDER BY v.visit_date DESC
");
$stmt->execute();
$visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ─── SUMMARY CALCULATIONS ─────────────────── */
// Count totals and tally inside/outside movements
$totalLogs = count($logs);
$totalVisitors = count($visitors);

$inside = 0;
$outside = 0;

foreach ($logs as $log) {
  if ($log['log_type'] === 'inside')
    $inside++;
  if ($log['log_type'] === 'outside')
    $outside++;
}

/* ─── HTML TEMPLATE ────────────────────────── */
// Build the PDF HTML content — styles, summary, and tables
$html = "
<style>
  body {
    font-family: Inter, Arial, sans-serif;
    font-size: 11px;
    color: #111;
    margin: 20px;
  }

  h1 {
    font-size: 16px;
    font-weight: bold;
    text-align: center;
    margin-bottom: 5px;
  }

  .meta {
    text-align: center;
    font-size: 10px;
    margin-bottom: 15px;
  }

  h3 {
    font-size: 12px;
    font-weight: 600;
    margin-top: 15px;
    margin-bottom: 8px;
  }

  ul {
    padding-left: 18px;
    margin: 5px 0;
  }

  li {
    margin-bottom: 4px;
  }

  table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
  }

  th, td {
    border: 1px solid #ddd;
    padding: 6px;
    font-size: 11px;
  }

  th {
    background: #f2f2f2;
    font-weight: 600;
  }

  .footer {
    text-align: center;
    margin-top: 20px;
    font-size: 10px;
    color: #555;
  }
</style>

<h1>Dormonitory Report (" . strtoupper($type) . ")</h1>
<div class='meta'>
  Generated on: " . date("F d, Y h:i A") . "
</div>

<h3>Summary</h3>
<ul>
  <li>Total Resident Logs: <b>$totalLogs</b></li>
  <li>Inside Count: <b>$inside</b></li>
  <li>Outside Count: <b>$outside</b></li>
  <li>Total Visitors: <b>$totalVisitors</b></li>
</ul>

<h3>Resident Activity</h3>
<table>
<tr>
  <th>Name</th>
  <th>Room</th>
  <th>Type</th>
  <th>Time</th>
</tr>";

// Populate resident activity rows; show fallback if empty
if (empty($logs)) {
  $html .= "<tr><td colspan='4'>No records found</td></tr>";
} else {
  foreach ($logs as $log) {
    $time = date("M d, Y h:i A", strtotime($log['log_time']));
    $html .= "<tr>
      <td>{$log['first_name']} {$log['last_name']}</td>
      <td>{$log['room_number']}</td>
      <td>{$log['log_type']}</td>
      <td>{$time}</td>
    </tr>";
  }
}

$html .= "</table>";

// Visitor logs table
$html .= "
<h3>Visitor Logs</h3>
<table>
<tr>
  <th>Visitor</th>
  <th>Visited Resident</th>
  <th>Date</th>
</tr>";

// Populate visitor rows; show fallback if empty
if (empty($visitors)) {
  $html .= "<tr><td colspan='3'>No records found</td></tr>";
} else {
  foreach ($visitors as $v) {
    $date = date("M d, Y", strtotime($v['visit_date']));
    $html .= "<tr>
      <td>{$v['visitor_name']}</td>
      <td>{$v['first_name']} {$v['last_name']}</td>
      <td>{$date}</td>
    </tr>";
  }
}

$html .= "</table>

<div class='footer'>
  End of Report
</div>
";

/* ─── GENERATE PDF ─────────────────────────── */
// Load HTML into Dompdf, render, and stream as downloadable PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$dompdf->stream("report_$type.pdf", ["Attachment" => true]);