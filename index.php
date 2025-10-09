<?php
session_start();

// Inicializar variables para evitar errores
$locales_activos = [];
$novedades_publicas = [];
$promociones_generales = [];

try {
    include("config/db.php");
    
    // Obtener locales activos
    $locales_activos = $conn->query("SELECT * FROM locales WHERE estado='activo' ORDER BY nombre");
    
    // Obtener novedades
    $novedades_publicas = $conn->query("
        SELECT * FROM novedades 
        WHERE fecha_fin >= CURDATE() 
        AND estado = 'activa'
        ORDER BY fecha_inicio DESC 
        LIMIT 3
    ");
    
    // Obtener promociones - CONSULTA SEGURA
    $table_exists = $conn->query("SHOW TABLES LIKE 'promociones'");
    if ($table_exists->num_rows > 0) {
        // Verificar si las columnas existen
        $columns = $conn->query("SHOW COLUMNS FROM promociones");
        $has_estado_promo = false;
        $has_fecha_hasta = false;
        
        while($col = $columns->fetch_assoc()) {
            if ($col['Field'] == 'estadoPromo') $has_estado_promo = true;
            if ($col['Field'] == 'fechaHastaPromo') $has_fecha_hasta = true;
        }
        
        if ($has_estado_promo && $has_fecha_hasta) {
            $promociones_generales = $conn->query("
                SELECT p.*, l.nombre as local_nombre 
                FROM promociones p 
                LEFT JOIN locales l ON p.codLocal = l.id 
                WHERE p.estadoPromo = 'aprobada' 
                AND p.fechaHastaPromo >= CURDATE()
                ORDER BY p.fechaCreacion DESC 
                LIMIT 6
            ");
        }
    }
    
} catch (Exception $e) {
    // Si hay error, continuar sin datos
    error_log("Error en index.php: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Stella Shopping Rosario - Ofertas y Promociones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .hero-section {
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('https://via.placeholder.com/1920x600/2c3e50/ffffff?text=Shopping+Rosario');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
            text-align: center;
        }
        .card-hover {
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        .carousel-control-prev,
        .carousel-control-next {
            width: 5%;
            background: rgba(0,0,0,0.3);
        }
        .carousel-item {
            padding: 20px 0;
        }
        .local-card {
            margin: 0 10px;
            height: 100%;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <strong>🛍️ Stella Shopping Rosario</strong>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="#locales">Locales</a></li>
                    <li class="nav-item"><a class="nav-link" href="#promociones">Promociones</a></li>
                    <li class="nav-item"><a class="nav-link" href="#novedades">Novedades</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contacto">Contacto</a></li>
                </ul>
                <div class="navbar-nav">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a class="nav-link" href="dashboard.php">Mi Cuenta</a>
                        <a class="nav-link" href="logout.php">Cerrar Sesión</a>
                    <?php else: ?>
                        <a class="nav-link" href="login.php">Iniciar Sesión</a>
                        <a class="nav-link" href="register.php">Registrarse</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <h1 class="display-4 fw-bold">Bienvenido a Stella Shopping Rosario</h1>
            <p class="lead">¡Descubre las mejores ofertas y promociones en tus locales favoritos!</p>
            <?php if (!isset($_SESSION['user_id'])): ?>
                <div class="mt-4">
                    <a href="register.php" class="btn btn-primary btn-lg me-3">🎁 Regístrate y Ahorra</a>
                    <a href="#promociones" class="btn btn-outline-light btn-lg">Ver Promociones</a>
                </div>
            <?php else: ?>
                <div class="mt-4">
                    <a href="dashboard.php" class="btn btn-success btn-lg me-3">Ir a Mi Cuenta</a>
                    <a href="#promociones" class="btn btn-outline-light btn-lg">Ver Promociones</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Sección: Por qué registrarse -->
    <section class="py-5 bg-light" id="beneficios">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col">
                    <h2>🎯 Beneficios Exclusivos para Clientes Registrados</h2>
                    <p class="text-muted">Regístrate y accede a ventajas especiales según tu categoría</p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card card-hover h-100 text-center">
                        <div class="card-body">
                            <h3 class="text-primary">👤 Inicial</h3>
                            <ul class="list-unstyled">
                                <li>✅ Acceso a promociones básicas</li>
                                <li>✅ Notificaciones de ofertas</li>
                                <li>✅ Acumulación de puntos</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card card-hover h-100 text-center">
                        <div class="card-body">
                            <h3 class="text-warning">👥 Medium</h3>
                            <ul class="list-unstyled">
                                <li>✅ Todos los beneficios Inicial</li>
                                <li>⭐ Promociones exclusivas Medium</li>
                                <li>⭐ Descuentos especiales</li>
                                <li>⭐ Atención preferencial</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card card-hover h-100 text-center">
                        <div class="card-body">
                            <h3 class="text-danger">⭐ Premium</h3>
                            <ul class="list-unstyled">
                                <li>✅ Todos los beneficios Medium</li>
                                <li>🎁 Promociones Premium exclusivas</li>
                                <li>🎁 Regalos sorpresa</li>
                                <li>🎁 Acceso prioritario a eventos</li>
                                <li>🎁 Descuentos VIP</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <?php if (!isset($_SESSION['user_id'])): ?>
            <div class="text-center mt-4">
                <a href="register.php" class="btn btn-primary btn-lg">¡Quiero Estos Beneficios!</a>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Sección: Locales con Carrusel -->
    <section class="py-5" id="locales">
        <div class="container">
            <div class="row text-center mb-4">
                <div class="col">
                    <h2>🏪 Nuestros Locales</h2>
                    <p class="text-muted">Descubre la variedad de locales en nuestro shopping</p>
                </div>
            </div>
            
            <?php if (isset($locales_activos) && $locales_activos->num_rows > 0): ?>
                <div id="localesCarousel" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <?php
                        $locales_count = 0;
                        $locales_per_slide = 3;
                        $total_locales = $locales_activos->num_rows;
                        
                        // Reiniciar el puntero del resultado
                        $locales_activos->data_seek(0);
                        
                        while($local = $locales_activos->fetch_assoc()):
                            if ($locales_count % $locales_per_slide == 0):
                        ?>
                        <div class="carousel-item <?php echo $locales_count == 0 ? 'active' : ''; ?>">
                            <div class="row justify-content-center">
                        <?php endif; ?>
                        
                                <div class="col-md-4">
                                    <div class="card card-hover local-card">
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <span class="display-6">
                                                    <?php 
                                                    // Iconos según el tipo de local
                                                    $icono = "🏪";
                                                    if (strpos(strtolower($local['nombre']), 'ropa') !== false) $icono = "👕";
                                                    elseif (strpos(strtolower($local['nombre']), 'comida') !== false) $icono = "🍕";
                                                    elseif (strpos(strtolower($local['nombre']), 'perfum') !== false) $icono = "💄";
                                                    elseif (strpos(strtolower($local['nombre']), 'óptica') !== false) $icono = "👓";
                                                    elseif (strpos(strtolower($local['nombre']), 'deport') !== false) $icono = "⚽";
                                                    echo $icono;
                                                    ?>
                                                </span>
                                            </div>
                                            <h5 class="card-title"><?php echo $local['nombre']; ?></h5>
                                            <p class="card-text"><?php echo $local['descripcion'] ?: 'Descubre nuestras ofertas especiales'; ?></p>
                                            <div class="mb-3">
                                                <span class="badge bg-primary">Código: <?php echo $local['codigo_local']; ?></span>
                                            </div>
                                            <?php if (!isset($_SESSION['user_id'])): ?>
                                                <div class="alert alert-warning mt-3">
                                                    <small>🔒 Regístrate para ver promociones exclusivas</small>
                                                </div>
                                            <?php else: ?>
                                                <small class="text-success">✅ Promociones disponibles</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                        
                        <?php
                            $locales_count++;
                            if ($locales_count % $locales_per_slide == 0 || $locales_count == $total_locales):
                        ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php endwhile; ?>
                    </div>
                    
                    <!-- Controles del carrusel -->
                    <?php if ($total_locales > $locales_per_slide): ?>
                    <button class="carousel-control-prev" type="button" data-bs-target="#localesCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Anterior</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#localesCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Siguiente</span>
                    </button>
                    <?php endif; ?>
                </div>
                
                <!-- Indicadores del carrusel -->
                <?php if ($total_locales > $locales_per_slide): ?>
                <div class="text-center mt-3">
                    <small class="text-muted">
                        Desliza o usa las flechas para ver más locales 
                        (<?php echo ceil($total_locales / $locales_per_slide); ?> páginas)
                    </small>
                </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="col-12 text-center">
                    <div class="alert alert-info">
                        <h5>Próximamente más locales...</h5>
                        <p class="mb-0">Estamos trabajando para traerte la mejor experiencia de compras.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Sección: Promociones Públicas -->
    <section class="py-5 bg-light" id="promociones">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col">
                    <h2>🎁 Promociones Destacadas</h2>
                    <p class="text-muted">Algunas de las ofertas disponibles en nuestro shopping</p>
                </div>
            </div>
            <div class="row">
                <?php if (isset($promociones_generales) && $promociones_generales && $promociones_generales->num_rows > 0): ?>
                    <?php while($promo = $promociones_generales->fetch_assoc()): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card card-hover h-100">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $promo['textoPromo']; ?></h5>
                                <p class="card-text">
                                    <strong>Local:</strong> <?php echo $promo['local_nombre']; ?><br>
                                    <strong>Válida hasta:</strong> <?php echo date('d/m/Y', strtotime($promo['fechaHastaPromo'])); ?>
                                </p>
                                <div class="mb-2">
                                    <span class="badge bg-info"><?php echo $promo['categoriaCliente']; ?></span>
                                    <span class="badge bg-secondary"><?php echo $promo['diasSemana']; ?></span>
                                </div>
                                <?php if (!isset($_SESSION['user_id'])): ?>
                                    <div class="alert alert-warning mt-3">
                                        <small>🔒 Regístrate para acceder a esta promoción y muchas más</small>
                                    </div>
                                <?php else: ?>
                                    <small class="text-success">✅ Disponible para tu categoría</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12 text-center">
                        <div class="alert alert-info">
                            <h5>No hay promociones públicas en este momento</h5>
                            <p class="mb-0">Regístrate para acceder a ofertas exclusivas</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (!isset($_SESSION['user_id'])): ?>
            <div class="text-center mt-4">
                <div class="alert alert-info">
                    <h5>🔓 Desbloquea Todas las Promociones</h5>
                    <p>Regístrate gratis y accede a promociones exclusivas según tu categoría de cliente</p>
                    <a href="register.php" class="btn btn-success">Ver Todas las Promociones</a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Sección: Novedades -->
    <section class="py-5" id="novedades">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col">
                    <h2>📢 Novedades del Shopping</h2>
                    <p class="text-muted">Mantente informado de las últimas noticias</p>
                </div>
            </div>
            <div class="row">
                <?php if (isset($novedades_publicas) && $novedades_publicas->num_rows > 0): ?>
                    <?php while($novedad = $novedades_publicas->fetch_assoc()): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card card-hover h-100">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $novedad['titulo']; ?></h5>
                                <?php if (!empty($novedad['descripcion'])): ?>
                                    <p class="card-text"><?php echo $novedad['descripcion']; ?></p>
                                <?php endif; ?>
                                <p class="card-text">
                                    <small class="text-muted">
                                        Válida: <?php echo date('d/m/Y', strtotime($novedad['fecha_inicio'])); ?> 
                                        al <?php echo date('d/m/Y', strtotime($novedad['fecha_fin'])); ?>
                                    </small>
                                </p>
                                <span class="badge bg-<?php 
                                    echo $novedad['categoria_objetivo'] == 'Premium' ? 'danger' : 
                                         ($novedad['categoria_objetivo'] == 'Medium' ? 'warning' : 'info'); 
                                ?>">
                                    Para: <?php echo $novedad['categoria_objetivo']; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12 text-center">
                        <p class="text-muted">No hay novedades en este momento.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Sección: Call to Action -->
    <section class="py-5 bg-primary text-white" id="registro">
        <div class="container text-center">
            <h2>¿Listo para Empezar a Ahorrar?</h2>
            <p class="lead">Únete a nuestra comunidad de clientes y disfruta de beneficios exclusivos</p>
            <?php if (!isset($_SESSION['user_id'])): ?>
                <div class="mt-4">
                    <a href="register.php" class="btn btn-light btn-lg me-3">Registrarse Gratis</a>
                    <a href="login.php" class="btn btn-outline-light btn-lg">Iniciar Sesión</a>
                </div>
            <?php else: ?>
                <div class="mt-4">
                    <a href="dashboard.php" class="btn btn-success btn-lg">Ir a Mi Panel</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4" id="contacto">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>🛍️ Stella Shopping Rosario</h5>
                    <p>El mejor shopping de la ciudad con las promociones más exclusivas</p>
                </div>
                <div class="col-md-3">
                    <h6>Contacto</h6>
                    <p>📧 info@stellashopping.com<br>📞 (341) 03-5004</p>
                </div>
                <div class="col-md-3">
                    <h6>Horarios</h6>
                    <p>Lunes a Sábado: 10:00 - 22:00<br>Domingos: 12:00 - 20:00</p>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p>&copy; 2024 Shopping Rosario. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>