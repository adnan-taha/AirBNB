<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApartmentRequest;
use App\Models\Apartment;
use Illuminate\Http\Request;

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
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="List returned")
     * )
     */
    public function index(Request $request)
    {
        $q = Apartment::query()->where('is_approved', true);

        if ($request->filled('city')) $q->where('city', $request->city);
        if ($request->filled('province')) $q->where('province', $request->province);
        if ($request->filled('min_price')) $q->where('price_per_day', '>=', $request->min_price);
        if ($request->filled('max_price')) $q->where('price_per_day', '<=', $request->max_price);

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
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="type", type="string", enum={"Apartment","Penthouse","Hotel","Villa"}),
     *             @OA\Property(property="province", type="string", enum={"Damascus","Aleppo","Homs","Rif Dimashq","Tartous","Latakia"}),
     *             @OA\Property(property="city", type="string", example="Mazzeh"),
     *             @OA\Property(property="rooms", type="integer"),
     *             @OA\Property(property="bathrooms", type="integer"),
     *             @OA\Property(property="parking", type="boolean"),
     *             @OA\Property(property="area", type="integer"),
     *             @OA\Property(property="build_year", type="integer"),
     *             @OA\Property(property="price_per_day", type="number", format="float"),
     *             @OA\Property(
     *                 property="images",
     *                 type="array",
     *                 @OA\Items(type="string"),
     *                 nullable=true
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Apartment created successfully"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function store(ApartmentRequest $request)
    {
        $data = $request->validated();
        $data['owner_id'] = auth()->id();
        $data['is_approved'] = false;
        $data['images'] = $data['images'] ?? [];

        $apartment = Apartment::create($data);
        return response()->json($apartment, 201);
    }

    /**
     * @OA\Put(
     *     path="/api/apartments/{id}",
     *     tags={"Apartments"},
     *     summary="Update apartment (owner or admin only)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="type", type="string", enum={"Apartment","Penthouse","Hotel","Villa"}),
     *             @OA\Property(property="province", type="string", enum={"Damascus","Aleppo","Homs","Rif Dimashq","Tartous","Latakia"}),
     *             @OA\Property(property="city", type="string"),
     *             @OA\Property(property="rooms", type="integer"),
     *             @OA\Property(property="bathrooms", type="integer"),
     *             @OA\Property(property="parking", type="boolean"),
     *             @OA\Property(property="area", type="integer"),
     *             @OA\Property(property="build_year", type="integer"),
     *             @OA\Property(property="price_per_day", type="number", format="float"),
     *             @OA\Property(
     *                 property="images",
     *                 type="array",
     *                 @OA\Items(type="string"),
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
        if (!$apartment) return response()->json(['message' => 'Apartment not found'], 404);

        if ($apartment->owner_id !== auth()->id() && auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Not allowed'], 403);
        }

        $data = $request->validated();
        $apartment->fill($data);
        $apartment->images = $data['images'] ?? $apartment->images;
        $apartment->is_approved = false; // re-approval required
        $apartment->save();

        return response()->json($apartment);
    }

    /**
     * @OA\Delete(
     *     path="/api/apartments/{id}",
     *     tags={"Apartments"},
     *     summary="Delete apartment (owner or admin only)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Deleted"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy($id)
    {
        $apartment = Apartment::find($id);
        if (!$apartment) return response()->json(['message' => 'Apartment not found'], 404);
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
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function approve($id)
    {
        if (auth()->user()->role !== 'admin') return response()->json(['message' => 'Forbidden'], 403);

        $apartment = Apartment::find($id);
        if (!$apartment) return response()->json(['message' => 'Apartment not found'], 404);

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
        return response()->json(Apartment::where('owner_id', auth()->id())->paginate(12));
    }

    /**
     * @OA\Get(
     *     path="/api/apartment/unapproved",
     *     summary="Get all unapproved apartment",
     *     tags={"Apartments"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of unapproved apartment",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unapproved apartment retrieved successfully"),
     *             @OA\Property(
     *                 property="apartment",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=2),
     *                     @OA\Property(property="rooms", type="integer", example="2"),
     *                     @OA\Property(property="price_per_day", type="integer", example="12"),
     *                     @OA\Property(property="description", type="string", example="a nice vila"),
     *                     @OA\Property(property="build_year", type="integer", example="2010"),
     *                     @OA\Property(property="area", type="integer", example="120"),
     *                     @OA\Property(property="parking", type="boolean", example=false)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No unapproved users found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No unapproved users found.")
     *         )
     *     )
     * )
     */
    public function unapprovedApartment()
    {
        $apartment = Apartment::where('is_approved', false)->get();

        if ($apartment->isEmpty()) {
            return response()->json([
                'message' => 'No unapproved apartment found.'
            ], 404);
        }

        return response()->json([
            'message' => 'Unapproved apartment retrieved successfully',
            'users' => $apartment
        ]);
    }
}
