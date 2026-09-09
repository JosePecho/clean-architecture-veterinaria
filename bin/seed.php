<?php

declare(strict_types=1);

/**
 * Carga los datos iniciales de la app web en los archivos data/*.txt.
 * Reinicia por completo el estado (borra citas y notificaciones).
 *
 *   php bin/seed.php
 */

require __DIR__ . '/autoload.php';

use Vetcare\Dominio\Modelo\Dueno;
use Vetcare\Dominio\Modelo\Mascota;
use Vetcare\Dominio\Modelo\Veterinario;
use Vetcare\Dominio\ValueObject\Email;
use Vetcare\Dominio\ValueObject\Especie;
use Vetcare\Dominio\ValueObject\Telefono;
use Vetcare\Infraestructura\Persistencia\Archivo\DuenoArchivoRepository;
use Vetcare\Infraestructura\Persistencia\Archivo\MascotaArchivoRepository;
use Vetcare\Infraestructura\Persistencia\Archivo\VeterinarioArchivoRepository;

date_default_timezone_set('America/Lima');

$dirDatos = __DIR__ . '/../data';
if (!is_dir($dirDatos)) {
    mkdir($dirDatos, 0777, true);
}

foreach (['duenos.txt', 'mascotas.txt', 'veterinarios.txt', 'citas.txt', 'notificaciones.txt'] as $archivo) {
    $ruta = $dirDatos . '/' . $archivo;
    if (is_file($ruta)) {
        unlink($ruta);
    }
}

$duenos = new DuenoArchivoRepository($dirDatos);
$mascotas = new MascotaArchivoRepository($dirDatos);
$veterinarios = new VeterinarioArchivoRepository($dirDatos);

$duenos->guardar(new Dueno('D-1', 'Ana Torres', new Telefono('987654321'), new Email('ana.torres@example.com')));
$duenos->guardar(new Dueno('D-2', 'Luis Vega', new Telefono('955112233'), new Email('luis.vega@example.com')));

$mascotas->guardar(new Mascota('M-1', 'Firulais', Especie::PERRO, new DateTimeImmutable('2019-05-01'), 'D-1'));
$mascotas->guardar(new Mascota('M-2', 'Piolin', Especie::AVE, new DateTimeImmutable('2026-04-01'), 'D-1'));
$mascotas->guardar(new Mascota('M-3', 'Michi', Especie::GATO, new DateTimeImmutable('2022-02-15'), 'D-2'));

$veterinarios->guardar(new Veterinario('V-1', 'Dra. Rojas', [Especie::PERRO, Especie::GATO]));
$veterinarios->guardar(new Veterinario('V-2', 'Dr. Salas', [Especie::AVE, Especie::REPTIL, Especie::ROEDOR]));

echo 'Datos iniciales escritos en ' . $dirDatos . DIRECTORY_SEPARATOR . '*.txt' . PHP_EOL;
echo 'Ahora: php -S localhost:8000 -t public' . PHP_EOL;
