<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $userToDelete = User::find($id);

        if (!$userToDelete) {
            return response()->json([
                'message' => 'User not found with the id : ' . $id
            ], 404);
        }

        if ($userToDelete->isRoot()) {
            return $this->unauthorized();
        }

        if (!$user->isRoot() && !$user->isAdmin() &&  $userToDelete->id != $user->id) {
            return $this->unauthorized();
        }

        if ($user->isAdmin() && $userToDelete->isAdmin()) {
            return $this->unauthorized();
        }

        $userToDelete->delete();

        return response()->json($user);
    }

    private function unauthorized()
    {
        return response()->json([
            'message' => 'Unauthorized.'
        ], 401);
    }
}
