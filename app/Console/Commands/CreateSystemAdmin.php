<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class CreateSystemAdmin extends Command
{
    protected $signature = 'app:create-admin';

    protected $description = 'Create the first system administrator account';

    public function handle(): int
    {
        if (User::where('role', 'Admin')->exists()) {
            $this->error('A system administrator already exists.');

            return self::FAILURE;
        }

        if (! Role::where('key', 'Admin')->exists()) {
            $this->error('The Admin role is missing. Run database migrations first.');

            return self::FAILURE;
        }

        $name = trim((string) $this->ask('Administrator full name'));
        $email = strtolower(trim((string) $this->ask('Administrator email')));

        if ($name === '' || mb_strlen($name) > 255) {
            $this->error('Name must contain between 1 and 255 characters.');

            return self::FAILURE;
        }

        if (strlen($email) > 255 || ! filter_var($email, FILTER_VALIDATE_EMAIL) || User::where('email', $email)->exists()) {
            $this->error('Enter a valid email address that is not already registered.');

            return self::FAILURE;
        }

        $password = (string) $this->secret('Password (at least 12 characters)');
        $confirmation = (string) $this->secret('Confirm password');

        if (mb_strlen($password) < 12 || ! hash_equals($password, $confirmation)) {
            $this->error('The password must contain at least 12 characters and both entries must match.');

            return self::FAILURE;
        }

        try {
            DB::transaction(function () use ($name, $email, $password): void {
                Role::where('key', 'Admin')->lockForUpdate()->firstOrFail();

                if (User::where('role', 'Admin')->exists()) {
                    throw ValidationException::withMessages(['admin' => 'A system administrator already exists.']);
                }

                User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => $password,
                    'role' => 'Admin',
                    'approval_status' => 'approved',
                    'email_verified_at' => now(),
                ]);
            });
        } catch (Throwable $exception) {
            Log::error('System administrator account creation failed.', [
                'exception' => $exception::class,
            ]);
            $this->error($exception instanceof ValidationException
                ? 'A system administrator already exists.'
                : 'The administrator account could not be created. Check the application logs.');

            return self::FAILURE;
        }

        $this->info("System administrator created for {$email}.");

        return self::SUCCESS;
    }
}
