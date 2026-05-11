<?php
// src/PDFManager.php

namespace Hospital;

use setasign\Fpdi\Fpdi;
use Exception;

require_once __DIR__ . '/../vendor/autoload.php';

class PDFManager {
    /**
     * Creates an initial file for a new patient.
     */
    public static function createInitialPDF($patientName, $mrn, $fileNumber, $department = '', $regDate = '', $rackNumber = '') {
        $pdf = new Fpdi();
        self::addCoverPage($pdf, $patientName, $mrn, $department, $regDate, $rackNumber);
        
        $filename = 'master_' . preg_replace('/[^a-zA-Z0-9]/', '_', $mrn) . '.pdf';
        $path = Config::STORAGE_PATH . $filename;
        
        $pdf->Output('F', $path);
        return $filename;
    }

    private static function addCoverPage($pdf, $patientName, $mrn, $department, $regDate, $rackNumber) {
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, 'Patient Master Medical Record', 0, 1, 'C');
        $pdf->Ln(10);
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 10, 'Patient Name: ' . $patientName, 0, 1);
        $pdf->Cell(0, 10, 'MR#: ' . $mrn, 0, 1);
        if ($rackNumber) $pdf->Cell(0, 10, 'Rack Number: ' . $rackNumber, 0, 1);
        if ($department) $pdf->Cell(0, 10, 'Department: ' . $department, 0, 1);
        if ($regDate) $pdf->Cell(0, 10, 'Admission Date: ' . $regDate, 0, 1);
        $pdf->Cell(0, 10, 'Print Date: ' . date('Y-m-d H:i:s'), 0, 1);
        $pdf->Ln(10);
        $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
    }

    /**
     * Appends a document (PDF, Image, or Word) to the existing master file.
     */
    public static function appendPDF($masterPath, $newFilePath, $originalFilename) {
        $pdf = new Fpdi();
        
        // 1. Add pages from existing master
        try {
            $pageCount = $pdf->setSourceFile($masterPath);
            for ($n = 1; $n <= $pageCount; $n++) {
                $tplIdx = $pdf->importPage($n);
                $specs = $pdf->getImportedPageSize($tplIdx);
                $pdf->AddPage($specs['orientation'], [$specs['width'], $specs['height']]);
                $pdf->useTemplate($tplIdx);
            }
        } catch (Exception $e) {
            throw new Exception("Error reading master PDF: " . $e->getMessage());
        }
        
        // 2. Add pages from the new file
        self::addContentToPDF($pdf, $newFilePath, $originalFilename);
        
        $pdf->Output('F', $masterPath);
    }

    /**
     * Rebuilds the entire master PDF from scratch using individual files.
     */
    public static function rebuildMasterPDF($patient, $history) {
        $pdf = new Fpdi();
        
        // 1. Add Cover Page
        self::addCoverPage(
            $pdf, 
            $patient['full_name'], 
            $patient['mrn'], 
            $patient['department'], 
            $patient['registration_date'], 
            $patient['rack_number']
        );

        // 2. Add each individual file from history
        foreach ($history as $item) {
            $filePath = Config::INDIVIDUAL_STORAGE_PATH . $item['file_path'];
            if (file_exists($filePath)) {
                self::addContentToPDF($pdf, $filePath, $item['filename']);
            }
        }

        $masterPath = Config::STORAGE_PATH . $patient['master_pdf_path'];
        $pdf->Output('F', $masterPath);
    }

    /**
     * Helper to add content based on file type
     */
    private static function addContentToPDF($pdf, $filePath, $originalFilename) {
        $ext = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
        
        if ($ext === 'pdf') {
            try {
                $pageCount = $pdf->setSourceFile($filePath);
                for ($n = 1; $n <= $pageCount; $n++) {
                    $tplIdx = $pdf->importPage($n);
                    $specs = $pdf->getImportedPageSize($tplIdx);
                    $pdf->AddPage($specs['orientation'], [$specs['width'], $specs['height']]);
                    $pdf->useTemplate($tplIdx);
                }
            } catch (Exception $e) {
                if (strpos($e->getMessage(), 'compression technique') !== false || strpos($e->getMessage(), 'parser') !== false) {
                    // Fallback: Add a page describing the error
                    $pdf->AddPage();
                    $pdf->SetFont('Arial', 'B', 12);
                    $pdf->Cell(0, 10, 'Error including PDF: ' . $originalFilename, 0, 1);
                    $pdf->SetFont('Arial', '', 10);
                    $pdf->MultiCell(0, 10, "This PDF version is not supported for merging. Please use PDF version 1.4 or 'Reduced Size PDF'.");
                } else {
                    throw $e;
                }
            }
        } else if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $pdf->AddPage();
            $pdf->SetFont('Arial', 'I', 10);
            $pdf->Cell(0, 10, 'Uploaded Image: ' . $originalFilename, 0, 1);
            
            $imgPath = $filePath;
            $fpdfType = strtoupper($ext === 'jpeg' ? 'jpg' : $ext);
            
            if ($ext === 'webp' || $ext === 'gif') {
                $tempJpg = $filePath . '.jpg';
                $image = ($ext === 'webp') ? imagecreatefromwebp($filePath) : imagecreatefromgif($filePath);
                if ($image) {
                    imagejpeg($image, $tempJpg, 90);
                    imagedestroy($image);
                    $imgPath = $tempJpg;
                    $fpdfType = 'JPG';
                }
            }

            // Get image dimensions for scaling
            list($width, $height) = getimagesize($imgPath);
            $maxWidth = 190;
            $maxHeight = 250;
            $ratio = min($maxWidth / $width, $maxHeight / $height);
            $newWidth = $width * $ratio;
            
            $pdf->Image($imgPath, 10, 20, $newWidth, 0, $fpdfType);

            if ($imgPath !== $filePath && file_exists($imgPath)) {
                unlink($imgPath);
            }
        } else if (in_array($ext, ['doc', 'docx'])) {
            self::appendWordAsText($pdf, $filePath, $originalFilename);
        }
    }

    private static function appendWordAsText($pdf, $filePath, $filename) {
        $text = "";
        if (class_exists('\PhpOffice\PhpWord\IOFactory')) {
            try {
                $phpWord = \PhpOffice\PhpWord\IOFactory::load($filePath);
                foreach($phpWord->getSections() as $section) {
                    foreach($section->getElements() as $element) {
                        if (method_exists($element, 'getText')) {
                            $text .= $element->getText() . "\n";
                        }
                    }
                }
            } catch (Exception $e) { $text = "Error reading Word file."; }
        } else { $text = self::readDocxText($filePath); }

        if (empty($text)) $text = "[Word file uploaded: $filename - No text extracted]";

        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'Document: ' . $filename, 0, 1);
        $pdf->Ln(5);
        $pdf->SetFont('Arial', '', 10);
        $pdf->MultiCell(0, 5, $text);
    }

    private static function readDocxText($filename) {
        $content = '';
        if (!$filename || !file_exists($filename)) return false;
        $zip = new \ZipArchive();
        if ($zip->open($filename) === true) {
            if (($index = $zip->locateName('word/document.xml')) !== false) {
                $data = $zip->getFromIndex($index);
                $zip->close();
                $content = strip_tags($data);
            }
            $zip->close();
        }
        return $content;
    }
}
