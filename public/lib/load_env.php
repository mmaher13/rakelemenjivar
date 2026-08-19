<?php
/**
 * Minimal .env loader. Reads KEY=VALUE pairs and returns them as an array.
 * Lines starting with # are comments. Blank lines ignored.
 */
function load_env_file(string $path): array {
    $env = [];
    if (!is_readable($path)) return $env;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v);
        if (strlen($v) >= 2 && (
            ($v[0] === '"' && substr($v, -1) === '"') ||
            ($v[0] === "'" && substr($v, -1) === "'")
        )) {
            $v = substr($v, 1, -1);
        }
        $env[$k] = $v;
    }
    return $env;
}
