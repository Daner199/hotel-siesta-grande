<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Support\Galeria;
use App\Support\Paises;
use App\Support\ValidacionUsuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * "Datos del hotel": nombre, fotos, contacto, redes, horarios y ubicación en el mapa.
 * Una sola fila (id = 1). Al guardar se limpia la caché de Hotel::datos().
 */
class HotelController extends Controller
{
    public function edit()
    {
        $hotel = Hotel::fila() ?? $this->filaDesdeConfig();

        return view('admin.hotel.editar', [
            'hotel'    => $hotel,
            'fotos'    => Hotel::datos()['fotos'],
            'paises'   => Paises::lista(),
            'telefono' => Paises::separarTelefono($hotel->telefono),
            'whatsapp' => Paises::separarTelefono($hotel->whatsapp),
        ]);
    }

    public function update(Request $request)
    {
        $this->preparar($request);
        $datos = $request->validate($this->reglas(), $this->mensajes());

        $hotel = Hotel::fila() ?? $this->filaDesdeConfig();
        $quitar = $request->input('quitar', []);
        $nuevas = [];      // fotos nuevas ya copiadas (por si hay que deshacer)
        $viejas = [];      // fotos reemplazadas o quitadas (se borran al final)

        try {
            foreach (Hotel::FOTOS as $campo) {
                if ($request->hasFile($campo)) {
                    $nuevas[$campo] = $request->file($campo)->store('hotel', 'public');
                    $viejas[] = $hotel->$campo;
                    $hotel->$campo = $nuevas[$campo];
                } elseif (in_array($campo, $quitar, true)) {
                    $viejas[] = $hotel->$campo;
                    $hotel->$campo = null;
                }
            }

            $hotel->fill([
                'nombre'     => $datos['nombre'],
                'eslogan'    => $datos['eslogan'] ?? null,
                'direccion'  => $datos['direccion'],
                'referencia' => $datos['referencia'] ?? null,
                'ciudad'     => $datos['ciudad'],
                'telefono'   => ValidacionUsuario::telefonoE164($datos['telefono'] ?? null, $datos['telefono_pais'] ?? null),
                'whatsapp'   => ValidacionUsuario::telefonoE164($datos['whatsapp'] ?? null, $datos['whatsapp_pais'] ?? null),
                'correo'     => $datos['correo'] ?? null,
                'facebook'   => $datos['facebook'] ?? null,
                'instagram'  => $datos['instagram'] ?? null,
                'tiktok'     => $datos['tiktok'] ?? null,
                'check_in'   => $datos['check_in'],
                'check_out'  => $datos['check_out'],
                'latitud'    => $datos['latitud'] ?? null,
                'longitud'   => $datos['longitud'] ?? null,
            ]);
            $hotel->id = 1;
            $hotel->save();
        } catch (Throwable $e) {
            Storage::disk('public')->delete(array_values($nuevas));
            throw $e;
        }

        Storage::disk('public')->delete(array_filter($viejas));
        Hotel::olvidar();

        return redirect()
            ->route('admin.hotel.edit')
            ->with('exito', 'Se guardaron los datos del hotel. La página pública ya muestra los cambios.');
    }

    // Si la tabla está vacía, el formulario arranca con los datos de config/hotel.php
    private function filaDesdeConfig(): Hotel
    {
        $c = config('hotel');

        return new Hotel([
            'nombre'     => $c['nombre'],
            'eslogan'    => $c['eslogan'],
            'direccion'  => $c['direccion']['calle'],
            'referencia' => $c['direccion']['referencia'],
            'ciudad'     => $c['direccion']['ciudad'],
            'telefono'   => $c['telefono'],
            'whatsapp'   => $c['whatsapp'],
            'correo'     => $c['correo'],
            'facebook'   => $c['redes']['facebook'],
            'instagram'  => $c['redes']['instagram'],
            'tiktok'     => $c['redes']['tiktok'],
            'check_in'   => $c['check_in'],
            'check_out'  => $c['check_out'],
            'latitud'    => $c['mapa']['latitud'],
            'longitud'   => $c['mapa']['longitud'],
        ]);
    }

    // ---------- Validación ----------

    private function preparar(Request $request): void
    {
        $texto = fn ($campo) => ValidacionUsuario::limpiarTexto($request->input($campo));
        $url   = fn ($campo) => trim((string) $request->input($campo)) ?: null;

        $request->merge([
            'nombre'     => $texto('nombre'),
            'eslogan'    => $texto('eslogan'),
            'direccion'  => $texto('direccion'),
            'referencia' => $texto('referencia'),
            'ciudad'     => $texto('ciudad'),
            'telefono'   => preg_replace('/\D/', '', (string) $request->input('telefono')) ?: null,
            'whatsapp'   => preg_replace('/\D/', '', (string) $request->input('whatsapp')) ?: null,
            'correo'     => Str::lower(trim((string) $request->input('correo'))) ?: null,
            'facebook'   => $url('facebook'),
            'instagram'  => $url('instagram'),
            'tiktok'     => $url('tiktok'),
            'latitud'    => $request->filled('latitud') ? $request->input('latitud') : null,
            'longitud'   => $request->filled('longitud') ? $request->input('longitud') : null,
        ]);
    }

