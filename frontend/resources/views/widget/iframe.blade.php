<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Chat Widget</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        #chat-box::-webkit-scrollbar { width: 4px; }
        #chat-box::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        
        .typing-dot {
            display: inline-block; width: 4px; height: 4px; border-radius: 50%;
            background: #6b7280; animation: wave 1.3s linear infinite;
        }
        .typing-dot:nth-child(2) { animation-delay: -1.1s; }
        .typing-dot:nth-child(3) { animation-delay: -0.9s; }
        @keyframes wave {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-4px); }
        }
    </style>
</head>
<body class="bg-transparent overflow-hidden">
    <div class="flex flex-col h-screen bg-white shadow-2xl overflow-hidden border border-gray-200">
        
        <!-- Header -->
        <div class="bg-blue-600 p-4 text-white font-bold shadow-md flex justify-between items-center">
            <div class="flex items-center gap-2">
                <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                <span class="truncate">{{ $chatbot->name }}</span>
            </div>
            <button onclick="window.parent.postMessage('closeChat', '*')" class="text-white hover:opacity-75">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>

        <!-- Messages Area -->
        <div id="chat-box" class="flex-1 p-4 overflow-y-auto space-y-4 bg-gray-50">
            <!-- Initial Bot Message -->
            <div class="flex justify-start">
                <div class="bg-white p-3 rounded-lg shadow-sm max-w-[85%] text-sm border border-gray-200 text-gray-800">
                    Hello! How can I help you today?
                </div>
            </div>
        </div>

        <!-- Typing Indicator -->
        <div id="typing-indicator" class="hidden px-4 py-2">
            <div class="flex justify-start">
                <div class="bg-gray-200 p-2 rounded-lg flex gap-1 items-center">
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                </div>
            </div>
        </div>

        <!-- Input Area -->
        <div class="p-3 bg-white border-t">
            <form id="chat-form" class="flex gap-2">
                <!-- IMPORTANT: Added 'name' attributes so FormData picks them up -->
                <input type="hidden" name="chatbot_id" value="{{ $chatbot->id }}">
                <input type="hidden" name="token" value="{{ $token }}"> 

                <input type="text" id="message-input" name="message" required 
                    placeholder="Write a message..." 
                    autocomplete="off"
                    class="flex-1 border border-gray-300 rounded-full px-4 py-2 text-sm focus:outline-none focus:border-blue-500">
                
                <button type="submit" id="send-btn" class="bg-blue-600 text-white rounded-full p-2 hover:bg-blue-700 disabled:bg-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                    </svg>
                </button>
            </form>
        </div>
    </div>
<script>
    // 1. SELECT ELEMENTS
    const chatForm = document.getElementById('chat-form');
    const messageInput = document.getElementById('message-input');
    const chatBox = document.getElementById('chat-box');
    const typingIndicator = document.getElementById('typing-indicator');
    const sendBtn = document.getElementById('send-btn');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // 2. CONFIGURATION
    // Unique key for this chatbot's conversation in this browser
    const storageKey = `chat_conv_id_${ {{ $chatbot->id }} }`;

    /**
     * Helper to add message bubbles to the UI
     */
    function appendMessage(sender, text) {
        const isBot = sender === 'bot';
        const wrapper = document.createElement('div');
        wrapper.className = `flex ${isBot ? 'justify-start' : 'justify-end'} animate-fade-in`;
        
        wrapper.innerHTML = `
            <div class="${isBot ? 'bg-white border border-gray-200 text-gray-800' : 'bg-blue-600 text-white'} 
                        p-3 rounded-lg shadow-sm max-w-[85%] text-sm break-words">
                ${text}
            </div>
        `;
        
        chatBox.appendChild(wrapper);
        // Smooth scroll to bottom
        chatBox.scrollTo({ top: chatBox.scrollHeight, behavior: 'smooth' });
    }

    /**
     * Handle Form Submission
     */
    chatForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const message = messageInput.value.trim();
        if (!message) return;

        // --- STEP A: CAPTURE DATA BEFORE CLEARING INPUT ---
        const formData = new FormData(chatForm);
        const existingConvId = localStorage.getItem(storageKey);
        
        if (existingConvId) {
            formData.append('conversation_id', existingConvId);
        }

        // --- STEP B: UPDATE UI ---
        appendMessage('user', message); // Show user message immediately
        messageInput.value = '';        // Now safe to clear
        typingIndicator.classList.remove('hidden'); // Show "thinking" dots
        sendBtn.disabled = true;
        chatBox.scrollTop = chatBox.scrollHeight;

        try {
            // --- STEP C: SEND TO SERVER ---
            const response = await fetch("{{ route('chat.send') }}", {
                method: 'POST',
                body: formData,
                headers: { 
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                }
            });

            const data = await response.json();

            if (response.ok && data.status === 'success') {
                // Save conversation ID so we stay in the same thread
                localStorage.setItem(storageKey, data.conversation_id);
                
                // Show bot response
                appendMessage('bot', data.bot_message);
            } else {
                // Handle Validation Errors or Auth Errors
                const errorMsg = data.message || 'Something went wrong.';
                appendMessage('bot', `Error: ${errorMsg}`);
            }

        } catch (error) {
            console.error('Fetch Error:', error);
            appendMessage('bot', 'Connection error. Please check your internet.');
        } finally {
            // --- STEP D: CLEANUP UI ---
            typingIndicator.classList.add('hidden');
            sendBtn.disabled = false;
            messageInput.focus();
        }
    });

    /**
     * INITIALIZATION
     */
    window.addEventListener('DOMContentLoaded', () => {
        // Scroll to bottom on load
        chatBox.scrollTop = chatBox.scrollHeight;
        
        // Focus input automatically
        messageInput.focus();

        // Check if we have an existing session
        const existingConvId = localStorage.getItem(storageKey);
        if (existingConvId) {
            console.log("Resuming conversation:", existingConvId);
            // Optional: You can fetch message history here if you want
        }
    });

    /**
     * CLOSE BUTTON LOGIC
     * Sends a message to the parent window (the website using the widget)
     * so it can hide the iframe.
     */
    function closeWidget() {
        window.parent.postMessage('closeChat', '*');
    }
</script>
</body>
</html>