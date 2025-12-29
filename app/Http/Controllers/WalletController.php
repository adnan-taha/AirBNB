<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminWalletTopUpRequest;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class WalletController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/wallet/{id}",
     *     summary="Admin add money to user wallet",
     *     description="Allows admin to add money to any user's wallet by user ID",
     *     tags={"MONEY🤑"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Target user ID",
     *         @OA\Schema(type="integer", example=12)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"amount"},
     *             @OA\Property(
     *                 property="amount",
     *                 type="integer",
     *                 minimum=1,
     *                 example=1000,
     *                 description="Amount to add (must be positive)"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Wallet updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Wallet updated successfully"),
     *             @OA\Property(property="wallet", type="integer", example=3500)
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden (admin only)"),
     *     @OA\Response(response=404, description="User not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function topUp(AdminWalletTopUpRequest $request, $id)
    {
        // Ensure $id is numeric
        $id = (int) $id;

        $user = DB::transaction(function () use ($id, $request) {
            // Fetch user inside transaction and lock row
            $user = User::where('id', $id)
                ->lockForUpdate()
                ->first();

            if (!$user) {
                throw new ModelNotFoundException("User not found");
            }

            // Add amount to wallet
            $user->wallet += $request->amount;
            $user->save();

            return $user;
        });

        return response()->json([
            'message' => 'Wallet updated successfully',
            'wallet' => $user->wallet,
        ]);
    }
}
