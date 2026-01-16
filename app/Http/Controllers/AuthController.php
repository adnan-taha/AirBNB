<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests\RegisterRequest;
use App\Http\Requests\AdminRegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Services\FirebaseNotificationService;
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
            'last_name' => $data['last_name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'password' => Hash::make($data['password']),
            'role' => $data['role'],   // tenant / owner
            'birth_date' => $data['birth_date'] ?? null,
            'photo' => $data['photo'] ?? '',
            'id_photo_front' => $data['id_photo_front'] ?? '',
            'id_photo_back' => $data['id_photo_back'] ?? '',
            'is_approved' => false,           // admin must approve
        ]);

        return response()->json([
            'message' => 'Registered successfully. Wait for admin approval.',
            'user' => $user
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/register/admin",
     *     tags={"Auth"},
     *     summary="Register a new admin",
     *     description="Creates a new admin account.",
     *     security={{"sanctum": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"first_name","last_name","phone","password"},
     *
     *             @OA\Property(property="first_name", type="string", example="Adnan"),
     *             @OA\Property(property="last_name", type="string", example="Khalil"),
     *             @OA\Property(property="phone", type="string", example="0999999999"),
     *             @OA\Property(property="email", type="string", example="adnan@example.com"),
     *             @OA\Property(property="password", type="string", example="123456"),
     *             @OA\Property(property="birth_date", type="string", format="date", example="2000-05-10"),
     *             @OA\Property(property="photo", type="string", format="url", example="http://localhost:8000/storage/uploads/gqeLHBemszfLGTBJjJDIkPsarbPs125oqAU6OcnA.jpg"),
     *             @OA\Property(property="id_photo_front", type="string", format="url", example="http://localhost:8000/storage/uploads/gqeLHBemszfLGTBJjJDIkPsarbPs125oqAU6OcnA.jpg"),
     *             @OA\Property(property="id_photo_back", type="string", format="url", example="http://localhost:8000/storage/uploads/gqeLHBemszfLGTBJjJDIkPsarbPs125oqAU6OcnA.jpg"),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Admin registered successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Registered successfully."),
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
    public function register_admin(AdminRegisterRequest $request)
    {
        $data = $request->validated();

        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'password' => Hash::make($data['password']),
            'role' => 'admin',
            'birth_date' => $data['birth_date'] ?? null,
            'photo' => $data['photo'] ?? '',
            'id_photo_front' => $data['id_photo_front'] ?? '',
            'id_photo_back' => $data['id_photo_back'] ?? '',
            'is_approved' => true,
        ]);

        return response()->json([
            'message' => 'Registered successfully.',
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

        // Set is_approved to true
        $user->is_approved = true;
        $user->save();

        app(FirebaseNotificationService::class)->send(
            $user->fcm_token,
            'Account Approved',
            'Your account is now active'
        );

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

    /**
     * @OA\Get(
     *     path="/api/health",
     *     summary="Check API health",
     *     security={{"sanctum": {}}},
     *     tags={"Health"},
     *     @OA\Response(
     *         response=200,
     *         description="API is healthy",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="healthy")
     *         )
     *     )
     * )
     */
    public function health()
    {
        $user = auth()->user();
        app(FirebaseNotificationService::class)->send(
            $user->fcm_token,
            'Booking Rejected',
            'Unfortunately, your booking was rejected'
        );
        return response()->json(['status' => 'healthy']);
    }

    /**
     * @OA\Post(
     *     path="/api/store-token",
     *     summary="Store FCM token for authenticated user",
     *     tags={"FCM"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"token"},
     *             @OA\Property(property="token", type="string", example="fcm_device_token_here")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Token saved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Token saved")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="array", @OA\Items(type="string"))
     *         )
     *     )
     * )
     */
    public function storeToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string'
        ]);

        $user = auth()->user();
        $user->fcm_token = $request->token;
        $user->save();

        return response()->json(['message' => 'Token saved']);
    }

    /**
     * @OA\Get(
     *     path="/api/users/all",
     *     tags={"Auth"},
     *     summary="Get all users with selected fields",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of users",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="users",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="first_name", type="string"),
     *                     @OA\Property(property="last_name", type="string"),
     *                     @OA\Property(property="photo", type="string"),
     *                     @OA\Property(property="is_approved", type="boolean")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function getAllUsers()
    {
        // Fetch users with only the specified columns
        $users = User::select('id', 'first_name', 'last_name', 'photo', 'is_approved')->get();

        return response()->json([
            'users' => $users,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/users/{id}",
     *     tags={"Auth"},
     *     summary="Get a single user by ID with selected fields",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User data",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="first_name", type="string"),
     *             @OA\Property(property="last_name", type="string"),
     *             @OA\Property(property="birth_date", type="string", format="date"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="photo", type="string"),
     *             @OA\Property(property="id_photo_front", type="string"),
     *             @OA\Property(property="id_photo_back", type="string"),
     *             @OA\Property(property="wallet", type="number", format="float"),
     *             @OA\Property(property="created_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=404, description="User not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function getUserById($id)
    {
        $user = User::select(
            'id',
            'first_name',
            'last_name',
            'birth_date',
            'phone',
            'photo',
            'id_photo_front',
            'id_photo_back',
            'wallet',
            'created_at'
        )
            ->where('id', $id)
            ->where('is_approved', true)
            ->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        return response()->json($user, 200);
    }


    /**
     * @OA\Get(
     *     path="/api/users/admin/{id}",
     *     tags={"Auth"},
     *     summary="Get a single user by ID with selected fields even if not approved",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User data",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="first_name", type="string"),
     *             @OA\Property(property="last_name", type="string"),
     *             @OA\Property(property="birth_date", type="string", format="date"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="photo", type="string"),
     *             @OA\Property(property="id_photo_front", type="string"),
     *             @OA\Property(property="id_photo_back", type="string"),
     *             @OA\Property(property="wallet", type="number", format="float"),
     *             @OA\Property(property="created_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=404, description="User not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function getUserByIdUn($id)
    {
        $user = User::select(
            'id',
            'first_name',
            'last_name',
            'birth_date',
            'phone',
            'photo',
            'id_photo_front',
            'id_photo_back',
            'wallet',
            'created_at'
        )->find($id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        return response()->json($user, 200);
    }
}
