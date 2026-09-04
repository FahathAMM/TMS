<?php

namespace App\Services;

use App\Models\Administration\User;
use Illuminate\Support\Facades\Auth;

final class AuthUser
{
    public static function user(): ?User
    {
        return Auth::user();
    }

    public static function id(): ?int
    {
        return Auth::id();
    }

    public static function check(): bool
    {
        return Auth::check();
    }

    public static function guest(): bool
    {
        return Auth::guest();
    }

    public static function name(): ?string
    {
        return static::user()?->name;
    }

    public static function email(): ?string
    {
        return static::user()?->email;
    }

    public static function is(User|int $user): bool
    {
        return static::id() === ($user instanceof User ? $user->id : $user);
    }

    public static function hasRole(array|string $role): bool
    {
        return (bool) static::user()?->hasRole($role);
    }

    public static function can(string $permission): bool
    {
        return (bool) static::user()?->can($permission);
    }
}
