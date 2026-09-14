<?php
/**
 * Authentication & role-based access control.
 */
require_once __DIR__ . '/functions.php';

function current_user()
{
    if (!isset($_SESSION['user_id'])) { return null; }
    $u = fetch_one(
        'SELECT u.*, d.name AS department_name ' .
        'FROM users u LEFT JOIN departments d ON d.id = u.department_id ' .
        'WHERE u.id = ' . (int)$_SESSION['user_id'] . ' AND u.status = 1'
    );
    if (!$u) { unset($_SESSION['user_id']); }
    return $u;
}

function require_login()
{
    $u = current_user();
    if (!$u) {
        flash_set('warning', 'Please sign in to continue.');
        redirect_to(BASE_URL . '/login.php');
    }
    return $u;
}

function has_role($user, ...$roles)
{
    return $user !== null && in_array($user['role'], $roles, true);
}

function require_role(...$roles)
{
    $u = require_login();
    if (!has_role($u, ...$roles)) {
        flash_set('danger', 'Access denied: you do not have permission for that page.');
        redirect_to(BASE_URL . '/index.php');
    }
    return $u;
}

function login_user($username, $password)
{
    $u = fetch_one("SELECT * FROM users WHERE username = '" . esc($username) . "' AND status = 1");
    if (!$u || !password_verify($password, $u['password'])) {
        return null;
    }
    $_SESSION['user_id'] = (int)$u['id'];
    return current_user();
}

function logout_user()
{
    unset($_SESSION['user_id'], $_SESSION['flash']);
}