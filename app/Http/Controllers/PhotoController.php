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
     *                 type="object",
     *                 @OA\Property(
     *                     property="images",
     *                     type="array",
     *                     description="Select one or more images",
     *                     @OA\Items(type="string", format="binary")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Images uploaded successfully"),
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function uploadImages(Request $request)
    {
        $request->validate([
            'images' => 'required|array',
            'images.*' => 'required|file|mimes:jpg,jpeg,png|max:5120',
        ]);

        $urls = [];
        foreach ($request->file('images') as $file) {
            $path = $file->store('uploads', 'public');
            $urls[] = Storage::url($path);
        }

        return response()->json(['urls' => $urls]);
    }

}
