<?php
require_once 'src/Config.php';
require_once 'src/Database.php';
require_once 'src/BackupManager.php';

$manager = new \Hospital\BackupManager();
$result = $manager->createBackup();
print_r($result);
