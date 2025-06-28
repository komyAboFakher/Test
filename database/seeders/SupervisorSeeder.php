<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Supervisor;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SupervisorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //Supervisor::factory()->count(10)->create();
        $supervisorUsers = User::where('role', 'supervisor')->get();

        foreach ($supervisorUsers as $user) {
            Supervisor::factory()->create([
                'user_id' => $user->id,
            ]);
        }
    }
}
