<?php
/**
 * Builds matrix.json for tests/ordering/purchase-matrix.spec.ts: one bookable ticket per
 * pricing_type × type × voucher_type combination, for the agent of the e2e account.
 *
 *   php scripts/build-matrix.php [--app=<app dir>] [--email=agent.user@example.com] [--out=matrix.json]
 *                                [--db-url=mysql://user:pass@host/db] [--refresh]
 *
 * Reads the app's `default` datasource, or --db-url / E2E_MATRIX_DB_URL when the site BASE_URL
 * points at uses another database (it must be that site's DB: ids and the agent come from it).
 * --email defaults to E2E_USER_EMAIL from .env. Only ids are written; existing cells are kept
 * unless --refresh is passed, so hand-picked tickets survive a rebuild.
 *
 * "Bookable" mirrors what the app checks before it lets the agent add a ticket:
 *  - attraction published, ticket not deleted and inside its publish window, type normal/complex;
 *  - the agent has a price for it: settlement_agents (settlement) or fdatesettlements_agents (fixed dates);
 *  - stock: generated → tickets.stock null or ≥ 2; barcode/pdf → ≥ 2 unused codes/PDFs in the pool;
 *  - fixed dates: a fixeddates row from tomorrow on (on the ticket, or on every package child);
 *  - two-way: the depart leg (ticket_trip_types.type = 'depart');
 *  - complex: classified by its child tickets' voucher_type (all children must agree).
 */

$opts = getopt('', ['app::', 'email::', 'out::', 'db-url::', 'refresh']);
$root = dirname(__DIR__);
// Default app: the parent folder when this suite lives in the app repo (e2e/), else ../tdpmobile2.
$defaultApp = is_file($root . '/../config/bootstrap.php') ? $root . '/..' : $root . '/../tdpmobile2';
$app = realpath($opts['app'] ?? $defaultApp) ?: exit("App directory not found.\n");
$out = $opts['out'] ?? ($root . '/matrix.json');

$email = $opts['email'] ?? '';
if ($email === '' && is_file($root . '/.env')) {
    foreach (file($root . '/.env', FILE_IGNORE_NEW_LINES) as $line) {
        if (preg_match('/^\s*E2E_USER_EMAIL\s*=\s*"?([^"#\s]+)/', $line, $m)) {
            $email = $m[1];
        }
    }
}
if ($email === '') {
    exit("Pass --email or set E2E_USER_EMAIL in .env.\n");
}

chdir($app);
require $app . '/vendor/autoload.php';
require $app . '/config/bootstrap.php';
// --db-url: the database of the site BASE_URL points at, when the app's own config uses another one.
$dbUrl = $opts['db-url'] ?? getenv('E2E_MATRIX_DB_URL') ?: '';
if ($dbUrl !== '') {
    \Cake\Datasource\ConnectionManager::drop('default');
    \Cake\Datasource\ConnectionManager::setConfig('default', ['url' => $dbUrl]);
}
/** @var \Cake\Database\Connection $db */
$db = \Cake\Datasource\ConnectionManager::get('default');

// Query errors would print the bound values (the account email); show only the reason.
set_exception_handler(static function (\Throwable $e): void {
    $reason = $e->getPrevious() ? $e->getPrevious()->getMessage() : preg_replace('/\nQuery:.*/s', '', $e->getMessage());
    fwrite(STDERR, 'build-matrix failed: ' . $reason . "\n");
    exit(1);
});

$agent = $db->execute('SELECT LOWER(HEX(agent_id)) agent FROM agent_users WHERE email = ?', [$email])->fetch('assoc');
if (!$agent) {
    exit("No agent user with that email in this database.\n");
}
$agentId = $agent['agent'];

const PRICING = ['settlement', 'fdate', 'fdatetime', 'two_way_fdatetime'];
const TYPES = ['normal', 'complex'];
const VOUCHERS = ['generated', 'barcode', 'pdf', 'api'];

$uuid = static fn(string $hex): string => preg_replace('/^(.{8})(.{4})(.{4})(.{4})(.{12})$/', '$1-$2-$3-$4-$5', strtolower($hex));

