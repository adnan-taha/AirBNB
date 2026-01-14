<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PhotoController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/photos/upload",
     *     tags={"Photos"},
     *     summary="Upload one or more images and get URLs",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"images[]"},
     *                 @OA\Property(
     *                     property="images[]",
     *                     type="array",
     *                     description="Select one or more images",
     *                     @OA\Items(type="string", format="binary")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Images uploaded successfully"),
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=204, description="Preflight OK")
     * )
     */
    public function uploadImages(Request $request)
    {
        // ✅ Allow OPTIONS preflight (CORS)
        if ($request->isMethod('OPTIONS')) {
            return response()->noContent();
        }

        // ✅ Validate
        $request->validate([
            'images' => ['required'],
            'images.*' => ['file', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        // ✅ Get files from both Swagger & real frontend
        $files = $request->file('images') ?? $request->file('images[]');

        // ✅ Normalize to array
        if (!is_array($files)) {
            $files = [$files];
        }

        $urls = [];

        foreach ($files as $file) {
            $path = $file->store('uploads', 'public');
            $urls[] = Storage::url($path);
        }

        return response()->json([
            'urls' => $urls,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/photos",
     *     tags={"Photos"},
     *     summary="Get all uploaded images",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="List of all images"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function getAllImages()
    {
        // Get all files in 'public/uploads'
        $files = Storage::disk('public')->files('uploads');

        // Generate URLs
        $urls = array_map(fn($file) => Storage::url($file), $files);

        return response()->json([
            'images' => $urls,
        ], 200);
    }
}
