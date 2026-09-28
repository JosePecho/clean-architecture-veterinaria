<?php

declare(strict_types=1);

/**
 * ============================================================================
 *  COMPOSITION ROOT + DEMO EJECUTABLE
 * ============================================================================
 *  Este archivo es lo unico "sucio" del proyecto: aqui se conocen las 4 capas
 *  a la vez y se arma el grafo de objetos (inyeccion de dependencias manual).
 *
 *  Ejecutar:  php bin/demo.php
 * ============================================================================
 */

require __DIR__ . '/autoload.php';

use Vetcare\Aplicacion\Servicio\ServicioDeCitas;
use Vetcare\Dominio\CasoDeUso\AgendarCita;
use Vetcare\Dominio\CasoDeUso\AtenderCita;
use Vetcare\Dominio\CasoDeUso\CancelarCita;
use Vetcare\Dominio\CasoDeUso\ConfirmarCita;
use Vetcare\Dominio\CasoDeUso\RegistrarFichaDeMascota;
use Vetcare\Dominio\CasoDeUso\ReprogramarCita;
use Vetcare\Dominio\Modelo\Dueno;
use Vetcare\Dominio\Modelo\Mascota;
use Vetcare\Dominio\Modelo\Veterinario;
use Vetcare\Dominio\Servicio\AgendaDelVeterinario;
use Vetcare\Dominio\Servicio\CalculadoraDeTarifa;
use Vetcare\Dominio\ValueObject\Email;
use Vetcare\Dominio\ValueObject\Especie;
use Vetcare\Dominio\ValueObject\Telefono;
use Vetcare\Infraestructura\Evento\DespachadorEnMemoria;
use Vetcare\Infraestructura\Evento\Listener\AvisarReprogramacion;
use Vetcare\Infraestructura\Evento\Listener\EnviarRecordatorioAlDueno;
use Vetcare\Infraestructura\Evento\Listener\LiberarCupoEnAgenda;
use Vetcare\Infraestructura\Evento\Listener\RegistrarEnHistoriaClinica;
use Vetcare\Infraestructura\Foto\SubidorDeFotosNulo;
use Vetcare\Infraestructura\Persistencia\EnMemoria\CitaEnMemoriaRepository;
use Vetcare\Infraestructura\Persistencia\EnMemoria\DuenoEnMemoriaRepository;
use Vetcare\Infraestructura\Persistencia\EnMemoria\FichaMascotaEnMemoriaRepository;
use Vetcare\Infraestructura\Persistencia\EnMemoria\MascotaEnMemoriaRepository;
use Vetcare\Infraestructura\Persistencia\EnMemoria\VeterinarioEnMemoriaRepository;
use Vetcare\Infraestructura\Reloj\RelojFijo;
use Vetcare\Presentacion\Consola\ControladorDeCitas;
use Vetcare\Presentacion\Consola\PresentadorDeCitas;

date_default_timezone_set('America/Lima');

/* -------------------------------------------------------------------------
 * CAPA DE INFRAESTRUCTURA (los "detalles": persistencia, reloj, eventos)
 * ---------------------------------------------------------------------- */
$reloj = new RelojFijo(new DateTimeImmutable('2026-09-10 07:00:00'));

$repoDuenos = new DuenoEnMemoriaRepository();
$repoMascotas = new MascotaEnMemoriaRepository();
$repoVeterinarios = new VeterinarioEnMemoriaRepository();
$repoCitas = new CitaEnMemoriaRepository();
$repoFichas = new FichaMascotaEnMemoriaRepository();

// "Sumidero" de los mensajes que producen los listeners: en el demo, la consola.
$aConsola = static function (string $mensaje): void {
    echo '      ' . $mensaje . PHP_EOL;
};

$despachador = new DespachadorEnMemoria();
$despachador->suscribir('cita.agendada', new EnviarRecordatorioAlDueno($aConsola));
$despachador->suscribir('cita.reprogramada', new AvisarReprogramacion($aConsola));
$despachador->suscribir('cita.cancelada', new LiberarCupoEnAgenda($aConsola));
$despachador->suscribir('cita.completada', new RegistrarEnHistoriaClinica($aConsola));

/* -------------------------------------------------------------------------
 * DATOS SEMILLA (Entities del Dominio guardadas via los repositorios)
 * ---------------------------------------------------------------------- */
$repoDuenos->guardar(new Dueno(
    'D-1',
    'Ana Torres',
    new Telefono('987 654 321'),
    new Email('ana.torres@example.com'),
));

$repoMascotas->guardar(new Mascota(
    'M-1',
    'Firulais',
    Especie::PERRO,
    new DateTimeImmutable('2019-05-01'),
    'D-1',
));
$repoMascotas->guardar(new Mascota(
    'M-2',
    'Piolin',
    Especie::AVE,                       // especie exotica
    new DateTimeImmutable('2026-04-01'), // cachorro (5 meses el 2026-09-10)
    'D-1',
));

$repoVeterinarios->guardar(new Veterinario('V-1', 'Dra. Rojas', [Especie::PERRO, Especie::GATO]));
$repoVeterinarios->guardar(new Veterinario('V-2', 'Dr. Salas', [Especie::AVE, Especie::REPTIL, Especie::ROEDOR]));

