<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Helpers\ResponseHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;


/**
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     required={"email", "name", "password"},
 *     @OA\Property(property="email", type="string", format="email"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="phone", type="string", nullable=true),
 *     @OA\Property(property="address", type="string", nullable=true),
 *     @OA\Property(property="role_id", type="number", default=1),
 *     @OA\Property(property="active", type="boolean", default=true)
 * )
 */


class UserController extends Controller
{
    /**
    * @OA\Get(
    *     path="/api/users",
    *     tags={"User Management"},
    *     summary="Retrieve all users",
    *     description="Get a list of all users.",
    *     security={{"bearerAuth": {}}},
    *     @OA\Response(
    *         response=200,
    *         description="Successful response",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="users", type="array", @OA\Items(ref="#/components/schemas/User"))
    *         )
    *     ),
    *     @OA\Response(
    *         response=401,
    *         description="Unauthorized"
    *     )
    * )
    */
    public function index(Request $request)
{
    $query = User::query();

    if ($request->has('id')) {
        $query->where('id', $request->id);
    }

    if ($request->has('email')) {
        $query->where('email', 'like', '%' . $request->email . '%');
    }

    if ($request->has('name')) {
        $query->where('name', 'like', '%' . $request->name . '%');
    }

    if ($request->has('phone')) {
        $query->where('phone', 'like', '%' . $request->phone . '%');
    }

    if ($request->has('role_id')) {
        $query->where('role_id', $request->role_id);
    }

    if ($request->has('active')) {
        $query->where('active', filter_var($request->active, FILTER_VALIDATE_BOOLEAN));
    }

    if ($request->has('sort_by')) {
        $sortBy = $request->input('sort_by');
        $order = $request->input('order', 'asc');

        // Kiểm tra cột hợp lệ
        $validColumns = ['id', 'name', 'email', 'role_id', 'active'];
        if (in_array($sortBy, $validColumns)) {
            $query->orderBy($sortBy, $order);
        }
    }

    try {
        $users = $query->paginate(10);

        return ResponseHelper::success(
            $users,
            'Users retrieved successfully'
        );
    } catch (\Exception $e) {
        return ResponseHelper::error(
            'Failed to retrieve users',
            ['exception' => $e->getMessage()],
            500
        );
    }
}
     /**
     * @OA\Get(
     *     path="/api/users/info",
     *     tags={"User Management"},
     *     summary="Retrieve user info based on token",
     *     description="Returns the authenticated user's information.",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="User info retrieved successfully",
     *         @OA\JsonContent(ref="#/components/schemas/User")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */

    public function getUserInfo(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return ResponseHelper::customError('Invalid credentials', [
                'token' => ['E999'],
            ], 403);
        }

