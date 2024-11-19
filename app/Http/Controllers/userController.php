<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log; // Agregar esta línea al inicio del archivo con los otros imports


class UserController extends Controller
{
    // Registro de usuario
    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'profile_picture' => 'nullable|image|mimes:jpg,png,jpeg|max:2048'
        ]);

        $validatedData['password'] = Hash::make($validatedData['password']);

        if ($request->hasFile('profile_picture')) {
            $uploadedFileUrl = Cloudinary::upload($request->file('profile_picture')->getRealPath(), [
                'folder' => 'UserImg-Agentlite'
            ])->getSecurePath();
            $validatedData['profile_picture'] = $uploadedFileUrl;
        }

        $user = User::create($validatedData);

        return response()->json(['message' => 'Registro exitoso', 'user' => $user], 201);
    }

    // Login de usuario
    public function login(Request $request)
    {
        try {
            $credentials = $request->only('email', 'password');

            if (!Auth::attempt($credentials)) {
                return response()->json(['message' => 'Credenciales incorrectas'], 401);
            }

            $token = $request->user()->createToken('authToken')->plainTextToken;

            return response()->json([
                'message' => 'Login exitoso',
                'token' => $token,
                'user' => Auth::user(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error durante el login', 'error' => $e->getMessage()], 500);
        }
    }


    // Solicitar restablecimiento de contraseña
    public function requestPasswordReset(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => __($status)])
            : response()->json(['message' => __($status)], 500);
    }

    // Restablecer contraseña
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => __($status)])
            : response()->json(['message' => __($status)], 500);
    }

    // Actualizar usuario
    public function update(Request $request, $userId)
    {
        try {
            // Agregar logs para debugging
            Log::info('Files in request:', $request->allFiles());
            Log::info('Has file?', ['has_file' => $request->hasFile('profile_picture')]);

            if ($request->hasFile('profile_picture')) {
                Log::info('File details:', [
                    'name' => $request->file('profile_picture')->getClientOriginalName(),
                    'size' => $request->file('profile_picture')->getSize(),
                    'mime' => $request->file('profile_picture')->getMimeType()
                ]);
            }

            // Validar datos
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'profile_picture' => 'nullable|image|mimes:jpg,png,jpeg|max:2048',
            ]);

            $user = User::find($userId);

            if (!$user) {
                return response()->json(['message' => 'Usuario no encontrado'], 404);
            }

            // Actualizar campos básicos
            $user->name = $validatedData['name'];
            $user->last_name = $validatedData['last_name'];
            $user->email = $validatedData['email'];

            // Manejar la imagen de perfil usando Cloudinary
            if ($request->hasFile('profile_picture')) {
                try {
                    Log::info('Intentando subir archivo a Cloudinary');
                    
                    // Subir archivo a Cloudinary y obtener la respuesta
                    $uploadedFile = Cloudinary::upload(
                        $request->file('profile_picture')->getRealPath(),
                        [
                            'folder' => 'UserImg-Agentlite',
                            'transformation' => [
                                'width' => 400,
                                'height' => 400,
                                'crop' => 'fill',
                                'gravity' => 'face'
                            ]
                        ]
                    );
            
                    // Verificar si `getSecurePath` devuelve una URL válida
                    $uploadedFileUrl = $uploadedFile->getSecurePath();
                    Log::info('Respuesta de Cloudinary', ['response' => $uploadedFile]);
            
                    if (!$uploadedFileUrl) {
                        throw new \Exception("Cloudinary no devolvió una URL válida para la imagen.");
                    }
            
                    Log::info('Archivo subido exitosamente a Cloudinary', ['url' => $uploadedFileUrl]);
            
                    // Asignar la URL si es válida
                    $user->profile_picture = $uploadedFileUrl;
                } catch (\Exception $e) {
                    Log::error('Error al subir a Cloudinary:', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    return response()->json([
                        'message' => 'Error al subir la imagen',
                        'error' => $e->getMessage()
                    ], 500);
                }
            }
            

            $user->save();

            return response()->json([
                'message' => 'Usuario actualizado exitosamente',
                'user' => $user,
            ]);
        } catch (\Exception $e) {
            Log::error('Error general en update:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Error al actualizar el usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
