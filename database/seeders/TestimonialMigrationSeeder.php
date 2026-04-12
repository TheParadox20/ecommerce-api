<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\Testimonial;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestimonialMigrationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Move all Testimonials to Reviews
        $testimonials = Testimonial::all();
        
        foreach ($testimonials as $testimonial) {
            Review::create([
                'reviewer_name' => $testimonial->name,
                'role' => $testimonial->role,
                'rate' => $testimonial->rating,
                'review' => $testimonial->comment,
                'status' => 'pending', // All migrated ones start as pending
                'product_id' => null,   // General testimonials don't have a product link
                'created_at' => $testimonial->created_at,
                'updated_at' => $testimonial->updated_at,
            ]);
        }

        // 2. Reset ALL existing reviews to pending
        Review::where('status', '!=', 'pending')->update(['status' => 'pending']);

        // 3. Mark testimonials as inactive (optional, keeping them for safety but they won't show)
        Testimonial::query()->update(['is_active' => false]);
    }
}
