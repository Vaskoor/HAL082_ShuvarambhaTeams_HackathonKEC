<?php

namespace App\Http\Controllers;

use App\Models\Chatbot;
use App\Models\Conversation;
use App\Models\File;
use App\Models\Folder;
use App\Models\GuestUser;
use App\Models\Message;
use App\Models\Website;
use App\Models\WebsiteToken;
use App\Services\AIChatService;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ChatbotController extends Controller
{
    
    protected $aiChatService;

    public function __construct(AIChatService $aiChatService)
    {
        $this->aiChatService = $aiChatService;
    }
    public function store(Request $request)
    {
        // 1. VALIDATE ALL INPUTS FIRST
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'files_id' => 'nullable|array',
            'files_id.*' => 'exists:files,id', // Check if each file ID exists
            'folder_id' => 'nullable|array',
            'folder_id.*' => 'exists:folders,id', // Check if each folder ID exists
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            return DB::transaction(function () use ($request) {
                // 2. CREATE THE CHATBOT
                $chatbot = Chatbot::create([
                    'user_id' => Auth::id(),
                    'name'    => $request->name,
                    'status'  => 'draft',
                    'configuration' => [
                        'system_prompt' => '',
                        'few_shot_examples' => '',
                    ],
                ]);

                // 3. LOGIC TO MERGE FILE IDS
                // Get IDs directly from the files array
                $directFileIds = $request->input('files_id', []);

                // Get all file IDs that belong to the selected folders
                // Recursive function to get all child folder IDs
                function getAllChildFolderIds($folderIds) {
                    $allFolderIds = $folderIds;

                    // Get direct children of current folders
                    $childFolders = Folder::whereIn('parent_id', $folderIds)->pluck('id')->toArray();

                    if (!empty($childFolders)) {
                        // Merge child IDs and recurse
                        $allFolderIds = array_merge($allFolderIds, getAllChildFolderIds($childFolders));
                    }

                    return $allFolderIds;
                }

                // Get all folders including nested ones
                $folderIds = $request->input('folder_id', []);
                $allFolderIds = getAllChildFolderIds($folderIds);

                // Get all files in these folders
                $filesFromFolders = File::whereIn('folder_id', $allFolderIds)
                                        ->pluck('id')
                                        ->toArray();

                // Merge with direct file IDs
                $finalFileIds = array_unique(array_merge($directFileIds, $filesFromFolders));

                // 4. ATTACH TO PIVOT TABLE (chatbot_files)
                if (!empty($finalFileIds)) {
                    $chatbot->files()->attach($finalFileIds);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Chatbot created successfully!',
                    'data' => $chatbot
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }



    
    public function index(Request $request)
    {
        $chatbots = Chatbot::all();
        return view('chatbots.index', [
            'chatbots'=>$chatbots
        ]);
    }

    // public function edit(Request $request, Chatbot $chatbot)
    // {
    //     $selected_files = $chatbot->files()->get()->map(function($file){
    //         return [
    //             'id' => $file->id,
    //             'name' => $file->name,
    //             'file_sizes' => $file->size,
    //             'is_vectorized' => $file->is_vectorized,
    //             'configuration' => $file->configuration,
    //         ];
    //     });

    //     $go_back_url = url()->previous(); // previous page URL

    //     return view('chatbots.edit', [
    //         'chatbot' => [
    //             'id' => $chatbot->id,
    //             'name' => $chatbot->name,
    //             'description' => $chatbot->description,
    //             'selected_files' => $selected_files,
    //             'configurations' => $chatbot->configurations ? json_decode($chatbot->configurations, true) : [],
    //         ],
    //         'go_back_url' => $go_back_url,
    //     ]);
    // }



        /**
     * Show the edit page for a chatbot
     */
public function edit(Request $request, Chatbot $chatbot)
{
    $chatbot->load([
        'websites.tokens',
        'files.fileType'
    ]);

    $selected_files = $chatbot->files->map(function ($file) {
        return [
            'id' => $file->id,
            'name' => $file->filename,
            'file_sizes' => $file->filesize,
            'file_type' => $file->fileType ? $file->fileType->name : null,
            'is_vectorized' => $file->is_vectorized,
        ];
    });

    // ✅ Ensure every website has at least one token
    foreach ($chatbot->websites as $website) {
        if ($website->tokens->isEmpty()) {
            $website->tokens()->create([
                'token' => bin2hex(random_bytes(32)), // generate secure token
            ]);

            // reload tokens for this website
            $website->load('tokens');
        }
    }

    // Prepare websites with tokens
    $websites = $chatbot->websites->map(function ($website) {
        return [
            'id' => $website->id,
            'name' => $website->name,
            'domain' => $website->url, // use correct column name
            'tokens' => $website->tokens->map(function ($token) {
                return [
                    'id' => $token->id,
                    'token' => $token->token,
                    'created_at' => $token->created_at,
                ];
            }),
        ];
    });

    // ✅ Comma separated website domains
    $websiteUrls = $chatbot->websites
        ->pluck('url') // use actual column name (not domain)
        ->implode(',');

    // @dd($websites);
    return view('chatbots.edit', [
        'chatbot' => [
            'id' => $chatbot->id,
            'name' => $chatbot->name,
            'description' => $chatbot->description,
            'selected_files' => $selected_files,
            'configurations' => $chatbot->configurations
                ? json_decode($chatbot->configurations, true)
                : [],
            'websites' => $websiteUrls,
            'websites_list' => $websites,
        ],
        'go_back_url' => url()->previous(),
    ]);
}
//     public function edit(Request $request, Chatbot $chatbot)
// {
//     // Map selected files
//     $selected_files = $chatbot->files()->with('fileType')->get()->map(function ($file) {
//         return [
//             'id' => $file->id,
//             'name' => $file->filename,
//             'file_sizes' => $file->filesize,
//             'file_type' => $file->fileType ? $file->fileType->name : null, // access as property
//             'is_vectorized' => $file->is_vectorized,
//         ];
//     });

//     $response_data = [
//         'chatbot' => [
//             'id' => $chatbot->id,
//             'name' => $chatbot->name,
//             'description' => $chatbot->description,
//             'selected_files' => $selected_files,
//             'configurations' => $chatbot->configurations ? json_decode($chatbot->configurations, true) : [],
//         ],
//         'go_back_url' => url()->previous(),
//     ];

//     // Return JSON response with pretty print
//     return response()->json($response_data, 200, [], JSON_PRETTY_PRINT);
// }

    /**
     * Handle the POST request to update the chatbot
     */
public function update(Request $request, Chatbot $chatbot)
{
    // Validate basic chatbot info
    $request->validate([
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'system_prompts' => 'nullable|string',
        'few_shots' => 'nullable|array',
        'few_shots.*.input' => 'nullable|string',
        'few_shots.*.output' => 'nullable|string',
        'websites' => 'nullable|string', // comma-separated URLs
    ]);

    // Update chatbot basic info
    $chatbot->name = $request->input('name');
    $chatbot->description = $request->input('description');

    // Prepare configurations JSON
    $configurations = [
        'system_prompts' => $request->input('system_prompts'),
        'few_shots' => $request->input('few_shots', []),
    ];
    $chatbot->configurations = json_encode($configurations);

    // Save the chatbot
    $chatbot->save();

    // Handle websites
    $websitesInput = $request->input('websites', '');
    $websiteUrls = array_filter(array_map('trim', explode(',', $websitesInput)));

    // Retrieve existing websites or create new ones
    $websiteIds = [];
    foreach ($websiteUrls as $url) {
        $website = Website::firstOrCreate(['url' => $url]);
        $websiteIds[$website->id] = ['published_at' => now()];
    }

    // Sync with pivot table (chatbot_publish)
    $chatbot->websites()->sync($websiteIds);

    // Redirect back with success message
    return redirect()->route('chatbot.edit', $chatbot->id)
        ->with('success', 'Chatbot updated successfully!');
}






// public function loadWidget(Request $request, Chatbot $chatbot)
// {
//     $token = $request->query('token');

//     if (!$token) {
//         abort(403, 'Missing Security Token');
//     }

//     // 1. Find the token. 
//     // We check for BOTH the raw token and the hashed version 
//     // to ensure it works whether you use the professional hashing or direct strings.
//     $hashed = hash('sha256', $token);
//     $websiteToken = \App\Models\WebsiteToken::where('token', $token)
//         ->orWhere('token', $hashed)
//         ->first();

//     // 2. Security Validation
//     if (!$websiteToken) {
//         abort(403, 'Invalid Security Token');
//     }

//     // 3. Authorization: Is this chatbot allowed on this website?
//     // Check if the chatbot's website_id matches the token's website_id
//     dd($chatbot);
//     if ($chatbot->website_id !== $websiteToken->website_id) {
//         abort(403, 'Chatbot not authorized for this domain');
//     }

//     // 4. Update usage (Optional)
//     $websiteToken->update(['last_used_at' => now()]);

//     // 5. Return the HTML View with Iframe-specific headers
//     return response()
//         ->view('widget.iframe', [
//             'chatbot' => $chatbot,
//             'website' => $websiteToken->website
//         ])
//         ->header('X-Frame-Options', 'ALLOWALL') // Allow loading in iframe
//         ->header('Content-Security-Policy', 'frame-ancestors *'); // Modern browser security
// }



// public function loadWidget(Request $request, Chatbot $chatbot)
// {
//     $token = $request->query('token');

//     if (!$token) {
//         abort(403, 'No token provided');
//     }

//     // 1. Find the token record
//     $hashed = hash('sha256', $token);
//     $websiteToken = \App\Models\WebsiteToken::where('token', $token)
//         ->orWhere('token', $hashed)
//         ->first();

//     if (!$websiteToken) {
//         abort(403, 'Invalid token');
//     }

//     // 2. THE FIX: Use your isPublishedOn method!
//     // This checks the pivot table (chatbot_publish) to see if this 
//     // chatbot is linked to the website associated with the token.
//     if (!$chatbot->isPublishedOn($websiteToken->website_id)) {
//         abort(403, 'This chatbot is not published on this website.');
//     }

//     return response()
//         ->view('widget.iframe', [
//             'chatbot' => $chatbot,
//             'token'   => $token, // <-- Pass it here
//         ])
//         ->header('X-Frame-Options', 'ALLOWALL')
//         ->header('Content-Security-Policy', 'frame-ancestors *');
// }


public function loadWidget(Request $request, Chatbot $chatbot)
{
    $token = $request->query('token');

    if (!$token) {
        abort(403, 'No token provided');
    }

    $hashed = hash('sha256', $token);
    $websiteToken = WebsiteToken::with('website')
        ->where('token', $token)
        ->orWhere('token', $hashed)
        ->first();

    if (!$websiteToken || !$websiteToken->website) {
        abort(403, 'Invalid token or website not found');
    }

    $website = $websiteToken->website;
    $referer = $request->headers->get('referer');

    if ($referer) {
        $currentHost = strtolower(parse_url($referer, PHP_URL_HOST));
        $allowedHost = strtolower(parse_url($website->url, PHP_URL_HOST));

        // Remove 'www.' for flexible matching
        $currentHost = ltrim($currentHost, 'www.');
        $allowedHost = ltrim($allowedHost, 'www.');

        if ($currentHost !== $allowedHost) {
            abort(403, "Domain mismatch. Token registered for '{$allowedHost}', but accessed from '{$currentHost}'.");
        }
    }

    // Check relationship via the helper method in Chatbot model
    if (!$chatbot->isPublishedOn($website->id)) {
        abort(403, 'This chatbot is not published on this authorized website.');
    }

    return response()
        ->view('widget.iframe', [
            'chatbot' => $chatbot,
            'token'   => $token,
        ])
        ->header('X-Frame-Options', 'ALLOWALL')
        ->header('Content-Security-Policy', "frame-ancestors *");
}



    // // Send message
    // public function sendMessage(Request $request)
    // {
    //     // 1. Validation
    //     $request->validate([
    //         'message' => 'required|string',
    //         'chatbot_id' => 'required|exists:chatbots,id',
    //         'token' => 'required|string', // Token is now required in the body
    //     ]);

    //     // 2. Token Authentication
    //     // We check the 'token' field from the form/ajax request
    //     $plainToken = $request->input('token');
    //     $hashedToken = hash('sha256', $plainToken);

    //     // Look for the token (checking both raw and hashed for compatibility)
    //     $websiteToken = WebsiteToken::where('token', $plainToken)
    //         ->orWhere('token', $hashedToken)
    //         ->first();

    //     if (!$websiteToken) {
    //         return response()->json(['error' => 'Invalid or missing website token'], 401);
    //     }

    //     // 3. Security: Check if this Chatbot is actually published on this website
    //     $chatbot = Chatbot::findOrFail($request->chatbot_id);
    //     if (!$chatbot->isPublishedOn($websiteToken->website_id)) {
    //         return response()->json(['error' => 'Chatbot not authorized for this domain'], 403);
    //     }

    //     // 4. Handle Guest/User Identification
    //     // If it's the widget, we usually treat them as guests based on a session or unique ID
    //     $guest = null;
    //     $sessionToken = $request->session()->get('guest_token');

    //     if (!$sessionToken) {
    //         $sessionToken = Str::uuid();
    //         $guest = GuestUser::create([
    //             'session_token' => $sessionToken,
    //             'ip_address' => $request->ip(),
    //         ]);
    //         $request->session()->put('guest_token', $sessionToken);
    //     } else {
    //         $guest = GuestUser::where('session_token', $sessionToken)->first();
    //     }

    //     // 5. Get or Create Conversation
    //     $conversation = Conversation::firstOrCreate([
    //         'chatbot_id' => $chatbot->id,
    //         'guest_id' => $guest ? $guest->id : null,
    //         'user_id' => auth()->id() ?? null,
    //     ]);

    //     // 6. Save User Message
    //     $userMessage = Message::create([
    //         'conversation_id' => $conversation->id,
    //         'sender' => 'user',
    //         'message' => $request->message,
    //     ]);

    //     // 7. Generate Bot Reply (AI Logic goes here)
    //     $botResponse = "I received your message: " . $request->message; 
        
    //     $assistantMessage = Message::create([
    //         'conversation_id' => $conversation->id,
    //         'sender' => 'assistant',
    //         'message' => $botResponse,
    //     ]);

    //     return response()->json([
    //         'status' => 'success',
    //         'user_message' => $userMessage->message,
    //         'bot_message' => $assistantMessage->message,
    //         'conversation_id' => $conversation->id
    //     ]);
    // }




    // public function sendMessage(Request $request)
    // {
    //     $request->validate([
    //         'message' => 'required|string',
    //         'chatbot_id' => 'required|exists:chatbots,id',
    //         'token' => 'required|string',
    //         'conversation_id' => 'nullable' // Add this
    //     ]);

    //     // [Authentication & Token Logic - Keep your existing code here]
    //     $plainToken = $request->input('token');
    //     $hashedToken = hash('sha256', $plainToken);
    //     $websiteToken = WebsiteToken::where('token', $plainToken)->orWhere('token', $hashedToken)->first();
    //     if (!$websiteToken) return response()->json(['error' => 'Invalid token'], 401);

    //     $chatbot = Chatbot::findOrFail($request->chatbot_id);
    //     if (!$chatbot->isPublishedOn($websiteToken->website_id)) {
    //         return response()->json(['error' => 'Unauthorized'], 403);
    //     }

    //     $conversation = null;

    //     // 4. Handle Conversation Retrieval
    //     // First: Check if a Conversation ID was sent by JavaScript
    //     if ($request->filled('conversation_id')) {
    //         $conversation = Conversation::where('id', $request->conversation_id)
    //             ->where('chatbot_id', $chatbot->id)
    //             ->first();
    //     }

    //     // Second: Fallback to Session (for same-domain testing)
    //     if (!$conversation) {
    //         $sessionToken = $request->session()->get('guest_token');
    //         if ($sessionToken) {
    //             $guest = GuestUser::where('session_token', $sessionToken)->first();
    //             if ($guest) {
    //                 $conversation = Conversation::where('chatbot_id', $chatbot->id)
    //                     ->where('guest_id', $guest->id)
    //                     ->first();
    //             }
    //         }
    //     }

    //     // Third: If still no conversation, create a NEW guest and conversation
    //     if (!$conversation) {
    //         $sessionToken = Str::uuid();
    //         $guest = GuestUser::create([
    //             'session_token' => $sessionToken,
    //             'ip_address' => $request->ip(),
    //         ]);
    //         $request->session()->put('guest_token', $sessionToken);

    //         $conversation = Conversation::create([
    //             'chatbot_id' => $chatbot->id,
    //             'guest_id' => $guest->id,
    //             'user_type' => 'guest'
    //         ]);
    //     }

    //     // 5. Save Messages
    //     $userMessage = Message::create([
    //         'conversation_id' => $conversation->id,
    //         'sender' => 'user',
    //         'message' => $request->message,
    //     ]);

    //     $assistantMessage = Message::create([
    //         'conversation_id' => $conversation->id,
    //         'sender' => 'assistant',
    //         'message' => "Bot reply: " . $request->message,
    //     ]);

    //     return response()->json([
    //         'status' => 'success',
    //         'user_message' => $userMessage->message,
    //         'bot_message' => $assistantMessage->message,
    //         'conversation_id' => $conversation->id // JS will save this
    //     ]);
    // }











    public function sendMessage(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'chatbot_id' => 'required|exists:chatbots,id',
            'token' => 'required|string',
            'conversation_id' => 'nullable'
        ]);

        // 1. Authentication & Token Logic
        $plainToken = $request->input('token');
        $hashedToken = hash('sha256', $plainToken);
        $websiteToken = WebsiteToken::where('token', $plainToken)->orWhere('token', $hashedToken)->first();
        
        if (!$websiteToken) return response()->json(['error' => 'Invalid token'], 401);

        $chatbot = Chatbot::findOrFail($request->chatbot_id);
        if (!$chatbot->isPublishedOn($websiteToken->website_id)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $conversation = null;

        // 2. Handle Conversation Retrieval
        if ($request->filled('conversation_id')) {
            $conversation = Conversation::where('id', $request->conversation_id)
                ->where('chatbot_id', $chatbot->id)
                ->first();
        }

        if (!$conversation) {
            $sessionToken = $request->session()->get('guest_token');
            if ($sessionToken) {
                $guest = GuestUser::where('session_token', $sessionToken)->first();
                if ($guest) {
                    $conversation = Conversation::where('chatbot_id', $chatbot->id)
                        ->where('guest_id', $guest->id)
                        ->first();
                }
            }
        }

        if (!$conversation) {
            $sessionToken = (string) \Illuminate\Support\Str::uuid();
            $guest = GuestUser::create([
                'session_token' => $sessionToken,
                'ip_address' => $request->ip(),
            ]);
            $request->session()->put('guest_token', $sessionToken);

            $conversation = Conversation::create([
                'chatbot_id' => $chatbot->id,
                'guest_id' => $guest->id,
                'user_type' => 'guest'
            ]);
        }

        // 3. Save User Message
        $userMessage = Message::create([
            'conversation_id' => $conversation->id,
            'sender' => 'user',
            'message' => $request->message,
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
            $query = $userMessage->message;

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

        // Save Assistant Message
        $assistantMessage = Message::create([
            'conversation_id' => $conversation->id,
            'sender' => 'assistant',
            'message' => $botAnswer,
            'sources' => $botSourcesJson, // JSON stored in DB
        ]);
        // 7. Return Response
        return response()->json([
            'status' => 'success',
            'user_message' => $userMessage->message,
            'bot_message' => $assistantMessage->message,
            'conversation_id' => $conversation->id 
        ]);
    }

    // Load conversation messages
    public function loadMessages(Conversation $conversation)
    {
        return response()->json($conversation->messages()->orderBy('created_at')->get());
    }





    public function generateToken(Website $website)
    {
        $plainToken = WebsiteToken::generateForWebsite($website->id);

        return response()->json([
            'token' => $plainToken
        ]);
    }





}