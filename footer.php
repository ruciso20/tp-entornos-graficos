<?php
// Obtener el año actual de forma dinamica
$current_year = date('Y');

// Determinar rol actual del usuario
$current_rol = isset($_SESSION['user_id']) ? ($_SESSION['rol'] ?? 'cliente') : 'invitado';

$url_paths = [
  __DIR__ . '/utils/url.php',          // Si footer está en raíz
  __DIR__ . '/../utils/url.php',       // Si footer está en subcarpeta
  __DIR__ . '/../../utils/url.php',    // Si footer está 2 niveles abajo
];

//vemos si encuentra la ubicacion segun las que tenemos definidas
$url_loaded = false;
foreach ($url_paths as $path) {
  if (file_exists($path)) {
    require_once $path;
    $url_loaded = true;
    break;
  }
}

// Si falllan los 3 intentos de busqueda
if (!$url_loaded) {
  function smartUrl($path = '')
  {
    // hardcodeamos /tpentornosgraficos/
    return '/tpentornosgraficos/' . ltrim($path, '/');
  }
}
?>

<!-- Footer  -->
<footer class="bg-dark text-white py-5 mt-5">
  <div class="container">
    <div class="row justify-content-between">
      <!-- Columna 1 - Nombre, descripcion y redes sociales -->
      <div class="col-lg-4 col-md-6 mb-4">
        <h5><i class="fas fa-shopping-bag me-2" aria-hidden="true"></i> Stella Shopping Rosario</h5>
        <p class="mt-3">El mejor shopping de la ciudad con las promociones más exclusivas y la mejor experiencia de compras.</p>

        <!-- Indicador de rol segun el tipo de usuario -->
        <?php if ($current_rol != 'invitado'): ?>
          <div class="mb-3">
            <small class="text-light">
              <i class="fas fa-user-circle me-1"></i>
              Sesión activa como:
              <span class="badge bg-<?php
                                    switch ($current_rol) {
                                      case 'admin':
                                        echo 'success';
                                        break;
                                      case 'dueño':
                                        echo 'success';
                                        break;
                                      case 'cliente':
                                        echo 'success';
                                        break;
                                      default:
                                        echo 'secondary';
                                    }
                                    ?>">
                <?php echo ucfirst($current_rol); ?>
              </span>
            </small>
          </div>
        <?php endif; ?>

        <div class="social-links mt-3">
          <a href="#" class="text-white me-3" title="Facebook">
            <i class="fab fa-facebook fa-lg"></i></a>
          <a href="#" class="text-white me-3" title="Instagram">
            <i class="fab fa-instagram fa-lg"></i></a>
          <a href="#" class="text-white me-3" title="Twitter">
            <i class="fab fa-twitter fa-lg"></i></a>
          <a href="#" class="text-white" title="TikTok">
            <i class="fab fa-tiktok fa-lg"></i></a>
        </div>
      </div>

      <!-- Columna 2 - Enlaces segun cada tipo de usuario en particular -->
      <div class="col-lg-3 col-md-6 mb-4">
        <h6 id="enlaces-rapidos">Enlaces Rapidos</h6>
        <ul class="list-unstyled">
          <?php if ($current_rol != 'invitado'): ?>
            <li class="mb-2">
              <a href="<?php echo smartUrl('dashboard.php'); ?>" class="text-white text-decoration-none">
                <i class="fas fa-tachometer-alt me-1"></i>
                <?php echo "Panel de Control" ?>
              </a>
            </li>

            <!-- Secciones segun rol -->
            <?php if ($current_rol == 'cliente'): ?>

              <!-- CLIENTE -->
              <li class="mb-2">
                <a href="<?php echo smartUrl('cliente/mi_progreso.php'); ?>" class="text-white text-decoration-none">
                  <i class="fas fa-chart-line me-1"></i> Mi Progreso
                </a>
              </li>
              <li class="mb-2">
                <a href="<?php echo smartUrl('cliente/promociones.php'); ?>" class="text-white text-decoration-none">
                  <i class="fas fa-tags me-1"></i> Mis Promociones
                </a>
              </li>
              <li class="mb-2">
                <a href="<?php echo smartUrl('cliente/novedades.php'); ?>" class="text-white text-decoration-none">
                  <i class="fas fa-newspaper me-1"></i> Mis Novedades
                </a>
              </li>
              <li class="mb-2">
                <a href="<?php echo smartUrl('cliente/perfil.php'); ?>" class="text-white text-decoration-none">
                  <i class="fas fa-user me-1"></i> Mi Perfil
                </a>
              </li>

            <?php elseif ($current_rol == 'dueno'): ?>

              <!-- DUEÑO -->
              <li class="mb-2">
                <a href="<?php echo smartUrl('dueno/locales.php'); ?>" class="text-white text-decoration-none">
                  <i class="fas fa-store me-1"></i> Mis Locales
                </a>
              </li>
              <li class="mb-2">
                <a href="<?php echo smartUrl('dueno/promociones.php'); ?>" class="text-white text-decoration-none">
                  <i class="fas fa-tags me-1"></i> Mis Promociones
                </a>
              </li>
              <li class="mb-2">
                <a href="<?php echo smartUrl('dueno/solicitudes.php'); ?>" class="text-white text-decoration-none">
                  <i class="fas fa-clipboard-list me-1"></i> Solicitudes de Promociones
                </a>
              </li>
              <li class="mb-2">
                <a href="<?php echo smartUrl('dueno/reportes.php'); ?>" class="text-white text-decoration-none">
                  <i class="fas fa-chart-pie me-1"></i> Reportes
                </a>
              </li>

            <?php elseif ($current_rol == 'admin'): ?>

              <!-- ADMIN -->
              <li class="mb-2">
                <a href="<?php echo smartUrl('admin/admin_locales.php'); ?>" class="text-white text-decoration-none">
                  <i class="fas fa-store me-1"></i> Locales
                </a>
              </li>
              <li class="mb-2">
                <a href="<?php echo smartUrl('admin/admin_usuarios.php'); ?>" class="text-white text-decoration-none">
                  <i class="fas fa-users me-1"></i> Usuarios
                </a>
              </li>
              <li class="mb-2">
                <a href="<?php echo smartUrl('admin/admin_promociones.php'); ?>" class="text-white text-decoration-none">
                  <i class="fas fa-tags me-1"></i> Promociones
                </a>
              </li>
              <li class="mb-2">
                <a href="<?php echo smartUrl('admin/admin_novedades.php'); ?>" class="text-white text-decoration-none">
                  <i class="fas fa-newspaper me-1"></i> Novedades
                </a>
              </li>
            <?php endif; ?>

            <li class="mt-3 pt-2 border-top">
              <a href="<?php echo smartUrl('logout.php'); ?>" class="text-white text-decoration-none">
                <i class="fas fa-sign-out-alt me-1"></i> Cerrar Sesión
              </a>
            </li>

          <?php else: ?>

            <!-- USUARIO NO REGISTRADO -->
            <li class="mb-2">
              <a href="<?php echo smartUrl('login.php'); ?>" class="text-white text-decoration-none">
                <i class="fas fa-sign-in-alt me-1"></i> Iniciar Sesión
              </a>
            </li>
            <li class="mb-2">
              <a href="<?php echo smartUrl('register.php'); ?>" class="text-white text-decoration-none">
                <i class="fas fa-user-plus me-1"></i> Registrarse
              </a>
            </li>
          <?php endif; ?>
        </ul>
      </div>

      <!-- Columna 3 - Contacto -->
      <div class="col-lg-3 col-md-6 mb-4">
        <h6 id="contacto-footer">Contacto</h6>
        <ul class="list-unstyled">
          <li class="mb-2">
            <a href="<?php echo smartUrl('index.php#contacto'); ?>" class="text-white text-decoration-none">
              <i class="fas fa-envelope me-2"></i> Formulario de Contacto
            </a>
          </li>
          <li class="mb-2">
            <i class="fas fa-map-marker-alt me-2" aria-hidden="true"></i>
            <span class="text-white">Av. San Martín 1234, Rosario</span>
          </li>
          <li class="mb-2">
            <i class="fas fa-phone me-2" aria-hidden="true"></i>
            <span class="text-white">(341) 123-4567</span>
          </li>
          <li class="mb-2">
            <i class="fas fa-clock me-2" aria-hidden="true"></i>
            <span class="text-white">Lun-Dom: 10:00 - 22:00</span>
          </li>
          <li class="mb-2">
            <i class="fas fa-envelope me-2" aria-hidden="true"></i>
            <span class="text-white">info@stellashopping.com</span>
          </li>
        </ul>
      </div>
    </div>

    <!-- Pie del propio footer -->
    <div class="row align-items-center">
      <div class="col-md-6">
        <p class="mb-0">
          <i class="fas fa-copyright me-1"></i>
          <?php echo $current_year; ?> Stella Shopping Rosario. <br>
          Todos los derechos reservados.
        </p>
      </div>
      <div class="col-md-6 text-md-end">
        <a href="#" class="text-white text-decoration-none me-3">
          <i class="fas fa-shield-alt me-1"></i> Política de Privacidad
        </a>
        <a href="#" class="text-white text-decoration-none">
          <i class="fas fa-file-contract me-1"></i> Términos de Servicio
        </a>
      </div>
    </div>
  </div>
</footer>