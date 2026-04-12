<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\NavMenu;

class NavMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $menus = [
            ['label' => 'Home', 'url' => '/', 'order_position' => 1],
            ['label' => 'Products', 'url' => '/products', 'order_position' => 2],
            ['label' => 'Our Story', 'url' => '/about', 'order_position' => 3],
            ['label' => 'Recipes', 'url' => '/recipes', 'order_position' => 4],
            ['label' => 'Blog', 'url' => '/blog', 'order_position' => 5],
            ['label' => 'Contact Us', 'url' => '/contact', 'order_position' => 6],
        ];

        foreach ($menus as $menu) {
            NavMenu::create($menu);
        }
    }
}
