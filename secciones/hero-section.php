 <!-- Hero Section  -->
 <section class="hero-section">
   <div class="container">
     <div class="row align-items-center">
       <!-- columna izquierda - contenido principal -->
       <div class="col-lg-6">
         <div class="hero-content">
           <div class="hero-badge mb-4">
             <span class="badge bg-light text-dark fs-6">
               <i class="fas fa-star me-2"></i>Más de 50 locales exclusivos
             </span>
           </div>

           <h1 class="hero-title display-4 fw-bold mb-4">
             Vive la experiencia
             <span class="text-primary">Stella</span> Shopping
           </h1>

           <p class="hero-subtitle lead mb-5">
             El destino de compras premium en Rosario.
             Descubre las mejores marcas y promociones exclusivas.
           </p>

           <!-- estadisticas de ejemplo simplificadas -->
           <div class="hero-stats d-flex gap-4 mb-4">
             <div class="stat-item text-center">
               <div class="stat-number text-primary fw-bold">50+</div>
               <div class="stat-label text-muted">Locales</div>
             </div>
             <div class="stat-item text-center">
               <div class="stat-number text-primary fw-bold">100+</div>
               <div class="stat-label text-muted">Marcas</div>
             </div>
             <div class="stat-item text-center">
               <div class="stat-number text-primary fw-bold">24/7</div>
               <div class="stat-label text-muted">Promociones</div>
             </div>
           </div>

           <!-- botones  -->
           <div class="hero-actions d-flex flex-wrap gap-3 mb-4">
             <?php if (!isset($_SESSION['user_id'])): ?>
               <a href="register.php" class="btn btn-primary btn-lg px-4">
                 <i class="fas fa-gift me-2"></i>Regístrate y Ahorra
               </a>
               <a href="#locales" class="btn btn-outline-primary btn-lg px-4">
                 <i class="fas fa-store me-2"></i>Ver Locales
               </a>
             <?php else: ?>
               <a href="dashboard.php" class="btn btn-primary btn-lg px-4">
                 <i class="fas fa-tachometer-alt me-2"></i>Mi Cuenta
               </a>
               <a href="#locales" class="btn btn-outline-primary btn-lg px-4">
                 <i class="fas fa-store me-2"></i>Explorar Locales
               </a>
             <?php endif; ?>
           </div>

           <!-- trust badges ejemplificados -->
           <div class="trust-badges">
             <div class="d-flex flex-wrap gap-3 text-muted">
               <small><i class="fas fa-shield-alt me-1"></i> Compra segura</small>
               <small><i class="fas fa-clock me-1"></i> 10:00 - 22:00</small>
               <small><i class="fas fa-parking me-1"></i> Estacionamiento gratis</small>
             </div>
           </div>
         </div>
       </div>

       <!-- columna derecha - cards flotantes -->
       <div class="col-lg-6">
         <div class="hero-cards position-relative">

           <!-- card 1 -->
           <div class="floating-card card-1">
             <div class="card border-0 shadow-lg card-hover">
               <div class="card-body text-center p-3">
                 <div class="text-primary mb-2">
                   <i class="fas fa-tags fa-2x"></i>
                 </div>
                 <h6 class="fw-bold mb-1">Hasta 50% OFF</h6>
                 <small class="text-muted">Marcas seleccionadas</small>
               </div>
             </div>
           </div>

           <!-- card 2 -->
           <div class="floating-card card-2">
             <div class="card border-0 shadow-lg card-hover">
               <div class="card-body text-center p-3">
                 <div class="text-success mb-2">
                   <i class="fas fa-crown fa-2x"></i>
                 </div>
                 <h6 class="fw-bold mb-1">Club Premium</h6>
                 <small class="text-muted">Beneficios exclusivos</small>
               </div>
             </div>
           </div>

           <!-- card 3 -->
           <div class="floating-card card-3">
             <div class="card border-0 shadow-lg card-hover">
               <div class="card-body text-center p-3">
                 <div class="text-warning mb-2">
                   <i class="fas fa-bolt fa-2x"></i>
                 </div>
                 <h6 class="fw-bold mb-1">Ofertas Flash</h6>
                 <small class="text-muted">Tiempo limitado</small>
               </div>
             </div>
           </div>
         </div>
       </div>
     </div>
   </div>
 </section>