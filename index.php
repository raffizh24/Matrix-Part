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
// KOLOM NON MODEL
// =========================
$excludeColumns = [
    'id',
    'No',
    'Component',
    'Description',
    'UoM'
];

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

    <div class="container-fluid py-5">

        <!-- Header -->
        <div class="text-center mb-5">
            <h1 class="fw-bold">Matrix Part Search</h1>
            <p class="text-muted">Cari data berdasarkan Part Code, Part Name, atau Model AC</p>
        </div>

        <!-- Search Cards -->
        <div class="row g-4">

            <!-- Search by Part Code -->
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">Search by Part Code</h5>

                        <form method="GET">
                            <input type="hidden" name="type" value="component">

                            <div class="mb-3">
                                <label class="form-label">Part Code</label>
                                <input type="text"
                                    name="keyword"
                                    class="form-control"
                                    placeholder="Masukkan Part Code"
                                    value="<?php echo ($type == 'component') ? htmlspecialchars($keyword) : ''; ?>"
                                    required>
                            </div>

                            <button type="submit" name="search" class="btn btn-primary w-100">
                                Search
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Search by Part Name -->
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">Search by Part Name</h5>

                        <form method="GET">
                            <input type="hidden" name="type" value="description">

                            <div class="mb-3">
                                <label class="form-label">Part Name</label>
                                <input type="text"
                                    name="keyword"
                                    class="form-control"
                                    placeholder="Masukkan Part Name"
                                    value="<?php echo ($type == 'description') ? htmlspecialchars($keyword) : ''; ?>"
                                    required>
                            </div>

                            <button type="submit" name="search" class="btn btn-success w-100">
                                Search
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Search by Model -->
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">Search by Model</h5>

                        <form method="GET">
                            <input type="hidden" name="type" value="model">

                            <div class="mb-3">
                                <label class="form-label">Select Model</label>

                                <select name="keyword" class="form-select" required>
                                    <option value="">-- Pilih Model --</option>

                                    <?php foreach ($columns as $col): ?>
                                        <?php if (!in_array($col, $excludeColumns)): ?>
                                            <option value="<?php echo htmlspecialchars($col); ?>"
                                                <?php echo ($type == 'model' && $keyword == $col) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($col); ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <button type="submit" name="search" class="btn btn-warning w-100">
                                Search
                            </button>
                        </form>
                    </div>
                </div>
            </div>

        </div>

        <!-- Info -->
        <?php if ($type == 'model' && $keyword != ''): ?>
            <div class="alert alert-info mt-4">
                Model: <strong><?php echo htmlspecialchars($keyword); ?></strong>
            </div>
        <?php endif; ?>

        <!-- Result -->
        <?php if (count($data) > 0): ?>

            <div class="card shadow-sm mt-4">
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

                                    <?php if ($type == 'model'): ?>
                                        <th>Model</th>
                                    <?php else: ?>
                                        <th>Available Models</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($data as $row): ?>
                                    <tr>
                                        <td><?php echo $row['No']; ?></td>
                                        <td><?php echo $row['Component']; ?></td>
                                        <td><?php echo $row['Description']; ?></td>
                                        <td><?php echo $row['UoM']; ?></td>

                                        <td>
                                            <?php
                                            if ($type == 'model') {
                                                echo htmlspecialchars($keyword);
                                            } else {
                                                $models = [];

                                                foreach ($row as $col => $val) {
                                                    if (
                                                        !in_array($col, $excludeColumns)
                                                        && $val == 1
                                                    ) {
                                                        $models[] = $col;
                                                    }
                                                }

                                                echo !empty($models)
                                                    ? implode(', ', $models)
                                                    : '-';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                            </tbody>

                        </table>
                    </div>

                </div>
            </div>

        <?php elseif (isset($_GET['search'])): ?>

            <div class="alert alert-warning text-center mt-4">
                Data tidak ditemukan
            </div>

        <?php endif; ?>

    </div>

    <script src="js/bootstrap.bundle.min.js"></script>

</body>

</html>