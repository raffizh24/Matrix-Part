<?php
require '../conn.php';

// ===============================
// SIMPAN HOLD
// ===============================
if (isset($_POST['save'])) {
    $part_code = mysqli_real_escape_string($conn, $_POST['part_code']);
    $part_name = mysqli_real_escape_string($conn, $_POST['part_name']);
    $supplier  = mysqli_real_escape_string($conn, $_POST['supplier']);
    $qty       = (int)$_POST['qty'];
    $cmc       = mysqli_real_escape_string($conn, $_POST['cmc']);
    $pqa       = mysqli_real_escape_string($conn, $_POST['pqa']);
    $reason    = mysqli_real_escape_string($conn, $_POST['reason']);

    // LOGIN USER
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // CEK USER
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

// ===============================
// SELESAIKAN HOLD (DENGAN VERIFIKASI USER)
// ===============================
if (isset($_POST['save_done'])) {
    $id       = (int)$_POST['id'];
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // CEK USER
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

    // UPDATE STATUS MENJADI COMPLETED
    mysqli_query($conn, "
        UPDATE qc_holding
        SET
            status='Completed',
            completed_at=NOW()
        WHERE id='$id'
    ");

    echo "
    <script>
    alert('Status berhasil diubah menjadi Selesai.');
    window.location='qc_holding.php';
    </script>";
    exit;
}

// PENCARIAN
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

// RINGKASAN DATA
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

// DATA TABEL
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

// FUNGSI DURASI HOLD (DALAM HARI)
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
        return $diff->d . " Hari";
    } else {
        return "0 Hari";
    }
}