// Live tickets of published attractions the agent has a price for.
$rows = $db->execute("
    SELECT HEX(t.id) id, HEX(t.attraction_id) attraction_id, t.sku_id, t.name, t.type, t.voucher_type, t.stock,
           t.supplier_api_id, t.require_travellers_info, a.title, a.pricing_type,
           (SELECT tt.type FROM ticket_trip_types tt WHERE tt.ticket_id = t.id LIMIT 1) trip
    FROM tickets t
    JOIN attractions a ON a.id = t.attraction_id
    WHERE a.status = 'published' AND COALESCE(t.deleted, 0) = 0 AND t.type IN ('normal', 'complex')
      AND t.publish_start_date <= NOW() AND t.publish_end_date >= NOW()
      AND (
        (a.pricing_type = 'settlement' AND EXISTS (SELECT 1 FROM settlement_agents s WHERE s.ticket_id = t.id AND s.agent_id = UNHEX(?)))
        OR (a.pricing_type <> 'settlement' AND EXISTS (SELECT 1 FROM fdatesettlements_agents f WHERE f.ticket_id = t.id AND f.agent_id = UNHEX(?)))
      )", [$agentId, $agentId])->fetchAll('assoc');

$children = static function (string $hexId) use ($db): array {
    return $db->execute('SELECT HEX(c.id) id, c.sku_id, c.voucher_type, c.stock FROM packages_tickets pt JOIN tickets c ON c.id = pt.ticket_id WHERE pt.package_ticket_id = UNHEX(?) AND COALESCE(c.deleted, 0) = 0', [$hexId])->fetchAll('assoc');
};
$poolCount = static function (string $voucherType, ?string $sku) use ($db): int {
    if (!$sku || !in_array($voucherType, ['barcode', 'pdf'], true)) {
        return PHP_INT_MAX;
    }
    $table = $voucherType === 'pdf' ? 'pdfs' : 'barcodes';
    return (int)$db->execute("SELECT COUNT(*) FROM $table WHERE ticket_sku_id = ? AND status = 'new' AND validity_end_date >= NOW()", [$sku])->fetchColumn(0);
};
$futureDates = static function (string $hexId) use ($db): int {
    return (int)$db->execute('SELECT COUNT(*) FROM fixeddates WHERE ticket_id = UNHEX(?) AND date > CURDATE() AND (stock IS NULL OR stock > 0)', [$hexId])->fetchColumn(0);
};
$hasStock = static fn(array $t, string $voucher): bool => match ($voucher) {
    'generated' => $t['stock'] === null || (int)$t['stock'] >= 2,
    'barcode', 'pdf' => $poolCount($voucher, $t['sku_id']) >= 2,
    default => true,
};

$candidates = [];
foreach ($rows as $t) {
    if ($t['pricing_type'] === 'two_way_fdatetime' && $t['trip'] !== 'depart') {
        continue;
    }
    $voucher = $t['voucher_type'];
    $dates = $t['pricing_type'] === 'settlement' ? 1 : $futureDates($t['id']);
    if ($t['type'] === 'complex') {
        $kids = $children($t['id']);
        $types = array_unique(array_map(fn($k) => $k['voucher_type'] ?: 'generated', $kids));
        if (count($types) !== 1) {
            continue; // no children, or children with different voucher types
        }
        $voucher = reset($types);
        if (array_filter($kids, fn($k) => !$hasStock($k, $voucher))) {
            continue;
        }
        if ($t['pricing_type'] !== 'settlement' && !$dates) {
            $dates = min(array_map(fn($k) => $futureDates($k['id']), $kids));
        }
    } else {
        $voucher = $voucher ?: 'generated'; // null is issued as generated (OrdersController)
        if (!$hasStock($t, $voucher)) {
            continue;
        }
    }
    if (!$dates || !in_array($voucher, VOUCHERS, true)) {
        continue;
    }
    // Prefer: no traveller details, more bookable dates.
    $score = ($t['require_travellers_info'] ? 0 : 1000000) + $dates;
    $key = "{$t['pricing_type']}/{$t['type']}/{$voucher}";
    if (!isset($candidates[$key]) || $score > $candidates[$key]['score']) {
        $candidates[$key] = [
            'score' => $score,
            'cell' => [
                'attraction' => $uuid($t['attraction_id']),
                'ticket' => $uuid($t['id']),
                'note' => trim("{$t['title']} — {$t['name']}"),
            ],
        ];
    }
}

$existing = is_file($out) ? (json_decode(file_get_contents($out), true)['cells'] ?? []) : [];
$cells = [];
$found = 0;
foreach (PRICING as $p) {
    foreach (TYPES as $ty) {
        foreach (VOUCHERS as $v) {
            $key = "$p/$ty/$v";
            $cells[$key] = (!isset($opts['refresh']) && !empty($existing[$key]['ticket']))
                ? $existing[$key]
                : ($candidates[$key]['cell'] ?? null);
            $found += $cells[$key] ? 1 : 0;
        }
    }
}

file_put_contents($out, json_encode([
    '_comment' => 'pricing_type/type/voucher_type → a ticket the e2e account can book. null = none found; edit by hand or rebuild with scripts/build-matrix.php.',
    'cells' => $cells,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");

echo "Wrote $out: $found of " . count($cells) . " combinations have a ticket.\n";
foreach ($cells as $key => $cell) {
    printf("  %-40s %s\n", $key, $cell ? $cell['note'] : '-');
}
