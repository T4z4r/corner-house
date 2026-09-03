@props([
    'title' => 'Ask Corner House',
    'subtitle' => 'AI assistant · replies based on our guest pages',
])

<div class="chat" data-chat-widget>
    <button type="button" class="chat-toggle" data-chat-toggle aria-label="Open chat" aria-expanded="false">
        <svg class="chat-toggle-icon" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
        </svg>
        <span class="chat-toggle-close" aria-hidden="true">&times;</span>
    </button>

    <div class="chat-panel" data-chat-panel hidden>
        <div class="chat-header">
            <div>
                <strong>{{ $title }}</strong>
                <span class="chat-subtitle">{{ $subtitle }}</span>
            </div>
            <button type="button" data-chat-toggle aria-label="Close chat">&times;</button>
        </div>

        <div class="chat-modes" role="tablist" aria-label="Chat options">
            <button type="button" data-chat-mode="ask" role="tab" aria-selected="true">Ask AI</button>
            <button type="button" data-chat-mode="message" role="tab" aria-selected="false">Message us</button>
        </div>

        <div class="chat-body" data-chat-body>
            <div class="chat-msg ai">Ask about availability, pricing, amenities, or house rules.</div>
        </div>

        <form class="chat-form" data-chat-form data-mode-panel="ask">
            <input type="text" data-chat-input placeholder="Ask a question…" maxlength="2000" autocomplete="off" required>
            <button class="btn btn-primary" type="submit">Send</button>
        </form>

        <form class="chat-form chat-message-form" data-message-form data-mode-panel="message" hidden>
            <input type="text" data-msg-name placeholder="Your name" required>
            <input type="email" data-msg-email placeholder="Your email" required>
            <textarea data-msg-body rows="3" placeholder="Your message" maxlength="5000" required></textarea>
            <button class="btn btn-primary" type="submit">Send message</button>
        </form>
    </div>
</div>
