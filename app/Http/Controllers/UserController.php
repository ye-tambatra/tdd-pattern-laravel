<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        if ($user->id != $id) {
            return response()->json([], 401);
        }

        $user->delete();
        return response()->json($user);
    }
}
