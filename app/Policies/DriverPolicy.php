<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Driver;

class DriverPolicy
{
    /**
     * Determine if the given user can update the driver.
     */
    public function update(User $user, Driver $driver)
    {
        return $user->role === 'admin'; // only admin can update drivers
    }

    public function delete(User $user, Driver $driver)
    {
        return $user->role === 'admin';
    }
}
