<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use App\Services\UserServices;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log; // Fixed the Log import

class UserController extends Controller
{
    protected $userServices;

    public function __construct(UserServices $userServices)
    {
        $this->userServices = $userServices;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $allUsers = User::all();

            return response()->json([
                'success' => true,
                'message' => 'Users fetched successfully',
                'data'    => $allUsers
            ], 200);
        } catch (Exception $e) {
            Log::error("Index Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch users',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request)
    {
        try {
            $validated = $request->validated();
            $userCreated = $this->userServices->createUser($validated);

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'data'    => $userCreated
            ], 201);
        } catch (Exception $e) {
            Log::error("Store Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'User creation failed',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $user
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        try {
            $updatedUser = $this->userServices->updateUser($request->validated(), $user);

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $updatedUser
            ], 200);
        } catch (Exception $e) {
            Log::error("Update Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        try {
            
            if (auth()->id() === $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Security: You cannot delete your own account from this panel.'
                ], 403);
            }

            $this->userServices->deleteUser($user);

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ], 200);
        } catch (Exception $e) {
            Log::error("Delete Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
