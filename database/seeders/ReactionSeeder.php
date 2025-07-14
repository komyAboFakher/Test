<?php

namespace Database\Seeders;

use App\Models\Reaction;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ReactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $reactions = ['like', 'dislike', 'love', 'haha', 'wow', 'sad', 'angry'];

        foreach ($reactions as $type) {
            Reaction::create(['type' => $type]);
        }
    }
}
