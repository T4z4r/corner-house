export function initChatWidget() {
    const root = document.querySelector('[data-chat-widget]');
    if (!root || root.dataset.bound === '1') {
        return;
    }
    root.dataset.bound = '1';

    const panel = root.querySelector('[data-chat-panel]');
    const form = root.querySelector('[data-chat-form]');
    const messageForm = root.querySelector('[data-message-form]');
    const input = root.querySelector('[data-chat-input]');
    const body = root.querySelector('[data-chat-body]');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const source = root.dataset.source || 'website';
    const storageKey = 'cornerhouse.chatSession.' + source;
    let sessionId = null;

    try {
        sessionId = localStorage.getItem(storageKey);
    } catch (e) {
        sessionId = null;
    }
    if (!sessionId) {
        sessionId = (window.crypto && crypto.randomUUID) ? crypto.randomUUID() : String(Date.now());
        try {
            localStorage.setItem(storageKey, sessionId);
        } catch (e) {
            /* storage unavailable */
        }
    }

    const escapeHtml = (value) => String(value).replace(/</g, '&lt;');
    const addMsg = (text, cls) => {
        if (!body) {
            return;
        }
        body.insertAdjacentHTML('beforeend', '<div class="ch-chat-msg ' + cls + '">' + escapeHtml(text) + '</div>');
        body.scrollTop = body.scrollHeight;
    };

    const setOpen = (open) => {
        if (!panel) {
            return;
        }
        panel.classList.toggle('d-none', !open);
        root.classList.toggle('is-open', open);
    };

    document.querySelectorAll('[data-chat-toggle], [data-open-chat-widget]').forEach((el) => {
        el.addEventListener('click', (event) => {
            event.preventDefault();
            setOpen(panel.classList.contains('d-none'));
        });
    });

    const setMode = (mode) => {
        const isAsk = mode === 'ask';
        if (form) {
            form.classList.toggle('d-none', !isAsk);
        }
        if (messageForm) {
            messageForm.classList.toggle('d-none', isAsk);
        }
        root.querySelectorAll('[data-chat-mode]').forEach((btn) => {
            const active = btn.dataset.chatMode === mode;
            btn.classList.toggle('btn-ch-primary', active);
            btn.classList.toggle('btn-outline-secondary', !active);
        });
    };

    root.querySelectorAll('[data-chat-mode]').forEach((btn) => {
        btn.addEventListener('click', () => setMode(btn.dataset.chatMode));
    });

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const message = input?.value.trim();
        if (!message) {
            return;
        }
        addMsg(message, 'guest');
        input.value = '';
        addMsg('Thinking…', 'ai ch-chat-pending');
        try {
            const response = await fetch('/api/v1/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({ message, session_id: sessionId, source }),
            });
            const data = await response.json();
            root.querySelector('.ch-chat-pending')?.remove();
            addMsg(data.reply || 'Sorry, I could not answer that.', 'ai');
        } catch (e) {
            root.querySelector('.ch-chat-pending')?.remove();
            addMsg('The assistant is unavailable right now.', 'ai');
        }
    });

    messageForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const payload = {
            name: root.querySelector('[data-msg-name]')?.value.trim(),
            email: root.querySelector('[data-msg-email]')?.value.trim(),
            message: root.querySelector('[data-msg-body]')?.value.trim(),
            session_id: sessionId,
        };
        addMsg(payload.message, 'guest');
        try {
            const response = await fetch('/api/v1/messages', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify(payload),
            });
            const data = await response.json();
            addMsg(data.reply || 'Thanks — we have received your message and will reply by email.', 'ai');
            const bodyField = root.querySelector('[data-msg-body]');
            if (bodyField) {
                bodyField.value = '';
            }
        } catch (e) {
            addMsg('We could not send that message just now.', 'ai');
        }
    });
}
