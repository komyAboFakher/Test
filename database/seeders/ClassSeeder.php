<?php

namespace Database\Seeders;

use App\Models\SchoolClass;
use Illuminate\Database\Seeder;
//use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ClassSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //schoolClass::create([
        //    'name' => 'John Doe',
        //    'email' => 'johndoe@example.com',
        //    'className'=>'10-1',
        //    'studentsNum',
        //    'password' => bcrypt('password'),
        //]);
    

    SchoolClass::factory()->count(10)->create();



    }
}
