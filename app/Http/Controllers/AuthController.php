<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    // this controller is used for authentication related actions, such as retrieving the authenticated user
    public function user(Request $request)
    {
        return new UserResource($request->user());
    }
}
