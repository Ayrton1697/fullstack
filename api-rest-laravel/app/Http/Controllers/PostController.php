<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Post;
use App\Helpers\JwtAuth;

class PostController extends Controller {

    public function __construct() {
        $this->middleware('api.auth', ['except' =>[
            
            'index',
            'show',
            'getImage',
            'getPostsByCategory',
            'getPostsByUser'
            
            ]]);
    }

    public function index() {
        $posts = Post::All()->load('category');

        return response()->json([
                    'code' => 200,
                    'status' => 'success',
                    'posts' => $posts
                        ], 200);
    }

    public function show($id) {
        $post = Post::find($id)->load('category')
                                ->load('user');
                       
        if (is_object($post)) {
            $data = array(
                'code' => 200,
                'status' => 'success',
                'post' => $post
            );
        } else {
            $data = array(
                'code' => 404,
                'status' => 'error',
                'message' => 'No existe la entrada'
            );
        }
        return response()->json($data, $data['code']);
    }

    public function store(Request $request) {
        //Recoger datos por post
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);

        //Conseguir el usuario identificado
        if (!empty($params_array)) {
            $user = $this->getIdentity($request);
        
        //Validar los datos
        $validate = \Validator::make($params_array, [
                    'title' => 'required',
                    'content' => 'required',
                    'category_id' => 'required',
                    'image' => 'required'
        ]);

        //Guardar el post
        if ($validate->fails()) {
            $data = array(
                'code' => 404,
                'status' => 'error',
                'message' => 'No se ha guardado el post, faltan datos'
            );
        } else {
            $post = new Post();
            $post->user_id = $user->sub;
            $post->category_id = $params->category_id;
            $post->title = $params->title;
            $post->content = $params->content;
            $post->image = $params->image;
            $post->save();

            $data = array(
                'code' => 200,
                'status' => 'success',
                'post' => $post
            );
        }
    }else{
        $data = array(
                'code' => 404,
                'status' => 'error',
                'message' => 'No se ha guardado el post, no llegaron los parametros'
            );
    }

        //Devolver la respuesta
        return response()->json($data, $data['code']);
    }

    public function update($id, Request $request) {
        //Recoger datos por post
        $json = $request->input('json', null);
        $params_array = json_decode($json, true);
        //Datos para devolver
        $data = array(
            'code' => 400,
            'status' => 'error',
            'message' => 'No se ha podido actualizar la entrada'
        );
        if (!empty($params_array)) {
            //Validar datos
            $validate = \Validator::make($params_array, [
                        'title' => 'required',
                        'content' => 'required',
                        'category_id' => 'required'
            ]);
            //Eliminar datos que no queremos actualizar
            if ($validate->fails()) {
                $data['errors'] = $validate->errors();
                return response()->json($data, $data['code']);
            }
            unset($params_array['id']);
            unset($params_array['user_id']);
            unset($params_array['created_at']);
            unset($params_array['user']);

            //Conseguir usuario
            
            $user = $this->getIdentity($request);
            

            //Buscar el registro
            $post = Post::where('id', $id)
                    ->where('user_id', $user->sub)
                    ->first();
            //var_dump($post->user_id);

            if (!empty($post) && is_object($post)) {
                //Actualizar el registro
                $post->update($params_array);
               
                //Devolver datos actualizados
                $data = array(
                    'code' => 200,
                    'status' => 'success',
                    'post' => $post,
                    'changes' => $params_array
                );
            }
            /*
             *  //Condiciones (updateorCreate solo acepta UN where)
              $where = [
              'id' => $id,
              'user_id' => $user->sub
              ];

              $post = Post::updateOrCreate($where, $params_array); */
        }
        return response()->json($data, $data['code']);
    }

    public function destroy($id, Request $request) {

        //Conseguir usuario identificao
        $user = $this->getIdentity($request);
        //Conseguir el registro
        $post = Post::where('id', $id)
                ->where('user_id', $user->sub)
                ->first();

        if (!empty($post)) {
            //Borrar el registro
            $post->delete();
            $data = [
                'code' => 200,
                'status' => 'success',
                'post' => $post
            ];
        } else {
            $data = array(
                'code' => 404,
                'status' => 'error',
                'message' => 'No se ha podido eliminar el post'
            );
        }


        return response()->json($data, $data['code']);
    }

    private function getIdentity($request) {
        $jwtauth = new JwtAuth();
        $token = $request->header('Authorization', null);
        $user = $jwtauth->checkToken($token, true);

        return $user;
    }

    public function upload(Request $request) {
        //Recoger la imagen de la peticion
        $image = $request->file('file0');
        //Validar la imagen
        $validate = \Validator::make($request->all(), [
                    'file0' => 'required|image|mimes:jpg,jpeg,png,gif'
        ]);
        //Guardar la imagen
        if (!$image || $validate->fails()) {
            $data = array(
                'code' => 404,
                'status' => 'error',
                'message' => 'No se ha podido guardar la imagen'
            );
        } else {
            $image_name = time() . $image->getClientOriginalName();
            \Storage::disk('images')->put($image_name, \File::get($image));
            $data = [
                'code' => 200,
                'status' => 'success',
                'image' => $image_name
            ];
        }
        //Devolver datos
        return response()->json($data, $data['code']);
    }

    public function getImage($filename) {
        //Comprobar si existe el fichero
        $isset = \Storage::disk('images')->exists($filename);
        if ($isset) {
            //Conseguir la imagen
            $file = \Storage::disk('images')->get($filename);
            //Devolver la imagen
            return new Response($file, 200);
        } else {
            //Mostar error
            $data = array(
                'code' => 404,
                'status' => 'error',
                'message' => 'No existe la imagen'
            );
        }
        return response()->json($data, $data['code']);
    }

    public function getPostsByCategory($id) {
        $posts = Post::where('category_id', $id)->get();

        return response()->json([
                    'status' => 'success',
                    'posts' => $posts
                        ], 200);
    }

    public function getPostsByUser($id) {
        $posts = Post::where('user_id', $id)->get();

        return response()->json([
                    'status' => 'success',
                    'posts' => $posts
                        ], 200);
    }

}
