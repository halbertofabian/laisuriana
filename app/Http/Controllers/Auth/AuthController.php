<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
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
            'redirect' => route('desktop.dashboard'),
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

        $data = app(AuthService::class)->buscarUsuariosActivos($q);

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
