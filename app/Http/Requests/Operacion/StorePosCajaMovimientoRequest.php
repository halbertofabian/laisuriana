<?php

namespace App\Http\Requests\Operacion;

use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class StorePosCajaMovimientoRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $denominaciones = (array) $this->input('denominaciones', []);

        foreach (['1000', '500', '200', '100', '50', '20', '10', '5', '2', '1', '0_50'] as $clave) {
            $valor = $denominaciones[$clave] ?? 0;
            $denominaciones[$clave] = ($valor === '' || $valor === null) ? 0 : $valor;
        }

        if ($this->routeIs('pos.caja.retiros.store')) {
            $this->merge(['tipo' => 'retiro', 'denominaciones' => $denominaciones]);
            return;
        }

        if ($this->routeIs('pos.caja.gastos.store')) {
            $this->merge(['tipo' => 'gasto']);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'tipo' => ['nullable', Rule::in(['retiro', 'gasto'])],
            'monto' => ['required', 'numeric', 'gt:0'],
            'categoria' => ['nullable', 'string', 'max:120'],
            'referencia' => ['nullable', 'string', 'max:180'],
            'motivo' => ['nullable', 'string', 'max:2000'],
            'autoriza_usr_id' => ['nullable', 'integer'],
            'autoriza_password' => ['nullable', 'string', 'max:255'],
        ];

        if ($this->routeIs('pos.caja.retiros.store') || $this->input('tipo') === 'retiro') {
            $rules['denominaciones'] = ['required', 'array'];
            foreach (['1000', '500', '200', '100', '50', '20', '10', '5', '2', '1', '0_50'] as $clave) {
                $rules["denominaciones.{$clave}"] = ['required', 'integer', 'min:0'];
            }
        }

        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $tipo = (string) $this->input('tipo');
            $categoria = trim((string) $this->input('categoria', ''));

            if ($tipo === 'gasto' && $categoria === '') {
                $validator->errors()->add('categoria', 'Debes capturar la categoría o concepto del gasto.');
            }

            if ($tipo !== 'retiro') {
                return;
            }

            $valores = [
                '1000' => 1000,
                '500' => 500,
                '200' => 200,
                '100' => 100,
                '50' => 50,
                '20' => 20,
                '10' => 10,
                '5' => 5,
                '2' => 2,
                '1' => 1,
                '0_50' => 0.5,
            ];
            $totalDenominaciones = 0.0;
            foreach ($valores as $clave => $valor) {
                $totalDenominaciones += (int) $this->input("denominaciones.{$clave}", 0) * (float) $valor;
            }
            $monto = (float) $this->input('monto', 0);

            if (abs($totalDenominaciones - $monto) > 0.001) {
                $validator->errors()->add('denominaciones', 'El total de las denominaciones debe coincidir con el monto del retiro.');
            }

            $autorizaUsrId = (int) $this->input('autoriza_usr_id', 0);
            $autorizaPassword = (string) $this->input('autoriza_password', '');

            if ($autorizaUsrId <= 0) {
                $validator->errors()->add('autoriza_usr_id', 'Selecciona el usuario que autoriza el retiro.');
                return;
            }

            if (trim($autorizaPassword) === '') {
                $validator->errors()->add('autoriza_password', 'Captura la contraseña del usuario autorizado.');
                return;
            }

            $usuarioAutorizado = Usuario::query()
                ->where('usr_id', $autorizaUsrId)
                ->where('usr_estatus', 'activo')
                ->where('usr_deleted', false)
                ->whereNull('usr_deleted_at')
                ->first();

            if (!$usuarioAutorizado || !$usuarioAutorizado->tienePermiso('pos.retiro_caja')) {
                $validator->errors()->add('autoriza_usr_id', 'El usuario seleccionado no puede autorizar retiros de caja.');
                return;
            }

            if (!Hash::check($autorizaPassword, (string) $usuarioAutorizado->usr_password)) {
                $validator->errors()->add('autoriza_password', 'La contraseña capturada no coincide con el usuario autorizado.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'tipo.required' => 'El tipo de movimiento es obligatorio.',
            'tipo.in' => 'El tipo de movimiento no es válido.',
            'monto.required' => 'El monto es obligatorio.',
            'monto.numeric' => 'El monto debe ser numérico.',
            'monto.gt' => 'El monto debe ser mayor a cero.',
            'denominaciones.required' => 'Captura las denominaciones que se retirarán de caja.',
            'denominaciones.array' => 'Las denominaciones enviadas no son válidas.',
            'denominaciones.*.integer' => 'La cantidad de piezas debe ser un número entero.',
            'denominaciones.*.min' => 'La cantidad de piezas no puede ser negativa.',
        ];
    }
}
