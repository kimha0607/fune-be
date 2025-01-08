<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Appointment;
use App\Models\Clinic;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

/**
 * @OA\Schema(
 *     schema="Appointment",
 *     type="object",
 *     @OA\Property(property="id", type="integer", description="Appointment ID"),
 *     @OA\Property(property="patient_id", type="integer", description="Patient ID"),
 *     @OA\Property(property="doctor_id", type="integer", description="Doctor ID"),
 *     @OA\Property(property="clinic_id", type="integer", description="Clinic ID"),
 *     @OA\Property(property="appointment_time", type="string", format="date-time", description="Appointment time"),
 *     @OA\Property(property="status", type="string", enum={"pending", "confirmed", "cancelled"}, description="Appointment status")
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
     *         name="clinic_id",
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
 
         $query->with([
             'patient.children',
             'doctor',
             'clinic'
         ]);
 
         if ($request->filled('patient_id')) {
             $query->where('patient_id', $request->patient_id);
         }
 
         if ($request->filled('doctor_id')) {
             $query->where('doctor_id', $request->doctor_id);
         }
 
         if ($request->filled('clinic_id')) {
             $query->where('clinic_id', $request->clinic_id);
         }
 
         if ($request->filled('status')) {
             $query->where('status', $request->status);
         }
 
         if ($request->filled('start_time') && $request->filled('end_time')) {
             $query->whereBetween('appointment_time', [$request->start_time, $request->end_time]);
         }
 
         if ($request->filled('created_start_date') && $request->filled('created_end_date')) {
             $query->whereBetween('created_at', [
                 Carbon::parse($request->created_start_date)->startOfDay(),
                 Carbon::parse($request->created_end_date)->endOfDay()
             ]);
         } elseif ($request->filled('created_start_date')) {
             $query->whereDate('created_at', '>=', $request->created_start_date);
         } elseif ($request->filled('created_end_date')) {
             $query->whereDate('created_at', '<=', $request->created_end_date);
         }
 
         if ($request->filled('sort_by') && $request->filled('order')) {
             $query->orderBy($request->sort_by, $request->order);
         } else {
             $query->orderBy('appointment_time', 'asc');
         }
 
         // Lấy giá trị `per_page` từ request hoặc mặc định là 10
         $perPage = $request->input('per_page', 10);
         $appointments = $query->paginate($perPage);
 
         return ResponseHelper::success($appointments, 'Appointments retrieved successfully');
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
            return ResponseHelper::error('Appointment not found', [], 404);
        }

        return ResponseHelper::success($appointment);
    }

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
        $doctor = User::where('id', $doctor_id)->where('role_id', 2)->first();

        if (!$doctor) {
            return ResponseHelper::error('Doctor not found', [], 404);
        }

        $query = Appointment::where('doctor_id', $doctor_id)->with(['patient', 'clinic']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('start_time') && $request->filled('end_time')) {
            $query->whereBetween('appointment_time', [$request->start_time, $request->end_time]);
        }

        $appointments = $query->orderBy('appointment_time', 'asc')->get();

        return ResponseHelper::success($appointments);
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
     *             @OA\Property(property="patient_id", type="integer", description="ID of the patient", example=1),
     *             @OA\Property(property="doctor_id", type="integer", description="ID of the doctor", example=2),
     *             @OA\Property(property="clinic_id", type="integer", description="ID of the clinic", example=1),
     *             @OA\Property(property="dental_issue", type="string", example="caries", description="Dental issue of the appointment"), 
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


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:users,id',
            'doctor_id' => 'required|exists:users,id',
            'clinic_id' => 'required|exists:clinics,id',
            'dental_issue' => 'required|string',
            'appointment_time' => 'required|date|after:now',
        ]);

        if ($validator->fails()) {
        $errors = [];

        foreach ($validator->errors()->toArray() as $field => $messages) {
            foreach ($messages as $message) {
                if ($field == 'appointment_time' && $message == 'The appointment time must be a date after now.') {
                    $errors[] = [
                        'code' => 'E003',
                        'field' => 'appointment_time',
                    ];
                } else {
                    $errors[] = [
                        'code' => 'E999',
                        'message' => $message,
                        'field' => $field,
                    ];
                }
            }
        }

        return ResponseHelper::error('Validation error', $errors, 422);
    }
    
        // Create a new appointment
        $appointment = Appointment::create([
            'patient_id' => $request->patient_id,
            'doctor_id' => $request->doctor_id,
            'dental_issue' => $request->dental_issue,
            'clinic_id' => $request->clinic_id,
            'appointment_time' => $request->appointment_time,
            'status' => 'pending',
        ]);
    
        return ResponseHelper::success([
            'message' => 'Appointment created successfully',
            'appointment' => $appointment,
        ], 'Appointment created successfully', 201);
    }

    /**
     * @OA\Patch(
     *     path="/api/appointments/{id}/status",
     *     tags={"Appointments"},
     *     summary="Approve or reject an appointment",
     *     description="Update the status of an appointment to 'confirmed' or 'cancelled'.",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the appointment",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"status"},
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 enum={"approved", "rejected"},
     *                 description="New status of the appointment"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Status updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Appointment status updated successfully"),
     *             @OA\Property(property="appointment", ref="#/components/schemas/Appointment")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Appointment not found",
     *         @OA\JsonContent(type="object", @OA\Property(property="error", type="string"))
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Invalid status value",
     *         @OA\JsonContent(type="object", @OA\Property(property="error", type="string"))
     *     )
     * )
     */
    public function updateStatus($id, Request $request)
    {
        // Validate the status input
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:confirmed,cancelled',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error('Invalid status value', $validator->errors(), 422);
        }

        // Find the appointment by ID
        $appointment = Appointment::find($id);

        if (!$appointment) {
            return ResponseHelper::error('Appointment not found', [], 404);
        }

        // Update the status
        $appointment->status = $request->status;
        $appointment->save();

        return ResponseHelper::success([
            'message' => 'Appointment status updated successfully',
            'appointment' => $appointment
        ]);
    }


    public function statistics(Request $request)
    {
        $year = $request->get('year', now()->year);
    
        $appointments = Appointment::whereYear('appointment_time', $year)
            ->get()
            ->groupBy(function ($appointment) {
                return Carbon::parse($appointment->appointment_time)->month;
            });
    
        $fullStatistics = collect(range(1, 12))->map(function ($month) use ($appointments) {
            return [
                'month' => $month,
                'totalAppointments' => $appointments->has($month) ? $appointments->get($month)->count() : 0,
            ];
        });
    
        return ResponseHelper::success($fullStatistics, 'Statistics retrieved successfully');
    }

}