<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'cliente') {
    header("Location: ../login.php");
    exit;
}

include("../config/db.php");

//sincronizar la categoria actual del cliente

if (isset($_SESSION['user_id']) && $_SESSION['rol'] == 'cliente') {
    $cat_query = $conn->prepare("SELECT categoria_cliente FROM usuarios WHERE id = ?");
    $cat_query->bind_param("i", $_SESSION['user_id']);
    $cat_query->execute();
    $cat_result = $cat_query->get_result();
    $usuario_data = $cat_result->fetch_assoc();

    if ($usuario_data) {
        $_SESSION['categoria_cliente'] = $usuario_data['categoria_cliente'];
    }
}

// Obtener promociones disponibles para el cliente según su categoría
$categoria_cliente = $_SESSION['categoria'];
$usuario_id = $_SESSION['user_id'];

// Usar los nombres correctos de las columnas según tu estructura
$promociones_query = $conn->prepare("
    SELECT p.*, l.nombre as local_nombre
    FROM promociones p 
    JOIN locales l ON p.local_id = l.id 
    WHERE p.estado = 'aprobada' 
    AND p.fecha_fin >= CURDATE()
    AND p.fecha_inicio <= CURDATE()
    AND (
        p.categoria_minima = ? 
        OR p.categoria_minima = 'inicial'
        OR (? = 'premium' AND p.categoria_minima IN ('inicial', 'medium', 'premium'))
        OR (? = 'medium' AND p.categoria_minima IN ('inicial', 'medium'))
    )
    ORDER BY p.fecha_inicio DESC
");

$promociones_query->bind_param("sss", $categoria_cliente, $categoria_cliente, $categoria_cliente);
$promociones_query->execute();
$promociones = $promociones_query->get_result();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Promociones - Cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .promo-card {
            transition: transform 0.2s;
            border: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .promo-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        }

        .exclusiva {
            border-left: 4px solid #ffc107 !important;
        }

        .categoria-badge {
            font-size: 0.8em;
        }

        /* Estilos para la impresión */
        @media print {
            .no-print {
                display: none !important;
            }

            .comprobante-content {
                display: block !important;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <div class="navbar-brand">
                <a class="navbar-brand fw-bold" href="../index.php">🛍️
                    <span class="ms-1">Stella Shopping Rosario</span></a>
                <span class="navbar-text text-light">Usar Promociones</span>
            </div>
            <div class="d-flex">
                <a href="../dashboard.php" class="btn btn-outline-light">Volver</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4 no-print">
        <h2>Promociones Disponibles</h2>
        <p class="text-muted">Estas son las promociones a las que tienes acceso según tu categoría <strong><?php echo ucfirst($categoria_cliente); ?></strong></p>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['success']; ?>
                <?php unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php echo $_SESSION['error']; ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <?php if ($promociones->num_rows > 0): ?>
                <?php while ($promo = $promociones->fetch_assoc()):
                    $es_exclusiva = $promo['categoria_minima'] == $categoria_cliente && $categoria_cliente != 'inicial';
                    $dias_array = explode(",", $promo['dias_validos']);
                    $dias_semana = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
                    $dias_display = array_map(function ($dia) use ($dias_semana) {
                        return isset($dias_semana[$dia]) ? substr($dias_semana[$dia], 0, 3) : $dia;
                    }, $dias_array);
                ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 promo-card <?php echo $es_exclusiva ? 'exclusiva' : ''; ?>">
                            <div class="card-body d-flex flex-column">
                                <?php if ($es_exclusiva): ?>
                                    <span class="badge bg-warning mb-2">⭐ Exclusiva</span>
                                <?php endif; ?>

                                <h5 class="card-title"><?php echo htmlspecialchars($promo['titulo']); ?></h5>
                                <p class="card-text flex-grow-1">
                                    <small class="text-muted"><?php echo htmlspecialchars($promo['descripcion']); ?></small>
                                </p>

                                <div class="mt-auto">
                                    <p class="mb-1">
                                        <strong>🏪 Local:</strong> <?php echo htmlspecialchars($promo['local_nombre']); ?>
                                    </p>
                                    <p class="mb-1">
                                        <strong>📅 Válida hasta:</strong> <?php echo date('d/m/Y', strtotime($promo['fecha_fin'])); ?>
                                    </p>
                                    <p class="mb-2">
                                        <strong>📆 Días:</strong>
                                        <span class="badge bg-light text-dark"><?php echo implode(", ", $dias_display); ?></span>
                                    </p>

                                    <div class="mb-3">
                                        <span class="badge categoria-badge bg-<?php
                                                                                echo $promo['categoria_minima'] == 'premium' ? 'danger' : ($promo['categoria_minima'] == 'medium' ? 'warning' : 'info');
                                                                                ?>">
                                            Para: <?php echo ucfirst($promo['categoria_minima']); ?>
                                        </span>
                                        <?php if ($promo['categoria_minima'] == $categoria_cliente): ?>
                                            <span class="badge categoria-badge bg-success">✅ Disponible</span>
                                        <?php endif; ?>
                                    </div>

                                    <button class="btn btn-primary w-100" onclick="usarPromocion(<?php echo $promo['id']; ?>, '<?php echo htmlspecialchars($promo['local_nombre']); ?>')">
                                        🎯 Usar Promoción
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info text-center py-5">
                        <h4>No hay promociones disponibles</h4>
                        <p class="mb-0">Vuelve más tarde para descubrir nuevas ofertas exclusivas.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function usarPromocion(promoId, localNombre) {
            if (confirm(`¿Quieres usar esta promoción en ${localNombre}?`)) {
                // Redirigir a la página de uso de promoción
                window.location.href = `usar_promocion.php?promo_id=${promoId}`;
            }
        }

        // Creamos la funcion para la impresion de PDF 
        function imprimirComprobante(usoId, promoId, localNombre, promoTitulo, promoDescripcion, fechaFin, diasValidos, categoria) {
            // Crear ventana de impresión
            const printWindow = window.open('', '_blank', 'width=800,height=600');

            // Usar concatenación de strings en lugar de template literal para evitar problemas de terminación
            var comprobanteHTML = '';
            comprobanteHTML += '<!DOCTYPE html>';
            comprobanteHTML += '<html>';
            comprobanteHTML += '<head>';
            comprobanteHTML += '    <title>Comprobante de Promoción - Stella Shopping</title>';
            comprobanteHTML += '    <style>';
            comprobanteHTML += '        body { font-family: Arial, sans-serif; margin: 20px; background: white; }';
            comprobanteHTML += '        .header { background: #2c3e50; color: white; padding: 20px; text-align: center; border-radius: 10px; margin-bottom: 20px; }';
            comprobanteHTML += '        .logo { font-size: 28px; font-weight: bold; margin-bottom: 10px; }';
            comprobanteHTML += '        .comprobante-info { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #3498db; }';
            comprobanteHTML += '        .promo-details { border: 2px solid #3498db; padding: 20px; border-radius: 8px; margin-bottom: 20px; }';
            comprobanteHTML += '        .qr-section { text-align: center; margin: 25px 0; padding: 20px; border: 2px dashed #bdc3c7; border-radius: 10px; }';
            comprobanteHTML += '        .footer { text-align: center; margin-top: 30px; color: #7f8c8d; font-size: 12px; border-top: 1px solid #ecf0f1; padding-top: 15px; }';
            comprobanteHTML += '        .badge { background: #e74c3c; color: white; padding: 5px 10px; border-radius: 3px; font-size: 12px; font-weight: bold; }';
            comprobanteHTML += '        .instructions { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }';
            comprobanteHTML += '        table { width: 100%; border-collapse: collapse; }';
            comprobanteHTML += '        table td { padding: 8px; border-bottom: 1px solid #ecf0f1; }';
            comprobanteHTML += "        .code-display { font-family: 'Courier New', monospace; font-size: 18px; background: #34495e; color: white; padding: 15px; border-radius: 5px; letter-spacing: 2px; margin: 10px 0; }";
            comprobanteHTML += '        @media print { body { margin: 0; } .header { margin: 0 0 20px 0; border-radius: 0; } }';
            comprobanteHTML += '    </style>';
            comprobanteHTML += '</head>';
            comprobanteHTML += '<body>';
            comprobanteHTML += '    <div class="header">';
            comprobanteHTML += '        <div class="logo">🛍️ STELLA SHOPPING</div>';
            comprobanteHTML += '        <h2>COMPROBANTE DE PROMOCIÓN</h2>';
            comprobanteHTML += '    </div>';
            comprobanteHTML += '    <div class="comprobante-info">';
            comprobanteHTML += '        <table>';
            comprobanteHTML += '            <tr>';
            comprobanteHTML += '                <td width="50%"><strong>N° Comprobante:</strong> ' + usoId + '</td>';
            comprobanteHTML += '                <td width="50%"><strong>Fecha:</strong> ' + new Date().toLocaleString('es-AR') + '</td>';
            comprobanteHTML += '            </tr>';
            comprobanteHTML += '            <tr>';
            comprobanteHTML += '                <td><strong>Código:</strong> PROMO-' + promoId + '-' + usoId + '</td>';
            comprobanteHTML += '                <td><strong>Estado:</strong> <span class="badge">PENDIENTE</span></td>';
            comprobanteHTML += '            </tr>';
            comprobanteHTML += '        </table>';
            comprobanteHTML += '    </div>';
            comprobanteHTML += '    <div class="promo-details">';
            comprobanteHTML += '        <h3 style="color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 15px; margin-bottom: 20px;">' + promoTitulo + '</h3>';
            comprobanteHTML += '        <table>';
            comprobanteHTML += '            <tr>';
            comprobanteHTML += '                <td width="30%"><strong>🏪 Local:</strong></td>';
            comprobanteHTML += '                <td width="70%"><strong>' + localNombre + '</strong></td>';
            comprobanteHTML += '            </tr>';
            comprobanteHTML += '            <tr>';
            comprobanteHTML += '                <td><strong>📅 Válida hasta:</strong></td>';
            comprobanteHTML += '                <td>' + fechaFin + '</td>';
            comprobanteHTML += '            </tr>';
            comprobanteHTML += '            <tr>';
            comprobanteHTML += '                <td><strong>📆 Días válidos:</strong></td>';
            comprobanteHTML += '                <td>' + diasValidos + '</td>';
            comprobanteHTML += '            </tr>';
            comprobanteHTML += '            <tr>';
            comprobanteHTML += '                <td><strong>🎯 Categoría:</strong></td>';
            comprobanteHTML += '                <td>' + (categoria.charAt(0).toUpperCase() + categoria.slice(1)) + '</td>';
            comprobanteHTML += '            </tr>';
            comprobanteHTML += '        </table>';
            comprobanteHTML += '        <div style="background: #ecf0f1; padding: 15px; border-radius: 5px; margin-top: 15px;">';
            comprobanteHTML += '            <strong>📝 Descripción:</strong><br>' + promoDescripcion;
            comprobanteHTML += '        </div>';
            comprobanteHTML += '    </div>';
            comprobanteHTML += '    <div class="qr-section">';
            comprobanteHTML += '        <div style="margin-bottom: 15px; font-size: 16px; font-weight: bold; color: #2c3e50;">CÓDIGO DE USO</div>';
            comprobanteHTML += '        <div class="code-display">PROMO-' + promoId + '-' + usoId + '</div>';
            comprobanteHTML += '        <div style="margin-top: 10px; font-size: 14px; color: #7f8c8d;">Presenta este código en el local para validar tu promoción</div>';
            comprobanteHTML += '    </div>';
            comprobanteHTML += '    <div class="instructions">';
            comprobanteHTML += '        <strong>💡 INSTRUCCIONES:</strong><br>';
            comprobanteHTML += '        1. Presenta este comprobante en <strong>' + localNombre + '</strong><br>';
            comprobanteHTML += '        2. Muestra el código de uso al personal del local<br>';
            comprobanteHTML += '        3. El dueño validará y aceptará tu promoción<br>';
            comprobanteHTML += '        4. ¡Disfruta de tu descuento!';
            comprobanteHTML += '    </div>';
            comprobanteHTML += '    <div class="footer">';
            comprobanteHTML += '        <strong>Stella Shopping - Sistema de Promociones</strong><br>';
            comprobanteHTML += '        Av. San Martín 1234, Rosario • Tel: (341) 123-4567<br>';
            comprobanteHTML += '        Comprobante generado automáticamente • ' + new Date().toLocaleString('es-AR');
            comprobanteHTML += '    </div>';
            comprobanteHTML += '    <script>';
            comprobanteHTML += '        window.onload = function() { window.print(); setTimeout(function(){ window.close(); }, 1000); };';
            comprobanteHTML += '    </' + 'script>';
            comprobanteHTML += '</body>';
            comprobanteHTML += '</html>';

            printWindow.document.open();
            printWindow.document.write(comprobanteHTML);
            printWindow.document.close();
        }
    </script>
    <!-- footer -->
    <?php include('../footer.php'); ?>
    <!-- bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>