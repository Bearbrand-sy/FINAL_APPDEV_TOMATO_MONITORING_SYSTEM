<?php
session_start();
include "../db.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != "Driver") {
    header("Location: ../index.php");
    exit();
}

$driverName = $_SESSION['name'];

// ── Auto-create tables ────────────────────────────────
$conn->query("
    CREATE TABLE IF NOT EXISTS `in_transit` (
        `id`            INT AUTO_INCREMENT PRIMARY KEY,
        `delivery_id`   INT          NOT NULL,
        `transport_id`  VARCHAR(20)  NOT NULL,
        `product`       VARCHAR(100) NOT NULL,
        `origin`        VARCHAR(100) NOT NULL,
        `destination`   VARCHAR(100) NOT NULL,
        `driver`        VARCHAR(100),
        `vehicle_plate` VARCHAR(20),
        `quantity`      INT,
        `priority`      VARCHAR(20),
        `started_at`    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
        `started_by`    VARCHAR(100),
        UNIQUE KEY `uq_delivery` (`delivery_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
");

$conn->query("
    CREATE TABLE IF NOT EXISTS `delivered` (
        `id`             INT AUTO_INCREMENT PRIMARY KEY,
        `delivery_id`    INT          NOT NULL,
        `transport_id`   VARCHAR(20)  NOT NULL,
        `product`        VARCHAR(100) NOT NULL,
        `origin`         VARCHAR(100) NOT NULL,
        `destination`    VARCHAR(100) NOT NULL,
        `driver`         VARCHAR(100),
        `vehicle_plate`  VARCHAR(20),
        `quantity`       INT,
        `priority`       VARCHAR(20),
        `avg_temp`       DECIMAL(5,2),
        `avg_hum`        DECIMAL(5,2),
        `total_readings` INT          DEFAULT 0,
        `delivered_at`   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
        `delivered_by`   VARCHAR(100),
        UNIQUE KEY `uq_delivery` (`delivery_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
");

// ── Handle POST: status change ────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['delivery_id'])) {
    $did    = (int)$_POST['delivery_id'];
    $action = $_POST['action'];

    // Security: only allow if this delivery belongs to the logged-in driver
    $check = $conn->prepare("SELECT * FROM deliveries WHERE id = ? AND driver = ?");
    $check->bind_param("is", $did, $driverName);
    $check->execute();
    $dRow = $check->get_result()->fetch_assoc();

    if ($dRow) {
        if ($action === 'in_transit') {
            $conn->query("UPDATE deliveries SET status='In Transit' WHERE id=$did");
            $stmt = $conn->prepare("
                INSERT IGNORE INTO in_transit
                    (delivery_id, transport_id, product, origin, destination,
                     driver, vehicle_plate, quantity, priority, started_by)
                VALUES (?,?,?,?,?,?,?,?,?,?)
            ");
            $stmt->bind_param("issssssiis",
                $did,
                $dRow['transport_id'], $dRow['product'],
                $dRow['origin'],       $dRow['destination'],
                $dRow['driver'],       $dRow['vehicle_plate'],
                $dRow['quantity'],     $dRow['priority'],
                $driverName
            );
            $stmt->execute();

        } elseif ($action === 'delivered') {
            $stats = $conn->query("
                SELECT ROUND(AVG(temperature),1) AS avg_temp,
                       ROUND(AVG(humidity),1)    AS avg_hum,
                       COUNT(*)                  AS total
                FROM sensor_logs WHERE delivery_id = $did
            ")->fetch_assoc();

            $avgTemp   = $stats['avg_temp'] ?? null;
            $avgHum    = $stats['avg_hum']  ?? null;
            $totalRead = (int)($stats['total'] ?? 0);

            $conn->query("UPDATE deliveries SET status='Delivered' WHERE id=$did");

            $stmt = $conn->prepare("
                INSERT IGNORE INTO delivered
                    (delivery_id, transport_id, product, origin, destination,
                     driver, vehicle_plate, quantity, priority,
                     avg_temp, avg_hum, total_readings, delivered_by)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
            ");
            $stmt->bind_param("issssssissdds",
                $did,
                $dRow['transport_id'], $dRow['product'],
                $dRow['origin'],       $dRow['destination'],
                $dRow['driver'],       $dRow['vehicle_plate'],
                $dRow['quantity'],     $dRow['priority'],
                $avgTemp,              $avgHum,
                $totalRead,            $driverName
            );
            $stmt->execute();
        }
    }

    header("Location: driver.php");
    exit();
}

// ── Fetch active deliveries for THIS driver only ──────
$activeStmt = $conn->prepare("
    SELECT
        d.*,
        (SELECT ROUND(sl.temperature,1) FROM sensor_logs sl WHERE sl.delivery_id = d.id ORDER BY sl.recorded_at DESC LIMIT 1) AS last_temp,
        (SELECT ROUND(sl.humidity,1)    FROM sensor_logs sl WHERE sl.delivery_id = d.id ORDER BY sl.recorded_at DESC LIMIT 1) AS last_hum,
        (SELECT sl.ventilation          FROM sensor_logs sl WHERE sl.delivery_id = d.id ORDER BY sl.recorded_at DESC LIMIT 1) AS last_vent,
        (SELECT sl.recorded_at          FROM sensor_logs sl WHERE sl.delivery_id = d.id ORDER BY sl.recorded_at DESC LIMIT 1) AS last_reading
    FROM deliveries d
    WHERE d.driver = ?
      AND d.status IN ('Pending','In Transit')
    ORDER BY FIELD(d.status,'In Transit','Pending'), d.delivery_date ASC
");
$activeStmt->bind_param("s", $driverName);
$activeStmt->execute();
$activeResult = $activeStmt->get_result();
$activeRows   = [];
while ($row = $activeResult->fetch_assoc()) $activeRows[] = $row;

// ── Fetch completed deliveries for THIS driver ────────
$doneStmt = $conn->prepare("
    SELECT dv.*, d.delivery_date, d.notes
    FROM delivered dv
    LEFT JOIN deliveries d ON d.id = dv.delivery_id
    WHERE dv.driver = ?
    ORDER BY dv.delivered_at DESC
");
$doneStmt->bind_param("s", $driverName);
$doneStmt->execute();
$doneResult = $doneStmt->get_result();
$doneRows   = [];
while ($row = $doneResult->fetch_assoc()) $doneRows[] = $row;

// ── Summary ───────────────────────────────────────────
$cPending   = count(array_filter($activeRows, fn($r) => $r['status'] === 'Pending'));
$cTransit   = count(array_filter($activeRows, fn($r) => $r['status'] === 'In Transit'));
$cDelivered = count($doneRows);
$cTotal     = count($activeRows);

function sStatus($temp, $hum) {
    if ($temp === null || $hum === null) return 'no-data';
    if ($temp >= 15 && $temp <= 30 && $hum >= 50 && $hum <= 60) return 'safe';
    if ($temp < 10  || $temp > 35  || $hum < 40  || $hum > 70)  return 'danger';
    return 'warning';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GreenGrow · Driver Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Fraunces:opsz,wght@9..144,300;9..144,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/manager.css">
    <style>
        /* stat cards */
        .stat-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:24px; }
        .stat-card { background:var(--surface); border:1px solid var(--border); border-radius:12px; padding:18px 16px; text-align:center; box-shadow:0 2px 8px #0d271510; }
        .stat-icon { font-size:1.5rem; margin-bottom:6px; }
        .stat-label { font-size:.62rem; letter-spacing:.1em; text-transform:uppercase; color:var(--muted); margin-bottom:4px; }
        .stat-value { font-family:'Fraunces',serif; font-size:2rem; font-weight:300; color:var(--text); }
        .stat-value.green { color:var(--green); }
        .stat-value.amber { color:var(--amber); }
        .stat-value.blue  { color:#3b82f6; }

        /* delivery cards */
        .delivery-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:14px; }
        .delivery-card { background:var(--surface2); border:1.5px solid var(--border); border-radius:12px; padding:16px 18px; cursor:pointer; transition:all .2s; }
        .delivery-card:hover { border-color:var(--green); box-shadow:0 3px 16px var(--green-glow); transform:translateY(-1px); }
        .delivery-card.card-transit { border-color:#fcd34d; background:#fffdf0; }
        .delivery-card.card-danger  { border-color:#fca5a5; background:#fff8f8; }
        .delivery-card.card-safe    { border-color:#86efac; background:#f0fbf2; }

        .dc-header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px; }
        .dc-tid { font-family:'Fraunces',serif; font-size:1.05rem; font-weight:600; color:var(--text); }
        .dc-num { font-size:.65rem; color:var(--muted); margin-top:1px; }
        .dc-route { font-size:.72rem; color:var(--muted); margin-bottom:12px; line-height:1.6; }
        .dc-route b { color:var(--text); }

        .dc-sensors { display:grid; grid-template-columns:1fr 1fr 1fr; gap:7px; margin-bottom:12px; }
        .dc-s { background:var(--surface); border:1px solid var(--border); border-radius:8px; padding:7px 5px; text-align:center; }
        .dc-s-lbl { font-size:.55rem; letter-spacing:.07em; text-transform:uppercase; color:var(--muted); margin-bottom:3px; }
        .dc-s-val { font-family:'Fraunces',serif; font-size:.95rem; font-weight:300; }
        .dc-s-val.ok   { color:var(--green); }
        .dc-s-val.warn { color:var(--red); }
        .dc-s-val.none { color:var(--muted); }

        .dc-footer { display:flex; justify-content:space-between; align-items:center; padding-top:10px; border-top:1px solid var(--border); font-size:.65rem; color:var(--muted); }

        /* badges */
        .badge { display:inline-block; padding:2px 9px; border-radius:20px; font-size:.63rem; letter-spacing:.06em; text-transform:uppercase; font-weight:500; }
        .b-pending   { background:var(--surface2); color:var(--muted); border:1px solid var(--border); }
        .b-transit   { background:#fef3c7; color:#92400e; border:1px solid #fcd34d; }
        .b-delivered { background:#dcfce7; color:#166534; border:1px solid #86efac; }
        .b-safe      { background:#dcfce7; color:#166534; border:1px solid #86efac; }
        .b-warning   { background:#fef3c7; color:#92400e; border:1px solid #fcd34d; }
        .b-danger    { background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; }
        .b-no-data   { background:var(--surface2); color:var(--muted); border:1px solid var(--border); }

        /* priority colors */
        .priority-high   { color:var(--red); font-weight:500; }
        .priority-medium { color:var(--amber); font-weight:500; }
        .priority-low    { color:var(--green); font-weight:500; }

        /* table */
        .table-wrap { overflow-x:auto; }
        table { width:100%; border-collapse:collapse; font-size:.78rem; }
        thead tr { border-bottom:2px solid var(--border); }
        th { text-align:left; padding:10px 12px; font-size:.65rem; letter-spacing:.1em; text-transform:uppercase; color:var(--muted); font-weight:500; white-space:nowrap; }
        tbody tr { border-bottom:1px solid var(--border); transition:background .15s; }
        tbody tr:hover { background:var(--surface2); }
        tbody tr:last-child { border-bottom:none; }
        td { padding:10px 12px; color:var(--text); vertical-align:middle; }

        /* modal */
        .modal-overlay { display:none; position:fixed; inset:0; background:rgba(20,39,26,.45); backdrop-filter:blur(4px); z-index:500; align-items:center; justify-content:center; }
        .modal-overlay.open { display:flex; }
        .modal-box { background:var(--surface); border:1px solid var(--border); border-radius:18px; padding:28px 30px; width:100%; max-width:500px; max-height:90vh; overflow-y:auto; box-shadow:0 20px 60px #0d271530; animation:modalIn .25s ease; position:relative; }
        @keyframes modalIn { from{opacity:0;transform:scale(.95) translateY(10px);}to{opacity:1;transform:scale(1) translateY(0);} }

        .modal-close { position:absolute; top:16px; right:18px; background:none; border:none; font-size:1.3rem; color:var(--muted); cursor:pointer; padding:4px 8px; border-radius:6px; transition:all .2s; }
        .modal-close:hover { background:var(--surface2); color:var(--text); }

        .modal-title { font-family:'Fraunces',serif; font-size:1.4rem; font-weight:300; color:var(--text); margin-bottom:4px; }
        .modal-sub   { font-size:.72rem; color:var(--muted); margin-bottom:20px; }

        .modal-section { font-size:.65rem; letter-spacing:.1em; text-transform:uppercase; color:var(--muted); margin:18px 0 10px; display:flex; align-items:center; gap:8px; }
        .modal-section::after { content:''; flex:1; height:1px; background:var(--border); }

        .modal-row { display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid var(--border); font-size:.8rem; }
        .modal-row:last-child { border-bottom:none; }
        .modal-row span { color:var(--muted); }
        .modal-row b    { color:var(--text); font-weight:500; }

        .sensor-row { display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px; margin-top:10px; }
        .sensor-box { background:var(--surface2); border:1px solid var(--border); border-radius:10px; padding:12px; text-align:center; }
        .sensor-box-label { font-size:.6rem; letter-spacing:.08em; text-transform:uppercase; color:var(--muted); margin-bottom:4px; }
        .sensor-box-value { font-family:'Fraunces',serif; font-size:1.4rem; font-weight:300; }
        .sensor-box-value.ok   { color:var(--green); }
        .sensor-box-value.warn { color:var(--red); }
        .sensor-box-value.none { color:var(--muted); }

        /* alert banner inside modal */
        .sensor-alert { display:none; align-items:center; gap:8px; background:#fee2e2; border:1px solid #fca5a5; border-radius:8px; padding:9px 13px; font-size:.75rem; color:#b91c1c; margin-top:12px; }
        .sensor-alert.show { display:flex; }

        .btn-row { display:flex; gap:10px; margin-top:22px; }
        .btn-transit {
            flex:1; padding:11px; border-radius:9px;
            border:2px solid #fcd34d; background:#fef9e7; color:#92400e;
            font-family:'DM Mono',monospace; font-size:.78rem; letter-spacing:.06em;
            text-transform:uppercase; font-weight:500; cursor:pointer; transition:all .2s;
        }
        .btn-transit:hover { background:#fef3c7; border-color:#f59e0b; }
        .btn-delivered {
            flex:1; padding:11px; border-radius:9px;
            border:none; background:var(--green); color:#fff;
            font-family:'DM Mono',monospace; font-size:.78rem; letter-spacing:.06em;
            text-transform:uppercase; font-weight:500; cursor:pointer; transition:all .2s;
        }
        .btn-delivered:hover { opacity:.88; transform:translateY(-1px); }
        .btn-disabled { opacity:.4 !important; cursor:not-allowed !important; pointer-events:none !important; }

        /* notes box in modal */
        .notes-box { background:var(--surface2); border:1px solid var(--border); border-radius:8px; padding:10px 14px; font-size:.78rem; color:var(--text); margin-top:6px; line-height:1.6; }

        .empty-state { text-align:center; padding:36px; color:var(--muted); }
        .empty-state .empty-icon { font-size:2rem; opacity:.35; margin-bottom:8px; }
        .empty-state p { font-size:.78rem; }

        @media (max-width:700px) {
            .stat-grid { grid-template-columns:repeat(2,1fr); }
            .delivery-grid { grid-template-columns:1fr; }
            .modal-box { padding:20px 16px; }
        }
    </style>
</head>
<body>

<!-- ── MODAL ────────────────────────────────────────── -->
<div class="modal-overlay" id="modalOverlay" onclick="closeModal(event)">
    <div class="modal-box">
        <button class="modal-close" onclick="closeModalDirect()">✕</button>

        <div class="modal-title" id="m-tid">—</div>
        <div class="modal-sub"   id="m-sub">Delivery details</div>

        <div class="modal-section">Delivery Info</div>
        <div class="modal-row"><span>Delivery #</span><b id="m-id">—</b></div>
        <div class="modal-row"><span>Transport ID</span><b id="m-transport">—</b></div>
        <div class="modal-row"><span>Product</span><b id="m-product">—</b></div>
        <div class="modal-row"><span>Quantity</span><b id="m-qty">—</b></div>
        <div class="modal-row"><span>Delivery Date</span><b id="m-date">—</b></div>
        <div class="modal-row"><span>Priority</span><b id="m-priority">—</b></div>
        <div class="modal-row"><span>Status</span><b id="m-status">—</b></div>

        <div class="modal-section">Route</div>
        <div class="modal-row"><span>Origin</span><b id="m-origin">—</b></div>
        <div class="modal-row"><span>Destination</span><b id="m-dest">—</b></div>

        <div class="modal-section">Your Assignment</div>
        <div class="modal-row"><span>Driver</span><b id="m-driver">—</b></div>
        <div class="modal-row"><span>Vehicle Plate</span><b id="m-plate">—</b></div>

        <div class="modal-section">Sensor Conditions</div>
        <div class="sensor-row">
            <div class="sensor-box">
                <div class="sensor-box-label">🌡 Temperature</div>
                <div class="sensor-box-value" id="m-temp">—</div>
            </div>
            <div class="sensor-box">
                <div class="sensor-box-label">💧 Humidity</div>
                <div class="sensor-box-value" id="m-hum">—</div>
            </div>
            <div class="sensor-box">
                <div class="sensor-box-label">🌬 Ventilation</div>
                <div class="sensor-box-value" id="m-vent">—</div>
            </div>
        </div>
        <div class="sensor-alert" id="sensorAlert">
            ⚠ Sensor values out of safe range — handle cargo with care
        </div>

        <div class="modal-section">Notes</div>
        <div class="notes-box" id="m-notes">—</div>

        <!-- Action buttons -->
        <form method="POST" id="modalForm">
            <input type="hidden" name="delivery_id" id="m-delivery-id">
            <div class="btn-row">
                <button type="submit" name="action" value="in_transit"
                        class="btn-transit" id="btnTransit">
                    🔄 In Transit
                </button>
                <button type="submit" name="action" value="delivered"
                        class="btn-delivered" id="btnDelivered">
                    ✅ Mark as Delivered
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ── SIDEBAR ───────────────────────────────────────── -->
<div class="sidebar">
    <div class="logo">🌿 GreenGrow<span>Farm Management</span></div>
    <a href="driver.php" class="nav-btn active">My Deliveries</a>
    <div class="sidebar-foot">
        <div class="user-card">
            <div class="user-avatar"><?= strtoupper($_SESSION['name'][0]) ?></div>
            <div class="user-meta">
                <div class="user-name"><?= htmlspecialchars($_SESSION['name']) ?></div>
                <div class="user-role" style="color:var(--amber);font-size:.63rem;letter-spacing:.07em;text-transform:uppercase;margin-top:2px;">
                    Driver
                </div>
            </div>
        </div>
        <form method="POST" action="../logout.php" style="margin:0">
            <button type="submit" class="logout-btn">↩ Logout</button>
        </form>
    </div>
</div>

<!-- ── MAIN ──────────────────────────────────────────── -->
<div class="main">
    <div class="page-header">
        <h1>My Deliveries</h1>
        <p>Assigned to <b style="color:var(--green)"><?= htmlspecialchars($driverName) ?></b> — click a card to update status</p>
    </div>

    <!-- Summary stats -->
    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-icon">📦</div>
            <div class="stat-label">Active</div>
            <div class="stat-value"><?= $cTotal ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">⏳</div>
            <div class="stat-label">Pending</div>
            <div class="stat-value amber"><?= $cPending ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🚛</div>
            <div class="stat-label">In Transit</div>
            <div class="stat-value blue"><?= $cTransit ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">✅</div>
            <div class="stat-label">Delivered</div>
            <div class="stat-value green"><?= $cDelivered ?></div>
        </div>
    </div>

    <!-- ── Active delivery cards ── -->
    <div class="panel">
        <div class="panel-title">
            Active Deliveries
            <span style="font-size:.7rem;color:var(--muted);text-transform:none;letter-spacing:0;margin-left:4px;"><?= $cTotal ?> assigned · tap to manage</span>
        </div>

        <?php if (empty($activeRows)): ?>
        <div class="empty-state">
            <div class="empty-icon">📭</div>
            <p>No active deliveries assigned to you right now.</p>
        </div>
        <?php else: ?>
        <div class="delivery-grid">
            <?php foreach ($activeRows as $d):
                $ss      = sStatus($d['last_temp'], $d['last_hum']);
                $tOk     = $d['last_temp'] !== null && $d['last_temp'] >= 15 && $d['last_temp'] <= 30;
                $hOk     = $d['last_hum']  !== null && $d['last_hum']  >= 50 && $d['last_hum']  <= 60;
                $cardCls = $d['status'] === 'In Transit'
                    ? 'card-transit'
                    : ($ss === 'danger' ? 'card-danger' : ($ss === 'safe' ? 'card-safe' : ''));
                $sBadge  = $d['status'] === 'In Transit' ? 'b-transit' : 'b-pending';
                $pc      = match($d['priority']) { 'High' => 'priority-high', 'Low' => 'priority-low', default => 'priority-medium' };
            ?>
            <div class="delivery-card <?= $cardCls ?>"
                 onclick='openModal(<?= json_encode([
                     "id"           => $d['id'],
                     "transport_id" => $d['transport_id'],
                     "product"      => $d['product'],
                     "origin"       => $d['origin'],
                     "destination"  => $d['destination'],
                     "driver"       => $d['driver'],
                     "vehicle_plate"=> $d['vehicle_plate'] ?? "—",
                     "quantity"     => $d['quantity'],
                     "delivery_date"=> $d['delivery_date'],
                     "priority"     => $d['priority'],
                     "status"       => $d['status'],
                     "notes"        => $d['notes'] ?? "",
                     "last_temp"    => $d['last_temp'],
                     "last_hum"     => $d['last_hum'],
                     "last_vent"    => $d['last_vent'],
                 ]) ?>)'>

                <div class="dc-header">
                    <div>
                        <div class="dc-tid"><?= htmlspecialchars($d['transport_id']) ?></div>
                        <div class="dc-num">Delivery #<?= $d['id'] ?></div>
                    </div>
                    <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px;">
                        <span class="badge <?= $sBadge ?>"><?= htmlspecialchars($d['status']) ?></span>
                        <span class="<?= $pc ?>" style="font-size:.65rem;">▲ <?= htmlspecialchars($d['priority']) ?></span>
                    </div>
                </div>

                <div class="dc-route">
                    🍅 <b><?= htmlspecialchars($d['product']) ?></b> · <?= (int)$d['quantity'] ?> crates<br>
                    📍 <?= htmlspecialchars($d['origin']) ?> → <?= htmlspecialchars($d['destination']) ?><br>
                    📅 <?= htmlspecialchars($d['delivery_date']) ?>
                    <?php if ($d['notes']): ?>
                    <br>📝 <span style="font-style:italic;color:var(--muted);"><?= htmlspecialchars(mb_strimwidth($d['notes'], 0, 50, '…')) ?></span>
                    <?php endif; ?>
                </div>

                <!-- Latest sensor readings -->
                <div class="dc-sensors">
                    <div class="dc-s">
                        <div class="dc-s-lbl">🌡 Temp</div>
                        <div class="dc-s-val <?= $d['last_temp'] !== null ? ($tOk ? 'ok' : 'warn') : 'none' ?>">
                            <?= $d['last_temp'] !== null ? $d['last_temp'].'°C' : '—' ?>
                        </div>
                    </div>
                    <div class="dc-s">
                        <div class="dc-s-lbl">💧 Humidity</div>
                        <div class="dc-s-val <?= $d['last_hum'] !== null ? ($hOk ? 'ok' : 'warn') : 'none' ?>">
                            <?= $d['last_hum'] !== null ? $d['last_hum'].'%' : '—' ?>
                        </div>
                    </div>
                    <div class="dc-s">
                        <div class="dc-s-lbl">🌬 Vent</div>
                        <div class="dc-s-val <?= $d['last_vent'] !== null ? ($d['last_vent'] ? 'warn' : 'ok') : 'none' ?>">
                            <?= $d['last_vent'] !== null ? ($d['last_vent'] ? 'ON' : 'OFF') : '—' ?>
                        </div>
                    </div>
                </div>

                <div class="dc-footer">
                    <span class="badge b-<?= $ss === 'no-data' ? 'no-data' : $ss ?>">
                        <?= $ss === 'no-data' ? 'No Data' : ucfirst($ss) ?>
                    </span>
                    <span>
                        <?= $d['last_reading'] ? '🕐 '.date('M d, H:i', strtotime($d['last_reading'])) : 'No readings' ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── Delivered table ── -->
    <div class="panel">
        <div class="panel-title">
            Completed Deliveries
            <span style="font-size:.7rem;color:var(--muted);text-transform:none;letter-spacing:0;margin-left:4px;">(<?= $cDelivered ?> records)</span>
        </div>

        <?php if (empty($doneRows)): ?>
        <div class="empty-state">
            <div class="empty-icon">✅</div>
            <p>No completed deliveries yet.</p>
        </div>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Transport ID</th>
                        <th>Product</th>
                        <th>Route</th>
                        <th>Vehicle</th>
                        <th>Qty</th>
                        <th>Delivery Date</th>
                        <th>Avg Temp</th>
                        <th>Avg Humidity</th>
                        <th>Readings</th>
                        <th>Delivered At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($doneRows as $d):
                        $tOk = $d['avg_temp'] !== null && $d['avg_temp'] >= 15 && $d['avg_temp'] <= 30;
                        $hOk = $d['avg_hum']  !== null && $d['avg_hum']  >= 50 && $d['avg_hum']  <= 60;
                    ?>
                    <tr>
                        <td><b><?= htmlspecialchars($d['transport_id']) ?></b></td>
                        <td>🍅 <?= htmlspecialchars($d['product']) ?></td>
                        <td style="font-size:.72rem;">
                            <?= htmlspecialchars($d['origin']) ?><br>
                            <span style="color:var(--muted)">→ <?= htmlspecialchars($d['destination']) ?></span>
                        </td>
                        <td style="font-size:.72rem;"><?= htmlspecialchars($d['vehicle_plate'] ?? '—') ?></td>
                        <td><?= (int)$d['quantity'] ?></td>
                        <td><?= htmlspecialchars($d['delivery_date'] ?? '—') ?></td>
                        <td style="color:<?= $d['avg_temp'] !== null ? ($tOk ? 'var(--green)' : 'var(--red)') : 'var(--muted)' ?>;font-weight:500;">
                            <?= $d['avg_temp'] !== null ? $d['avg_temp'].' °C' : '—' ?>
                        </td>
                        <td style="color:<?= $d['avg_hum'] !== null ? ($hOk ? 'var(--green)' : 'var(--red)') : 'var(--muted)' ?>;font-weight:500;">
                            <?= $d['avg_hum'] !== null ? $d['avg_hum'].' %' : '—' ?>
                        </td>
                        <td style="text-align:center;"><?= (int)$d['total_readings'] ?></td>
                        <td style="font-size:.72rem;white-space:nowrap;">
                            <?= date('M d, Y H:i', strtotime($d['delivered_at'])) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function openModal(d) {
    document.getElementById("m-tid").textContent       = d.transport_id;
    document.getElementById("m-sub").textContent       = d.product + " · " + d.origin + " → " + d.destination;
    document.getElementById("m-id").textContent        = "#" + d.id;
    document.getElementById("m-transport").textContent = d.transport_id;
    document.getElementById("m-product").textContent   = d.product;
    document.getElementById("m-qty").textContent       = d.quantity + " crates";
    document.getElementById("m-date").textContent      = d.delivery_date;
    document.getElementById("m-priority").textContent  = d.priority;
    document.getElementById("m-status").textContent    = d.status;
    document.getElementById("m-origin").textContent    = d.origin;
    document.getElementById("m-dest").textContent      = d.destination;
    document.getElementById("m-driver").textContent    = d.driver  || "—";
    document.getElementById("m-plate").textContent     = d.vehicle_plate || "—";
    document.getElementById("m-notes").textContent     = d.notes  || "No special instructions.";
    document.getElementById("m-delivery-id").value     = d.id;

    // Sensor values
    const tOk = d.last_temp !== null && d.last_temp >= 15 && d.last_temp <= 30;
    const hOk = d.last_hum  !== null && d.last_hum  >= 50 && d.last_hum  <= 60;

    const tEl = document.getElementById("m-temp");
    tEl.textContent = d.last_temp !== null ? d.last_temp + "°C" : "—";
    tEl.className   = "sensor-box-value " + (d.last_temp !== null ? (tOk ? "ok" : "warn") : "none");

    const hEl = document.getElementById("m-hum");
    hEl.textContent = d.last_hum !== null ? d.last_hum + "%" : "—";
    hEl.className   = "sensor-box-value " + (d.last_hum !== null ? (hOk ? "ok" : "warn") : "none");

    const vEl = document.getElementById("m-vent");
    vEl.textContent = d.last_vent !== null ? (d.last_vent ? "ON" : "OFF") : "—";
    vEl.className   = "sensor-box-value " + (d.last_vent !== null ? (d.last_vent ? "warn" : "ok") : "none");

    // Sensor alert
    const alert = document.getElementById("sensorAlert");
    alert.classList.toggle("show", d.last_temp !== null && (!tOk || !hOk));

    // Button states
    const btnT = document.getElementById("btnTransit");
    const btnD = document.getElementById("btnDelivered");
    btnT.classList.toggle("btn-disabled", d.status === "In Transit");
    btnD.classList.remove("btn-disabled");

    document.getElementById("modalOverlay").classList.add("open");
}

function closeModal(e) {
    if (e.target === document.getElementById("modalOverlay"))
        document.getElementById("modalOverlay").classList.remove("open");
}

function closeModalDirect() {
    document.getElementById("modalOverlay").classList.remove("open");
}

document.addEventListener("keydown", e => {
    if (e.key === "Escape") closeModalDirect();
});
</script>
</body>
</html>