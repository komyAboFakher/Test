<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Teacher;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TeacherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //Teacher::factory()->count(10)->create();

        $teacherUsers = User::where('role', 'teacher')->get();

        foreach ($teacherUsers as $user) {
            Teacher::factory()->create([
                'user_id' => $user->id,
            ]);
        }
    }
}
