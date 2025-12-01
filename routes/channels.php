<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('solicitudes', function ($user) {
    //return $user->rol->rol_nombre === 'Administrador' || $user->rol->rol_nombre === 'Gestor';
    return true;
});
// Autorización para ver actualizaciones de una oficina
Broadcast::channel('oficina.{id}', function ($user, $id) {
    // Aquí puedes validar si el usuario pertenece a esa oficina o tiene permisos
    // Por ahora, retornamos true si está logueado
    return true; // $user->oficina_id == $id; <--- Idealmente
});