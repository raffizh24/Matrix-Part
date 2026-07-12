<?php
require 'conn.php';
$data = [];
$keyword = '';
$type = '';
if (isset($_GET['search'])) {
    $keyword = trim($_GET['keyword']);
    $type = $_GET['type'];
    if ($type == 'component') {
        $stmt = $conn->prepare("
            SELECT *
            FROM matrix_part2
            WHERE component LIKE ?
            ORDER BY component, model_code
        ");
        $like = "%$keyword%";
        $stmt->bind_param("s", $like);
    } elseif ($type == 'description') {
        $stmt = $conn->prepare("
            SELECT *
            FROM matrix_part2
            WHERE description LIKE ?
            ORDER BY component, model_code
        ");
        $like = "%$keyword%";
        $stmt->bind_param("s", $like);
    } elseif ($type == 'model') {
        $stmt = $conn->prepare("
            SELECT *
            FROM matrix_part2
            WHERE model_code LIKE ?
            ORDER BY component, model_code
        ");
        $like = "%$keyword%";
        $stmt->bind_param("s", $like);
    }
    if (isset($stmt)) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $stmt->close();
    }
}
/*
|--------------------------------------------------------------------------
| PIVOT MATRIX
|--------------------------------------------------------------------------
*/
$pivotData = [];
$allModels = [];
if (count($data) > 0) {
    foreach ($data as $row) {
        $component = $row['component'];
        if (!isset($pivotData[$component])) {
            $pivotData[$component] = [
                'component'   => $row['component'],
                'description' => $row['description'],
                'models'      => []
            ];
        }
        $pivotData[$component]['models'][$row['model_code']] = $row['qty'];
        $allModels[$row['model_code']] = true;
    }
    ksort($allModels);
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Matrix Part Search</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <style>
        body {
            background: #f8f9fa;
        }

        .matrix-table {
            font-size: 12px;
            white-space: nowrap;
        }

        .matrix-table th {
            text-align: center;
            vertical-align: middle;
        }

        .matrix-table td {
            text-align: center;
            vertical-align: middle;
        }

        .sticky-col {
            position: sticky;
            left: 0;
            background: white;
            z-index: 2;
        }

        .sticky-col-2 {
            position: sticky;
            left: 180px;
            background: white;
            z-index: 2;
        }

        .thead-sticky {
            position: sticky;
            top: 0;
            z-index: 3;
        }
    </style>
</head>

<body>
    <div class="position-fixed top-0 end-0 m-4 d-flex gap-2">
        <a href="function/qc_holding.php"
            class="btn btn-success shadow">
            📋 QC Holding
        </a>
        <a href="function/upload.php"
            class="btn btn-primary shadow">
            ⬆ Upload Matrix
        </a>
        <a href="<?php echo strtok($_SERVER['REQUEST_URI'], '?'); ?>"
            class="btn btn-secondary shadow">
            ↻ Refresh
        </a>
    </div>
    <div class="container-fluid py-4">
        <div class="text-center mb-4">
            <h1 class="fw-bold">
                Matrix Part Search
            </h1>
            <p class="text-muted">
                Search by Part Code, Part Name, atau Model
            </p>
        </div>
        <div class="row g-3">
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5>Search by Part Code</h5>
                        <form method="GET">
                            <input type="hidden"
                                name="type"
                                value="component">
                            <input type="text"
                                name="keyword"
                                class="form-control mb-3"
                                placeholder="Masukkan Part Code"
                                required>
                            <button type="submit"
                                name="search"
                                class="btn btn-primary w-100">
                                Search
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5>Search by Part Name</h5>
                        <form method="GET">
                            <input type="hidden"
                                name="type"
                                value="description">
                            <input type="text"
                                name="keyword"
                                class="form-control mb-3"
                                placeholder="Masukkan Part Name"
                                required>
                            <button type="submit"
                                name="search"
                                class="btn btn-success w-100">
                                Search
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5>Search by Model</h5>
                        <form method="GET">
                            <input type="hidden"
                                name="type"
                                value="model">
                            <input type="text"
                                name="keyword"
                                class="form-control mb-3"
                                placeholder="Masukkan Model"
                                required>
                            <button type="submit"
                                name="search"
                                class="btn btn-warning w-100">
                                Search
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php if ($keyword != ''): ?>
            <div class="alert alert-info mt-4">
                Keyword :
                <strong><?= htmlspecialchars($keyword) ?></strong>
            </div>
        <?php endif; ?>
        <?php if (count($pivotData) > 0): ?>
            <div class="card mt-4 shadow">
                <div class="card-header bg-primary text-white">
                    Total Part :
                    <?= number_format(count($pivotData)) ?>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered matrix-table mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th class="thead-sticky sticky-col"
                                    style="min-width:180px;">
                                    Part Code
                                </th>
                                <th class="thead-sticky sticky-col-2"
                                    style="min-width:300px;">
                                    Description
                                </th>
                                <?php foreach (array_keys($allModels) as $model): ?>
                                    <th class="thead-sticky">
                                        <?= htmlspecialchars($model) ?>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pivotData as $item): ?>
                                <tr>
                                    <td class="sticky-col text-start">
                                        <?= htmlspecialchars($item['component']) ?>
                                    </td>
                                    <td class="sticky-col-2 text-start">
                                        <?= htmlspecialchars($item['description']) ?>
                                    </td>
                                    <?php foreach (array_keys($allModels) as $model): ?>
                                        <td>
                                            <?= isset($item['models'][$model])
                                                ? htmlspecialchars($item['models'][$model])
                                                : '' ?>
                                        </td>
                                    <?php endforeach; ?>
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