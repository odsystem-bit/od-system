<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ── Super Admin ──
        if (! User::where('email', 'admin@mantota.com')->exists()) {
            $admin = User::create([
                'name'       => 'Super Admin',
                'email'      => 'admin@mantota.com',
                'password'   => 'Admin@2026!',
                'role'       => UserRole::ADMIN,
                'kyc_status' => 'approved',
                'email_verified_at' => now(),
            ]);

            Wallet::create([
                'user_id'         => $admin->id,
                'balance'         => 0,
                'pending_balance' => 0,
            ]);
        }

        $this->call(SettingsSeeder::class);
    }
}
