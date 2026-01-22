<?php 

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\EmailOtp;
use Laravel\Passport\Token;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;





class AuthController extends Controller
{
    // REGISTER
    public function register(Request $request)
    {
        // Validate request
        $request->validate([
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        // Create full name
        $fullName = trim($request->firstName . ' ' . $request->lastName);
        
        // Check if name already exists
        // if (User::where('name', $fullName)->exists()) {
        //     return response()->json([
        //         'message' => 'A user with this name already exists',
        //         'errors' => [
        //             'name' => ['The name ' . $fullName . ' is already taken']
        //         ]
        //     ], 422);
        // }

        // Create user
        $user = User::create([
            'name' => $fullName,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'User registered successfully',
            // 'user' => $user,
        ], 201);
    }

    

    
    // LOGOUT
    public function logout(Request $request)
    {
        // Get the authenticated user
        $user = $request->user();
        
        // Get the user's active tokens
        $tokens = Token::where('user_id', $user->id)->where('revoked', false)->get();
        
        if ($tokens->isEmpty()) {
            return response()->json([
                'message' => 'No active tokens found for this user'
            ], 404);
        }
        
        // Revoke all active tokens for this user
        $revokedCount = Token::where('user_id', $user->id)
            ->where('revoked', false)
            ->update(['revoked' => true]);
        
        return response()->json([
            'message' => 'Successfully logged out',
            
        ], 200);
    }

// Send otp

    public function sendOtp(Request $request)
{
    $request->validate([
        'email' => 'required|email|exists:users,email'
    ]);

    $email = $request->email;

    // Check if there's an existing OTP with resend timer
    $existingOtp = EmailOtp::where('email', $email)
        ->where('resend_available_at', '>', now())
        ->first();

    if ($existingOtp) {
        $secondsLeft = $existingOtp->resend_available_at->diffInSeconds(now());

        return response()->json([
            // 'status' => false,
            'message' => "Please wait {$secondsLeft} seconds before requesting a new OTP."
        ], 429);
    }

    $otp = rand(100000, 999999);

    // OTP store / update with resend timer
    EmailOtp::updateOrCreate(
        ['email' => $email],
        [
            'otp' => $otp,
            'expires_at' => now()->addMinutes(5),
            'resend_available_at' => now()->addSeconds(30)
        ]
    );

    // SMTP mail
    Mail::raw("Your OTP is: $otp. It is valid for 5 minutes.", function ($msg) use ($email) {
        $msg->to($email)->subject('Email Verification OTP');
    });

    return response()->json([
        // 'status' => true,
        'message' => 'OTP sent successfully. You can resend OTP after 60 seconds.'
    ]);
}


// otp verification

public function verifyOtp(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'otp'   => 'required|numeric'
    ]);

    $otpData = EmailOtp::where('email', $request->email)
        ->where('otp', $request->otp)
        ->where('expires_at', '>=', now())
        ->first();

    if (!$otpData) {
        return response()->json([
            'status' => false,
            'message' => 'Invalid or expired OTP'
        ], 422);
    }

    // OTP delete
    $otpData->delete();

    // 🔐 Laravel inbuilt password reset token generate
    $user = User::where('email', $request->email)->first();
    if (!$user) {
        return response()->json([
            // 'status' => false,
            'message' => 'User not found'
        ], 404);
    }

    $token = Password::createToken($user);
    

    return response()->json([
        // 'status'  => true,
        'token'   => $token,
        'message' => 'Email verified.'
    ]);
}



public function changePassword(Request $request)
{
    $request->validate([
        'email'    => 'required|email',
        'token'    => 'required',
        'password' => 'required|string|min:6|confirmed'
    ]);

    $status = Password::reset(
        [
            'email' => $request->email,
            'token' => $request->token,
            'password' => $request->password,
            'password_confirmation' => $request->password_confirmation,
        ],
        function ($user, $password) {
            // 🔴 Old password same check
            if (Hash::check($password, $user->password)) {
                throw ValidationException::withMessages([
                    'password' => 'New password cannot be the same as your old password.'
                ]);
            }

            $user->password = Hash::make($password);
            $user->save();
        }
    );

    if ($status !== Password::PASSWORD_RESET) {
        return response()->json([
            'status' => false,
            'message' => __($status)
        ], 422);
    }

    // Remove any remaining OTP tokens for this email after successful password change
    EmailOtp::where('email', $request->email)->delete();

    return response()->json([
        'status' => true,
        'message' => 'Password changed successfully.'
    ]);
}

}