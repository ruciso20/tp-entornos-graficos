<!-- seccion de las Novedades  -->
<section class="py-5 bg-light" id="novedades">
  <div class="container">
    <div class="row text-center mb-5">
      <div class="col">
        <h2 class="fw-bold">📢 Novedades del Shopping</h2>
        <p class="text-muted fs-5">
          <?php if (isset($_SESSION['user_id'])): ?>
            Información importante para ti
          <?php else: ?>
            Mantente informado de las últimas noticias
          <?php endif; ?>
        </p>
      </div>
    </div>

    <div class="row">
      <?php if (isset($novedades_publicas) && $novedades_publicas->num_rows > 0): ?>
        <?php while ($novedad = $novedades_publicas->fetch_assoc()):
          $es_exclusiva = $novedad['categoria_objetivo'] != 'Inicial';
        ?>
          <div class="col-md-6 col-lg-4 mb-4">
            <div class="card card-hover h-100 novedad-card <?php echo $es_exclusiva ?: ''; ?>">
              <div class="card-body">

                <h5 class="card-title"><?php echo htmlspecialchars($novedad['titulo']); ?></h5>

                <?php if (!empty($novedad['descripcion'])): ?>
                  <p class="card-text"><?php echo htmlspecialchars($novedad['descripcion']); ?></p>
                <?php endif; ?>

                <div class="d-flex justify-content-between align-items-center mt-3">
                  <small class="text-muted">
                    <i class="fas fa-calendar"></i>
                    <?php echo date('d/m/Y', strtotime($novedad['fecha_inicio'])); ?>
                    -
                    <?php echo date('d/m/Y', strtotime($novedad['fecha_fin'])); ?>
                  </small>
                  <span class="badge bg-<?php
                                        echo $novedad['categoria_objetivo'] == 'Premium' ? 'danger' : ($novedad['categoria_objetivo'] == 'Medium' ? 'warning' : 'info');
                                        ?>">
                    <?php echo $novedad['categoria_objetivo']; ?>
                  </span>
                </div>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="col-12 text-center">
          <div class="alert alert-info">
            <h5><i class="fas fa-info-circle"></i> No hay novedades en este momento</h5>
            <p class="mb-0">Vuelve pronto para conocer las últimas noticias del shopping.</p>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <?php if (!isset($_SESSION['user_id'])): ?>
      <div class="text-center mt-4">
        <div class="alert alert-warning">
          <h5><i class="fas fa-user-plus"></i> Más Novedades y Promociones para Clientes Registrados</h5>
          <p>Regístrate para acceder a información exclusiva según tu categoría</p>
          <a href="register.php" class="btn btn-primary">
            <i class="fas fa-user-plus"></i> Registrarse Gratis
          </a>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>