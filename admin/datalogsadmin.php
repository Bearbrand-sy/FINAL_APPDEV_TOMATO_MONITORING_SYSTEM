<?php
session_start();
include "../db.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != "Admin") {
    header("Location: ../index.php");
    exit();
}

$period = $_GET['period'] ?? 'weekly';
$interval = match($period) {
    'weekly'  => 'INTERVAL 7 DAY',
    'monthly' => 'INTERVAL 30 DAY',
    default   => 'INTERVAL 1 DAY',
};

$conn->query("
    CREATE TABLE IF NOT EXISTS `sensor_logs` (
        `id`          BIGINT AUTO_INCREMENT PRIMARY KEY,
        `delivery_id` INT          NOT NULL,
        `temperature` DECIMAL(5,2) NOT NULL,
        `humidity`    DECIMAL(5,2) NOT NULL,
        `ventilation` TINYINT(1)   NOT NULL DEFAULT 0,
        `recorded_at` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_delivery_time (`delivery_id`, `recorded_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
");

$sql = "
    SELECT
        sl.id, sl.delivery_id,
        d.transport_id AS transport_id,
        d.product      AS plant,
        d.origin, d.destination, d.driver, d.quantity, d.delivery_date,
        ROUND(sl.temperature, 1) AS temperature,
        ROUND(sl.humidity,    1) AS humidity,
        sl.ventilation, sl.recorded_at
    FROM sensor_logs sl
    INNER JOIN deliveries d ON d.id = sl.delivery_id
    WHERE sl.recorded_at >= DATE_SUB(NOW(), $interval)
    ORDER BY sl.recorded_at DESC
    LIMIT 200
";
$result = $conn->query($sql);
$logs   = [];
if ($result) while ($row = $result->fetch_assoc()) $logs[] = $row;

function getStatus($temp, $hum) {
    if ($temp >= 15 && $temp <= 30 && $hum >= 50 && $hum <= 60) return 'safe';
    if ($temp < 10 || $temp > 35 || $hum < 40 || $hum > 70) return 'danger';
    return 'warning';
}

$totalLogs   = count($logs);
$safeCount   = count(array_filter($logs, fn($r) => getStatus($r['temperature'], $r['humidity']) === 'safe'));
$warnCount   = count(array_filter($logs, fn($r) => getStatus($r['temperature'], $r['humidity']) === 'warning'));
$dangerCount = $totalLogs - $safeCount - $warnCount;
$avgTemp     = $totalLogs ? round(array_sum(array_column($logs, 'temperature')) / $totalLogs, 1) : '—';
$avgHum      = $totalLogs ? round(array_sum(array_column($logs, 'humidity'))    / $totalLogs, 1) : '—';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GreenGrow · Data Logs</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Fraunces:opsz,wght@9..144,300;9..144,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/manager.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .stat-grid { display:grid; grid-template-columns:repeat(5,1fr); gap:12px; margin-bottom:20px; }
        .stat-card { background:var(--surface); border:1px solid var(--border); border-radius:12px; padding:16px 14px; text-align:center; box-shadow:0 2px 8px #0d271510; }
        .stat-label { font-size:.62rem; letter-spacing:.1em; text-transform:uppercase; color:var(--muted); margin-bottom:6px; }
        .stat-value { font-family:'Fraunces',serif; font-size:1.6rem; font-weight:300; color:var(--text); }
        .stat-value.green { color:var(--green); } .stat-value.amber { color:var(--amber); } .stat-value.red { color:var(--red); }
        .filter-row { display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
        .filter-row label { font-size:.72rem; letter-spacing:.08em; text-transform:uppercase; color:var(--muted); }
        select.filter-select { background:var(--surface2); border:1px solid var(--border); border-radius:8px; color:var(--text); font-family:'DM Mono',monospace; font-size:.82rem; padding:8px 14px; cursor:pointer; outline:none; transition:border-color .2s; }
        select.filter-select:focus { border-color:var(--green); }
        .badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:.68rem; letter-spacing:.06em; text-transform:uppercase; font-weight:500; }
        .badge-safe    { background:#dcfce7; color:#166534; border:1px solid #86efac; }
        .badge-warning { background:#fef3c7; color:#92400e; border:1px solid #fcd34d; }
        .badge-danger  { background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; }
        .table-wrap { overflow-x:auto; max-height:400px; overflow-y:auto; }
        .table-wrap::-webkit-scrollbar { width:3px; }
        .table-wrap::-webkit-scrollbar-thumb { background:var(--border); border-radius:4px; }
        .empty-state { text-align:center; padding:40px; color:var(--muted); }
        .empty-state .empty-icon { font-size:2rem; opacity:.35; margin-bottom:10px; }
        .chart-layout { display:flex; align-items:center; justify-content:center; gap:40px; padding:10px 0; flex-wrap:wrap; }
        .chart-donut  { position:relative; width:220px; height:220px; flex-shrink:0; }
        @media (max-width:800px) { .stat-grid { grid-template-columns:repeat(2,1fr); } }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="logo">🌿 GreenGrow<span>Farm Management</span></div>
    <a href="dashboard.php"          class="nav-btn">Monitoring</a>
    <a href="datalogsadmin.php"      class="nav-btn active">Data Logs</a>
    <a href="transportlogsadmin.php" class="nav-btn">Transport Logs</a>
    <a href="useradmin.php"          class="nav-btn">Users</a>
    <div class="sidebar-foot">
        <div class="user-card">
            <div class="user-avatar"><?= strtoupper($_SESSION['name'][0]) ?></div>
            <div class="user-meta">
                <div class="user-name"><?= htmlspecialchars($_SESSION['name']) ?></div>
                <div class="user-role role-admin"><?= htmlspecialchars($_SESSION['role']) ?></div>
            </div>
        </div>
        <form method="POST" action="../logout.php" style="margin:0">
            <button type="submit" class="logout-btn">↩ Logout</button>
        </form>
    </div>
</div>

<div class="main">
    <div class="page-header">
        <h1>Data Logs</h1>
        <p>Historical sensor readings per transport</p>
    </div>

    <div class="stat-grid">
        <div class="stat-card"><div class="stat-label">Total Readings</div><div class="stat-value"><?= $totalLogs ?></div></div>
        <div class="stat-card"><div class="stat-label">Safe</div><div class="stat-value green"><?= $safeCount ?></div></div>
        <div class="stat-card"><div class="stat-label">Warning</div><div class="stat-value amber"><?= $warnCount ?></div></div>
        <div class="stat-card"><div class="stat-label">Danger</div><div class="stat-value red"><?= $dangerCount ?></div></div>
        <div class="stat-card"><div class="stat-label">Avg Temp / Hum</div><div class="stat-value" style="font-size:1.1rem;"><?= $avgTemp ?>°C / <?= $avgHum ?>%</div></div>
    </div>

    <div class="panel">
        <div class="panel-title">Filter</div>
        <form method="GET">
            <div class="filter-row">
                <label>Period</label>
                <select name="period" class="filter-select" onchange="this.form.submit()">
                    <option value="daily"   <?= $period==='daily'  ?'selected':'' ?>>Daily</option>
                    <option value="weekly"  <?= $period==='weekly' ?'selected':'' ?>>Weekly</option>
                    <option value="monthly" <?= $period==='monthly'?'selected':'' ?>>Monthly</option>
                </select>
            </div>
        </form>
    </div>

    <div class="panel">
        <div class="panel-title">Status Distribution</div>
        <?php if ($totalLogs === 0): ?>
        <div class="empty-state"><div class="empty-icon">📊</div><p>No sensor data yet for this period.</p></div>
        <?php else: ?>
        <div class="chart-layout">
            <div class="chart-donut"><canvas id="sensorChart"></canvas></div>
            <div style="display:flex;flex-direction:column;gap:14px;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <span style="width:14px;height:14px;border-radius:50%;background:#1a9e3f;display:inline-block;flex-shrink:0;"></span>
                    <div><div style="font-size:.75rem;color:var(--text);font-weight:500;">Safe</div><div style="font-size:.68rem;color:var(--muted);"><?= $safeCount ?> readings (<?= $totalLogs ? round($safeCount/$totalLogs*100) : 0 ?>%)</div></div>
                </div>
                <div style="display:flex;align-items:center;gap:10px;">
                    <span style="width:14px;height:14px;border-radius:50%;background:#d97706;display:inline-block;flex-shrink:0;"></span>
                    <div><div style="font-size:.75rem;color:var(--text);font-weight:500;">Warning</div><div style="font-size:.68rem;color:var(--muted);"><?= $warnCount ?> readings (<?= $totalLogs ? round($warnCount/$totalLogs*100) : 0 ?>%)</div></div>
                </div>
                <div style="display:flex;align-items:center;gap:10px;">
                    <span style="width:14px;height:14px;border-radius:50%;background:#dc2626;display:inline-block;flex-shrink:0;"></span>
                    <div><div style="font-size:.75rem;color:var(--text);font-weight:500;">Danger</div><div style="font-size:.68rem;color:var(--muted);"><?= $dangerCount ?> readings (<?= $totalLogs ? round($dangerCount/$totalLogs*100) : 0 ?>%)</div></div>
                </div>
                <div style="border-top:1px solid var(--border);padding-top:12px;">
                    <div style="font-size:.65rem;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);margin-bottom:4px;">Safe Range</div>
                    <div style="font-size:.7rem;color:var(--text);">🌡 Temp: 15 – 30 °C</div>
                    <div style="font-size:.7rem;color:var(--text);margin-top:3px;">💧 Humidity: 50 – 60 %</div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="panel">
        <div class="panel-title">Records <span style="font-size:.7rem;color:var(--muted);text-transform:none;letter-spacing:0;font-weight:normal;margin-left:4px;">(<?= $totalLogs ?> entries)</span></div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Recorded At</th><th>Transport ID</th><th>Plant</th><th>Route</th><th>Driver</th><th>Qty</th><th>Temperature</th><th>Humidity</th><th>Ventilation</th><th>Status</th></tr>
                </thead>
                <tbody>
                <?php if (empty($logs)): ?>
                <tr><td colspan="10" style="text-align:center;padding:32px;color:var(--muted);font-size:.78rem;">No data found for this period.</td></tr>
                <?php else: foreach ($logs as $row):
                    $status    = getStatus($row['temperature'], $row['humidity']);
                    $tOk       = $row['temperature'] >= 15 && $row['temperature'] <= 30;
                    $hOk       = $row['humidity']    >= 50 && $row['humidity']    <= 60;
                    $ventLabel = $row['ventilation'] ? 'ON' : 'OFF';
                    $ventColor = $row['ventilation'] ? 'var(--red)' : 'var(--green)';
                ?>
                <tr>
                    <td style="white-space:nowrap;font-size:.72rem;"><?= date('Y-m-d H:i', strtotime($row['recorded_at'])) ?></td>
                    <td><b><?= htmlspecialchars($row['transport_id']) ?></b></td>
                    <td>🍅 <?= htmlspecialchars($row['plant']) ?></td>
                    <td style="font-size:.72rem;white-space:nowrap;"><?= htmlspecialchars($row['origin']) ?><br><span style="color:var(--muted)">→ <?= htmlspecialchars($row['destination']) ?></span></td>
                    <td style="font-size:.72rem;"><?= htmlspecialchars($row['driver'] ?? '—') ?></td>
                    <td style="font-size:.72rem;"><?= htmlspecialchars($row['quantity'] ?? '—') ?></td>
                    <td style="color:<?= $tOk?'var(--green)':'var(--red)' ?>;font-weight:500;"><?= $row['temperature'] ?> °C</td>
                    <td style="color:<?= $hOk?'var(--green)':'var(--red)' ?>;font-weight:500;"><?= $row['humidity'] ?> %</td>
                    <td style="color:<?= $ventColor ?>;font-weight:500;"><?= $ventLabel ?></td>
                    <td><span class="badge badge-<?= $status ?>"><?= ucfirst($status) ?></span></td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
<?php if ($totalLogs > 0): ?>
new Chart(document.getElementById('sensorChart').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: ['Safe', 'Warning', 'Danger'],
        datasets: [{ data: [<?= $safeCount ?>, <?= $warnCount ?>, <?= $dangerCount ?>], backgroundColor: ['rgba(26,158,63,0.85)','rgba(217,119,6,0.85)','rgba(220,38,38,0.85)'], borderColor: ['#1a9e3f','#d97706','#dc2626'], borderWidth: 2, hoverOffset: 8 }]
    },
    options: { responsive: true, maintainAspectRatio: false, cutout: '62%', plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => { const total = ctx.dataset.data.reduce((a,b)=>a+b,0); const pct = total ? Math.round(ctx.parsed/total*100) : 0; return ` ${ctx.label}: ${ctx.parsed} readings (${pct}%)`; } } } } }
});
<?php endif; ?>
</script>
</body>
</html>