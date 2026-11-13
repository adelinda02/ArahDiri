<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login');
    }

    /** Masuk dengan nama pengguna, email, atau nomor HP. */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'identifier' => ['required', 'string', 'max:254'],
            'password' => ['required', 'string', 'max:128'],
            'remember' => ['boolean'],
        ], [], ['identifier' => 'akun', 'password' => 'kata sandi']);

        $id = trim($data['identifier']);
        $field = match (true) {
            str_contains($id, '@') => 'email',
            (bool) preg_match('/^\+?[\d\s-]{8,}$/', $id) => 'phone',
            default => 'username',
        };
        $value = match ($field) {
            'phone' => User::normalizePhone($id),
            default => mb_strtolower($id),
        };

        if (! Auth::attempt([$field => $value, 'password' => $data['password'], 'is_active' => true], (bool) ($data['remember'] ?? false))) {
            throw ValidationException::withMessages(['identifier' => 'Akun atau kata sandi tidak cocok.']);
        }

        $request->session()->regenerate();
        AuditLog::record('login', $request->user()->id);

        $user = $request->user();
        $home = $user->mustChangePassword() ? route('profil')
            : ($user->canEnterAdmin() ? route('admin.dashboard') : route('beranda'));

        return redirect()->intended($home)->with('status', 'Selamat datang kembali, '.$request->user()->name.'.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing');
    }
}
