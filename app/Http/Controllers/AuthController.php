<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;    
class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(Request $request){
        $validator = validator($request->all(), [
            "name" => "required|unique:users,name",
            "email" => "required|email|unique:users,email",
            "password" => "required|confirmed",
            "role" => "sometimes|in:user,scheduler,admin",
        ]);

        if($validator->fails()){
            return response()->json([
                "ok" => false,
                "message"=> "Request didn't pass validation", 
                "data" => $validator->errors()
            ], 400);
        }

        $user = User::create($validator->validated());
        $user->token = $user->createToken("api-token")->accessToken;

        return response()->json([
            "ok" => true,
            "message"=> "User created successfully",
            "data" => $user
        ], 201);
    }

    public function index(){
        return User::all();
    }

    /**
     * Search function finding users by name or email with validator
     */

    public function search(Request $request){
        $validator = validator($request->all(), [
            "search" => "required"
        ]);

        if($validator->fails()){
            return response()->json([
                "ok" => false,
                "message"=> "Request didn't pass validation", 
                "data" => $validator->errors()
            ], 400);
        }

        $users = User::where("name", "like", "%".$request->search."%")->orWhere("email", "like", "%".$request->search."%")->get();

        return response()->json([
            "ok" => true,
            "message"=> "Users found successfully",
            "data" => $users
        ], 200);
    }

    /**
     * Login function using auth() function 
     * with validation if the user input wrong credentials 3time it will be locked in 1 hour
     */

    public function login(Request $request){
        $validator = validator($request->all(), [
            "email" => "required|email",
            "password" => "required"
        ]);

        if($validator->fails()){
            return response()->json([
                "ok" => false,
                "message"=> "Request didn't pass validation", 
                "data" => $validator->errors()
            ], 400);
        }

        if(auth()->attempt($validator->validated())){
            $user = auth()->user();
            $user->token = $user->createToken("api-token")->accessToken;
            return response()->json([
                "ok" => true,
                "message"=> "User logged in successfully",
                "data" => $user
            ], 200);
        }

        return response()->json([
            "ok" => false,
            "message"=> "User not found",
            "data" => null
        ], 404);
        
    }
    // user1 = start =  nov 11  approved
    // user2 = start =  nov 11 rejected

    /**
     * Check token if it is valid
     */
    public function checkToken(Request $request){
        return response()->json([
            "ok" => true,
            "message"=>"User info has been retrieved",
            "data"=> $request->user()
        ], 200);
    }


    /**
     * logout user
     */

    public function logout(Request $request){
        $request->user()->token()->revoke();
        return response()->json([
            "ok" => true,
            "message"=>"User has been logged out",
            "data"=> null
        ], 200);
    }
}
