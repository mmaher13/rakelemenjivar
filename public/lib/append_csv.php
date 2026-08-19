<?php
/**
 * Append a row to a CSV log file with locking and auto-header.
 * Keep the file OUTSIDE the web root so it can't be downloaded.
 */
function append_csv_row(string $path, array $headers, array $row): bool {
    $dir = dirname($path);
    if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) {
        error_log('[append_csv] Could not create dir: ' . $dir);
        return false;
    }

    $needs_header = !file_exists($path) || filesize($path) === 0;

    $fh = @fopen($path, 'ab');
    if ($fh === false) {
        error_log('[append_csv] Could not open file for append: ' . $path);
        return false;
    }
    if (!flock($fh, LOCK_EX)) {
        error_log('[append_csv] Could not acquire lock on: ' . $path);
        fclose($fh);
        return false;
    }

    clearstatcache(true, $path);
    if ($needs_header && filesize($path) === 0) {
        fwrite($fh, "\xEF\xBB\xBF"); // BOM so Excel reads UTF-8 accents
        fputcsv($fh, $headers);
    }

    fputcsv($fh, $row);
    fflush($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
    @chmod($path, 0640);
    return true;
}
