<?php
require 'conn.php';

// ===============================
// SAVE HOLD
// ===============================
if (isset($_POST['save'])) {
    $PASSWORD = "SeidPart01";

    if ($_POST['password'] != $PASSWORD) {

        echo "<script>
            alert('Password salah!');
            window.location='qc_holding.php';
          </script>";
        exit;
    }

    $part_code = mysqli_real_escape_string($conn, $_POST['part_code']);
    $part_name = mysqli_real_escape_string($conn, $_POST['part_name']);
    $supplier  = mysqli_real_escape_string($conn, $_POST['supplier']);
    $qty       = (int)$_POST['qty'];
    $cmc       = mysqli_real_escape_string($conn, $_POST['cmc']);
    $pqa       = mysqli_real_escape_string($conn, $_POST['pqa']);
    $reason    = mysqli_real_escape_string($conn, $_POST['reason']);

    $sql = "INSERT INTO qc_holding
            (
                part_code,
                part_name,
                supplier,
                qty,
                cmc,
                pqa,
                reason,
                status,
                created_at
            )
            VALUES
            (
                '$part_code',
                '$part_name',
                '$supplier',
                '$qty',
                '$cmc',
                '$pqa',
                '$reason',
                'Hold',
                NOW()
            )";

    if (mysqli_query($conn, $sql)) {

        echo "<script>
        alert('Data berhasil disimpan.');
        window.location='qc_holding.php';
    </script>";
    } else {

        die("Error : " . mysqli_error($conn));
    }
}

