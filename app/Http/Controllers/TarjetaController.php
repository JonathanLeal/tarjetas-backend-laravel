<?php

namespace App\Http\Controllers;

use App\Models\Tarjeta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TarjetaController extends Controller
{
    public function verTarjetas(){
        $user = Auth::user();
        
        $tarjetas = DB::table("tarjeta as t")
                    ->join("users as u","t.id_usuario","=","u.id")
                    ->where("t.id_usuario", $user->id)
                    ->select("t.id","t.monto","t.banco","t.cvv","t.numTarjeta","t.fechaVencimiento","u.name")
                    ->get();
        if (count($tarjetas) <= 0) {
            return response()->json(['mensaje', 'este usuario no tiene tarjetas', 404]);
        }

        return response()->json($tarjetas);
    }

    public function transaccionesPorTarjeta(Request $request){
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'numTarjeta' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['validacion', $validator->errors()], 422);
        }

        $transacciones = DB::table('transacciones as t')
                        ->join('users as u', 't.id_users', '=', 'u.id')
                        ->join('tarjeta as ts', 't.id_tarjeta', '=', 'ts.id')
                        ->where('ts.numTarjeta', $request->numTarjeta)
                        ->where('ts.id_usuario', $user->id)
                        ->select('t.id','u.name','t.monto','ts.numTarjeta','t.concepto','t.fecha','t.aignado_a','t.dia')
                        ->get();
    
        if ($transacciones->count() <= 0) {
            return response()->json(['mensaje' => 'no hay tarjetas por ese usuario ni por ese numero'], 404);
        }
        
        return response()->json($transacciones);
    }

    public function transacciones(Request $request){
        $user = Auth::user();

        $transacciones = DB::table('transacciones as t')
                        ->join('users as u', 't.id_users', '=', 'u.id')
                        ->join('tarjeta as ts', 't.id_tarjeta', '=', 'ts.id')
                        ->where('ts.id_usuario', $user->id)
                        ->select('t.id','u.name','ts.monto','ts.numTarjeta','t.concepto','t.fecha','t.aignado_a','t.dia')
                        ->get();
    
        if ($transacciones->count() <= 0) {
            return response()->json(['mensaje' => 'no hay tarjetas por ese usuario ni por ese numero']);
        }
        
        return response()->json($transacciones);
    }

    public function insertarTarjeta(Request $request){
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'monto'       => 'required|numeric',
            'banco'       => 'required|string',
            'cvv'         => 'required|string',
            'vencimiento' => 'required|string',
            'numTarjeta'  => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['mensaje'=> $validator->errors()], 422);
        }

        $numEncontrado = Tarjeta::where('numTarjeta', $request->numTarjeta)->first();
        $cvvEncontrado = Tarjeta::where('cvv', $request->cvv)->first();
        if ($numEncontrado || $cvvEncontrado) {
            return response()->json(['mensaje'=> 'numero de tarjeta o cvv ya registrado'], 400);
        }

        DB::beginTransaction();
        try {
            $tarjeta = new Tarjeta();
            $tarjeta->monto            = $request->monto;
            $tarjeta->banco            = $request->banco;
            $tarjeta->id_usuario       = $user->id;
            $tarjeta->cvv              = $request->cvv;
            $tarjeta->fechaVencimiento = $request->vencimiento;
            $tarjeta->numTarjeta       = $request->numTarjeta;
            $tarjeta->save();
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['error'=> $th->getMessage()], 500);
        }
        DB::commit();
        return response()->json(['mensaje' => 'tarjeta de credita insertada con exito']);
    }

    public function seleccionarTarjeta($id){
        $tarjeta = Tarjeta::find($id);
        if ($tarjeta) {
            return response()->json(['mensaje'=> 'no se encontro la tarjeta'], 400);
        }
        return response()->json($tarjeta);
    }
    public function editarTarjeta(Request $request, $id){
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'monto'       => 'required|numeric',
            'banco'       => 'required|string',
            'cvv'         => 'required|string',
            'vencimiento' => 'required|string',
            'numTarjeta'  => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['mensaje'=> $validator->errors()], 422);
        }

        $tarjetaEncontrada = Tarjeta::where('id', $id)->first();
        if (!$tarjetaEncontrada) {
            return response()->json(['mensaje'=> 'tarjeta no encontrada'], 400);
        }

        DB::beginTransaction();
        try {
            $tarjetaEncontrada->monto            = $request->monto;
            $tarjetaEncontrada->banco            = $request->banco;
            $tarjetaEncontrada->id_usuario       = $user->id;
            $tarjetaEncontrada->cvv              = $request->cvv;
            $tarjetaEncontrada->fechaVencimiento = $request->vencimiento;
            $tarjetaEncontrada->numTarjeta       = $request->numTarjeta;
            $tarjetaEncontrada->save();
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['error'=> $th->getMessage()], 500);
        }
        DB::commit();
        return response()->json(['mensaje' => 'tarjeta de credita editado con exito']);
    }

    public function eliminarTarjeta($id){
        $user = Auth::user();
        $tarjeta = Tarjeta::where('id_usuario', $user->id)->where('id', $id)->first();
        if(!$tarjeta){
            return response()->json(['error'=> 'no existe esa tarjeta'],404);
        }

        $transaccioExistente = DB::table('transacciones as t')
                               ->join('tarjeta as ts', 't.id_tarjeta', '=', 'ts.id')
                               ->where('t.id_tarjeta', $id)
                               ->select('t.id')
                               ->first();
        
        if ($transaccioExistente) {
            return response()->json(['mensaje' => 'no puede eliminar la tarjeta porque tiene transacciones hechas'], 400);
        }
        $tarjeta->delete();
        return response()->json(['mensaje'=> 'tarjeta eliminada con exito']);
    }
}
