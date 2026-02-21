(function() {
    // 1. Get the script tag that loaded this file
    const scriptTag = document.currentScript || 
                     document.querySelector('script[src*="chat-widget.js"]');
    
    // 2. Parse parameters from the SCRIPT URL (not the page URL)
    const scriptUrl = new URL(scriptTag.src);
    const chatbotId = scriptUrl.searchParams.get('chatbot_id');
    const token = scriptUrl.searchParams.get('token');
    const baseUrl = scriptUrl.origin; // Automatically gets https://your-laravel-app.com

    if (!chatbotId || !token) {
        console.error('Chatbot ID and token are required in the script URL');
        return;
    }

    const container = document.createElement('div');
    // ... (your existing styling)
    container.style.position = 'fixed';
    container.style.bottom = '20px';
    container.style.right = '20px';
    container.style.width = '350px';
    container.style.height = '500px';
    container.style.zIndex = "999999";

    const iframe = document.createElement('iframe');
    // Use the absolute baseUrl here
    iframe.src = `${baseUrl}/widget/load/${chatbotId}?token=${token}`;
    iframe.style.width = '100%';
    iframe.style.height = '100%';
    iframe.style.border = 'none';

    container.appendChild(iframe);
    document.body.appendChild(container);
})();