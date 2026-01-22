<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;



class ChnagePasswordController extends Controller
{
    public function password(Request $request)
{
    $request->validate([
        'old_password' => 'required',
        'new_password' => 'required|min:6|confirmed'
        // confirmed => new_password_confirmation field chahiye frontend se
    ]);

    $user = Auth::user(); // login user

    // Old password check
    if (!Hash::check($request->old_password, $user->password)) {
        return response()->json([
            'status' => false,
            'message' => 'Old password is incorrect'
        ], 400);
    }

    // Update new password
    $user->password = Hash::make($request->new_password);
    $user->save();

    return response()->json([
        'status' => true,
        'message' => 'Password changed successfully'
    ]);
}
}
