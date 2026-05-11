<?php
// scripts/auto_backup.php
/**
 * CLI script for automated backups.
 * Run this via scheduled task: php.exe C:\inetpub\wwwroot\records\scripts\auto_backup.php
 */

require_once __DIR__ . '/../src/Config.php';
require_once __DIR__ . '/../src/BackupManager.php';

use Hospital\BackupManager;

echo "--- AIH Auto Backup Started: " . date('Y-m-d H:i:s') . " ---\n";

try {
    $manager = new BackupManager();
    $result = $manager->createBackup();

    if ($result['status'] === 'success') {
        echo "SUCCESS: Backup created at " . $result['path'] . " (" . round($result['size'] / 1024 / 1024, 2) . " MB)\n";
    } else {
        echo "ERROR: " . $result['message'] . "\n";
    }
} catch (Exception $e) {
    echo "CRITICAL ERROR: " . $e->getMessage() . "\n";
}

echo "--- Auto Backup Finished ---\n";
