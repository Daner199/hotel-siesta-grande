<?php

namespace App\Support;

use App\Models\Foto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Lógica común de las galerías (fotos de tipos y de habitaciones).
 * - jpg/png/webp, máximo 2 MB cada una, máximo 12 por galería
 * - la primera foto es la principal; solo hay una principal
 * - al borrar la principal, la siguiente pasa a serlo
 */
class Galeria
{
    public const MAX_FOTOS = 12;
    public const MAX_KB    = 2048;

    // Valida la subida (incluido el máximo por galería)
    public static function validar(Request $request, HasMany $fotos): array
    {
        $datos = $request->validate([
            'fotos'   => ['required', 'array', 'max:' . self::MAX_FOTOS],
            'fotos.*' => ['bail', 'file', 'mimes:jpg,jpeg,png,webp', 'max:' . self::MAX_KB],
        ], [
            'fotos.required'   => 'Elige al menos una foto.',
            'fotos.array'      => 'Elige al menos una foto.',
            'fotos.max'        => 'Puedes subir hasta ' . self::MAX_FOTOS . ' fotos a la vez.',
            'fotos.*.uploaded' => 'Una foto no se pudo subir (puede pesar más de 2 MB).',
            'fotos.*.file'     => 'Una foto no se pudo subir.',
            'fotos.*.mimes'    => 'Solo se aceptan fotos JPG, PNG o WEBP.',
            'fotos.*.max'      => 'Cada foto puede pesar como máximo 2 MB.',
        ]);

        $libres = self::MAX_FOTOS - self::base($fotos)->count();

        if (count($datos['fotos']) > $libres) {
            throw ValidationException::withMessages([
                'fotos' => $libres > 0
                    ? "Solo puedes agregar {$libres} " . ($libres === 1 ? 'foto más' : 'fotos más') . ' (máximo ' . self::MAX_FOTOS . ').'
                    : 'La galería ya tiene ' . self::MAX_FOTOS . ' fotos. Elimina alguna para subir otras.',
            ]);
        }

        return $datos['fotos'];
    }

    /**
     * Guarda los archivos y sus registros.
     * Si la BD falla, se borran los archivos ya copiados (no quedan huérfanos).
     *
     * @param  UploadedFile[]  $archivos
     */
    public static function subir(HasMany $fotos, array $archivos, string $carpeta): int
    {
        $guardados = [];

        try {
            foreach ($archivos as $archivo) {
                $guardados[] = $archivo->store($carpeta, 'public');
            }

            DB::transaction(function () use ($fotos, $guardados) {
                $orden        = (int) self::base($fotos)->max('orden');
                $hayPrincipal = self::base($fotos)->where('es_principal', true)->exists();

                foreach ($guardados as $i => $ruta) {
                    $fotos->create([
                        'ruta'         => $ruta,
                        'orden'        => $orden + $i + 1,
                        'es_principal' => ! $hayPrincipal && $i === 0,
                    ]);
                }
            });
        } catch (Throwable $e) {
            Storage::disk('public')->delete($guardados);
            throw $e;
        }

        return count($guardados);
    }

    // Marca una foto como principal (primero se quita la anterior: índice único parcial)
    public static function hacerPrincipal(HasMany $fotos, Foto $foto): void
    {
        DB::transaction(function () use ($fotos, $foto) {
            self::base($fotos)->where('es_principal', true)->update(['es_principal' => false]);
            $foto->update(['es_principal' => true]);
        });
    }

    // Intercambia el lugar con la foto vecina ('izquierda' o 'derecha') y renumera 1..n
    public static function mover(HasMany $fotos, Foto $foto, string $direccion): void
    {
        DB::transaction(function () use ($fotos, $foto, $direccion) {
            $ids = $fotos->getQuery()->clone()->pluck('id')->all();   // en el orden actual
            $pos = array_search($foto->id, $ids, true);
            $otro = $direccion === 'izquierda' ? $pos - 1 : $pos + 1;

            if ($pos === false || ! isset($ids[$otro])) {
                return; // ya está en el extremo
            }

            [$ids[$pos], $ids[$otro]] = [$ids[$otro], $ids[$pos]];

            foreach ($ids as $i => $id) {
                self::base($fotos)->whereKey($id)->update(['orden' => $i + 1]);
            }
        });
    }

    // Borra la foto (registro y archivo); si era la principal, la siguiente pasa a serlo
    public static function eliminar(HasMany $fotos, Foto $foto): void
    {
        DB::transaction(function () use ($fotos, $foto) {
            $eraPrincipal = $foto->es_principal;
            $foto->delete();

            if ($eraPrincipal) {
                $fotos->getQuery()->clone()->first()?->update(['es_principal' => true]);
            }
        });

        Storage::disk('public')->delete($foto->ruta);
    }

    // Las fotos de la galería, sin ORDER BY (para count, max, update)
    private static function base(HasMany $fotos): Builder
    {
        return $fotos->getQuery()->clone()->reorder();
    }
}
