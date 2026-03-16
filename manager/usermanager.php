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
        // Check for duplicate email
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = 'Email already exists.';
        } else {
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $pass, $role);
            if ($stmt->execute()) {
                $success = "User \"$name\" added successfully.";
            } else {
                $error = 'Could not add user.';
            }
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
        /* role badges */
        .role-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: .68rem; letter-spacing: .06em; text-transform: uppercase; font-weight: 500; }
        .role-badge.admin   { background: #ede9fe; color: #5b21b6; border: 1px solid #c4b5fd; }
        .role-badge.manager { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .role-badge.user    { background: var(--surface2); color: var(--muted); border: 1px solid var(--border); }

        /* alert banners */
        .alert { padding: 10px 14px; border-radius: 8px; font-size: .78rem; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .alert-error   { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

        /* delete button */
        .btn-danger { padding: 5px 12px; border-radius: 6px; border: 1px solid #fca5a5; background: transparent; color: var(--red); font-family: 'DM Mono', monospace; font-size: .7rem; cursor: pointer; transition: all .2s; }
        .btn-danger:hover { background: #fee2e2; }
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
        <div class="alert alert-success">✅ <?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Add User -->
    <div class="panel">
        <div class="panel-title">Add User</div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-grid">
                <div class="form-field">
                    <label>Full Name</label>
                    <input type="text" name="name" placeholder="Name Surname"
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
                    <div style="position:relative;">
                        <input type="password" name="password" id="passwordInput"
                               placeholder="Enter password"
                               style="width:100%; padding-right:38px;">
                        <button type="button" onclick="togglePassword()"
                                id="eyeBtn"
                                style="position:absolute; right:10px; top:50%; transform:translateY(-50%);
                                       background:none; border:none; cursor:pointer; padding:0;
                                       color:var(--muted); font-size:1rem; line-height:1;"
                                title="Show / hide password">
                            👁
                        </button>
                    </div>
                </div>
                <div class="form-field">
                    <label>Role</label>
                    <select name="role">
                        <option value="User"    <?= (($_POST['role'] ?? '') === 'User')    ? 'selected' : '' ?>>User</option>
                        <option value="Manager" <?= (($_POST['role'] ?? '') === 'Manager') ? 'selected' : '' ?>>Manager</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn-primary">+ Add User</button>
        </form>
    </div>

    <!-- User Table -->
    <div class="panel">
        <div class="panel-title">Accounts</div>
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
                        <td colspan="5" style="text-align:center; padding:24px; color:var(--muted); font-size:.78rem;">
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
                                  onsubmit="return confirm('Delete <?= htmlspecialchars($u['name']) ?>?')">
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
    const input  = document.getElementById("passwordInput");
    const btn    = document.getElementById("eyeBtn");
    const isHidden = input.type === "password";
    input.type   = isHidden ? "text" : "password";
    btn.textContent = isHidden ? "👁" : "👁";
    btn.title    = isHidden ? "Hide password" : "Show password";
}
</script>
</body>
</html>