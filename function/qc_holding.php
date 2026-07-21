<?php
require '../conn.php';
// ===============================
// SAVE HOLD
// ===============================
if (isset($_POST['save'])) {
    $part_code = mysqli_real_escape_string(
        $conn,
        $_POST['part_code']
    );
    $part_name = mysqli_real_escape_string(
        $conn,
        $_POST['part_name']
    );
    $supplier = mysqli_real_escape_string(
        $conn,
        $_POST['supplier']
    );
    $qty = (int)$_POST['qty'];
    $cmc = mysqli_real_escape_string(
        $conn,
        $_POST['cmc']
    );
    $pqa = mysqli_real_escape_string(
        $conn,
        $_POST['pqa']
    );
    $reason = mysqli_real_escape_string(
        $conn,
        $_POST['reason']
    );
    // LOGIN USER
    $username = mysqli_real_escape_string(
        $conn,
        $_POST['username']
    );
    $password = mysqli_real_escape_string(
        $conn,
        $_POST['password']
    );
    // CHECK USER
    $check_user = mysqli_query($conn, "
        SELECT *
        FROM `user`
        WHERE username='$username'
        AND password='$password'
    ");
    if (mysqli_num_rows($check_user) == 0) {
        echo "
        <script>
        alert('Username atau Password salah!');
        window.location='qc_holding.php';
        </script>";
        exit;
    }
    // AMBIL DATA USER
    $user = mysqli_fetch_assoc($check_user);
    $created_by = $user['nama'];
    // INSERT DATA HOLD
    $sql = "
    INSERT INTO qc_holding
    (
        part_code,
        part_name,
        supplier,
        qty,
        cmc,
        pqa,
        reason,
        created_by,
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
        '$created_by',
        'Hold',
        NOW()
    )
    ";
    if (mysqli_query($conn, $sql)) {
        echo "
        <script>
        alert('Data berhasil disimpan.');
        window.location='qc_holding.php';
        </script>";
    } else {
        die(mysqli_error($conn));
    }
}
// COMPLETE HOLD
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
// SEARCH
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
// SUMMARY
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
// DATA
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
// HOLD DURATION FUNCTION (DAY ONLY)
function holdDuration($start, $end = null)
{
    $startTime = new DateTime($start);
    if ($end) {
        $endTime = new DateTime($end);
    } else {
        $endTime = new DateTime();
    }
    $diff = $startTime->diff($endTime);
    if ($diff->d > 0) {
        return $diff->d . " Day";
    } else {
        return "0 Day";
    }
}
// REVISI QTY
if (isset($_POST['save_revisi'])) {
    $id = (int)$_POST['id'];
    $qty_rev = (int)$_POST['qty_rev'];
    $reason = mysqli_real_escape_string(
        $conn,
        $_POST['reason']
    );
    $username = mysqli_real_escape_string(
        $conn,
        $_POST['username']
    );
    $password = mysqli_real_escape_string(
        $conn,
        $_POST['password']
    );
    // CHECK LOGIN USER
    $check = mysqli_query($conn, "
    SELECT *
    FROM `user`
    WHERE username='$username'
    AND password='$password'
");
    if (mysqli_num_rows($check) == 0) {
        echo "
        <script>
        alert('Username atau Password salah!');
        window.location='qc_holding.php';
        </script>";
        exit;
    }
    $user = mysqli_fetch_assoc($check);
    $rev_by = $user['nama'];
    // UPDATE DATA
    mysqli_query($conn, "
        UPDATE qc_holding
        SET
            qty_rev='$qty_rev',
            rev_by='$rev_by',
            reason='$reason'
        WHERE id='$id'
    ");
    echo "
    <script>
    alert('Revisi berhasil disimpan');
    window.location='qc_holding.php';
    </script>";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>QC Holding</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
</head>

<body class="bg-light">
    <div class="container-fluid py-4">
        <!-- HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold">📋 QC Holding</h2>
                <small class="text-muted">
                    Monitoring Part Hold
                </small>
            </div>
            <div>
                <a href="../index.php" class="btn btn-secondary">
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
                                placeholder="Supplier Name"
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
                            <label>Username</label>
                            <input
                                type="text"
                                name="username"
                                class="form-control mb-3"
                                autocomplete="off"
                                placeholder="Username"
                                required>
                            <label>Password</label>
                            <input
                                type="password"
                                name="password"
                                placeholder="Password"
                                class="form-control"
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
                <table class="table table-bordered table-hover mb-0 text-center align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="50">No</th>
                            <th width="100">Date Time</th>
                            <th width="100">Created By</th>
                            <th width="100">Part Code</th>
                            <th width="150">Part Name</th>
                            <th width="100">Supplier</th>
                            <th width="50">Qty</th>
                            <th width="50">Rev</th>
                            <th width="100">Rev By</th>
                            <th width="50">CMC</th>
                            <th width="50">PQA</th>
                            <th width="400">Reason Hold</th>
                            <th width="100">Hold Time</th>
                            <th width="50">Status</th>
                            <th width="100">Action</th>
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
                                <td><?= $row['created_by']; ?></td>
                                <td><?= $row['part_code']; ?></td>
                                <td><?= $row['part_name']; ?></td>
                                <td><?= $row['supplier']; ?></td>
                                <td><?= $row['qty']; ?></td>
                                <td><?= $row['qty_rev']; ?></td>
                                <td><?= $row['rev_by']; ?></td>
                                <td><?= $row['cmc']; ?></td>
                                <td><?= $row['pqa']; ?></td>
                                <td><?= $row['reason']; ?></td>
                                <td>
                                    <?php
                                    if ($row['status'] == "Hold") {
                                        echo '<span class="badge bg-warning text-dark">';
                                        echo holdDuration($row['created_at']);
                                        echo '</span>';
                                    } else {
                                        echo '<span class="badge bg-success">';
                                        echo holdDuration(
                                            $row['created_at'],
                                            $row['completed_at']
                                        );
                                        echo '</span>';
                                    }
                                    ?>
                                </td>
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
                                        <button
                                            type="button"
                                            class="btn btn-outline-warning btn-sm"
                                            data-bs-toggle="modal"
                                            data-bs-target="#revisiModal"
                                            onclick="setRevisi(<?= $row['id']; ?>,<?= $row['qty']; ?>,`<?= htmlspecialchars($row['reason']); ?>`)">
                                            EDIT
                                        </button>
                                        <a
                                            href="?done=<?= $row['id']; ?>"
                                            class="btn btn-outline-success btn-sm"
                                            onclick="return confirm('Mark as completed?')">
                                            DONE
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
    <!-- MODAL REVISI -->
    <div class="modal fade" id="revisiModal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-warning">
                        <h5>
                            Revisi Hold
                        </h5>
                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal">
                        </button>
                    </div>
                    <div class="modal-body">
                        <input
                            type="hidden"
                            name="id"
                            id="rev_id">
                        <div class="row">
                            <div class="col-md-6">
                                <label>
                                    Qty Original
                                </label>
                                <input
                                    type="number"
                                    id="qty_old"
                                    class="form-control"
                                    readonly>
                            </div>
                            <div class="col-md-6">
                                <label>
                                    Qty Revision
                                </label>
                                <input
                                    type="number"
                                    name="qty_rev"
                                    class="form-control"
                                    required>
                            </div>
                        </div>
                        <br>
                        <label>
                            Reason Hold
                        </label>
                        <textarea
                            name="reason"
                            id="reason_text"
                            class="form-control"
                            rows="4"
                            required></textarea>
                        <hr>
                        <label>
                            Username
                        </label>
                        <input
                            type="text"
                            name="username"
                            class="form-control mb-3"
                            required>
                        <label>
                            Password
                        </label>
                        <input
                            type="password"
                            name="password"
                            class="form-control"
                            required>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal">
                            Cancel
                        </button>
                        <button
                            name="save_revisi"
                            class="btn btn-warning">
                            Save Revisi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- JavaScript -->
    <script src="../js/bootstrap.bundle.min.js"></script>
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

        function setRevisi(id, qty, reason) {
            document.getElementById('rev_id').value = id;
            document.getElementById('qty_old').value = qty;
            document.getElementById('reason_text').value = reason;
        }
    </script>
</body>

</html>