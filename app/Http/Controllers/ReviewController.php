<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewRequest;
use App\Models\Booking;
use App\Models\Review;
use Carbon\Carbon;

class ReviewController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/reviews",
     *     tags={"Reviews"},
     *     summary="Create review (tenant only)",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"booking_id","rating"},
     *             @OA\Property(property="booking_id", type="integer"),
     *             @OA\Property(property="rating", type="integer", example=5),
     *             @OA\Property(property="comment", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Review created"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(ReviewRequest $request)
    {
        $user = auth()->user();

        // Tenant only
        if ($user->role !== 'tenant') {
            return response()->json(['message' => 'Only tenants can review'], 403);
        }

        // Check booking belongs to tenant and is approved
        $booking = Booking::where('id', $request->booking_id)
            ->where('tenant_id', $user->id)
            ->where('status', 'approved')
            ->first();

        if (!$booking) {
            return response()->json(['message' => 'Invalid booking'], 403);
        }

        // Optional: check if booking has ended
        // if (Carbon::parse($booking->end_date)->isFuture()) {
        //     return response()->json(['message' => 'Booking not finished yet'], 403);
        // }

        $existingReview = Review::where('booking_id', $booking->id)->first();
        if ($existingReview) {
            return response()->json(['message' => 'You have already submitted a review for this booking'], 400);
        }

        // Create the review
        $review = Review::create([
            'apartment_id' => $booking->apartment_id,
            'tenant_id' => $user->id,
            'booking_id' => $booking->id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return response()->json([
            'message' => 'Review created successfully',
            'review' => $review
        ], 201);
    }



    /**
     * @OA\Get(
     *     path="/api/apartments/{id}/reviews",
     *     tags={"Reviews"},
     *     summary="Get apartment reviews",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Reviews list")
     * )
     */
    public function apartmentReviews($id)
    {
        $reviews = Review::where('apartment_id', $id)
            ->with('tenant:id,first_name,last_name')
            ->latest()
            ->paginate(10);

        return response()->json($reviews);
    }
}
