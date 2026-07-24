<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateSuperAdmin extends Command
{
    protected $signature = 'admin:create-superadmin';

    protected $description = 'Buat akun superadmin secara interaktif (tidak menyimpan kredensial di kode/seeder)';

    public function handle(): int
    {
        $this->info('Buat akun Super Admin baru.');

        $name = $this->ask('Nama lengkap');
        $email = $this->ask('Email');
        $password = $this->secret('Password (min. 12 karakter)');
        $passwordConfirmation = $this->secret('Konfirmasi password');

        $validator = Validator::make(
            [
                'name' => $name,
                'email' => $email,
                'password' => $password,
            ],
            [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email',
                'password' => 'required|string|min:12',
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        if ($password !== $passwordConfirmation) {
            $this->error('Password dan konfirmasi tidak cocok.');

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'nama_lengkap' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => User::ROLE_SUPERADMIN,
            'unit_sppg' => null,
            'is_active' => true,
        ]);

        $this->info("Superadmin '{$user->email}' berhasil dibuat.");

        return self::SUCCESS;
    }
}
