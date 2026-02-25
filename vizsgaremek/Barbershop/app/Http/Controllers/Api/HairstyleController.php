<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\HairstyleRequest;
use App\Models\Hairstyle;
use Illuminate\Http\JsonResponse;

class HairstyleController extends Controller
{
    public function index(): JsonResponse
    {
        $hairstyles = Hairstyle::all();

        return response()->json($hairstyles);
    }

    public function show(Hairstyle $hairstyle): JsonResponse
    {
        return response()->json($hairstyle);
    }

    public function store(HairstyleRequest $request): JsonResponse
    {
        $hairstyle = Hairstyle::create($request->validated());

        return response()->json([
            'message' => 'Hajstílus sikeresen létrehozva.',
            'hairstyle' => $hairstyle,
        ], 201);
    }

    public function update(HairstyleRequest $request, Hairstyle $hairstyle): JsonResponse
    {
        $hairstyle->update($request->validated());

        return response()->json([
            'message' => 'Hajstílus frissítve.',
            'hairstyle' => $hairstyle,
        ]);
    }

    public function destroy(Hairstyle $hairstyle): JsonResponse
    {
        $hairstyle->delete();

        return response()->json([
            'message' => 'Hajstílus törölve.',
        ]);
    }
}
