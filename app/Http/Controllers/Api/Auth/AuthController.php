<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Mail\ResetPassword;
use App\Mail\ResetPasswordNotification;
use App\Mail\VerifyEmail;
use App\Models\User;
use Carbon\Carbon;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    use ApiResponse;

    public function register(Request $request)
    {

        //Validation
        $attr = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed'
        ]);

        $user = User::create([
            'name' => $attr['name'],
            'email' => $attr['email'],
            'password' => bcrypt($attr['password'])
        ]);

        if ($user) {
            $verify =  DB::table('password_resets')->where([
                ['email', $request->all()['email']]
            ]);

            if ($verify->exists()) {
                $verify->delete();
            }

            $code = rand(100000, 999999);
            DB::table('password_resets')
                ->insert(
                    [
                        'email' => $request->all()['email'],
                        'token' => $code
                    ]
                );
        }

        Mail::to($request->email)->send(new VerifyEmail($code));
        

        return $this->success([
            'message' => 'Successful created user. Please check your email for a 6-digit pin to verify your email.',
            'token' => $user->createToken('API Token')->plainTextToken
        ]);
    }

    public function verifyEmail(Request $request)
    {

        $validation = $request->validate([
            'token' => 'required'
        ]);

        $select = DB::table('password_resets')
            ->where('email', Auth::user()->email)
            ->where('token', $request->token);

        if ($select->get()->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid code',
            ], 400);
        }

        $select = DB::table('password_resets')
            ->where('email', Auth::user()->email)
            ->where('token', $request->token)
            ->delete();

        $user = User::find(Auth::user()->id);
        $user->email_verified_at = Carbon::now()->getTimestamp();
        $user->save();

        return new JsonResponse(['success' => true, 'message' => "Email verified successfully"], 200);
    }

    public function login(Request $request)
    {
        $attr = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string|min:8'
        ]);

        if (!Auth::attempt($attr)) {
            return $this->error('Invalid login details', 401);
        }

        return $this->success([
            'token' => auth()->user()->createToken('API Token')->plainTextToken
        ]);
    }

    public function logout()
    {
        auth()->user()->tokens()->delete();

        return [
            'message' => 'Logout Successful'
        ];
    }

    public function forgotPassword(Request $request)
    {

        $attr = $request->validate([
            'email' => 'required|string|email|max:255'
        ]);

        $verify = User::where('email', $request->all()['email'])->exists();

        if ($verify) {
            $verify2 =  DB::table('password_resets')->where([
                ['email', $request->all()['email']]
            ]);

            if ($verify2->exists()) {
                $verify2->delete();
            }

            $token = random_int(100000, 999999);
            $password_reset = DB::table('password_resets')->insert([
                'email' => $request->all()['email'],
                'token' =>  $token,
                'created_at' => Carbon::now()
            ]);

            if ($password_reset) {
                Mail::to($request->all()['email'])->send(new ResetPassword($token));

                return response()->json([
                    'success' => true,
                    'message' => 'Please check your email for a 6 digit code',
                ], 200);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'This email does not exist in our records',
            ], 400);
        }
    }

    public function verifyToken(Request $request)
    {
        $attr = $request->validate([
            'email' => 'required|string|email|max:255',
            'token' => 'required'
        ]);

        $check = DB::table('password_resets')->where([
            ['email', $request->all()['email']],
            ['token', $request->all()['token']],
        ]);

        if ($check->exists()) {
            //Check if token is expired
            $difference = Carbon::now()->diffInSeconds($check->first()->created_at);
            if ($difference > 360) {
                return response()->json([
                    'success' => false,
                    'message' => 'This token has expired',
                ], 400);
            }

            $delete = DB::table('password_resets')->where([
                ['email', $request->all()['email']],
                ['token', $request->all()['token']],
            ])->delete();

            return response()->json([
                'success' => true,
                'message' => 'You can now request another token',
            ], 200);

        }else{
            return response()->json([
                'success' => false,
                'message' => 'The token provided is invalid',
            ], 401);
        }
    }

    public function resetPassword(Request $request){
        $attr = $request->validate([
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8|confirmed'
        ]);

        $user = User::where('email', $attr['email'])->first();

        $user->update([
            'password' => bcrypt($attr['password']) 
        ]);

        Mail::to($request->email)->send(new ResetPasswordNotification($user->name));

        return response()->json([
            'success' => true,
            'message' => 'Your password has been reset successfully',
            'token' => $user->first()->createToken('API Token')->plainTextToken
        ], 200);

    }
}
