<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Prompt Templates
    |--------------------------------------------------------------------------
    |
    | This file stores all your system instructions and prompt templates
    | for the AI Foundation Layer. Use {{variable}} syntax for placeholders.
    |
    */

    // 'product_generator' => [
    //     'system' => 'You are a professional e-commerce copywriter.',
    //     'prompt' => 'Write a catchy, high-converting product description for: {{name}}. Key features: {{features}}.'
    // ],
    'product_generator' => [
        'system' => 'You are a professional e-commerce copywriter.',
        'prompt' => 'Write a catchy product description for "{{name}}" based on these features: {{features}}. 
                     CRITICAL: The description MUST be between 300 and 400 characters total. 
                     Do not use long intros, get straight to the benefits.',
    ],

    'customer_support' => [
        'system' => 'You are a helpful support assistant for an e-commerce store.',
        'prompt' => 'Draft a polite and professional reply to this customer query: {{query}}'
    ],

    'seo_optimizer' => [
        'system' => 'You are an SEO expert.',
        'prompt' => 'Generate 5 high-ranking keywords and a meta description for a page about: {{topic}}'
    ],
    'review_analyzer' => [
        'system' => 'Return ONLY JSON. Analyze review sentiment (positive/negative/neutral), create a 10-word summary, and extract 3 short keyword tags.',
        'prompt' => 'Review Comment: "{{comment}}". 
                     Respond in this JSON format: {"sentiment": "...", "summary": "...", "tags": ["tag1", "tag2", "tag3"]}'
    ],
    'review_summarizer' => [
        'system' => 'You are a customer sentiment analyst for a high-end e-commerce store.',
        'prompt' => 'Based on this list of customer reviews: "{{reviews}}", write a single professional paragraph (max 400 characters) that summarizes the overall customer experience. Mention what people love and any common issues found in the feedback.',
    ],
];
