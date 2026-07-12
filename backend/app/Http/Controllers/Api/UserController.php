<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * CRUD user — HANYA superadmin (di-gate middleware 'role:superadmin' di route).
 */
class UserController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return UserResource::collection(
            User::orderBy('name')->paginate(15)
        );
    }

    public function store(StoreUserRequest $request): UserResource
    {
        // 'password' di-cast 'hashed' di model -> plain text otomatis di-hash.
        $user = User::create($request->validated());

        return (new UserResource($user))->additional(['message' => 'User berhasil dibuat.'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(User $user): UserResource
    {
        return new UserResource($user);
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $data = $request->validated();

        // password opsional: jangan timpa dengan null saat tidak diisi.
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return (new UserResource($user))->additional(['message' => 'User berhasil diperbarui.']);
    }

    public function destroy(User $user): \Illuminate\Http\Response
    {
        $user->delete();

        return response()->noContent();
    }
}
