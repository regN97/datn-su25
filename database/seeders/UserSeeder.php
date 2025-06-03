<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = [
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('12345678'),
            'email_verified_at' => Carbon::now(),
            'phone_number' => '0123456789',
            'address' => 'Ha Noi, Viet Nam',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'emergency_contact_name' => 'Emergency Contact',
            'emergency_contact_phone' => '0987654321',
            'emergency_contact_relation' => 'Family',
            'role_id' => 1,
            'is_active' => true,
            'last_login' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ];

        User::create($user);
    }
}
