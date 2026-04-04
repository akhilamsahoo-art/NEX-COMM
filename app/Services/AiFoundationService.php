<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class AiFoundationService
{
    protected string $geminiKey;

    public function __construct()
    {
        $this->geminiKey = config('services.gemini.key') ?? '';
        if (empty($this->geminiKey)) {
            throw new Exception('Gemini API Key is missing in .env or config.');
        }
    }

    public function generate(string $taskKey, array $variables = [])
    {
        try {
            $config = config("ai_prompts.$taskKey");
            if (!$config) throw new Exception("Prompt template [$taskKey] not found.");

            $prompt = $this->buildPrompt($config['prompt'], $variables);
            $system = $config['system'] ?? 'You are a helpful assistant.';

            // ✅ RECOMMENDED: Use gemini-2.5-flash for stability. 
            // Change to 'gemini-3-flash-preview' only if you specifically need Gemini 3 features.
            $modelId = "gemini-2.5-flash"; 
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$modelId}:generateContent?key=" . $this->geminiKey;

            $response = Http::timeout(60)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, [
                    'contents' => [
                        [
                            'role' => 'user', 
                            'parts' => [['text' => $prompt]]
                        ]
                    ],
                    'system_instruction' => [
                        'parts' => [['text' => $system]]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'maxOutputTokens' => 4096,
                        // Removed thinkingConfig to prevent "Unknown name" errors
                    ]
                ]);

            if ($response->failed()) {
                throw new Exception("Gemini API Error: " . $response->body());
            }

            $aiResult = $response->json('candidates.0.content.parts.0.text');

            if (!$aiResult) {
                throw new Exception("Gemini returned an empty response. Check if the prompt was blocked by safety filters.");
            }

            $this->logToDB($taskKey, $prompt, $aiResult, $modelId);

            return $aiResult;

        } catch (Exception $e) {
            Log::error("Gemini Service Error: " . $e->getMessage());
            throw $e; 
        }
    }

    private function logToDB($taskKey, $prompt, $response, $model)
    {
        DB::table('ai_interactions')->insert([
            'task_name'  => $taskKey,
            'prompt'     => $prompt,
            'response'   => $response,
            'model'      => $model,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function buildPrompt(string $template, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $template = str_replace("{{{$key}}}", (string) $value, $template);
        }
        return $template;
    }
}