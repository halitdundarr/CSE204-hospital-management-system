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

?>
