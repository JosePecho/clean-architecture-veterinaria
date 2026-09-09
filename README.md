# Clínica Veterinaria — Clean Architecture en PHP nativo (4 capas)

Sistema de **gestión de citas de una clínica veterinaria** construido para practicar
las **4 capas** de Clean Architecture. La capa de Dominio replica la estructura del
documento *"Clean Architecture: la Capa de Dominio"* (analogía del restaurante).

- PHP 8.1+ **sin framework y sin Composer** (autoloader PSR-4 hecho a mano).
- **Sin base de datos.** Toda la persistencia son **archivos de texto plano** (`data/*.txt`).
- Dos presentaciones sobre el mismo Dominio: **HTML** (app web) y consola (demo guiado).

---

## Cómo ejecutar

Requisito único: **PHP 8.1 o superior** en el PATH (`php -v`). No hace falta Composer,
ni MySQL, ni Apache/Nginx.

### Opción A — Aplicación web (capa de Presentación en HTML)

```bash
# 1. Cargar los datos iniciales (crea los archivos data/*.txt)
php bin/seed.php

# 2. Levantar el servidor web embebido de PHP
php -S localhost:8000 -t public

# 3. Abrir en el navegador
#    http://localhost:8000
```

En la página podés **agendar, confirmar, reprogramar, atender y cancelar** citas.
El panel inferior *"Eventos de dominio"* muestra lo que anuncian los Domain Events
(se guarda en `data/notificaciones.txt`).

> Al agendar/reprogramar, elegí una fecha **futura** dentro del horario 08:00–18:00;
> el Dominio rechaza citas en el pasado o fuera de la jornada del veterinario.

Para **reiniciar** el estado: volvé a correr `php bin/seed.php` (borra citas y notificaciones).

### Opción B — Demo por consola (recorrido guiado)

```bash
php bin/demo.php
```

Ejecuta 11 escenarios con un reloj fijo y almacenamiento **en memoria** (no toca
`data/`), para ver de un vistazo el flujo completo y los rechazos por reglas de negocio.

---

## Persistencia: solo archivos de texto (nada de MySQL)

La app web guarda todo en hojas de texto separadas por `|`, una línea por registro,
dentro de `data/`:

| Archivo | Formato de cada línea | Ejemplo |
|---|---|---|
| `data/duenos.txt` | `id\|nombre\|telefono\|email` | `D-1\|Ana Torres\|987654321\|ana.torres@example.com` |
| `data/mascotas.txt` | `id\|nombre\|especie\|nacimiento\|duenoId` | `M-1\|Firulais\|perro\|2019-05-01\|D-1` |
| `data/veterinarios.txt` | `id\|nombre\|especies(coma)\|horaIni\|horaFin` | `V-1\|Dra. Rojas\|perro,gato\|8\|18` |
| `data/citas.txt` | `id\|mascotaId\|vetId\|inicio\|fin\|motivo\|centimos\|moneda\|estado\|diagnostico` | `CITA-001\|M-1\|V-1\|2027-01-15 09:00:00\|2027-01-15 09:30:00\|consulta_general\|6000\|PEN\|programada\|` |
| `data/notificaciones.txt` | log de los Domain Events | `[2026-09-09 06:51] [notificaciones] SMS al dueno: ...` |

Podés abrir esos archivos con cualquier editor para inspeccionar el estado.
Las implementaciones están en `src/Infraestructura/Persistencia/Archivo/` y cumplen
las **mismas Repository Interfaces** del Dominio (`src/Dominio/Contrato/`). Cambiarlas
por una versión MySQL no tocaría ni una línea del Dominio ni de la Aplicación — de
hecho ya conviven dos implementaciones: `Archivo/` (web) y `EnMemoria/` (demo y tests).

---

## Las 4 capas y la Regla de Dependencia

```
        ┌─────────────────────────────────────────────────────┐
        │                   PRESENTACIÓN                       │  src/Presentacion
        │   Web/  → PaginaCitas (HTML) · ControladorWebDeCitas │
        │   Consola/ → ControladorDeCitas · PresentadorDeCitas │
        └───────────────────────┬─────────────────────────────┘
                                │ depende de
        ┌───────────────────────▼─────────────────────────────┐
        │                    APLICACIÓN                        │  src/Aplicacion
        │      ServicioDeCitas · DTOs (Request / CitaDTO)      │
        └───────────────────────┬─────────────────────────────┘
                                │ depende de
        ┌───────────────────────▼─────────────────────────────┐
        │                     DOMINIO                          │  src/Dominio
        │  Entities · Value Objects · Business Rules           │  ◄── no depende
        │  Use Cases · Repository Interfaces                   │      de NADIE
        │  Domain Services · Domain Events                     │
        └───────────────────────▲─────────────────────────────┘
                                │ implementa sus interfaces
        ┌───────────────────────┴─────────────────────────────┐
        │           INFRAESTRUCTURA  (capa de "datos")         │  src/Infraestructura
        │  Persistencia/Archivo/  → repos en .txt   (app web)  │
        │  Persistencia/EnMemoria/ → repos en RAM   (demo)     │
        │  Reloj · DespachadorEnMemoria · Listeners            │
        └─────────────────────────────────────────────────────┘
```

