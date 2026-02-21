<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Tax Associate</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

  <!-- Custom style -->
  <link href="<?php echo get_template_directory_uri() ?>/css/style.css" rel="stylesheet">
  
</head>
<body>
  <!-- ================= TOP BAR ================= -->
<div class="topbar">
  <div class="container d-flex justify-content-between">
    <div>
      <i class="bi bi-telephone"></i> 087 245 0491 |
      <i class="bi bi-envelope"></i> contact@sample.ie |
      <i class="bi bi-clock"></i> Mon - Thu: 8:00 am - 5:00 pm
    </div>
    <div>
      <a href="#"><i class="bi bi-facebook"></i></a>
      <a href="#"><i class="bi bi-twitter"></i></a>
      <a href="#"><i class="bi bi-linkedin"></i></a>
    </div>
  </div>
</div>

<!-- ================= NAVBAR ================= -->
<nav class="navbar navbar-expand-lg bg-white">
  <div class="container">
    <?php $logoimg = get_header_image(); ?>
    <a class="navbar-brand" href="<?php echo site_url()?>">TaxAssociate</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse justify-content-end" id="mainNav">
      <?php
        wp_nav_menu(array(
            'theme_location' => 'primary-menu',
            'menu_class'     => 'navbar-nav',
            'container'      => false,
            'walker'         => new Bootstrap_Nav_Walker(),
        ));
      ?>
    </div>
  </div>
</nav>