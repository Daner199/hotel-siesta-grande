<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Reglas de validación de datos personales.
 * Las usan: registro de clientes, recepcionistas y contacto de agencias.
 * Son las MISMAS que valida public/js/validacion.js en el navegador.
 */
class ValidacionUsuario
{
    // Solo letras (con tildes y ñ). Entre palabras: espacio, guion o apóstrofo.
    public const SOLO_LETRAS = 'regex:/^\pL+(?:[\s\'-]\pL+)*$/u';

    // Limpia los datos antes de validar
    public static function preparar(Request $request): void
    {
        $request->merge([
            'nombre'   => self::mayusculaInicial(self::limpiarTexto($request->input('nombre'))),
            'apellido' => self::mayusculaInicial(self::limpiarTexto($request->input('apellido'))),
            'telefono' => preg_replace('/\D/', '', (string) $request->input('telefono')) ?: null,
            'email'    => Str::lower(trim((string) $request->input('email'))),
        ]);
    }

    /**
     * @param int|null $ignorarId           Al editar: id del usuario, para que pueda conservar su correo
     * @param bool     $passwordObligatoria  false al editar (vacía = no se cambia)
     */
    public static function reglas(?int $ignorarId = null, bool $passwordObligatoria = true): array
    {
        return [
            'nombre'        => ['required', 'string', 'min:2', 'max:100', self::SOLO_LETRAS],
            'apellido'      => ['required', 'string', 'min:2', 'max:100', self::SOLO_LETRAS],
            'telefono_pais' => ['nullable', 'required_with:telefono', Rule::in(array_keys(Paises::lista()))],
            'telefono'      => ['nullable', 'digits_between:4,15', 'phone:telefono_pais'],
            'email'         => [
                'required', 'string', 'max:150',
                'email:rfc,filter',
                'regex:/^[^@\s]+@[^@\s]+\.[a-z]{2,}$/i',
                Rule::unique('usuario', 'email')->ignore($ignorarId),
            ],
            'password'      => [
                $passwordObligatoria ? 'required' : 'nullable',
                'string', 'min:8', 'max:72',
                'regex:/[A-Za-z]/',
                'regex:/[0-9]/',
                'confirmed',
            ],
        ];
    }

    public static function mensajes(): array
    {
        return [
            'nombre.required'   => 'Escribe el nombre.',
            'nombre.min'        => 'El nombre debe tener al menos 2 letras.',
            'nombre.max'        => 'El nombre es demasiado largo.',
            'nombre.regex'      => 'El nombre solo puede tener letras.',

            'apellido.required' => 'Escribe el apellido.',
            'apellido.min'      => 'El apellido debe tener al menos 2 letras.',
            'apellido.max'      => 'El apellido es demasiado largo.',
            'apellido.regex'    => 'El apellido solo puede tener letras.',

            'telefono_pais.required_with' => 'Elige el país del teléfono.',
            'telefono_pais.in'            => 'Elige un país de la lista.',
            'telefono.digits_between'     => 'El teléfono solo puede tener números (entre 4 y 15).',
            'telefono.phone'              => 'Ese número no es válido para el país elegido.',

            'email.required'    => 'Escribe el correo.',
            'email.email'       => 'El correo no tiene un formato válido.',
            'email.regex'       => 'Escribe el correo completo, por ejemplo nombre@gmail.com.',
            'email.max'         => 'El correo es demasiado largo.',
            'email.unique'      => 'Ya existe una cuenta con este correo.',

            'password.required'  => 'Crea una contraseña.',
            'password.min'       => 'La contraseña debe tener al menos 8 caracteres.',
            'password.max'       => 'La contraseña no puede tener más de 72 caracteres.',
            'password.regex'     => 'La contraseña debe tener al menos una letra y un número.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ];
    }

    // 71234567 + BO  →  +59171234567
    public static function telefonoE164(?string $numero, ?string $pais): ?string
    {
        return $numero ? phone($numero, $pais)->formatE164() : null;
    }

    // Quita espacios al inicio/final y deja uno solo entre palabras
    public static function limpiarTexto(mixed $texto): ?string
    {
        $texto = trim(preg_replace('/\s+/u', ' ', (string) $texto));

        return $texto === '' ? null : $texto;
    }

    // Palabras que van en minúscula salvo que sean la primera ("José de la Cruz")
    private const PARTICULAS = ['de', 'del', 'la', 'las', 'los', 'y'];

    // "maría o'brien-pérez" → "María O'Brien-Pérez" (solo la primera letra; el resto se respeta)
    public static function mayusculaInicial(?string $texto): ?string
    {
        if ($texto === null) {
            return null;
        }

        $texto = preg_replace_callback(
            '/(^|[\s\'-])(\p{Ll})/u',
            fn ($m) => $m[1] . mb_strtoupper($m[2]),
            $texto
        );

        $palabras = explode(' ', $texto);

        foreach ($palabras as $i => $palabra) {
            if ($i > 0 && in_array(mb_strtolower($palabra), self::PARTICULAS, true)) {
                $palabras[$i] = mb_strtolower($palabra);
            }
        }

        return implode(' ', $palabras);
    }
}