<?php

declare(strict_types=1);

namespace Vetcare\Presentacion\Web;

use Vetcare\Aplicacion\DTO\CitaDTO;

/**
 * Capa de Presentacion: arma el documento HTML completo.
 * Solo conoce DTOs y arrays de texto; nunca toca el Dominio ni la Infraestructura.
 */
final class PaginaCitas
{
    /**
     * @param CitaDTO[]                              $citas
     * @param array<string, string>                 $mascotas
     * @param array<string, string>                 $veterinarios
     * @param array<string, string>                 $motivos
     * @param string[]                              $notificaciones
     * @param array{tipo: string, mensaje: string}|null $flash
     */
    public function render(
        array $citas,
        array $mascotas,
        array $veterinarios,
        array $motivos,
        array $notificaciones,
        ?array $flash,
    ): string {
        $banner = $flash === null ? '' : sprintf(
            '<div class="flash flash-%s">%s</div>',
            $flash['tipo'] === 'ok' ? 'ok' : 'error',
            $this->e($flash['mensaje']),
        );

        $filas = $citas === []
            ? '<tr><td colspan="9" class="vacio">Todavia no hay citas. Agenda la primera con el formulario de arriba.</td></tr>'
            : implode('', array_map($this->fila(...), $citas));

        $notis = $notificaciones === []
            ? '<li class="vacio">Sin eventos todavia.</li>'
            : implode('', array_map(fn (string $n): string => '<li>' . $this->e($n) . '</li>', $notificaciones));

        $optMascotas = $this->opciones($mascotas);
        $optVeterinarios = $this->opciones($veterinarios);
        $optMotivos = $this->opciones($motivos);
        $estilos = $this->estilos();

        return <<<HTML
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Clinica Veterinaria - Citas</title>
<style>{$estilos}</style>
</head>
<body>
<main class="contenedor">
  <header>
    <h1>Clinica Veterinaria</h1>
    <p class="sub">Gestion de citas &middot; capa de Presentacion en HTML &middot; datos en archivos <code>.txt</code></p>
  </header>

  {$banner}

  <section class="tarjeta">
    <h2>Agendar una cita</h2>
    <form method="post" action="" class="form-agendar">
      <input type="hidden" name="accion" value="agendar">
      <label>Mascota
        <select name="mascota" required>{$optMascotas}</select>
      </label>
      <label>Veterinario
        <select name="veterinario" required>{$optVeterinarios}</select>
      </label>
      <label>Motivo
        <select name="motivo" required>{$optMotivos}</select>
      </label>
      <label>Inicio
        <input type="datetime-local" name="inicio" required>
      </label>
      <button type="submit" class="primario">Agendar cita</button>
    </form>
  </section>

  <section class="tarjeta">
    <h2>Citas registradas</h2>
    <div class="tabla-scroll">
      <table>
        <thead>
          <tr>
            <th>ID</th><th>Mascota</th><th>Veterinario</th><th>Franja</th>
            <th>Motivo</th><th>Costo</th><th>Estado</th><th>Diagnostico</th><th>Acciones</th>
          </tr>
        </thead>
        <tbody>{$filas}</tbody>
      </table>
    </div>
  </section>

  <section class="tarjeta">
    <h2>Eventos de dominio</h2>
    <p class="sub">Lo que anuncian los <em>Domain Events</em> al reaccionar. Se guarda en <code>data/notificaciones.txt</code>.</p>
    <ul class="notis">{$notis}</ul>
  </section>
</main>
</body>
</html>
HTML;
    }

    private function fila(CitaDTO $cita): string
    {
        return '<tr>'
            . '<td>' . $this->e($cita->id) . '</td>'
            . '<td>' . $this->e($cita->mascota) . '</td>'
            . '<td>' . $this->e($cita->veterinario) . '</td>'
            . '<td class="nowrap">' . $this->e($cita->franja) . '</td>'
            . '<td>' . $this->e($cita->motivo) . '</td>'
            . '<td class="nowrap">' . $this->e($cita->costo) . '</td>'
            . '<td><span class="estado estado-' . $this->e($cita->estado) . '">' . $this->e($cita->estado) . '</span></td>'
            . '<td>' . $this->e($cita->diagnostico ?? '—') . '</td>'
            . '<td class="acciones">' . $this->acciones($cita) . '</td>'
            . '</tr>';
    }

    private function acciones(CitaDTO $cita): string
    {
        return match ($cita->estado) {
            'programada' => $this->botonSimple($cita->id, 'confirmar', 'Confirmar')
                . $this->formConInput($cita->id, 'reprogramar', 'inicio', 'datetime-local', '', 'Reprogramar')
                . $this->formConInput($cita->id, 'cancelar', 'motivo', 'text', 'Motivo', 'Cancelar', true),
            'confirmada' => $this->formConInput($cita->id, 'atender', 'diagnostico', 'text', 'Diagnostico (min. 5 letras)', 'Atender y cerrar')
                . $this->formConInput($cita->id, 'reprogramar', 'inicio', 'datetime-local', '', 'Reprogramar')
                . $this->formConInput($cita->id, 'cancelar', 'motivo', 'text', 'Motivo', 'Cancelar', true),
            default => '<span class="vacio">—</span>',
        };
    }

