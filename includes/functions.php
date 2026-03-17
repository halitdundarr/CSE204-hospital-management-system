<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}

function get_csrf_token() {
	if (empty($_SESSION['csrf_token'])) {
		$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
	}
	return $_SESSION['csrf_token'];
}

function csrf_input_field() {
	$token = htmlspecialchars(get_csrf_token(), ENT_QUOTES, 'UTF-8');
	return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

function is_valid_csrf_token($token) {
	return is_string($token)
		&& isset($_SESSION['csrf_token'])
		&& hash_equals($_SESSION['csrf_token'], $token);
}

function ensure_audit_log_table($conn) {
	static $checked = false;
	if ($checked) {
		return;
	}
	$checked = true;

	$create_sql = "CREATE TABLE IF NOT EXISTS `AUDIT_LOG` (
		`Audit_ID` INT NOT NULL AUTO_INCREMENT,
		`Actor_Role` VARCHAR(50) NOT NULL,
		`Actor_ID` BIGINT NOT NULL,
		`Action_Type` VARCHAR(100) NOT NULL,
		`Target_Table` VARCHAR(100) NOT NULL,
		`Target_ID` BIGINT DEFAULT NULL,
		`Details` TEXT,
		`Created_At` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (`Audit_ID`),
		INDEX `idx_actor` (`Actor_Role`, `Actor_ID`),
		INDEX `idx_action_time` (`Action_Type`, `Created_At`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

	@$conn->query($create_sql);
}

function audit_log_action($conn, $actor_role, $actor_id, $action_type, $target_table, $target_id = null, $details = []) {
	ensure_audit_log_table($conn);

	$details_json = null;
	if (is_array($details)) {
		$details_json = json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	} elseif (is_string($details)) {
		$details_json = $details;
	}

	$sql = "INSERT INTO `AUDIT_LOG`
		(`Actor_Role`, `Actor_ID`, `Action_Type`, `Target_Table`, `Target_ID`, `Details`)
		VALUES (?, ?, ?, ?, ?, ?)";
	$stmt = $conn->prepare($sql);
	if (!$stmt) {
		return false;
	}

	$actor_id = (int)$actor_id;
	$target_id_param = is_null($target_id) ? null : (int)$target_id;
	$stmt->bind_param(
		"sissis",
		$actor_role,
		$actor_id,
		$action_type,
		$target_table,
		$target_id_param,
		$details_json
	);

	$ok = $stmt->execute();
	$stmt->close();
	return $ok;
}

?>
