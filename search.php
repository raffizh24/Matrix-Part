<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$user = "root";
$pass = "";
$db   = "seid_ac_matrix-part";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi gagal : " . $conn->connect_error);
}

// =========================
// AMBIL SEMUA KOLOM
// =========================
$columns = [];
$getColumns = $conn->query("SHOW COLUMNS FROM matrix_part");
while($col = $getColumns->fetch_assoc()){
    if($col['Field'] != 'id'){
        $columns[] = $col['Field'];
    }
}

// =========================
// SEARCH
// =========================
$data = [];
$keyword = "";
$type = "";
if(isset($_GET['search'])){

    $keyword = trim($_GET['keyword']);
    $type = $_GET['type'];

    if($type == 'component'){

        $sql = "SELECT * FROM matrix_part
        WHERE Component LIKE '%$keyword%'";
    }
    else if($type == 'description'){

        $sql = "SELECT * FROM matrix_part
        WHERE Description LIKE '%$keyword%'";
    }
    else if($type == 'model'){

        // cari model column
        if(in_array($keyword, $columns)){

            $sql = "SELECT * FROM matrix_part
            WHERE `$keyword` IS NOT NULL
            AND `$keyword` != ''
            AND `$keyword` != '0'";

        }else{

            $sql = "SELECT * FROM matrix_part WHERE 1=0";
        }
    }
    else{

        $sql = "SELECT * FROM matrix_part WHERE 1=0";
    }

    $query = $conn->query($sql);

    while($row = $query->fetch_assoc()){
        $data[] = $row;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Matrix Part Search</title>
    <style>

        body{
            font-family:Arial;
            background:#f5f5f5;
            padding:20px;
        }

        .box{
            background:white;
            padding:20px;
            border-radius:10px;
            box-shadow:0 0 10px rgba(0,0,0,0.1);
        }

        h2{
            margin-top:0;
        }

        input, select{
            padding:10px;
            margin-right:10px;
        }

        button{
            padding:10px 20px;
            background:#007bff;
            color:white;
            border:none;
            border-radius:5px;
            cursor:pointer;
        }

        table{
            width:100%;
            border-collapse:collapse;
            margin-top:20px;
            background:white;
        }

        table th,
        table td{
            border:1px solid #ccc;
            padding:10px;
            text-align:left;
            font-size:14px;
        }

        table th{
            background:#007bff;
            color:white;
        }

        .info{
            margin-top:15px;
            font-weight:bold;
            color:#007bff;
        }

    </style>
</head>
<body>
<!-- Search Form -->
<div class="box">
    <h2>Matrix Part Search</h2>
    <form method="GET">

        <select name="type" required>

            <option value="">
                -- Pilih Search --
            </option>

            <option value="component"
            <?php if($type=='component') echo 'selected'; ?> >
                Component
            </option>

            <option value="description"
            <?php if($type=='description') echo 'selected'; ?> >
                Description
            </option>

            <option value="model"
            <?php if($type=='model') echo 'selected'; ?> >
                Model
            </option>

        </select>

        <input
            type="text"
            name="keyword"
            placeholder="Masukkan keyword / model"
            value="<?php echo htmlspecialchars($keyword); ?>"
            required
        >

        <button type="submit" name="search">
            Search
        </button>

    </form>
    <?php if($type == 'model' && $keyword != ''){ ?>
        <div class="info">
            Model : <b><?php echo $keyword; ?></b>
        </div>

    <?php } ?>

</div>

<!-- Search Results -->
<?php if(count($data) > 0){ ?>
<table>
    <tr>
        <th>No</th>
        <th>Component</th>
        <th>Description</th>
        <th>UoM</th>
        <?php if($type == 'model'){ ?>
            <th><?php echo $keyword; ?></th>
        <?php } ?>
    </tr>
    <?php foreach($data as $row){ ?>
    <tr>
        <td><?php echo $row['No']; ?></td>
        <td><?php echo $row['Component']; ?></td>
        <td><?php echo $row['Description']; ?></td>
        <td><?php echo $row['UoM']; ?></td>
        <?php if($type == 'model'){ ?>
            <td><?php echo $row[$keyword]; ?></td>
        <?php } ?>
    </tr>
    <?php } ?>
</table>
<?php } ?>
</body>
</html>