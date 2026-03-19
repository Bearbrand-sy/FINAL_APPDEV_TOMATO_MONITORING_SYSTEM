<?php
session_start();
include "../db.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != "Admin") {
    header("Location: ../index.php");
    exit();
}

// ── Fetch active deliveries from DB ──────────────────
$result = $conn->query("
    SELECT
        d.id            AS delivery_id,
        d.transport_id,
        d.product,
        d.origin,
        d.destination,
        d.status,
        d.driver,
        d.quantity,
        d.priority
    FROM deliveries d
    WHERE d.status IN ('Pending', 'In Transit')
    ORDER BY FIELD(d.status, 'In Transit', 'Pending'), d.delivery_date ASC
");

$transports = [];
while ($row = $result->fetch_assoc()) {
    $transports[] = [
        "deliveryId"  => (int)$row['delivery_id'],
        "id"          => $row['transport_id'],
        "product"     => $row['product'],
        "route"       => $row['origin'] . " → " . $row['destination'],
        "status"      => $row['status'],
        "driver"      => $row['driver'],
        "quantity"    => $row['quantity'] . " crates",
        "priority"    => $row['priority'],
        "tempMin"     => 15,
        "tempMax"     => 30,
        "humidityMin" => 50,
        "humidityMax" => 60,
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GreenGrow · Monitoring</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Fraunces:opsz,wght@9..144,300;9..144,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/manager.css">
</head>
<body>

<div class="sidebar">
    <div class="logo">🌿 GreenGrow<span>Farm Management</span></div>
    <a href="dashboard.php"    class="nav-btn active">Monitoring</a>
    <a href="datalogsadmin.php" class="nav-btn">Data Logs</a>
    <a href="transportlogsadmin.php"   class="nav-btn">Transport Logs</a>
    <a href="useradmin.php"    class="nav-btn">Users</a>
    <div class="sidebar-foot">
        <div class="user-card">
            <div class="user-avatar"><?= strtoupper($_SESSION['name'][0]) ?></div>
            <div class="user-meta">
                <div class="user-name"><?= htmlspecialchars($_SESSION['name']) ?></div>
                <div class="user-role role-admin"><?= htmlspecialchars($_SESSION['role']) ?></div>
            </div>
        </div>
        <form method="POST" action="../logout.php" style="margin:0">
            <button class="logout-btn" type="submit">↩ Logout</button>
        </form>
    </div>
</div>

<div class="main">
    <div class="page-header">
        <h1>Monitoring Dashboard</h1>
        <p>Real-time farm &amp; transport sensor data</p>
    </div>

    <!-- Overview -->
    <div class="panel">
        <div class="panel-title">Overview</div>
        <div class="two-col">
            <div class="col-block">
                <h3>Plant Details</h3>
                <div class="info-row"><span>Crop</span><b>Tomato</b></div>
                <div class="info-row"><span>Temp Range</span><b>15 – 30 °C</b></div>
                <div class="info-row"><span>Humidity Range</span><b>50 – 60 %</b></div>
            </div>
            <div class="col-block">
                <h3>Automation</h3>
                <div class="info-row"><span>Humidity Threshold</span><b>53 %</b></div>
                <div class="info-row"><span>Temp Limit</span><b>16 °C</b></div>
                <div class="info-row">
                    <span>Auto Ventilation</span>
                    <label class="switch"><input type="checkbox" checked><span class="slider"></span></label>
                </div>
            </div>
        </div>
    </div>

    <!-- Transport Monitoring -->
    <div class="panel">
        <div class="panel-title">
            Transport Monitoring
            <span id="globalLivePip" class="live-pip"></span>
        </div>

        <div class="alert-bar" id="alertBar">
            ⚠ Sensor values out of safe range — check transport conditions
        </div>

        <?php if (empty($transports)): ?>
        <div style="text-align:center;padding:48px;color:var(--muted);">
            <div style="font-size:2.5rem;opacity:.25;margin-bottom:12px;">🚛</div>
            <p style="font-size:.82rem;">No active deliveries at the moment.</p>
        </div>
        <?php else: ?>
        <div class="transport-layout">

            <!-- LEFT: delivery cards -->
            <div class="transport-card-col" id="transportCards"></div>

            <!-- RIGHT: detail panel -->
            <div class="detail-panel" id="detailPanel">
                <div class="graph-wrap" style="margin-bottom:1rem;">
                    <div class="graph-header">
                        <span class="graph-title">Sensor History</span>
                        <div class="graph-legend">
                            <span><span class="legend-dot" style="background:#1a9e3f"></span>Temp (°C)</span>
                            <span><span class="legend-dot" style="background:#3b82f6"></span>Humidity (%)</span>
                        </div>
                    </div>
                    <div style="font-size:.62rem;color:var(--muted);margin-bottom:6px;display:flex;gap:16px;">
                        <span>🌡 Safe: <b id="hint-temp">—</b></span>
                        <span>💧 Safe: <b id="hint-hum">—</b></span>
                    </div>
                    <canvas id="sensorChart" style="display:block;width:100%;"></canvas>
                </div>

                <div class="detail-col-title">Transport Details</div>
                <div class="info-row"><span>ID</span><b id="d-id"></b></div>
                <div class="info-row"><span>Product</span><b id="d-product"></b></div>
                <div class="info-row"><span>Route</span><b id="d-route"></b></div>
                <div class="info-row"><span>Status</span><b id="d-status"></b></div>
                <div class="info-row"><span>Driver</span><b id="d-driver"></b></div>
                <div class="info-row"><span>Quantity</span><b id="d-quantity"></b></div>
                <div class="info-row"><span>Priority</span><b id="d-priority"></b></div>

                <div class="detail-col-title" style="margin-top:14px;">Live Sensors</div>
                <div class="info-row"><span>Temperature</span><b id="d-temp">--</b></div>
                <div class="info-row"><span>Humidity</span><b id="d-humidity">--</b></div>
                <div class="info-row"><span>Ventilation</span><b id="d-vent">--</b></div>
                <div class="info-row"><span>Temp Range</span><b id="d-temprange"></b></div>
                <div class="info-row"><span>Humidity Range</span><b id="d-humrange"></b></div>
            </div>

            <div class="detail-idle" id="detailIdle">
                <div class="idle-icon">📋</div>
                <p>Select a delivery card to view live sensor data</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// ── Transport data from PHP/DB ─────────────────────────
const list = <?= json_encode($transports) ?>;

let selectedIdx = null;
let interval    = null;
let currentT    = null;
const maxPts    = 20;
let tHistory    = [];
let hHistory    = [];

// ── Render cards ──────────────────────────────────────
function renderCards() {
    const c = document.getElementById("transportCards");
    if (!c) return;
    c.innerHTML = "";

    list.forEach((t, i) => {
        const isSel = selectedIdx === i;
        const sc    = t.status === "In Transit" ? "transit"
                    : t.status === "Arrived"    ? "arrived" : "pending";

        const div = document.createElement("div");
        div.className = "transport-card" + (isSel ? " selected" : "");
        div.onclick   = () => selectCard(i);
        div.innerHTML = `
            <div class="tc-header">
                <span class="tc-id">${t.id}
                    <span class="live-pip${isSel ? " active" : ""}"></span>
                </span>
                <span class="tc-badge ${sc}">${t.status}</span>
            </div>
            <div class="tc-product">🍅 ${t.product}</div>
            <div class="tc-route">${t.route}</div>
            <div class="tc-sensors">
                <div class="tc-sensor">
                    <div class="tc-sensor-label">Temp</div>
                    <div class="tc-sensor-value" id="ct-${i}">--°C</div>
                </div>
                <div class="tc-sensor">
                    <div class="tc-sensor-label">Humidity</div>
                    <div class="tc-sensor-value" id="ch-${i}">--%</div>
                </div>
                <div class="tc-sensor">
                    <div class="tc-sensor-label">Vent</div>
                    <div class="tc-sensor-value" id="cv-${i}">--</div>
                </div>
            </div>`;
        c.appendChild(div);
    });
}

// ── Select card ───────────────────────────────────────
function selectCard(i) {
    if (selectedIdx === i) {
        selectedIdx = null; currentT = null; stopMonitor();
        document.getElementById("detailPanel").classList.remove("show");
        document.getElementById("detailIdle").style.display = "block";
        document.getElementById("alertBar").classList.remove("show");
        document.getElementById("globalLivePip").classList.remove("active");
        renderCards(); return;
    }

    stopMonitor();
    selectedIdx = i; currentT = list[i];
    tHistory = []; hHistory = [];

    const t = list[i];
    document.getElementById("d-id").textContent        = t.id;
    document.getElementById("d-product").textContent   = t.product;
    document.getElementById("d-route").textContent     = t.route;
    document.getElementById("d-driver").textContent    = t.driver;
    document.getElementById("d-quantity").textContent  = t.quantity;
    document.getElementById("d-priority").textContent  = t.priority;
    document.getElementById("d-temprange").textContent = t.tempMin + " – " + t.tempMax + " °C";
    document.getElementById("d-humrange").textContent  = t.humidityMin + " – " + t.humidityMax + " %";
    document.getElementById("hint-temp").textContent   = t.tempMin + "–" + t.tempMax + " °C";
    document.getElementById("hint-hum").textContent    = t.humidityMin + "–" + t.humidityMax + " %";

    const isTr = t.status === "In Transit";
    document.getElementById("d-status").innerHTML =
        `<span class="badge ${isTr ? "transit" : "pending"}">${t.status}</span>`;

    document.getElementById("detailPanel").classList.add("show");
    document.getElementById("detailIdle").style.display = "none";
    document.getElementById("globalLivePip").classList.add("active");

    renderCards();
    startMonitor(t, i);
}

// ── Sensor simulation ─────────────────────────────────
function startMonitor(t, i) { tick(t, i); interval = setInterval(() => tick(t, i), 3000); }
function stopMonitor()       { if (interval) { clearInterval(interval); interval = null; } }

function tick(t, i) {
    const temp   = rnd(t.tempMin - 5, t.tempMax + 5);
    const hum    = rnd(t.humidityMin - 6, t.humidityMax + 6);
    const ventOn = temp > t.tempMax;
    const tOk    = temp >= t.tempMin && temp <= t.tempMax;
    const hOk    = hum  >= t.humidityMin && hum <= t.humidityMax;

    // Update mini card sensors
    const sv = (id, val, ok) => {
        const e = document.getElementById(id);
        if (e) { e.textContent = val; e.className = "tc-sensor-value " + (ok ? "ok" : "warn"); }
    };
    sv("ct-" + i, temp + "°C", tOk);
    sv("ch-" + i, hum  + "%",  hOk);

    const ve = document.getElementById("cv-" + i);
    if (ve) { ve.textContent = ventOn ? "ON" : "OFF"; ve.className = "tc-sensor-value " + (ventOn ? "warn" : "ok"); }

    // Update detail panel
    const ok  = s => `<b style="color:var(--green)">${s}</b>`;
    const bad = s => `<b style="color:var(--red)">${s}</b>`;
    document.getElementById("d-temp").innerHTML     = tOk ? ok(temp + " °C") : bad(temp + " °C");
    document.getElementById("d-humidity").innerHTML = hOk ? ok(hum  + " %")  : bad(hum  + " %");
    document.getElementById("d-vent").innerHTML     = ventOn ? bad("ON") : ok("OFF");
    document.getElementById("alertBar").classList.toggle("show", !tOk || !hOk);

    // Save reading to sensor_logs
    fetch("../api/save_sensor.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            delivery_id: t.deliveryId,
            temperature: temp,
            humidity:    hum,
            ventilation: ventOn ? 1 : 0
        })
    }).catch(() => {});

    tHistory.push(temp); hHistory.push(hum);
    if (tHistory.length > maxPts) { tHistory.shift(); hHistory.shift(); }
    drawChart();
}

