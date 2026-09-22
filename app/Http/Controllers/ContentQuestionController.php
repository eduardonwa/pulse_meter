<?php

namespace App\Http\Controllers;

use App\Mail\NewContentQuestion;
use App\Models\ContentQuestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ContentQuestionController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', 'in:es,en'],
            'question' => ['required', 'string', 'min:3', 'max:1000'],
            'name' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'website' => ['nullable', 'max:0'],
        ]);

        $question = ContentQuestion::query()->create([
            'locale' => $validated['locale'],
            'question' => $validated['question'],
            'name' => $validated['name'] ?? null,
            'email' => $validated['email'],
        ]);

        $recipient = config('mail.support_address')
            ?: config('mail.from.address');

        try {
            Mail::to($recipient)->send(new NewContentQuestion($question));
        } catch (Throwable $exception) {
            Log::warning('Could not send the content question notification.', [
                'content_question_id' => $question->getKey(),
                'error' => $exception->getMessage(),
            ]);
        }

        return response()->json(['submitted' => true], 201);
    }
}
