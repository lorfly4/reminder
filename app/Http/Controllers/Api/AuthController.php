<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\Category;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request): User {
            $user = User::create([
                'name' => $request->string('name')->toString(),
                'email' => Str::lower($request->string('email')->toString()),
                'password' => Hash::make($request->string('password')->toString()),
            ]);

            $defaults = [
                ['Personal', '#6366F1'], ['Work', '#3B82F6'], ['Education', '#8B5CF6'],
                ['Finance', '#10B981'], ['Health', '#EF4444'], ['Meeting', '#F59E0B'],
                ['Other', '#64748B'],
            ];
            foreach ($defaults as [$name, $color]) {
                $user->categories()->create(['name' => $name, 'color' => $color]);
            }
            return $user;
        });

        Auth::login($user);
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }
        return $this->success($user, 'Registration successful');
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = [
            'email' => Str::lower($request->string('email')->toString()),
            'password' => $request->string('password')->toString(),
        ];
        if (! Auth::attempt($credentials, true)) {
            return response()->json(['message' => 'Email atau password tidak valid.', 'errors' => ['email' => ['Kredensial tidak cocok.']]], 422);
        }
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }
        return $this->success($request->user(), 'Login successful');
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
        return $this->success(null, 'Logout successful');
    }

    public function user(Request $request): JsonResponse
    {
        return $this->success($request->user());
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        Password::sendResetLink(['email' => $request->string('email')->toString()]);
        return $this->success(null, 'If the email exists, a reset link has been sent.');
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill(['password' => Hash::make($password)])->setRememberToken(Str::random(60));
                $user->save();
                event(new PasswordReset($user));
            }
        );
        if ($status !== Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Reset password gagal.', 'errors' => ['email' => [__($status)] ]], 422);
        }
        return $this->success(null, 'Password reset successful');
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $request->user()->update($request->validated());
        return $this->success($request->user()->fresh(), 'Profile updated');
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $request->user()->update(['password' => Hash::make($request->string('password')->toString())]);
        return $this->success(null, 'Password changed');
    }

    private function success(mixed $data, ?string $message = null): JsonResponse
    {
        return response()->json(['data' => $data, 'meta' => null, 'message' => $message]);
    }
}
