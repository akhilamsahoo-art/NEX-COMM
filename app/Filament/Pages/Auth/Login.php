<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class Login extends BaseLogin
{
    public function authenticate(): ?LoginResponse
    {
        $data = $this->form->getState();

        // ✅ FIELD VALIDATION
        $validator = Validator::make($data, [
            'email' => 'required|email',
            'password' => ['required', 'regex:/^[0-9]{6,15}$/']
        ], [
            'email.required' => 'Email is required',
            'email.email' => 'Enter a valid email address',

            'password.required' => 'Password is required',
            'password.regex' => 'Password must be 6 to 15 digits (numbers only)',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages(
                $validator->errors()->toArray()
            );
        }

        // 🔍 Find user
        $user = User::where('email', $data['email'])->first();

        // ❌ Email not found
        if (!$user) {
            throw ValidationException::withMessages([
                'email' => 'Email not registered',
            ]);
        }

        // ❌ Wrong password
        if (!Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'Incorrect password',
            ]);
        }

        // 🔥 BLOCK NON-ADMIN
        if ($user->role !== 'super_admin') {
            Notification::make()
                ->title('Access Denied')
                ->body('Only Super Admin can login')
                ->danger()
                ->send();
        
            return null;
        }

        // ✅ LOGIN
        Auth::login($user);

        return app(LoginResponse::class);
    }
}