<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Kelola akun (role: admin). Hanya ada dua jenis akun: admin dan seller.
 */
class AdminUserController extends Controller
{
    // GET /api/admin/users?role=admin|seller&q=
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'role' => ['nullable', 'in:'.implode(',', User::ROLES)],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $users = User::query()
            ->when($request->query('role'), fn ($q, $role) => $q->where('role', $role))
            ->when($request->query('q'), fn ($q, $term) => $q->where(
                fn ($w) => $w->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")
            ))
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        return UserResource::collection($users);
    }

    // POST /api/admin/users  (buat akun admin atau seller)
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'farm_name' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:100'],
            'password' => ['required', Password::min(8)],
            'role' => ['required', 'in:'.implode(',', User::ROLES)],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'farm_name' => $data['farm_name'] ?? null,
            'region' => $data['region'] ?? null,
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
        ]);

        return (new UserResource($user))->response()->setStatusCode(201);
    }
}
