<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PerfilController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user();

        // 1. Validaciones Generales
        $request->validate([
            'usuario_nombre' => 'required|string|max:255',
            // Validamos que el email sea único, EXCEPTO para este usuario
            'usuario_correo' => 'required|email|unique:usuarios,usuario_correo,' . $user->id, 
        ]);

        // 2. Lógica de Cambio de Contraseña
        if ($request->filled('current_password')) {
            $request->validate([
                'current_password' => 'required|current_password', // Laravel verifica que coincida con la actual
                'new_password' => ['required', 'confirmed', Password::defaults()], // 'confirmed' busca new_password_confirmation
            ]);

            // Actualizamos el password hasheado
            $user->usuario_pass = Hash::make($request->new_password);
        }

        // 3. Actualizar datos básicos
        $user->usuario_nombre = $request->usuario_nombre; // O el nombre de la columna en tu BD (ej. usuario_nombre)
        $user->usuario_correo = $request->usuario_correo; // O el nombre de la columna en tu BD (ej. usuario_correo)
        $user->save();

        return response()->json([
            'message' => 'Perfil actualizado correctamente.',
            'user' => $user
        ]);
    }
}