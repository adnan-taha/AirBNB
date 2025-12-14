<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // REGISTER
    /**
     * @OA\Post(
     *     path="/api/register",
     *     tags={"Auth"},
     *     summary="Register a new user (tenant or owner)",
     *     description="Creates a new user account. User must be approved by admin before login.",
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"first_name","last_name","phone","password","role"},
     *
     *             @OA\Property(property="first_name", type="string", example="Adnan"),
     *             @OA\Property(property="last_name", type="string", example="Khalil"),
     *             @OA\Property(property="phone", type="string", example="0999999999"),
     *             @OA\Property(property="email", type="string", example="adnan@example.com"),
     *             @OA\Property(property="password", type="string", example="123456"),
     *             @OA\Property(property="role", type="string", example="tenant", enum={"tenant","owner"}),
     *             @OA\Property(property="birth_date", type="string", format="date", example="2000-05-10"),
     *             @OA\Property(property="photo", type="string", format="url", example="http://localhost:8000/storage/uploads/gqeLHBemszfLGTBJjJDIkPsarbPs125oqAU6OcnA.jpg"),
     *             @OA\Property(property="id_photo_front", type="string", format="url", example="http://localhost:8000/storage/uploads/gqeLHBemszfLGTBJjJDIkPsarbPs125oqAU6OcnA.jpg"),
     *             @OA\Property(property="id_photo_back", type="string", format="url", example="http://localhost:8000/storage/uploads/gqeLHBemszfLGTBJjJDIkPsarbPs125oqAU6OcnA.jpg"),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="User registered successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Registered successfully. Wait for admin approval."),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function register(RegisterRequest $request)
    {
        $data = $request->validated();

        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'],
            'phone'      => $data['phone'],
            'email'      => $data['email'] ?? null,
            'password'   => Hash::make($data['password']),
            'role'       => $data['role'],   // tenant / owner
            'birth_date' => $data['birth_date'] ?? null,
            'is_approved'=> false,           // admin must approve
        ]);

        return response()->json([
            'message' => 'Registered successfully. Wait for admin approval.',
            'user' => $user
        ]);
    }

    // LOGIN
    /**
     * @OA\Post(
     *     path="/api/login",
     *     tags={"Auth"},
     *     summary="Login user",
     *     description="Logs in a user using phone and password. Requires admin approval.",
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone","password"},
     *             @OA\Property(property="phone", type="string", example="0999999999"),
     *             @OA\Property(property="password", type="string", example="12345"),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Login successful"),
     *             @OA\Property(property="token", type="string", example="1|asdfasfsdafasdfadsfasdf"),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="User not approved by admin"
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Invalid phone or password"
     *     )
     * )
     */
    public function login(LoginRequest $request)
    {
        $user = User::where('phone', $request->phone)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid phone or password'], 401);
        }

        if (!$user->is_approved) {
            return response()->json(['message' => 'Account not approved by admin yet'], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => $user
        ]);
    }

    // LOGOUT
    /**
     * @OA\Post(
     *     path="/api/logout",
     *     tags={"Auth"},
     *     summary="Logout user",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successfully logged out",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Logged out successfully")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function logout()
    {
        auth()->user()->tokens()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    // Profile
    /**
     * @OA\Get(
     *     path="/api/me",
     *     tags={"Auth"},
     *     summary="Get authenticated user",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Authenticated user data",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="first_name", type="string", example="Adnan"),
     *             @OA\Property(property="last_name", type="string", example="Khalil"),
     *             @OA\Property(property="role", type="string", example="tenant"),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function me()
    {
        return response()->json(auth()->user());
    }

    //approve
    /**
     * @OA\Patch(
     *     path="/api/users/{id}/approve",
     *     summary="Approve a user by admin",
     *     tags={"Auth"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the user to approve",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User approved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User approved successfully."),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User not found.")
     *         )
     *     )
     * )
     */
    public function approveUser($id)
    {
        // Find the user by ID
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found.'
            ], 404);
        }

        // Optional: Prevent admin from approving themselves
        if ($user->role === 'admin') {
            return response()->json([
                'message' => 'Cannot approve an admin.'
            ], 400);
        }

        // Set is_approved to true
        $user->is_approved = true;
        $user->save();

        return response()->json([
            'message' => 'User approved successfully.',
            'user' => $user
        ]);
    }

    //all unapproved users
    /**
     * @OA\Get(
     *     path="/api/users/unapproved",
     *     summary="Get all unapproved users",
     *     tags={"Auth"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of unapproved users",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unapproved users retrieved successfully"),
     *             @OA\Property(
     *                 property="users",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=2),
     *                     @OA\Property(property="first_name", type="string", example="Alice"),
     *                     @OA\Property(property="last_name", type="string", example="Smith"),
     *                     @OA\Property(property="email", type="string", example="alice@example.com"),
     *                     @OA\Property(property="phone", type="string", example="123456789"),
     *                     @OA\Property(property="role", type="string", example="tenant"),
     *                     @OA\Property(property="is_approved", type="boolean", example=false)
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
    public function unapprovedUsers()
    {
        $users = User::where('is_approved', false)->get();

        if ($users->isEmpty()) {
            return response()->json([
                'message' => 'No unapproved users found.'
            ], 404);
        }

        return response()->json([
            'message' => 'Unapproved users retrieved successfully',
            'users' => $users
        ]);
    }



}
