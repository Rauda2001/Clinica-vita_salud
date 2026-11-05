<?php
$subcarpetas = [
    'config',
    'controllers',
    'models',
    'views/layouts',
    'views/auth',
    'views/admin',
    'views/enfermera',
    'views/medico',
    'public/css',
    'public/js'
];

foreach ($subcarpetas as $carpeta) {
    $ruta = __DIR__ . DIRECTORY_SEPARATOR . $carpeta;
    if (!is_dir($ruta)) {
        mkdir($ruta, 0755, true);
        echo "📁 Carpeta Creada con éxito: $carpeta <br>";
    }
}
echo "<b>Estructura de directorios lista para hospedar el código.</b>";