<?php
// download_images.php - CORREGIDO
echo "=== DESCARGADOR DE IMÁGENES PARA LOCALES ===\n\n";

// Verificar funciones
if (!ini_get('allow_url_fopen')) {
  echo "❌ allow_url_fopen está deshabilitado\n";
  echo "📋 Usando método alternativo...\n";
}

$image_urls = [
  // URLs de Picsum (funcionan siempre)
  'https://picsum.photos/400/300?random=1',
  'https://picsum.photos/400/300?random=2',
  'https://picsum.photos/400/300?random=3',
  'https://picsum.photos/400/300?random=4',
  'https://picsum.photos/400/300?random=5',
  'https://picsum.photos/400/300?random=6',
  'https://picsum.photos/400/300?random=7',
  'https://picsum.photos/400/300?random=8',
  'https://picsum.photos/400/300?random=9',
  'https://picsum.photos/400/300?random=10',
  'https://picsum.photos/400/300?random=11',
  'https://picsum.photos/400/300?random=12',
  'https://picsum.photos/400/300?random=13',
  'https://picsum.photos/400/300?random=14',
  'https://picsum.photos/400/300?random=15',
  'https://picsum.photos/400/300?random=16',
  'https://picsum.photos/400/300?random=17',
  'https://picsum.photos/400/300?random=18',
  'https://picsum.photos/400/300?random=19',
  'https://picsum.photos/400/300?random=20',
  'https://picsum.photos/400/300?random=21',
  'https://picsum.photos/400/300?random=22',
  'https://picsum.photos/400/300?random=23',
  'https://picsum.photos/400/300?random=24',
  'https://picsum.photos/400/300?random=25',
  'https://picsum.photos/400/300?random=26',
  'https://picsum.photos/400/300?random=27',
  'https://picsum.photos/400/300?random=28',
  'https://picsum.photos/400/300?random=29',
  'https://picsum.photos/400/300?random=30'
];

// Crear directorio
$local_dir = 'assets/images/locales/';
if (!is_dir($local_dir)) {
  if (!mkdir($local_dir, 0755, true)) {
    echo "❌ No se pudo crear: $local_dir\n";
    echo "📋 Creando estructura manualmente...\n";
    // Intentar crear paso a paso
    if (!mkdir('assets', 0755)) die("No se pudo crear assets/");
    if (!mkdir('assets/images', 0755)) die("No se pudo crear assets/images/");
    if (!mkdir($local_dir, 0755)) die("No se pudo crear $local_dir");
  }
}

echo "📁 Directorio: $local_dir\n";
echo "📥 Descargando 30 imágenes...\n\n";

$descargadas = 0;

foreach ($image_urls as $index => $url) {
  $image_number = $index + 1;
  $filename = "local{$image_number}.jpg";
  $filepath = $local_dir . $filename;

  echo "{$image_number}/30: ";

  // Método 1: file_get_contents
  $image_data = @file_get_contents($url);

  if ($image_data === false) {
    // Método 2: cURL alternativo
    $image_data = downloadWithCurl($url);
  }

  if ($image_data !== false && strlen($image_data) > 5000) {
    file_put_contents($filepath, $image_data);
    echo "✅\n";
    $descargadas++;
  } else {
    echo "❌ (usando placeholder)\n";
    // Crear imagen placeholder básica
    createPlaceholderImage($filepath, $image_number);
    $descargadas++;
  }

  sleep(1); // Pausa
}

echo "\n✅ Completado: {$descargadas}/30 imágenes\n";
echo "📁 Las imágenes están en: $local_dir\n";

// Función alternativa con cURL
function downloadWithCurl($url)
{
  if (!function_exists('curl_init')) return false;

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
  curl_setopt($ch, CURLOPT_TIMEOUT, 10);
  curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
  $data = curl_exec($ch);
  curl_close($ch);

  return $data;
}

// Crear imagen placeholder si falla la descarga
function createPlaceholderImage($filepath, $number)
{
  $image = imagecreate(400, 300);

  // Colores
  $background = imagecolorallocate($image, rand(200, 255), rand(200, 255), rand(200, 255));
  $text_color = imagecolorallocate($image, rand(0, 100), rand(0, 100), rand(0, 100));
  $accent = imagecolorallocate($image, rand(0, 150), rand(0, 150), rand(0, 150));

  // Rectángulo de acento
  imagefilledrectangle($image, 50, 100, 350, 200, $accent);

  // Texto
  imagestring($image, 5, 150, 130, "LOCAL $number", $text_color);
  imagestring($image, 3, 130, 160, "Stella Shopping", $text_color);

  imagejpeg($image, $filepath, 85);
  imagedestroy($image);
}
