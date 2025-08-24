<?php

namespace Database\Seeders;

use App\Models\Other;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class OtherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $others = User::where('role', 'other')->get();

        foreach ($others as $other) {
            Other::factory()->create([
                'user_id' => $other->id,
            ]);
        }
    }
}
