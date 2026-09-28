<?php

declare(strict_types=1);

/**
 * ============================================================================
 *  FRONT CONTROLLER + COMPOSITION ROOT  (capa de Presentacion: HTML)
 * ============================================================================
 *  Unico archivo que conoce las 4 capas a la vez. Arma el grafo de objetos con
 *  los repositorios que guardan en archivos .txt (nada de MySQL) y despacha la
 *  peticion HTTP con el patron Post-Redirect-Get.
 *
 *  Levantar:  php -S localhost:8000 -t public
 * ============================================================================
 */

require __DIR__ . '/../bin/autoload.php';

use Vetcare\Aplicacion\Servicio\ServicioDeCitas;
use Vetcare\Dominio\CasoDeUso\AgendarCita;
use Vetcare\Dominio\CasoDeUso\AtenderCita;
use Vetcare\Dominio\CasoDeUso\CancelarCita;
use Vetcare\Dominio\CasoDeUso\ConfirmarCita;
use Vetcare\Dominio\CasoDeUso\RegistrarFichaDeMascota;
use Vetcare\Dominio\CasoDeUso\ReprogramarCita;
use Vetcare\Dominio\Servicio\AgendaDelVeterinario;
use Vetcare\Dominio\Servicio\CalculadoraDeTarifa;
use Vetcare\Infraestructura\Evento\DespachadorEnMemoria;
use Vetcare\Infraestructura\Evento\Listener\AvisarReprogramacion;
use Vetcare\Infraestructura\Evento\Listener\EnviarRecordatorioAlDueno;
use Vetcare\Infraestructura\Evento\Listener\LiberarCupoEnAgenda;
use Vetcare\Infraestructura\Evento\Listener\RegistrarEnHistoriaClinica;
use Vetcare\Infraestructura\Evento\RegistroDeNotificaciones;
use Vetcare\Infraestructura\Foto\SubidorDeFotosLocal;
use Vetcare\Infraestructura\Persistencia\Archivo\CitaArchivoRepository;
use Vetcare\Infraestructura\Persistencia\Archivo\FichaMascotaArchivoRepository;
use Vetcare\Infraestructura\Persistencia\Archivo\MascotaArchivoRepository;
use Vetcare\Infraestructura\Persistencia\Archivo\VeterinarioArchivoRepository;
use Vetcare\Infraestructura\Reloj\RelojDelSistema;
use Vetcare\Presentacion\Web\ControladorWebDeCitas;
use Vetcare\Presentacion\Web\PaginaCitas;

date_default_timezone_set('America/Lima');
session_start();

$dirDatos = __DIR__ . '/../data';
$dirUploads = __DIR__ . '/uploads/mascotas';
if (!is_dir($dirUploads)) {
    mkdir($dirUploads, 0777, true);
}

/* --- CAPA DE INFRAESTRUCTURA: repositorios en archivos .txt, reloj, eventos, fotos --- */
$repoMascotas = new MascotaArchivoRepository($dirDatos);
$repoVeterinarios = new VeterinarioArchivoRepository($dirDatos);
$repoCitas = new CitaArchivoRepository($dirDatos);
$repoFichas = new FichaMascotaArchivoRepository($dirDatos);
$subidorFotos = new SubidorDeFotosLocal($dirUploads, '/uploads/mascotas');

$reloj = new RelojDelSistema();

$registro = new RegistroDeNotificaciones($dirDatos . '/notificaciones.txt');
$emitir = $registro->registrar(...); // Closure(string): void  -> escribe en el .txt

$despachador = new DespachadorEnMemoria();
$despachador->suscribir('cita.agendada', new EnviarRecordatorioAlDueno($emitir));
$despachador->suscribir('cita.reprogramada', new AvisarReprogramacion($emitir));
$despachador->suscribir('cita.cancelada', new LiberarCupoEnAgenda($emitir));
$despachador->suscribir('cita.completada', new RegistrarEnHistoriaClinica($emitir));

/* --- CAPA DE DOMINIO: Domain Services + Use Cases --- */
$agenda = new AgendaDelVeterinario();
$tarifas = new CalculadoraDeTarifa();
$registrarFicha = new RegistrarFichaDeMascota($repoFichas, $subidorFotos);

$servicio = new ServicioDeCitas(
    new AgendarCita($repoMascotas, $repoVeterinarios, $repoCitas, $agenda, $tarifas, $despachador, $reloj),
    new ConfirmarCita($repoCitas, $despachador),
    new ReprogramarCita($repoCitas, $repoVeterinarios, $agenda, $despachador, $reloj),
    new CancelarCita($repoCitas, $despachador, $reloj),
    new AtenderCita($repoCitas, $despachador, $reloj),
    $repoCitas,
    $repoMascotas,
    $repoVeterinarios,
    $repoFichas,
    $registrarFicha,
);

/* --- CAPA DE PRESENTACION --- */
$controlador = new ControladorWebDeCitas($servicio);
$pagina = new PaginaCitas();

/* --- Despacho de la peticion (Post-Redirect-Get) --- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = is_string($_POST['accion'] ?? null) ? $_POST['accion'] : '';
    $_SESSION['flash'] = $controlador->procesar($accion, $_POST);

    header('Location: /');
    exit;
}

$flash = is_array($_SESSION['flash'] ?? null) ? $_SESSION['flash'] : null;
unset($_SESSION['flash']);

if ($repoMascotas->listarTodas() === []) {
    http_response_code(503);
    echo '<p style="font:16px system-ui;max-width:640px;margin:40px auto">'
        . 'No hay datos cargados. Corre primero <code>php bin/seed.php</code> '
        . 'y vuelve a abrir esta pagina.</p>';
    exit;
}

echo $pagina->render(
    $servicio->listarCitas(),
    $servicio->mascotasParaSeleccion(),
    $servicio->veterinariosParaSeleccion(),
    $servicio->motivosParaSeleccion(),
    $registro->ultimas(12),
    $flash,
);
