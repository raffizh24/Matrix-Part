<?php

require 'conn.php';
require 'library/SimpleXLS.php';

use Shuchkin\SimpleXLS;

$uploadPassword = "Admin123";
$message = "";

if (isset($_POST['upload'])) {

    $inputPassword = $_POST['password'];

    if ($inputPassword != $uploadPassword) {

        $message = "Password salah!";

    } else {

        if ($_FILES['excel_file']['error'] == 0) {

            $file = $_FILES['excel_file']['tmp_name'];

            if ($xls = SimpleXLS::parse($file)) {

                $tableName = "matrix_part";

                // ==========================
                // HAPUS TABEL LAMA
                // ==========================
                $conn->query("DROP TABLE IF EXISTS `$tableName`");

                // ==========================
                // BUAT TABEL BARU
                // ==========================
                $createSQL = "
                CREATE TABLE `$tableName` (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    component VARCHAR(100) NOT NULL,
                    description VARCHAR(255),
                    model VARCHAR(100) NOT NULL,
                    value VARCHAR(50),

                    INDEX idx_component(component),
                    INDEX idx_model(model)
                )
                ENGINE=InnoDB
                DEFAULT CHARSET=utf8mb4
                ";

                if (!$conn->query($createSQL)) {
                    die($conn->error);
                }

                $sheetCount = $xls->sheetsCount();

                $inserted = 0;

                // ==========================
                // LOOP SEMUA SHEET
                // ==========================
                for ($s = 0; $s < $sheetCount; $s++) {

                    $rows = $xls->rows($s);

                    if (count($rows) < 2) {
                        continue;
                    }

                    $headers = $rows[0];

                    $componentIndex = -1;
                    $descriptionIndex = -1;

                    // ==========================
                    // CARI KOLOM COMPONENT & DESCRIPTION
                    // ==========================
                    foreach ($headers as $idx => $header) {

                        $header = trim($header);

                        if (strcasecmp($header, 'Component') == 0) {
                            $componentIndex = $idx;
                        }

                        if (strcasecmp($header, 'Description') == 0) {
                            $descriptionIndex = $idx;
                        }
                    }

                    if ($componentIndex < 0) {
                        continue;
                    }

                    // ==========================
                    // LOOP SEMUA BARIS
                    // ==========================
                    for ($r = 1; $r < count($rows); $r++) {

                        $row = $rows[$r];

                        $component =
                            isset($row[$componentIndex])
                            ? trim($row[$componentIndex])
                            : '';

                        if ($component == '') {
                            continue;
                        }

                        $description =
                            ($descriptionIndex >= 0 &&
                                isset($row[$descriptionIndex]))
                            ? trim($row[$descriptionIndex])
                            : '';

                        // ==========================
                        // LOOP KOLOM MODEL
                        // ==========================
                        foreach ($headers as $colIndex => $modelName) {

                            $modelName = trim($modelName);

                            // skip kolom non-model
                            if (
                                $modelName == '' ||
                                strcasecmp($modelName, 'No') == 0 ||
                                strcasecmp($modelName, 'Component') == 0 ||
                                strcasecmp($modelName, 'Description') == 0
                            ) {
                                continue;
                            }

                            $value =
                                isset($row[$colIndex])
                                ? trim($row[$colIndex])
                                : '';

                            // kosong = tidak dipakai
                            if ($value == '') {
                                continue;
                            }

                            $componentEsc =
                                $conn->real_escape_string($component);

                            $descriptionEsc =
                                $conn->real_escape_string($description);

                            $modelEsc =
                                $conn->real_escape_string($modelName);

                            $valueEsc =
                                $conn->real_escape_string($value);

                            $insertSQL = "
                            INSERT INTO `$tableName`
                            (
                                component,
                                description,
                                model,
                                value
                            )
                            VALUES
                            (
                                '$componentEsc',
                                '$descriptionEsc',
                                '$modelEsc',
                                '$valueEsc'
                            )
                            ";

                            if (!$conn->query($insertSQL)) {

                                echo "<pre>";
                                echo $insertSQL;
                                echo "</pre>";

                                die($conn->error);
                            }

                            $inserted++;
                        }
                    }
                }

                $message = "Upload berhasil! Total data: " . number_format($inserted);

            } else {

                $message = SimpleXLS::parseError();
            }
        }
    }
}

?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Upload Matrix Part</title>

    <link rel="stylesheet"
          href="css/bootstrap.min.css">
</head>

<body>

<div class="container text-center">

    <div class="position-fixed top-0 end-0 m-4">

        <a href="index.php"
           class="btn btn-secondary">
            Back to Home
        </a>

    </div>

    <h1 class="mt-5 mb-4">
        Upload Matrix Part XLS
    </h1>

    <form method="POST"
          enctype="multipart/form-data"
          class="mx-auto"
          style="max-width:500px;">

        <div class="mb-3">

            <input type="file"
                   name="excel_file"
                   accept=".xls"
                   class="form-control"
                   required>

        </div>

        <div class="mb-3">

            <input type="password"
                   name="password"
                   class="form-control"
                   placeholder="Password"
                   required>

        </div>

        <button type="submit"
                name="upload"
                class="btn btn-primary w-100">

            Upload

        </button>

    </form>

    <?php if (!empty($message)) : ?>

        <div class="alert mt-4 <?php echo strpos($message,'berhasil') !== false ? 'alert-success' : 'alert-danger'; ?>"
             style="max-width:600px;margin:auto;">

            <?php echo $message; ?>

        </div>

    <?php endif; ?>

</div>

<script src="js/bootstrap.bundle.min.js"></script>

</body>
</html>