<?php
session_start();
include "../db.php";

if(!isset($_SESSION['role']) || $_SESSION['role'] != "Manager"){
    header("Location: ../index.php");
    exit();
}

$query  = "SELECT * FROM transport_monitoring";
$result = $conn->query($query);
$transports = [];

while($row = $result->fetch_assoc()){
    $transports[] = [
        "id"          => $row['transport_id'],
        "product"     => $row['product'],
        "route"       => $row['origin'] . " → " . $row['destination'],
        "status"      => $row['status'],
        "driver"      => $row['driver'],
        "quantity"    => $row['quantity'],
        "tempMin"     => $row['temp_min'],
        "tempMax"     => $row['temp_max'],
        "humidityMin" => $row['humidity_min'],
        "humidityMax" => $row['humidity_max']
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
    <a href="manager.php"          class="nav-btn active">Monitoring</a>
    <a href="datalogsmanager.php"  class="nav-btn">Data Logs</a>
    <a href="transportmanager.php" class="nav-btn">Transport Logs</a>
    <a href="create_delivery.php"  class="nav-btn">Create Delivery</a>
    <a href="accounts_manager.php" class="nav-btn">Users</a>
    <div class="sidebar-foot">
        <div class="user-card">
            <div class="user-avatar"><?= strtoupper($_SESSION['name'][0]) ?></div>
            <div class="user-meta">
                <div class="user-name"><?= htmlspecialchars($_SESSION['name']) ?></div>
                <div class="user-role"><?= htmlspecialchars($_SESSION['role']) ?></div>
            </div>
        </div>
        <form method="POST" action="../logout.php">
            <button class="logout-btn" type="submit">↩ Logout</button>
        </form>
    </div>
</div>

<div class="main">
    <div class="page-header">
        <h1>Monitoring Dashboard</h1>
        <p>Real-time farm &amp; transport sensor data</p>
    </div>

    <!-- Plant + Automation -->
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

        <div class="alert-bar" id="alertBar">⚠ Sensor values out of safe range — check transport conditions</div>

        <div class="transport-layout">

            <div class="transport-card-col" id="transportCards"></div>

            <div class="detail-panel" id="detailPanel">
                <!-- Dual-axis line graph -->
                <div class="graph-wrap" style="margin-bottom:1rem;">
                    <div class="graph-header">
                        <span class="graph-title">Sensor History</span>
                        <div class="graph-legend">
                            <span><span class="legend-dot" style="background:#1a9e3f"></span>Temp (°C)</span>
                            <span><span class="legend-dot" style="background:#3b82f6"></span>Humidity (%)</span>
                        </div>
                    </div>
                    <!-- safe-range reference lines shown below canvas -->
                    <div style="font-size:.62rem;color:var(--muted);margin-bottom:6px;display:flex;gap:16px;" id="chartRangeHint">
                        <span>🌡 Safe: <b id="hint-temp">—</b></span>
                        <span>💧 Safe: <b id="hint-hum">—</b></span>
                    </div>
                    <canvas id="sensorChart" style="display:block;width:100%;height:160px;"></canvas>
                </div>

                <div class="detail-col-title">Transport Details</div>
                <div class="info-row"><span>ID</span><b id="d-id"></b></div>
                <div class="info-row"><span>Product</span><b id="d-product"></b></div>
                <div class="info-row"><span>Route</span><b id="d-route"></b></div>
                <div class="info-row"><span>Status</span><b id="d-status"></b></div>
                <div class="info-row"><span>Driver</span><b id="d-driver"></b></div>
                <div class="info-row"><span>Quantity</span><b id="d-quantity"></b></div>

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
    </div>
</div>

<script>

// USER
(function() {
    try {
        const u = JSON.parse(localStorage.getItem("loggedUser") || "{}");
        document.getElementById("userAvatar").textContent = (u.name||"?").charAt(0).toUpperCase();
        document.getElementById("userName").textContent   = u.name || "Unknown";
        const r = document.getElementById("userRole");
        r.textContent = u.role || "";
        r.className   = "user-role role-" + (u.role||"").toLowerCase();
    } catch(e) {}
})();

function logout() {
    if (confirm("Are you sure you want to logout?")) {
        localStorage.removeItem("loggedUser");
        window.location.href = "../index.html";
    }
}


// TRANSPORT DATA
const builtIn = [
    { id:"TR-001", product:"Tomatoes", route:"Farm → Davao Market",  status:"In Transit", driver:"Pedro Santos", quantity:"15 crates", tempMin:15, tempMax:30, humidityMin:50, humidityMax:60 },
    { id:"TR-002", product:"Tomatoes", route:"Farm → CDO Market",    status:"Arrived",    driver:"Juan Reyes",   quantity:"22 crates", tempMin:15, tempMax:30, humidityMin:50, humidityMax:60 },
    { id:"TR-003", product:"Tomatoes", route:"Farm → Butuan Market", status:"In Transit", driver:"Mario Cruz",   quantity:"18 crates", tempMin:15, tempMax:30, humidityMin:50, humidityMax:60 },
];

let list = builtIn;
let selectedIdx = null;
let interval = null;

const maxPts = 20;
let tHistory = [];
let hHistory = [];

let tempWarning = false;
let humWarning  = false;


// RENDER CARDS
function renderCards() {

    const c = document.getElementById("transportCards");
    c.innerHTML = "";

    list.forEach((t,i)=>{

        const isSel = selectedIdx === i;

        const sc = t.status === "In Transit"
            ? "transit"
            : t.status === "Arrived"
            ? "arrived"
            : "pending";

        const div = document.createElement("div");

        div.className = "transport-card" + (isSel ? " selected":"");

        div.onclick = () => selectCard(i);

        div.innerHTML = `
        <div class="tc-header">
            <span class="tc-id">${t.id}</span>
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

        </div>
        `;

        c.appendChild(div);

    });

}



// SELECT CARD
function selectCard(i){

    stopMonitor();

    selectedIdx = i;

    const t = list[i];

    tHistory=[];
    hHistory=[];

    document.getElementById("detailPanel").classList.add("show");
    document.getElementById("detailIdle").style.display="none";

    document.getElementById("d-id").textContent = t.id;
    document.getElementById("d-product").textContent = t.product;
    document.getElementById("d-route").textContent = t.route;
    document.getElementById("d-driver").textContent = t.driver;
    document.getElementById("d-quantity").textContent = t.quantity;

    document.getElementById("d-temprange").textContent =
        t.tempMin + " – " + t.tempMax + " °C";

    document.getElementById("d-humrange").textContent =
        t.humidityMin + " – " + t.humidityMax + " %";

    startMonitor(t,i);

}



// SENSOR MONITOR
function startMonitor(t,i){

    tick(t,i);

    interval = setInterval(()=>{

        tick(t,i);

    },3000);

}


function stopMonitor(){

    if(interval){

        clearInterval(interval);
        interval=null;

    }

}



// SENSOR TICK
function tick(t,i){

    let temp = rnd(t.tempMin-5, t.tempMax+5);
    let hum  = rnd(t.humidityMin-5, t.humidityMax+5);

    if(temp > 40) temp = 40;
    if(hum  > 80) hum  = 80;

    const ventOn = temp > t.tempMax;

    const tOk = temp >= t.tempMin && temp <= t.tempMax;
    const hOk = hum  >= t.humidityMin && hum <= t.humidityMax;

    tempWarning = !tOk;
    humWarning  = !hOk;

    updateSensor("ct-"+i, temp+"°C", tOk);
    updateSensor("ch-"+i, hum+"%", hOk);

    const vent = document.getElementById("cv-"+i);

    if(vent){

        vent.textContent = ventOn ? "ON" : "OFF";
        vent.className = "tc-sensor-value "+(ventOn?"warn":"ok");

    }

    document.getElementById("d-temp").innerHTML =
        tOk ? green(temp+" °C") : red(temp+" °C");

    document.getElementById("d-humidity").innerHTML =
        hOk ? green(hum+" %") : red(hum+" %");

    document.getElementById("d-vent").innerHTML =
        ventOn ? red("ON") : green("OFF");

    document.getElementById("alertBar")
        .classList.toggle("show", tempWarning || humWarning);

    tHistory.push(temp);
    hHistory.push(hum);

    if(tHistory.length>maxPts){

        tHistory.shift();
        hHistory.shift();

    }

    drawChart();

}



function updateSensor(id,val,ok){

    const e=document.getElementById(id);

    if(!e) return;

    e.textContent=val;

    e.className="tc-sensor-value "+(ok?"ok":"warn");

}

function green(v){ return `<b style="color:var(--green)">${v}</b>`; }
function red(v){ return `<b style="color:var(--red)">${v}</b>`; }

function rnd(a,b){

    return Math.floor(Math.random()*(b-a+1))+a;

}



// CHART
function drawChart(){

    const canvas = document.getElementById("sensorChart");
    const ctx = canvas.getContext("2d");

    const W = canvas.offsetWidth || 600;
    const H = 160;

    canvas.width = W;
    canvas.height = H;

    ctx.fillStyle = "#edf7ee";
    ctx.fillRect(0,0,W,H);

    const pad = {t:12,r:16,b:28,l:40};

    const iW = W - pad.l - pad.r;
    const iH = H - pad.t - pad.b;

    const minV = 0;
    const maxV = 100;
    const rng = maxV - minV;

    const n = tHistory.length;

    const xp = i => pad.l + (i/(n-1)) * iW;
    const yp = v => pad.t + iH - ((v-minV)/rng)*iH;

    // GRID + NUMBERS
    ctx.strokeStyle = "#cbd5e1";
    ctx.fillStyle = "#475569";
    ctx.font = "10px sans-serif";

    const steps = 5;

    for(let i=0;i<=steps;i++){

        const val = minV + (rng/steps)*i;
        const y = yp(val);

        ctx.beginPath();
        ctx.moveTo(pad.l, y);
        ctx.lineTo(W-pad.r, y);
        ctx.stroke();

        ctx.fillText(Math.round(val), 5, y+3);
    }


    // HUMIDITY LINE
    drawLine(
        hHistory,
        humWarning ? "#ff0000" : "#3b82f6",
        "rgba(59,130,246,0.1)"
    );

    // TEMP LINE
    drawLine(
        tHistory,
        tempWarning ? "#ff0000" : "#1a9e3f",
        "rgba(26,158,63,0.1)"
    );


    function drawLine(data,color,fill){

        if(data.length < 2) return;

        ctx.beginPath();

        data.forEach((v,i)=>{
            if(i==0) ctx.moveTo(xp(i),yp(v));
            else ctx.lineTo(xp(i),yp(v));
        });

        ctx.strokeStyle = color;
        ctx.lineWidth = 3;
        ctx.stroke();

        ctx.lineTo(xp(data.length-1), pad.t+iH);
        ctx.lineTo(xp(0), pad.t+iH);
        ctx.closePath();

        ctx.fillStyle = fill;
        ctx.fill();
    }


}
renderCards();

</script>
</body>
</html>