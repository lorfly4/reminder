<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::updateOrCreate([
            'email' => 'test@example.com',
        ], [
            'name' => 'Test User',
            'password' => Hash::make('password'),
            'timezone' => 'Asia/Jakarta',
            'language' => 'id',
            'theme' => 'system',
        ]);

        $defaults = [
            ['Personal', '#6366F1'], ['Work', '#3B82F6'], ['Education', '#8B5CF6'],
            ['Finance', '#10B981'], ['Health', '#EF4444'], ['Meeting', '#F59E0B'],
            ['Other', '#64748B'],
        ];
        foreach ($defaults as [$name, $color]) {
            $user->categories()->updateOrCreate(['name' => $name], ['color' => $color]);
        }
    }
}
