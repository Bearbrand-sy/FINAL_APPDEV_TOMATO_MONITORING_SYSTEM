<?php
session_start();
include "../db.php";

if(!isset($_SESSION['role']) || $_SESSION['role'] != "Manager"){
    header("Location: ../index.php");
    exit();
}

if(isset($_POST['create_delivery'])){

$transport_id = $_POST['transport_id'];
$date         = $_POST['delivery_date'];
$product      = "Tomato";
$quantity     = $_POST['quantity'];
$origin       = $_POST['origin'];
$destination  = $_POST['destination'];
$driver       = $_POST['driver'];
$plate        = $_POST['vehicle_plate'];
$priority     = $_POST['priority'];
$notes        = $_POST['notes'];

$stmt = $conn->prepare("INSERT INTO deliveries
(transport_id,product,quantity,delivery_date,origin,destination,driver,vehicle_plate,priority,notes)
VALUES (?,?,?,?,?,?,?,?,?,?)");

$stmt->bind_param("ssisssssss",
$transport_id,
$product,
$quantity,
$date,
$origin,
$destination,
$driver,
$plate,
$priority,
$notes
);

$stmt->execute();
}

$deliveries = $conn->query("SELECT * FROM deliveries ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GreenGrow · Create Delivery</title>

<link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Fraunces:opsz,wght@9..144,300;9..144,600&display=swap" rel="stylesheet">

<link rel="stylesheet" href="../assets/delivery.css">

</head>

<body>

<!-- SIDEBAR -->

<div class="sidebar">

<div class="logo">
🌿 GreenGrow
<span>Farm Management</span>
</div>

<a href="manager.php" class="nav-btn">Monitoring</a>
<a href="datalogsmanager.php" class="nav-btn">Data Logs</a>
<a href="transportmanager.php" class="nav-btn">Transport Logs</a>
<a href="create_delivery.php" class="nav-btn active">Create Delivery</a>
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

<!-- MAIN -->

<div class="main">

<div class="page-header">
<h1>Create Delivery</h1>
<p>Schedule and dispatch a new transport order</p>
</div>

<div class="panel">

<div class="panel-title">Delivery Details</div>

<form method="POST">

<div class="form-grid">

<div class="form-field">
<label>Transport ID</label>
<input type="text" name="transport_id" required>
</div>

<div class="form-field">
<label>Delivery Date</label>
<input type="date" name="delivery_date" required>
</div>

<div class="form-field">
<label>Plant / Product</label>
<input type="text" value="🍅 Tomato" readonly>
</div>

<div class="form-field">
<label>Quantity (plastic crates)</label>
<input type="number" name="quantity" required>
</div>

</div>

<div class="section-label">Route</div>

<div class="form-grid">

<div class="form-field">
<label>Origin</label>

<select name="origin" required>
<option value="">Select</option>
<option>Farm</option>
<option>CDO Market</option>
<option>Davao Market</option>
<option>Butuan Market</option>
<option>Iligan Market</option>
</select>

</div>

<div class="form-field">
<label>Destination</label>

<select name="destination" required>
<option value="">Select</option>
<option>Farm</option>
<option>CDO Market</option>
<option>Davao Market</option>
<option>Butuan Market</option>
<option>Iligan Market</option>
</select>

</div>

</div>

<div class="section-label">Conditions</div>

<div class="form-grid">

<div class="form-field">
<label>Temp Range (°C)</label>
<input type="text" value="15 - 30" readonly>
</div>

<div class="form-field">
<label>Humidity Range (%)</label>
<input type="text" value="50 - 60" readonly>
</div>

<div class="form-field">
<label>Driver Name</label>
<input type="text" name="driver">
</div>

<div class="form-field">
<label>Vehicle Plate</label>
<input type="text" name="vehicle_plate">
</div>

</div>

<div class="section-label">Priority</div>

<div class="priority-group">

<input class="priority-option" type="radio" name="priority" id="prio-high" value="High">
<label class="priority-label" for="prio-high">🔴 High</label>

<input class="priority-option" type="radio" name="priority" id="prio-medium" value="Medium" checked>
<label class="priority-label" for="prio-medium">🟡 Medium</label>

<input class="priority-option" type="radio" name="priority" id="prio-low" value="Low">
<label class="priority-label" for="prio-low">🟢 Low</label>

</div>

<div class="section-label">Additional Notes</div>

<div class="form-field">
<textarea name="notes"></textarea>
</div>

<div class="btn-row">

<button type="submit" name="create_delivery" class="btn-primary">
+ Create Delivery
</button>

</div>

</form>

</div>

<!-- DELIVERY LIST -->

<div class="panel">

<div class="panel-title">Recent Deliveries</div>

<div class="delivery-list">

<?php if($deliveries->num_rows == 0){ ?>

<div class="empty-state">
<div class="empty-icon">📦</div>
<p>No deliveries created yet</p>
</div>

<?php } ?>

<?php while($row = $deliveries->fetch_assoc()){ ?>

<div class="delivery-item">

<div class="delivery-item-icon">🚛</div>

<div class="delivery-item-info">

<div class="delivery-item-title">
<?= $row['product'] ?> · <?= $row['origin'] ?> → <?= $row['destination'] ?>
</div>

<div class="delivery-item-sub">
<?= $row['quantity'] ?> crates · Driver: <?= $row['driver'] ?>
</div>

</div>

<div class="delivery-item-meta">

<div class="delivery-item-id">
<?= $row['transport_id'] ?>
</div>

<span class="badge badge-pending">
<?= $row['status'] ?>
</span>

</div>

</div>

<?php } ?>

</div>

</div>

</div>

</body>
</html>