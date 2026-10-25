<?php
/**
 * OCMOD verification harness.
 *
 * Applies OCMOD install.xml files to a pristine OpenCart 3.x "upload" directory using the same
 * line-based algorithm as admin/controller/marketplace/modification.php::refresh(), then:
 *   - reports every operation whose <search> was not found,
 *   - runs `php -l` on every modified PHP file and every PHP file in the extensions' upload/ folders,
 *   - parses every modified Twig template with Twig (syntax check only, nothing is rendered).
 *
 * Usage: php tests/ocmod-harness.php <opencart-upload-dir> <extension-dir> [<extension-dir> ...]
 *
 * Exit code is non-zero when an operation without error="skip" is not found, or when linting fails.
 * Operations marked error="skip" that are not found are reported as warnings (for example
 * theme-specific operations that do not match the default theme).
 */

declare(strict_types=1);

if ($argc < 3) {
    fwrite(STDERR, "Usage: php {$argv[0]} <opencart-upload-dir> <extension-dir> [...]\n");
    exit(2);
}

$core = rtrim(realpath($argv[1]) ?: '', '/');
if ($core === '' || !is_file($core . '/index.php')) {
    fwrite(STDERR, "Not an OpenCart upload directory: {$argv[1]}\n");
    exit(2);
}

$autoload = __DIR__ . '/../vendor/autoload.php';
$twig = null;
if (is_file($autoload)) {
    require $autoload;
    $twig = new \Twig\Environment(new \Twig\Loader\ArrayLoader([]));
}

$dirs = [
    'catalog' => $core . '/catalog/',
    'admin'   => $core . '/admin/',
    'system'  => $core . '/system/',
];

$errors = 0;
$warnings = 0;
$modification = [];
$original = [];
$uploadPhp = [];
$uploadTwig = [];

foreach (array_slice($argv, 2) as $extDir) {
    $extDir = rtrim($extDir, '/');
    $xmlFile = $extDir . '/install.xml';

    foreach (['php', 'twig'] as $ext) {
        $it = is_dir($extDir . '/upload')
            ? new RecursiveIteratorIterator(new RecursiveDirectoryIterator($extDir . '/upload', FilesystemIterator::SKIP_DOTS))
            : [];
        foreach ($it as $f) {
            if ($f->getExtension() === $ext) {
                if ($ext === 'php') {
                    $uploadPhp[] = $f->getPathname();
                } else {
                    $uploadTwig[] = $f->getPathname();
                }
            }
        }
    }

    // Extensions > Installer only copies upload/ files into these directories (marketplace/install.php).
    $allowedUpload = [
        'admin/controller/extension/', 'admin/language/', 'admin/model/extension/', 'admin/view/image/',
        'admin/view/javascript/', 'admin/view/stylesheet/', 'admin/view/template/extension/',
        'catalog/controller/extension/', 'catalog/language/', 'catalog/model/extension/',
        'catalog/view/javascript/', 'catalog/view/theme/', 'system/config/', 'system/library/', 'image/catalog/',
    ];
    foreach (is_dir($extDir . '/upload') ? new RecursiveIteratorIterator(new RecursiveDirectoryIterator($extDir . '/upload', FilesystemIterator::SKIP_DOTS)) : [] as $f) {
        $relative = substr($f->getPathname(), strlen($extDir . '/upload/'));
        $allowed = false;
        foreach ($allowedUpload as $prefix) {
            $allowed = $allowed || strpos($relative, $prefix) === 0;
        }
        if (!$allowed) {
            echo "  FAIL   {$extDir}/upload/{$relative}: the OpenCart installer refuses files outside its allowed directories\n";
            $errors++;
        }
    }

    if (!is_file($xmlFile)) {
        continue;
    }

    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->preserveWhiteSpace = false;
    if (!@$dom->loadXML((string)file_get_contents($xmlFile))) {
        echo "ERROR  {$xmlFile}: invalid XML\n";
        $errors++;
        continue;
    }

    $name = $dom->getElementsByTagName('name')->item(0)->textContent ?? basename($extDir);
    echo "MOD: {$name} ({$extDir})\n";

    foreach ($dom->getElementsByTagName('modification')->item(0)->getElementsByTagName('file') as $fileNode) {
        $operations = $fileNode->getElementsByTagName('operation');
        $paths = explode('|', str_replace('\\', '/', $fileNode->getAttribute('path')));

        foreach ($paths as $path) {
            $full = '';
            foreach ($dirs as $prefix => $dir) {
                if (substr($path, 0, strlen($prefix)) === $prefix) {
                    $full = $dir . substr($path, strlen($prefix) + 1);
                }
            }
            $files = $full ? glob($full, GLOB_BRACE) : [];
            if (!$files) {
                echo "  WARN   no core file matches path {$path}\n";
                $warnings++;
                continue;
            }

            foreach ($files as $file) {
                $key = substr($file, strlen($core) + 1);
                if (!isset($modification[$key])) {
                    $content = (string)file_get_contents($file);
                    $modification[$key] = preg_replace('~\r?\n~', "\n", $content);
                    $original[$key] = $modification[$key];
                }

                foreach ($operations as $n => $operation) {
                    $error = $operation->getAttribute('error');
                    $searchNode = $operation->getElementsByTagName('search')->item(0);
                    $addNode = $operation->getElementsByTagName('add')->item(0);

                    $ignoreif = $operation->getElementsByTagName('ignoreif')->item(0);
                    if ($ignoreif && strpos($modification[$key], $ignoreif->textContent) !== false) {
                        continue;
                    }

                    $search = $searchNode->textContent;
                    $trim = $searchNode->getAttribute('trim');
                    if (!$trim || $trim === 'true') {
                        $search = trim($search);
                    }
                    $index = $searchNode->getAttribute('index');
                    $indexes = $index !== '' ? explode(',', $index) : [];

                    $add = $addNode->textContent;
                    if ($addNode->getAttribute('trim') === 'true') {
                        $add = trim($add);
                    }
                    $position = $addNode->getAttribute('position');
                    $offset = $addNode->getAttribute('offset');
                    $offset = $offset === '' ? 0 : (int)$offset;

                    $status = false;
                    $hits = 0;
                    $i = 0;
                    $lines = explode("\n", $modification[$key]);
                    for ($line_id = 0; $line_id < count($lines); $line_id++) {
                        $line = $lines[$line_id];
                        $match = false;
                        if (stripos($line, $search) !== false) {
                            if (!$indexes || in_array((string)$i, $indexes, true)) {
                                $match = true;
                            }
                            $i++;
                        }
                        if ($match) {
                            $new_lines = explode("\n", $add);
                            switch ($position) {
                                default:
                                case 'replace':
                                    if ($offset < 0) {
                                        array_splice($lines, $line_id + $offset, abs($offset) + 1, [str_replace($search, $add, $line)]);
                                        $line_id -= $offset;
                                    } else {
                                        array_splice($lines, $line_id, $offset + 1, [str_replace($search, $add, $line)]);
                                    }
                                    break;
                                case 'before':
                                    array_splice($lines, $line_id - $offset, 0, $new_lines);
                                    $line_id += count($new_lines);
                                    break;
                                case 'after':
                                    array_splice($lines, ($line_id + 1) + $offset, 0, $new_lines);
                                    $line_id += count($new_lines);
                                    break;
                            }
                            $status = true;
                            $hits++;
                        }
                    }
                    $modification[$key] = implode("\n", $lines);

                    $label = sprintf('%s op#%d "%s"', $key, $n + 1, mb_strimwidth($search, 0, 70, '...'));
                    if ($status) {
                        echo "  OK     {$label} ({$hits} match" . ($hits > 1 ? 'es' : '') . ")\n";
                    } elseif ($error === 'skip') {
                        echo "  SKIP   {$label} not found (error=skip)\n";
                        $warnings++;
                    } else {
                        echo "  FAIL   {$label} not found" . ($error === 'abort' ? ' (abort)' : ' (remaining operations for this file are dropped)') . "\n";
                        $errors++;
                        if ($error !== 'abort') {
                            break;
                        }
                    }
                }
            }
        }
    }
}

