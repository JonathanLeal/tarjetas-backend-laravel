<?php

namespace App\Http\Controllers;

use App\Models\Tarjeta;
use App\Models\Transaccion;
use App\Models\Transferencia;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TransferenciaController extends Controller
{
    public function obtenerTarjetas(){
        $user = Auth::user();
        $tarjetas = DB::table("tarjeta as t")
                    ->where("t.id_usuario", $user->id)
                    ->select("t.id", "t.numTarjeta")
                    ->get();
        return response()->json($tarjetas);
    }

    public function realizarTransferencias(Request $request){
        $user = Auth::user();
        $validator = Validator::make($request->all(), [
            "tarjeta"                => "required|integer",
            "concepto"               => "required|string",
            "beneficiario"           => "required|string",
            "numTarjetaBeneficiario" => "required|string",
            "diaTransaccion"         => "required|string",
            "monto"                  => "required|numeric"
        ]);

        if ($validator->fails()) {
            return response()->json(['validaciones' => $validator->errors()], 422);
        }

        $fechaActual = Carbon::now();

        $tarjetaEncontrada = Tarjeta::where('id', $request->tarjeta)->first();

        if (!$tarjetaEncontrada) {
            return response()->json(['validacion' => 'La tarjeta beneficiaria no fue encontrada'], 404);
        }

        if ($request->monto > $tarjetaEncontrada->monto) {
            return response()->json(['validacion' => 'No cuenta con ese monto para transferir'], 400);
        }

        DB::beginTransaction();
        try {
            $transferencia = new Transferencia();
            $transferencia->id_tarjeta             = $tarjetaEncontrada->id;
            $transferencia->montoTransferido       = $request->monto;
            $transferencia->fecha                  = $fechaActual;
            $transferencia->numTarjetaBeneficiario = $request->numTarjetaBeneficiario;
            $transferencia->diaTransferencia       = $request->diaTransaccion;
            $transferencia->concepto               = $request->concepto;
            $transferencia->id_user                = $user->id;
            $transferencia->benficiario           = $request->beneficiario;
            $transferencia->save();

            $transaccion = new Transaccion();
            $transaccion->fecha            = $transferencia->fecha;
            $transaccion->monto            = $transferencia->montoTransferido;
            $transaccion->concepto         = $transferencia->concepto;
            $transaccion->id_users         = $transferencia->id_user;
            $transaccion->dia              = $transferencia->diaTransferencia;
            $transaccion->aignado_a        = $transferencia->benficiario;
            $transaccion->id_tarjeta       = $transferencia->id_tarjeta;
            $transaccion->id_transferencia = $transferencia->id;
            $transaccion->save();

            $tarjetaEncontrada->monto -= $request->monto;
            $tarjetaEncontrada->save();
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['error'=> $th->getMessage()],500);
        }
        DB::commit();
        return response()->json(['mensaje'=> 'Transferencia realizada con exito']);
    }
}
