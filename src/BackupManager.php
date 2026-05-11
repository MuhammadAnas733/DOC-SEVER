<?php
// src/BackupManager.php

namespace Hospital;

use ZipArchive;
use Exception;

class BackupManager {
    private $config;
    private $backupDir;
    private $mysqlBinPath = 'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\';

    public function __construct() {
        $this->backupDir = realpath(__DIR__ . '/../backups/');
        if (!$this->backupDir) {
            mkdir(__DIR__ . '/../backups/', 0777, true);
            $this->backupDir = realpath(__DIR__ . '/../backups/');
        }
    }

    /**
     * Perform a full backup (Database + Storage)
     */
    public function createBackup() {
        set_time_limit(0);
        $timestamp = date('Y-m-d_H-i-s');
        $tempDir = $this->backupDir . DIRECTORY_SEPARATOR . 'temp_' . $timestamp;
        mkdir($tempDir, 0777, true);

        try {
            // 1. Database Dump
            $dbFile = $tempDir . DIRECTORY_SEPARATOR . 'database.sql';
            $this->dumpDatabase($dbFile);

            // 2. Storage Copy (Zipping storage contents)
            $storageZip = $tempDir . DIRECTORY_SEPARATOR . 'storage.zip';
            $this->zipDirectory(realpath(__DIR__ . '/../storage/'), $storageZip);

            // 3. Final Package
            $finalZipName = "backup_{$timestamp}.zip";
            $finalZipPath = $this->backupDir . DIRECTORY_SEPARATOR . $finalZipName;
            
            $finalZip = new ZipArchive();
            if ($finalZip->open($finalZipPath, ZipArchive::CREATE) === TRUE) {
                $finalZip->addFile($dbFile, 'database.sql');
                $finalZip->addFile($storageZip, 'storage.zip');
                $finalZip->close();
            } else {
                throw new Exception("Failed to create final backup zip.");
            }

            // Cleanup temp files
            unlink($dbFile);
            unlink($storageZip);
            rmdir($tempDir);

            $this->log("Backup created successfully: $finalZipName");
            return [
                'status' => 'success',
                'filename' => $finalZipName,
                'path' => $finalZipPath,
                'size' => filesize($finalZipPath)
            ];

        } catch (Exception $e) {
            $this->log("Backup failed: " . $e->getMessage());
            // Cleanup temp if exists
            if (isset($dbFile) && file_exists($dbFile)) unlink($dbFile);
            if (isset($storageZip) && file_exists($storageZip)) unlink($storageZip);
            if (isset($tempDir) && is_dir($tempDir)) rmdir($tempDir);
            
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    private function dumpDatabase($outputFile) {
        $host = Config::DB_HOST;
        $user = Config::DB_USER;
        $pass = Config::DB_PASS;
        $name = Config::DB_NAME;
        $port = Config::DB_PORT;

        $command = "\"{$this->mysqlBinPath}mysqldump.exe\" --host={$host} --port={$port} --user={$user} --password={$pass} {$name} > \"{$outputFile}\"";
        
        // Execute the command
        $output = [];
        $returnVar = 0;
        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            throw new Exception("Database dump failed with exit code $returnVar. Command: $command");
        }
    }

    private function zipDirectory($source, $destination) {
        if (!extension_loaded('zip') || !file_exists($source)) {
            throw new Exception("Zip extension not loaded or source directory missing.");
        }

        $zip = new ZipArchive();
        if (!$zip->open($destination, ZipArchive::CREATE)) {
            throw new Exception("Could not create zip file: $destination");
        }

        $source = str_replace('\\', '/', realpath($source));

        if (is_dir($source) === true) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source), \RecursiveIteratorIterator::LEAVES_ONLY);

