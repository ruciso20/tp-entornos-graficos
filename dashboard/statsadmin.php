<!-- estadisticas para admin -->

<div class="row mb-4">
  <div class="col-md-3">
    <div class="card card-stat text-white bg-primary">
      <div class="card-body text-center">
        <h3><?php echo $total_locales; ?></h3>
        <p>Locales Activos</p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card card-stat text-white bg-success">
      <div class="card-body text-center">
        <h3><?php echo $total_clientes; ?></h3>
        <p>Total Clientes</p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card card-stat text-white bg-warning">
      <div class="card-body text-center">
        <h3><?php echo $dueños_pendientes; ?></h3>
        <p>Dueños Pendientes</p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card card-stat text-white bg-danger">
      <div class="card-body text-center">
        <h3><?php echo $promociones_pendientes; ?></h3>
        <p>Promociones Pendientes</p>
      </div>
    </div>
  </div>
</div>