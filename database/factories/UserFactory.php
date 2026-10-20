<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    /** Kata sandi terenkripsi yang digunakan ulang untuk data uji. */
    protected static ?string $password;

    /** Membuat akun vendor aktif untuk kebutuhan pengujian dan pengembangan. */
    public function definition(): array
    {
        return [
            'username' => fake()->unique()->userName(),
            'role' => 'vendor',
            'status' => 'active',
            'company_name' => fake()->company(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }
}