/* -------------------------------------------------------------------------
 * CAPA DE DOMINIO (Domain Services + Use Cases)
 * ---------------------------------------------------------------------- */
$agendaDelVeterinario = new AgendaDelVeterinario();
$calculadoraDeTarifa = new CalculadoraDeTarifa();

$agendarCita = new AgendarCita(
    $repoMascotas,
    $repoVeterinarios,
    $repoCitas,
    $agendaDelVeterinario,
    $calculadoraDeTarifa,
    $despachador,
    $reloj,
);
$confirmarCita = new ConfirmarCita($repoCitas, $despachador);
$reprogramarCita = new ReprogramarCita($repoCitas, $repoVeterinarios, $agendaDelVeterinario, $despachador, $reloj);
$cancelarCita = new CancelarCita($repoCitas, $despachador, $reloj);
$atenderCita = new AtenderCita($repoCitas, $despachador, $reloj);
$registrarFicha = new RegistrarFichaDeMascota($repoFichas, new SubidorDeFotosNulo());

/* -------------------------------------------------------------------------
 * CAPA DE APLICACION (fachada que orquesta y traduce a DTOs)
 * ---------------------------------------------------------------------- */
$servicioDeCitas = new ServicioDeCitas(
    $agendarCita,
    $confirmarCita,
    $reprogramarCita,
    $cancelarCita,
    $atenderCita,
    $repoCitas,
    $repoMascotas,
    $repoVeterinarios,
    $repoFichas,
    $registrarFicha,
);

/* -------------------------------------------------------------------------
 * CAPA DE PRESENTACION (controlador de consola + presentador)
 * ---------------------------------------------------------------------- */
$vista = new PresentadorDeCitas();
$controlador = new ControladorDeCitas($servicioDeCitas, $vista);

/* =========================================================================
 * ESCENARIOS
 * ====================================================================== */
echo 'CLINICA VETERINARIA  -  demo Clean Architecture (4 capas)' . PHP_EOL;
echo 'Hoy (reloj fijo): 2026-09-10 07:00' . PHP_EOL;

$vista->titulo('1. Agendar cita de Firulais (perro) con la Dra. Rojas  [caso feliz]');
$citaFirulais = $controlador->agendar([
    'mascota' => 'M-1',
    'veterinario' => 'V-1',
    'inicio' => '2026-09-12 09:00',
    'motivo' => 'consulta_general',
]);

$vista->titulo('2. Otra cita que se solapa con la Dra. Rojas 09:15  [Domain Service: agenda]');
$controlador->agendar([
    'mascota' => 'M-1',
    'veterinario' => 'V-1',
    'inicio' => '2026-09-12 09:15',
    'motivo' => 'control',
]);

$vista->titulo('3. Pedir que la Dra. Rojas (perros/gatos) atienda a Piolin (ave)  [Business Rule: especie]');
$controlador->agendar([
    'mascota' => 'M-2',
    'veterinario' => 'V-1',
    'inicio' => '2026-09-12 11:00',
    'motivo' => 'consulta_general',
]);

$vista->titulo('4. Agendar a Piolin (ave + cachorro) con el Dr. Salas  [Domain Service: tarifa con recargos]');
$citaPiolin = $controlador->agendar([
    'mascota' => 'M-2',
    'veterinario' => 'V-2',
    'inicio' => '2026-09-12 10:00',
    'motivo' => 'consulta_general',
]);

$vista->titulo('5. Agendar un control de Firulais para hoy 15:00  [se usara en el paso 6]');
$citaControlHoy = $controlador->agendar([
    'mascota' => 'M-1',
    'veterinario' => 'V-1',
    'inicio' => '2026-09-10 15:00',
    'motivo' => 'control',
]);

$vista->titulo('6. Reprogramar ese control (faltan 8 h)  [Business Rule: 24 h de anticipacion]');
if ($citaControlHoy !== null) {
    $controlador->reprogramar($citaControlHoy, '2026-09-11 10:00');
}

$vista->titulo('7. Reprogramar la cita de Firulais del 09-12 a las 14:00  [Domain Event: reprogramada]');
if ($citaFirulais !== null) {
    $controlador->reprogramar($citaFirulais, '2026-09-12 14:00');
}

$vista->titulo('8. Confirmar la cita de Firulais  [Business Rule: el estado solo avanza]');
if ($citaFirulais !== null) {
    $controlador->confirmar($citaFirulais);
}

$vista->titulo('9. Atender la cita de Firulais y cerrarla  [Domain Event: completada -> historia clinica]');
if ($citaFirulais !== null) {
    $controlador->atender($citaFirulais, 'Chequeo general sin novedades. Vacunas al dia.');
}

$vista->titulo('10. Intentar cancelar una cita ya completada  [Business Rule: transicion invalida]');
if ($citaFirulais !== null) {
    $controlador->cancelar($citaFirulais, 'El dueno ya no puede asistir');
}

$vista->titulo('11. Cancelar la cita de Piolin  [Domain Event: cancelada -> libera cupo]');
if ($citaPiolin !== null) {
    $controlador->cancelar($citaPiolin, 'La mascota mejoro sola');
}

echo PHP_EOL . 'Fin del demo.' . PHP_EOL;
