<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\JsonResponse;

class BannerController extends Controller
{
    public function index(): JsonResponse
    {
        $banners = Banner::where('is_active', true)
            ->orderBy('order')
            ->get(['id', 'title', 'image_path', 'link_type', 'link_value', 'order'])
            ->map(fn ($b) => [
                'id'         => $b->id,
                'title'      => $b->title,
                'image_url'  => $b->image_url,
                'link_type'  => $b->link_type,
                'link_value' => $b->link_value,
            ]);

        return response()->json(['data' => $banners]);
    }
}
