<?php
require '../conn.php';
if (isset($_GET['part_code'])) {
    $part_code = mysqli_real_escape_string(
        $conn,
        $_GET['part_code']
    );
    $sql = "
    SELECT description
    FROM matrix_part
    WHERE component='$part_code'
    LIMIT 1
    ";
    $result = mysqli_query($conn, $sql);
    if ($row = mysqli_fetch_assoc($result)) {
        echo json_encode([
            'description' => $row['description']
        ]);
    } else {
        echo json_encode([
            'description' => ''
        ]);
    }
}
