<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CORS Paths
    |--------------------------------------------------------------------------
    |
    | Aquí puedes definir las rutas que deben habilitar CORS. Puedes usar
    | comodines como 'api/*'. Dejarlo como ['*'] lo habilita para todo.
    |
    */

    'paths' => ['api/*'],

    /*
    |--------------------------------------------------------------------------
    | Allowed Methods
    |--------------------------------------------------------------------------
    |
    | Especifica qué métodos HTTP están permitidos. Puedes usar ['*']
    | para permitirlos todos.
    |
    */

    'allowed_methods' => ['*'],

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins
    |--------------------------------------------------------------------------
    |
    | Define qué orígenes (dominios) pueden hacer peticiones.
    | Aquí es donde debes poner la URL de tu app de Vue.
    |
    */

    'allowed_origins' => [
        env('FRONTEND_URL', 'http://localhost:5174'), 
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins Patterns
    |--------------------------------------------------------------------------
    |
    | Puedes usar expresiones regulares para orígenes dinámicos.
    |
    */

    'allowed_origins_patterns' => [],

    /*
    |--------------------------------------------------------------------------
    | Allowed Headers
    |--------------------------------------------------------------------------
    |
    | Define qué encabezados HTTP se permiten en la petición.
    |
    */

    'allowed_headers' => [
        'Content-Type',
        'Accept',
        'Authorization', // <-- ¡ASEGÚRATE QUE ESTÉ AQUÍ!
        'X-Requested-With',
    ],

    /*
    |--------------------------------------------------------------------------
    | Exposed Headers
    |--------------------------------------------------------------------------
    |
    | Define qué encabezados se pueden exponer al navegador.
    |
    */

    'exposed_headers' => [],

    /*
    |--------------------------------------------------------------------------
    | Max Age
    |--------------------------------------------------------------------------
    |
    | Define el tiempo (en segundos) que la respuesta 'pre-flight' (OPTIONS)
    | puede ser cacheada por el navegador.
    |
    */

    'max_age' => 0,

    /*
    |--------------------------------------------------------------------------
    | Supports Credentials
    |--------------------------------------------------------------------------
    |
    | Define si tu API permite credenciales (cookies, sesiones)
    | desde otro dominio.
    |
    */

    'supports_credentials' => false,

];