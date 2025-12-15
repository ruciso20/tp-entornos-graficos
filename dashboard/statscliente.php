<!-- estadísticas para el cliente -->

<section aria-label="Estadísticas del cliente">
  <div class="row mb-4">

    <div class="col-md-4">
      <div class="card card-stat text-white bg-primary"
        aria-label="Promociones disponibles: <?php echo $promociones_disponibles; ?>">
        <div class="card-body text-center">
          <span class="display-6 fw-bold">
            <?php echo $promociones_disponibles; ?>
          </span>
          <p class="mb-0">Promociones Disponibles</p>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card card-stat text-white bg-info"
        aria-label="Novedades disponibles según tu categoría: <?php echo $novedades_disponibles; ?>">
        <div class="card-body text-center">
          <span class="display-6 fw-bold">
            <?php echo $novedades_disponibles; ?>
          </span>
          <p class="mb-0">Novedades según tu Categoría</p>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card card-stat text-white bg-success"
        aria-label="Categoría actual del cliente: <?php echo $categoria; ?>">
        <div class="card-body text-center">
          <span class="display-6 fw-bold">
            <?php echo htmlspecialchars($categoria); ?>
          </span>
          <p class="mb-0">Tu Categoría</p>
        </div>
      </div>
    </div>

  </div>
</section>