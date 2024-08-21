<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BooksSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = \Faker\Factory::create();
        $books = [];
        for ($i = 0; $i < 10; $i++) {
            $books[] = [
                'name' => $faker->sentence,
                'author' => $faker->name,
                'publish_date' => $faker->date,
            ];
        }
        \DB::table('books')->insert($books);
    }
}
