<?php
/**
 * CLI: php tools_cleanup_visit_logs.php 90
 * Deletes visit_logs older than N days. Default: 90.
 */
declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    exit("CLI only.\n");
}

$days = isset($argv[1]) ? max(1, (int)$argv[1]) : 90;
$threshold = (new DateTimeImmutable())->modify('-' . $days . ' days')->format('Y-m-d H:i:s');

$stmt = db()->prepare('DELETE FROM visit_logs WHERE created_at < ?');
$stmt->execute([$threshold]);

echo "Deleted " . $stmt->rowCount() . " rows older than {$days} days.\n";
