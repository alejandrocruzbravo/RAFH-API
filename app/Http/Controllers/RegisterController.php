<?php

namespace App\Http\Controllers;
use App\Models\usuario;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

use Illuminate\Http\Request;

class RegisterController extends Controller
{
    //
        public function register(Request $req)  {
            //valida que el token sea de un admin para poder usar el controller
            abort_if(!($req->user()->isAdmin()),403,"Acceso Denegado");
            // return response()->json(['data'=>'entro por que es admin']);
            //validaciones requeridas de la peticion
                try{
                    $validacion = $req->validate([
                    'usuario_nombre'=>'required',
                    'usuario_correo'=>'required|email',
                    'usuario_pass'=>'required',
                    'usuario_rol'=>['required',Rule::notIn(['1'])]//tiene que ser un rol diferente al de admin para poder ingresar un nuevo usuario
                    ]);

                //creacion de usuario 
                $user = usuario::create([
                    'usuario_nombre'=>$validacion['usuario_nombre'],
                    'usuario_correo'=>$validacion['usuario_correo'],
                    'usuario_pass'=>Hash::make($validacion['usuario_pass']),
                    'usuario_id_rol'=>$validacion['usuario_rol']
                ]);

                return response()->json(['mensaje'=>"Registrado Correctamente"]);
            }catch (ValidationException $e){
                return  response()->json([
                    "success"=>false,
                    "mensaje"=>"Error en la peticion",
                ],422);
            }

        
    }
}
