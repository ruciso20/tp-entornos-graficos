<?php
// Obtener el año actual dinámicamente
$current_year = date('Y');
?>

<!-- Footer  -->
<footer class="bg-dark text-white py-5 mt-5">
  <div class="container">
    <div class="row">
      <div class="col-lg-4 col-md-6 mb-4">
        <h5><i class="fas fa-shopping-bag me-2"></i> Stella Shopping Rosario</h5>
        <p class="mt-3">El mejor shopping de la ciudad con las promociones más exclusivas y la mejor experiencia de compras.</p>
        <div class="social-links mt-3">
          <a href="#" class="text-white-50 me-3" title="Facebook"><i class="fab fa-facebook fa-lg"></i></a>
          <a href="#" class="text-white-50 me-3" title="Instagram"><i class="fab fa-instagram fa-lg"></i></a>
          <a href="#" class="text-white-50 me-3" title="Twitter"><i class="fab fa-twitter fa-lg"></i></a>
          <a href="#" class="text-white-50" title="TikTok"><i class="fab fa-tiktok fa-lg"></i></a>
        </div>
      </div>
      <div class="col-lg-2 col-md-6 mb-4">
        <h6>Enlaces Rápidos</h6>
        <ul class="list-unstyled">
          <li class="mb-2"><a href="index.php#promociones" class="text-white-50 text-decoration-none">Promociones</a></li>
          <li class="mb-2"><a href="index.php#locales" class="text-white-50 text-decoration-none">Locales</a></li>
          <li class="mb-2"><a href="index.php#novedades" class="text-white-50 text-decoration-none">Novedades</a></li>
          <li class="mb-2"><a href="index.php#contacto" class="text-white-50 text-decoration-none">Contacto</a></li>
        </ul>
      </div>
      <div class="col-lg-3 col-md-6 mb-4">
        <h6>Mi Cuenta</h6>
        <ul class="list-unstyled">
          <?php if (isset($_SESSION['user_id'])): ?>
            <li class="mb-2"><a href="dashboard.php" class="text-white-50 text-decoration-none">Dashboard</a></li>
            <li class="mb-2"><a href="logout.php" class="text-white-50 text-decoration-none">Cerrar Sesión</a></li>
          <?php else: ?>
            <li class="mb-2"><a href="login.php" class="text-white-50 text-decoration-none">Iniciar Sesión</a></li>
            <li class="mb-2"><a href="register.php" class="text-white-50 text-decoration-none">Registrarse</a></li>
          <?php endif; ?>
        </ul>
      </div>
      <div class="col-lg-3 col-md-6 mb-4">
        <h6>Contacto</h6>
        <p class="mb-2"><i class="fas fa-map-marker-alt me-2"></i> Av. San Martín 1234, Rosario</p>
        <p class="mb-2"><i class="fas fa-phone me-2"></i> (341) 123-4567</p>
        <p class="mb-2"><i class="fas fa-envelope me-2"></i> info@stellashopping.com</p>
        <p class="mb-2"><i class="fas fa-clock me-2"></i> Lun-Dom: 10:00 - 22:00</p>
      </div>
    </div>
    <hr class="my-4">
    <div class="row align-items-center">
      <div class="col-md-6">
        <p class="mb-0">&copy; <?php echo $current_year; ?> Stella Shopping. Todos los derechos reservados.</p>
      </div>
      <div class="col-md-6 text-md-end">
        <a href="#" class="text-white-50 text-decoration-none me-3">Política de Privacidad</a>
        <a href="#" class="text-white-50 text-decoration-none">Términos de Servicio</a>
      </div>
    </div>
  </div>
</footer>