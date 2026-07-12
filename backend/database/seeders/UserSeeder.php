<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Akun uji untuk development & blackbox testing (Fase 2+).
 * Semua password = "password" (User model meng-cast 'password' => 'hashed',
 * jadi plain text otomatis di-hash).
 *
 * Ganti kredensial ini di lingkungan produksi!
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Super Admin', 'email' => 'superadmin@siklas.test', 'role' => 'superadmin', 'password' => 'password'],
            ['name' => 'Admin Pendataan', 'email' => 'admin@siklas.test', 'role' => 'admin', 'password' => 'password'],
            ['name' => 'Kepala Desa', 'email' => 'approver@siklas.test', 'role' => 'approver', 'password' => 'password'],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(['email' => $u['email']], $u);
        }
    }
}
