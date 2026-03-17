<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    $_SESSION['login_error'] = "Access denied. Please login as admin.";
    header("Location: ../index.php");
    exit;
}

ensure_audit_log_table($conn);

$allowed_roles = ['admin', 'doctor', 'patient'];
$selected_role = isset($_GET['actor_role']) ? trim($_GET['actor_role']) : '';
if (!in_array($selected_role, $allowed_roles, true)) {
    $selected_role = '';
}

$selected_action = isset($_GET['action_type']) ? trim($_GET['action_type']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';
$search_q = isset($_GET['q']) ? trim($_GET['q']) : '';

$available_actions = [];
$actions_result = $conn->query("SELECT DISTINCT `Action_Type` FROM `AUDIT_LOG` ORDER BY `Action_Type` ASC");
if ($actions_result) {
    while ($row = $actions_result->fetch_assoc()) {
        $available_actions[] = $row['Action_Type'];
    }
}

$logs = [];
$base_sql = "SELECT
            `Audit_ID`, `Actor_Role`, `Actor_ID`, `Action_Type`,
            `Target_Table`, `Target_ID`, `Details`, `Created_At`
        FROM `AUDIT_LOG`";

$where_clauses = [];
$params = [];
$types = '';

if ($selected_role !== '') {
    $where_clauses[] = "`Actor_Role` = ?";
    $types .= 's';
    $params[] = $selected_role;
}

if ($selected_action !== '') {
    $where_clauses[] = "`Action_Type` = ?";
    $types .= 's';
    $params[] = $selected_action;
}

if ($date_from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) {
    $where_clauses[] = "`Created_At` >= ?";
    $types .= 's';
    $params[] = $date_from . ' 00:00:00';
}

if ($date_to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to)) {
    $where_clauses[] = "`Created_At` <= ?";
    $types .= 's';
    $params[] = $date_to . ' 23:59:59';
}

if ($search_q !== '') {
    $where_clauses[] = "(`Details` LIKE ? OR `Target_Table` LIKE ? OR `Action_Type` LIKE ?)";
    $types .= 'sss';
    $like = '%' . $search_q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql = $base_sql;
if (!empty($where_clauses)) {
    $sql .= ' WHERE ' . implode(' AND ', $where_clauses);
}
$sql .= ' ORDER BY `Created_At` DESC, `Audit_ID` DESC LIMIT 200';

$stmt = $conn->prepare($sql);
if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Audit Logs</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="sidebar">
        <h2>Admin Menu</h2>
        <ul>
            <li><a href="add_doctor.php">Add New Doctor</a></li>
            <li><a href="add_nurse.php">Add New Nurse</a></li>
            <li><a href="add_patient.php">Add New Patient</a></li>
            <li><a href="find_patient_doctors.php">List Patient's Doctors</a></li>
            <li><a href="view_all_patients_appointments.php">View All Patients & Appointments</a></li>
            <li><a href="manage_appointments.php">Manage Appointments</a></li>
            <li><a href="reports.php">View Reports</a></li>
            <li><a href="audit_logs.php">View Audit Logs</a></li>
        </ul>
        <div class="logout-link">
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>Audit Log</h1>
        </div>

        <div class="content-section">
            <h2>Recent Activity (Last 200 Events)</h2>
            <form method="GET" action="audit_logs.php" style="margin-bottom: 16px;">
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px; align-items:end;">
                    <div>
                        <label for="actor_role"><strong>Role</strong></label>
                        <select id="actor_role" name="actor_role">
                            <option value="">All</option>
                            <option value="admin" <?php echo $selected_role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="doctor" <?php echo $selected_role === 'doctor' ? 'selected' : ''; ?>>Doctor</option>
                            <option value="patient" <?php echo $selected_role === 'patient' ? 'selected' : ''; ?>>Patient</option>
                        </select>
                    </div>
                    <div>
                        <label for="action_type"><strong>Action</strong></label>
                        <select id="action_type" name="action_type">
                            <option value="">All</option>
                            <?php foreach ($available_actions as $action): ?>
                                <option value="<?php echo htmlspecialchars($action); ?>" <?php echo $selected_action === $action ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($action); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="date_from"><strong>Date From</strong></label>
                        <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                    </div>
                    <div>
                        <label for="date_to"><strong>Date To</strong></label>
                        <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                    </div>
                    <div>
                        <label for="q"><strong>Search</strong></label>
                        <input type="text" id="q" name="q" placeholder="Details or action" value="<?php echo htmlspecialchars($search_q); ?>">
                    </div>
                    <div style="display:flex; gap:8px;">
                        <button type="submit">Apply</button>
                        <a href="audit_logs.php" style="display:inline-block; padding:10px 12px; border:1px solid #dbe2ef; border-radius:10px; text-decoration:none;">Clear</a>
                    </div>
                </div>
            </form>

            <?php if (empty($logs)): ?>
                <p>No audit entries yet.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Actor</th>
                            <th>Action</th>
                            <th>Target</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($log['Created_At']); ?></td>
                                <td><?php echo htmlspecialchars($log['Actor_Role'] . ' #' . $log['Actor_ID']); ?></td>
                                <td><?php echo htmlspecialchars($log['Action_Type']); ?></td>
                                <td><?php echo htmlspecialchars($log['Target_Table'] . ' #' . ($log['Target_ID'] ?? 'N/A')); ?></td>
                                <td><?php echo htmlspecialchars($log['Details'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
