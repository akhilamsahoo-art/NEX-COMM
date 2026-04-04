<?php

namespace App\Observers;

use App\Models\Review;
use App\Jobs\AnalyzeReviewAi;

class ReviewObserver
{
    public function created(Review $review): void
    {
        // Triggers the AI Job when a new review is created
        AnalyzeReviewAi::dispatch($review);
    }

    public function updated(Review $review): void
    {
        // Only re-run if the text of the comment actually changed
        if ($review->isDirty('comment')) {
            AnalyzeReviewAi::dispatch($review);
        }
    }

    public function deleted(Review $review): void
    {
        // Get the product BEFORE the review is fully erased from memory
        $product = $review->product;

        if ($product) {
            // We don't need to analyze the deleted review. 
            // We just need to trigger a fresh summary for the remaining reviews.
            // Create a temporary "fake" review object or a specific Job for Product Refresh.
            AnalyzeReviewAi::dispatch($review); 
        }
    }
}