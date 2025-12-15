<!-- panel cliente -->

<div class="alert alert-success">
  <h1 class="h5">Panel de Cliente</h1>

  <p>
    ¡Bienvenido <strong><?php echo $nombre; ?></strong>! Disfruta de tus beneficios
  </p>

  <nav aria-label="Panel del cliente">

    <!-- Fila 1 -->
    <div class="row mt-4 justify-content-center">
      <div class="col-md-5 mb-3">
        <div class="card card-hover h-100">
          <div class="card-body text-center">
            <h2 class="h5">Promociones</h2>
            <p>Descubre y utiliza promociones exclusivas según tu categoría</p>
            <a href="cliente/promociones.php"
              class="btn btn-primary w-100"
              role="button"
              aria-label="Ver promociones disponibles">
              Ver Promociones
            </a>
          </div>
        </div>
      </div>

      <div class="col-md-5 mb-3">
        <div class="card card-hover h-100">
          <div class="card-body text-center">
            <h2 class="h5">Novedades</h2>
            <p>Mantente informado de las últimas novedades del shopping</p>
            <a href="cliente/novedades.php"
              class="btn btn-info w-100"
              role="button"
              aria-label="Ver novedades del shopping">
              Ver Novedades
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Fila 2 -->
    <div class="row mt-4 justify-content-center">
      <div class="col-md-5 mb-3">
        <div class="card card-hover h-100">
          <div class="card-body text-center">
            <h2 class="h5">Mi Progreso</h2>
            <p>Revisa todas las promociones que has utilizado</p>
            <a href="cliente/mi_progreso.php"
              class="btn btn-warning w-100"
              role="button"
              aria-label="Ver progreso de promociones">
              Ver Progreso
            </a>
          </div>
        </div>
      </div>

      <div class="col-md-5 mb-3">
        <div class="card card-hover h-100">
          <div class="card-body text-center">
            <h2 class="h5">Mi Perfil</h2>
            <p>Gestiona tu información personal y preferencias</p>
            <a href="cliente/perfil.php"
              class="btn btn-success w-100"
              role="button"
              aria-label="Editar perfil del cliente">
              Editar Perfil
            </a>
          </div>
        </div>
      </div>
    </div>

  </nav>

  <!-- Progreso de categoría de un cliente -->


  <div class="row justify-content-center">
    <div class="col-md-10"> <!-- Mismo ancho que las cards de arriba -->
      <div class="card mt-4">
        <div class="card-header text-center">
          <h6 class="mb-0">Tu Progreso de Categoría</h6>
        </div>
        <div class="card-body">
          <div class="row justify-content-center">
            <div class="col-md-4 mb-3">
              <div class="card <?php echo $categoria == 'Inicial' ? 'bg-primary text-white' : 'bg-light'; ?> h-100">
                <div class="card-body text-center">
                  <h5>Inicial</h5>
                  <p class="small">Promociones básicas</p>
                  <?php echo $categoria == 'Inicial' ? '<span class="badge bg-warning">ACTUAL</span>' : ''; ?>
                </div>
              </div>
            </div>
            <div class="col-md-4 mb-3">
              <div class="card <?php echo $categoria == 'Medium' ? 'bg-warning text-dark' : 'bg-light'; ?> h-100">
                <div class="card-body text-center">
                  <h5>Medium</h5>
                  <p class="small">+ Promociones exclusivas</p>
                  <?php echo $categoria == 'Medium' ? '<span class="badge bg-warning">ACTUAL</span>' : ''; ?>
                </div>
              </div>
            </div>
            <div class="col-md-4 mb-3">
              <div class="card <?php echo $categoria == 'Premium' ? 'bg-danger text-white' : 'bg-light'; ?> h-100">
                <div class="card-body text-center">
                  <h5>Premium</h5>
                  <p class="small">Todas las promociones + <br>beneficios VIP</p>
                  <?php echo $categoria == 'Premium' ? '<span class="badge bg-warning">ACTUAL</span>' : ''; ?>
                </div>
              </div>
            </div>
          </div>
          <div class="text-center mt-3">
            <p class="text-muted mb-0">
              <small>💡 <strong>Consejo:</strong> Usa más promociones para subir de categoría y desbloquear beneficios exclusivos.</small>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>