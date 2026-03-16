<?php
session_start();
include "../db.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != "Manager") {
    header("Location: ../index.php");
    exit();
}

// ── Fetch drivers from users table ───────────────────
$driverResult = $conn->query("SELECT id, name FROM users WHERE role = 'Driver' ORDER BY name ASC");
$drivers = [];
while ($row = $driverResult->fetch_assoc()) $drivers[] = $row;

// ── Handle form submission ────────────────────────────
$success = '';
$error   = '';

if (isset($_POST['create_delivery'])) {
    $transport_id = trim($_POST['transport_id']  ?? '');
    $date         = trim($_POST['delivery_date'] ?? '');
    $quantity     = (int)($_POST['quantity']     ?? 0);
    $origin       = trim($_POST['origin']        ?? '');
    $destination  = trim($_POST['destination']   ?? '');
    $driver       = trim($_POST['driver']        ?? '');
    $plate        = trim($_POST['vehicle_plate'] ?? '');
    $priority     = $_POST['priority']           ?? 'Medium';
    $notes        = trim($_POST['notes']         ?? '');
    $product      = "Tomato";

    if (!$transport_id || !$date || !$quantity || !$origin || !$destination || !$driver) {
        $error = 'Please fill in all required fields including driver.';
    } elseif ($origin === $destination) {
        $error = 'Origin and destination cannot be the same.';
    } else {
        $stmt = $conn->prepare("
            INSERT INTO deliveries
                (transport_id, product, quantity, delivery_date, origin, destination,
                 driver, vehicle_plate, priority, notes)
            VALUES (?,?,?,?,?,?,?,?,?,?)
        ");
        $stmt->bind_param("ssisssssss",
            $transport_id, $product, $quantity, $date,
            $origin, $destination, $driver, $plate, $priority, $notes
        );
        if ($stmt->execute()) {
            $success = "Delivery $transport_id created and assigned to $driver.";
        } else {
            $error = 'Could not create delivery.';
        }
    }
}

// ── Fetch recent deliveries (exclude Delivered) ───────
$deliveries = $conn->query("
    SELECT * FROM deliveries
    WHERE status != 'Delivered'
    ORDER BY created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GreenGrow · Create Delivery</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Fraunces:opsz,wght@9..144,300;9..144,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/delivery.css">
    <style>
        .alert { padding:10px 14px; border-radius:8px; font-size:.78rem; margin-bottom:16px; display:flex; align-items:center; gap:8px; }
        .alert-success { background:#dcfce7; color:#166534; border:1px solid #86efac; }
        .alert-error   { background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; }

        /* driver select styling */
        .driver-select-wrap { display:flex; align-items:center; gap:8px; }
        .driver-avatar { width:28px; height:28px; border-radius:50%; background:var(--green-dim); border:1.5px solid var(--green); display:flex; align-items:center; justify-content:center; font-size:.75rem; font-weight:600; color:var(--green); flex-shrink:0; }

        /* no drivers warning */
        .no-driver-warn { background:#fef3c7; border:1px solid #fcd34d; border-radius:8px; padding:10px 14px; font-size:.75rem; color:#92400e; display:flex; align-items:center; gap:8px; }

        /* status badge variants */
        .badge-transit  { background:#fef3c7; color:#92400e;  border:1px solid #fcd34d; }
        .badge-pending  { background:var(--surface2); color:var(--muted); border:1px solid var(--border); }
        .badge-delayed  { background:#fee2e2; color:#991b1b;  border:1px solid #fca5a5; }
        .badge-safe     { background:#dcfce7; color:#166534;  border:1px solid #86efac; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="logo">🌿 GreenGrow<span>Farm Management</span></div>
    <a href="manager.php"          class="nav-btn">Monitoring</a>
    <a href="datalogsmanager.php"  class="nav-btn">Data Logs</a>
    <a href="transportmanager.php" class="nav-btn">Transport Logs</a>
    <a href="create_delivery.php"  class="nav-btn active">Create Delivery</a>
    <a href="usermanager.php" class="nav-btn">Users</a>
    <div class="sidebar-foot">
        <div class="user-card">
            <div class="user-avatar"><?= strtoupper($_SESSION['name'][0]) ?></div>
            <div class="user-meta">
                <div class="user-name"><?= htmlspecialchars($_SESSION['name']) ?></div>
                <div class="user-role role-manager"><?= htmlspecialchars($_SESSION['role']) ?></div>
            </div>
        </div>
        <form method="POST" action="../logout.php" style="margin:0">
            <button class="logout-btn">↩ Logout</button>
        </form>
    </div>
</div>

<div class="main">
    <div class="page-header">
        <h1>Create Delivery</h1>
        <p>Schedule and dispatch a new transport order</p>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="panel">
        <div class="panel-title">Delivery Details</div>

        <form method="POST">

            <div class="form-grid">
                <div class="form-field">
                    <label>Transport ID <span style="color:var(--red)">*</span></label>
                    <input type="text" name="transport_id"
                           value="<?= htmlspecialchars($_POST['transport_id'] ?? '') ?>"
                           placeholder="e.g. TR-006"
                           oninput="this.value=this.value.toUpperCase()" required>
                </div>

                <div class="form-field">
                    <label>Delivery Date <span style="color:var(--red)">*</span></label>
                    <input type="date" name="delivery_date"
                           value="<?= htmlspecialchars($_POST['delivery_date'] ?? date('Y-m-d')) ?>" required>
                </div>

                <div class="form-field">
                    <label>Plant / Product</label>
                    <input type="text" value="🍅 Tomato" readonly
                           style="background:#f0fbf2;border-color:#a7d9b0;color:var(--green);cursor:default;">
                </div>

                <div class="form-field">
                    <label>Quantity (plastic crates) <span style="color:var(--red)">*</span></label>
                    <input type="number" name="quantity" min="1" step="1"
                           value="<?= htmlspecialchars($_POST['quantity'] ?? '') ?>"
                           placeholder="e.g. 20" required>
                </div>
            </div>

            <div class="section-label">Route</div>
            <div class="form-grid">
                <?php
                $locations  = ['Farm','CDO Market','Davao Market','Butuan Market','Iligan Market'];
                $selOrigin  = $_POST['origin']      ?? '';
                $selDest    = $_POST['destination'] ?? '';
                ?>
                <div class="form-field">
                    <label>Origin <span style="color:var(--red)">*</span></label>
                    <select name="origin" required>
                        <option value="">— Select Origin —</option>
                        <?php foreach ($locations as $loc): ?>
                        <option value="<?= $loc ?>" <?= $selOrigin===$loc?'selected':'' ?>><?= $loc ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field">
                    <label>Destination <span style="color:var(--red)">*</span></label>
                    <select name="destination" required>
                        <option value="">— Select Destination —</option>
                        <?php foreach ($locations as $loc): ?>
                        <option value="<?= $loc ?>" <?= $selDest===$loc?'selected':'' ?>><?= $loc ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="section-label">Conditions</div>
            <div class="form-grid">
                <div class="form-field">
                    <label>Temp Range (°C)</label>
                    <input type="text" value="15 - 30" readonly
                           style="background:#f0fbf2;border-color:#a7d9b0;color:var(--green);cursor:default;">
                </div>
                <div class="form-field">
                    <label>Humidity Range (%)</label>
                    <input type="text" value="50 - 60" readonly
                           style="background:#f0fbf2;border-color:#a7d9b0;color:var(--green);cursor:default;">
                </div>

                <!-- Driver dropdown from users table -->
                <div class="form-field">
                    <label>Driver <span style="color:var(--red)">*</span></label>
                    <?php if (empty($drivers)): ?>
                    <div class="no-driver-warn">
                        ⚠ No drivers found. Add driver accounts in
                        <a href="accounts_manager.php" style="color:#92400e;font-weight:500;">User Management</a>.
                    </div>
                    <input type="hidden" name="driver" value="">
                    <?php else: ?>
                    <select name="driver" required>
                        <option value="">— Select Driver —</option>
                        <?php foreach ($drivers as $d): ?>
                        <option value="<?= htmlspecialchars($d['name']) ?>"
                            <?= (($_POST['driver'] ?? '') === $d['name']) ? 'selected' : '' ?>>
                            🚛 <?= htmlspecialchars($d['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>
                </div>

                <div class="form-field">
                    <label>Vehicle Plate</label>
                    <input type="text" name="vehicle_plate"
                           value="<?= htmlspecialchars($_POST['vehicle_plate'] ?? '') ?>"
                           placeholder="e.g. ABC 1234"
                           oninput="this.value=this.value.toUpperCase()">
                </div>
            </div>

            <div class="section-label">Priority</div>
            <div class="priority-group">
                <?php $selPrio = $_POST['priority'] ?? 'Medium'; ?>
                <input class="priority-option" type="radio" name="priority" id="prio-high"   value="High"   <?= $selPrio==='High'  ?'checked':'' ?>>
                <label class="priority-label" for="prio-high">🔴 High</label>

                <input class="priority-option" type="radio" name="priority" id="prio-medium" value="Medium" <?= $selPrio==='Medium'?'checked':'' ?>>
                <label class="priority-label" for="prio-medium">🟡 Medium</label>

                <input class="priority-option" type="radio" name="priority" id="prio-low"    value="Low"    <?= $selPrio==='Low'   ?'checked':'' ?>>
                <label class="priority-label" for="prio-low">🟢 Low</label>
            </div>

            <div class="section-label">Additional Notes</div>
            <div class="form-field">
                <textarea name="notes" placeholder="Special handling instructions, cold chain requirements…"><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
            </div>

            <div class="btn-row">
                <button type="submit" name="create_delivery" class="btn-primary">
                    + Create Delivery
                </button>
            </div>

        </form>
    </div>

    <!-- Recent Deliveries (excluding Delivered) -->
    <div class="panel">
        <div class="panel-title">
            Recent Deliveries
            <span style="font-size:.7rem;color:var(--muted);text-transform:none;letter-spacing:0;margin-left:4px;">active only</span>
        </div>

        <div class="delivery-list">

            <?php if ($deliveries->num_rows == 0): ?>
            <div class="empty-state">
                <div class="empty-icon">📦</div>
                <p>No active deliveries yet</p>
            </div>
            <?php else: ?>

            <?php while ($row = $deliveries->fetch_assoc()):
                $status = $row['status'] ?? 'Pending';
                $sc = match(strtolower($status)) {
                    'in transit' => 'badge-transit',
                    'delayed'    => 'badge-delayed',
                    default      => 'badge-pending',
                };
                $pc = match($row['priority'] ?? 'Medium') {
                    'High'   => '#dc2626',
                    'Low'    => '#1a9e3f',
                    default  => '#d97706',
                };
            ?>
            <div class="delivery-item">
                <div class="delivery-item-icon">🚛</div>
                <div class="delivery-item-info">
                    <div class="delivery-item-title">
                        <?= htmlspecialchars($row['product']) ?> ·
                        <?= htmlspecialchars($row['origin']) ?> → <?= htmlspecialchars($row['destination']) ?>
                    </div>
                    <div class="delivery-item-sub">
                        <?= (int)$row['quantity'] ?> crates ·
                        🚛 <?= htmlspecialchars($row['driver'] ?? '—') ?> ·
                        📅 <?= htmlspecialchars($row['delivery_date']) ?>
                    </div>
                </div>
                <div class="delivery-item-meta">
                    <div class="delivery-item-id" style="font-weight:600;">
                        <?= htmlspecialchars($row['transport_id']) ?>
                    </div>
                    <div style="display:flex;gap:5px;margin-top:4px;justify-content:flex-end;flex-wrap:wrap;">
                        <span class="badge <?= $sc ?>"><?= htmlspecialchars($status) ?></span>
                        <span class="badge" style="color:<?= $pc ?>;border-color:<?= $pc ?>22;background:<?= $pc ?>11;">
                            <?= htmlspecialchars($row['priority'] ?? 'Medium') ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
            <?php endif; ?>

        </div>
    </div>

</div>

</body>
</html>