    private function reglas(): array
    {
        $paises = Rule::in(array_keys(Paises::lista()));
        $foto   = ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:' . Galeria::MAX_KB];

        return [
            'nombre'     => ['bail', 'required', 'string', 'min:3', 'max:100'],
            'eslogan'    => ['nullable', 'string', 'max:150'],
            'direccion'  => ['bail', 'required', 'string', 'max:200'],
            'referencia' => ['nullable', 'string', 'max:255'],
            'ciudad'     => ['bail', 'required', 'string', 'max:100'],

            'telefono_pais' => ['nullable', 'required_with:telefono', $paises],
            'telefono'      => ['nullable', 'digits_between:4,15', 'phone:telefono_pais'],
            'whatsapp_pais' => ['nullable', 'required_with:whatsapp', $paises],
            'whatsapp'      => ['nullable', 'digits_between:4,15', 'phone:whatsapp_pais,mobile'],
            'correo'        => ['nullable', 'string', 'max:150', 'email:rfc,filter', 'regex:/^[^@\s]+@[^@\s]+\.[a-z]{2,}$/i'],

            'facebook'  => ['nullable', 'url', 'max:255', 'regex:#^https://(www\.|m\.)?facebook\.com/.+#i'],
            'instagram' => ['nullable', 'url', 'max:255', 'regex:#^https://(www\.)?instagram\.com/.+#i'],
            'tiktok'    => ['nullable', 'url', 'max:255', 'regex:#^https://(www\.)?tiktok\.com/@.+#i'],

            'check_in'  => ['required', 'date_format:H:i'],
            'check_out' => ['required', 'date_format:H:i'],

            'latitud'  => ['nullable', 'required_with:longitud', 'numeric', 'between:-90,90'],
            'longitud' => ['nullable', 'required_with:latitud', 'numeric', 'between:-180,180'],

            'logo'             => $foto,
            'portada'          => $foto,
            'foto_fachada'     => $foto,
            'foto_piscina'     => $foto,
            'foto_restaurante' => $foto,
            'quitar'           => ['nullable', 'array'],
            'quitar.*'         => [Rule::in(Hotel::FOTOS)],
        ];
    }

    private function mensajes(): array
    {
        $foto = [
            'file'  => 'La foto no se pudo subir.',
            'mimes' => 'Solo se aceptan fotos JPG, PNG o WEBP.',
            'max'   => 'La foto puede pesar como máximo 2 MB.',
        ];

        $mensajes = [
            'nombre.required'    => 'Escribe el nombre del hotel.',
            'nombre.min'         => 'El nombre debe tener al menos 3 caracteres.',
            'direccion.required' => 'Escribe la dirección.',
            'ciudad.required'    => 'Escribe la ciudad.',

            'telefono_pais.required_with' => 'Elige el país del teléfono.',
            'telefono.digits_between'     => 'El teléfono solo puede tener números (entre 4 y 15).',
            'telefono.phone'              => 'Ese número no es válido para el país elegido.',
            'whatsapp_pais.required_with' => 'Elige el país del WhatsApp.',
            'whatsapp.digits_between'     => 'El WhatsApp solo puede tener números (entre 4 y 15).',
            'whatsapp.phone'              => 'Escribe un número de celular válido para el país elegido.',

            'correo.email' => 'El correo no tiene un formato válido.',
            'correo.regex' => 'Escribe el correo completo, por ejemplo reservas@siestagrande.com.',

            'facebook.url'    => 'Escribe la dirección completa, por ejemplo https://www.facebook.com/hotelsiestagrande',
            'facebook.regex'  => 'Debe ser una página de Facebook (https://www.facebook.com/...).',
            'instagram.url'   => 'Escribe la dirección completa, por ejemplo https://www.instagram.com/hotelsiestagrande',
            'instagram.regex' => 'Debe ser un perfil de Instagram (https://www.instagram.com/...).',
            'tiktok.url'      => 'Escribe la dirección completa, por ejemplo https://www.tiktok.com/@hotelsiestagrande',
            'tiktok.regex'    => 'Debe ser un perfil de TikTok (https://www.tiktok.com/@...).',

            'check_in.required'     => 'Indica la hora de check-in.',
            'check_in.date_format'  => 'La hora de check-in no es válida.',
            'check_out.required'    => 'Indica la hora de check-out.',
            'check_out.date_format' => 'La hora de check-out no es válida.',

            'latitud.*'  => 'La ubicación del mapa no es válida. Vuelve a poner el pin.',
            'longitud.*' => 'La ubicación del mapa no es válida. Vuelve a poner el pin.',
        ];

        foreach (Hotel::FOTOS as $campo) {
            foreach ($foto as $regla => $texto) {
                $mensajes["{$campo}.{$regla}"] = $texto;
            }
        }

        return $mensajes;
    }
}
