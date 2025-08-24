<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Parents;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ParentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $parents = User::where('role', 'parent')->get();

        foreach ($parents as $parent) {
            Parents::factory()->create([
                'user_id' => $parent->id,
            ]);
        }
    }
}
