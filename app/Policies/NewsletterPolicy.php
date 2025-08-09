<?php

namespace App\Policies;

use App\Models\Newsletter;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NewsletterPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {

    }

    public function view(User $user, Newsletter $newsletter): bool
    {
    }

    public function create(User $user): bool
    {
    }

    public function update(User $user, Newsletter $newsletter): bool
    {
    }

    public function delete(User $user, Newsletter $newsletter): bool
    {
    }

    public function restore(User $user, Newsletter $newsletter): bool
    {
    }

    public function forceDelete(User $user, Newsletter $newsletter): bool
    {
    }
}
