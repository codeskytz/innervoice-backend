<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SeedController extends Controller
{
    public function seed()
    {
        // Simple placeholder, real seeding can be handled by Laravel seeders
        return response()->json(['message' => 'Seeding completed internally.']);
    }
}
