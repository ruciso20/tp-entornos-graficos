<?php
session_start();

$locales_activos = [];
$novedades_publicas = [];
$promociones_destacadas = [];

try {
    include("config/db.php");

    // obtener locales activos
    $locales_activos = $conn->query("SELECT * FROM locales WHERE estado='aprobado' ORDER BY nombre");

    // obtener novedades
    $novedades_publicas = $conn->query("
        SELECT * FROM novedades 
        WHERE fecha_fin >= CURDATE() 
        AND estado = 'activa'
        ORDER BY fecha_inicio DESC 
        LIMIT 3
    ");

    // obtener promociones destacadas según si el usuario está logueado o no
    $promociones_destacadas = $conn->query("
        SELECT p.*, l.nombre as local_nombre 
        FROM promociones p 
        LEFT JOIN locales l ON p.local_id = l.id 
        WHERE p.estado = 'aprobada' 
        AND p.fecha_fin >= CURDATE()
        AND l.estado = 'aprobado'
        ORDER BY 
            CASE 
                WHEN p.categoria_minima = 'Premium' THEN 1
                WHEN p.categoria_minima = 'Medium' THEN 2
                ELSE 3
            END,
            p.fecha_inicio DESC 
        LIMIT 12
    ");
} catch (Exception $e) {
    // si hay error, continuamos sin datos
    error_log("Error en index.php: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Stella Shopping - Ofertas y Promociones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Para iconos y demas -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        /* estilos para la sección hero */
        .hero-section {
            padding-top: 40px 0 60px;
            padding-bottom: 3rem;
            background-color: #f8f9fa;
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

        /* parte de newsletter */
        .newsletter-content {
            padding: 2rem 0;
        }

        .feature-item {
            padding: 1.5rem 1rem;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.1);
            transition: transform 0.3s ease;
            height: 100%;
        }

        .feature-item:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.15);
        }

        @media (max-width: 768px) {
            .feature-item {
                margin-bottom: 1rem;
            }
        }

        /* esto para las cards flotantes del hero */
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

        /* para hacerlo responsive */
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

        @media (max-width: 576px) {
            .card-title {
                font-size: 1.1rem;
            }

            .card-text {
                font-size: 0.9rem;
            }

            .btn-lg {
                padding: 0.75rem 1.5rem;
                font-size: 1rem;
            }
        }

        /* para los locales*/
        .local-card-item {
            transition: all 0.3s ease;
            border: none;
            border-radius: 12px;
        }

        .local-card-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .categoria-badge {
            font-size: 0.7em;
            text-transform: capitalize;
        }

        .local-icon {
            transition: transform 0.3s ease;
        }

        .local-card-item:hover .local-icon {
            transform: scale(1.1);
        }

        #searchLocales:focus,
        #filterCategoria:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }

        .local-imagen-container {
            overflow: hidden;
        }

        .local-imagen {
            transition: transform 0.3s ease;
        }

        .local-card-item:hover .local-imagen {
            transform: scale(1.05);
        }

        .local-imagen-default {
            transition: all 0.3s ease;
        }

        /* para cuando pasamos el mouse x arriba */
        .local-card-item:hover .local-imagen-default {
            transform: scale(1.05);
            opacity: 0.9;
        }

        .local-imagen,
        .local-imagen-default {
            height: 200px;
            object-fit: cover;
        }

        /* Para que sea responsive las imagenes de locales, y no pasarnos con la altura */
        @media (max-width: 768px) {

            .local-imagen,
            .local-imagen-default {
                height: 150px;
            }
        }
    </style>
</head>

<body>
    <!-- navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">🛍️ Stella Shopping Rosario</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="#promociones">Promociones</a></li>
                    <li class="nav-item"><a class="nav-link" href="#novedades">Novedades</a></li>
                    <li class="nav-item"><a class="nav-link" href="#locales">Locales</a></li>
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


    <main class="flex-grow-1">
        <?php include('secciones/hero-section.php'); ?>

        <?php include('secciones/promociones.php'); ?>

        <?php include('secciones/novedades.php'); ?>

        <?php include('secciones/beneficios.php'); ?>

        <?php include('secciones/locales.php'); ?>

        <?php include('secciones/newsletter.php'); ?>

        <?php include('secciones/contacto.php'); ?>
    </main>
    <!-- footer -->
    <?php include('footer.php'); ?>

    <!-- bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // smooth scroll para los enlaces internos
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // manejo del formulario de contacto
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            alert('¡Mensaje enviado! Te contactaremos pronto.');
            this.reset();
        });
    </script>


    <script>
        // Funcionalidad para la busqueda
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchLocales');
            const localCards = document.querySelectorAll('.local-card');
            const resultCount = document.getElementById('countNumber');

            function filterLocales() {
                const searchTerm = searchInput.value.toLowerCase();
                let visibleCount = 0;

                localCards.forEach(card => {
                    const nombre = card.getAttribute('data-nombre');
                    const descripcion = card.getAttribute('data-descripcion');

                    const matchesSearch = nombre.includes(searchTerm) || descripcion.includes(searchTerm);

                    if (matchesSearch) {
                        card.style.display = 'block';
                        visibleCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                resultCount.textContent = visibleCount;
            }

            searchInput.addEventListener('input', filterLocales);
        });
    </script>

</body>

</html>