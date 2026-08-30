@props([
    'source' => 'website',
    'title' => 'Ask Corner House',
    'showMessage' => true,
])

<div class="ch-chat" id="chChat" data-chat-widget data-source="{{ $source }}" data-show-message="{{ $showMessage ? '1' : '0' }}">
    <button type="button" class="ch-chat-toggle" data-chat-toggle aria-label="Open chatbot">
        <i class="bi bi-robot"></i>
    </button>
    <div class="ch-chat-panel d-none" data-chat-panel>
        <div class="ch-chat-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-robot me-1"></i>{{ $title }}</span>
            <button type="button" class="btn btn-sm btn-link text-white p-0" data-chat-toggle aria-label="Close">&times;</button>
        </div>
        @if ($showMessage)
            <div class="ch-chat-modes btn-group w-100 rounded-0" role="group">
                <button type="button" class="btn btn-sm btn-ch-primary" data-chat-mode="ask">Ask AI</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-chat-mode="message">Message us</button>
            </div>
        @endif
        <div class="ch-chat-body" data-chat-body>
            <div class="ch-chat-msg ai">Ask about availability, pricing, amenities, or house rules.</div>
        </div>
        <form class="ch-chat-form" data-chat-form>
            <input type="text" class="form-control" data-chat-input placeholder="Ask a question..." maxlength="2000" required>
            <button class="btn btn-ch-primary" type="submit">Send</button>
        </form>
        @if ($showMessage)
            <form class="ch-chat-form d-none flex-column align-items-stretch gap-2 p-2" data-message-form>
                <input type="text" class="form-control" data-msg-name placeholder="Your name" required>
                <input type="email" class="form-control" data-msg-email placeholder="Your email" required>
                <textarea class="form-control" data-msg-body rows="3" placeholder="Your message" maxlength="5000" required></textarea>
                <button class="btn btn-ch-primary" type="submit">Send message</button>
            </form>
        @endif
    </div>
</div>
