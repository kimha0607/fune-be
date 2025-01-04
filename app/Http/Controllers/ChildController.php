<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Child;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Schema(
 *     schema="Child",
 *     type="object",
 *     @OA\Property(property="id", type="integer", description="Child ID"),
 *     @OA\Property(property="user_id", type="integer", description="User ID of the parent"),
 *     @OA\Property(property="name", type="string", description="Name of child"),
 *     @OA\Property(property="dob", type="string", format="date", description="Date of birth of the child"),
 *     @OA\Property(property="gender", type="string", nullable=true, description="Gender of the child")
 * )
 */
class ChildController extends Controller
{
        /**
     * @OA\Get(
     *     path="/api/children/{userId}",
     *     tags={"Children Management"},
     *     summary="Retrieve all children of a user",
     *     description="Get a list of all children for a specific user.",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         required=true,
     *         description="ID of the user whose children are to be retrieved",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Children retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="children", type="array", @OA\Items(ref="#/components/schemas/Child"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function index($userId)
    {
        $user = User::find($userId);

        if (!$user) {
            return ResponseHelper::error('User not found', [], 410);
        }

        $children = $user->children;

        return ResponseHelper::success($children, 'Children retrieved successfully');
    }

        /**
     * @OA\Post(
     *     path="/api/children",
     *     tags={"Children Management"},
     *     summary="Create a new child",
     *     description="Register a new child under a user.",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Child")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Child created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Child")
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
            'name' => 'required|string|max:255',
            'user_id' => 'required|exists:users,id',
            'dob' => 'required|date',
            'gender' => 'nullable|string|max:10',
        ]);

        if ($validator->fails()) {
            $errors = collect($validator->errors())->map(function ($messages, $field) {
                return array_map(function ($message) use ($field) {
                    return [
                        "code" => "E001",
                        "field" => $field,
                        "message" => $message
                    ];
                }, $messages);
            })->flatten(1);

            return ResponseHelper::error('Validation failed', $errors, 422);
        }

        $child = Child::create($request->all());

        return ResponseHelper::success($child, 'Child created successfully', 201);
    }
}
