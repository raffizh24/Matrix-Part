<?php
require '../conn.php';
require '../library/SimpleXLS.php';

use Shuchkin\SimpleXLS;

$uploadPassword = "Admin123";
$message = "";
if (isset($_POST['upload'])) {
    if ($_POST['password'] != $uploadPassword) {
        $message = "Password salah!";
    } else {
        if ($_FILES['excel_file']['error'] == 0) {
            $file = $_FILES['excel_file']['tmp_name'];
            if ($xls = SimpleXLS::parse($file)) {
                // HAPUS TABLE LAMA
                $conn->query("DROP TABLE IF EXISTS matrix_part");
                // BUAT TABLE BARU
                $sql = "
                CREATE TABLE matrix_part (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    component VARCHAR(100) NOT NULL,
                    description TEXT,
                    model_code VARCHAR(100) NOT NULL,
                    qty VARCHAR(50),
                    sheet_name VARCHAR(100),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_component(component),
                    INDEX idx_model_code(model_code)
                )
                ENGINE=InnoDB
                DEFAULT CHARSET=utf8mb4
                ";
                if (!$conn->query($sql)) {
                    die($conn->error);
                }
                $totalInsert = 0;
                $log = [];
                foreach ($xls->sheetNames() as $sheetIndex => $sheetName) {
                    $rows = $xls->rows($sheetIndex);
                    if (!$rows || count($rows) == 0) {
                        continue;
                    }
                    // CARI HEADER ROW YANG BERISI COMPONENT
                    $headerRow = -1;
                    foreach ($rows as $rowIndex => $row) {
                        foreach ($row as $cell) {
                            if (
                                strtoupper(trim($cell)) === 'COMPONENT'
                            ) {
                                $headerRow = $rowIndex;
                                break 2;
                            }
                        }
                    }
                    if ($headerRow < 0) {
                        $log[] = $sheetName . " : Header tidak ditemukan";
                        continue;
                    }
                    $header = $rows[$headerRow];
                    $componentIndex = null;
                    $descriptionIndex = null;
                    foreach ($header as $idx => $colName) {
                        $colName = strtoupper(trim($colName));
                        if ($colName === 'COMPONENT') {
                            $componentIndex = $idx;
                        }
                        if ($colName === 'DESCRIPTION') {
                            $descriptionIndex = $idx;
                        }
                    }
                    if ($componentIndex === null) {
                        $log[] = $sheetName . " : Component tidak ditemukan";
                        continue;
                    }
                    $sheetInsert = 0;
                    // DATA DIMULAI SETELAH HEADER
                    for ($r = $headerRow + 1; $r < count($rows); $r++) {
                        $row = $rows[$r];
                        $component = trim($row[$componentIndex] ?? '');
                        if ($component == '') {
                            continue;
                        }
                        $description = '';
                        if ($descriptionIndex !== null) {
                            $description = trim($row[$descriptionIndex] ?? '');
                        }
                        // MODEL SELALU MULAI KOLOM KE-3
                        for ($colIndex = 3; $colIndex < count($header); $colIndex++) {
                            $modelCode = trim($header[$colIndex] ?? '');
                            if ($modelCode == '') {
                                continue;
                            }
                            $qty = trim($row[$colIndex] ?? '');
                            if ($qty == '') {
                                continue;
                            }
                            $stmt = $conn->prepare("
                                INSERT INTO matrix_part
                                (
                                    component,
                                    description,
                                    model_code,
                                    qty,
                                    sheet_name
                                )
                                VALUES
                                (?, ?, ?, ?, ?)
                            ");
                            if (!$stmt) {
                                die($conn->error);
                            }
                            $stmt->bind_param(
                                "sssss",
                                $component,
                                $description,
                                $modelCode,
                                $qty,
                                $sheetName
                            );
                            if ($stmt->execute()) {
                                $sheetInsert++;
                                $totalInsert++;
                            }
                            $stmt->close();
                        }
                    }
                    $log[] = $sheetName . " : " . number_format($sheetInsert) . " record";
                }
                $message =
                    "UPLOAD BERHASIL<br><br>" .
                    implode("<br>", $log) .
                    "<br><br><b>Total Record : " .
                    number_format($totalInsert) .
                    "</b>";
            } else {
                $message = SimpleXLS::parseError();
            }
        } else {
            $message = "File gagal diupload.";
        }
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Upload Master Data</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
</head>

<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <h2 class="text-center mb-4">
                    Upload Matrix Part 2
                </h2>
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <input
                            type="file"
                            name="excel_file"
                            class="form-control"
                            accept=".xls"
                            required>
                    </div>
                    <div class="mb-3">
                        <input
                            type="password"
                            name="password"
                            class="form-control"
                            placeholder="Password"
                            required>
                    </div>
                    <button
                        type="submit"
                        name="upload"
                        class="btn btn-primary w-100">
                        Upload
                    </button>
                </form>
                <?php if (!empty($message)) : ?>
                    <div class="alert alert-info mt-3">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="js/bootstrap.bundle.min.js"></script>
</body>

</html>