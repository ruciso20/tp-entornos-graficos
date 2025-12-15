<!-- seccion Newsletter -->
<section
  class="py-5 bg-primary text-white"
  id="newsletter"
  aria-label="Suscripción a novedades del shopping">
  <div class="container">
    <div class="row justify-content-center text-center">
      <div class="col-lg-8">
        <div class="newsletter-content">
          <div class="mb-4">
            <i class="fas fa-envelope-open-text fa-3x mb-3" aria-hidden="true"></i>
            <h2 class="fw-bold">Recibe Nuestras Novedades</h2>
            <p class="lead mb-4">Mantente informado sobre promociones exclusivas y las últimas novedades del shopping.</p>
          </div>

          <?php if (isset($_SESSION['user_id'])): ?>
            <!-- Usuario registrado -->
            <div class="alert alert-success mb-4" role="alert">
              <i class="fas fa-check-circle me-2" aria-hidden="true"></i>
              <strong>¡Ya estás suscrito!</strong>
              Como usuario registrado, recibirás automáticamente nuestras novedades.
            </div>


            <div class="row mt-4">
              <div class="col-md-6">
                <div class="feature-item">
                  <i class="fas fa-bell fa-2x text-warning mb-3" aria-hidden="true"></i>
                  <h5>Notificaciones Personalizadas</h5>
                  <small>Recibe ofertas según tus intereses y categoría</small>
                </div>
              </div>
              <div class="col-md-6">
                <div class="feature-item">
                  <i class="fas fa-star fa-2x text-warning mb-3" aria-hidden="true"></i>
                  <h5>Contenido Exclusivo</h5>
                  <small>Promociones que otros no ven</small>
                </div>
              </div>
            </div>

          <?php else: ?>
            <!-- Usuario NO registrado -->
            <div class="alert alert-warning mb-4" role="alert">
              <i class="fas fa-info-circle me-2" aria-hidden="true"></i>
              <strong>Regístrate para recibir novedades</strong>
              Crea una cuenta y recibe promociones personalizadas según tu categoría
            </div>


            <div class="hero-actions d-flex flex-wrap gap-3 justify-content-center">
              <a href="register.php" class="btn btn-warning btn-lg px-4">
                <i class="fas fa-user-plus me-2" aria-hidden="true"></i>Crear Cuenta
              </a>
              <a href="login.php" class="btn btn-outline-light btn-lg px-4">
                <i class="fas fa-sign-in-alt me-2" aria-hidden="true"></i>Ya Tengo Cuenta
              </a>
            </div>

            <div class="row mt-5">
              <div class="col-md-4">
                <div class="feature-item">
                  <i class="fas fa-bolt fa-2x text-warning mb-3" aria-hidden="true"></i>
                  <h5>Ofertas Exclusivas</h5>
                  <small>Según tu categoría de usuario</small>
                </div>
              </div>
              <div class="col-md-4">
                <div class="feature-item">
                  <i class="fas fa-calendar-star fa-2x text-warning mb-3" aria-hidden="true"></i>
                  <h5>Eventos Prioritarios</h5>
                  <small>Invitaciones a eventos especiales</small>
                </div>
              </div>
              <div class="col-md-4">
                <div class="feature-item">
                  <i class="fas fa-gift fa-2x text-warning mb-3" aria-hidden="true"></i>
                  <h5>Beneficios Únicos</h5>
                  <small>Descuentos y regalos sorpresa</small>
                </div>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>