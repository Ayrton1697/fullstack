<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Category;

class CategoryController extends Controller {

    public function __construct() {
        $this->middleware('api.auth', ['except' => ['index', 'show']]);
    }

    public function index() {
        $categories = Category::all();

        return response()->json([
                    'categories' => $categories,
                    'code' => 200,
                    'status' => 'success'
        ]);
    }

    public function show($id) {

        $category = Category::find($id);

        if (is_object($category)) {
            $data = array(
                'category' => $category,
                'code' => 200,
                'status' => 'success'
            );
        } else {
            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'No existe la categoria'
            );
        }

        return response()->json($data, $data['code']);
    }

    public function store(Request $request) {
        //Recoger datos por post
        $json = request()->input('json', null);
        $params_array = json_decode($json, true);

        //Validar datos
        if (!empty($params_array)) {


            $validate = \Validator::make($params_array, [
                        'name' => 'required'
            ]);
            //Guardar la categoria
            if ($validate->fails()) {
                $data = array(
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'Error al guardar la categoria'
                );
            } else {
                $category = new Category();
                $category->name = $params_array['name'];
                $category->save();

                $data = array(
                    'code' => 200,
                    'status' => 'success',
                    'category' => $category
                );
            }
        } else {
            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'No has enviado ninguna categoria'
            );
        }

        //Devolver resultado
        return response()->json($data, $data['code']);
    }

    public function update($id, Request $request) {

        //Recoger datos por post
        $json = $request->input('json', null);
        $params_array = json_decode($json, true);

        if (!empty($params_array)) {

            //Validarlos
            $validate = \Validator::make($params_array, [
                        'name' => 'required'
            ]);
            //Quitar lo que no se quiere actualizar
            unset($params_array['id']);
            unset($params_array['created_at']);

            //Actualizar el registro
            $category = Category::where('id', $id)->update($params_array);

            //Devolver los datos
            $data = [
                'code' => 200,
                'status' => 'success',
                'category' => $params_array
            ];
        } else {
            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'No has enviado ninguna categoria'
            );
        }
        return response()->json($data, $data['code']);
    }

}