function rnd(a, b) { return Math.floor(Math.random() * (b - a + 1)) + a; }

// ── Chart ─────────────────────────────────────────────
function drawChart() {
    const canvas = document.getElementById("sensorChart");
    const ctx    = canvas.getContext("2d");
    const W      = canvas.parentElement ? canvas.parentElement.clientWidth - 28 : 480;
    const H      = 160;
    canvas.width  = W;
    canvas.height = H;
    canvas.style.width  = W + "px";
    canvas.style.height = H + "px";

    const pad = { t:12, r:16, b:28, l:36 };
    const iW  = W - pad.l - pad.r;
    const iH  = H - pad.t - pad.b;

    ctx.fillStyle = "#edf7ee";
    ctx.fillRect(0, 0, W, H);

    const all  = [...tHistory, ...hHistory, 0, 100];
    const minV = Math.max(0,   Math.min(...all) - 5);
    const maxV = Math.min(100, Math.max(...all) + 5);
    const rng  = maxV - minV || 1;
    const n    = Math.max(tHistory.length, 2);
    const xp   = i => pad.l + (i / (n - 1)) * iW;
    const yp   = v => pad.t + iH - ((v - minV) / rng) * iH;

    // Grid
    ctx.strokeStyle = "#d0e8d2"; ctx.lineWidth = 1;
    for (let g = 0; g <= 4; g++) {
        const y = pad.t + (g / 4) * iH;
        ctx.beginPath(); ctx.moveTo(pad.l, y); ctx.lineTo(W - pad.r, y); ctx.stroke();
        ctx.fillStyle = "#6b8f72"; ctx.font = "10px DM Mono,monospace"; ctx.textAlign = "right";
        ctx.fillText(Math.round(maxV - (g / 4) * rng), pad.l - 4, y + 4);
    }

    // Safe-range shaded bands
    if (currentT) {
        ctx.fillStyle = "rgba(26,158,63,0.08)";
        ctx.fillRect(pad.l, yp(currentT.tempMax), iW, yp(currentT.tempMin) - yp(currentT.tempMax));
        ctx.fillStyle = "rgba(59,130,246,0.07)";
        ctx.fillRect(pad.l, yp(currentT.humidityMax), iW, yp(currentT.humidityMin) - yp(currentT.humidityMax));
    }

    function line(data, color, fill) {
        if (data.length < 2) return;
        const last   = data[data.length - 1];
        const isWarn = currentT && (
            color === "#1a9e3f"
                ? (last < currentT.tempMin     || last > currentT.tempMax)
                : (last < currentT.humidityMin || last > currentT.humidityMax)
        );
        const lc = isWarn ? "#dc2626" : color;

        ctx.beginPath();
        data.forEach((v, i) => i === 0 ? ctx.moveTo(xp(i), yp(v)) : ctx.lineTo(xp(i), yp(v)));
        ctx.strokeStyle = lc; ctx.lineWidth = 2; ctx.lineJoin = "round"; ctx.stroke();

        ctx.lineTo(xp(data.length - 1), pad.t + iH);
        ctx.lineTo(xp(0), pad.t + iH); ctx.closePath();
        ctx.fillStyle = isWarn ? "rgba(220,38,38,0.08)" : fill;
        ctx.fill();

        const lx = xp(data.length - 1), ly = yp(last);
        ctx.beginPath(); ctx.arc(lx, ly, 4, 0, Math.PI * 2);
        ctx.fillStyle = lc; ctx.fill();
    }

    line(hHistory, "#3b82f6", "rgba(59,130,246,0.08)");
    line(tHistory, "#1a9e3f", "rgba(26,158,63,0.10)");

    ctx.fillStyle = "#6b8f72"; ctx.font = "9px DM Mono,monospace"; ctx.textAlign = "center";
    const step = Math.max(1, Math.floor(n / 5));
    for (let i = 0; i < n; i += step)
        ctx.fillText("t-" + (n - 1 - i), xp(i), H - pad.b + 14);
}

renderCards();
</script>
</body>
</html>