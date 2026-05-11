<?php
// src/Patient.php

namespace Hospital;

use PDO;
use Exception;

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/PDFManager.php';

class Patient {
    public static function create($mrn, $fullName, $dob, $gender, $department = '', $registrationDate = '', $rackNumber = '') {
        $department = trim($department);
        $db = Database::getInstance();
        
        // Check if MRN exists
        $stmt = $db->prepare("SELECT id FROM patients WHERE mrn = ?");
        $stmt->execute([$mrn]);
        if ($stmt->fetch()) {
            throw new Exception("Patient with MRN $mrn already exists.");
        }

        // Create initial file
        $pdfFilename = PDFManager::createInitialPDF($fullName, $mrn, '', $department, $registrationDate, $rackNumber);
        
        // Insert into DB
        $stmt = $db->prepare("INSERT INTO patients (mrn, full_name, dob, gender, department, registration_date, rack_number, master_pdf_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$mrn, $fullName, $dob, $gender, $department, $registrationDate, $rackNumber, $pdfFilename]);
        
        return $db->lastInsertId();
    }

    public static function getByMRN($identifier) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM patients WHERE mrn = ?");
        $stmt->execute([$identifier]);
        return $stmt->fetch();
    }

    public static function getAll() {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM patients ORDER BY updated_at DESC");
        return $stmt->fetchAll();
    }

    public static function appendRecord($mrn, $tmpFilePath, $originalFilename) {
        $patient = self::getByMRN($mrn);
        if (!$patient) {
            throw new Exception("Patient not found.");
        }
        
        // 1. Save individual file
        if (!is_dir(Config::INDIVIDUAL_STORAGE_PATH)) {
            mkdir(Config::INDIVIDUAL_STORAGE_PATH, 0777, true);
        }
        
        $ext = pathinfo($originalFilename, PATHINFO_EXTENSION);
        $uniqueFilename = $mrn . '_' . time() . '_' . uniqid() . '.' . $ext;
        $individualPath = Config::INDIVIDUAL_STORAGE_PATH . $uniqueFilename;
        copy($tmpFilePath, $individualPath);
        
        // 2. Append to Master File
        $masterPath = Config::STORAGE_PATH . $patient['master_pdf_path'];
        PDFManager::appendPDF($masterPath, $individualPath, $originalFilename);
        
        // 3. Log upload with file path and order
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM upload_history WHERE patient_id = ?");
        $stmt->execute([$patient['id']]);
        $nextOrder = $stmt->fetchColumn();

        $stmt = $db->prepare("INSERT INTO upload_history (patient_id, filename, file_path, sort_order) VALUES (?, ?, ?, ?)");
        $stmt->execute([$patient['id'], $originalFilename, $uniqueFilename, $nextOrder]);
        
        // 4. Update updated_at
        self::touch($patient['id']);
    }

    public static function touch($patientId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE patients SET updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$patientId]);
    }

    public static function getHistory($mrn) {
        $patient = self::getByMRN($mrn);
        if (!$patient) return [];
        
        $db = Database::getInstance();
        // Sort by sort_order DESC to show newest/highest order on top by default, or ASC for manual control
        // Let's go with ASC so drag and drop feels natural (position 1, 2, 3...)
        $stmt = $db->prepare("SELECT * FROM upload_history WHERE patient_id = ? ORDER BY sort_order ASC");
        $stmt->execute([$patient['id']]);
        return $stmt->fetchAll();
    }

    public static function updateOrder($mrn, $orderArray) {
        $patient = self::getByMRN($mrn);
        if (!$patient) throw new Exception("Patient not found.");

        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            foreach ($orderArray as $index => $id) {
                // Ensure IDs are treated as integers
                $numericId = (int)$id;
                $stmt = $db->prepare("UPDATE upload_history SET sort_order = ? WHERE id = ? AND patient_id = ?");
                $stmt->execute([$index + 1, $numericId, (int)$patient['id']]);
            }
            $db->commit();
            
            // Rebuild Master File
            self::regenerateMasterFile($mrn);

            // Update patient updated_at
            self::touch($patient['id']);
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function regenerateMasterFile($mrn) {
        $patient = self::getByMRN($mrn);
        if (!$patient) throw new Exception("Patient not found.");

        $history = self::getHistory($mrn);
        
        // Use PDFManager to create a fresh master file
        PDFManager::rebuildMasterPDF(
            $patient, 
            $history
        );
    }
}
