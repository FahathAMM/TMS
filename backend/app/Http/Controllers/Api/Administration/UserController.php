<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $sortable = ['name', 'email', 'employee_id', 'is_active', 'joining_date', 'created_at'];
        $sort = in_array($request->sort, $sortable) ? $request->sort : 'created_at';
        $dir  = $request->dir === 'asc' ? 'asc' : 'desc';

        $users = User::with('roles', 'permissions')
            ->when($request->search, fn ($q) =>
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%")
                  ->orWhere('employee_id', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%"))
            ->orderBy($sort, $dir)
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'data' => UserResource::collection($users->items()),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
                'per_page'     => $users->perPage(),
                'total'        => $users->total(),
                'from'         => $users->firstItem(),
                'to'           => $users->lastItem(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'              => 'required|string|max:255',
            'email'             => 'required|email|unique:users',
            'password'          => ['required', Password::min(8)],
            'roles'             => 'nullable|array',
            'roles.*'           => 'string|exists:roles,name',
            'phone'             => 'nullable|string|max:20',
            'is_active'         => 'boolean',
            'avatar'            => 'sometimes|nullable|image|max:2048',
            'employee_id'       => 'nullable|string|max:50|unique:users,employee_id',
            'gender'            => 'nullable|in:male,female,other',
            'date_of_birth'     => 'nullable|date',
            'joining_date'      => 'nullable|date',
            'address'           => 'nullable|string|max:500',
            'emergency_contact' => 'nullable|string|max:100',
        ]);

        $roles = $data['roles'] ?? [];
        unset($data['roles']);

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user = User::create($data);
        $user->syncRoles(Role::whereIn('name', $roles)->where('guard_name', 'web')->get());

        return response()->json([
            'message' => 'User created successfully',
            'data'    => new UserResource($user->load('roles', 'permissions')),
        ], 201);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json(['data' => new UserResource($user->load('roles', 'permissions'))]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'name'              => 'sometimes|string|max:255',
            'email'             => 'sometimes|email|unique:users,email,' . $user->id,
            'password'          => ['sometimes', Password::min(8)],
            'roles'             => 'sometimes|nullable|array',
            'roles.*'           => 'string|exists:roles,name',
            'phone'             => 'nullable|string|max:20',
            'is_active'         => 'boolean',
            'avatar'            => 'sometimes|nullable|image|max:2048',
            'employee_id'       => 'nullable|string|max:50|unique:users,employee_id,' . $user->id,
            'gender'            => 'nullable|in:male,female,other',
            'date_of_birth'     => 'nullable|date',
            'joining_date'      => 'nullable|date',
            'address'           => 'nullable|string|max:500',
            'emergency_contact' => 'nullable|string|max:100',
        ]);

        if (array_key_exists('roles', $data)) {
            $names = $data['roles'] ?? [];
            $user->syncRoles(Role::whereIn('name', $names)->where('guard_name', 'web')->get());
            unset($data['roles']);
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($data);

        return response()->json([
            'message' => 'User updated successfully',
            'data'    => new UserResource($user->fresh(['roles', 'permissions'])),
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        if ($user->id === auth()->user()?->id) {
            return response()->json(['message' => 'Cannot delete your own account.'], 422);
        }

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }
}
