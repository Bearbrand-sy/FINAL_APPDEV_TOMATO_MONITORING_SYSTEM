<?php
session_start();
include "../db.php";

if(!isset($_SESSION['role']) || $_SESSION['role'] != "Manager"){
    header("Location: ../index.php");
    exit();
}

// Define coordinates for each location
$locations = [
    "Manolo Farm" => ["lat" => 8.4267, "lng" => 125.0000],
    "CDO Market" => ["lat" => 8.4542, "lng" => 124.6319],
    "Davao Market" => ["lat" => 7.1907, "lng" => 125.4553],
    "Butuan Market" => ["lat" => 8.9481, "lng" => 125.5403],
    "Iligan Market" => ["lat" => 8.2280, "lng" => 124.2450],
];

// Fetch deliveries
$deliveries = $conn->query("SELECT * FROM deliveries ORDER BY delivery_date DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>GreenGrow · Transport Logs</title>

<link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Fraunces:wght@300;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/transport.css">

<!-- MAP -->
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="logo">🌿 GreenGrow<span>Farm Management</span></div>

    <a href="manager.php" class="nav-btn">Monitoring</a>
    <a href="datalogsmanager.php" class="nav-btn">Data Logs</a>
    <a href="transportmanager.php" class="nav-btn active">Transport Logs</a>
    <a href="create_delivery.php" class="nav-btn">Create Delivery</a>
    <a href="accounts_manager.php" class="nav-btn">Users</a>

    <div class="sidebar-foot">
        <div class="user-card">
            <div class="user-avatar"><?= strtoupper($_SESSION['name'][0]) ?></div>
            <div class="user-meta">
                <div class="user-name"><?= $_SESSION['name'] ?></div>
                <div class="user-role role-manager"><?= $_SESSION['role'] ?></div>
            </div>
        </div>
        <form method="POST" action="../logout.php">
            <button class="logout-btn">↩ Logout</button>
        </form>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="main">
    <div class="page-header">
        <h1>Transport Logs</h1>
        <p>Track shipment history and sensor data</p>
    </div>

    <!-- FILTER PANEL -->
    <div class="panel filter-panel">
        <div class="panel-title">Filter</div>
        <div class="filter-bar">
            <input type="text" placeholder="Search transport, plant..." id="searchInput">
            <select id="timeFilter">
                <option>All Time</option>
                <option>Today</option>
                <option>This Week</option>
                <option>This Month</option>
            </select>
            <select id="statusFilter">
                <option>All Status</option>
                <option>Delivered</option>
                <option>In Transit</option>
                <option>Delayed</option>
            </select>
        </div>
    </div>

    <!-- CONTENT ROW -->
    <div class="content-row">

        <!-- LEFT TABLE -->
        <div class="logs-side">
            <div class="panel">
                <div class="panel-title">Transport Records</div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Route</th>
                                <th>Temp</th>
                                <th>Hum</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $deliveries->fetch_assoc()): ?>
                            <?php
                                $originLat = $locations[$row['origin']]['lat'] ?? 8.4542;
                                $originLng = $locations[$row['origin']]['lng'] ?? 124.6319;
                                $destLat   = $locations[$row['destination']]['lat'] ?? 8.4542;
                                $destLng   = $locations[$row['destination']]['lng'] ?? 124.6319;
                            ?>
                            <tr class="delivery-row"
                                data-transport="<?= $row['transport_id'] ?>"
                                data-product="<?= $row['product'] ?>"
                                data-origin="<?= htmlspecialchars($row['origin']) ?>"
                                data-destination="<?= htmlspecialchars($row['destination']) ?>"
                                data-temp="<?= $row['temp_range'] ?>"
                                data-humidity="<?= $row['humidity_range'] ?>"
                                data-lat-origin="<?= $originLat ?>"
                                data-lng-origin="<?= $originLng ?>"
                                data-lat-dest="<?= $destLat ?>"
                                data-lng-dest="<?= $destLng ?>"
                                data-status="<?= $row['status'] ?>"
                            >
                                <td><?= $row['transport_id'] ?></td>
                                <td><?= $row['origin'] ?> → <?= $row['destination'] ?></td>
                                <td><?= $row['temp_range'] ?></td>
                                <td><?= $row['humidity_range'] ?></td>
                                <td><?= $row['status'] ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- RIGHT MAP -->
        <div class="map-side">
            <div class="panel">
                <div class="panel-title">Transport Route Map</div>
                <div id="map"></div>
                <div id="routeInfo" style="margin-top:10px;font-size:0.85rem;"></div>
            </div>
        </div>

    </div>
</div>

<script>
// Initialize map
var map = L.map('map').setView([8.4542, 124.6319], 8);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

var originMarker, destMarker, routeLine;

// Click event to show route and info
document.querySelectorAll('.delivery-row').forEach(function(row){
    row.addEventListener('click', function(){
        var lat1 = parseFloat(this.dataset.latOrigin);
        var lng1 = parseFloat(this.dataset.lngOrigin);
        var lat2 = parseFloat(this.dataset.latDest);
        var lng2 = parseFloat(this.dataset.lngDest);

        if(originMarker) map.removeLayer(originMarker);
        if(destMarker) map.removeLayer(destMarker);
        if(routeLine) map.removeLayer(routeLine);

        originMarker = L.marker([lat1,lng1]).addTo(map).bindPopup(this.dataset.origin).openPopup();
        destMarker   = L.marker([lat2,lng2]).addTo(map).bindPopup(this.dataset.destination);

        routeLine = L.polyline([[lat1,lng1],[lat2,lng2]], {color:'green'}).addTo(map);
        map.fitBounds(routeLine.getBounds(), {padding:[50,50]});

        document.getElementById('routeInfo').innerHTML =
            `<b>${this.dataset.transport}</b> | Plant: ${this.dataset.product} <br>
            Route: ${this.dataset.origin} → ${this.dataset.destination} <br>
            Temp: ${this.dataset.temp} | Humidity: ${this.dataset.humidity} <br>
            Status: ${this.dataset.status}`;
    });
});
</script>

</body>
</html>