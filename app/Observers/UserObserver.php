<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user)
    {
        activity()->performedOn($user)->causedBy(auth()->user() ?? null)
            ->withProperties(['attributes' => $user->toArray()]) // Log all attributes
            ->log('User created: ' . $user->email);
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user)
    {
        activity()->performedOn($user)->causedBy(auth()->user() ?? null)
            ->withProperties(['old' => $user->getOriginal(), 'new' => $user->getChanges()]) // Log changed attributes
            ->log('User updated: ' . $user->email);
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
