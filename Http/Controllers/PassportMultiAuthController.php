<?php

namespace Nanissa\PassportMultiAuth\Http\Controllers;

use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Nanissa\PassportMultiAuth\Http\Requests\AuthLoginRequest;
use Nanissa\PassportMultiAuth\Http\Requests\AuthRegisterRequest;

class PassportMultiAuthController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        return view('passportmultiauth::index');
    }

    public function login(AuthLoginRequest $request)
    {
        if(!$this->checkForUser($request)) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }
        $credentials = request(['email', 'password'], $request->remember_me);
        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }
        $user = $request->user();
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->token;

        if ($request->remember_me)
            $token->expires_at = Carbon::now()->addWeeks(1);
            $token->save();
            return response()->json([
                'access_token' => $tokenResult->accessToken,
                'token_type' => 'Bearer',
                'expires_at' => Carbon::parse(
                    $tokenResult->token->expires_at
                )->toDateTimeString(),
            ]);

    }

    public function register(AuthRegisterRequest $request)
    {
        $request['password'] = Hash::make($request->password);

        $model = $this->getUserModel($request);

        return $model::create($request->all());
    }

    public function logout(Request $request)
    {
        return $request->user()->tokens->each(function ($token, $key) {
            $token->delete();
        });
    }

    public function getUserModel(Request $request)
    {
        $type = $request->user_type ?? 'user';
        $models = config('passportmultiauth.user_models');

        if (array_key_exists($type,(array)$models))
            return $models[$type];
        else
            return User::class;
    }

    public function checkForUser(Request $request)
    {
        $model = $this->getUserModel($request);
        $user = $model::where('email', $request->email)->first();
        if($user)
            return true;
        else
            return false;
    }
}
