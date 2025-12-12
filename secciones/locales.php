<?php
// Configuracion de paginacion
$locales_por_pagina = 6; // Mostrar 6 locales por pagina
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $locales_por_pagina;


try {
  include("config/db.php");

  // Contar locales aprobados para mostrar
  $total_locales_query = $conn->query("SELECT COUNT(*) as total FROM locales WHERE estado='aprobado'");
  $total_locales = $total_locales_query->fetch_assoc()['total'];

  // Calcular total de las paginas
  $total_paginas = ceil($total_locales / $locales_por_pagina);

  // validamos la pagina actual
  if ($pagina_actual < 1) $pagina_actual = 1;
  if ($pagina_actual > $total_paginas && $total_paginas > 0) $pagina_actual = $total_paginas;

  // Obtener locales para la página actual
  $locales_activos = $conn->query("
        SELECT * FROM locales 
        WHERE estado='aprobado' 
        ORDER BY nombre 
        LIMIT $locales_por_pagina OFFSET $offset
    ");

  // Para la búsqueda JavaScript, necesitamos todos los locales
  $todos_locales = $conn->query("SELECT * FROM locales WHERE estado='aprobado' ORDER BY nombre");
} catch (Exception $e) {
  error_log("Error en paginación: " . $e->getMessage());
  $total_locales = 0;
  $total_paginas = 1;
}
?>

<!-- seccion de Locales -->
<section class="py-5 bg-light" id="locales">
  <div class="container">
    <div class="row text-center mb-4">
      <div class="col">
        <h2 class="fw-bold">🏪 Nuestros Locales</h2>
        <p class="text-muted">Descubre la variedad de locales en nuestro shopping</p>
      </div>
    </div>

    <!-- barra de búsqueda -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="card shadow-sm">
          <div class="card-body">
            <div class="row justify-content-center">
              <div class="col-md-8">
                <div class="input-group">
                  <span class="input-group-text bg-white border-end-0">
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

    <!-- grid de los Locales -->
    <div class="row" id="localesGrid">
      <?php if (isset($locales_activos) && $locales_activos->num_rows > 0):
        $locales_en_pagina = 0;
      ?>
        <?php while ($local = $locales_activos->fetch_assoc()):
          $locales_en_pagina++;
          $imagen_local = !empty($local['imagen_url']) ? $local['imagen_url'] : null;
        ?>
          <div class="col-lg-4 col-md-6 mb-4 local-card"
            data-nombre="<?php echo htmlspecialchars(strtolower($local['nombre'])); ?>"
            data-descripcion="<?php echo htmlspecialchars(strtolower($local['descripcion'] ?? '')); ?>"
            style="display: block;">
            <div class="card h-100 local-card-item shadow-sm border-0">
              <div class="card-body p-0">
                <!-- Imagen del local -->
                <div class="local-imagen-container position-relative">
                  <?php if ($imagen_local): ?>
                    <img src="<?php echo htmlspecialchars($imagen_local); ?>"
                      class="card-img-top local-imagen"
                      alt="<?php echo htmlspecialchars($local['nombre']); ?>"
                      style="height: 200px; object-fit: cover; border-radius: 12px 12px 0 0;">
                  <?php else: ?>
                    <div class="local-imagen-default d-flex align-items-center justify-content-center"
                      style="height: 200px; background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%); border-radius: 12px 12px 0 0;">
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

        <!-- PAGINACION -->
        <?php if ($total_paginas > 1): ?>
          <div class="col-12 mt-4">
            <nav aria-label="Paginación de locales">
              <ul class="pagination justify-content-center">

                <!-- Boton anterior -->
                <li class="page-item <?php echo ($pagina_actual == 1) ? 'disabled' : ''; ?>">
                  <a class="page-link"
                    href="?pagina=<?php echo $pagina_actual - 1; ?>#locales"
                    aria-label="Anterior">
                    <i class="fas fa-chevron-left"></i>
                  </a>
                </li>

                <!-- Primera pagina -->
                <?php if ($pagina_actual > 3): ?>
                  <li class="page-item">
                    <a class="page-link" href="?pagina=1#locales">1</a>
                  </li>
                  <li class="page-item disabled">
                    <span class="page-link">...</span>
                  </li>
                <?php endif; ?>

                <!-- paginas alrededor de la actual -->
                <?php for ($i = max(1, $pagina_actual - 2); $i <= min($total_paginas, $pagina_actual + 2); $i++): ?>
                  <li class="page-item <?php echo ($i == $pagina_actual) ? 'active' : ''; ?>">
                    <a class="page-link" href="?pagina=<?php echo $i; ?>#locales">
                      <?php echo $i; ?>
                      <?php if ($i == $pagina_actual): ?>
                        <span class="visually-hidden">(actual)</span>
                      <?php endif; ?>
                    </a>
                  </li>
                <?php endfor; ?>

                <!-- ultima pagina -->
                <?php if ($pagina_actual < $total_paginas - 2): ?>
                  <li class="page-item disabled">
                    <span class="page-link">...</span>
                  </li>
                  <li class="page-item">
                    <a class="page-link" href="?pagina=<?php echo $total_paginas; ?>#locales">
                      <?php echo $total_paginas; ?>
                    </a>
                  </li>
                <?php endif; ?>

                <!-- boton siguiente -->
                <li class="page-item <?php echo ($pagina_actual == $total_paginas) ? 'disabled' : ''; ?>">
                  <a class="page-link"
                    href="?pagina=<?php echo $pagina_actual + 1; ?>#locales"
                    aria-label="Siguiente">
                    <i class="fas fa-chevron-right"></i>
                  </a>
                </li>
              </ul>
            </nav>
          </div>
        <?php endif; ?>

      <?php else: ?>
        <div class="col-12 text-center">
          <div class="alert alert-info">
            <h5><i class="fas fa-info-circle"></i> Próximamente más locales...</h5>
            <p class="mb-0">Estamos trabajando para traerte la mejor experiencia.</p>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <!-- total de locales -->
    <?php if ($total_paginas > 1): ?>
      <div class="row mt-4">
        <div class="col-12">
          <div class="card bg-light border-0">
            <div class="card-body text-center py-2">
              <small class="text-muted">
                Mostrando <?php echo $offset + 1; ?> - <?php echo min($offset + $locales_por_pagina, $total_locales); ?>
                de <?php echo $total_locales; ?> Locales
              </small>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- JavaScript para busqueda y paginacion -->
<script>
  // contador
  document.getElementById('countNumber').textContent = <?php echo $locales_activos->num_rows ?? 0; ?>;

  // funcion para la busqueda en tiempo real
  document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchLocales');
    const localCards = document.querySelectorAll('.local-card');
    const resultCount = document.getElementById('countNumber');

    function filterLocales() {
      const searchTerm = searchInput.value.toLowerCase().trim();
      let visibleCount = 0;

      localCards.forEach(card => {
        const nombre = card.getAttribute('data-nombre');
        const descripcion = card.getAttribute('data-descripcion');

        const matchesSearch = nombre.includes(searchTerm) ||
          descripcion.includes(searchTerm);

        if (matchesSearch) {
          card.style.display = 'block';
          visibleCount++;
        } else {
          card.style.display = 'none';
        }
      });

      // Si no hay resultados en esta pagina, mostramos mensaje
      if (visibleCount === 0 && searchTerm !== '') {
        const noResults = document.createElement('div');
        noResults.className = 'col-12 text-center';
        noResults.innerHTML = `
                <div class="alert alert-warning">
                    <h5><i class="fas fa-search me-2"></i>No se encontraron resultados</h5>
                    <p class="mb-0">Intenta con otros términos de búsqueda.</p>
                </div>
            `;

        // Insertar mensaje despues del grid
        const grid = document.getElementById('localesGrid');
        const existingMessage = grid.querySelector('.no-results-message');
        if (!existingMessage) {
          noResults.classList.add('no-results-message');
          grid.appendChild(noResults);
        }
      } else {
        // Remover mensaje si existen
        const existingMessage = document.querySelector('.no-results-message');
        if (existingMessage) {
          existingMessage.remove();
        }
      }

      resultCount.textContent = visibleCount;
    }

    searchInput.addEventListener('input', filterLocales);

    // Scroll suave al hacer clic en paginación
    document.querySelectorAll('.pagination a').forEach(link => {
      link.addEventListener('click', function(e) {
        if (this.getAttribute('href')?.includes('#locales')) {
          e.preventDefault();
          const targetPage = this.getAttribute('href');

          // Scroll suave a la sección locales
          document.getElementById('locales').scrollIntoView({
            behavior: 'smooth'
          });

          // Pequeño delay antes de cambiar de página
          setTimeout(() => {
            window.location.href = targetPage;
          }, 300);
        }
      });
    });

    // Efecto de carga para cambios de página
    document.querySelectorAll('.page-link').forEach(link => {
      link.addEventListener('click', function() {
        if (this.getAttribute('href')?.includes('pagina=')) {
          // Mostrar indicador de carga
          const grid = document.getElementById('localesGrid');
          grid.style.opacity = '0.7';
          grid.style.transition = 'opacity 0.3s';
        }
      });
    });
  });
