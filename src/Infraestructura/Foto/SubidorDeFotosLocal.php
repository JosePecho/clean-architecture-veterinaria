<?php

declare(strict_types=1);

namespace Vetcare\Infraestructura\Foto;

use finfo;
use Vetcare\Dominio\Contrato\SubidorDeFotos;
use Vetcare\Dominio\Excepcion\ReglaDeNegocioViolada;

final class SubidorDeFotosLocal implements SubidorDeFotos
{
    private const TIPOS = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    public function __construct(
        private readonly string $directorioDestino,
        private readonly string $urlBase,
        private readonly int $tamanoMaximo = 2_097_152,
    ) {
    }

    public function subir(?array $archivo): ?string
    {
        if ($archivo === null || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            throw new ReglaDeNegocioViolada('Error al subir la foto.');
        }

        if ($archivo['size'] > $this->tamanoMaximo) {
            throw new ReglaDeNegocioViolada('La foto supera 2 MB.');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($archivo['tmp_name']);
        if ($mime === false || !isset(self::TIPOS[$mime])) {
            throw new ReglaDeNegocioViolada('Formato no permitido. Usa JPG, PNG o WEBP.');
        }

        if (!is_dir($this->directorioDestino) && !mkdir($this->directorioDestino, 0775, true)) {
            throw new ReglaDeNegocioViolada('No se pudo crear el directorio de destino.');
        }

        $nombre = bin2hex(random_bytes(16)) . '.' . self::TIPOS[$mime];
        $destino = rtrim($this->directorioDestino, "/\\") . '/' . $nombre;

        $movido = is_uploaded_file($archivo['tmp_name'])
            ? move_uploaded_file($archivo['tmp_name'], $destino)
            : (@rename($archivo['tmp_name'], $destino) || @copy($archivo['tmp_name'], $destino));

        if (!$movido) {
            throw new ReglaDeNegocioViolada('No se pudo guardar la foto.');
        }

        return rtrim($this->urlBase, '/') . '/' . $nombre;
    }
}