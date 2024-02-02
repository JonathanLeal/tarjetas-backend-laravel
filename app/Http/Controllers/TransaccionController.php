<?php

namespace App\Http\Controllers;

use App\Models\Transaccion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TransaccionController extends Controller
{
    public function listarTransacciones(Request $request){
        $user = Auth::user();
    
        // Verificar si $user es nulo antes de usar su propiedad 'id'
        if (!$user) {
            return response()->json(['error' => 'Usuario no autenticado'], 401);
        }
    
        $transacciones = DB::table('transacciones as t')
                        ->join('users as u', 't.id_users', '=', 'u.id')
                        ->join('tarjeta as ts', 't.id_tarjeta', '=', 'ts.id')
                        ->where('t.dia', $request->dia)
                        ->where('t.id_users', $user->id)
                        ->select('t.id','u.name','t.monto','ts.numTarjeta','t.concepto','t.fecha','t.aignado_a')
                        ->get();
    
        if ($transacciones->isEmpty()) {
            return response()->json(['error' => 'No hay transacciones hechas para este día o usuario'], 404);
        }
        
        return response()->json($transacciones);
    }
    
}