</script>
<style>
  .pagination .page-item.active .page-link {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-color: #667eea;
    color: white;
  }

  .pagination .page-link {
    color: #667eea;
    border: 1px solid #dee2e6;
    margin: 0 2px;
    border-radius: 8px;
    transition: all 0.3s ease;
    min-width: 40px;
    text-align: center;
    padding: 0.5rem 0.75rem;
    font-weight: 500;
  }

  .pagination .page-link:not(:first-child):not(:last-child) {
    color: #667eea;
  }

  .pagination .page-link i {
    color: #6c757d;
    transition: color 0.3s ease;
  }

  /* Pagina activa */
  .pagination .page-item.active .page-link {
    background: linear-gradient(135deg, #667eea 100%);
    border-color: #667eea;
    color: white !important;
  }

  /* Hover para los numeros */
  .pagination .page-link:hover:not(:first-child):not(:last-child) {
    background-color: rgba(102, 126, 234, 0.1);
    border-color: #667eea;
    color: #4a61d4 !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(102, 126, 234, 0.2);
  }

  /* Hover para las flechas */
  .pagination .page-link:hover i {
    color: #4a61d4;
  }

  .pagination .page-link:hover {
    background-color: rgba(102, 126, 234, 0.1);
    border-color: #667eea;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(102, 126, 234, 0.2);
  }

  /* Flechas deshabilitadas por llegar al tope */
  .pagination .page-item.disabled .page-link {
    color: #adb5bd !important;
    background-color: #f8f9fa;
    border-color: #dee2e6;
  }

  .pagination .page-item.disabled .page-link i {
    color: #adb5bd;
  }

  .local-card-item {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 12px;
    overflow: hidden;
  }

  .local-card-item:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15) !important;
  }

  @keyframes fadeIn {
    from {
      opacity: 0;
      transform: translateY(10px);
    }

    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  #localesGrid>.local-card {
    animation: fadeIn 0.5s ease-out;
  }

  /* Responsive */
  @media (max-width: 768px) {
    .pagination .page-link {
      padding: 0.375rem 0.5rem;
      font-size: 0.875rem;
      min-width: 32px;
      margin: 0 1px;
    }
  }

  .badge.bg-primary {
    background: linear-gradient(135deg, #667eea 100%) !important;
    padding: 0.4em 0.8em;
    font-weight: 500;
  }
</style>