<?php
// public/api.php

use Hospital\Config;
use Hospital\Patient;

require_once __DIR__ . '/../src/Config.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Patient.php';

require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/BackupManager.php';

header('Content-Type: application/json');
ini_set('display_errors', 0); // Prevent warnings from leaking into JSON
ini_set('upload_max_filesize', '100M');
ini_set('post_max_size', '110M');
ini_set('memory_limit', '512M');
error_reporting(E_ALL); 
ob_start(); // Buffer all output to catch unexpected warnings/notices

function sendJsonResponse($data) {
    if (ob_get_length()) ob_end_clean();
    echo json_encode($data);
    exit;
}

// Auto-migration for sort_order
try {
    $db_mig = \Hospital\Database::getInstance();
    $check = $db_mig->query("SHOW COLUMNS FROM upload_history LIKE 'sort_order'");
    if (!$check->fetch()) {
        $db_mig->exec("ALTER TABLE upload_history ADD COLUMN sort_order INT DEFAULT 0");
        $db_mig->exec("UPDATE upload_history SET sort_order = id");
    }
} catch (Exception $e) {}

$action = $_GET['action'] ?? '';

// Allow logout without check, otherwise check auth
if ($action === 'logout') {
    \Hospital\Auth::logout();
    header("Location: login.php");
    exit;
}

if (!\Hospital\Auth::check()) {
    http_response_code(401);
    sendJsonResponse(['success' => false, 'error' => 'Authentication required']);
}

