<!-- panel dueño  -->

<div class="alert alert-warning">
  <h5>Panel de Dueño de Locales</h5>
  <p class="mb-3">¡Bienvenido <strong><?php echo $nombre; ?></strong>! Gestiona tus locales y promociones.</p>

  <div class="row mt-3">
    <div class="col-md-3 mb-3">
      <a href="dueno/locales.php" class="btn btn-primary w-100">
        Gestionar Locales
      </a>
    </div>

    <div class="col-md-3 mb-3">
      <?php if ($locales_aprobados > 0): ?>
        <a href="dueno/promociones.php" class="btn btn-success w-100">
          Gestionar Promociones
        </a>
      <?php else: ?>
        <button class="btn btn-secondary w-100" disabled>
          Gestionar Promociones
        </button>
      <?php endif; ?>
    </div>

    <div class="col-md-3 mb-3">
      <?php if ($locales_aprobados > 0): ?>
        <a href="dueno/solicitudes.php" class="btn btn-warning w-100">
          Gestionar Solicitudes
          <?php if ($solicitudes_pendientes > 0): ?>
            <span class="badge bg-danger"><?php echo $solicitudes_pendientes; ?></span>
          <?php endif; ?>
        </a>
      <?php else: ?>
        <button class="btn btn-secondary w-100" disabled>
          Gestionar Solicitudes
        </button>
      <?php endif; ?>
    </div>

    <div class="col-md-3 mb-3">
      <?php if ($locales_aprobados > 0): ?>
        <a href="dueno/reportes.php" class="btn btn-dark w-100">
          Reportes
        </a>
      <?php else: ?>
        <button class="btn btn-secondary w-100" disabled>
          Reportes
        </button>
      <?php endif; ?>
    </div>
  </div>
</div>