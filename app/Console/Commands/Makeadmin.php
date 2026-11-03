<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\BookingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class MakeAdmin extends Command
{
    protected $signature = 'angkorride:make-admin {phone : Phone number used to sign in} {name : Full name} {--email= : Optional email}';

    protected $description = 'Create an AngkorRide administrator (or promote an existing account) to sign in at /admin';

    public function handle(BookingService $svc): int
    {
        $phone = (string) $svc->normalizePhone($this->argument('phone'));

        if (! preg_match('/^\+?\d{8,15}$/', $phone)) {
            $this->error('That does not look like a phone number.');

            return self::FAILURE;
        }

        $existing = User::whereIn('phone', $svc->phoneVariants($phone))->first();

        if ($existing && $existing->role !== 'admin') {
            $this->error("{$existing->full_name} already has a {$existing->role} account with this number. Use a different number for the admin.");

            return self::FAILURE;
        }

        $password = $this->secret('Password (at least 8 characters)');

        if (strlen((string) $password) < 8 || $password !== $this->secret('Repeat the password')) {
            $this->error('Passwords must match and be at least 8 characters.');

            return self::FAILURE;
        }

        $user = $existing ?? new User(['phone' => $phone]);
        $user->fill([
            'full_name' => $this->argument('name'),
            'email' => $this->option('email') ?: $user->email,
            'password_hash' => Hash::make($password),
            'role' => 'admin',
            'is_verified' => true,
            'is_active' => true,
        ])->save();

        $this->info("Administrator ready. Sign in at /admin/login with {$user->phone}.");

        return self::SUCCESS;
    }
}