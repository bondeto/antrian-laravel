<?php

namespace Database\Seeders;

use App\Models\Counter;
use App\Models\Floor;
use App\Models\Service;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Users
        User::factory()->create([
            'name' => 'Operator 1',
            'email' => 'op1@example.com',
            'password' => bcrypt('password'),
        ]);
        
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        // Floors
        $f1 = Floor::create(['name' => 'Lantai 1 - CS & Teller', 'level' => 1]);
        $f2 = Floor::create(['name' => 'Lantai 2 - Kredit & Loan', 'level' => 2]);
        $f3 = Floor::create(['name' => 'Lantai 3 - Management', 'level' => 3]);

        // Services & Counters Floor 1
        $s1_teller = Service::create(['floor_id' => $f1->id, 'name' => 'Teller', 'code' => 'A']);
        $s1_cs = Service::create(['floor_id' => $f1->id, 'name' => 'Customer Service', 'code' => 'B']);

        Counter::create(['floor_id' => $f1->id, 'name' => 'Loket 1 (Teller)']);
        Counter::create(['floor_id' => $f1->id, 'name' => 'Loket 2 (Teller)']);
        Counter::create(['floor_id' => $f1->id, 'name' => 'Loket 3 (CS)']);

        // Services & Counters Floor 2
        $s2_kredit = Service::create(['floor_id' => $f2->id, 'name' => 'Pengajuan Kredit', 'code' => 'C']);
        $s2_loan = Service::create(['floor_id' => $f2->id, 'name' => 'Layanan Nasabah', 'code' => 'D']);

        Counter::create(['floor_id' => $f2->id, 'name' => 'Meja 1 (Kredit)']);
        Counter::create(['floor_id' => $f2->id, 'name' => 'Meja 2 (Admin)']);

        // Services & Counters Floor 3
        $s3_umum = Service::create(['floor_id' => $f3->id, 'name' => 'Sekretaris', 'code' => 'E']);
        
        Counter::create(['floor_id' => $f3->id, 'name' => 'Front Desk Lt 3']);
    }
}
