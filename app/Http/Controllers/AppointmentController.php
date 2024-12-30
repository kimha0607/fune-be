<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Appointment;
use App\Models\Clinic;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Schema(
 *     schema="Appointment",
 *     type="object",
 *     @OA\Property(property="id", type="integer", description="Appointment ID"),
 *     @OA\Property(property="patient_id", type="integer", description="Patient ID"),
 *     @OA\Property(property="doctor_id", type="integer", description="Doctor ID"),
 *     @OA\Property(property="clinic_id", type="integer", description="Clinic ID"),
 *     @OA\Property(property="appointment_time", type="string", format="date-time", description="Appointment time"),
 *     @OA\Property(property="status", type="string", enum={"pending", "completed", "cancelled"}, description="Appointment status")
 * )
 */


class AppointmentController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/appointments",
     *     tags={"Appointments"},
     *     summary="Retrieve a list of appointments",
     *     description="Fetch a paginated list of appointments with optional filters for patient name, doctor name, clinic name, status, and appointment time.",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="patient_name",
     *         in="query",
     *         description="Filter by patient name",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="doctor_name",
     *         in="query",
     *         description="Filter by doctor name",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="clinic_name",
     *         in="query",
     *         description="Filter by clinic name",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by appointment status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pending", "completed", "cancelled"})
     *     ),
     *     @OA\Parameter(
     *         name="start_time",
     *         in="query",
     *         description="Filter appointments starting from this time",
     *         required=false,
     *         @OA\Schema(type="string", format="date-time")
     *     ),
     *     @OA\Parameter(
     *         name="end_time",
     *         in="query",
     *         description="Filter appointments ending at this time",
     *         required=false,
     *         @OA\Schema(type="string", format="date-time")
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Field to sort by",
     *         required=false,
     *         @OA\Schema(type="string", enum={"appointment_time", "status"})
     *     ),
     *     @OA\Parameter(
     *         name="sort_order",
     *         in="query",
     *         description="Sort order (asc or desc)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Appointment")),
     *             @OA\Property(property="meta", type="object", description="Pagination metadata")
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
        $query = Appointment::query();

        $query->with(['patient', 'doctor', 'clinic']);

        if ($request->filled('patient_name')) {
            $query->whereHas('patient', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->patient_name . '%');
            });
        }

        if ($request->filled('doctor_name')) {
            $query->whereHas('doctor', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->doctor_name . '%');
            });
        }

        if ($request->filled('clinic_name')) {
            $query->whereHas('clinic', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->clinic_name . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('start_time') && $request->filled('end_time')) {
            $query->whereBetween('appointment_time', [$request->start_time, $request->end_time]);
        }

        if ($request->filled('sort_by') && $request->filled('sort_order')) {
            $query->orderBy($request->sort_by, $request->sort_order);
        } else {
            $query->orderBy('appointment_time', 'asc');
        }

        $appointments = $query->paginate(10);

        return response()->json($appointments);
    }

       /**
     * @OA\Get(
     *     path="/api/appointments/{id}",
     *     tags={"Appointments"},
     *     summary="Get details of a specific appointment",
     *     description="Retrieve detailed information of a specific appointment by its ID.",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the appointment",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(ref="#/components/schemas/Appointment")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Appointment not found",
     *         @OA\JsonContent(type="object", @OA\Property(property="error", type="string"))
     *     )
     * )
     */

     public function show($id)
     {
         $appointment = Appointment::with(['patient', 'doctor', 'clinic'])->find($id);
 
         if (!$appointment) {
             return response()->json(['error' => 'Appointment not found'], 404);
         }
 
         return response()->json($appointment);
     }
 

    /**
     * @OA\Post(
     *     path="/api/appointments",
     *     tags={"Appointments"},
     *     summary="Create a new appointment",
     *     description="Schedule a new appointment for a patient with a doctor at a specific clinic.",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"clinic_id", "doctor_id", "appointment_time"},
     *             @OA\Property(property="clinic_id", type="integer", description="ID of the clinic"),
     *             @OA\Property(property="doctor_id", type="integer", description="ID of the doctor"),
     *             @OA\Property(
    *                      property="appointment_time", 
    *                      type="string", 
    *                      format="date-time", 
    *                      description="Scheduled time for the appointment", 
    *                      example="2025-1-11 08:45:26"
    *                  )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Appointment created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Appointment created successfully"),
     *             @OA\Property(property="appointment", ref="#/components/schemas/Appointment")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *         @OA\JsonContent(type="object", @OA\Property(property="error", type="string"))
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(type="object", @OA\Property(property="errors", type="object"))
     *     )
     * )
     */


       /**
     * @OA\Get(
     *     path="/api/appointments/doctor/{doctor_id}",
     *     tags={"Appointments"},
     *     summary="Retrieve appointments for a specific doctor",
     *     description="Fetch a list of appointments for a specific doctor, with optional filters.",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="doctor_id",
     *         in="path",
     *         description="ID of the doctor",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by appointment status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pending", "completed", "cancelled"})
     *     ),
     *     @OA\Parameter(
     *         name="start_time",
     *         in="query",
     *         description="Filter appointments starting from this time",
     *         required=false,
     *         @OA\Schema(type="string", format="date-time")
     *     ),
     *     @OA\Parameter(
     *         name="end_time",
     *         in="query",
     *         description="Filter appointments ending at this time",
     *         required=false,
     *         @OA\Schema(type="string", format="date-time")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Appointment"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Doctor not found"
     *     )
     * )
     */
    public function getAppointmentsByDoctor($doctor_id, Request $request)
    {
        // Check if the doctor exists
        $doctor = User::where('id', $doctor_id)->where('role_id', 2)->first();

        if (!$doctor) {
            return response()->json(['error' => 'Doctor not found'], 404);
        }

        // Query appointments for the doctor
        $query = Appointment::where('doctor_id', $doctor_id)->with(['patient', 'clinic']);

        // Apply optional filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('start_time') && $request->filled('end_time')) {
            $query->whereBetween('appointment_time', [$request->start_time, $request->end_time]);
        }

        $appointments = $query->orderBy('appointment_time', 'asc')->get();

        return response()->json(['data' => $appointments], 200);
    }

    public function store(Request $request)
    {   
        $validator = Validator::make($request->all(), [
            'clinic_id' => 'required|exists:clinics,id',
            'doctor_id' => 'required|exists:users,id',
            'appointment_time' => 'required|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        Log::channel('single')->info('Validation passed:', $validated);

        $clinic = Clinic::findOrFail($validated['clinic_id']);

        $doctor = User::where('id', $validated['doctor_id'])->where('role_id', 2)->first();

        if (!$doctor) {
            return response()->json(['error' => 'Invalid doctor'], 400);
        }

        if (!$clinic->doctors->contains($doctor->id)) {
            return response()->json(['error' => 'Doctor does not work at this clinic'], 400);
        }

        $appointment = Appointment::create([
            'patient_id' => auth()->id(),
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinic->id,
            'appointment_time' => $validated['appointment_time'],
            'status' => 'pending',
        ]);

        return response()->json(['message' => 'Appointment created successfully', 'appointment' => $appointment], 201);
    }
}