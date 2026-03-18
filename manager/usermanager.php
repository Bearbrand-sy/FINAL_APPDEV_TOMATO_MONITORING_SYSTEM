<?php
session_start();
include "../db.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != "Manager") {
    header("Location: ../index.php");
    exit();
}

$success = '';
$error   = '';

// ── ADD USER ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $name    = trim($_POST['name']     ?? '');
    $email   = trim($_POST['email']    ?? '');
    $contact = trim($_POST['contact']  ?? '');
    $role    = $_POST['role']          ?? 'User';
    $pass    = trim($_POST['password'] ?? '');

    if (!$name || !$email || !$contact || !$pass) {
        $error = 'Please fill in all fields including password.';
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = 'Email already exists.';
        } else {

            // ✅ ONLY CHANGE: HASH PASSWORD
            $hashedPassword = password_hash($pass, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $hashedPassword, $role);

            if ($stmt->execute()) {
                $success = "User \"$name\" added successfully as $role.";
            } else {
                $error = 'Could not add user.';
            }

            $stmt->close();
        }
        $check->close();
    }
}

// ── DELETE USER ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int)($_POST['del_id'] ?? 0);
    if ($id > 0) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $success = 'User deleted successfully.';
    }
}

// ── FETCH ALL USERS ───────────────────────────────────
$result = $conn->query("SELECT id, name, email, role FROM users ORDER BY id ASC");
$users  = [];
while ($row = $result->fetch_assoc()) $users[] = $row;

