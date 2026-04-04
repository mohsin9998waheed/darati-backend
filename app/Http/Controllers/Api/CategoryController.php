<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Cache::remember('api:categories', 3600, function () {
            return Category::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'slug', 'icon'])
                ->toArray();
        });

        return response()->json(['data' => $categories]);
    }
}
