<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function redirectToProvider($provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    // Gestionar la devolución de llamada del proveedor
    public function handleProviderCallback($provider)
    {
        try {
            $user = Socialite::driver($provider)->stateless()->user();
            $this->loginOrRegisterUser($user, $provider);
            return redirect()->route('dashboard');
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors(['error' => 'Ha ocurrido un error al autenticar con ' . $provider]);
        }
    }

    // Método para manejar el registro o inicio de sesión
    protected function loginOrRegisterUser($socialUser, $provider)
    {
        // Busca al usuario por 'provider_id' y 'provider' y lo actualiza o crea si no existe.

        $user = User::where('provider_id', $socialUser->id)
            ->where('provider', $provider)
            ->first();

        // Si el usuario ya existe, actualiza los datos
        if ($user) {
            $user->update([
                'name' => $socialUser->name,
                'email' => $socialUser->email,
                'avatar' => $socialUser->avatar,
            ]);
        } else {
            // Si no existe, busca por el email
            $user = User::where('email', $socialUser->email)->first();

            // Si existe un usuario con el mismo email, asocia el proveedor social a este usuario
            if ($user) {
                $user->update([
                    'provider_id' => $socialUser->id,
                    'provider' => $provider,
                ]);
            } else {
                // Si no existe, crea un nuevo usuario
                $user = User::create([
                    'name' => $socialUser->name,
                    'email' => $socialUser->email,
                    'avatar' => $socialUser->avatar,
                    'provider_id' => $socialUser->id,
                    'provider' => $provider,
                ]);
            }
        }


        // Inicia sesión con el usuario encontrado o creado.
        Auth::login($user);
    }
}
