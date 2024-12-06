<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

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
    *     path="/users",
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
    public function index()
    {
        $users = User::all();
        return response()->json(['users' => $users]);
    }

    /**
     * @OA\Get(
     *     path="/users/{id}",
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
    *     path="/users",
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
            'role_id' => 'required|number',
            'password' => 'required|string|min:6',
            'active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::create([
            'email' => $request->email,
            'name' => $request->name,
            'phone' => $request->phone,
            'address' => $request->address,
            'role_id' => $request->role_id,
            'password' => Hash::make($request->password),
            'active' => $request->active,
        ]);

        return response()->json(['message' => 'User created successfully', 'user' => $user], 201);
    }

    /**
    * @OA\Put(
    *     path="/users/{id}",
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
            'email' => 'required|email|unique:users,email,' . $id,
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:15',
            'address' => 'nullable|string',
            'role_id' => 'required|string',
            'password' => 'nullable|string|min:6',
            'active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user->update([
            'email' => $request->email,
            'name' => $request->name,
            'phone' => $request->phone,
            'address' => $request->address,
            'role_id' => $request->role_id,
            'password' => $request->password ? Hash::make($request->password) : $user->password,
            'active' => $request->active,
        ]);

        return response()->json(['message' => 'User updated successfully', 'user' => $user]);
    }

    /**
    * @OA\Delete(
    *     path="/users/{id}",
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
    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }
}
