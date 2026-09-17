const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

function page(result = { paymentIntent: { status: 'requires_capture' } }) {
    const nodes = new Map();
    const node = id => {
        if (!nodes.has(id)) nodes.set(id, { checked: true, disabled: false, hidden: true, events: {}, addEventListener(type, callback) { this.events[type] = callback; } });
        return nodes.get(id);
    };
    const payment = { events: {}, mount() {}, on(type, callback) { this.events[type] = callback; } };
    const calls = [], redirects = [];
    const elements = { create: () => payment, submit: async () => ({}) };
    const stripe = { elements: () => elements, confirmPayment: async options => { calls.push(options); return result; } };
    let source = fs.readFileSync('resources/views/website/booking/security-deposit.blade.php', 'utf8').split('<script>')[1].split('</script>')[0];
    source = source.replace(/@json\([^\n]+\)/g, '"test-value"');
    vm.runInNewContext(source, { document: { getElementById: node, addEventListener: (_, callback) => callback() }, Stripe: () => stripe, window: { Stripe: () => stripe, location: { assign: url => redirects.push(url) } } });
    return { node, payment, calls, redirects, submit: () => node('deposit-form').events.submit({ preventDefault() {} }) };
}

test('security hold requires complete card details and explicit consent', async () => {
    const checkout = page();
    await checkout.submit();
    assert.equal(checkout.calls.length, 0);
    checkout.payment.events.change({ complete: true });
    checkout.node('deposit-consent').checked = false;
    await checkout.submit();
    assert.equal(checkout.calls.length, 0);
    checkout.node('deposit-consent').checked = true;
    await checkout.submit();
    assert.equal(checkout.calls[0].redirect, 'if_required');
    assert.equal(checkout.redirects.length, 1);
});

test('failed hold authorisation remains retryable and never shows success', async () => {
    const checkout = page({ error: { message: 'Card declined' } });
    checkout.payment.events.change({ complete: true });
    await checkout.submit();
    assert.equal(checkout.node('deposit-error').textContent, 'Card declined');
    assert.equal(checkout.node('deposit-submit').disabled, false);
    assert.equal(checkout.redirects.length, 0);
});

test('an incomplete hold is not displayed as successfully authorised', async () => {
    const checkout = page({ paymentIntent: { status: 'requires_action' } });
    checkout.payment.events.change({ complete: true });
    await checkout.submit();
    assert.equal(checkout.redirects.length, 0);
    assert.match(checkout.node('deposit-error').textContent, /not complete/);
});
