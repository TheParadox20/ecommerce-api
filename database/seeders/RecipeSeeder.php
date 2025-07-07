<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Recipe;

class RecipeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $recipes = [
            [
                'title' => 'Breakfast Oats with Berries',
                'content' => 'A delicious and nutritious breakfast that combines creamy oats with fresh berries and honey. Perfect for starting your day with energy and flavor.',
                'image' => '/temp/oats-1.jpg',
                'ingredients' => [
                    '1 cup rolled oats',
                    '2 cups water or milk',
                    '1/4 cup mixed berries',
                    '2 tablespoons honey',
                    '1/4 cup chopped nuts',
                    '1/2 teaspoon cinnamon'
                ],
                'instructions' => [
                    'Bring water or milk to a boil in a medium saucepan',
                    'Add oats and reduce heat to low',
                    'Cook for 5-7 minutes, stirring occasionally',
                    'Remove from heat and let stand for 2 minutes',
                    'Top with berries, honey, nuts, and cinnamon',
                    'Serve hot and enjoy!'
                ],
                'cooking_time' => 15,
                'servings' => 2,
                'difficulty' => 'easy',
                'category' => 'breakfast',
                'is_featured' => true,
                'views' => 1250
            ],
            [
                'title' => 'Super Breakfast Oats with Honey',
                'content' => 'An energizing breakfast bowl packed with protein, fiber, and natural sweetness. This recipe will keep you full and focused throughout the morning.',
                'image' => '/temp/nutmill-1.jpg',
                'ingredients' => [
                    '1 cup steel-cut oats',
                    '3 cups water',
                    '1/4 cup honey',
                    '1/4 cup chopped almonds',
                    '1/4 cup dried cranberries',
                    '1 tablespoon chia seeds',
                    '1/2 teaspoon vanilla extract'
                ],
                'instructions' => [
                    'Rinse oats under cold water',
                    'Bring water to a boil in a large pot',
                    'Add oats and reduce heat to simmer',
                    'Cook for 20-25 minutes, stirring occasionally',
                    'Add honey and vanilla, stir well',
                    'Top with almonds, cranberries, and chia seeds',
                    'Let stand for 5 minutes before serving'
                ],
                'cooking_time' => 30,
                'servings' => 4,
                'difficulty' => 'medium',
                'category' => 'breakfast',
                'is_featured' => true,
                'views' => 980
            ],
            [
                'title' => 'Quick Oatmeal with Bananas',
                'content' => 'A speedy breakfast solution that doesn\'t compromise on taste or nutrition. Perfect for busy mornings when you need something quick and satisfying.',
                'image' => '/temp/oats-1.jpg',
                'ingredients' => [
                    '1 cup quick oats',
                    '1 1/2 cups milk',
                    '1 ripe banana, sliced',
                    '2 tablespoons maple syrup',
                    '1/4 teaspoon salt',
                    '1/4 cup walnuts, chopped'
                ],
                'instructions' => [
                    'Combine oats, milk, and salt in a microwave-safe bowl',
                    'Microwave on high for 2-3 minutes',
                    'Stir in maple syrup and banana slices',
                    'Top with chopped walnuts',
                    'Serve immediately while hot'
                ],
                'cooking_time' => 5,
                'servings' => 1,
                'difficulty' => 'easy',
                'category' => 'breakfast',
                'is_featured' => false,
                'views' => 750
            ],
            [
                'title' => 'Overnight Oats with Chia Seeds',
                'content' => 'Prepare this the night before for a no-cook breakfast that\'s ready when you wake up. Creamy, nutritious, and absolutely delicious.',
                'image' => '/temp/nutmill-1.jpg',
                'ingredients' => [
                    '1/2 cup rolled oats',
                    '1/2 cup almond milk',
                    '2 tablespoons chia seeds',
                    '1 tablespoon honey',
                    '1/4 cup mixed berries',
                    '1 tablespoon almond butter'
                ],
                'instructions' => [
                    'Mix oats, almond milk, chia seeds, and honey in a jar',
                    'Stir well to combine all ingredients',
                    'Cover and refrigerate overnight (8-12 hours)',
                    'In the morning, top with berries and almond butter',
                    'Stir gently and enjoy cold or warm'
                ],
                'cooking_time' => 480, // 8 hours
                'servings' => 1,
                'difficulty' => 'easy',
                'category' => 'breakfast',
                'is_featured' => false,
                'views' => 620
            ],
            [
                'title' => 'Savory Oatmeal with Vegetables',
                'content' => 'A unique twist on traditional oatmeal, this savory version is perfect for lunch or dinner. Packed with vegetables and flavor.',
                'image' => '/temp/oats-1.jpg',
                'ingredients' => [
                    '1 cup steel-cut oats',
                    '2 cups vegetable broth',
                    '1/2 cup diced carrots',
                    '1/2 cup diced bell peppers',
                    '1/4 cup chopped spinach',
                    '2 tablespoons olive oil',
                    '1/2 teaspoon garlic powder',
                    'Salt and pepper to taste'
                ],
                'instructions' => [
                    'Heat olive oil in a large saucepan over medium heat',
                    'Add diced vegetables and sauté for 3-4 minutes',
                    'Add oats and vegetable broth, bring to a boil',
                    'Reduce heat and simmer for 20-25 minutes',
                    'Stir in spinach and seasonings',
                    'Cook for additional 2-3 minutes',
                    'Serve hot with additional vegetables on top'
                ],
                'cooking_time' => 35,
                'servings' => 2,
                'difficulty' => 'medium',
                'category' => 'lunch',
                'is_featured' => false,
                'views' => 450
            ],
            [
                'title' => 'Oat Energy Balls',
                'content' => 'Perfect for snacking or as a pre-workout boost, these energy balls are packed with oats, nuts, and natural sweetness.',
                'image' => '/temp/nutmill-1.jpg',
                'ingredients' => [
                    '1 cup rolled oats',
                    '1/2 cup almond butter',
                    '1/3 cup honey',
                    '1/4 cup chopped dates',
                    '1/4 cup chopped almonds',
                    '2 tablespoons chia seeds',
                    '1/2 teaspoon vanilla extract'
                ],
                'instructions' => [
                    'Mix all ingredients in a large bowl',
                    'Stir until well combined and sticky',
                    'Roll mixture into 1-inch balls',
                    'Place on a baking sheet lined with parchment paper',
                    'Refrigerate for 30 minutes to firm up',
                    'Store in an airtight container in the refrigerator'
                ],
                'cooking_time' => 45,
                'servings' => 12,
                'difficulty' => 'easy',
                'category' => 'snack',
                'is_featured' => true,
                'views' => 890
            ]
        ];

        foreach ($recipes as $recipe) {
            Recipe::create($recipe);
        }
    }
} 