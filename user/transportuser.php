<?php
session_start();
include "../db.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != "User") {
    header("Location: ../index.php");
    exit();
}

$locations = [
    "Manolo Farm"   => ["lat" => 8.4267,  "lng" => 125.0000],
    "Farm"          => ["lat" => 8.4267,  "lng" => 125.0000],
    "CDO Market"    => ["lat" => 8.4542,  "lng" => 124.6319],
    "Davao Market"  => ["lat" => 7.1907,  "lng" => 125.4553],
    "Butuan Market" => ["lat" => 8.9481,  "lng" => 125.5403],
    "Iligan Market" => ["lat" => 8.2280,  "lng" => 124.2450],
];

$deliveries = $conn->query("SELECT * FROM deliveries ORDER BY delivery_date DESC");
$rows = [];
while ($row = $deliveries->fetch_assoc()) $rows[] = $row;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GreenGrow · Transport Logs</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Fraunces:opsz,wght@9..144,300;9..144,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/manager.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <style>
        .main { max-width: 1400px; width: 100%; }

        .filter-bar { display: flex; gap: 10px; flex-wrap: wrap; }
        .filter-bar input,
        .filter-bar select {
            background: var(--surface2); border: 1px solid var(--border);
            border-radius: 8px; padding: 8px 12px;
            font-family: 'DM Mono', monospace; font-size: .78rem;
            color: var(--text); outline: none; transition: border-color .2s;
        }
        .filter-bar input { min-width: 220px; }
        .filter-bar input::placeholder { color: var(--muted); }
        .filter-bar input:focus,
        .filter-bar select:focus { border-color: var(--green); }

        .content-row {
            display: grid;
            grid-template-columns: 500px 1fr;
            gap: 16px;
            align-items: stretch;
        }

        /* left table panel */
        .logs-panel {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: 0 2px 12px #0d271514;
            display: flex;
            flex-direction: column;
            height: 600px;
            overflow: hidden;
        }
        .logs-panel-header { padding: 16px 14px 0; flex-shrink: 0; }
        .logs-panel-title {
            font-size: .68rem; letter-spacing: .1em; text-transform: uppercase;
            color: var(--muted); margin-bottom: 12px;
            display: flex; align-items: center; gap: 8px;
        }
        .logs-panel-title::after { content: ''; flex: 1; height: 1px; background: var(--border); }

        .table-scroll { overflow-y: auto; flex: 1; }
        .table-scroll::-webkit-scrollbar { width: 3px; }
        .table-scroll::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }

        .tiny-table { width: 100%; border-collapse: collapse; }
        .tiny-table th {
            text-align: left; padding: 7px 10px;
            font-size: .6rem; letter-spacing: .09em; text-transform: uppercase;
            color: var(--muted); font-weight: 500; white-space: nowrap;
            border-bottom: 2px solid var(--border);
            position: sticky; top: 0; background: var(--surface); z-index: 1;
        }
        .tiny-table td { padding: 9px 10px; color: var(--text); border-bottom: 1px solid var(--border); vertical-align: middle; font-size: .72rem; }
        .tiny-table tbody tr { cursor: pointer; transition: background .15s; }
        .tiny-table tbody tr:hover { background: var(--surface2); }
        .tiny-table tbody tr.row-selected { background: #f0fbf2; box-shadow: inset 3px 0 0 var(--green); }
        .tiny-table tbody tr:last-child td { border-bottom: none; }

        /* status pills */
        .pill { display: inline-block; padding: 2px 7px; border-radius: 20px; font-size: .58rem; letter-spacing: .05em; text-transform: uppercase; font-weight: 500; }
        .pill-delivered { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .pill-transit   { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; }
        .pill-delayed   { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .pill-pending   { background: var(--surface2); color: var(--muted); border: 1px solid var(--border); }

        /* right map panel */
        .map-panel {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: 0 2px 12px #0d271514;
            display: flex;
            flex-direction: column;
            height: 600px;
            overflow: hidden;
        }
        .map-panel-header { padding: 16px 20px 0; flex-shrink: 0; }
        .map-panel-title {
            font-size: .68rem; letter-spacing: .1em; text-transform: uppercase;
            color: var(--muted); margin-bottom: 10px;
            display: flex; align-items: center; gap: 8px;
        }
        .map-panel-title::after { content: ''; flex: 1; height: 1px; background: var(--border); }

        .route-strip {
            padding: 8px 20px 10px;
            font-size: .75rem;
            display: flex; align-items: center; gap: 14px;
            flex-wrap: wrap; flex-shrink: 0;
            border-bottom: 1px solid var(--border);
            background: var(--surface2);
        }
        .ri { display: flex; flex-direction: column; gap: 1px; }
        .ri-l { font-size: .55rem; letter-spacing: .09em; text-transform: uppercase; color: var(--muted); }
        .ri-v { font-size: .75rem; color: var(--text); font-weight: 500; }
        .ri-arrow { color: var(--green); font-size: .9rem; }
        .route-idle { padding: 8px 20px 10px; font-size: .72rem; color: var(--muted); border-bottom: 1px solid var(--border); background: var(--surface2); }

        #map { flex: 1; width: 100%; min-height: 0; z-index: 0; }

        @media (max-width: 900px) {
            .content-row { grid-template-columns: 1fr; }
            .logs-panel, .map-panel { height: 420px; }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="logo">🌿 GreenGrow<span>Farm Management</span></div>
    <a href="user.php"          class="nav-btn">Dashboard</a>
    <a href="transportuser.php" class="nav-btn active">Transport Logs</a>
    <div class="sidebar-foot">
        <div class="user-card">
            <div class="user-avatar"><?= strtoupper($_SESSION['name'][0]) ?></div>
            <div class="user-meta">
                <div class="user-name"><?= htmlspecialchars($_SESSION['name']) ?></div>
                <div class="user-role role-user"><?= htmlspecialchars($_SESSION['role']) ?></div>
            </div>
        </div>
        <form method="POST" action="../logout.php" style="margin:0">
            <button type="submit" class="logout-btn">↩ Logout</button>
        </form>
    </div>
</div>

<div class="main">
    <div class="page-header">
        <h1>Transport Logs</h1>
        <p>Track shipment history and delivery routes</p>
    </div>

    <!-- Filter -->
    <div class="panel" style="margin-bottom:16px;">
        <div class="panel-title">Filter</div>
        <div class="filter-bar">
            <input type="text" id="searchInput" placeholder="Search ID, route, status…" oninput="filterRows()">
            <select id="statusFilter" onchange="filterRows()">
                <option value="all">All Status</option>
                <option value="In Transit">In Transit</option>
                <option value="Delivered">Delivered</option>
                <option value="Pending">Pending</option>
                <option value="Delayed">Delayed</option>
            </select>
        </div>
    </div>

    <!-- Content row -->
    <div class="content-row">

        <!-- LEFT: compact table -->
        <div class="logs-panel">
            <div class="logs-panel-header">
                <div class="logs-panel-title">Records</div>
            </div>
            <div class="table-scroll">
                <table class="tiny-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Route</th>
                            <th>Temp</th>
                            <th>Hum</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="transportBody">
                    <?php foreach ($rows as $row):
                        $oLat = $locations[$row['origin']]['lat']      ?? 8.4542;
                        $oLng = $locations[$row['origin']]['lng']      ?? 124.6319;
                        $dLat = $locations[$row['destination']]['lat'] ?? 8.4542;
                        $dLng = $locations[$row['destination']]['lng'] ?? 124.6319;

                        $status = $row['status'] ?? 'Pending';
                        $sc = match(strtolower($status)) {
                            'delivered'  => 'pill-delivered',
                            'in transit' => 'pill-transit',
                            'delayed'    => 'pill-delayed',
                            default      => 'pill-pending',
                        };
                    ?>
                    <tr class="delivery-row"
                        data-transport="<?= htmlspecialchars($row['transport_id']) ?>"
                        data-product="<?= htmlspecialchars($row['product'] ?? 'Tomato') ?>"
                        data-origin="<?= htmlspecialchars($row['origin']) ?>"
                        data-destination="<?= htmlspecialchars($row['destination']) ?>"
                        data-driver="<?= htmlspecialchars($row['driver'] ?? '—') ?>"
                        data-plate="<?= htmlspecialchars($row['vehicle_plate'] ?? '—') ?>"
                        data-qty="<?= (int)($row['quantity'] ?? 0) ?>"
                        data-date="<?= htmlspecialchars($row['delivery_date'] ?? '') ?>"
                        data-priority="<?= htmlspecialchars($row['priority'] ?? '—') ?>"
                        data-lat-origin="<?= $oLat ?>"
                        data-lng-origin="<?= $oLng ?>"
                        data-lat-dest="<?= $dLat ?>"
                        data-lng-dest="<?= $dLng ?>"
                        data-status="<?= htmlspecialchars($status) ?>">
                        <td><b style="font-size:.72rem"><?= htmlspecialchars($row['transport_id']) ?></b></td>
                        <td style="font-size:.68rem;">
                            <?= htmlspecialchars($row['origin']) ?><br>
                            <span style="color:var(--muted)">→ <?= htmlspecialchars($row['destination']) ?></span>
                        </td>
                        <td class="sim-temp" style="color:var(--green)">--</td>
                        <td class="sim-hum"  style="color:var(--green)">--</td>
                        <td><span class="pill <?= $sc ?>"><?= htmlspecialchars($status) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($rows)): ?>
                    <tr><td colspan="5" style="text-align:center;padding:24px;color:var(--muted);font-size:.75rem;">No records found</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- RIGHT: map -->
        <div class="map-panel">
            <div class="map-panel-header">
                <div class="map-panel-title">Delivery Route Map</div>
            </div>
            <div id="routeStrip" class="route-idle">← Select a record to view its route</div>
            <div id="map"></div>
        </div>

    </div>
</div>

<script>
// ── SIMULATED SENSOR VALUES ──────────────────────────
const T_MIN = 15, T_MAX = 30;
const H_MIN = 50, H_MAX = 60;

function rnd(a, b) { return Math.floor(Math.random() * (b - a + 1)) + a; }

function simulateSensors() {
    document.querySelectorAll(".delivery-row").forEach(row => {
        const temp = rnd(T_MIN - 5, T_MAX + 5);
        const hum  = rnd(H_MIN - 6, H_MAX + 6);
        const tOk  = temp >= T_MIN && temp <= T_MAX;
        const hOk  = hum  >= H_MIN && hum  <= H_MAX;

        const tEl = row.querySelector(".sim-temp");
        const hEl = row.querySelector(".sim-hum");

        if (tEl) { tEl.textContent = temp + "°C"; tEl.style.color = tOk ? "var(--green)" : "var(--red)"; }
        if (hEl) { hEl.textContent = hum  + "%";  hEl.style.color = hOk ? "var(--green)" : "var(--red)"; }

        row.dataset.temp = temp + "°C";
        row.dataset.hum  = hum  + "%";
        row.dataset.tOk  = tOk ? "1" : "0";
        row.dataset.hOk  = hOk ? "1" : "0";

        // Update route strip if this row is selected
        if (row.classList.contains("row-selected")) updateStrip(row);
    });
}

simulateSensors();
setInterval(simulateSensors, 4000);

// ── MAP ──────────────────────────────────────────────
const map = L.map('map').setView([8.4542, 124.6319], 8);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap', maxZoom: 19
}).addTo(map);

let originMarker = null, destMarker = null, routeLayer = null;
let selectedRow  = null;

const greenIcon = L.divIcon({
    className: '',
    html: `<div style="width:13px;height:13px;background:#1a9e3f;border:2px solid #fff;border-radius:50%;box-shadow:0 0 0 3px #22c55e44"></div>`,
    iconSize: [13,13], iconAnchor: [6,6]
});
const redIcon = L.divIcon({
    className: '',
    html: `<div style="width:13px;height:13px;background:#dc2626;border:2px solid #fff;border-radius:50%;box-shadow:0 0 0 3px #dc262644"></div>`,
    iconSize: [13,13], iconAnchor: [6,6]
});

document.querySelectorAll('.delivery-row').forEach(row => {
    row.addEventListener('click', function () {
        if (selectedRow) selectedRow.classList.remove('row-selected');
        selectedRow = this;
        this.classList.add('row-selected');

        const oLat = parseFloat(this.dataset.latOrigin);
        const oLng = parseFloat(this.dataset.lngOrigin);
        const dLat = parseFloat(this.dataset.latDest);
        const dLng = parseFloat(this.dataset.lngDest);

        if (originMarker) map.removeLayer(originMarker);
        if (destMarker)   map.removeLayer(destMarker);
        if (routeLayer)   map.removeLayer(routeLayer);

        originMarker = L.marker([oLat, oLng], { icon: greenIcon })
            .addTo(map)
            .bindPopup(`<b>🌾 ${this.dataset.origin}</b><br><small>Origin</small>`);
        destMarker = L.marker([dLat, dLng], { icon: redIcon })
            .addTo(map)
            .bindPopup(`<b>🏪 ${this.dataset.destination}</b><br><small>Destination</small>`);

        fetch(`https://router.project-osrm.org/route/v1/driving/${oLng},${oLat};${dLng},${dLat}?overview=full&geometries=geojson`)
            .then(r => r.json())
            .then(data => {
                if (!data.routes?.[0]) throw new Error('No route');
                routeLayer = L.geoJSON(data.routes[0].geometry, {
                    style: { color: '#1a9e3f', weight: 4, opacity: .9 }
                }).addTo(map);
                map.fitBounds(routeLayer.getBounds(), { padding: [50, 50] });
                map.invalidateSize();
            })
            .catch(() => {
                routeLayer = L.polyline([[oLat, oLng],[dLat, dLng]], {
                    color: '#1a9e3f', weight: 3, dashArray: '8 5', opacity: .7
                }).addTo(map);
                map.fitBounds([[oLat, oLng],[dLat, dLng]], { padding: [60, 60] });
            });

        updateStrip(this);
        setTimeout(() => map.invalidateSize(), 100);
    });
});

function updateStrip(row) {
    const strip   = document.getElementById('routeStrip');
    const temp    = row.dataset.temp  || '--';
    const hum     = row.dataset.hum   || '--';
    const tColor  = row.dataset.tOk === '1' ? 'var(--green)' : 'var(--red)';
    const hColor  = row.dataset.hOk === '1' ? 'var(--green)' : 'var(--red)';
    const status  = row.dataset.status;
    const driver  = row.dataset.driver  || '—';
    const plate   = row.dataset.plate   || '—';
    const qty     = row.dataset.qty     || '—';
    const date    = row.dataset.date    || '—';
    const prio    = row.dataset.priority || '—';

    strip.className = 'route-strip';
    strip.innerHTML = `
        <div class="ri"><span class="ri-l">ID</span><span class="ri-v">${row.dataset.transport}</span></div>
        <div class="ri"><span class="ri-l">From</span><span class="ri-v">${row.dataset.origin}</span></div>
        <span class="ri-arrow">→</span>
        <div class="ri"><span class="ri-l">To</span><span class="ri-v">${row.dataset.destination}</span></div>
        <div class="ri"><span class="ri-l">Driver</span><span class="ri-v">${driver}</span></div>
        <div class="ri"><span class="ri-l">Plate</span><span class="ri-v">${plate}</span></div>
        <div class="ri"><span class="ri-l">Qty</span><span class="ri-v">${qty} crates</span></div>
        <div class="ri"><span class="ri-l">Date</span><span class="ri-v">${date}</span></div>
        <div class="ri"><span class="ri-l">Priority</span><span class="ri-v">${prio}</span></div>
        <div class="ri"><span class="ri-l">Temp</span><span class="ri-v" style="color:${tColor}">${temp}</span></div>
        <div class="ri"><span class="ri-l">Humidity</span><span class="ri-v" style="color:${hColor}">${hum}</span></div>
        <div class="ri"><span class="ri-l">Status</span><span class="ri-v">${status}</span></div>
    `;
}

// ── FILTER ───────────────────────────────────────────
function filterRows() {
    const q  = document.getElementById("searchInput").value.toLowerCase();
    const st = document.getElementById("statusFilter").value;
    document.querySelectorAll(".delivery-row").forEach(r => {
        const matchQ  = !q  || r.textContent.toLowerCase().includes(q);
        const matchSt = st === "all" || r.dataset.status === st;
        r.style.display = matchQ && matchSt ? "" : "none";
    });
}
</script>
</body>
</html>