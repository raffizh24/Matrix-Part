<?php
require 'conn.php';
require 'library/SimpleXLS.php';
use Shuchkin\SimpleXLS;

// PASSWORD UPLOAD
$uploadPassword = "Admin123";
$message = "";

// =========================
// FUNCTION CLEAN COLUMN
// =========================
function cleanColumnName($name)
{
    $name = trim($name);

    $name = preg_replace('/[^A-Za-z0-9_]/', '_', $name);

    $name = preg_replace('/_+/', '_', $name);

    $name = substr($name, 0, 60);

    if ($name == '') {
        $name = 'column_' . rand(1000,9999);
    }

    return $name;
}

// =========================
// UPLOAD PROCESS
// =========================
if(isset($_POST['upload'])){

    // CHECK PASSWORD
    $inputPassword = $_POST['password'];

    if($inputPassword != $uploadPassword){

        $message = "Password salah!";

    }else{

        if($_FILES['excel_file']['error'] == 0){

            $file = $_FILES['excel_file']['tmp_name'];

            // PARSE XLS
            if ($xls = SimpleXLS::parse($file)) {

                $rows = $xls->rows();

                if(count($rows) > 0){

                    // HEADER
                    $headers = $rows[0];

                    $tableName = "matrix_part";

                    // DROP TABLE
                    $dropSQL = "DROP TABLE IF EXISTS `$tableName`";

                    if(!$conn->query($dropSQL)){
                        die($conn->error);
                    }

                    // CREATE TABLE
                    $createSQL = "CREATE TABLE `$tableName` (
                        id INT AUTO_INCREMENT PRIMARY KEY,";

                    $usedColumns = [];

                    foreach($headers as $header){

                        $col = cleanColumnName($header);

                        // avoid duplicate
                        if(in_array($col, $usedColumns)){
                            $col .= "_" . rand(100,999);
                        }

                        $usedColumns[] = $col;

                        $createSQL .= "`$col` TEXT,";
                    }

                    $createSQL = rtrim($createSQL, ",");

                    $createSQL .= ")
                    ENGINE=InnoDB
                    DEFAULT CHARSET=utf8mb4";

                    if(!$conn->query($createSQL)){
                        die($conn->error);
                    }

                    // INSERT DATA
                    for($i=1; $i<count($rows); $i++){

                        $row = $rows[$i];

                        $cols = [];
                        $vals = [];

                        foreach($headers as $index => $header){

                            $col = cleanColumnName($header);

                            $cols[] = "`$col`";

                            $val = isset($row[$index]) ? $row[$index] : '';

                            $val = $conn->real_escape_string($val);

                            $vals[] = "'$val'";
                        }

                        $insertSQL = "INSERT INTO `$tableName`
                        (" . implode(",", $cols) . ")
                        VALUES
                        (" . implode(",", $vals) . ")";

                        if(!$conn->query($insertSQL)){

                            echo "<pre>";
                            echo $insertSQL;
                            echo "</pre>";

                            die($conn->error);
                        }
                    }

                    $message = "Upload berhasil!";
                }

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
    <title>Matrix Part</title>
    <link rel="stylesheet" href="css/bootstrap.min.css" rel="stylesheet">
</head>
<body> 
<div class="container text-center">
    <h1 class="h1 m-5">Upload XLS Matrix Part</h1>
    <form method="POST" enctype="multipart/form-data" style="max-width: 500px;" class="mx-auto">
        <div class="mb-3">
            <input class="form-control" type="file" name="excel_file" accept=".xls" required>
        </div>
        <input type="password" class="form-control" name="password" placeholder="Password" required>
        <button type="submit" name="upload" class="btn btn-primary my-3 w-100">Uploads</button>
    </form>
    <?php if (!empty($message)) : ?>
        <div class="alert <?php echo ($message == 'Upload berhasil!') ? 'alert-success' : 'alert-danger'; ?> mx-auto" role="alert" style="max-width: 500px;">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
</div>
<!-- Javascript -->
<script src="../../js/bootstrap.bundle.min.js"></script>
</body>
</html>