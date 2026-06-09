<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function getUser($id)
    {
        $user = User::find($id);

        return response()->json([
            'status' => true,
            'data' => $user
        ]);
    }
}