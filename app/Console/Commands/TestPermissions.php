<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class TestPermissions extends Command
{
    protected $signature = 'test:permissions {email}';

    protected $description = 'Test user permissions';

    public function handle()
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("User with email {$email} not found.");

            return;
        }

        $this->info("Testing permissions for: {$user->name} ({$user->email})");
        $this->info('Roles: '.$user->roles->pluck('name')->join(', '));

        $permissions = [
            'view_products', 'edit_products', 'view_orders', 'edit_orders',
            'view_users', 'assign_roles', 'view_reports',
        ];

        foreach ($permissions as $permission) {
            $hasPermission = $user->can($permission) ? '✓' : '✗';
            $this->line("{$hasPermission} {$permission}");
        }
    }
}
