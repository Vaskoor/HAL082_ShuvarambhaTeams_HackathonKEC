<?php

namespace App\Http\Controllers;

use App\Models\Chatbot;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\AIChatService;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    
    protected $aiChatService;

    public function __construct(AIChatService $aiChatService)
    {
        $this->aiChatService = $aiChatService;
    }
    public function index(Chatbot $chatbot, Conversation $conversation)
    {
        // Safety check: ensure conversation belongs to this chatbot
        if ($conversation->chatbot_id !== $chatbot->id) abort(403);
        
        return $conversation->messages;
    }

    public function store(Request $request, Chatbot $chatbot, Conversation $conversation)
    {
        $request->validate([
            'message' => 'required|string'
        ]);

        // 4. Prepare History for Ollama
        // Fetch all messages for this conversation to maintain context
        $history = Message::where('conversation_id', $conversation->id)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($msg) {
                return [
                    'role' => ($msg->sender === 'user') ? 'user' : 'assistant',
                    'content' => $msg->message
                ];
            })->toArray();

        // dd($chatbot->files);
        $vectorGroupIds= $chatbot->files
        ->map(function ($file) {
            $config = json_decode($file->configuration, true); // decode JSON to array
            return $config['file_group_id'] ?? null; // get file_group_id, fallback null
        })
        ->filter() // remove nulls if some files don't have file_group_id
        ->values() // reindex the array
        ->toArray();
        try {
            $query = $request->message;
            $configuration = json_decode($chatbot->configuration, true);
            $system_prompt = $configuration['system_prompts'] ?? '';
            $few_shots_examples = $configuration['few_shots'] ?? [];

            // Correct property access
            // $response = $this->aiChatService->ask($query, $vectorGroupIds, $history, $system_prompt, $few_shots_examples);
            // Get raw response from AI service
            $response = $this->aiChatService->ask($query, $vectorGroupIds, $history, $system_prompt, $few_shots_examples);

            $botAnswer = $response['answer'] ?? 'I apologize, but I encountered an issue processing your request.';
            $botSources = $response['sources'] ?? [];

            // Encode sources to JSON for database
            $botSourcesJson = json_encode($botSources);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Ollama Error: " . $e->getMessage());
            $botAnswer = "I'm sorry, I'm having trouble connecting to my brain right now.";
            $botSourcesJson = json_encode([]);
        }


        // Store user's message
        $userMessage = Message::create([
            'conversation_id' => $conversation->id,
            'sender' => 'user', // must match allowed ENUM values
            'message' => $request->message,
        ]);

        // Ensure 'assistant' is allowed in ENUM


        // Save Assistant Message
        $aiMessage = Message::create([
            'conversation_id' => $conversation->id,
            'sender' => 'assistant',
            'message' => $botAnswer,
            'sources' => $botSourcesJson, // JSON stored in DB
        ]);
        // // Store AI response in DB
        // $aiMessage = Message::create([
        //     'conversation_id' => $conversation->id,
        //     'sender' => 'assistant', // must match ENUM('user','assistant')
        //     'message' => "I am the {$chatbot->name}. You said: {$request->message}",
        // ]);

        return response()->json([
            'user_message' => $userMessage,
            'ai_message' => $aiMessage
        ]);
    }
}