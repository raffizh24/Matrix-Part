<?php
$conn = mysqli_connect("localhost", "root", "", "seid_ac_matrix-part");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
