<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApartmentRequest;
use App\Models\Apartment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ApartmentController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/apartments",
     *     tags={"Apartments"},
     *     summary="List apartments (public)",
     *     @OA\Parameter(name="city", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="province", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="min_price", in="query", @OA\Schema(type="number")),
     *     @OA\Parameter(name="max_price", in="query", @OA\Schema(type="number")),
     *     @OA\Response(response=200, description="List returned")
     * )
     */
    public function index(Request $request)
    {
        $q = Apartment::query()->where('is_approved', true);

        if ($request->filled('city')) {
            $q->where('city', $request->city);
        }
        if ($request->filled('province')) {
            $q->where('province', $request->province);
        }
        if ($request->filled('min_price')) {
            $q->where('price_per_day', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $q->where('price_per_day', '<=', $request->max_price);
        }

        $perPage = (int)$request->get('per_page', 12);

        return response()->json($q->paginate($perPage));
    }


    /**
     * @OA\Get(
     *     path="/api/apartments/{id}",
     *     tags={"Apartments"},
     *     summary="Get apartment details",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Apartment data"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show($id)
    {
        $apartment = Apartment::with(['owner'])->find($id);

        if (!$apartment || !$apartment->is_approved) {
            return response()->json(['message' => 'Apartment not found or not approved'], 404);
        }

        return response()->json($apartment);
    }


    /**
     * @OA\Post(
     *     path="/api/apartments",
     *     tags={"Apartments"},
     *     summary="Create new apartment",
     *     description="Creates a new apartment. The images field should contain URLs returned from the image upload endpoint.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="province", type="string"),
     *             @OA\Property(property="city", type="string"),
     *             @OA\Property(property="rooms", type="integer"),
     *             @OA\Property(property="price_per_day", type="number", format="float"),
     *             @OA\Property(
     *                 property="images",
     *                 type="array",
     *                 @OA\Items(type="string"),
     *                 description="Array of image URLs returned from the image upload endpoint",
     *                 nullable=true
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Apartment created successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Account not approved")
     * )
     */
    public function store(ApartmentRequest $request)
    {
        $data = $request->validated();
        $data['owner_id'] = auth()->id();
        $data['is_approved'] = false;
        $data['images'] = $data['images'] ?? [];

        $apartment = Apartment::create([
            'title' => $data['title'],
            'description' => $data['description'],
            'province' => $data['province'],
            'city' => $data['city'],
            'rooms' => $data['rooms'],
            'price_per_day' => $data['price_per_day'],
            'owner_id' => $data['owner_id'],
            'is_approved' => $data['is_approved'],
            'images' => $data['images'],
        ]);

        return response()->json($apartment, 201);
    }

    /**
     * @OA\Put(
     *     path="/api/apartments/{id}",
     *     tags={"Apartments"},
     *     summary="Update apartment (owner or admin only)",
     *     description="Updates an existing apartment. The images field should contain URLs returned from the image upload endpoint.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Apartment ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="province", type="string"),
     *             @OA\Property(property="city", type="string"),
     *             @OA\Property(property="rooms", type="integer"),
     *             @OA\Property(property="price_per_day", type="number", format="float"),
     *             @OA\Property(
     *                 property="images",
     *                 type="array",
     *                 @OA\Items(type="string"),
     *                 description="Array of image URLs returned from the image upload endpoint",
     *                 nullable=true
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Apartment updated successfully"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Apartment not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function update(ApartmentRequest $request, $id)
    {
        $apartment = Apartment::find($id);

        if (!$apartment) {
            return response()->json(['message' => 'Apartment not found'], 404);
        }

        // Owner or admin only
        if ($apartment->owner_id !== auth()->id() && auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Not allowed'], 403);
        }

        $data = $request->validated();

        // Update all fields
        $apartment->fill($data);

        // Update images if provided as URLs
        if (array_key_exists('images', $data)) {
            $apartment->images = $data['images'];
        }

        // After editing, admin must re-approve
        $apartment->is_approved = false;

        $apartment->save();

        return response()->json($apartment);
    }



    /**
     * @OA\Delete(
     *     path="/api/apartments/{id}",
     *     tags={"Apartments"},
     *     summary="Delete apartment (owner or admin)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Deleted"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function destroy($id)
    {
        $apartment = Apartment::find($id);

        if (!$apartment) {
            return response()->json(['message' => 'Apartment not found'], 404);
        }

        if ($apartment->owner_id !== auth()->id() && auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Not allowed'], 403);
        }

        $apartment->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }


    /**
     * @OA\Post(
     *     path="/api/apartments/{id}/approve",
     *     tags={"Apartments"},
     *     summary="Approve apartment (admin only)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Approved"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function approve($id)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $apartment = Apartment::find($id);

        if (!$apartment) {
            return response()->json(['message' => 'Apartment not found'], 404);
        }

        $apartment->is_approved = true;
        $apartment->save();

        return response()->json(['message' => 'Apartment approved', 'apartment' => $apartment]);
    }


    /**
     * @OA\Get(
     *     path="/api/owner/apartments",
     *     tags={"Apartments"},
     *     summary="List owner's apartments",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="List returned")
     * )
     */
    public function ownerIndex()
    {
        $list = Apartment::where('owner_id', auth()->id())->paginate(12);

        return response()->json($list);
    }

    /**
     * @OA\Get(
     *     path="/api/apartment/unapproved",
     *     tags={"Apartments"},
     *     summary="Get all unapproved apartments",
     *     description="Returns a list of all apartments that are not yet approved. Admin only.",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of unapproved apartments",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="My Apartment"),
     *                 @OA\Property(property="description", type="string", example="Nice place to stay"),
     *                 @OA\Property(property="province", type="string", example="Damascus"),
     *                 @OA\Property(property="city", type="string", example="CityName"),
     *                 @OA\Property(property="rooms", type="integer", example=2),
     *                 @OA\Property(property="price_per_day", type="number", format="float", example=100.0),
     *                 @OA\Property(property="owner_id", type="integer", example=1),
     *                 @OA\Property(property="images", type="array", @OA\Items(type="string"), example={"url1","url2"}),
     *                 @OA\Property(property="is_approved", type="boolean", example=false),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No unapproved apartments found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="No unapproved apartments found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Forbidden")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     )
     * )
     */
    public function getUnapproved()
    {
        $user = auth()->user();
        \Log::info('Current user: ', ['user' => $user]);

        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $list = Apartment::where('is_approved', false)->get();
        \Log::info('Unapproved apartments: ', ['count' => $list->count()]);

        if ($list->isEmpty()) {
            return response()->json(['message' => 'No unapproved apartments found'], 404);
        }

        return response()->json($list, 200);
    }

    /**
     * @OA\Get(
     *     path="/api/apartments/{id}/rating",
     *     tags={"Apartments"},
     *     summary="Get average rating of an apartment",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Average rating returned",
     *         @OA\JsonContent(
     *             @OA\Property(property="apartment_id", type="integer"),
     *             @OA\Property(property="average_rating", type="number", example=4.5),
     *             @OA\Property(property="reviews_count", type="integer", example=10)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Apartment not found"
     *     )
     * )
     */
    public function rating($id)
    {
        $apartment = \App\Models\Apartment::find($id);

        if (!$apartment) {
            return response()->json(['message' => 'Apartment not found'], 404);
        }

        $average = $apartment->reviews()->avg('rating');
        $count   = $apartment->reviews()->count();

        return response()->json([
            'apartment_id'   => $apartment->id,
            'average_rating' => $average ? round($average, 2) : null,
            'reviews_count'  => $count,
        ]);
    }




}


