<!-- seccion de Locales -->
<section class="py-5 bg-light" id="locales">
  <div class="container">
    <div class="row text-center mb-4">
      <div class="col">
        <h2 class="fw-bold">🏪 Nuestros Locales</h2>
        <p class="text-muted">Descubre la variedad de locales en nuestro shopping</p>
      </div>
    </div>

    <!-- barra de búsqueda  -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="card">
          <div class="card-body">
            <div class="row justify-content-center">
              <div class="col-md-8">
                <div class="input-group">
                  <span class="input-group-text bg-light border-end-0">
                    <i class="fas fa-search text-muted"></i>
                  </span>
                  <input type="text" id="searchLocales" class="form-control border-start-0"
                    placeholder="Buscar locales por nombre o descripción..."
                    aria-label="Buscar locales">
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- contador de los resultados de la barra -->
    <div class="row mb-3">
      <div class="col-12">
        <div id="resultCount" class="text-muted">
          Mostrando <span id="countNumber">0</span> locales
        </div>
      </div>
    </div>

    <!-- grid de los Locales -->
    <div class="row" id="localesGrid">
      <?php if (isset($locales_activos) && $locales_activos->num_rows > 0):
        $locales_activos->data_seek(0);
        $locales_count = 0;
      ?>
        <?php while ($local = $locales_activos->fetch_assoc()):
          $locales_count++;
          // Imagen del local o icono
          $imagen_local = !empty($local['imagen_url']) ? $local['imagen_url'] : null;
        ?>
          <div class="col-lg-4 col-md-6 mb-4 local-card"
            data-nombre="<?php echo htmlspecialchars(strtolower($local['nombre'])); ?>"
            data-descripcion="<?php echo htmlspecialchars(strtolower($local['descripcion'] ?? '')); ?>">
            <div class="card h-100 local-card-item shadow-sm">
              <div class="card-body p-0">
                <!-- Imagen del local -->
                <div class="local-imagen-container position-relative">
                  <?php if ($imagen_local): ?>
                    <img src="<?php echo htmlspecialchars($imagen_local); ?>"
                      class="card-img-top local-imagen"
                      alt="<?php echo htmlspecialchars($local['nombre']); ?>"
                      style="height: 200px; object-fit: cover; border-radius: 12px 12px 0 0;">
                  <?php else: ?>
                    <!-- Icono por defecto si no hay imagen -->
                    <div class="local-imagen-default d-flex align-items-center justify-content-center"
                      style="height: 200px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px 12px 0 0;">
                      <div class="text-white text-center">
                        <div style="font-size: 3rem;">🏪</div>
                        <small class="fw-bold"><?php echo htmlspecialchars($local['nombre']); ?></small>
                      </div>
                    </div>
                  <?php endif; ?>
                </div>

                <!-- Contenido textual -->
                <div class="p-3">
                  <h5 class="card-title fw-bold mb-2"><?php echo htmlspecialchars($local['nombre']); ?></h5>

                  <?php if (!empty($local['descripcion'])): ?>
                    <p class="card-text text-muted small mb-3">
                      <?php echo htmlspecialchars($local['descripcion']); ?>
                    </p>
                  <?php endif; ?>

                  <div class="local-info">
                    <?php if (!isset($_SESSION['user_id'])): ?>
                      <div class="alert alert-warning py-2 mb-0">
                        <small>
                          <i class="fas fa-lock me-1"></i>
                          Regístrate para ver promociones
                        </small>
                      </div>
                    <?php else: ?>
                      <div class="alert alert-success py-2 mb-0">
                        <small>
                          <i class="fas fa-check me-1"></i>
                          Promociones disponibles
                        </small>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endwhile; ?>

        <script>
          // Inicializar contador
          document.getElementById('countNumber').textContent = <?php echo $locales_count; ?>;
        </script>

      <?php else: ?>
        <div class="col-12 text-center">
          <div class="alert alert-info">
            <h5><i class="fas fa-info-circle"></i> Próximamente más locales...</h5>
            <p class="mb-0">Estamos trabajando para traerte la mejor experiencia.</p>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>