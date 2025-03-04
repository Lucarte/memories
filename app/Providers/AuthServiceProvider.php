<?php
namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->registerPolicies();

        // Register the custom gate for approveUser
        Gate::define('approveUser', function (User $user) {
            return $user->isAdmin(); // Only admins can approve users
        });
    }
}
