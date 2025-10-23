<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Maneja la solicitud de inicio de sesión.
     */
    public function login(Request $request)
    {
        $request->validate([
            'usuario_correo' => 'required|email',
            'usuario_pass' => 'required',
        ]);

        // Intentamos encontrar al usuario por su correo
        $user = usuario::where('usuario_correo', $request->usuario_correo)->first();

        // Verificamos si el usuario existe y la contraseña es correcta
        if (! $user || ! Hash::check($request->usuario_pass, $user->usuario_pass)) {
            throw ValidationException::withMessages([
                'usuario_correo' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        // Creamos el token para el usuario
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => '¡Inicio de sesión exitoso!',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ]);
    }

    /**
     * Cierra la sesión del usuario (revoca el token).
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada exitosamente.'
        ]);
    }


}
?>