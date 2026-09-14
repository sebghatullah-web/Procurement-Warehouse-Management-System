<?php
/**
 * Shared helper functions.
 */
require_once __DIR__ . '/config.php';

function h($s)
{
    return htmlspecialchars((string)($s ?? ''), ENT_QUOTES);
}

function esc($s)
{
    global $conn;
    return $conn->real_escape_string((string)($s ?? ''));
}

function is_post()
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function redirect_to($url)
{
    header('Location: ' . $url);
    exit;
}

function fetch_one($sql)
{
    global $conn;
    $r = $conn->query($sql);
    return ($r && $r->num_rows) ? $r->fetch_assoc() : null;
}

function fetch_all($sql)
{
    global $conn;
    $r = $conn->query($sql);
    if (!$r) { return []; }
    if (method_exists($r, 'fetch_all')) { return $r->fetch_all(MYSQLI_ASSOC); }
    $rows = [];
    while ($row = $r->fetch_assoc()) { $rows[] = $row; }
    return $rows;
}

function exec_sql($sql)
{
    global $conn;
    $conn->query($sql);
    return $conn->errno === 0;
}

function last_error()
{
    global $conn;
    return $conn->error;
}

function inserted_id()
{
    global $conn;
    return $conn->insert_id;
}

function affected_rows()
{
    global $conn;
    return $conn->affected_rows;
}

/* ---------------- Flash messages ---------------- */
function flash_set($type, $msg)
{
    if (!isset($_SESSION['flash'])) { $_SESSION['flash'] = []; }
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

function flash_render()
{
    if (!isset($_SESSION['flash']) || count($_SESSION['flash']) === 0) { return ''; }
    $out = '';
    foreach ($_SESSION['flash'] as $f) {
        $out .= '<div class="alert alert-' . h($f['type']) . ' alert-dismissible fade show shadow-sm mt-2" role="alert">'
              . h($f['msg'])
              . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
              . '</div>';
    }
    unset($_SESSION['flash']);
    return $out;
}

/* ---------------- Formatting ---------------- */
function money($n)
{
    return number_format((float)($n ?? 0), 2);
}

function money0($n)
{
    $v = (float)($n ?? 0);
    return number_format($v, $v === floor($v) ? 0 : 2);
}

function xnum($n)
{
    $v = (float)($n ?? 0);
    return number_format($v, $v === floor($v) ? 0 : 2);
}

function today()
{
    return date('Y-m-d');
}

/* ---------------- Status helpers ---------------- */
function _req_statuses()
{
    return [
        'pending'            => 'Pending Review',
        'closed'             => 'Closed (Unnecessary)',
        'warehouse_check'    => 'Warehouse Check',
        'purchase_required'  => 'Purchase Required',
        'quotation_pending'  => 'Collecting Quotations',
        'committee_pending'  => 'Awaiting Committee',
        'approved'           => 'Approved',
        'purchased'          => 'Purchased / Ordered',
        'received'           => 'Received at Gate',
        'completed'          => 'Completed',
    ];
}

function _pur_statuses()
{
    return [
        'draft'             => 'Draft',
        'quotation'         => 'Collecting Quotations',
        'committee_pending' => 'Awaiting Committee',
        'approved'          => 'Approved',
        'ordered'           => 'Ordered / Purchased',
        'received'          => 'Received at Gate',
        'completed'         => 'Completed',
        'rejected'          => 'Rejected',
    ];
}

function req_status_label($s)
{
    $m = _req_statuses();
    return $m[$s] ?? ucwords(str_replace('_', ' ', (string)$s));
}

function pur_status_label($s)
{
    $m = _pur_statuses();
    return $m[$s] ?? ucwords(str_replace('_', ' ', (string)$s));
}

function badge_class($s)
{
    $map = [
        'pending' => 'text-bg-warning', 'closed' => 'text-bg-secondary',
        'warehouse_check' => 'text-bg-info', 'purchase_required' => 'text-bg-warning',
        'quotation_pending' => 'text-bg-info', 'committee_pending' => 'text-bg-primary',
        'approved' => 'text-bg-primary', 'purchased' => 'text-bg-info',
        'received' => 'text-bg-secondary', 'completed' => 'text-bg-success',
        'draft' => 'text-bg-secondary', 'quotation' => 'text-bg-info',
        'ordered' => 'text-bg-info', 'rejected' => 'text-bg-danger',
        'urgent' => 'text-bg-danger', 'normal' => 'text-bg-secondary',
        'paid' => 'text-bg-success', 'direct' => 'text-bg-dark',
        'warehouse' => 'text-bg-info', 'open' => 'text-bg-success',
    ];
    return $map[$s] ?? 'text-bg-secondary';
}

function badge($s)
{
    $m = _req_statuses();
    foreach (_pur_statuses() as $k => $v) { $m[$k] = $v; }
    $label = $m[$s] ?? ucwords(str_replace('_', ' ', (string)$s));
    return '<span class="badge rounded-pill ' . badge_class($s) . '">' . h($label) . '</span>';
}

function role_label($r)
{
    $map = [
        'employee' => 'Employee', 'procurement_manager' => 'Procurement Manager',
        'warehouse_manager' => 'Warehouse Manager', 'gate_security' => 'Gate Security',
        'committee' => 'Committee Member', 'general_manager' => 'General Manager',
        'admin' => 'Administrator',
    ];
    return $map[$r] ?? $r;
}

function roles()
{
    return [
        'employee' => 'Employee',
        'procurement_manager' => 'Procurement Manager',
        'warehouse_manager' => 'Warehouse Manager',
        'gate_security' => 'Gate Security',
        'committee' => 'Committee Member',
        'general_manager' => 'General Manager',
        'admin' => 'Administrator',
    ];
}

function _ucwords($s)
{
    $parts = explode(' ', (string)$s);
    foreach ($parts as $i => $w) {
        if ($w !== '') {
            $parts[$i] = strtoupper(substr($w, 0, 1)) . substr($w, 1);
        }
    }
    return implode(' ', $parts);
}