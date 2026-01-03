<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\{UserResource};
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Http\Requests\Auth\{LoginRequest, UpdateProfileRequest, ChangePasswordRequest};

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $validated = $request->validated();

        Log::info('JWT TTL type:', [
            'value' => config('jwt.ttl'),
            'type' => gettype(config('jwt.ttl'))
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json(['error' => 'Email hoặc mật khẩu không đúng'], 401);
        }

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => (int)config('jwt.ttl') * 60,
            'user' => new UserResource($user)
        ]);
    }


    public function register(Request $request)
    {
        Log::info('=== REGISTER DEBUG ===');
        Log::info('All:', $request->all());
        Log::info('JSON:', $request->json()->all());

        $data = $request->json()->all();

        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users,email',
            'password' => 'required|string|min:6',
        ], [
            'name.required' => 'Tên là bắt buộc',
            'email.required' => 'Email là bắt buộc',
            'email.email' => 'Email không hợp lệ',
            'email.unique' => 'Email đã tồn tại',
            'password.required' => 'Mật khẩu là bắt buộc',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'customer',
        ]);

        return response()->json([
            'message' => 'Đăng ký thành công',
            'user' => new UserResource($user)
        ], 201);
    }

    public function me()
    {
        return new UserResource(auth('api')->user());
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        /** @var \App\Models\User $user */
        $user = auth('api')->user();
        $user->update($request->validated());

        return response()->json([
            'message' => 'Cập nhật hồ sơ thành công',
            'user' => new UserResource($user)
        ]);
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        /** @var \App\Models\User $user */
        $user = auth('api')->user();

        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json(['message' => 'Mật khẩu cũ không chính xác'], 400);
        }

        $user->update(['password' => Hash::make($request->new_password)]);

        try {
            JWTAuth::invalidate(JWTAuth::getToken());

            return response()->json([
                'success' => true,
                'message' => 'Đổi mật khẩu thành công. Vui lòng đăng nhập lại với mật khẩu mới.'
            ]);
        } catch (JWTException $e) {
            return response()->json(['message' => 'Đổi mật khẩu thành công, nhưng không thể hủy token cũ.']);
        }
    }

    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());
        return response()->json(['success' => true, 'message' => 'Đã đăng xuất']);
    }

    public function refresh()
    {
        $newToken = JWTAuth::refresh(JWTAuth::getToken());
        return response()->json([
            'access_token' => $newToken,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60
        ]);
    }

    public function users(Request $request)
    {
        $query = User::query();

        $query->when($request->search, function ($q, $search) {
            return $q->where(function ($subQuery) use ($search) {
                $subQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        });

        $query->when($request->role, function ($q, $role) {
            return $q->where('role', $role);
        });

        $query->orderBy('created_at', 'desc');

        $users = $query->paginate(10);

        return UserResource::collection($users);
    }
}
