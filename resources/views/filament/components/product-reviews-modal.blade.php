<div class="space-y-4 p-2">
    @forelse($reviews as $review)
        <div class="p-4 rounded-xl border border-gray-200 bg-gray-50 dark:bg-gray-900 dark:border-gray-700 shadow-sm">
            <div class="flex justify-between items-start mb-3">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-gray-900 dark:text-white">{{ $review->user->name ?? 'Anonymous Customer' }}</span>
                        
                        <div class="flex text-yellow-500 text-xs">
                            {!! str_repeat('★', $review->rating) !!}{!! str_repeat('☆', 5 - $review->rating) !!}
                        </div>
                    </div>
                    <span class="text-xs text-gray-500">{{ $review->created_at->diffForHumans() }}</span>
                </div>

                @php
                    $badgeClasses = match($review->sentiment) {
                        'positive' => 'bg-green-100 text-green-700 border-green-200',
                        'negative' => 'bg-red-100 text-red-700 border-red-200',
                        'neutral'  => 'bg-blue-100 text-blue-700 border-blue-200',
                        default    => 'bg-gray-100 text-gray-700 border-gray-200',
                    };
                @endphp
                <span class="px-2 py-1 rounded-md text-[10px] font-bold uppercase border {{ $badgeClasses }}">
                    {{ $review->sentiment ?? 'Processing' }}
                </span>
            </div>

            <p class="text-sm text-gray-700 dark:text-gray-300 italic mb-3">
                "{{ $review->comment }}"
            </p>

            @if($review->ai_summary)
                <div class="mt-3 p-3 bg-white dark:bg-gray-800 rounded-lg border border-blue-100 dark:border-blue-900">
                    <div class="flex items-center gap-1 mb-1">
                        <span class="text-blue-600 text-xs">✨</span>
                        <span class="text-[11px] font-bold text-blue-600 uppercase tracking-wider">AI Insights</span>
                    </div>
                    <p class="text-xs text-blue-800 dark:text-blue-300 leading-relaxed">
                        {{ $review->ai_summary }}
                    </p>
                    
                    @if($review->key_features)
                        <div class="mt-2 flex flex-wrap gap-1">
                            @foreach($review->key_features as $feature)
                                <span class="px-2 py-0.5 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-[10px] rounded-full border border-blue-100 dark:border-blue-800">
                                    #{{ $feature }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        </div>
    @empty
        <div class="flex flex-col items-center justify-center py-10 text-gray-500">
            <x-heroicon-o-chat-bubble-bottom-center-text class="w-12 h-12 mb-2 opacity-20" />
            <p class="text-sm">No reviews yet for this product.</p>
        </div>
    @endforelse
</div>