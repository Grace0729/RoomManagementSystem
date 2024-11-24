<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Death;
use Illuminate\Support\Facades\Auth;

class DeathController extends Controller
{
    /**
     * Handle death addition or request based on user role.
     * Admins will add the death directly.
     * Non-admin users will request death, which will be pending approval from the admin.
     */
    public function store(Request $request)
    {
        $user = Auth::user(); 

        
        $validator = validator($request->all(), [
            "name" => "required|unique:deaths,name",
            "start_date" => "required|date",
            "end_date" => "required|date",
            "profession" => "required",
            "user_id" => "required|exists:users,id"
        ]);

        if ($validator->fails()) {
            return response()->json([
                "ok" => false,
                "message" => "Request didn't pass validation",
                "data" => $validator->errors()
            ], 400);
        }

        
        if ($user->role === 'admin') {
            $death = Death::create($validator->validated()); 

            return response()->json([
                "ok" => true,
                "message" => "Death created successfully",
                "data" => $death
            ], 201);
        }

        
        if ($user->role !== 'admin') {
            
            $deathRequest = Death::create([
                'name' => $request->name,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'profession' => $request->profession,
                'user_id' => $user->id,
                'status' => 'pending'  
            ]);

            return response()->json([
                "ok" => true,
                "message" => "Death request created successfully. Awaiting admin approval.",
                "data" => $deathRequest
            ], 201);
        }
    }

    /**
     * Admin approves or rejects death request.
     */
    public function updateDeathRequest(Request $request, $id)
    {
        
        $user = Auth::user();
        if ($user->role !== 'admin') {
            return response()->json([
                "ok" => false,
                "message" => "You are not authorized to approve/reject death requests."
            ], 403);
        }

        
        $validator = validator($request->all(), [
            'status' => 'required|in:approved,rejected'
        ]);

        if ($validator->fails()) {
            return response()->json([
                "ok" => false,
                "message" => "Invalid status provided. It must be either 'approved' or 'rejected'.",
                "data" => $validator->errors()
            ], 400);
        }

        
        $deathRequest = Death::find($id);

        if (!$deathRequest || $deathRequest->status !== 'pending') {
            return response()->json([
                "ok" => false,
                "message" => "Death request not found or already processed."
            ], 404);
        }

        
        $deathRequest->status = $request->status;
        $deathRequest->save();

        
        if ($request->status === 'approved') {
            
            Death::create($deathRequest->toArray());
            
        }

        return response()->json([
            "ok" => true,
            "message" => $request->status == 'approved' ? "Death request approved and added." : "Death request rejected.",
            "data" => $deathRequest
        ], 200);
    }

    /**
     * Get all death records (admin only).
     */
    public function index(){
        $user = Auth::user();
        
        if ($user->role !== 'admin') {
            return response()->json([
                "ok" => false,
                "message" => "You are not authorized to view all deaths."
            ], 403);
        }

        $deaths = Death::all();
        return response()->json([
            "ok" => true,
            "message" => "Deaths found successfully",
            "data" => $deaths
        ], 200);
    }
}

