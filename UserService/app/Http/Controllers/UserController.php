<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        return new UserResource($users, 'Success', 'List of Users');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required',
            'password' => 'required',
            'address' => 'required',
            'phone' => 'required',
        ]);

        if ($validator->fails()) {
            return new UserResource(null, 'Failed', $validator->errors());
        }

        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->email_verified_at = now();
        $user->remember_token = Str::random(10);
        $user->address = $request->address;
        $user->phone = $request->phone;
        $user->save();

        return new UserResource($user, 'Success', 'User created successfully');
    }

    public function show($id)
    {
        $user = User::find($id);
        if ($user) {
            return new UserResource($user, 'Success', 'User found');
        } else {
            return new UserResource(null, 'Failed', 'User not found');
        }
    }

    public function getUserOrders($id)
{
    $user = User::find($id);

    if (!$user) {
        return response()->json([
            'status' => 'Failed',
            'message' => 'User not found',
            'data' => null
        ], 404);
    }

    $orderResponse = Http::get(
        env('ORDER_SERVICE_URL') . '/api/orders/user/filter',
        [
            'user_id' => $id
        ]
    );

    if ($orderResponse->failed()) {
        return response()->json([
            'status' => 'Failed',
            'message' => 'Gagal mengambil riwayat order dari OrderService',
            'data' => null
        ], 500);
    }

    return response()->json([
        'status' => 'Success',
        'message' => 'Riwayat order user berhasil diambil',
        'data' => [
            'user' => $user,
            'orders' => $orderResponse->json()
        ]
    ]);
}
    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if ($user) {
            if (!$user) {
                return new UserResource(null, 'Failed', 'User not found');
            }

            if ($request->has('name')) {
                $user->name = $request->name;
            }

            if ($request->has('email')) {
                $user->email = $request->email;
            }

            if ($request->has('password')) {
                $user->password = bcrypt($request->password);
            }

            if ($request->has('address')) {
                $user->address = $request->address;
            }

            if ($request->has('phone')) {
                $user->phone = $request->phone;
            }

            $user->save();

            return new UserResource($user, 'Success', 'User updated successfully');
        } else {
            return new UserResource(null, 'Failed', 'User not found');
        }
    }

    public function destroy($id)
    {
        $user = User::find($id);
        if ($user) {
            $user->delete();

            return new UserResource($user, 'Success', 'User deleted successfully');
        } else {
            return new UserResource(null, 'Failed', 'User not found');
        }
    }
}
