<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'SimpleXLS.php';

$host = "localhost";
$user = "root";
$pass = "";
$db   = "seid_ac_matrix-part";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi gagal : " . $conn->connect_error);
}

// PASSWORD UPLOAD
$uploadPassword = "SafeUpload01";

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
            if ($xls = Shuchkin\SimpleXLS::parse($file)) {

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

                $message = Shuchkin\SimpleXLS::parseError();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>

    <title>Upload XLS Matrix Part</title>

    <style>

        body{
            font-family:Arial;
            background:#f5f5f5;
            padding:40px;
        }

        .box{
            width:500px;
            background:white;
            padding:25px;
            border-radius:10px;
            box-shadow:0 0 10px rgba(0,0,0,0.1);
        }

        h2{
            margin-top:0;
        }

        input[type=file],
        input[type=password]{
            width:100%;
            padding:10px;
            margin-top:10px;
            margin-bottom:20px;
            box-sizing:border-box;
        }

        button{
            padding:10px 20px;
            background:#007bff;
            color:white;
            border:none;
            border-radius:5px;
            cursor:pointer;
        }

        button:hover{
            background:#0056b3;
        }

        .msg{
            margin-top:20px;
            font-weight:bold;
            color:red;
        }

        .success{
            color:green;
        }

    </style>

</head>
<body>

<div class="box">

    <h2>Upload XLS Matrix Part</h2>

    <form method="POST" enctype="multipart/form-data">

        <label>Choose XLS File</label>

        <input
            type="file"
            name="excel_file"
            accept=".xls"
            required
        >

        <label>Password Upload</label>

        <input
            type="password"
            name="password"
            placeholder="Password"
            required
        >

        <button type="submit" name="upload">
            Upload
        </button>

    </form>

    <div class="msg <?php echo ($message == 'Upload berhasil!') ? 'success' : ''; ?>">
        <?php echo $message; ?>
    </div>

</div>

</body>
</html>