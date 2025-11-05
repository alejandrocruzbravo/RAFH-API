<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('solicitudes', function ($user) {
    //return $user->rol->rol_nombre === 'Administrador' || $user->rol->rol_nombre === 'Gestor';
    return true;
});