try {
    switch ($action) {
        case 'backup_download':
            \Hospital\Auth::requireAdmin();
            $filename = $_GET['filename'] ?? '';
            if (!$filename) throw new Exception("Filename required.");
            $path = realpath(__DIR__ . '/../backups/' . basename($filename));
            if (!$path || !file_exists($path)) throw new Exception("Backup file not found.");
            
            header('Content-Description: File Transfer');
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . basename($path) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($path));
            readfile($path);
            exit;
            break;

        case 'stats':
            $db = \Hospital\Database::getInstance();
            
            // Total Patients
            $stmt = $db->query("SELECT COUNT(*) FROM patients");
            $totalPatients = $stmt->fetchColumn();
            
            // Files Currently Issued
            $stmt = $db->query("SELECT COUNT(*) FROM file_issuances WHERE status = 'Issued'");
            $issuedFiles = $stmt->fetchColumn();
            
            // Admissions Today
            $today = date('Y-m-d');
            $stmt = $db->prepare("SELECT COUNT(*) FROM patients WHERE registration_date = ?");
            $stmt->execute([$today]);
            $todayAdmissions = $stmt->fetchColumn();
            
            // Total Issuances (Cumulative)
            $stmt = $db->query("SELECT COUNT(*) FROM file_issuances");
            $totalIssuances = $stmt->fetchColumn();
            
            // Recent Activity
            $stmt = $db->query("SELECT mrn, full_name, created_at, 'registration' as type FROM patients ORDER BY created_at DESC LIMIT 5");
            $recentPatients = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $stmt = $db->query("SELECT p.mrn, p.full_name, f.issued_to, f.created_at, 'issuance' as type FROM file_issuances f JOIN patients p ON f.patient_id = p.id ORDER BY f.created_at DESC LIMIT 5");
            $recentIssuances = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            sendJsonResponse([
                'totalPatients' => $totalPatients,
                'issuedFiles' => $issuedFiles,
                'totalIssuances' => $totalIssuances,
                'todayAdmissions' => $todayAdmissions,
                'recentActivity' => array_slice(array_merge($recentPatients, $recentIssuances), 0, 8)
            ]);
            break;

        case 'dashboard_analytics':
            $db = \Hospital\Database::getInstance();
            
            // 1. Patients by Department
            $stmt = $db->query("SELECT department, COUNT(*) as count FROM patients GROUP BY department ORDER BY count DESC");
            $deptStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // 2. Most Outgoing Files (Top 5 Issued)
            $stmt = $db->query("SELECT p.full_name, p.mrn, p.department, COUNT(f.id) as issuances FROM file_issuances f JOIN patients p ON f.patient_id = p.id GROUP BY f.patient_id ORDER BY issuances DESC LIMIT 5");
            $topFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

            sendJsonResponse([
                'deptStats' => $deptStats,
                'topFiles' => $topFiles
            ]);
            break;

        case 'search_rack':
            $term = $_GET['term'] ?? '';
            if (strlen($term) < 2) {
                sendJsonResponse([]); // Return empty if term is too short
                break;
            }
            
            $db = \Hospital\Database::getInstance();
            $searchTerm = "%$term%";
            
            // Search in Patients (Name/MRN) AND File Issuances (Doctor/Issued To)
            // Using UNION to combine results and DISTINCT to avoid duplicates
            $sql = "
                SELECT DISTINCT p.id, p.mrn, p.full_name, p.rack_number, p.department, 'Direct Match' as match_type 
                FROM patients p 
                WHERE p.mrn LIKE ? OR p.full_name LIKE ?
                UNION
                SELECT DISTINCT p.id, p.mrn, p.full_name, p.rack_number, p.department, 'History Match' as match_type
                FROM patients p 
                JOIN file_issuances f ON f.patient_id = p.id 
                WHERE f.issued_to LIKE ?
                LIMIT 20
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            sendJsonResponse($results);
            break;
        case 'register':
            $mrn = $_POST['mrn'] ?? '';
            $fullName = $_POST['full_name'] ?? '';
            $dob = $_POST['dob'] ?? '';
            $gender = $_POST['gender'] ?? '';
            $department = $_POST['department'] ?? '';
            $regDate = $_POST['reg_date'] ?? '';
            $rackNumber = $_POST['rack_number'] ?? '';

            if (!$mrn || !$fullName) {
                throw new Exception("MRN and Name are required.");
            }

            Patient::create($mrn, $fullName, $dob, $gender, $department, $regDate, $rackNumber);
            sendJsonResponse(['success' => true, 'message' => 'Patient registered and master file created.']);
            break;

        case 'append':
            $mrn = $_POST['mrn'] ?? '';
            if (!$mrn) throw new Exception("MRN is required.");

            $uploadedFiles = [];
            $totalSize = 0;
            $maxSize = 30 * 1024 * 1024; // 30MB in bytes

            // Handle multiple files if sent via files[]
            if (!empty($_FILES['files']['tmp_name'][0])) {
                foreach ($_FILES['files']['tmp_name'] as $key => $tmpName) {
                    $fileSize = $_FILES['files']['size'][$key];
                    $totalSize += $fileSize;
                    
                    $uploadedFiles[] = [
                        'tmp_name' => $tmpName,
                        'name' => $_FILES['files']['name'][$key],
                        'size' => $fileSize
                    ];
                }
            } 
            // Handle single file if sent via "file" (backwards compatibility)
            else if (!empty($_FILES['file']['tmp_name'])) {
                $totalSize = $_FILES['file']['size'];
                $uploadedFiles[] = [
                    'tmp_name' => $_FILES['file']['tmp_name'],
                    'name' => $_FILES['file']['name'],
                    'size' => $totalSize
                ];
            }

            if (empty($uploadedFiles)) {
                throw new Exception("No files uploaded.");
            }

            // Validate total size
            if ($totalSize > $maxSize) {
                $sizeMB = round($totalSize / 1024 / 1024, 2);
                throw new Exception("Total file size ({$sizeMB} MB) exceeds the 30MB limit. Please upload fewer or smaller files.");
            }

            foreach ($uploadedFiles as $file) {
                Patient::appendRecord($mrn, $file['tmp_name'], $file['name']);
            }

            sendJsonResponse(['success' => true, 'message' => count($uploadedFiles) . ' record(s) successfully appended.']);
            break;

        case 'issue_file':
            $mrn = $_POST['mrn'] ?? '';
            $issuedTo = $_POST['issued_to'] ?? '';
            $phoneNumber = $_POST['phone_number'] ?? '';
            $dept = $_POST['department'] ?? '';
            $issueDate = $_POST['issue_date'] ?? '';
            $returnDate = !empty($_POST['return_date']) ? $_POST['return_date'] : null;
            $remarks = $_POST['remarks'] ?? '';

            if (!$mrn || !$issuedTo) {
                throw new Exception("MRN and Recipient Name are required.");
            }

            $patient = Patient::getByMRN($mrn);
            if (!$patient) {
                throw new Exception("Patient not found.");
            }

            $db = \Hospital\Database::getInstance();
            
            // Critical: Check if already issued
            $check = $db->prepare("SELECT id, issued_to FROM file_issuances WHERE patient_id = ? AND status = 'Issued'");
            $check->execute([$patient['id']]);
            $existing = $check->fetch();
            if ($existing) {
                throw new Exception("File is currently issued to " . $existing['issued_to'] . ". Please return it first.");
            }

            $stmt = $db->prepare("INSERT INTO file_issuances (patient_id, issued_to, phone_number, department, issue_date, return_date, remarks) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$patient['id'], $issuedTo, $phoneNumber, $dept, $issueDate, $returnDate, $remarks]);
            
            sendJsonResponse(['success' => true, 'message' => 'Medical file issued successfully.']);
            break;

        case 'check_issuance':
            $mrn = $_GET['mrn'] ?? '';
            if (!$mrn) throw new Exception("MRN required.");
            
            $patient = Patient::getByMRN($mrn);
            if (!$patient) throw new Exception("Patient not found.");

            $db = \Hospital\Database::getInstance();
            $stmt = $db->prepare("SELECT * FROM file_issuances WHERE patient_id = ? AND status = 'Issued' ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$patient['id']]);
            $issuance = $stmt->fetch();

            if ($issuance) {
                sendJsonResponse(['status' => 'Issued', 'data' => $issuance]);
            } else {
                sendJsonResponse(['status' => 'Available']);
            }
            break;

        case 'list_issuances':
            $db = \Hospital\Database::getInstance();
            $search = $_GET['search'] ?? '';
            $filter = $_GET['filter'] ?? '';

            if (!empty($search)) {
                // If searching, search across ALL patients and join their latest issuance status
                $query = "SELECT p.id as patient_id, p.full_name, p.mrn, p.department as patient_dept,
                          f.id, f.issued_to, f.status, f.issue_date, f.return_date, f.remarks, f.phone_number, f.department,
                          (SELECT COUNT(*) FROM file_issuances f2 WHERE f2.patient_id = p.id) as total_issuances
                          FROM patients p
                          LEFT JOIN file_issuances f ON f.id = (SELECT MAX(id) FROM file_issuances WHERE patient_id = p.id)
                          WHERE (p.full_name LIKE ? OR p.mrn LIKE ? OR f.issued_to LIKE ? OR f.phone_number LIKE ?)";
                $params = ["%$search%", "%$search%", "%$search%", "%$search%"];
            } else {
                // Base query: original behavior for list/filter
                $query = "SELECT f.*, f.patient_id, p.full_name, p.mrn,
                          (SELECT COUNT(*) FROM file_issuances f2 WHERE f2.patient_id = f.patient_id) as total_issuances
                          FROM file_issuances f 
                          JOIN patients p ON f.patient_id = p.id";
                
                $whereClauses = [];
                $params = [];

                if ($filter === 'weekly') {
                    $whereClauses[] = "f.issue_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                } elseif ($filter === 'monthly') {
                    $whereClauses[] = "f.issue_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                } else {
                    $whereClauses[] = "f.id IN (SELECT MAX(id) FROM file_issuances GROUP BY patient_id)";
                }

                if (!empty($whereClauses)) {
                    $query .= " WHERE " . implode(" AND ", $whereClauses);
                }
            }

            $query .= " ORDER BY " . (!empty($search) ? "p.full_name ASC" : "f.created_at DESC");
            
            // Data healing: Ensure Returned files MUST have a return date
            try {
                $db->exec("UPDATE file_issuances SET return_date = issue_date WHERE status = 'Returned' AND (return_date IS NULL OR return_date = '' OR return_date = '0000-00-00 00:00:00')");
            } catch (Exception $e) {}

            $stmt = $db->prepare($query);
            $stmt->execute($params);
            sendJsonResponse($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'get_issuance_history':
            $patientId = $_GET['patient_id'] ?? '';
            if (!$patientId) throw new Exception("Patient ID required.");

            $db = \Hospital\Database::getInstance();
            $stmt = $db->prepare("SELECT * FROM file_issuances WHERE patient_id = ? ORDER BY created_at DESC");
            $stmt->execute([$patientId]);
            sendJsonResponse($stmt->fetchAll());
            break;

        case 'return_file':
            $id = $_POST['id'] ?? '';
            if (!$id) throw new Exception("ID required.");
            
            $returnDate = date('Y-m-d H:i:s'); // Auto-set to current time

            $db = \Hospital\Database::getInstance();
            $stmt = $db->prepare("UPDATE file_issuances SET status = 'Returned', return_date = ? WHERE id = ?");
            $stmt->execute([$returnDate, $id]);
            sendJsonResponse(['success' => true, 'message' => 'File returned successfully.']);
            break;

        case 'list':
            $db = \Hospital\Database::getInstance();
            // Subquery to check for active issuance
            $query = "SELECT p.*, 
                      (SELECT id FROM file_issuances f WHERE f.patient_id = p.id AND f.status = 'Issued' LIMIT 1) as active_issuance_id,
                      (SELECT issued_to FROM file_issuances f WHERE f.patient_id = p.id AND f.status = 'Issued' LIMIT 1) as active_issued_to,
                      (SELECT phone_number FROM file_issuances f WHERE f.patient_id = p.id AND f.status = 'Issued' LIMIT 1) as active_phone_number,
                      (SELECT department FROM file_issuances f WHERE f.patient_id = p.id AND f.status = 'Issued' LIMIT 1) as active_department,
                      (SELECT issue_date FROM file_issuances f WHERE f.patient_id = p.id AND f.status = 'Issued' LIMIT 1) as active_issue_date,
                      (SELECT remarks FROM file_issuances f WHERE f.patient_id = p.id AND f.status = 'Issued' LIMIT 1) as active_remarks,
                      (SELECT COUNT(*) FROM file_issuances f2 WHERE f2.patient_id = p.id) as total_issuances
                      FROM patients p WHERE 1=1";
            $params = [];

            // Advanced Filters
            if (!empty($_GET['search'])) {
                $query .= " AND (p.mrn LIKE ? OR p.full_name LIKE ? OR p.rack_number LIKE ?)";
                $params[] = "%".$_GET['search']."%";
                $params[] = "%".$_GET['search']."%";
                $params[] = "%".$_GET['search']."%";
            }

            if (!empty($_GET['department'])) {
                $query .= " AND p.department = ?";
                $params[] = $_GET['department'];
            }

            if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
                $query .= " AND p.registration_date BETWEEN ? AND ?";
                $params[] = $_GET['start_date'];
                $params[] = $_GET['end_date'];
            }

            $query .= " ORDER BY p.updated_at DESC";
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            sendJsonResponse($stmt->fetchAll());
            break;

        case 'view':
            // Securely serve the Master File
            $mrn = $_GET['mrn'] ?? '';
            $patient = Patient::getByMRN($mrn);
            if (!$patient) {
                http_response_code(404);
                die("Patient not found.");
            }

            $filePath = Config::STORAGE_PATH . $patient['master_pdf_path'];
            if (!file_exists($filePath)) {
                http_response_code(404);
                die("File not found.");
            }

            header('Content-Type: application/pdf');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Cache-Control: post-check=0, pre-check=0', false);
            header('Pragma: no-cache');
            header('Content-Disposition: inline; filename="' . $patient['master_pdf_path'] . '"');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;

        case 'history':
            $mrn = $_GET['mrn'] ?? '';
            sendJsonResponse(Patient::getHistory($mrn));
            break;

        case 'get_patient':
            $mrn = $_GET['mrn'] ?? '';
            $patient = Patient::getByMRN($mrn);
            if ($patient) {
                sendJsonResponse(['success' => true, 'patient' => $patient]);
            } else {
                sendJsonResponse(['success' => false, 'error' => 'Patient not found']);
            }
            break;

        case 'reorder':
            $mrn = $_POST['mrn'] ?? '';
            $order = $_POST['order'] ?? ''; // Expecting JSON array of IDs
            if (!$mrn || !$order) {
                sendJsonResponse(['success' => false, 'error' => 'MRN and Order are required.']);
            }
            $orderArray = json_decode($order, true);
            if (!is_array($orderArray)) {
                sendJsonResponse(['success' => false, 'error' => 'Invalid order data format.']);
            }
            try {
                Patient::updateOrder($mrn, $orderArray);
                sendJsonResponse(['success' => true, 'message' => 'Medical record order updated and Master File reconstructed.']);
            } catch (Exception $e) {
                sendJsonResponse(['success' => false, 'error' => $e->getMessage()]);
            }
            break;

        case 'suggestions':
            $query = $_GET['q'] ?? '';
            if (strlen($query) < 2) {
                echo json_encode([]);
                break;
            }
            $db = \Hospital\Database::getInstance();
            $stmt = $db->prepare("SELECT mrn, full_name FROM patients WHERE mrn LIKE ? OR full_name LIKE ? LIMIT 10");
            $searchTerm = "%$query%";
            $stmt->execute([$searchTerm, $searchTerm]);
            sendJsonResponse($stmt->fetchAll());
            break;

        case 'view_individual':
            $id = $_GET['id'] ?? '';
            $db = \Hospital\Database::getInstance();
            $stmt = $db->prepare("SELECT * FROM upload_history WHERE id = ?");
            $stmt->execute([$id]);
            $record = $stmt->fetch();
            
            if (!$record || empty($record['file_path'])) {
                http_response_code(404);
                die("Record or file not found.");
            }

            $filePath = Config::INDIVIDUAL_STORAGE_PATH . $record['file_path'];
            if (!file_exists($filePath)) {
                http_response_code(404);
                die("Physical file missing.");
            }

            $ext = strtolower(pathinfo($record['file_path'], PATHINFO_EXTENSION));
            $mimeTypes = [
                'pdf' => 'application/pdf',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                'doc' => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ];
            
            $contentType = $mimeTypes[$ext] ?? 'application/octet-stream';
            header("Content-Type: $contentType");
            header('Content-Disposition: inline; filename="' . $record['filename'] . '"');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;

        case 'user_list':
            \Hospital\Auth::requireAdmin();
            $db = \Hospital\Database::getInstance();
            $stmt = $db->query("SELECT id, username, role, created_at FROM users ORDER BY created_at DESC");
            sendJsonResponse($stmt->fetchAll());
            break;

        case 'user_add':
            \Hospital\Auth::requireAdmin();
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'user';

            if (!$username || !$password) {
                throw new Exception("Username and password are required.");
            }

            $db = \Hospital\Database::getInstance();
            // Check if user exists
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                throw new Exception("User already exists.");
            }

            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt->execute([$username, $hashed, $role]);
            echo json_encode(['success' => true, 'message' => 'User created successfully.']);
            break;

        case 'user_delete':
            \Hospital\Auth::requireAdmin();
            $id = $_POST['id'] ?? '';
            if (!$id) throw new Exception("User ID required.");
            
            // Prevent deleting self
            if ($id == $_SESSION['user_id']) {
                throw new Exception("You cannot delete your own account.");
            }

            $db = \Hospital\Database::getInstance();
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'User deleted successfully.']);
            break;

        case 'delete_patient':
            \Hospital\Auth::requireAdmin();
            $mrn = $_POST['mrn'] ?? '';
            if (!$mrn) throw new Exception("MRN required.");
            
            $patient = Patient::getByMRN($mrn);
            if (!$patient) throw new Exception("Patient not found.");

            $db = \Hospital\Database::getInstance();
            $db->beginTransaction();
            try {
                // Delete issuance history
                $db->prepare("DELETE FROM file_issuances WHERE patient_id = ?")->execute([$patient['id']]);
                
                // Get upload history to delete physical files
                $stmt = $db->prepare("SELECT file_path FROM upload_history WHERE patient_id = ?");
                $stmt->execute([$patient['id']]);
                $records = $stmt->fetchAll();
                foreach ($records as $record) {
                    $filePath = \Hospital\Config::INDIVIDUAL_STORAGE_PATH . $record['file_path'];
                    if (file_exists($filePath)) unlink($filePath);
                }

                // Delete upload history
                $db->prepare("DELETE FROM upload_history WHERE patient_id = ?")->execute([$patient['id']]);
                // Delete patient
                $db->prepare("DELETE FROM patients WHERE id = ?")->execute([$patient['id']]);
                
                // Delete physical master file
                $masterPath = \Hospital\Config::STORAGE_PATH . $patient['master_pdf_path'];
                if (file_exists($masterPath)) unlink($masterPath);
                
                $db->commit();
                sendJsonResponse(['success' => true, 'message' => 'Patient and all associated records deleted.']);
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
            break;

        case 'delete_record':
            \Hospital\Auth::requireAdmin();
            $id = $_POST['id'] ?? '';
            if (!$id) throw new Exception("Record ID required.");

            $db = \Hospital\Database::getInstance();
            $stmt = $db->prepare("SELECT u.*, p.mrn FROM upload_history u JOIN patients p ON u.patient_id = p.id WHERE u.id = ?");
            $stmt->execute([$id]);
            $record = $stmt->fetch();
            if (!$record) throw new Exception("Record not found.");

            // Delete individual file
            $filePath = Config::INDIVIDUAL_STORAGE_PATH . $record['file_path'];
            if (file_exists($filePath)) unlink($filePath);

            // Delete from DB
            $db->prepare("DELETE FROM upload_history WHERE id = ?")->execute([$id]);

            // Reconstruct Master PDF
            Patient::regenerateMasterFile($record['mrn']);

            sendJsonResponse(['success' => true, 'message' => 'Record deleted and Master File updated.']);
            break;

        case 'user_update_password':
            \Hospital\Auth::requireAdmin();
            $id = $_POST['id'] ?? '';
            $adminPassword = $_POST['admin_password'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if (!$id || !$password || !$adminPassword) {
                throw new Exception("User ID, new password, and admin password are required.");
            }
            
            if (strlen($password) < 4) {
                throw new Exception("Password must be at least 4 characters.");
            }

            $db = \Hospital\Database::getInstance();
            
            // Verify admin's current password
            $adminId = $_SESSION['user_id'];
            $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$adminId]);
            $admin = $stmt->fetch();
            
            if (!$admin || !password_verify($adminPassword, $admin['password'])) {
                throw new Exception("Invalid admin password. Please enter your current password correctly.");
            }

            // Update the target user's password
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $id]);
            echo json_encode(['success' => true, 'message' => 'Password updated successfully.']);
            break;

        case 'backup_create':
            \Hospital\Auth::requireAdmin();
            $manager = new \Hospital\BackupManager();
            sendJsonResponse($manager->createBackup());
            break;

        case 'backup_list':
            \Hospital\Auth::requireAdmin();
            $manager = new \Hospital\BackupManager();
            sendJsonResponse($manager->getBackups());
            break;

        case 'backup_delete':
            \Hospital\Auth::requireAdmin();
            $filename = $_POST['filename'] ?? '';
            if (!$filename) throw new Exception("Filename required.");
            $manager = new \Hospital\BackupManager();
            if ($manager->deleteBackup($filename)) {
                sendJsonResponse(['success' => true]);
            } else {
                sendJsonResponse(['success' => false, 'error' => 'Failed to delete backup.']);
            }
            break;

        case 'backup_restore':
            \Hospital\Auth::requireAdmin();
            $filename = $_POST['filename'] ?? '';
            if (!$filename) throw new Exception("Filename required.");
            $manager = new \Hospital\BackupManager();
            sendJsonResponse($manager->restoreBackup($filename));
            break;
            
        case 'backup_upload_restore':
            \Hospital\Auth::requireAdmin();
            if (!isset($_FILES['backup_file'])) throw new Exception("No file uploaded.");
            $manager = new \Hospital\BackupManager();
            sendJsonResponse($manager->restoreFromUpload($_FILES['backup_file']['tmp_name']));
            break;

        case 'backup_get_schedule':
            \Hospital\Auth::requireAdmin();
            $manager = new \Hospital\BackupManager();
            sendJsonResponse(['status' => 'success', 'time' => $manager->getSchedule()]);
            break;

        case 'backup_set_schedule':
            \Hospital\Auth::requireAdmin();
            $time = $_POST['time'] ?? '';
            if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time)) throw new Exception("Invalid time format (HH:mm or HH:mm:ss).");
            $manager = new \Hospital\BackupManager();
            sendJsonResponse($manager->updateSchedule($time));
            break;

        default:
            throw new Exception("Invalid action.");
    }
} catch (\Throwable $e) {
    sendJsonResponse(['success' => false, 'error' => $e->getMessage()]);
}
