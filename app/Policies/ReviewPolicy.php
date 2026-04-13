<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    /**
     * Determine whether the user can view any reviews.
     */
    public function viewAny(User $user): bool
    {
        // Allow Super Admin, Managers, and Sellers
        return in_array($user->role, ['super_admin', 'manager', 'seller']);
    }

    /**
     * Determine whether the user can view a specific review.
     */
    public function view(User $user, Review $review): bool
    {
        return in_array($user->role, ['super_admin', 'manager', 'seller']);
    }

    /**
     * Determine whether the user can delete a review.
     */
    public function delete(User $user, Review $review): bool
    {
        // Only Admin and Managers can delete reviews
        return in_array($user->role, ['super_admin', 'manager']);
    }
}