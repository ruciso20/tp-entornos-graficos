<!-- Seccion "Por que registrarse" SOLO para usuarios NO registrados -->
<?php if (!isset($_SESSION['user_id'])): ?>
  <section class="py-5 bg-light" id="beneficios">
    <div class="container">
      <div class="row text-center mb-5">
        <div class="col">
          <h2 class="fw-bold">🎯 Beneficios Exclusivos para Clientes Registrados</h2>
          <p class="text-muted fs-5">Regístrate y accede a ventajas especiales según tu categoría</p>
        </div>
      </div>
      <div class="row">
        <div class="col-md-4 mb-4">
          <div class="card card-hover h-100 text-center border-0 shadow-sm">
            <div class="card-body p-4">
              <div class="feature-icon text-primary">
                <i class="fas fa-user"></i>
              </div>
              <h3 class="text-primary mb-3">Nivel Inicial</h3>
              <ul class="list-unstyled text-start">
                <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Acceso a promociones básicas</li>
                <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Notificaciones de ofertas</li>
                <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Acumulación de puntos</li>
                <li class="mb-2"><i class="fas fa-check text-success me-2"></i> App móvil gratuita</li>
              </ul>
            </div>
          </div>
        </div>
        <div class="col-md-4 mb-4">
          <div class="card card-hover h-100 text-center border-0 shadow-sm">
            <div class="card-body p-4">
              <div class="feature-icon text-warning">
                <i class="fas fa-users"></i>
              </div>
              <h3 class="text-warning mb-3">Nivel Medium</h3>
              <ul class="list-unstyled text-start">
                <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Todos los beneficios Inicial</li>
                <li class="mb-2"><i class="fas fa-star text-warning me-2"></i> Promociones exclusivas Medium</li>
                <li class="mb-2"><i class="fas fa-star text-warning me-2"></i> Descuentos especiales +10%</li>
                <li class="mb-2"><i class="fas fa-star text-warning me-2"></i> Atención preferencial</li>
                <li class="mb-2"><i class="fas fa-star text-warning me-2"></i> Estacionamiento gratuito</li>
              </ul>
            </div>
          </div>
        </div>
        <div class="col-md-4 mb-4">
          <div class="card card-hover h-100 text-center border-0 shadow-sm">
            <div class="card-body p-4">
              <div class="feature-icon text-danger">
                <i class="fas fa-crown"></i>
              </div>
              <h3 class="text-danger mb-3">Nivel Premium</h3>
              <ul class="list-unstyled text-start">
                <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Todos los beneficios Medium</li>
                <li class="mb-2"><i class="fas fa-gift text-danger me-2"></i> Promociones Premium exclusivas</li>
                <li class="mb-2"><i class="fas fa-gift text-danger me-2"></i> Regalos sorpresa mensuales</li>
                <li class="mb-2"><i class="fas fa-gift text-danger me-2"></i> Acceso prioritario a eventos</li>
                <li class="mb-2"><i class="fas fa-gift text-danger me-2"></i> Descuentos VIP hasta 30%</li>
                <li class="mb-2"><i class="fas fa-gift text-danger me-2"></i> Asistente personal de compras</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
      <div class="text-center mt-4">
        <a href="register.php" class="btn btn-primary btn-lg px-5 py-3">¡Quiero Estos Beneficios!</a>
      </div>
    </div>
  </section>
<?php endif; ?>