            foreach ($files as $name => $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($source) + 1);
                    $zip->addFile($filePath, $relativePath);
                }
            }
        } else if (is_file($source) === true) {
            $zip->addFile($source, basename($source));
        }

        return $zip->close();
    }

    private function log($message) {
        $logFile = $this->backupDir . DIRECTORY_SEPARATOR . 'backup_log.txt';
        $entry = "[" . date('Y-m-d H:i:s') . "] " . $message . PHP_EOL;
        file_put_contents($logFile, $entry, FILE_APPEND);
    }
    
    public function getBackups() {
        $files = glob($this->backupDir . DIRECTORY_SEPARATOR . 'backup_*.zip');
        $results = [];
        foreach ($files as $file) {
            $results[] = [
                'name' => basename($file),
                'date' => date('Y-m-d H:i:s', filemtime($file)),
                'size' => round(filesize($file) / 1024 / 1024, 2) . ' MB'
            ];
        }
        usort($results, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        return $results;
    }
    
    public function deleteBackup($filename) {
        $path = $this->backupDir . DIRECTORY_SEPARATOR . basename($filename);
        if (file_exists($path) && strpos($filename, 'backup_') === 0 && strpos($filename, '.zip') !== false) {
            return unlink($path);
        }
        return false;
    }

    /**
     * Restore a backup
     */
    public function restoreBackup($filename) {
        set_time_limit(0);
        $zipPath = $this->backupDir . DIRECTORY_SEPARATOR . basename($filename);
        if (!file_exists($zipPath)) {
            throw new Exception("Backup file not found.");
        }
        return $this->performRestore($zipPath, $filename);
    }

    public function restoreFromUpload($tmpPath) {
        return $this->performRestore($tmpPath, "Uploaded File");
    }

    private function performRestore($zipPath, $logSource) {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $tempRestoreDir = $this->backupDir . DIRECTORY_SEPARATOR . 'restore_' . time();
        mkdir($tempRestoreDir, 0777, true);

        $zip = new ZipArchive();
        if ($zip->open($zipPath) === TRUE) {
            $zip->extractTo($tempRestoreDir);
            $zip->close();
        } else {
            throw new Exception("Failed to open backup zip.");
        }

        try {
            // 1. Restore Database
            $this->log("Restore stage 1: Preparing database import...");
            $dbFile = $tempRestoreDir . DIRECTORY_SEPARATOR . 'database.sql';
            if (file_exists($dbFile)) {
                $this->importDatabase($dbFile);
                $this->log("Restore stage 1: Database imported successfully.");
            } else {
                $this->log("Restore stage 1: No database.sql found, skipping.");
            }

            // 2. Restore Storage
            $this->log("Restore stage 2: Preparing storage files extraction...");
            $storageZip = $tempRestoreDir . DIRECTORY_SEPARATOR . 'storage.zip';
            if (file_exists($storageZip)) {
                $targetStorage = realpath(__DIR__ . '/../storage/');
                $this->extractNestedZip($storageZip, $targetStorage);
                $this->log("Restore stage 2: Storage files extracted successfully.");
            } else {
                $this->log("Restore stage 2: No storage.zip found, skipping.");
            }

            // Cleanup
            $this->recursiveDelete($tempRestoreDir);
            
            $this->log("SYSTEM RESTORE COMPLETED SUCCESSFULLY from: $logSource");
            return ['status' => 'success'];

        } catch (Exception $e) {
            $this->log("Restore failed: " . $e->getMessage());
            $this->recursiveDelete($tempRestoreDir);
            throw $e;
        }
    }

    private function importDatabase($sqlFile) {
        $host = Config::DB_HOST;
        $user = Config::DB_USER;
        $pass = Config::DB_PASS;
        $name = Config::DB_NAME;
        $port = Config::DB_PORT;

        // Use 'source' instead of '<' for better Windows compatibility
        $safePath = str_replace('\\', '/', $sqlFile);
        $command = "\"{$this->mysqlBinPath}mysql.exe\" --host={$host} --port={$port} --user={$user} --password={$pass} {$name} -e \"source {$safePath}\"";
        
        $output = [];
        $returnVar = 0;
        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            $this->log("Database import failed logic. Output: " . implode("\n", $output));
            throw new Exception("Database import failed. Exit code: $returnVar");
        }
    }

    private function extractNestedZip($zipFile, $destination) {
        $zip = new ZipArchive();
        if ($zip->open($zipFile) === TRUE) {
            $zip->extractTo($destination);
            $zip->close();
        }
    }

    public function getSchedule() {
        try {
            $db = \Hospital\Database::getInstance();
            $stmt = $db->query("SELECT value FROM settings WHERE setting_key = 'backup_time'");
            if (!$stmt) return '02:00:00';
            $result = $stmt->fetch();
            return $result ? $result['value'] : '02:00:00';
        } catch (\Throwable $e) {
            return '02:00:00'; // Default if table not found or other error
        }
    }

    public function updateSchedule($time) {
        // 1. Update Database
        $db = \Hospital\Database::getInstance();
        // Create settings table if not exists
        $db->exec("CREATE TABLE IF NOT EXISTS settings (
            setting_key VARCHAR(50) PRIMARY KEY,
            value VARCHAR(255)
        )");
        
        $stmt = $db->prepare("INSERT INTO settings (setting_key, value) VALUES ('backup_time', ?) 
                              ON DUPLICATE KEY UPDATE value = ?");
        $stmt->execute([$time, $time]);

        // 2. Update Windows Task Scheduler
        $psScript = realpath(__DIR__ . '/../scripts/setup_daily_backup.ps1');
        $command = "powershell.exe -ExecutionPolicy Bypass -File \"$psScript\" -Time \"$time\"";
        
        $output = [];
        $returnVar = 0;
        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            return [
                'status' => 'partial_success', 
                'message' => 'Time saved to DB, but failed to update Task Scheduler. System permissions may be restricted.',
                'details' => implode("\n", $output)
            ];
        }

        return ['status' => 'success', 'message' => "Backup rescheduled to $time successfully."];
    }

    private function recursiveDelete($dir) {
        if (!file_exists($dir)) return true;
        if (!is_dir($dir)) return unlink($dir);
        foreach (scandir($dir) as $file) {
            if ($file == '.' || $file == '..') continue;
            if (!$this->recursiveDelete($dir . DIRECTORY_SEPARATOR . $file)) return false;
        }
        return rmdir($dir);
    }
}
