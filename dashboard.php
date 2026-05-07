<?php
require 'conn.php';
session_start();
if (!isset($_SESSION['role'])) {
  header('location: index.php');
  exit;
}

if (isset($_POST['btn_logout'])) {
  session_destroy();
  header('location: ../index.php');
  exit;
}
?>

<!doctype html>
<html lang="en" data-bs-theme="auto">

<head>
  <script src="js/color-modes.js"></script>
  <title>HEPI Apps</title>
  <link rel="canonical" href="https://getbootstrap.com/docs/5.3/examples/sign-in/">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@docsearch/css@3">
  <link href="css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
</head>

<body class="d-flex align-items-center py-4 bg-body-tertiary">
  <!-- Import Themes -->
  <?php include 'library/themes.php'; ?>
  <!DOCTYPE html>
  <html lang="en">

  <head>
    <meta charset="UTF-8">
    <title>AC System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
      html,
      body {
        margin: 0;
        padding: 0;
        height: 100%;
        overflow: hidden;
      }
    </style>
  </head>

  <body>
    <div class="container-fluid text-center position-relative w-100 h-100">
      <!-- Button Logout -->
      <div class="position-absolute top-0 end-0 me-4">
        <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#logoutModal">Logout</button>
      </div>
      <!-- Modal Logout -->
      <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <form action="" method="POST">
              <div class="modal-header">
                <h1 class="modal-title fs-5" id="logoutModalLabel">Notification</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">Logout?</div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
                <button type="submit" class="btn btn-primary" name="btn_logout">Yes</button>
              </div>
            </form>
          </div>
        </div>
      </div>
      <!-- Menu -->
      <div class="d-flex justify-content-center align-items-center h-100">
        <div>
          <div class="mb-3">
            <a href="leader/index.php" class="btn btn-lg btn-primary w-100 p-5">Heat Exchanger</a>
          </div>
          <div>
            <a href="page_piping/index.php" class="btn btn-lg btn-primary w-100 p-5">Piping</a>
          </div>
          <div style="opacity: 0; position: absolute; left: -9999px;">
            <form id="scanForm">
              <input type="text" id="myInput" autofocus>
            </form>
          </div>
        </div>
      </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
      const form = document.getElementById("scanForm");
      const input = document.getElementById("myInput");

      form.addEventListener("submit", function(e) {
        e.preventDefault();
        const url = input.value.trim();

        if (url !== "") {
          window.location.href = url;
        }
      });

      window.onload = () => input.focus();
      input.addEventListener("blur", () => setTimeout(() => input.focus(), 0));
    </script>
  </body>

  </html>