<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\User;

class UserController extends Controller {

    public function register(Request $request) {
        //Recoger datos del usuario
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);

        //Limpiar datos

        $params_array = array_map('trim', $params_array);

        //Validar datos user
        if (!empty($params_array) && !empty($params)) {
            $validate = \Validator::make($params_array, [
                        'name' => 'required|alpha',
                        'surname' => 'required|alpha',
                        'email' => 'required|email|unique:users', //Comprobar si user ya existe
                        'password' => 'required'
            ]);

            if ($validate->fails()) {
                $data = array(
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'No se ha creado el usuario',
                    'errors' => $validate->errors()
                );
            } else {

                //Cifrar password (algoritmo de cifrado sha256 es bastante seguro)

                $pwd = hash('sha256', $params->password);

                //Crear user

                $user = new User();
                $user->name = $params_array['name'];
                $user->surname = $params_array['surname'];
                $user->email = $params_array['email'];
                $user->password = $pwd;
                $user->role = 'ROLE_USER';

                $user->save();

                $data = array(
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'Se ha creado el usuario correctamente',
                    'user' => $user
                );
            }
        } else {
            $data = array(
                'status' => 'success',
                'code' => 200,
                'message' => 'Los datos enviados no son correctos'
            );
        }

        return response()->json($data, $data['code']);
    }

    public function login(Request $request) {

        $JwtAuth = new \JwtAuth();

        //Recibir datos por post
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);

        //Validarlos
        if (!empty($params_array) && !empty($params)) {
            $validate = \Validator::make($params_array, [
                        'email' => 'required|email', //Comprobar si user ya existe
                        'password' => 'required'
            ]);

            if ($validate->fails()) {
                $signup = array(
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'El usuario no se ha podido identificar',
                    'errors' => $validate->errors()
                );
            } else {

                //Cifrar la password

                $pwd = hash('SHA256', $params->password);

                //Devolver token
                $signup = $JwtAuth->signup($params->email, $pwd);

                if (!empty($params->getToken)) {
                    $signup = $JwtAuth->signup($params->email, $pwd, true);
                }
            }
        }

        return response()->json($signup, 200);
    }

    public function update(Request $request) {

        //Comprobar si usuario esta identificado

        $token = $request->header('Authorization');
        $jwtAuth = new \JwtAuth();
        $checkToken = $jwtAuth->checkToken($token);

        $json = $request->input('json', null);
        $params_array = json_decode($json, true);

        if ($checkToken && !empty($params_array)) {

            //Recoger datos por post
            //Sacar usuario identificado

            $user = $jwtAuth->checkToken($token, true);

            //Validar datos
            $validate = \Validator::make($params_array, [
                        'name' => 'required|alpha',
                        'surname' => 'required|alpha',
                        'email' => 'required|email|unique:users,' . $user->sub
            ]);

            //Quitar campos que no quiero actualizar
            unset($params_array['id']);
            unset($params_array['role']);
            unset($params_array['password']);
            unset($params_array['created_at']);
            unset($params_array['remember_token']);

            //Actualizar usuario
            $user_update = User::where('id', $user->sub)->update($params_array);

            //Devolver array
            $data = array(
                'code' => 200,
                'status' => 'success',
                'user' => $user,
                'changes' => $params_array
            );
        } else {
            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'El usuario no esta identificado'
            );
        }
        return response()->json($data, $data['code']);
    }

    public function upload(Request $request) {

        //Recoger los datos
        $image = $request->file('file0');

        $validate = \Validator::make($request->all(), [
                    'file0' => 'required|image|mimes:jpg,jpeg,png,gif'
        ]);

        //Subir y guardar imagen
        if (!$image || $validate->fails()) {
            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'Error al subir imagen'
            );
        } else {
            $image_name = time() . $image->getClientOriginalName();
            \Storage::disk('users')->put($image_name, \File::get($image));

            $data = array(
                'image' => $image_name,
                'code' => 200,
                'status' => 'success'
            );
        }



        return response()->json($data, $data['code']);
    }

    public function getImage($filename) {

        $isset = \Storage::disk('users')->exists($filename);
        if ($isset) {
            $file = \Storage::disk('users')->get($filename);
            return new Response($file, 200);
        } else {
            $data = array(
                'code' => 404,
                'status' => 'error',
                'message' => 'La imagen no existe'
            );
        }

        return response()->json($data, $data['code']);
    }

    public function detail($id) {
        $user = User::find($id);
        if (is_object($user)) {
            $data = array(
                'code' => 200,
                'status' => 'success',
                'user' => $user
            );
        }else{
              $data = array(
                'code' => 404,
                'status' => 'error',
                'message' => 'El usuario no existe'
            );
        }
        return response()->json($data, $data['code']);
    }

}
