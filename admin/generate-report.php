<?php
require '../vendor/autoload.php';
include('../config/db.php');

use Dompdf\Dompdf;

$type = $_GET['type'] ?? 'daily';

$where = "";

switch ($type) {
  case 'daily':
    $where = "DATE(log_time) = CURDATE()";
    break;

  case 'weekly':
    $where = "YEARWEEK(log_time, 1) = YEARWEEK(CURDATE(), 1)";
    break;

  case 'monthly':
    $where = "MONTH(log_time) = MONTH(CURDATE()) AND YEAR(log_time) = YEAR(CURDATE())";
    break;

  case 'yearly':
    $where = "YEAR(log_time) = YEAR(CURDATE())";
    break;
}

// ─── FETCH RESIDENT LOGS ─────────────────────────
$stmt = $pdo->query("
  SELECT r.first_name, r.last_name, rm.room_number, rl.log_type, rl.log_time
  FROM resident_log rl
  JOIN resident r ON rl.resident_id = r.resident_id
  JOIN room rm ON rl.room_id = rm.room_id
  WHERE $where
  ORDER BY rl.log_time DESC
");

$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ─── FETCH VISITORS ──────────────────────────────
$visitorWhere = str_replace("log_time", "visit_date", $where);

$stmt = $pdo->query("
  SELECT v.visitor_name, r.first_name, r.last_name, v.visit_date
  FROM visitor_log v
  JOIN resident r ON v.resident_id = r.resident_id
  WHERE $visitorWhere
");

$visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ─── SUMMARY ─────────────────────────────────────
$totalLogs = count($logs);
$totalVisitors = count($visitors);

// ─── HTML TEMPLATE ───────────────────────────────
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
    margin-bottom: 20px;
  }

  h3 {
    font-size: 12px;
    font-weight: 600;
    margin-top: 15px;
    margin-bottom: 8px;
  }

  p {
    font-size: 11px;
    margin: 4px 0;
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
</style>

<h1>Dormitory Report (" . strtoupper($type) . ")</h1>

<h3>Summary</h3>
<ul>
  <li>Total Resident Logs: <b>$totalLogs</b></li>
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

foreach ($logs as $log) {
  $html .= "<tr>
    <td>{$log['first_name']} {$log['last_name']}</td>
    <td>{$log['room_number']}</td>
    <td>{$log['log_type']}</td>
    <td>{$log['log_time']}</td>
  </tr>";
}

$html .= "</table>";

$html .= "<h3>Visitor Logs</h3>
<table border='1' width='100%' cellpadding='5'>
<tr>
<th>Visitor</th>
<th>Visited Resident</th>
<th>Date</th>
</tr>";

foreach ($visitors as $v) {
  $html .= "<tr>
    <td>{$v['visitor_name']}</td>
    <td>{$v['first_name']} {$v['last_name']}</td>
    <td>{$v['visit_date']}</td>
  </tr>";
}

$html .= "</table>";

// ─── GENERATE PDF ───────────────────────────────
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$dompdf->stream("report_$type.pdf", ["Attachment" => true]);