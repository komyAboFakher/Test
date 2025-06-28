<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subjects = ['physics', 'math', 'chemistry', 'history', 'biology', 'computer'];

        for ($grade = 1; $grade <= 12; $grade++) {
            foreach ($subjects as $name) {
                Subject::factory()->create([
                    'subjectName' => $name,
                    'grade' => $grade,
                ]);
            }
        }
    }
}
