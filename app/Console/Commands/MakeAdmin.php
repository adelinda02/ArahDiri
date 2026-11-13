<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

/** Akun admin hanya dibuat dari server (pengganti kode aktivasi admin di v14). */
class MakeAdmin extends Command
{
    protected $signature = 'arahdiri:make-admin {username} {email} {phone} {--owner : Beri peran owner}';

    protected $description = 'Buat akun admin ArahDiri';

    public function handle(): int
    {
        $data = [
            'username' => mb_strtolower($this->argument('username')),
            'email' => mb_strtolower($this->argument('email')),
            'phone' => User::normalizePhone($this->argument('phone')),
            'password' => $this->secret('Kata sandi (min. 10 karakter)'),
        ];
        $v = Validator::make($data, [
            'username' => ['required', 'regex:/^[a-z0-9._]{3,40}$/', 'unique:users,username'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['required', 'regex:/^08\d{8,12}$/', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:10', 'max:128'],
        ]);
        if ($v->fails()) {
            foreach ($v->errors()->all() as $e) {
                $this->error($e);
            }

            return self::FAILURE;
        }

        $user = User::create([...$data, 'name' => $data['username'], 'level' => User::LEVEL_SMA_SMK]);
        $user->forceFill(['role' => $this->option('owner') ? 'owner' : 'admin'])->save();
        $user->profile()->create();
        AuditLog::record('admin_created', null, 'user', (string) $user->id);
        $this->info("Admin {$user->username} dibuat.");

        return self::SUCCESS;
    }
}
