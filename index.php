<?php
require 'conn.php';

// =========================
// AMBIL SEMUA KOLOM
// =========================
$columns = [];
$getColumns = $conn->query("SHOW COLUMNS FROM matrix_part");
while ($col = $getColumns->fetch_assoc()) {
    $columns[] = $col['Field'];
}

// =========================
// KOLOM BUKAN MODEL
// =========================
$excludeColumns = [
    'id',
    'No',
    'Component',
    'Description',
    'UoM'
];

// =========================
// PARAMETER
// =========================
$data = [];
$keyword = "";
$type = "";
$modelFilter = isset($_GET['model_filter']) ? trim($_GET['model_filter']) : '';
$resultFilter = isset($_GET['result_filter']) ? trim($_GET['result_filter']) : '';

// =========================
// SEARCH
// =========================
if (isset($_GET['search'])) {
    $keyword = trim($_GET['keyword']);
    $type = $_GET['type'];
    if ($type == 'component') {
        $sql = "SELECT * FROM matrix_part
                WHERE Component LIKE '%$keyword%'";
    } elseif ($type == 'description') {
        $sql = "SELECT * FROM matrix_part
                WHERE Description LIKE '%$keyword%'";
    } elseif ($type == 'model') {
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
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Matrix Part Search</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
</head>

<body class="bg-light">
    <!-- REFRESH BUTTON -->
    <a href="<?php echo strtok($_SERVER["REQUEST_URI"], '?'); ?>"
        class="btn btn-secondary position-fixed top-0 end-0 m-4 shadow">
        ↻ Refresh
    </a>

    <div class="container-fluid py-5">
        <!-- HEADER -->
        <div class="text-center mb-4">
            <h1 class="fw-bold">Matrix Part Search</h1>
            <p class="text-muted">Cari berdasarkan Part Code, Part Name, atau Model</p>
        </div>
        <!-- SEARCH CARDS -->
        <div class="row g-4">
            <!-- PART CODE -->
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5>Search by Part Code</h5>

                        <form method="GET">
                            <input type="hidden" name="type" value="component">

                            <input type="text"
                                name="keyword"
                                class="form-control mb-3"
                                placeholder="Masukkan Part Code"
                                required>

                            <button type="submit" name="search" class="btn btn-primary w-100">
                                Search
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <!-- PART NAME -->
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5>Search by Part Name</h5>

                        <form method="GET">
                            <input type="hidden" name="type" value="description">
                            <input type="text"
                                name="keyword"
                                class="form-control mb-3"
                                placeholder="Masukkan Part Name"
                                required>

                            <button type="submit" name="search" class="btn btn-success w-100">
                                Search
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <!-- MODEL -->
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5>Search by Model</h5>
                        <form method="GET">
                            <input type="hidden" name="type" value="model">
                            <select name="keyword" class="form-select mb-3" required>
                                <option value="">-- Pilih Model --</option>
                                <?php foreach ($columns as $col): ?>
                                    <?php if (!in_array($col, $excludeColumns)): ?>
                                        <option value="<?= htmlspecialchars($col) ?>"
                                            <?= ($keyword == $col) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($col) ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="search" class="btn btn-warning w-100">
                                Search
                            </button>
                        </form>
                    </div>
                </div>
            </div>

        </div>

        <!-- INFO -->
        <?php if ($keyword != ''): ?>
            <div class="alert alert-info mt-4">
                <strong>
                    <?php
                    if ($type == 'component') echo "Part Code";
                    elseif ($type == 'description') echo "Part Name";
                    else echo "Model";
                    ?>
                </strong> :
                <?= htmlspecialchars($keyword) ?>
            </div>
        <?php endif; ?>

        <!-- FILTER MODEL -->
        <?php if (($type == 'component' || $type == 'description') && count($data) > 0): ?>
            <div class="card mt-4 shadow-sm">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <input type="hidden" name="type" value="<?= $type ?>">
                        <input type="hidden" name="keyword" value="<?= htmlspecialchars($keyword) ?>">
                        <input type="hidden" name="search" value="1">
                        <div class="col-md-8">
                            <select name="model_filter" class="form-select">
                                <option value="">-- Semua Model --</option>
                                <?php foreach ($columns as $col): ?>
                                    <?php if (!in_array($col, $excludeColumns)): ?>
                                        <option value="<?= $col ?>"
                                            <?= ($modelFilter == $col) ? 'selected' : '' ?>>
                                            <?= $col ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-dark w-100">Filter Model</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- FILTER RESULT BY MODEL -->
        <?php if ($type == 'model' && count($data) > 0): ?>
            <div class="card mt-4 shadow-sm">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <input type="hidden" name="type" value="model">
                        <input type="hidden" name="keyword" value="<?= htmlspecialchars($keyword) ?>">
                        <input type="hidden" name="search" value="1">
                        <div class="col-md-8">
                            <input type="text"
                                name="result_filter"
                                class="form-control"
                                placeholder="Cari Part Code / Part Name"
                                value="<?= htmlspecialchars($resultFilter) ?>">
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-dark w-100">Filter</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- RESULT -->
        <?php if (count($data) > 0): ?>
            <div class="card mt-4 shadow-sm">
                <div class="card-header bg-primary text-white">
                    Hasil Pencarian
                </div>
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>No</th>
                                <th>Component</th>
                                <th>Description</th>
                                <th>UoM</th>
                                <th>Qty</th>
                                <th><?= ($type == 'model') ? 'Model' : 'Available Models' ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $row): ?>
                                <?php
                                if ($modelFilter != '') {
                                    if (
                                        !isset($row[$modelFilter]) ||
                                        $row[$modelFilter] == '' ||
                                        $row[$modelFilter] == '0'
                                    ) continue;
                                }
                                if ($resultFilter != '') {
                                    $matchComponent = stripos($row['Component'], $resultFilter) !== false;
                                    $matchDescription = stripos($row['Description'], $resultFilter) !== false;
                                    if (!$matchComponent && !$matchDescription) continue;
                                }
                                ?>
                                <tr>
                                    <td><?= $row['No'] ?></td>
                                    <td><?= $row['Component'] ?></td>
                                    <td><?= $row['Description'] ?></td>
                                    <td><?= $row['UoM'] ?></td>
                                    <!-- QTY -->
                                    <td>
                                        <?php
                                        if ($type == 'model') {
                                            echo $row[$keyword];
                                        } else {
                                            $qtyList = [];
                                            foreach ($row as $col => $val) {
                                                if (
                                                    !in_array($col, $excludeColumns)
                                                    && $val != ''
                                                    && $val != '0'
                                                ) {
                                                    $qtyList[] = $val;
                                                }
                                            }
                                            echo implode(', ', array_unique($qtyList));
                                        }
                                        ?>
                                    </td>
                                    <!-- MODEL -->
                                    <td>
                                        <?php
                                        if ($type == 'model') {
                                            echo $keyword;
                                        } else {
                                            $models = [];
                                            foreach ($row as $col => $val) {
                                                if (
                                                    !in_array($col, $excludeColumns)
                                                    && $val != ''
                                                    && $val != '0'
                                                ) {
                                                    $models[] = $col;
                                                }
                                            }
                                            echo implode(', ', $models);
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php elseif (isset($_GET['search'])): ?>
            <div class="alert alert-warning mt-4 text-center">
                Data tidak ditemukan
            </div>
        <?php endif; ?>
    </div>
    <script src="js/bootstrap.bundle.min.js"></script>
</body>

</html>