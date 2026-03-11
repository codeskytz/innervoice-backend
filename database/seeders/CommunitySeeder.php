<?php

namespace Database\Seeders;

use App\Models\Community;
use Illuminate\Database\Seeder;

class CommunitySeeder extends Seeder
{
    public function run(): void
    {
        $communities = [
            [
                'name' => 'General',
                'description' => 'Open chat for everyone. Share thoughts, updates, and connect with the community.',
                'icon' => '💬',
                'color' => '#6366f1',
            ],
            [
                'name' => 'Dating Tips',
                'description' => 'Share your best dating advice, experiences, and get tips from others.',
                'icon' => '💕',
                'color' => '#ec4899',
            ],
            [
                'name' => 'Confessions',
                'description' => 'A safe space to share your confessions and inner thoughts anonymously.',
                'icon' => '🤫',
                'color' => '#8b5cf6',
            ],
            [
                'name' => 'Mental Health',
                'description' => 'Support each other. Talk openly about mental health, struggles, and growth.',
                'icon' => '🧠',
                'color' => '#10b981',
            ],
            [
                'name' => 'Relationships',
                'description' => 'Navigate friendships, love, and family with shared wisdom.',
                'icon' => '❤️',
                'color' => '#ef4444',
            ],
            [
                'name' => 'Vent Zone',
                'description' => 'Need to vent? Drop it here. No judgment, just listening.',
                'icon' => '😤',
                'color' => '#f59e0b',
            ],
        ];

        foreach ($communities as $community) {
            Community::firstOrCreate(['name' => $community['name']], $community);
        }
    }
}
