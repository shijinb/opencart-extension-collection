<?php
/**
 * Minimal OpenCart runtime for model-level integration tests.
 *
 * Loads OpenCart's own Registry, Model and DB\MySQLi classes from a core "upload" directory and
 * connects to a test database created by tests/integration/run.sh. Model files are loaded from the
 * directory of OCMOD-patched files first (OCMOD_DUMP), then the extensions' upload/ folders, then core.
 *
 * Environment: OC_CORE, OC_PATCHED, OC_EXTENSIONS, DB_HOST, DB_PORT, DB_USER, DB_PASSWORD, DB_DATABASE
 */

declare(strict_types=1);

error_reporting(E_ALL);
// Warnings and notices fail the test. Deprecations are ignored: older OpenCart 3.0.3.x core files
// (for example the mysqli driver) are not PHP 8.2 clean, and that is not what these tests check.
set_error_handler(function ($severity, $message, $file, $line) {
    if ($severity & (E_DEPRECATED | E_USER_DEPRECATED)) {
        return true;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

define('DB_PREFIX', 'oc_');

$core = rtrim((string)getenv('OC_CORE'), '/');

require $core . '/system/helper/utf8.php';
require $core . '/system/engine/registry.php';
require $core . '/system/engine/model.php';
require $core . '/system/library/db.php';
require $core . '/system/library/db/mysqli.php';

final class TestConfig {
    private $data = [
        'config_language_id'       => 1,
        'config_store_id'          => 0,
        'config_customer_group_id' => 1,
        'config_limit_admin'       => 20,
        'config_currency'          => 'USD',
    ];

    public function get($key) {
        return $this->data[$key] ?? null;
    }

    public function set($key, $value) {
        $this->data[$key] = $value;
    }
}

function oc_registry(): Registry {
    static $registry;

    if (!$registry) {
        $registry = new Registry();
        $registry->set('config', new TestConfig());
        $registry->set('db', new DB(
            'mysqli',
            (string)getenv('DB_HOST'),
            (string)getenv('DB_USER'),
            (string)getenv('DB_PASSWORD'),
            (string)getenv('DB_DATABASE'),
            (string)getenv('DB_PORT')
        ));
    }

    return $registry;
}

/** @param string $relative e.g. "admin/model/sale/order.php" */
function oc_load(string $relative): void {
    $candidates = [rtrim((string)getenv('OC_PATCHED'), '/') . '/' . $relative];

    foreach (glob(rtrim((string)getenv('OC_EXTENSIONS'), '/') . '/*/upload/' . $relative) ?: [] as $file) {
        $candidates[] = $file;
    }

    $candidates[] = rtrim((string)getenv('OC_CORE'), '/') . '/' . $relative;

    foreach ($candidates as $file) {
        if (is_file($file)) {
            require_once $file;
            return;
        }
    }

    throw new RuntimeException("Cannot find {$relative}");
}

$GLOBALS['test_failures'] = 0;
$GLOBALS['test_count'] = 0;

function check(string $name, bool $condition, $detail = null): void {
    $GLOBALS['test_count']++;

    if ($condition) {
        echo "  ok   {$name}\n";
    } else {
        $GLOBALS['test_failures']++;
        echo "  FAIL {$name}" . ($detail !== null ? ' -- got: ' . json_encode($detail) : '') . "\n";
    }
}

function finish(): void {
    echo sprintf("  %d checks, %d failed\n", $GLOBALS['test_count'], $GLOBALS['test_failures']);
    exit($GLOBALS['test_failures'] ? 1 : 0);
}