// ===============================
// COMPLETE HOLD
// ===============================
if (isset($_GET['done'])) {

    $id = (int)$_GET['done'];

    mysqli_query($conn, "
        UPDATE qc_holding
        SET
            status='Completed',
            completed_at=NOW()
        WHERE id='$id'
    ");

    header("Location: qc_holding.php");
    exit;
}

// ===============================
// SEARCH
// ===============================
$keyword = "";
$where = "";
if (isset($_GET['search'])) {

    $keyword = mysqli_real_escape_string($conn, $_GET['keyword']);

    $where = "
    WHERE
        part_code LIKE '%$keyword%'
        OR
        part_name LIKE '%$keyword%'
        OR
        supplier LIKE '%$keyword%'
    ";
}

// ===============================
// SUMMARY
// ===============================
$hold = mysqli_fetch_assoc(mysqli_query($conn, "
SELECT COUNT(*) total
FROM qc_holding
WHERE status='Hold'
"));

$completed = mysqli_fetch_assoc(mysqli_query($conn, "
SELECT COUNT(*) total
FROM qc_holding
WHERE status='Completed'
"));

// ===============================
// DATA
// ===============================
$currentMonth = date('m');
$currentYear  = date('Y');

$sql = "
SELECT *
FROM qc_holding
WHERE
(
    (
        MONTH(created_at) = '$currentMonth'
        AND YEAR(created_at) = '$currentYear'
    )
    OR
    status = 'Hold'
)
";

if ($keyword != "") {
    $sql .= "
    AND (
        part_code LIKE '%$keyword%'
        OR part_name LIKE '%$keyword%'
        OR supplier LIKE '%$keyword%'
    )
    ";
}

$sql .= "
ORDER BY
    CASE
        WHEN status='Hold' THEN 0
        ELSE 1
    END,
    created_at DESC
";

$data = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>QC Holding</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
</head>

<body class="bg-light">
    <div class="container py-4">
        <!-- HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold">📋 QC Holding</h2>
                <small class="text-muted">
                    Monitoring Part Hold
                </small>
            </div>
            <div>
                <a href="index.php" class="btn btn-secondary">
                    ← Back
                </a>
            </div>
        </div>

        <!-- SUMMARY -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card shadow-sm border-warning">
                    <div class="card-body">
                        <h5>Holding</h5>
                        <h2 class="text-warning">
                            <?= $hold['total']; ?>
                        </h2>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card shadow-sm border-success">
                    <div class="card-body">
                        <h5>Completed</h5>
                        <h2 class="text-success">
                            <?= $completed['total']; ?>
                        </h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- FORM -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                Add Hold
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label>Part Code</label>

                            <input
                                type="text"
                                name="part_code"
                                id="part_code"
                                class="form-control"
                                list="part_list"
                                autocomplete="off"
                                required>

                            <datalist id="part_list">
                                <?php
                                $parts = mysqli_query($conn, "SELECT DISTINCT component FROM matrix_part ORDER BY component");
                                while ($p = mysqli_fetch_assoc($parts)) {
                                    echo '<option value="' . $p['component'] . '">';
                                }
                                ?>
                            </datalist>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Part Name</label>
                            <input
                                type="text"
                                name="part_name"
                                id="part_name"
                                class="form-control"
                                required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Supplier</label>
                            <input
                                type="text"
                                name="supplier"
                                class="form-control"
                                autocomplete="off"
                                required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Qty</label>
                            <input
                                type="number"
                                name="qty"
                                class="form-control"
                                autocomplete="off"
                                required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>CMC</label>
                            <input
                                type="text"
                                name="cmc"
                                class="form-control"
                                placeholder="PIC Name"
                                autocomplete="off"
                                required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>PQA</label>
                            <input
                                type="text"
                                name="pqa"
                                class="form-control"
                                placeholder="PIC Name"
                                autocomplete="off"
                                required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label>Reason Hold</label>
                            <textarea
                                name="reason"
                                class="form-control"
                                rows="3"
                                autocomplete="off"></textarea>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Password</label>
                            <input
                                type="password"
                                name="password"
                                class="form-control"
                                placeholder="QC Password"
                                autocomplete="off"
                                required>
                        </div>
                    </div>
                    <button
                        class="btn btn-primary"
                        name="save"
                        type="submit"
                        onclick="return confirm('Save hold?')">
                        Save Hold
                    </button>
                </form>
            </div>
        </div>

        <!-- SEARCH -->
        <form method="GET" class="mb-3">
            <div class="input-group">
                <input
                    type="text"
                    name="keyword"
                    value="<?= htmlspecialchars($keyword); ?>"
                    class="form-control"
                    placeholder="Search Part Code / Part Name / Supplier">
                <button
                    class="btn btn-dark"
                    name="search">
                    Search
                </button>
            </div>
        </form>

        <!-- TABLE -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                QC Holding List
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0 text-center">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Date Time</th>
                            <th>Part Code</th>
                            <th>Part Name</th>
                            <th>Supplier</th>
                            <th>Qty</th>
                            <th>CMC</th>
                            <th>PQA</th>
                            <th>Reason Hold</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        $no = 1;
                        while ($row = mysqli_fetch_assoc($data)):
                        ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><?= date("d-m-Y H:i:s", strtotime($row['created_at'])); ?></td>
                                <td><?= $row['part_code']; ?></td>
                                <td><?= $row['part_name']; ?></td>
                                <td><?= $row['supplier']; ?></td>
                                <td><?= $row['qty']; ?></td>
                                <td><?= $row['cmc']; ?></td>
                                <td><?= $row['pqa']; ?></td>
                                <td><?= $row['reason']; ?></td>
                                <td>
                                    <?php
                                    if ($row['status'] == "Hold") {
                                        echo '<span class="btn btn-warning btn-sm">Hold</span>';
                                    } else {
                                        echo '<span class="btn btn-success btn-sm">Completed</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if ($row['status'] == "Hold") { ?>
                                        <a
                                            href="?done=<?= $row['id']; ?>"
                                            class="btn btn-outline-success btn-sm"
                                            onclick="return confirm('Mark as completed?')">
                                            Done
                                        </a>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        document
            .getElementById('part_code')
            .addEventListener('input', function() {

                let partCode = this.value;

                fetch(
                        'get_part.php?part_code=' +
                        encodeURIComponent(partCode)
                    )
                    .then(response => response.json())
                    .then(data => {

                        document
                            .getElementById('part_name')
                            .value = data.description;

                    });

            });
    </script>
    <script>
        document.getElementById('part_code')
            .addEventListener('input', function() {

                let partCode = this.value;

                console.log('Part Code:', partCode);

                fetch(
                        'get_part.php?part_code=' +
                        encodeURIComponent(partCode)
                    )
                    .then(response => response.json())
                    .then(data => {

                        console.log('Response:', data);

                        document.getElementById('part_name').value =
                            data.description || '';

                    })
                    .catch(error => {
                        console.log(error);
                    });

            });
    </script>
</body>

</html>