<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminAndCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        // Create default admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@innervoice.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('admin123'),
                'is_verified' => true,
                'is_admin' => true,
                'api_token' => hash('sha256', Str::random(80)),
            ]
        );

        // Create default categories
        $categories = [
            ['name' => 'Relationships', 'description' => 'Love, dating, and personal relationships'],
            ['name' => 'Work', 'description' => 'Career and workplace issues'],
            ['name' => 'Mental Health', 'description' => 'Mental health and emotional wellbeing'],
            ['name' => 'Family', 'description' => 'Family-related confessions'],
            ['name' => 'Secrets', 'description' => 'Things you\'ve been keeping hidden'],
            ['name' => 'Dreams', 'description' => 'Aspirations and future goals'],
            ['name' => 'Regrets', 'description' => 'Things you wish you could change'],
            ['name' => 'Gratitude', 'description' => 'Things you\'re grateful for'],
        ];

        foreach ($categories as $categoryData) {
            Category::firstOrCreate(
                ['slug' => Str::slug($categoryData['name'])],
                [
                    'name' => $categoryData['name'],
                    'description' => $categoryData['description'],
                    'color' => $this->getRandomColor(),
                ]
            );
        }

        echo "Admin and categories seeded successfully!\n";
    }

    private function getRandomColor(): string
    {
        $colors = ['#6366f1', '#ec4899', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6', '#ef4444', '#14b8a6'];
        return $colors[array_rand($colors)];
    }
}
