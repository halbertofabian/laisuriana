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
        if ($this->routeIs('pos.caja.retiros.store')) {
            $this->merge(['tipo' => 'retiro']);
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
        return [
            'tipo' => ['nullable', Rule::in(['retiro', 'gasto'])],
            'monto' => ['required', 'numeric', 'gt:0'],
            'categoria' => ['nullable', 'string', 'max:120'],
            'referencia' => ['nullable', 'string', 'max:180'],
            'motivo' => ['nullable', 'string', 'max:2000'],
            'autoriza_usr_id' => ['nullable', 'integer'],
            'autoriza_password' => ['nullable', 'string', 'max:255'],
        ];
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
        ];
    }
}