// ── Count by role ─────────────────────────────────────
$counts = ['Admin'=>0,'Manager'=>0,'User'=>0,'Driver'=>0];
foreach ($users as $u) {
    if (isset($counts[$u['role']])) $counts[$u['role']]++;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GreenGrow · Users</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Fraunces:opsz,wght@9..144,300;9..144,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/manager.css">
    <style>
        /* summary stat cards */
        .stat-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:22px; }
        .stat-card { background:var(--surface); border:1px solid var(--border); border-radius:12px; padding:16px 14px; text-align:center; box-shadow:0 2px 8px #0d271510; }
        .stat-icon  { font-size:1.3rem; margin-bottom:5px; }
        .stat-label { font-size:.6rem; letter-spacing:.1em; text-transform:uppercase; color:var(--muted); margin-bottom:3px; }
        .stat-value { font-family:'Fraunces',serif; font-size:1.8rem; font-weight:300; color:var(--text); }
        .stat-value.purple { color:#7c3aed; }
        .stat-value.green  { color:var(--green); }
        .stat-value.blue   { color:#3b82f6; }
        .stat-value.amber  { color:var(--amber); }

        /* role badges */
        .role-badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:.68rem; letter-spacing:.06em; text-transform:uppercase; font-weight:500; }
        .role-badge.admin   { background:#ede9fe; color:#5b21b6; border:1px solid #c4b5fd; }
        .role-badge.manager { background:#dcfce7; color:#166534; border:1px solid #86efac; }
        .role-badge.user    { background:var(--surface2); color:var(--muted); border:1px solid var(--border); }
        .role-badge.driver  { background:#fef3c7; color:#92400e; border:1px solid #fcd34d; }

        /* alerts */
        .alert { padding:10px 14px; border-radius:8px; font-size:.78rem; margin-bottom:16px; display:flex; align-items:center; gap:8px; }
        .alert-success { background:#dcfce7; color:#166534; border:1px solid #86efac; }
        .alert-error   { background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; }

        /* driver info box */
        .driver-info {
            background: #fef9e7;
            border: 1px solid #fcd34d;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: .75rem;
            color: #92400e;
            margin-top: 12px;
            display: none;
            align-items: flex-start;
            gap: 8px;
            line-height: 1.6;
        }
        .driver-info.show { display: flex; }

        /* delete button */
        .btn-danger { padding:5px 12px; border-radius:6px; border:1px solid #fca5a5; background:transparent; color:var(--red); font-family:'DM Mono',monospace; font-size:.7rem; cursor:pointer; transition:all .2s; }
        .btn-danger:hover { background:#fee2e2; }

        /* eye toggle fix */
        .pw-wrap { position:relative; }
        .pw-wrap input { width:100%; padding-right:38px; box-sizing:border-box; }
        .eye-btn { position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; padding:0; color:var(--muted); font-size:1rem; line-height:1; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="logo">🌿 GreenGrow<span>Farm Management</span></div>
    <a href="manager.php"          class="nav-btn">Monitoring</a>
    <a href="datalogsmanager.php"  class="nav-btn">Data Logs</a>
    <a href="transportmanager.php" class="nav-btn">Transport Logs</a>
    <a href="create_delivery.php"  class="nav-btn">Create Delivery</a>
    <a href="accounts_manager.php" class="nav-btn active">Users</a>
    <div class="sidebar-foot">
        <div class="user-card">
            <div class="user-avatar"><?= strtoupper($_SESSION['name'][0]) ?></div>
            <div class="user-meta">
                <div class="user-name"><?= htmlspecialchars($_SESSION['name']) ?></div>
                <div class="user-role role-manager"><?= htmlspecialchars($_SESSION['role']) ?></div>
            </div>
        </div>
        <form method="POST" action="../logout.php" style="margin:0">
            <button type="submit" class="logout-btn">↩ Logout</button>
        </form>
    </div>
</div>

<div class="main">
    <div class="page-header">
        <h1>User Management</h1>
        <p>Manage accounts and roles</p>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Summary stats -->
    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-icon">👑</div>
            <div class="stat-label">Admins</div>
            <div class="stat-value purple"><?= $counts['Admin'] ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🧑‍💼</div>
            <div class="stat-label">Managers</div>
            <div class="stat-value green"><?= $counts['Manager'] ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">👤</div>
            <div class="stat-label">Users</div>
            <div class="stat-value blue"><?= $counts['User'] ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🚛</div>
            <div class="stat-label">Drivers</div>
            <div class="stat-value amber"><?= $counts['Driver'] ?></div>
        </div>
    </div>

    <!-- Add User -->
    <div class="panel">
        <div class="panel-title">Add User</div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-grid">
                <div class="form-field">
                    <label>Full Name</label>
                    <input type="text" name="name" id="nameInput"
                           placeholder="Name Surname"
                           value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                </div>
                <div class="form-field">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="name@email.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div class="form-field">
                    <label>Contact Number</label>
                    <input type="text" name="contact" placeholder="09xxxxxxxxx"
                           value="<?= htmlspecialchars($_POST['contact'] ?? '') ?>">
                </div>
                <div class="form-field">
                    <label>Password</label>
                    <div class="pw-wrap">
                        <input type="password" name="password" id="passwordInput"
                               placeholder="Enter password">
                        <button type="button" class="eye-btn" id="eyeBtn"
                                onclick="togglePassword()" title="Show / hide password">
                            👁
                        </button>
                    </div>
                </div>
                <div class="form-field">
                    <label>Role</label>
                    <select name="role" id="roleSelect" onchange="onRoleChange(this.value)">
                        <option value="User"    <?= (($_POST['role'] ?? '') === 'User')    ? 'selected' : '' ?>>User</option>
                        <option value="Manager" <?= (($_POST['role'] ?? '') === 'Manager') ? 'selected' : '' ?>>Manager</option>
                        <option value="Driver"  <?= (($_POST['role'] ?? '') === 'Driver')  ? 'selected' : '' ?>>Driver</option>
                    </select>
                </div>
            </div>

            <!-- Driver reminder — shown only when Driver role is selected -->
            <div class="driver-info" id="driverInfo">
                🚛 <div>
                    <b>Driver account note:</b> The <b>Full Name</b> you enter must
                    exactly match the <b>Driver</b> column in the deliveries table
                    (e.g. <code>SANDER PEREJAN</code>). This is how the system links
                    the driver account to their assigned deliveries.
                </div>
            </div>

            <button type="submit" class="btn-primary" style="margin-top:16px;">+ Add User</button>
        </form>
    </div>

    <!-- User Table -->
    <div class="panel">
        <div class="panel-title">
            Accounts
            <span style="font-size:.7rem;color:var(--muted);text-transform:none;letter-spacing:0;margin-left:4px;">(<?= count($users) ?> total)</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="5" style="text-align:center;padding:24px;color:var(--muted);font-size:.78rem;">
                            No users found.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($users as $i => $u): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($u['name'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td>
                            <span class="role-badge <?= strtolower($u['role']) ?>">
                                <?= htmlspecialchars($u['role']) ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" style="display:inline"
                                  onsubmit="return confirm('Delete <?= htmlspecialchars(addslashes($u['name'])) ?>?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="del_id" value="<?= $u['id'] ?>">
                                <button type="submit" class="btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const input = document.getElementById("passwordInput");
    const btn   = document.getElementById("eyeBtn");
    const show  = input.type === "password";
    input.type      = show ? "text" : "password";
    btn.textContent = show ? "👁" : "👁";
    btn.title       = show ? "Hide password" : "Show password";
}

function onRoleChange(role) {
    const info = document.getElementById("driverInfo");
    if (role === "Driver") {
        info.classList.add("show");
    } else {
        info.classList.remove("show");
    }
}

// Show on page load if Driver was already selected (e.g. after form error)
document.addEventListener("DOMContentLoaded", function() {
    const role = document.getElementById("roleSelect").value;
    onRoleChange(role);
});
</script>
</body>
</html>