<?php

namespace Database\Seeders;

use App\Models\TeacherClass;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TeacherClassSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
    
        TeacherClass::factory()->count(20)->create();

    }
}
