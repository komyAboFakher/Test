<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        User::factory()->count(80)->create([
            'role' => 'student',
        ]);

        // 10 teachers
        User::factory()->count(10)->create([
            'role' => 'teacher',
        ]);

        // 10 supervisors
        User::factory()->count(10)->create([
            'role' => 'supervisor',
        ]);

        User::factory()->count(80)->create([
            'role' => 'parent',
        ]);

        User::factory()->count(5)->create([
            'role' => 'other',
        ]);


        // don't change the order please !!!

        $this->call([
            TeacherSeeder::class,
            SupervisorSeeder::class,
            OtherSeeder::class,
            ClassSeeder::class,
            ParentsSeeder::class,
            StudentSeeder::class,
            SubjectSeeder::class,
            TeacherClassSeeder::class,
            ReactionSeeder::class,
            PermissionSeeder::class,
            MarkSeeder::class,

        ]);
    }
}
