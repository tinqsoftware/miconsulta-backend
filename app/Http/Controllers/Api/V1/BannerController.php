<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    public function activos()
    {
        $banners = Banner::where('estado', true)
            ->orderBy('created_at', 'desc')
            ->get();
            
        // Map the full URL for the image
        $banners->transform(function ($banner) {
            $banner->imagen_url = url($banner->imagen_url);
            return $banner;
        });

        return response()->json([
            'status' => 'success',
            'data' => $banners
        ]);
    }
}
