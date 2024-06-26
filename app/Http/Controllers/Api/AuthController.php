<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Services\GoogleClientService;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;


class AuthController extends Controller
{

    protected $googleClientService;

    public function __construct(GoogleClientService $googleClientService)
    {
        $this->googleClientService = $googleClientService;
    }
    
    public function login(Request $request)
    {
        $credentials = $request->only(['email', 'password']);

        auth('api')->factory()->setTTL(43200);

        try {
            if (!$token = auth('api')->attempt($credentials)) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            // Obtén el usuario autenticado
            $user = auth('api')->user();

            // Verifica si el usuario ha completado la autenticación con Google
            $requireGoogleAuth = !$user->is_google_auth_completed;

            // Envía la respuesta al cliente con la información del token y si se necesita autenticación de Google
            return response()->json([
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60, // segundos
                'require_google_auth' => $requireGoogleAuth // Indica si se necesita autenticación de Google
            ]);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not create token'], 500);
        }
    }

    public function me()
    {
        $user = auth('api')->user();
        $this->googleClientService->refreshToken($user);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_google_auth_completed' => $user->is_google_auth_completed,
            'google_access_token' => $user->google_access_token,
            'google_token_expires_at' => $user->google_token_expires_at,
        ]);
    }

    public function logout()
    {

        auth('api')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function refresh()
    {
        try {
            return $this->respondWithToken(auth('api')->refresh());
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not refresh token'], 401);
        }
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL()
        ]);
    }
}