**Todas las flechas de dependencia apuntan hacia adentro, al Dominio.** El Dominio no
tiene SQL, ni HTML, ni HTTP, ni referencias a frameworks. Igual que *el Chef nunca
depende del mesero*.

`public/index.php` (web) y `bin/demo.php` (consola) son los **composition roots**: los
únicos lugares que conocen las 4 capas a la vez y arman el grafo de objetos
(inyección de dependencias manual).

---

## Mapa: documento → código (capa de Dominio)

| Elemento del documento | Dónde vive (`src/Dominio/`) |
|---|---|
| **Entity** | `Modelo/` → `Cita` (raíz), `Mascota`, `Veterinario`, `Dueno` |
| **Value Object** | `ValueObject/` → `FranjaHoraria`, `Dinero` (nunca negativo), `EstadoCita`, `Especie`, `MotivoDeConsulta`, `Telefono`, `Email`, `Diagnostico` |
| **Business Rule** | Dentro de las Entities: `Cita::agendar()` (no en el pasado, especie, jornada), `Cita::reprogramar()` (24 h de anticipación), `EstadoCita::puedeAvanzarA()` (el estado solo avanza) |
| **Use Case** | `CasoDeUso/` → `AgendarCita`, `ConfirmarCita`, `ReprogramarCita`, `CancelarCita`, `AtenderCita` |
| **Repository Interface** | `Contrato/` → `CitaRepository`, `MascotaRepository`, `VeterinarioRepository`, `DuenoRepository` (+ puertos `Reloj`, `DespachadorDeEventos`) |
| **Domain Service** | `Servicio/` → `AgendaDelVeterinario` (solapamientos), `CalculadoraDeTarifa` (precio = motivo + especie + edad) |
| **Domain Event** | `Evento/` → `CitaAgendada/Reprogramada/Cancelada/CompletadaEvent`; los *listeners* que reaccionan están en Infraestructura |

`Cita` tiene dos formas de nacer: `Cita::agendar()` (crea + valida reglas + emite
evento) y `Cita::rehidratar()` (solo para los repositorios: reconstruye desde el
archivo sin re-validar ni re-emitir eventos).

---

## Qué muestra la demo de consola

| # | Escenario | Concepto |
|---|---|---|
| 1 | Agendar cita (caso feliz) | Use Case + Entity + Domain Event |
| 2 | Cita que se solapa | Domain Service `AgendaDelVeterinario` |
| 3 | Veterinario que no atiende esa especie | Business Rule en la Entity |
| 4 | Cita de ave cachorra | Domain Service `CalculadoraDeTarifa` (PEN 60 → 89.70) |
| 5 | Control para "hoy" | preparación del paso 6 |
| 6 | Reprogramar con < 24 h | Business Rule temporal en `Cita` |
| 7 | Reprogramar con antelación válida | Domain Event `CitaReprogramadaEvent` |
| 8 | Confirmar | Business Rule "el estado solo avanza" |
| 9 | Atender y cerrar | Domain Event `CitaCompletadaEvent` → historia clínica |
| 10 | Cancelar una cita ya completada | Business Rule "transición inválida" |
| 11 | Cancelar cita programada | Domain Event `CitaCanceladaEvent` → libera cupo |

---

## Estructura de carpetas

```
bin/
  autoload.php         Autoloader PSR-4 manual (Vetcare\ -> src/)
  seed.php             Carga data/*.txt con los datos iniciales
  demo.php             Composition root + 11 escenarios por consola
public/
  index.php            Front controller + composition root de la app web
data/                  Persistencia en texto plano (se crea con seed.php)
src/
  Dominio/
    Modelo/            Entities (+ Comun/RegistraEventos)
    ValueObject/       Value Objects
    Servicio/          Domain Services
    Evento/            Domain Events
    CasoDeUso/         Use Cases
    Contrato/          Repository Interfaces + puertos (Reloj, DespachadorDeEventos)
    Excepcion/         ReglaDeNegocioViolada, RecursoNoEncontrado
  Aplicacion/
    DTO/              AgendarCitaRequest, CitaDTO
    Servicio/         ServicioDeCitas (fachada de orquestación)
  Infraestructura/
    Persistencia/Archivo/     Repos en .txt  (los usa la app web)
    Persistencia/EnMemoria/   Repos en RAM   (los usa la demo de consola)
    Reloj/                    RelojDelSistema, RelojFijo
    Evento/                   DespachadorEnMemoria, RegistroDeNotificaciones, Listener/
  Presentacion/
    Web/              PaginaCitas (HTML), ControladorWebDeCitas
    Consola/          ControladorDeCitas, PresentadorDeCitas
```

## Nota sobre dónde van los Use Cases

El documento ubica **Use Cases y Repository Interfaces dentro de la Capa de Dominio**,
y así se hizo aquí. La capa de **Aplicación** queda como *fachada de orquestación*:
recibe/devuelve DTOs, invoca los Use Cases y traduce el resultado del Dominio a algo
que la Presentación pueda mostrar, sin contener reglas de negocio.
