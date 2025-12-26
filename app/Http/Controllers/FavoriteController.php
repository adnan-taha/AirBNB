<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Models\Favorite;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/apartments/{id}/favorite",
     *     tags={"Favorites"},
     *     summary="Add apartment to favorites",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Added to favorites"),
     *     @OA\Response(response=404, description="Apartment not found")
     * )
     */
    public function store($id)
    {
        $user = auth()->user();

        $apartment = Apartment::find($id);
        if (!$apartment || !$apartment->is_approved) {
            return response()->json(['message' => 'Apartment not found'], 404);
        }

        Favorite::firstOrCreate([
            'user_id' => $user->id,
            'apartment_id' => $apartment->id,
        ]);

        return response()->json(['message' => 'Added to favorites']);
    }

    /**
     * @OA\Delete(
     *     path="/api/apartments/{id}/favorite",
     *     tags={"Favorites"},
     *     summary="Remove apartment from favorites",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Removed from favorites")
     * )
     */
    public function destroy($id)
    {
        Favorite::where('user_id', auth()->id())
            ->where('apartment_id', $id)
            ->delete();

        return response()->json(['message' => 'Removed from favorites']);
    }

    /**
     * @OA\Get(
     *     path="/api/favorites",
     *     tags={"Favorites"},
     *     summary="List user's favorite apartments",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Favorites list")
     * )
     */
    public function index()
    {
        $favorites = Favorite::with('apartment')
            ->where('user_id', auth()->id())
            ->paginate(12);

        return response()->json($favorites);
    }
}
