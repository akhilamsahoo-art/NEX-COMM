<?php

namespace App\Jobs;

use App\Models\Review;
use App\Services\AiFoundationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class AnalyzeReviewAi implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public Review $review)
    {
        // Laravel automatically handles serialization
    }

    /**
     * Execute the job.
     */
    public function handle(AiFoundationService $aiService): void
    {
        try {
            // --- STEP 1: INDIVIDUAL REVIEW ANALYSIS ---
            // Wrap in a check: only analyze if the review still exists in DB
            if ($this->review->exists) {
                $aiResponse = $aiService->generate('review_analyzer', [
                    'comment' => $this->review->comment,
                    'rating'  => $this->review->rating,
                ]);

                $cleanJson = preg_replace('/^```json\s*|\s*```$/s', '', trim($aiResponse));
                $data = json_decode($cleanJson, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                    $this->review->update([
                        'sentiment'    => strtolower($data['sentiment'] ?? 'neutral'),
                        'ai_summary'   => $data['summary'] ?? 'No summary generated.',
                        'key_features' => $data['tags'] ?? [], 
                    ]);
                }
            }

            // --- STEP 2: PRODUCT-WIDE SUMMARY ---
            // Use 'withoutEvents' to prevent infinite loops if Product has observers
            $product = $this->review->product;

            if ($product) {
                $allReviewsText = $product->reviews()->pluck('comment')->filter()->implode('; ');

                if (!empty($allReviewsText)) {
                    $productSummary = $aiService->generate('review_summarizer', [
                        'reviews' => $allReviewsText,
                    ]);

                    $product->update(['ai_summary' => $productSummary]);
                }
            }
        } catch (Exception $e) {
            Log::error("AI Job Failed: " . $e->getMessage());
            throw $e; 
        }
    }
}