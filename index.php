<?php
session_start();

// Inicializar variables para evitar errores
$locales_activos = [];
$novedades_publicas = [];
$promociones_destacadas = [];

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

    // Obtener promociones destacadas según si el usuario está logueado o no
    if (isset($_SESSION['user_id']) && isset($_SESSION['categoria'])) {
        $categoria = $_SESSION['categoria'];
        $promociones_destacadas = $conn->query("
            SELECT p.*, l.nombre as local_nombre 
            FROM promociones p 
            LEFT JOIN locales l ON p.local_id = l.id 
            WHERE p.estado = 'aprobada' 
            AND p.fecha_fin >= CURDATE()
            AND (
                p.categoria_minima = '$categoria' 
                OR p.categoria_minima = 'Inicial'
                OR ('$categoria' = 'Premium' AND p.categoria_minima IN ('Inicial', 'Medium', 'Premium'))
                OR ('$categoria' = 'Medium' AND p.categoria_minima IN ('Inicial', 'Medium'))
            )
            ORDER BY 
                CASE 
                    WHEN p.categoria_minima = 'Premium' THEN 1
                    WHEN p.categoria_minima = 'Medium' THEN 2
                    ELSE 3
                END,
                p.fecha_inicio DESC 
            LIMIT 8
        ");
    } else {
        $promociones_destacadas = $conn->query("
            SELECT p.*, l.nombre as local_nombre 
            FROM promociones p 
            LEFT JOIN locales l ON p.local_id = l.id 
            WHERE p.estado = 'aprobada' 
            AND p.fecha_fin >= CURDATE()
            AND p.categoria_minima = 'Inicial'
            ORDER BY p.fecha_inicio DESC 
            LIMIT 8
        ");
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
    <title>Stella Shopping - Ofertas y Promociones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .hero-section {
            padding-top: 40px 0 60px;
            /* equivalente a py-5 */
            padding-bottom: 3rem;
            /* equivalente a py-5 */
            background-color: #f8f9fa;
            /* equivalente a bg-light */
        }

        .hero-content {
            padding: 40px 0;
        }

        .hero-badge .badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
        }

        .hero-title {
            color: #333;
            line-height: 1.2;
            margin-bottom: 1rem;
        }

        .hero-subtitle {
            color: #666;
            line-height: 1.6;
        }

        .hero-stats {
            margin: 2rem 0;
        }

        .stat-number {
            font-size: 1.5rem;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.8rem;
            color: #888;
        }

        .hero-actions .btn {
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .hero-actions .btn-primary {
            box-shadow: 0 4px 15px rgba(13, 110, 253, 0.3);
        }

        .hero-actions .btn:hover {
            transform: translateY(-2px);
        }

        .trust-badges small {
            font-size: 0.8rem;
        }

        /* Cards flotantes al lado */
        .hero-cards {
            height: 400px;
            position: relative;
        }

        .floating-card {
            position: absolute;
            animation: float 3s ease-in-out infinite;
        }

        .floating-card .card {
            border-radius: 15px;
            min-width: 140px;
            transition: all 0.3s ease;
        }

        .card-1 {
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }

        .card-2 {
            top: 30%;
            right: 15%;
            animation-delay: 1s;
        }

        .card-3 {
            bottom: 10%;
            left: 20%;
            animation-delay: 2s;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero-section {
                padding: 60px 0;
            }

            .hero-title {
                font-size: 2rem;
            }

            .hero-cards {
                height: 300px;
                margin-top: 40px;
            }

            .floating-card {
                position: relative;
                margin-bottom: 20px;
                animation: none;
            }

            .card-1,
            .card-2,
            .card-3 {
                position: relative;
                top: auto;
                left: auto;
                right: auto;
                bottom: auto;
            }
        }

        /* Carousel de Locales Simple */
        .local-card {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            transition: all 0.3s ease;
            height: 100%;
        }

        .local-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .carousel-control-prev,
        .carousel-control-next {
            width: 40px;
            height: 40px;
            background: #0d6efd;
            border-radius: 50%;
            top: 50%;
            transform: translateY(-50%);
            opacity: 0.8;
        }

        .carousel-control-prev {
            left: -20px;
        }

        .carousel-control-next {
            right: -20px;
        }

        .carousel-control-prev:hover,
        .carousel-control-next:hover {
            opacity: 1;
        }

        /* Responsive */
        @media (max-width: 768px) {

            .carousel-control-prev,
            .carousel-control-next {
                display: none;
            }

            .local-card {
                margin-bottom: 1rem;
            }
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <strong><i class="fas fa-shopping-bag"></i> Stella Shopping</strong>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="#promociones">Promociones</a></li>
                    <li class="nav-item"><a class="nav-link" href="#locales">Locales</a></li>
                    <li class="nav-item"><a class="nav-link" href="#novedades">Novedades</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contacto">Contacto</a></li>
                </ul>
                <div class="navbar-nav">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Mi Cuenta</a>
                        <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
                    <?php else: ?>
                        <a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt"></i> Iniciar Sesión</a>
                        <a class="nav-link" href="register.php"><i class="fas fa-user-plus"></i> Registrarse</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section Simplificada -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <!-- Columna izquierda - Contenido principal -->
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

                        <!-- Stats simplificados -->
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

                        <!-- Botones de acción -->
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

                        <!-- Trust badges simplificados -->
                        <div class="trust-badges">
                            <div class="d-flex flex-wrap gap-3 text-muted">
                                <small><i class="fas fa-shield-alt me-1"></i> Compra segura</small>
                                <small><i class="fas fa-clock me-1"></i> 10:00 - 22:00</small>
                                <small><i class="fas fa-parking me-1"></i> Estacionamiento gratis</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna derecha - Cards flotantes -->
                <div class="col-lg-6">
                    <div class="hero-cards position-relative">
                        <!-- Card 1 -->
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

                        <!-- Card 2 -->
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

                        <!-- Card 3 -->
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

    <!-- SECCIÓN: Promociones Destacadas - CORREGIDA -->
    <section class="py-5 bg-light" id="promociones">
        <div class="container">
            <div class="row text-center mb-4">
                <div class="col">
                    <h2 class="fw-bold">🔥 Promociones Destacadas</h2>
                    <p class="text-muted fs-5">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            Las mejores ofertas para tu categoría <span class="badge bg-info"><?php echo $_SESSION['categoria']; ?></span>
                        <?php else: ?>
                            Ofertas especiales disponibles para todos
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <div class="row">
                <?php if (isset($promociones_destacadas) && $promociones_destacadas->num_rows > 0): ?>
                    <?php while ($promo = $promociones_destacadas->fetch_assoc()):
                        // CORREGIDO: usando categoria_minima en lugar de categoria_cliente
                        $es_premium = $promo['categoria_minima'] == 'Premium';
                        $es_medium = $promo['categoria_minima'] == 'Medium';
                        $es_exclusiva = isset($_SESSION['user_id']) && $promo['categoria_minima'] == $_SESSION['categoria'] && $_SESSION['categoria'] != 'Inicial';
                    ?>
                        <div class="col-lg-3 col-md-6 mb-4">
                            <div class="card card-hover h-100 <?php echo $es_exclusiva ? 'destacada-card' : ''; ?>">
                                <?php if ($es_exclusiva): ?>
                                    <span class="promo-badge badge bg-danger">
                                        <i class="fas fa-crown"></i> Exclusiva
                                    </span>
                                <?php elseif ($es_premium): ?>
                                    <span class="promo-badge badge bg-danger">Premium</span>
                                <?php elseif ($es_medium): ?>
                                    <span class="promo-badge badge bg-warning text-dark">Medium</span>
                                <?php else: ?>
                                    <span class="promo-badge badge bg-info">Inicial</span>
                                <?php endif; ?>

                                <div class="card-body">
                                    <!-- CORREGIDO: usando titulo en lugar de textoPromo -->
                                    <h5 class="card-title"><?php echo htmlspecialchars($promo['titulo']); ?></h5>
                                    <p class="card-text">
                                        <strong><i class="fas fa-store"></i> Local:</strong> <?php echo htmlspecialchars($promo['local_nombre']); ?><br>
                                        <!-- CORREGIDO: usando fecha_fin en lugar de fechaHastaPromo -->
                                        <strong><i class="fas fa-calendar"></i> Válida hasta:</strong> <?php echo date('d/m/Y', strtotime($promo['fecha_fin'])); ?>
                                    </p>
                                    <div class="mb-3">
                                        <!-- CORREGIDO: usando dias_validos en lugar de diasSemana -->
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-calendar-day"></i> <?php echo $promo['dias_validos']; ?>
                                        </span>
                                    </div>

                                    <?php if (!isset($_SESSION['user_id'])): ?>
                                        <div class="alert alert-warning mt-3 mb-0">
                                            <small><i class="fas fa-lock"></i> Regístrate para usar esta promoción</small>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-success mt-3 mb-2">
                                            <small>
                                                <i class="fas fa-check-circle"></i>
                                                <!-- CORREGIDO: usando categoria_minima en lugar de categoria_cliente -->
                                                <?php if ($promo['categoria_minima'] == $_SESSION['categoria']): ?>
                                                    Disponible para tu categoría
                                                <?php else: ?>
                                                    Disponible (categoría inferior)
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                        <!-- CORREGIDO: usando id en lugar de codPromo -->
                                        <button class="btn btn-primary btn-sm w-100" onclick="usarPromocion(<?php echo $promo['id']; ?>)">
                                            <i class="fas fa-shopping-cart"></i> Usar Promoción
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12 text-center">
                        <div class="alert alert-info">
                            <h5><i class="fas fa-info-circle"></i> No hay promociones destacadas en este momento</h5>
                            <p class="mb-0">
                                <?php if (!isset($_SESSION['user_id'])): ?>
                                    Regístrate para acceder a ofertas exclusivas
                                <?php else: ?>
                                    Vuelve más tarde para descubrir nuevas ofertas
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- SECCIÓN: Novedades del Shopping - CORREGIDA -->
    <section class="py-5 bg-light" id="novedades">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col">
                    <h2 class="fw-bold">📢 Novedades del Shopping</h2>
                    <p class="text-muted fs-5">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            Información importante para tu categoría <span class="badge bg-info"><?php echo $_SESSION['categoria']; ?></span>
                        <?php else: ?>
                            Mantente informado de las últimas noticias
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <div class="row">
                <?php if (isset($novedades_publicas) && $novedades_publicas->num_rows > 0): ?>
                    <?php while ($novedad = $novedades_publicas->fetch_assoc()):
                        // CORREGIDO: usando categoria_objetivo (correcto)
                        $es_exclusiva = $novedad['categoria_objetivo'] != 'Inicial';
                    ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card card-hover h-100 novedad-card <?php echo $es_exclusiva ? 'border-warning' : ''; ?>">
                                <div class="card-body">
                                    <?php if ($es_exclusiva): ?>
                                        <span class="badge bg-warning float-end">
                                            <i class="fas fa-star"></i> <?php echo $novedad['categoria_objetivo']; ?>
                                        </span>
                                    <?php endif; ?>

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

    <!-- Sección: Por qué registrarse -->
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
            <?php if (!isset($_SESSION['user_id'])): ?>
                <div class="text-center mt-4">
                    <a href="register.php" class="btn btn-primary btn-lg px-5 py-3">
                        <i class="fas fa-rocket"></i> ¡Quiero Estos Beneficios!
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Sección: Locales -->
    <section class="py-5 bg-light" id="locales">
        <div class="container">
            <div class="row text-center mb-4">
                <div class="col">
                    <h2 class="fw-bold">🏪 Nuestros Locales</h2>
                    <p class="text-muted">Descubre la variedad de locales en nuestro shopping</p>
                </div>
            </div>

            <?php if (isset($locales_activos) && $locales_activos->num_rows > 0): ?>
                <div id="localesCarousel" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <?php
                        $locales_count = 0;
                        $locales_per_slide = 4;
                        $total_locales = $locales_activos->num_rows;

                        $locales_activos->data_seek(0);

                        while ($local = $locales_activos->fetch_assoc()):
                            // Categoría simple
                            $categoria_local = "General";
                            $icono = "🏪";

                            if ($locales_count % $locales_per_slide == 0):
                        ?>
                                <div class="carousel-item <?php echo $locales_count == 0 ? 'active' : ''; ?>">
                                    <div class="row g-3">
                                    <?php endif; ?>

                                    <div class="col-lg-3 col-md-6">
                                        <div class="card local-card h-100">
                                            <div class="card-body text-center">
                                                <div class="mb-3">
                                                    <span style="font-size: 2.5rem;"><?php echo $icono; ?></span>
                                                </div>
                                                <h6 class="card-title fw-bold"><?php echo $local['nombre']; ?></h6>
                                                <p class="card-text small text-muted">
                                                    <?php echo $local['descripcion'] ?: 'Ofertas especiales disponibles'; ?>
                                                </p>
                                                <div class="mb-3">
                                                    <span class="badge bg-primary"><?php echo $local['codigo_local']; ?></span>
                                                    <span class="badge bg-secondary"><?php echo $categoria_local; ?></span>
                                                </div>
                                                <?php if (!isset($_SESSION['user_id'])): ?>
                                                    <small class="text-warning">
                                                        <i class="fas fa-lock"></i> Regístrate para promociones
                                                    </small>
                                                <?php else: ?>
                                                    <small class="text-success">
                                                        <i class="fas fa-check"></i> Promociones disponibles
                                                    </small>
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

                    <!-- Controles simples -->
                    <?php if ($total_locales > $locales_per_slide): ?>
                        <button class="carousel-control-prev" type="button" data-bs-target="#localesCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon"></span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#localesCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon"></span>
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Indicadores simples -->
                <?php if ($total_locales > $locales_per_slide): ?>
                    <div class="text-center mt-3">
                        <?php for ($i = 0; $i < ceil($total_locales / $locales_per_slide); $i++): ?>
                            <button type="button" data-bs-target="#localesCarousel" data-bs-slide-to="<?php echo $i; ?>"
                                class="btn btn-sm <?php echo $i == 0 ? 'btn-primary' : 'btn-outline-primary'; ?> mx-1">
                                <?php echo $i + 1; ?>
                            </button>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="text-center">
                    <div class="alert alert-info">
                        <h5>Próximamente más locales...</h5>
                        <p class="mb-0">Estamos trabajando para traerte la mejor experiencia.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Sección: Contacto Mejorada -->
    <section class="py-5 bg-light" id="contacto">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card contact-form shadow-sm">
                    <div class="card-body p-4">
                        <form id="contactForm">
                            <div class="row text-center mb-5">
                                <div class="col">
                                    <h2 class="fw-bold">📞 Contáctanos</h2>
                                    <p class="text-muted fs-5">¿Tienes preguntas? Estamos aquí para ayudarte</p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="contactName" class="form-label">Nombre Completo</label>
                                    <input type="text" class="form-control" id="contactName" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="contactEmail" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="contactEmail" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="contactSubject" class="form-label">Asunto</label>
                                <input type="text" class="form-control" id="contactSubject" required>
                            </div>
                            <div class="mb-3">
                                <label for="contactMessage" class="form-label">Mensaje</label>
                                <textarea class="form-control" id="contactMessage" rows="5" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                <i class="fas fa-paper-plane me-2"></i> Enviar Mensaje
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer Mejorado -->
    <footer class="bg-dark text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <h5><i class="fas fa-shopping-bag me-2"></i> Stella Shopping </h5>
                    <p class="mt-3">El mejor shopping de la ciudad con las promociones más exclusivas y la mejor experiencia de compras.</p>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6>Enlaces Rápidos</h6>
                    <ul class="list-unstyled">
                        <li><a href="#promociones" class="text-white-50 text-decoration-none">Promociones</a></li>
                        <li><a href="#locales" class="text-white-50 text-decoration-none">Locales</a></li>
                        <li><a href="#novedades" class="text-white-50 text-decoration-none">Novedades</a></li>
                        <li><a href="#contacto" class="text-white-50 text-decoration-none">Contacto</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <h6>Contacto</h6>
                    <p class="mb-2"><i class="fas fa-map-marker-alt me-2"></i> Av. San Martín 1234, Rosario</p>
                    <p class="mb-2"><i class="fas fa-phone me-2"></i> (341) 123-4567</p>
                    <p class="mb-2"><i class="fas fa-envelope me-2"></i> info@stellashopping.com</p>
                </div>
            </div>
            <hr class="my-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0">&copy; 2025 Stella Shopping. Todos los derechos reservados.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="#" class="text-white-50 text-decoration-none me-3">Política de Privacidad</a>
                    <a href="#" class="text-white-50 text-decoration-none">Términos de Servicio</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // CORREGIDO: usando promocionId en lugar de codPromo
        function usarPromocion(promocionId) {
            if (confirm('¿Deseas usar esta promoción?')) {
                // Aquí iría la lógica para usar la promoción
                alert('¡Promoción aplicada! Muestra este código en el local: PROMO-' + promocionId);
            }
        }

        // Smooth scroll para los enlaces internos
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Manejo del formulario de newsletter
        document.getElementById('newsletterForm').addEventListener('submit', function(e) {
            e.preventDefault();
            alert('¡Gracias por suscribirte a nuestro newsletter!');
            this.reset();
        });

        // Manejo del formulario de contacto
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            e.preventDefault();
            alert('¡Mensaje enviado! Te contactaremos pronto.');
            this.reset();
        });
    </script>
</body>

</html>