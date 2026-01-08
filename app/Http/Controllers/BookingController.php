<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookingRequest;
use App\Models\Apartment;
use App\Models\Booking;
use App\Services\FirebaseNotificationService;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class BookingController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/apartments/{id}/book",
     *     tags={"Bookings"},
     *     summary="Create booking (tenant only)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"start_date","end_date"},
     *             @OA\Property(property="start_date", type="string", format="date"),
     *             @OA\Property(property="end_date", type="string", format="date")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Booking created"),
     *     @OA\Response(response=409, description="Dates already booked"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function store(BookingRequest $request, $id)
    {
        $apartment = Apartment::where('id', $id)
            ->where('is_approved', true)
            ->first();

        if (!$apartment) {
            return response()->json(['message' => 'Apartment not available'], 404);
        }

        // Prevent overlapping bookings
        $overlap = Booking::where('apartment_id', $id)
            ->where('status', 'approved')
            ->where(function ($q) use ($request) {
                $q->whereBetween('start_date', [$request->start_date, $request->end_date])
                    ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
                    ->orWhere(function ($q) use ($request) {
                        $q->where('start_date', '<=', $request->start_date)
                            ->where('end_date', '>=', $request->end_date);
                    });
            })
            ->exists();

        if ($overlap) {
            return response()->json(['message' => 'Apartment already booked for these dates'], 409);
        }

        $days = now()->parse($request->start_date)
            ->diffInDays(now()->parse($request->end_date));

        $booking = Booking::create([
            'apartment_id' => $apartment->id,
            'tenant_id' => auth()->id(),
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => 'pending',
            'total_price' => $days * $apartment->price_per_day,
        ]);

        return response()->json($booking, 201);
    }

    /**
     * @OA\Post(
     *     path="/api/bookings/{id}/approve",
     *     tags={"Bookings"},
     *     summary="Approve booking (owner only)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Booking approved"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Booking not found")
     * )
     */
    public function approve($id)
    {
        $booking = Booking::with('apartment')->find($id);

        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        if ($booking->apartment->owner_id !== auth()->id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($booking->status !== 'pending') {
            return response()->json(['message' => 'Booking already processed'], 409);
        }

        DB::transaction(function () use ($booking) {

            // Lock tenant wallet
            $tenant = User::where('id', $booking->tenant_id)
                ->lockForUpdate()
                ->firstOrFail();

            // Lock owner wallet
            $owner = User::where('id', $booking->apartment->owner_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($tenant->wallet < $booking->total_price) {
                throw new HttpException(422, 'Insufficient wallet balance');
            }

            // Money transfer
            $tenant->wallet -= $booking->total_price;
            $owner->wallet += $booking->total_price;

            $tenant->save();
            $owner->save();

            // Approve booking
            $booking->status = 'approved';
            $booking->save();
        });
//        app(FirebaseNotificationService::class)->send(
//            $booking->tenant->fcm_token,
//            'Booking Approved',
//            'Your booking has been approved',
//            ['booking_id' => $booking->id]
//        );

        return response()->json([
            'message' => 'Booking approved, payment transferred successfully'
        ]);
    }


    /**
     * @OA\Post(
     *     path="/api/bookings/{id}/reject",
     *     tags={"Bookings"},
     *     summary="Reject booking (owner only)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Booking rejected"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Booking not found")
     * )
     */
    public function reject($id)
    {
        $booking = Booking::with('apartment')->find($id);

        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        if ($booking->apartment->owner_id !== auth()->id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $booking->status = 'rejected';
        $booking->save();

//        app(FirebaseNotificationService::class)->send(
//            $booking->tenant->fcm_token,
//            'Booking Rejected',
//            'Unfortunately, your booking was rejected'
//        );

        return response()->json(['message' => 'Booking rejected']);
    }

    /**
     * @OA\Get(
     *     path="/api/bookings",
     *     tags={"Bookings"},
     *     summary="List tenant bookings",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="List returned")
     * )
     */
    public function tenantBookings()
    {
        return response()->json(
            Booking::where('tenant_id', auth()->id())->paginate(10)
        );
    }

    /**
     * @OA\Get(
     *     path="/api/owner/bookings",
     *     tags={"Bookings"},
     *     summary="List owner bookings",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="List returned")
     * )
     */
    public function ownerBookings()
    {
        return response()->json(
            Booking::whereHas('apartment', function ($q) {
                $q->where('owner_id', auth()->id());
            })->paginate(10)
        );
    }

    /**
     * @OA\Put(
     *     path="/api/bookings/{id}",
     *     tags={"Bookings"},
     *     summary="Update booking (tenant only)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="start_date", type="string", format="date"),
     *             @OA\Property(property="end_date", type="string", format="date")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Booking updated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Booking not found"),
     *     @OA\Response(response=409, description="Dates already booked")
     * )
     */
    public function update(BookingRequest $request, $id)
    {
        $booking = Booking::with('apartment')->find($id);

        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        // Only tenant can update their own booking
        if ($booking->tenant_id !== auth()->id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Only allow updates for pending bookings
        if ($booking->status !== 'pending') {
            return response()->json(['message' => 'Can only update pending bookings'], 400);
        }

        $startDate = $request->start_date ?? $booking->start_date;
        $endDate = $request->end_date ?? $booking->end_date;

        // Validate that end_date is after start_date
        if (now()->parse($endDate)->lte(now()->parse($startDate))) {
            return response()->json(['message' => 'End date must be after start date'], 400);
        }

        // If dates are being changed, check for overlaps
        if ($request->has('start_date') || $request->has('end_date')) {
            $overlap = Booking::where('apartment_id', $booking->apartment_id)
                ->where('id', '!=', $booking->id)
                ->where('status', 'approved')
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('start_date', [$startDate, $endDate])
                        ->orWhereBetween('end_date', [$startDate, $endDate])
                        ->orWhere(function ($q) use ($startDate, $endDate) {
                            $q->where('start_date', '<=', $startDate)
                                ->where('end_date', '>=', $endDate);
                        });
                })
                ->exists();

            if ($overlap) {
                return response()->json(['message' => 'Apartment already booked for these dates'], 409);
            }

            // Recalculate total price if dates changed
            $days = now()->parse($startDate)->diffInDays(now()->parse($endDate));
            $booking->total_price = $days * $booking->apartment->price_per_day;
        }

        $booking->start_date = $startDate;
        $booking->end_date = $endDate;
        $booking->save();

        return response()->json(['message' => 'Booking updated', 'booking' => $booking]);
    }

    /**
     * @OA\Post(
     *     path="/api/bookings/{id}/cancel",
     *     tags={"Bookings"},
     *     summary="Cancel booking (tenant or owner)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Booking cancelled"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Booking not found"),
     *     @OA\Response(response=400, description="Booking already cancelled or cannot be cancelled")
     * )
     */
    public function cancel($id)
    {
        $booking = Booking::with('apartment')->find($id);

        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        // Check if user is tenant or owner
        $isTenant = $booking->tenant_id === auth()->id();
        $isOwner = $booking->apartment->owner_id === auth()->id();

        if (!$isTenant && !$isOwner) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Don't allow cancelling already cancelled bookings
        if ($booking->status === 'cancelled') {
            return response()->json(['message' => 'Booking is already cancelled'], 400);
        }

        $booking->status = 'cancelled';
        $booking->save();

        return response()->json(['message' => 'Booking cancelled', 'booking' => $booking]);
    }

    /**
     * @OA\Get(
     *     path="/api/apartments/{id}/bookings",
     *     tags={"Bookings"},
     *     summary="List bookings for one apartment",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Bookings list"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Apartment not found")
     * )
     */
    public function apartmentBookings($id)
    {

        $apartment = \App\Models\Apartment::findOrFail($id);

        $bookings = \App\Models\Booking::where('apartment_id', $id)
            ->with('tenant:id,first_name,last_name,email')
            ->orderBy('start_date')
            ->get();

        return response()->json($bookings);
    }

    /**
     * @OA\Get(
     *     path="/api/bookings/{id}",
     *     tags={"Bookings"},
     *     summary="Get booking details (tenant or owner)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Booking details returned"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Booking not found")
     * )
     */
    public function show($id)
    {
        $booking = Booking::with([
            'apartment',
            'tenant:id,first_name,last_name',
            'apartment.owner:id,first_name,last_name,email,wallet'
        ])->find($id);

        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        return response()->json($booking);
    }


}
