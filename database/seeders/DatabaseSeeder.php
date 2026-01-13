<?php

namespace Database\Seeders;

use App\Models\Counter;
use App\Models\Floor;
use App\Models\Service;
use App\Models\User;
use App\Models\Queue;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Core Structure
        $f1 = Floor::create(['name' => 'Lantai 1 - CS & Teller', 'level' => 1]);
        $f2 = Floor::create(['name' => 'Lantai 2 - Kredit & Loan', 'level' => 2]);
        $f3 = Floor::create(['name' => 'Lantai 3 - Management', 'level' => 3]);

        // Services Floor 1
        Service::create(['floor_id' => $f1->id, 'name' => 'Teller', 'code' => 'A']);
        Service::create(['floor_id' => $f1->id, 'name' => 'Customer Service', 'code' => 'B']);
        
        // Counters Floor 1
        Counter::create(['floor_id' => $f1->id, 'name' => 'Loket 1 (Teller)']);
        Counter::create(['floor_id' => $f1->id, 'name' => 'Loket 2 (Teller)']);
        Counter::create(['floor_id' => $f1->id, 'name' => 'Loket 3 (CS)']);

        // Services Floor 2
        Service::create(['floor_id' => $f2->id, 'name' => 'Pengajuan Kredit', 'code' => 'C']);
        Service::create(['floor_id' => $f2->id, 'name' => 'Layanan Nasabah', 'code' => 'D']);

        // Counters Floor 2
        Counter::create(['floor_id' => $f2->id, 'name' => 'Meja 1 (Kredit)']);
        Counter::create(['floor_id' => $f2->id, 'name' => 'Meja 2 (Admin)']);

        // Services Floor 3
        Service::create(['floor_id' => $f3->id, 'name' => 'Sekretaris', 'code' => 'E']);
        
        // Counters Floor 3
        Counter::create(['floor_id' => $f3->id, 'name' => 'Front Desk Lt 3']);

        // 2. Create Operators for each counter
        $counters = Counter::all();
        foreach ($counters as $index => $counter) {
            User::factory()->create([
                'name' => 'Operator ' . ($index + 1),
                'email' => 'op' . ($index + 1) . '@example.com',
                'password' => bcrypt('password'),
                'role' => 'operator',
            ]);
        }
        
        User::factory()->create([
            'name' => 'Administrator',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        // 3. Generate Sample Queue Data
        $services = Service::all();
        foreach ($services as $service) {
            // Create 10 served queues for today
            for ($i = 1; $i <= 10; $i++) {
                Queue::create([
                    'service_id' => $service->id,
                    'floor_id' => $service->floor_id,
                    'number' => $i,
                    'full_number' => $service->code . '-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                    'status' => 'served',
                    'counter_id' => $counters->where('floor_id', $service->floor_id)->random()->id,
                    'called_at' => now()->subMinutes(rand(10, 100)),
                    'served_at' => now()->subMinutes(rand(1, 9)),
                ]);
            }
            
            // Set last number
            $service->update(['last_number' => 10]);

            // Create 3 waiting queues
            for ($i = 11; $i <= 13; $i++) {
                Queue::create([
                    'service_id' => $service->id,
                    'floor_id' => $service->floor_id,
                    'number' => $i,
                    'full_number' => $service->code . '-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                    'status' => 'waiting',
                ]);
                $service->update(['last_number' => $i]);
            }
        }
    }
}
