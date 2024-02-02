<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {   
        $validator = Validator::make(request()->all(), [
            'email' => 'required|email',
            'password'=> 'required|string'
        ]);

        if ($validator->fails()){
            return response()->json(['validacion' => $validator->errors()->first()],422);
        }

        $credentials = request(['email', 'password']);

        if (! $token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth()->user());
    }

    public function editarUsuario(Request $request){
        $user = Auth::user();
        $validator = Validator::make(request()->all(), [
            'name' => 'required|string',
            'email' => 'required|email'
        ]);
        
        if ($validator->fails()){
            return response()->json($validator->errors(), 422);
        }

        DB::beginTransaction();
        try {
            $user->name = $request->name;
            $user->email = $request->email;
            $user->save();
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['error'=> $th->getMessage()], 500);
        }
        DB::commit();
        return response()->json(['mensaje'=> 'usuario editado con exito']);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }
    
    public function register(Request $request){
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string',
            'apellido' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['error'=> $validator->errors()], 422);
        }

        $emailEncontrado = User::where('email', $request->email)->first();
        if ($emailEncontrado) {
            return response()->json(['validacion' => 'ese correo electronica ya esta registrado'], 400);
        }

        DB::beginTransaction();
        try {
            $user = new User();
            $user->name = $request->nombre . " " . $request->apellido;
            $user->email = $request->email;
            $user->password = bcrypt($request->password);
            $user->save();
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['error servidor' => $th->getMessage()], 500);
        }
        DB::commit();
        return response()->json(['mensaje' => 'usuario registrado con exito']);
    }
}