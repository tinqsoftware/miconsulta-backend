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
            
        $banners->transform(function ($banner) {
            $banner->imagen_url = url($banner->imagen_url);
            $banner->imagen_popup_url = $banner->imagen_popup_url
                ? url($banner->imagen_popup_url)
                : null;
            $banner->link_url = blank($banner->link_url)
                ? null
                : trim($banner->link_url);
            return $banner;
        });

        return response()->json([
            'status' => 'success',
            'data' => $banners
        ]);
    }
}
