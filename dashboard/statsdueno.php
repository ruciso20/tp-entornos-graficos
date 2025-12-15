<!-- estadísticas para dueño -->

<section aria-label="Estadísticas del dueño de locales">
  <div class="row mb-4">

    <div class="col-md-4">
      <div class="card card-stat text-white bg-primary"
        aria-label="Locales activos: <?php echo $locales_aprobados; ?>">
        <div class="card-body text-center">
          <span class="display-6 fw-bold">
            <?php echo $locales_aprobados; ?>
          </span>
          <p class="mb-0">Locales Activos</p>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card card-stat text-white bg-success"
        aria-label="Promociones activas: <?php echo $promociones_activas; ?>">
        <div class="card-body text-center">
          <span class="display-6 fw-bold">
            <?php echo $promociones_activas; ?>
          </span>
          <p class="mb-0">Promociones Activas</p>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card card-stat text-white bg-warning"
        aria-label="Solicitudes pendientes: <?php echo $solicitudes_pendientes; ?>">
        <div class="card-body text-center">
          <span class="display-6 fw-bold">
            <?php echo $solicitudes_pendientes; ?>
          </span>
          <p class="mb-0">Solicitudes Pendientes</p>
        </div>
      </div>
    </div>

  </div>
</section>