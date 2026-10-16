<?php
// Start output buffering to prevent any accidental output
ob_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Session check
session_start();
if (empty($_SESSION['admin_id'])) {
    ob_end_clean();
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    $basePath = preg_replace('#/(admin_panel|backend)(/.*)?$#', '', $scriptDir);
    $basePath = ($basePath === '') ? '' : $basePath;
    header('Location: ' . $basePath . '/admin_panel/login.php');
    exit();
}

require_once __DIR__ . '/../dbconnect/db.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// Clear any previous output
ob_end_clean();

try {
    // Fetch all students with their latest attempt info
    $query = "
        SELECT 
            s.id,
            s.name,
            s.parent_name,
            s.surname,
            s.email,
            s.phone,
            s.city,
            s.standard,
            s.school,
            s.language,
            s.created_at,
            (SELECT COUNT(*) FROM student_attempts WHERE student_id = s.id) as attempts,
            (SELECT score FROM student_attempts WHERE student_id = s.id ORDER BY attempt_at DESC LIMIT 1) as last_score,
            (SELECT passed FROM student_attempts WHERE student_id = s.id ORDER BY attempt_at DESC LIMIT 1) as last_passed,
            (SELECT attempt_at FROM student_attempts WHERE student_id = s.id ORDER BY attempt_at DESC LIMIT 1) as last_attempt_at
        FROM students s
        ORDER BY s.id DESC
    ";
    
    $result = mysqli_query($conn, $query);
    if (!$result) {
        throw new Exception("Database query failed: " . mysqli_error($conn));
    }
    
    $students = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $students[] = $row;
    }
    mysqli_free_result($result);

    // Calculate statistics
    $totalStudents = count($students);
    $passedStudents = 0;
    $failedStudents = 0;
    $totalAttempts = 0;
    
    foreach ($students as $student) {
        if ($student['last_passed'] === '1' || $student['last_passed'] === 1) {
            $passedStudents++;
        } elseif ($student['last_passed'] === '0' || $student['last_passed'] === 0) {
            $failedStudents++;
        }
        $totalAttempts += (int)$student['attempts'];
    }

    // Create new Spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Student Records');
    
    // Set document properties
    $spreadsheet->getProperties()
        ->setCreator("Memory Test Admin")
        ->setTitle("Student Records Export")
        ->setSubject("Student Records")
        ->setDescription("Export of all student records from Memory Test application")
        ->setKeywords("students memory test records")
        ->setCategory("Academic Records");

    // Add Summary Section
    $sheet->setCellValue('A1', 'MEMORY TEST - STUDENT RECORDS SUMMARY');
    $sheet->mergeCells('A1:L1');
    $sheet->getStyle('A1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1D2B44']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
    ]);
    $sheet->getRowDimension(1)->setRowHeight(30);
    
    // Summary statistics
    $sheet->setCellValue('A2', 'Export Date:');
    $sheet->setCellValue('B2', date('F d, Y H:i:s'));
    $sheet->setCellValue('E2', 'Total Students:');
    $sheet->setCellValue('F2', $totalStudents);
    $sheet->setCellValue('H2', 'Passed:');
    $sheet->setCellValue('I2', $passedStudents);
    $sheet->setCellValue('J2', 'Failed:');
    $sheet->setCellValue('K2', $failedStudents);
    
    $sheet->getStyle('A2:A2')->getFont()->setBold(true);
    $sheet->getStyle('E2:E2')->getFont()->setBold(true);
    $sheet->getStyle('H2:H2')->getFont()->setBold(true)->getColor()->setRGB('008000');
    $sheet->getStyle('J2:J2')->getFont()->setBold(true)->getColor()->setRGB('FF0000');
    $sheet->getStyle('F2:F2')->getFont()->setBold(true);
    $sheet->getStyle('I2:I2')->getFont()->setBold(true)->getColor()->setRGB('008000');
    $sheet->getStyle('K2:K2')->getFont()->setBold(true)->getColor()->setRGB('FF0000');
    
    // Add empty row
    $sheet->getRowDimension(3)->setRowHeight(5);

    // Define headers (starting at row 4)
    $headers = [
        'ID', 'Full Name', 'Email', 'Phone Number', 'City', 
        'Standard', 'School Name', 'Language', 'Latest Score', 'Status', 
        'Total Attempts', 'Last Attempt Date', 'Registration Date'
    ];
    
    // Set headers
    $columnIndex = 1;
    foreach ($headers as $header) {
        $sheet->setCellValueByColumnAndRow($columnIndex, 4, $header);
        $columnIndex++;
    }
    
    // Style the header row
    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
            'size' => 12
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '4A90E2']
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_MEDIUM,
                'color' => ['rgb' => '000000']
            ]
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER
        ]
    ];
    $sheet->getStyle('A4:M4')->applyFromArray($headerStyle);
    $sheet->getRowDimension(4)->setRowHeight(25);
    
    // Freeze header rows (freeze first 4 rows including summary)
    $sheet->freezePane('A5');
    
    // Set column widths
    $sheet->getColumnDimension('A')->setWidth(8);  // ID
    $sheet->getColumnDimension('B')->setWidth(30); // Full Name
    $sheet->getColumnDimension('C')->setWidth(35); // Email
    $sheet->getColumnDimension('D')->setWidth(16); // Phone
    $sheet->getColumnDimension('E')->setWidth(15); // City
    $sheet->getColumnDimension('F')->setWidth(12); // Standard
    $sheet->getColumnDimension('G')->setWidth(30); // School
    $sheet->getColumnDimension('H')->setWidth(12); // Language
    $sheet->getColumnDimension('I')->setWidth(13); // Score
    $sheet->getColumnDimension('J')->setWidth(12); // Status
    $sheet->getColumnDimension('K')->setWidth(14); // Attempts
    $sheet->getColumnDimension('L')->setWidth(18); // Last Attempt
    $sheet->getColumnDimension('M')->setWidth(18); // Registration
    
    // Auto-filter on header row
    $sheet->setAutoFilter('A4:M4');
    
    // Fill data (starting at row 5)
    $row = 5;
    foreach ($students as $student) {
        // Build full name
        $fullName = $student['name'];
        if (!empty($student['parent_name'])) {
            $fullName .= ' ' . $student['parent_name'];
        }
        if (!empty($student['surname'])) {
            $fullName .= ' ' . $student['surname'];
        }

        // Language display (convert code to readable)
        $langDisplay = '-';
        if ($student['language'] === 'eng') $langDisplay = 'English';
        else if ($student['language'] === 'guj') $langDisplay = 'Gujarati';
        else if (!empty($student['language'])) $langDisplay = $student['language'];

        // Determine status
        $status = 'N/A';
        if ($student['last_passed'] === '1' || $student['last_passed'] === 1) {
            $status = 'Passed';
        } elseif ($student['last_passed'] === '0' || $student['last_passed'] === 0) {
            $status = 'Failed';
        }

        // Format dates
        $lastAttemptDate = !empty($student['last_attempt_at']) ? date('Y-m-d', strtotime($student['last_attempt_at'])) : '-';
        $registrationDate = !empty($student['created_at']) ? date('Y-m-d', strtotime($student['created_at'])) : '-';

        // Set cell values
        $sheet->setCellValueByColumnAndRow(1, $row, $student['id']);
        $sheet->setCellValueByColumnAndRow(2, $row, $fullName);
        $sheet->setCellValueByColumnAndRow(3, $row, $student['email']);
        $sheet->setCellValueByColumnAndRow(4, $row, $student['phone']);
        $sheet->setCellValueByColumnAndRow(5, $row, $student['city']);
        $sheet->setCellValueByColumnAndRow(6, $row, $student['standard']);
        $sheet->setCellValueByColumnAndRow(7, $row, $student['school']);
        $sheet->setCellValueByColumnAndRow(8, $row, $langDisplay);
        $sheet->setCellValueByColumnAndRow(9, $row, $student['last_score'] ?? '-');
        $sheet->setCellValueByColumnAndRow(10, $row, $status);
        $sheet->setCellValueByColumnAndRow(11, $row, $student['attempts']);
        $sheet->setCellValueByColumnAndRow(12, $row, $lastAttemptDate);
        $sheet->setCellValueByColumnAndRow(13, $row, $registrationDate);

        // Add alternating row colors
        if ($row % 2 == 0) {
            $sheet->getStyle('A' . $row . ':M' . $row)->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8F9FA']]
            ]);
        }

        // Style status cell based on pass/fail with background color
        if ($status === 'Passed') {
            $sheet->getStyleByColumnAndRow(10, $row)->applyFromArray([
                'font' => ['color' => ['rgb' => '008000'], 'bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D4EDDA']]
            ]);
        } elseif ($status === 'Failed') {
            $sheet->getStyleByColumnAndRow(10, $row)->applyFromArray([
                'font' => ['color' => ['rgb' => 'FF0000'], 'bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8D7DA']]
            ]);
        }

        // Highlight high scores (>= 50)
        if ($student['last_score'] !== null && $student['last_score'] >= 50) {
            $sheet->getStyleByColumnAndRow(9, $row)->getFont()->setBold(true)->getColor()->setRGB('0066CC');
        }

        $row++;
    }
    
    // Apply borders to all data
    $lastRow = $row - 1;
    if ($lastRow >= 5) {
        $sheet->getStyle('A4:M' . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'DDDDDD']
                ]
            ]
        ]);
    }
    
    // Center align ID, Score, Status, Attempts columns
    $sheet->getStyle('A5:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('H5:J' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('K5:L' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    // Add footer row with totals
    $footerRow = $lastRow + 2;
    $sheet->setCellValue('A' . $footerRow, 'TOTALS:');
    $sheet->setCellValue('B' . $footerRow, $totalStudents . ' Students');
    $sheet->setCellValue('I' . $footerRow, $passedStudents . ' Passed / ' . $failedStudents . ' Failed');
    $sheet->setCellValue('J' . $footerRow, $totalAttempts);
    
    $sheet->getStyle('A' . $footerRow . ':L' . $footerRow)->applyFromArray([
        'font' => ['bold' => true, 'size' => 11],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E9ECEF']],
        'borders' => [
            'top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '000000']]
        ]
    ]);
    
    // Generate filename with timestamp
    $filename = 'Student_Records_' . date('Y-m-d_His') . '.xlsx';
    
    // Create temp file
    $tempFile = tempnam(sys_get_temp_dir(), 'excel_');
    
    // Write to temp file first
    $writer = new Xlsx($spreadsheet);
    $writer->save($tempFile);
    
    // Clean up spreadsheet
    $spreadsheet->disconnectWorksheets();
    unset($spreadsheet);
    
    // Clear any output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Get file size
    $fileSize = filesize($tempFile);
    
    // Set headers for download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . $fileSize);
    header('Content-Transfer-Encoding: binary');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Expires: 0');
    
    // Output file
    readfile($tempFile);
    
    // Delete temp file
    unlink($tempFile);
    exit;
    
} catch (Exception $e) {
    // Clear any output
    if (ob_get_length()) {
        ob_end_clean();
    }
    // Return error as plain text since we're expecting an Excel file
    http_response_code(500);
    header('Content-Type: text/plain');
    echo 'Error generating Excel file: ' . $e->getMessage();
    exit;
}
