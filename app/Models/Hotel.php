<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Datos del hotel (tabla de una sola fila, id = 1). Los edita el admin en "Datos del hotel".
 * Para mostrarlos usar SIEMPRE Hotel::datos(): mezcla la BD con config/hotel.php (respaldo)
 * y queda en caché hasta que el admin guarde cambios.
 */
class Hotel extends Model
{
    protected $table = 'hotel';

    // Solo tiene updated_at
    const CREATED_AT = null;

    public $incrementing = false;

    // Columnas de fotos (ruta en storage/app/public)
    public const FOTOS = ['logo', 'portada', 'foto_fachada', 'foto_piscina', 'foto_restaurante'];

    protected $fillable = [
        'nombre', 'eslogan', 'direccion', 'referencia', 'ciudad',
        'telefono', 'whatsapp', 'correo', 'facebook', 'instagram', 'tiktok',
        'check_in', 'check_out', 'latitud', 'longitud',
        'logo', 'portada', 'foto_fachada', 'foto_piscina', 'foto_restaurante',
    ];

    private const CLAVE_CACHE = 'hotel.datos';

    // La única fila (null si la tabla todavía no existe o está vacía)
    public static function fila(): ?self
    {
        try {
            return static::find(1);
        } catch (QueryException) {
            return null;
        }
    }

    /**
     * Todos los datos listos para mostrar.
     * 'fotos' trae la URL de cada foto, o null si no hay (la vista muestra un fondo elegante).
     */
    public static function datos(): array
    {
        $datos = Cache::rememberForever(self::CLAVE_CACHE, fn () => self::armar());

        // La URL se arma en cada petición (usa el host actual, no se guarda en caché)
        $datos['fotos'] = array_map(
            fn ($ruta) => $ruta ? asset('storage/' . $ruta) : null,
            $datos['rutas']
        );

        return $datos;
    }

    // Borrar la caché (después de guardar cambios)
    public static function olvidar(): void
    {
        Cache::forget(self::CLAVE_CACHE);
    }

    private static function armar(): array
    {
        $c = config('hotel');
        $f = self::fila();

        // Obligatorios en la BD: siempre tienen valor si hay fila
        $o = fn (string $campo, $respaldo) => $f?->$campo ?? $respaldo;

        // Con fila: lo que guardó el admin (un opcional vacío es porque lo quitó a propósito).
        // Sin fila (tabla nueva o vacía): config/hotel.php.
        $v = fn (string $campo, $respaldo = null) => $f ? (filled($f->$campo) ? $f->$campo : null) : $respaldo;

        // Solo fotos que existan de verdad en el disco
        $ruta = fn (string $campo) => ($r = $f?->$campo) && Storage::disk('public')->exists($r) ? $r : null;

        // Ubicación: null si el admin no puso el pin
        $lat = $v('latitud', $c['mapa']['latitud']);
        $lng = $v('longitud', $c['mapa']['longitud']);

        return [
            'nombre'       => $o('nombre', $c['nombre']),
            'eslogan'      => $v('eslogan', $c['eslogan']),
            'desde'        => $c['desde'],
            'habitaciones' => $c['habitaciones'],

            'direccion' => [
                'calle'        => $o('direccion', $c['direccion']['calle']),
                'referencia'   => $v('referencia', $c['direccion']['referencia']),
                'ciudad'       => $o('ciudad', $c['direccion']['ciudad']),
                'departamento' => $c['direccion']['departamento'],
                'pais'         => $c['direccion']['pais'],
            ],

            'mapa' => $lat !== null && $lng !== null
                ? ['latitud' => (float) $lat, 'longitud' => (float) $lng]
                : null,

            'telefono' => $v('telefono', $c['telefono']),
            'whatsapp' => $v('whatsapp', $c['whatsapp']),
            'correo'   => $v('correo', $c['correo']),

            'check_in'  => substr((string) $o('check_in', $c['check_in']), 0, 5),
            'check_out' => substr((string) $o('check_out', $c['check_out']), 0, 5),
            'recepcion' => $c['recepcion'],

            'redes' => [
                'facebook'  => $v('facebook', $c['redes']['facebook']),
                'instagram' => $v('instagram', $c['redes']['instagram']),
                'tiktok'    => $v('tiktok', $c['redes']['tiktok']),
            ],

            'rutas' => [
                'logo'        => $ruta('logo'),
                'portada'     => $ruta('portada'),
                'fachada'     => $ruta('foto_fachada'),
                'piscina'     => $ruta('foto_piscina'),
                'restaurante' => $ruta('foto_restaurante'),
            ],
        ];
    }
}
