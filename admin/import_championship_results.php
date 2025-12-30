<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['upload_error_message'] = "Akses tidak sah.";
    header('Location: manage_championship_results.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excelFile'])) {
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $file = $_FILES['excelFile'];
    $fileName = basename($file['name']);
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];
    $fileType = mime_content_type($fileTmpName);

    $allowedTypes = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-excel',
    ];

    $uploadPath = $uploadDir . uniqid('import_', true) . '_' . $fileName;

    if ($fileError !== UPLOAD_ERR_OK) {
        $errorMessage = "Terjadi kesalahan saat mengunggah file. Kode error: " . $fileError;
        switch ($fileError) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $errorMessage = "Ukuran file terlalu besar.";
                break;
            case UPLOAD_ERR_PARTIAL:
                $errorMessage = "File hanya terunggah sebagian.";
                break;
            case UPLOAD_ERR_NO_FILE:
                $errorMessage = "Tidak ada file yang dipilih untuk diunggah.";
                break;
        }
        $_SESSION['upload_error_message'] = $errorMessage;
        header('Location: manage_championship_results.php');
        exit();
    }

    if (!in_array($fileType, $allowedTypes)) {
        $_SESSION['upload_error_message'] = "Format file tidak didukung. Harap unggah file .xlsx atau .xls.";
        header('Location: manage_championship_results.php');
        exit();
    }

    if ($fileSize > 5 * 1024 * 1024) {
        $_SESSION['upload_error_message'] = "Ukuran file maksimal adalah 5MB.";
        header('Location: manage_championship_results.php');
        exit();
    }

    if (!move_uploaded_file($fileTmpName, $uploadPath)) {
        $_SESSION['upload_error_message'] = "Gagal memindahkan file yang diunggah.";
        header('Location: manage_championship_results.php');
        exit();
    }

    // Define valid integer IDs for positions and their string mappings
    $validPositionIds = [1, 2, 3, 4, 5, 6];

    // Map common string/numeric inputs for position to their integer IDs
    $positionTextToId = [
        'juara 1' => 1,
        'juara 2' => 2,
        'juara 3' => 3,
        'juara harapan 1' => 4,
        'juara harapan 2' => 5,
        'juara harapan 3' => 6, // Map Harapan 3 to ID 6, consistent with 'Lainnya' from the form
        'lainnya' => 6
    ];

    try {
        $spreadsheet = IOFactory::load($uploadPath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn(); // e.g., 'E'
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn); // e.g., 5 for 'E'

        // --- DEBUGGING: Tambahkan ini untuk melihat deteksi baris/kolom tertinggi ---
        error_log("DEBUG: Highest Row Detected: " . $highestRow);
        error_log("DEBUG: Highest Column Detected: " . $highestColumn);
        // --- END DEBUGGING ---

        $importedRowCount = 0;
        $skippedRowCount = 0;
        $errorRows = [];

        $stmt_insert = $pdo->prepare("INSERT INTO championships
            (competition_name, participant_name, position, score, school)
            VALUES (?, ?, ?, ?, ?)");

        for ($row = 2; $row <= $highestRow; $row++) {
            // Fetch the row data. Using $highestColumn ensures we read up to the maximum column present in the sheet.
            $rowDataRaw = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row,
                                                 NULL,    // return values for empty cells as NULL
                                                 TRUE,    // return cell values only (no formatting)
                                                 TRUE);   // calculate formulas

            // Get the actual row data array. Use ?? [] to handle cases where $rowDataRaw[$row] might not exist
            // (e.g., if a row is completely empty, rangeToArray might return an empty array for that row key).
            $rowData = $rowDataRaw[$row] ?? [];

            // FIX: Pad array to ensure expected number of columns exist (A-E, i.e., 5 elements, indexes 0-4).
            // This prevents "Undefined array key" warnings if a row has fewer than 5 populated columns.
            $expectedColumnCount = 5;
            if (count($rowData) < $expectedColumnCount) {
                $rowData = array_pad($rowData, $expectedColumnCount, '');
            }

            // Safely retrieve data using the now-guaranteed indexes
            $competition_name_raw = $rowData[0];
            $participant_name_raw = $rowData[1];
            $position_raw = $rowData[2];
            $score_raw = $rowData[3];
            $school_raw = $rowData[4];

            $competition_name = htmlspecialchars(trim($competition_name_raw), ENT_QUOTES, 'UTF-8');
            $participant_name = htmlspecialchars(trim($participant_name_raw), ENT_QUOTES, 'UTF-8');
            $school = htmlspecialchars(trim($school_raw), ENT_QUOTES, 'UTF-8');

            // Convert position from Excel (can be text or number) to integer ID
            $position = 6; // Default to 'Lainnya' (ID 6) if not recognized

            $trimmed_position_raw = strtolower(trim($position_raw));

            if (is_numeric($trimmed_position_raw)) {
                $numeric_pos = (int)$trimmed_position_raw;
                if (in_array($numeric_pos, $validPositionIds)) {
                    $position = $numeric_pos;
                }
            } else {
                if (isset($positionTextToId[$trimmed_position_raw])) {
                    $position = $positionTextToId[$trimmed_position_raw];
                }
            }
            
            $score = ($score_raw !== null && $score_raw !== '') ? (float)$score_raw : null;

            // --- DEBUGGING: Tuliskan nilai yang dibaca ke log server ---
            // Perbaiki peringatan 'Array to string conversion' dengan print_r
            error_log("DEBUG: Processing Row {$row}:");
            error_log("  RAW \$rowData Array: " . print_r($rowData, true));
            error_log("  A (Competition Name) Raw: " . var_export($competition_name_raw, true) . " -> Trimmed: " . var_export($competition_name, true) . " -> Empty?: " . var_export(empty($competition_name), true));
            error_log("  B (Participant Name) Raw: " . var_export($participant_name_raw, true) . " -> Trimmed: " . var_export($participant_name, true) . " -> Empty?: " . var_export(empty($participant_name), true));
            error_log("  C (Position) Raw: " . var_export($position_raw, true) . " -> Converted ID: " . var_export($position, true) . " -> Is Valid ID?: " . var_export(in_array($position, $validPositionIds), true));
            error_log("  D (Score) Raw: " . var_export($score_raw, true) . " -> Converted (float/null): " . var_export($score, true));
            error_log("  E (School) Raw: " . var_export($school_raw, true) . " -> Trimmed: " . var_export($school, true) . " -> Empty?: " . var_export(empty($school), true));
            // --- END DEBUGGING ---

            // Updated validation: now check against integer IDs for position
            if (empty($competition_name) || empty($participant_name) || empty($school) || !in_array($position, $validPositionIds)) {
                $skippedRowCount++;
                $validation_detail = "Kompetisi: '" . $competition_name . "', Peserta: '" . $participant_name . "', Posisi: '" . $position . "', Sekolah: '" . $school . "'";
                // Perbarui pesan validasi untuk mencerminkan pemeriksaan ID integer
                $validation_detail .= " (Posisi tidak valid ID: " . (!in_array($position, $validPositionIds) ? 'TRUE' : 'FALSE') . ")";
                $errorRows[] = "Baris {$row}: Data tidak lengkap atau tidak valid. Detail: " . $validation_detail;
                continue;
            }

            try {
                $stmt_insert->execute([$competition_name, $participant_name, $position, $score, $school]);
                $importedRowCount++;
            } catch (PDOException $e) {
                $skippedRowCount++;
                $errorRows[] = "Baris {$row} (Nama Kompetisi: '{$competition_name}', Peserta: '{$participant_name}'): Gagal menyimpan ke database. Error: " . htmlspecialchars($e->getMessage());
            }
        }

        unlink($uploadPath);

        if ($importedRowCount > 0) {
            $successMessage = "Berhasil mengimpor {$importedRowCount} hasil kejuaraan.";
            if ($skippedRowCount > 0) {
                $successMessage .= " Ada {$skippedRowCount} baris yang dilewati karena masalah data.";
                $_SESSION['upload_error_message'] = implode("<br>", $errorRows);
            }
            $_SESSION['upload_success_message'] = $successMessage;
        } else {
            $_SESSION['upload_error_message'] = "Tidak ada hasil kejuaraan yang berhasil diimpor. Total " . $skippedRowCount . " baris dilewati.<br>" . implode("<br>", $errorRows);
        }

    } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
        $_SESSION['upload_error_message'] = "Error saat membaca file Excel: " . htmlspecialchars($e->getMessage());
        error_log("ERROR: PhpSpreadsheet Reader Exception: " . $e->getMessage());
    } catch (Exception $e) {
        $_SESSION['upload_error_message'] = "Terjadi kesalahan tak terduga: " . htmlspecialchars($e->getMessage());
        error_log("ERROR: Unexpected Exception: " . $e->getMessage());
    }

    header('Location: manage_championship_results.php');
    exit();

} else {
    $_SESSION['upload_error_message'] = "Akses tidak valid. Silakan unggah file melalui form.";
    header('Location: manage_championship_results.php');
    exit();
}