// REVISI QTY
if (isset($_POST['save_revisi'])) {
    $id       = (int)$_POST['id'];
    $qty_rev  = (int)$_POST['qty_rev'];
    $reason   = mysqli_real_escape_string($conn, $_POST['reason']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // CEK USER
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

    $user   = mysqli_fetch_assoc($check);
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
    alert('Revisi berhasil disimpan.');
    window.location='qc_holding.php';
    </script>";
}
?>
<!DOCTYPE html>
<html lang="id">

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
                <small class="text-muted">Pemantauan Part Hold</small>
            </div>
            <div>
                <a href="../index.php" class="btn btn-secondary">← Kembali</a>
            </div>
        </div>

        <!-- RINGKASAN -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card shadow-sm border-warning">
                    <div class="card-body">
                        <h5>Dalam Penahanan (Hold)</h5>
                        <h2 class="text-warning"><?= $hold['total']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm border-success">
                    <div class="card-body">
                        <h5>Selesai (Completed)</h5>
                        <h2 class="text-success"><?= $completed['total']; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- FORM TAMBAH HOLD -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">Tambah Data Hold</div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label>Kode Part</label>
                            <input type="text" name="part_code" id="part_code" class="form-control" list="part_list" autocomplete="off" required placeholder="Masukkan Kode Part">
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
                            <label>Nama Part</label>
                            <input type="text" name="part_name" id="part_name" class="form-control" required placeholder="Nama Part">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Pemasok (Supplier)</label>
                            <input type="text" name="supplier" class="form-control" autocomplete="off" placeholder="Nama Pemasok" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Jumlah (Qty)</label>
                            <input type="number" name="qty" class="form-control" autocomplete="off" placeholder="Jumlah Part" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>CMC</label>
                            <input type="text" name="cmc" class="form-control" placeholder="Nama PIC CMC" autocomplete="off" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>PQA</label>
                            <input type="text" name="pqa" class="form-control" placeholder="Nama PIC PQA" autocomplete="off" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label>Alasan Hold</label>
                            <textarea name="reason" class="form-control" rows="3" autocomplete="off" placeholder="Tuliskan alasan penahanan..."></textarea>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Nama Pengguna (Username)</label>
                            <input type="text" name="username" class="form-control mb-3" autocomplete="off" placeholder="Username" required>
                            <label>Kata Sandi (Password)</label>
                            <input type="password" name="password" placeholder="Password" class="form-control" required>
                        </div>
                    </div>
                    <button class="btn btn-primary" name="save" type="submit" onclick="return confirm('Simpan data hold ini?')">Simpan Data Hold</button>
                </form>
            </div>
        </div>

        <!-- PENCARIAN -->
        <form method="GET" class="mb-3">
            <div class="input-group">
                <input type="text" name="keyword" value="<?= htmlspecialchars($keyword); ?>" class="form-control" placeholder="Cari Kode Part / Nama Part / Pemasok">
                <button class="btn btn-dark" name="search">Cari</button>
            </div>
        </form>

        <!-- TABEL DATA -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">Daftar QC Holding</div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0 text-center align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="50">No</th>
                            <th width="100">Tanggal</th>
                            <th width="100">Dibuat Oleh</th>
                            <th width="100">Kode Part</th>
                            <th width="150">Nama Part</th>
                            <th width="100">Pemasok</th>
                            <th width="50">Qty</th>
                            <th width="50">Rev</th>
                            <th width="100">Direvisi</th>
                            <th width="50">CMC</th>
                            <th width="50">PQA</th>
                            <th width="400">Alasan Hold</th>
                            <th width="100">Lama Hold</th>
                            <th width="50">Status</th>
                            <th style="width: 140px;">Aksi</th>
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
                                        echo '<span class="badge bg-warning text-dark">' . holdDuration($row['created_at']) . '</span>';
                                    } else {
                                        echo '<span class="badge bg-success">' . holdDuration($row['created_at'], $row['completed_at']) . '</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    if ($row['status'] == "Hold") {
                                        echo '<span class="btn btn-warning btn-sm">Hold</span>';
                                    } else {
                                        echo '<span class="btn btn-success btn-sm">Selesai</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if ($row['status'] == "Hold") { ?>
                                        <button type="button" class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#revisiModal" onclick="setRevisi(<?= $row['id']; ?>,<?= $row['qty']; ?>,`<?= htmlspecialchars($row['reason']); ?>`)">
                                            UBAH
                                        </button>
                                        <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#doneModal" onclick="setDone(<?= $row['id']; ?>)">
                                            SELESAI
                                        </button>
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
                        <h5 class="modal-title">Revisi Hold</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="rev_id">
                        <div class="row">
                            <div class="col-md-6">
                                <label>Qty Awal</label>
                                <input type="number" id="qty_old" class="form-control" readonly>
                            </div>
                            <div class="col-md-6">
                                <label>Qty Revisi</label>
                                <input type="number" name="qty_rev" class="form-control" required placeholder="Masukkan jumlah baru">
                            </div>
                        </div>
                        <br>
                        <label>Alasan Hold</label>
                        <textarea name="reason" id="reason_text" class="form-control" rows="4" required placeholder="Tulis alasan revisi hold"></textarea>
                        <hr>
                        <label>Username</label>
                        <input type="text" name="username" class="form-control mb-3" autocomplete="off" required placeholder="Username">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required placeholder="Password">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button name="save_revisi" class="btn btn-warning">Simpan Revisi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL KONFIRMASI SELESAI -->
    <div class="modal fade" id="doneModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">Konfirmasi Selesai (Completed)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="done_id">
                        <p>Masukkan username & password Anda untuk menyelesaikan status hold ini:</p>
                        <div class="mb-3">
                            <label>Username</label>
                            <input type="text" name="username" class="form-control" autocomplete="off" required placeholder="Username">
                        </div>
                        <div class="mb-3">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" required placeholder="Password">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="save_done" class="btn btn-success">Selesaikan Hold</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('part_code').addEventListener('input', function() {
            let partCode = this.value;
            fetch('get_part.php?part_code=' + encodeURIComponent(partCode))
                .then(response => response.json())
                .then(data => {
                    document.getElementById('part_name').value = data.description || '';
                })
                .catch(error => console.log(error));
        });

        function setRevisi(id, qty, reason) {
            document.getElementById('rev_id').value = id;
            document.getElementById('qty_old').value = qty;
            document.getElementById('reason_text').value = reason;
        }

        function setDone(id) {
            document.getElementById('done_id').value = id;
        }
    </script>
</body>

</html>