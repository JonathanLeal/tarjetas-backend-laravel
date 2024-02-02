<?php

namespace App\Http\Controllers;

use App\Models\Control;
use App\Models\Gasto;
use App\Models\Ingreso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ControlesController extends Controller
{
    public function listarControl($mes){
        $user = Auth::user();
        $control = DB::table("control as c")
                   ->join("ingresos as i","c.id_ingreso","=","i.id")
                   ->join("gastos as g","c.id_gastos","=","g.id")
                   ->join("users as u","c.id_suario","=","u.id")
                   ->where("c.id_suario", $user->id)
                   ->where("c.mes", $mes)
                   ->select("c.id", "c.mes", "i.monto", "i.fecha_ingreso", "g.monto", "g.fecha_gasto", "g.tipo_gasto")
                   ->get();
        
        return response()->json($control);
    }

    public function insertarControl(Request $request){
        $user = Auth::user();
        $validator = Validator::make($request->all(), [
            "montoGasto"=> "required|numeric",
            "montoIngreso" => "required|numeric",
            "fechaIngreso" => "required|date",
            "fechaGasto" => "required|date",
            "mes" => "required|string",
            "tipoGasto" => "required|string"
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        DB::beginTransaction();
        try {
            $gasto = new Gasto();
            $gasto->tipo_gasto = $request->tipo_gasto;
            $gasto->monto = $request->montoGasto;
            $gasto->fecha_gasto = $request->fechaGasto;
            $gasto->save();

            $ingreso = new Ingreso();
            $ingreso->monto = $request->montoIngreso;
            $ingreso->fecha_ingreso = $request->fechaIngreso;
            $ingreso->save();

            $control = new Control();
            $control->id_gasto = $gasto->id;
            $control->id_ingreso = $ingreso->id;
            $control->id_suario = $user->id;
            $control->mes = $request->mes;
            $control->save();
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json($th->getMessage(), 500);
        }
        DB::commit();
        return response()->json(['mensaje' => 'ingresado con exito']);
    }
}
