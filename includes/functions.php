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

/**
 * Format an amount together with its currency symbol.
 * AFN -> "۱۲۳۴۵ ؋"   USD -> "$۱۲۳۴۵"
 */
function money_cur($n, $cur)
{
    $s = money0($n);
    if (strtoupper((string)($cur ?? 'AFN')) === 'USD') { return '$' . $s; }
    return $s . ' ؋';
}

/** Human label for a currency code. */
function cur_label($c)
{
    $c = strtoupper((string)($c ?? 'AFN'));
    if ($c === 'USD') { return 'دالر ($)'; }
    if ($c === 'AFN') { return 'افغانی (؋)'; }
    return h($c);
}

function today()
{
    return date('Y-m-d');
}

/**
 * Extract the leading numeric value from a free-text quantity string.
 * e.g. "3", "5 کیلوگرام", "۳ متر", "10، نیم بیل" -> 3 / 5 / 3 / 10.
 * Returns 0.0 when no number can be found.
 */
function qty_num($s)
{
    $s = str_replace([',', '٫', '،'], '.', (string)$s);
    if (preg_match('/-?[0-9]+(?:\.[0-9]+)?/', $s, $m)) {
        return (float)$m[0];
    }
    return 0.0;
}

/* ---------------- Status helpers ---------------- */
function _req_statuses()
{
    return [
        'pending'            => 'در انتظار بررسی',
        'closed'             => 'بسته شده (غیرضروری)',
        'warehouse_check'    => 'بررسی انبار',
        'purchase_required'  => 'نیاز به خرید',
        'quotation_pending'  => 'در حال جمع‌آوری قیمت‌ها',
        'committee_pending'  => 'در انتظار کمیته',
        'approved'           => 'تصویب شده',
        'supplier_selected'  => 'تأمین‌کننده نهایی انتخاب شده',
        'authority_approved' => 'تصویب ریاست شرکت',
        'financed'           => 'هزینه تأمین شد (مالی)',
        'purchased'          => 'خریداری / سفارش داده شده',
        'received'           => 'دریافت شده در گیت',
        'completed'          => 'کامل شده',
    ];
}

function _pur_statuses()
{
    return [
        'draft'              => 'پیش‌نویس',
        'quotation'          => 'جمع‌آوری قیمت‌ها',
        'committee_pending'  => 'در انتظار کمیته',
        'approved'           => 'تصویب کمیته - انتخاب تأمین‌کننده',
        'supplier_selected'  => 'در انتظار تصویب ریاست شرکت',
        'authority_approved' => 'تصویب ریاست - در انتظار مالی',
        'financed'           => 'هزینه تأمین شد - آماده ثبت سفارش',
        'ordered'            => 'سفارش داده شده',
        'received'           => 'دریافت شده در گیت',
        'completed'          => 'کامل شده',
        'rejected'           => 'رد شده',
    ];
}

/**
 * The controlled chain that runs AFTER the committee approves a purchase.
 * Used by the purchase page (timeline) and by the status guards.
 */
function _purchase_chain()
{
    return ['approved', 'supplier_selected', 'authority_approved', 'financed', 'ordered', 'received', 'completed'];
}

/** Index (1-based) of a purchase status inside the post-committee chain; 0 = not started. */
function purchase_stage_index($status)
{
    $i = array_search((string)$status, _purchase_chain(), true);
    return ($i === false) ? 0 : ($i + 1);
}

/** Payment methods the finance department can hand the money over with. */
function _finance_methods()
{
    return [
        'bank_cheque'   => 'چک بانکی',
        'cash'          => 'پول نقد',
        'bank_transfer' => 'انتقال بانکی',
        'other'         => 'طریقه دیگر',
    ];
}

function finance_method_label($m)
{
    $map = _finance_methods();
    return $map[$m] ?? '—';
}

/** Short label for the handover mode used in warehouse disposition. */
function disposition_mode_label($m)
{
    if ($m === 'direct') { return 'تحویل مستقیم به درخواست‌کننده'; }
    return 'ذخیره در انبار';
}

/** Next free handover slip number: HND-YYYY-0001 */
function next_handover_no()
{
    $prefix = 'HND-' . date('Y') . '-';
    $last = fetch_one("SELECT handover_no FROM consumptions
                       WHERE handover_no LIKE '$prefix%'
                       ORDER BY handover_no DESC LIMIT 1");
    $n = $last ? (int)substr((string)$last['handover_no'], strlen($prefix)) + 1 : 1;
    return $prefix . str_pad((string)$n, 4, '0', STR_PAD_LEFT);
}

/** Next free purchase number: PUR-YYYY-0001 */
function next_purchase_no()
{
    $prefix = 'PUR-' . date('Y') . '-';
    $last = fetch_one("SELECT purchase_no FROM purchases
                       WHERE purchase_no LIKE '$prefix%'
                       ORDER BY purchase_no DESC LIMIT 1");
    $n = $last ? (int)substr((string)$last['purchase_no'], strlen($prefix)) + 1 : 1;
    return $prefix . str_pad((string)$n, 4, '0', STR_PAD_LEFT);
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
        'supplier_selected' => 'text-bg-warning',
        'authority_approved' => 'text-bg-primary', 'financed' => 'text-bg-info',
        'bank_cheque' => 'text-bg-secondary', 'cash' => 'text-bg-secondary',
        'bank_transfer' => 'text-bg-secondary', 'other' => 'text-bg-secondary',
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
        'employee' => 'کارمند', 'procurement_manager' => 'مدیر خرید',
        'warehouse_manager' => 'مدیر انبار', 'gate_security' => 'امنیت گیت',
        'committee' => 'عضو کمیته', 'general_manager' => 'مدیر عمومی',
        'finance' => 'مدیر مالی', 'admin' => 'مدیر سیستم',
    ];
    return $map[$r] ?? $r;
}

function roles()
{
    return [
        'employee' => 'کارمند',
        'procurement_manager' => 'مدیر خرید',
        'warehouse_manager' => 'مدیر انبار',
        'gate_security' => 'امنیت گیت',
        'committee' => 'عضو کمیته',
        'general_manager' => 'مدیر عمومی (ریاست / رئیس اجرائیه)',
        'finance' => 'مالی (مدیر مالی)',
        'admin' => 'مدیر سیستم',
    ];
}

function _units()
{
    return [
        'pcs' => 'عدد', 'bag' => 'کیسه', 'ton' => 'تن', 'kg' => 'کیلوگرم',
        'ream' => 'دسته', 'roll' => 'رول', 'liter' => 'لیتر', 'set' => 'ست',
        'pair' => 'جفت', 'box' => 'جعبه', 'coil' => 'کلاف', 'meter' => 'متر',
    ];
}

function unit_label($u)
{
    $map = _units();
    return $map[$u] ?? $u;
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