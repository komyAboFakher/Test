<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('permissions')->insert([
            [
                'permission' => 'library',
                'description' => 'Access to manage library resources and activity',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'permission' => 'nurse',
                'description' => 'Access to student medical records and nurse logs',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'permission' => 'oversee',
                'description' => 'Access to oversee the attendance of the employees',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
