<!-- estadisticas para dueño -->

<div class="row mb-4">
  <div class="col-md-4">
    <div class="card card-stat text-white bg-primary">
      <div class="card-body text-center">
        <h3><?php echo $locales_aprobados; ?></h3>
        <p>Locales Activos</p>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card card-stat text-white bg-success">
      <div class="card-body text-center">
        <h3><?php echo $promociones_activas; ?></h3>
        <p>Promociones Activas</p>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card card-stat text-white bg-warning">
      <div class="card-body text-center">
        <h3><?php echo $solicitudes_pendientes; ?></h3>
        <p>Solicitudes Pendientes</p>
      </div>
    </div>
  </div>
</div>