    private function botonSimple(string $id, string $accion, string $etiqueta): string
    {
        return '<form method="post" action="">'
            . '<input type="hidden" name="accion" value="' . $this->e($accion) . '">'
            . '<input type="hidden" name="id" value="' . $this->e($id) . '">'
            . '<button type="submit">' . $this->e($etiqueta) . '</button>'
            . '</form>';
    }

    private function formConInput(
        string $id,
        string $accion,
        string $campo,
        string $tipo,
        string $placeholder,
        string $etiqueta,
        bool $peligro = false,
    ): string {
        $ph = $placeholder === '' ? '' : ' placeholder="' . $this->e($placeholder) . '"';
        $clase = $peligro ? ' class="peligro"' : '';

        return '<form method="post" action="" class="linea">'
            . '<input type="hidden" name="accion" value="' . $this->e($accion) . '">'
            . '<input type="hidden" name="id" value="' . $this->e($id) . '">'
            . '<input type="' . $this->e($tipo) . '" name="' . $this->e($campo) . '"' . $ph . ' required>'
            . '<button type="submit"' . $clase . '>' . $this->e($etiqueta) . '</button>'
            . '</form>';
    }

    /** @param array<string, string> $opciones */
    private function opciones(array $opciones): string
    {
        $html = '';
        foreach ($opciones as $valor => $etiqueta) {
            $html .= '<option value="' . $this->e((string) $valor) . '">' . $this->e($etiqueta) . '</option>';
        }

        return $html;
    }

    private function e(string $texto): string
    {
        return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
    }

    private function estilos(): string
    {
        return <<<CSS
:root { color-scheme: light; }
* { box-sizing: border-box; }
body { margin: 0; background: #f4f5f7; color: #1f2430; font: 15px/1.5 system-ui, -apple-system, Segoe UI, Roboto, sans-serif; }
.contenedor { max-width: 1040px; margin: 0 auto; padding: 24px 16px 64px; }
header h1 { margin: 0 0 4px; font-size: 24px; }
.sub { margin: 0; color: #5b6472; font-size: 13px; }
code { background: #e7e9ee; padding: 1px 5px; border-radius: 4px; font-size: 12px; }
.tarjeta { background: #fff; border: 1px solid #e2e5ea; border-radius: 10px; padding: 18px; margin-top: 18px; }
.tarjeta h2 { margin: 0 0 12px; font-size: 17px; }
.flash { margin-top: 16px; padding: 10px 14px; border-radius: 8px; font-weight: 600; }
.flash-ok { background: #e5f5ea; color: #1c6b3a; border: 1px solid #b6e0c4; }
.flash-error { background: #fdeaea; color: #a12626; border: 1px solid #f2c2c2; }
.form-agendar { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; align-items: end; }
.form-agendar label { display: flex; flex-direction: column; gap: 4px; font-size: 13px; color: #5b6472; }
.form-agendar button { grid-column: 1 / -1; }
input, select { padding: 7px 8px; border: 1px solid #c8cdd6; border-radius: 6px; font: inherit; background: #fff; }
button { padding: 7px 12px; border: 0; border-radius: 6px; background: #2f6b4f; color: #fff; font: inherit; font-weight: 600; cursor: pointer; }
button:hover { background: #275c43; }
button.primario { background: #1f5fbf; }
button.primario:hover { background: #1a4f9f; }
button.peligro { background: #b23b3b; }
button.peligro:hover { background: #9a3030; }
.tabla-scroll { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; font-size: 13px; }
th, td { text-align: left; padding: 8px 10px; border-bottom: 1px solid #ececf0; vertical-align: top; }
th { background: #fafbfc; color: #5b6472; font-size: 12px; text-transform: uppercase; letter-spacing: .03em; }
td.nowrap { white-space: nowrap; }
.vacio { color: #9098a4; }
.estado { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; }
.estado-programada { background: #e4eefb; color: #1f5fbf; }
.estado-confirmada { background: #e5f5ea; color: #1c6b3a; }
.estado-en_atencion { background: #fef3d9; color: #8a6100; }
.estado-completada { background: #e7e9ee; color: #3b4351; }
.estado-cancelada { background: #fdeaea; color: #a12626; }
td.acciones { display: flex; flex-wrap: wrap; gap: 6px; }
td.acciones form.linea { display: flex; gap: 4px; }
td.acciones input { max-width: 190px; }
td.acciones button { padding: 5px 9px; font-size: 12px; }
.notis { margin: 0; padding-left: 18px; font-size: 13px; color: #3b4351; }
.notis li { margin: 2px 0; }
@media (max-width: 640px) { .form-agendar { grid-template-columns: 1fr; } }
CSS;
    }
}
