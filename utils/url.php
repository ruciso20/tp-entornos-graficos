<?php
if (!defined('BASE_URL')) {
  // rutas relativas 
  $config_file = dirname(__DIR__) . '/config/constants.php';

  if (file_exists($config_file)) {
    require_once $config_file;
  } else {
    // Fallback para prod
    define('BASE_URL', (function () {
      $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        ? 'https://'
        : 'http://';

      $domain = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';

      $script_dir = dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php');
      $path = ($script_dir === '/' || $script_dir === '.')
        ? '/'
        : rtrim($script_dir, '/') . '/';

      return $protocol . $domain . $path;
    })());
  }
}

// url global
function smartUrl($path = '')
{
  if (!defined('BASE_URL')) {
    return $path;
  }

  $clean_path = ltrim($path, '/');

  // Para URLs externas , lo traemos como esta
  if (strpos($clean_path, 'http://') === 0 || strpos($clean_path, 'https://') === 0) {
    return $clean_path;
  }

  return rtrim((string)constant('BASE_URL'), '/') . '/' . $clean_path;
}

// para los archivos estaticos (prod)
function asset_url($path)
{
  $version = defined('ASSET_VERSION') ? constant('ASSET_VERSION') : '1.0';
  $url = smartUrl($path);

  // para cachear segun version
  if (defined('ENVIRONMENT') && constant('ENVIRONMENT') === 'production') {
    $separator = (strpos($url, '?') === false) ? '?' : '&';
    $url .= $separator . 'v=' . $version;
  }

  return $url;
}
