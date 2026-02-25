<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GalleryRequest;
use App\Models\GalleryImage;
use Illuminate\Http\JsonResponse;

class GalleryController extends Controller
{
    public function index(): JsonResponse
    {
        $images = GalleryImage::orderBy('created_at', 'desc')->get();

        return response()->json($images);
    }

    public function store(GalleryRequest $request): JsonResponse
    {
        $image = GalleryImage::create($request->validated());

        return response()->json([
            'message' => 'Galéria kép sikeresen hozzáadva.',
            'image' => $image,
        ], 201);
    }

    public function destroy(GalleryImage $galleryImage): JsonResponse
    {
        $galleryImage->delete();

        return response()->json([
            'message' => 'Galéria kép törölve.',
        ]);
    }
}
