<?php
session_start();
require '../config/db.php'; // Database connection
require '../vendor/autoload.php'; // PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\IOFactory;

// Enable error logging
ini_set('display_errors', 0); // Disable in production
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/upload_error.log');
error_reporting(E_ALL);

// Function to log messages
function logMessage($message) {
    error_log(date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL, 3, __DIR__ . '/upload_error.log');
}

try {
    logMessage('Starting upload processing');

    // Validate file upload
    if (!isset($_FILES['file_excel']) || $_FILES['file_excel']['error'] != UPLOAD_ERR_OK) {
        $errorCodes = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds server size limit.',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds form size limit.',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.'
        ];
        $errorMsg = $errorCodes[$_FILES['file_excel']['error']] ?? 'Unknown upload error.';
        throw new Exception('File upload failed: ' . $errorMsg);
    }

    // Check file size (5MB limit, matching frontend)
    if ($_FILES['file_excel']['size'] > 5 * 1024 * 1024) {
        throw new Exception('File too large. Maximum size is 5MB.');
    }

    // Validate MIME type
    $file = $_FILES['file_excel']['tmp_name'];
    $fileType = mime_content_type($file);
    if ($fileType != 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
        throw new Exception('Invalid file type. Only .xlsx files are allowed.');
    }

    logMessage('File validated: ' . $_FILES['file_excel']['name']);

    // Increase memory and execution time for large files
    ini_set('memory_limit', '256M');
    ini_set('max_execution_time', 300);

    // Load spreadsheet with memory optimization
    $reader = IOFactory::createReader('Xlsx');
    $reader->setReadDataOnly(true); // Skip formatting to save memory
    $spreadsheet = $reader->load($file);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();

    logMessage('Excel file loaded. Row count: ' . count($rows));

    // Start database transaction
    $conn->begin_transaction();
    $successCount = 0;

    // Skip header row
    array_shift($rows);

    // Prepare statements for checking and inserting
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $insertStmt = $conn->prepare("INSERT INTO users (full_name, username, password, asal_sekolah, role) VALUES (?, ?, ?, ?, ?)");

    foreach ($rows as $index => $row) {
        // Assuming columns: full_name, username, password, asal_sekolah, role
        $full_name = trim($row[0] ?? '');
        $username = trim($row[1] ?? '');
        $password_raw = trim($row[2] ?? '');
        $asal_sekolah = trim($row[3] ?? '');
        $role = trim($row[4] ?? 'user');

        if (empty($full_name) || empty($username) || empty($password_raw)) {
            logMessage("Skipping row $index: Missing required fields (full_name, username, or password)");
            continue;
        }

        // Hash password
        $password_hash = password_hash($password_raw, PASSWORD_DEFAULT);
        if ($password_hash === false) {
            logMessage("Skipping row $index: Password hashing failed for username '$username'");
            continue;
        }

        // Check for duplicate username
        $checkStmt->bind_param("s", $username);
        $checkStmt->execute();
        $checkStmt->store_result();
        if ($checkStmt->num_rows > 0) {
            logMessage("Skipping row $index: Username '$username' already exists");
            continue;
        }

        // Insert data
        $insertStmt->bind_param("sssss", $full_name, $username, $password_hash, $asal_sekolah, $role);
        if ($insertStmt->execute()) {
            $successCount++;
            logMessage("Inserted row $index: Username '$username'");
        } else {
            logMessage("Failed to insert row $index: " . $insertStmt->error);
        }
    }

    $checkStmt->close();
    $insertStmt->close();
    $conn->commit();
    logMessage("Upload completed. Inserted $successCount records");

    $_SESSION['alert_type'] = 'success';
    $_SESSION['alert_message'] = "Berhasil mengunggah $successCount data pengguna.";
    header("Location: upload_users.php"); // Redirect back to upload page for feedback
} catch (Exception $e) {
    $conn->rollback();
    logMessage('Error: ' . $e->getMessage());
    $_SESSION['alert_type'] = 'danger';
    $_SESSION['alert_message'] = 'Gagal memproses file: ' . $e->getMessage();
    header("Location: upload_users.php");
} finally {
    $conn->close();
}

exit;
?>