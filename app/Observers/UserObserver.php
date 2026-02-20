<?php

namespace App\Observers;

use App\Enums\ContactType;
use App\Enums\UserType;
use App\Models\Contact;
use App\Models\User;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        $isAppUser = in_array($user->user_type, [
            UserType::MERCHANT,
            UserType::NETWORK_MANAGER,
        ]);

        if ($isAppUser) {
            Contact::create([
                'user_id' => $user->id,
                'contact_type' => ContactType::BOTH,
                'is_default' => true,
            ]);
        }
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        //
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
