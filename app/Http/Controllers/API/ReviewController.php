<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Jobs\AnalyzeReviewAi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    /**
     * Store a newly created review and trigger AI analysis.
     */
    public function store(Request $request)
    {
        // 1. Validate the incoming data
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'rating'     => 'required|integer|min:1|max:5',
            'comment'    => 'required|string|min:10',
            // 'user_id'    => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        // 2. Create the Review record
        // $userId = auth()->id() ?? $request->user_id ?? 2;
        // Note: Using auth()->id() or a default for testing
        $review = Review::create([
            'product_id' => $request->product_id,
            'user_id'    => auth()->id(),
            'rating'     => $request->rating,
            'comment'    => $request->comment,
        ]);

        // 3. Dispatch the AI Analysis Job to the Queue
        // This sends the review to Gemini in the background
        AnalyzeReviewAi::dispatch($review);

        return response()->json([
            'status'  => 'success',
            'message' => 'Review submitted! AI analysis has started.',
            'data'    => $review->load('user')
        ], 201);
    }
}