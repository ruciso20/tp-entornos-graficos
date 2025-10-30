<!-- seccion de Promociones Destacadas -->
<section class="py-5 bg-light" id="promociones">
  <div class="container">
    <div class="row text-center mb-4">
      <div class="col">
        <h2 class="fw-bold">🔥 Todas las Promociones Disponibles</h2>
        <p class="text-muted fs-5">
          Descubre todas las ofertas del shopping. Regístrate para acceder a las promociones de tu categoría.
        </p>
      </div>
    </div>

    <div class="row">
      <?php if (isset($promociones_destacadas) && $promociones_destacadas->num_rows > 0): ?>
        <?php while ($promo = $promociones_destacadas->fetch_assoc()):
          // Determinar clase CSS según categoría del cliente
          $clase_categoria = '';
          $texto_categoria = '';

          switch ($promo['categoria_minima']) {
            case 'Premium':
              $clase_categoria = 'promo-premium';
              $texto_categoria = 'Premium';
              $icono_categoria = 'fas fa-crown';
              break;
            case 'Medium':
              $clase_categoria = 'promo-medium';
              $texto_categoria = 'Medium';
              $icono_categoria = 'fas fa-star';
              break;
            default:
              $clase_categoria = 'promo-inicial';
              $texto_categoria = 'Inicial';
              $icono_categoria = 'fas fa-user';
          }
        ?>
          <div class="col-lg-4 col-md-6 mb-4">
            <div class="card promo-card h-100 <?php echo $clase_categoria; ?>">
              <span class="categoria-badge badge bg-<?php
                                                    echo $promo['categoria_minima'] == 'Premium' ? 'danger' : ($promo['categoria_minima'] == 'Medium' ? 'warning' : 'info');
                                                    ?>">
                <i class="<?php echo $icono_categoria; ?> me-1"></i>
                <?php echo $texto_categoria; ?>
              </span>

              <div class="card-body">
                <h5 class="card-title"><?php echo htmlspecialchars($promo['titulo']); ?></h5>
                <p class="card-text">
                  <strong><i class="fas fa-store"></i> Local:</strong> <?php echo htmlspecialchars($promo['local_nombre']); ?><br>
                  <strong><i class="fas fa-calendar"></i> Válida hasta:</strong> <?php echo date('d/m/Y', strtotime($promo['fecha_fin'])); ?>
                </p>

                <?php if (!empty($promo['descripcion'])): ?>
                  <p class="card-text small text-muted">
                    <?php echo htmlspecialchars($promo['descripcion']); ?>
                  </p>
                <?php endif; ?>

                <div class="mb-3">
                  <span class="badge bg-secondary">
                    <i class="fas fa-calendar-day"></i> <?php echo $promo['dias_validos']; ?>
                  </span>
                </div>

                <!--  pedir registro si es necesario -->
                <div class="access-info mt-3">
                  <?php if (!isset($_SESSION['user_id'])): ?>
                    <div class="alert alert-warning mb-0">
                      <small>
                        <i class="fas fa-lock me-1"></i>
                        <?php if ($promo['categoria_minima'] == 'Inicial'): ?>
                          Regístrate para acceder a esta promoción
                        <?php else: ?>
                          Regístrate y sube de categoría para acceder
                        <?php endif; ?>
                      </small>
                    </div>
                  <?php else: ?>
                    <div class="alert alert-<?php
                                            // verificamos si el usuario puede acceder
                                            $puede_acceder = false;
                                            switch ($_SESSION['categoria']) {
                                              case 'Premium':
                                                $puede_acceder = true;
                                                break;
                                              case 'Medium':
                                                $puede_acceder = in_array($promo['categoria_minima'], ['Inicial', 'Medium']);
                                                break;
                                              case 'Inicial':
                                                $puede_acceder = $promo['categoria_minima'] == 'Inicial';
                                                break;
                                            }

                                            echo $puede_acceder ? 'success' : 'warning';
                                            ?> mb-0">
                      <small>
                        <i class="fas fa-<?php echo $puede_acceder ? 'check-circle' : 'info-circle'; ?> me-1"></i>
                        <?php if ($puede_acceder): ?>
                          Disponible para tu categoría
                        <?php else: ?>
                          Requiere categoría <?php echo $promo['categoria_minima']; ?>
                        <?php endif; ?>
                      </small>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="col-12 text-center">
          <div class="alert alert-info">
            <h5><i class="fas fa-info-circle"></i> No hay promociones disponibles en este momento</h5>
            <p class="mb-0">Vuelve más tarde para descubrir nuevas ofertas exclusivas.</p>
          </div>
        </div>
      <?php endif; ?>
    </div>