<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Usuario;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request, AuthService $authService): JsonResponse
    {
        $ok = $authService->login($request, $request->string('usuario')->toString(), $request->string('password')->toString());

        if (!$ok) {
            return response()->json([
                'message' => 'Credenciales inválidas o usuario inactivo.',
            ], 422);
        }

        $request->session()->regenerate();

        return response()->json([
            'message' => 'Inicio de sesión exitoso.',
            'redirect' => route('dashboard'),
        ]);
    }

    public function loginMobile(LoginRequest $request, AuthService $authService): JsonResponse
    {
        $ok = $authService->login(
            $request,
            $request->string('usuario')->toString(),
            $request->string('password')->toString()
        );

        if (!$ok) {
            return response()->json([
                'ok' => false,
                'message' => 'Credenciales inválidas o usuario inactivo.',
            ], 422);
        }

        $request->session()->regenerate();

        return response()->json([
            'ok' => true,
            'message' => 'Inicio de sesión exitoso.',
            'usuario' => $request->string('usuario')->toString(),
        ]);
    }

    public function buscarUsuarios(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json(['data' => []]);
        }

        $data = Usuario::query()
            ->select(['usr_usuario', 'usr_nombre'])
            ->where('usr_estatus', 'activo')
            ->where(function ($query) use ($q): void {
                $query->where('usr_usuario', 'like', "%{$q}%")
                    ->orWhere('usr_nombre', 'like', "%{$q}%");
            })
            ->orderBy('usr_usuario')
            ->limit(10)
            ->get();

        return response()->json(['data' => $data]);
    }

    public function buscarUsuariosMobile(Request $request): JsonResponse
    {
        return $this->buscarUsuarios($request);
    }

    public function logout(Request $request, AuthService $authService)
    {
        $authService->logout($request);

        return redirect()->route('login');
    }
}
