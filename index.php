<?php
require 'conn.php';

// =========================
// AMBIL SEMUA KOLOM
// =========================
$columns = [];
$getColumns = $conn->query("SHOW COLUMNS FROM matrix_part");
while ($col = $getColumns->fetch_assoc()) {
    if ($col['Field'] != 'id') {
        $columns[] = $col['Field'];
    }
}

// =========================
// SEARCH
// =========================
$data = [];
$keyword = "";
$type = "";
if (isset($_GET['search'])) {

    $keyword = trim($_GET['keyword']);
    $type = $_GET['type'];

    if ($type == 'component') {

        $sql = "SELECT * FROM matrix_part
        WHERE Component LIKE '%$keyword%'";
    } else if ($type == 'description') {

        $sql = "SELECT * FROM matrix_part
        WHERE Description LIKE '%$keyword%'";
    } else if ($type == 'model') {

        // cari model column
        if (in_array($keyword, $columns)) {

            $sql = "SELECT * FROM matrix_part
            WHERE `$keyword` IS NOT NULL
            AND `$keyword` != ''
            AND `$keyword` != '0'";
        } else {

            $sql = "SELECT * FROM matrix_part WHERE 1=0";
        }
    } else {

        $sql = "SELECT * FROM matrix_part WHERE 1=0";
    }

    $query = $conn->query($sql);

    while ($row = $query->fetch_assoc()) {
        $data[] = $row;
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Matrix Part</title>
    <link rel="stylesheet" href="css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container-fluid py-5">
        <!-- Header -->
        <div class="text-center mb-4">
            <h1 class="fw-bold">Matrix Part Search</h1>
            <p class="text-muted">Cari data berdasarkan Part Code, Part Name, atau Model AC</p>
        </div>

        <!-- Search Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">

                <form method="GET" class="row g-3 align-items-end">

                    <div class="col-md-3">
                        <label class="form-label">Search Type</label>
                        <select name="type" class="form-select" required>
                            <option value="">-- Pilih Search --</option>

                            <option value="component" <?php if ($type == 'component') echo 'selected'; ?>>
                                Part Code
                            </option>

                            <option value="description" <?php if ($type == 'description') echo 'selected'; ?>>
                                Part Name
                            </option>

                            <option value="model" <?php if ($type == 'model') echo 'selected'; ?>>
                                Model
                            </option>
                        </select>
                    </div>

                    <div class="col-md-7">
                        <label class="form-label">Keyword / Model</label>
                        <input
                            type="text"
                            name="keyword"
                            class="form-control"
                            placeholder="Masukkan keyword / model"
                            value="<?php echo htmlspecialchars($keyword); ?>"
                            required>
                    </div>

                    <div class="col-md-2 d-grid">
                        <button type="submit" name="search" class="btn btn-primary">
                            Search
                        </button>
                    </div>

                </form>

            </div>
        </div>

        <!-- Info -->
        <?php if ($type == 'model' && $keyword != '') { ?>
            <div class="alert alert-info">
                Model: <strong><?php echo htmlspecialchars($keyword); ?></strong>
            </div>
        <?php } ?>

        <!-- Result -->
        <?php if (count($data) > 0) { ?>

            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    Hasil Pencarian
                </div>

                <div class="card-body p-0">

                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">

                            <thead class="table-dark">
                                <tr>
                                    <th>No</th>
                                    <th>Component</th>
                                    <th>Description</th>
                                    <th>UoM</th>

                                    <?php if ($type == 'model') { ?>
                                        <th><?php echo htmlspecialchars($keyword); ?></th>
                                    <?php } ?>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($data as $row) { ?>
                                    <tr>
                                        <td><?php echo $row['No']; ?></td>
                                        <td><?php echo $row['Component']; ?></td>
                                        <td><?php echo $row['Description']; ?></td>
                                        <td><?php echo $row['UoM']; ?></td>

                                        <?php if ($type == 'model') { ?>
                                            <td><?php echo $row[$keyword]; ?></td>
                                        <?php } ?>
                                    </tr>
                                <?php } ?>
                            </tbody>

                        </table>
                    </div>

                </div>
            </div>

        <?php } elseif (isset($_GET['search'])) { ?>

            <div class="alert alert-warning text-center">
                Data tidak ditemukan
            </div>

        <?php } ?>

    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>

</html>