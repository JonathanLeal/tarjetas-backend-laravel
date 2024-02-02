<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TarjetaController;
use App\Http\Controllers\TransaccionController;
use App\Http\Controllers\TransferenciaController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group([

    'middleware' => 'api',
    'prefix' => 'auth'

], function ($router) {

    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('me', [AuthController::class, 'me']);
    Route::post('register', [AuthController::class, 'register']);

    Route::post('verTransacciones', [TransaccionController::class,'listarTransacciones']);

    Route::post('tarjetas', [TarjetaController::class, 'verTarjetas']);
    Route::post('tarjetasTransacciones', [TarjetaController::class, 'transaccionesPorTarjeta']);
    Route::post('transaccionesCompletas', [TarjetaController::class, 'transacciones']);
    Route::post('inserTarjeta', [TarjetaController::class, 'insertarTarjeta']);
    Route::post('editarTarjeta/{id}', [TarjetaController::class, 'editarTarjeta']);
    Route::post('tar/{id}', [TarjetaController::class, 'seleccionarTarjeta']);
    Route::post('eliminarTarjeta/{id}', [TarjetaController::class, 'eliminarTarjeta']);

    Route::get('selTarjetas', [TransferenciaController::class, 'obtenerTarjetas']);
    Route::post('hacerTransferencia', [TransferenciaController::class, 'realizarTransferencias']);
    Route::post('user', [AuthController::class, 'editarUsuario']);
});
