<!-- estadísticas para admin -->

<section aria-label="Estadísticas generales del administrador">
  <div class="row mb-4">

    <div class="col-md-3">
      <div class="card card-stat text-white bg-primary"
        aria-label="Total de locales activos: <?php echo $total_locales; ?>">
        <div class="card-body text-center">
          <span class="display-6 fw-bold">
            <?php echo $total_locales; ?>
          </span>
          <p class="mb-0">Locales Activos</p>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="card card-stat text-white bg-success"
        aria-label="Total de clientes registrados: <?php echo $total_clientes; ?>">
        <div class="card-body text-center">
          <span class="display-6 fw-bold">
            <?php echo $total_clientes; ?>
          </span>
          <p class="mb-0">Total Clientes</p>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="card card-stat text-white bg-warning"
        aria-label="Dueños pendientes de aprobación: <?php echo $dueños_pendientes; ?>">
        <div class="card-body text-center">
          <span class="display-6 fw-bold">
            <?php echo $dueños_pendientes; ?>
          </span>
          <p class="mb-0">Dueños Pendientes</p>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="card card-stat text-white bg-danger"
        aria-label="Promociones pendientes de aprobación: <?php echo $promociones_pendientes; ?>">
        <div class="card-body text-center">
          <span class="display-6 fw-bold">
            <?php echo $promociones_pendientes; ?>
          </span>
          <p class="mb-0">Promociones Pendientes</p>
        </div>
      </div>
    </div>

  </div>
</section>