// Lint
$tmp = sys_get_temp_dir() . '/ocmod-harness-' . getmypid();
@mkdir($tmp, 0777, true);
$phpBinary = PHP_BINARY;

$lintPhp = function (string $label, string $path) use ($phpBinary, &$errors): void {
    exec(escapeshellarg($phpBinary) . ' -l ' . escapeshellarg($path) . ' 2>&1', $out, $rc);
    if ($rc !== 0) {
        echo "  FAIL   php -l {$label}: " . implode(' ', array_slice($out, 0, 2)) . "\n";
        $errors++;
    }
};

$lintTwig = function (string $label, string $code) use ($twig, &$errors): void {
    if (!$twig) {
        return;
    }
    try {
        $twig->parse($twig->tokenize(new \Twig\Source($code, $label)));
    } catch (\Twig\Error\SyntaxError $e) {
        // Unknown filters/functions/tests are environment-specific, not syntax errors.
        if (preg_match('/^Unknown "[^"]+" (filter|function|test)/', $e->getRawMessage())) {
            return;
        }
        echo "  FAIL   twig {$label}: " . $e->getMessage() . "\n";
        $errors++;
    }
};

echo "LINT\n";
$checked = 0;
foreach ($modification as $key => $code) {
    if ($code === $original[$key]) {
        continue;
    }
    $checked++;
    if (substr($key, -4) === '.php') {
        $f = $tmp . '/' . md5($key) . '.php';
        file_put_contents($f, $code);
        $lintPhp($key, $f);
    } elseif (substr($key, -5) === '.twig') {
        $lintTwig($key, $code);
    }
}
foreach ($uploadPhp as $f) {
    $checked++;
    $lintPhp($f, $f);
}
foreach ($uploadTwig as $f) {
    $checked++;
    $lintTwig($f, (string)file_get_contents($f));
}
echo "  {$checked} files checked" . ($twig ? '' : ' (Twig not installed: templates not parsed, run composer install)') . "\n";

if (getenv('OCMOD_DUMP')) {
    foreach ($modification as $key => $code) {
        if ($code !== $original[$key]) {
            $dest = getenv('OCMOD_DUMP') . '/' . $key;
            @mkdir(dirname($dest), 0777, true);
            file_put_contents($dest, $code);
        }
    }
}

array_map('unlink', glob($tmp . '/*') ?: []);
@rmdir($tmp);

echo "RESULT: {$errors} error(s), {$warnings} warning(s)\n";
exit($errors > 0 ? 1 : 0);
