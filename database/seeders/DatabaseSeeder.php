<?php

namespace Database\Seeders;

use App\Models\Claim;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Akun demo memakai kredensial sesuai spesifikasi; ganti kata sandi sebelum produksi.
        foreach ([
            ['username' => 'owner', 'email' => 'owner@ptjaya.com', 'password' => 'owner123', 'role' => 'owner'],
            ['username' => 'admin', 'email' => 'admin@ptjaya.com', 'password' => 'admin123', 'role' => 'admin'],
            ['username' => 'vendor', 'email' => 'vendor@ptlampu.com', 'password' => 'vendor123', 'role' => 'vendor', 'company_name' => 'PT Lampu'],
        ] as $account) {
            User::updateOrCreate(
                ['username' => $account['username']],
                [
                    ...$account,
                    'password' => Hash::make($account['password']),
                    'status' => 'active',
                ]
            );
        }

        $project = Project::firstOrCreate(
            ['name' => 'Penerangan Area Kerja PT Jaya'],
            ['description' => 'Proyek pemasangan dan pemeliharaan penerangan area kerja.']
        );

        $vendor = User::where('username', 'vendor')->firstOrFail();
        $claim = Claim::firstOrCreate(
            ['claim_number' => 'CLM-DEMO-20261020'],
            [
                'project_id' => $project->id,
                'vendor_id' => $vendor->id,
                'version' => 1,
                'status' => 'submitted',
                'total_claim_amount' => 150000,
                'total_actual_amount' => 0,
            ]
        );

        $claim->items()->firstOrCreate(
            ['item_name' => 'Lampu LED 20W'],
            [
                'unit' => 'unit',
                'claimed_quantity' => 10,
                'claimed_unit_price' => 15000,
            ]
        );
    }
}
