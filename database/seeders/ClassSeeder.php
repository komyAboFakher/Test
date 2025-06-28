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
       
    // don't change the count, don't you dare to do that !!!!!!
    SchoolClass::factory()->count(26)->create();



    }
}
