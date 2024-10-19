<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $categories = [
            'Music',
            'Sports',
            'Dance',
            'Viola',
            'Musical Theater',
            'Programming',
            'Art',
            'Various',
            'Climbing',
            'Running',
            'Swimming',
            'Harmonica',
            'IJK',
            'FJO',
            'CMS',
            'Theater',
            'Horse Riding',
            'Meditation',
            'Cold Plunges',
            'Primary School',
            'Around-the-World',
        ];

        foreach ($categories as $category) {
            DB::table('categories')->insert([
                'category' => $category,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
