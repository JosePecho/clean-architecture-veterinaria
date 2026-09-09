<?php

declare(strict_types=1);

/**
 * Autoloader PSR-4 hecho a mano: PHP nativo, sin Composer.
 * Mapea el prefijo de namespace "Vetcare\" a la carpeta src/.
 */
spl_autoload_register(static function (string $clase): void {
    $prefijo = 'Vetcare\\';
    if (!str_starts_with($clase, $prefijo)) {
        return;
    }

    $relativo = substr($clase, strlen($prefijo));
    $ruta = __DIR__ . '/../src/' . str_replace('\\', '/', $relativo) . '.php';

    if (is_file($ruta)) {
        require $ruta;
    }
});