        return ResponseHelper::success($user, 'Get user successful', 201);
    } 

    /**
     * @OA\Get(
     *     path="/api/users/{id}",
     *     tags={"User Management"},
     *     summary="Retrieve a specific user",
     *     description="Returns the details of a specific user by ID.",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the user to retrieve",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(ref="#/components/schemas/User")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     )
     * )
     */

    public function show($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        return response()->json(['user' => $user]);
    }


    /**
    * @OA\Post(
    *     path="/api/users",
    *     tags={"User Management"},
    *     summary="Create a new user",
    *     description="Register a new user in the system.",
    *     security={{"bearerAuth": {}}},
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\JsonContent(ref="#/components/schemas/User")
    *     ),
    *     @OA\Response(
    *         response=201,
    *         description="User created successfully",
    *         @OA\JsonContent(ref="#/components/schemas/User")
    *     ),
    *     @OA\Response(
    *         response=422,
    *         description="Validation error"
    *     )
    * )
    */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:15',
            'address' => 'nullable|string',
            'role_id' => 'required|integer',
            'password' => 'required|string|min:6',
            'active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            $errors = [];
    
            foreach ($validator->errors()->toArray() as $field => $messages) {
                foreach ($messages as $message) {
                    $errors[] = [
                        'code' => 'E999',
                        'message' => $message,
                        'field' => $field,
                    ];
                }
            }
    
            return ResponseHelper::error('Validation error', $errors, 422);
        }

        try {
            $user = User::create([
                'email' => $request->email,
                'name' => $request->name,
                'phone' => $request->phone,
                'address' => $request->address,
                'role_id' => $request->role_id,
                'password' => Hash::make($request->password),
                'active' => $request->active,
            ]);

            return ResponseHelper::success(
                $user,
                'User created successfully',
                201
            );
        } catch (\Exception $e) {
            return ResponseHelper::error(
                'Failed to create user',
                ['exception' => $e->getMessage()],
                500
            );
        }
    }


    /**
    * @OA\Put(
    *     path="/api/users/{id}",
    *     tags={"User Management"},
    *     summary="Update a user",
    *     description="Update an existing user's details.",
    *     security={{"bearerAuth": {}}},
    *     @OA\Parameter(
    *         name="id",
    *         in="path",
    *         required=true,
    *         description="ID of the user",
    *         @OA\Schema(type="integer")
    *     ),
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\JsonContent(ref="#/components/schemas/User")
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="User updated successfully",
    *         @OA\JsonContent(ref="#/components/schemas/User")
    *     ),
    *     @OA\Response(
    *         response=404,
    *         description="User not found"
    *     )
    * )
    */
    public function update(Request $request, $id)
    {
        $user = User::find($id);
    
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
    
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:15',
            'address' => 'nullable|string',
            'role_id' => 'required|numeric',
            'active' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            $errors = [];
            foreach ($validator->errors()->toArray() as $field => $messages) {
                foreach ($messages as $message) {
                    $errors[] = [
                        'code' => 'E999',
                        'message' => $message,
                        'field' => $field,
                    ];
                }
            }
            return ResponseHelper::error('Validation error', $errors, 422);
        }
    
        $user->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'address' => $request->address,
            'role_id' => $request->role_id,
            'active' => $request->active,
        ]);
        return ResponseHelper::success($user, 'User updated successfully', 201);
    }

    /**
    * @OA\Put(
    *     path="/api/users/change-password",
    *     tags={"User Management"},
    *     summary="Change user password",
    *     description="Change the password of the authenticated user.",
    *     security={{"bearerAuth": {}}},
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="current_password", type="string"),
    *             @OA\Property(property="new_password", type="string")
    *         )
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Password changed successfully",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="message", type="string", example="Password changed successfully")
    *         )
    *     ),
    *     @OA\Response(
    *         response=400,
    *         description="Invalid input or incorrect current password"
    *     )
    * )
    */

    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::customError('Validation error', $validator->errors(), 422);
        }

        $user = $request->user();
        
        
        if (!Hash::check($request->current_password, $user->password)) {
            return ResponseHelper::customError('The current password is incorrect', [], 401);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return ResponseHelper::success([], 'Password changed successfully', 200);
    }

    /**
    * @OA\Delete(
    *     path="/api/users/{id}",
    *     tags={"User Management"},
    *     summary="Delete a user",
    *     description="Remove a user from the system.",
    *     security={{"bearerAuth": {}}},
    *     @OA\Parameter(
    *         name="id",
    *         in="path",
    *         required=true,
    *         description="ID of the user",
    *         @OA\Schema(type="integer")
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="User deleted successfully"
    *     ),
    *     @OA\Response(
    *         response=404,
    *         description="User not found"
    *     )
    * )
    */
    public function destroy(Request $request)
    {
        $ids = $request->input('ids');
    
        if (empty($ids) || !is_array($ids)) {
            return ResponseHelper::customError('Invalid input', [
                'ids' => ['E999'],
            ], 422);
        }
    
        $users = User::whereIn('id', $ids)->get();
    
        if ($users->isEmpty() || empty($ids) || !is_array($ids)) {
            return ResponseHelper::customError('Users not found', [
                'user' => ['E004'],
            ], 422);
        }
    
        try {
            User::whereIn('id', $ids)->delete();
    
            return ResponseHelper::success(
                ['deleted_ids' => $ids],
                'Users deleted successfully'
            );
        } catch (\Exception $e) {
            return ResponseHelper::error(
                'Failed to delete users',
                ['exception' => $e->getMessage()],
                500
            );
        }
    }


    public function checkRole(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return ResponseHelper::customError('Unauthorized', [
                    'user' => ['E004'],
                ], 422);
            }

            $role = $user->role;

            return ResponseHelper::success([
                'role_id' => $user->role_id
            ], 'Role fetched successfully', 200);
        } catch (JWTException $e) {
            return ResponseHelper::customError('Token error', [
                'code' => 'E003',
                'message' => $e->getMessage()
            ], 401);
        }
    }
}

