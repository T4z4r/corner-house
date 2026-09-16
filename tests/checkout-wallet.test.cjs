const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

function checkout(result = { paymentIntent: { id: 'pi_wallet', status: 'succeeded' } }) {
    const nodes = new Map();
    const node = id => {
        if (!nodes.has(id)) nodes.set(id, { hidden: false, disabled: false, checked: true, textContent: 'Pay', dataset: {}, style: {}, events: {}, addEventListener(type, fn) { this.events[type] = fn; }, querySelectorAll() { return []; } });
        return nodes.get(id);
    };
    const element = () => ({ events: {}, mount() {}, on(type, fn) { this.events[type] = fn; } });
    const card = element(), wallet = element(), calls = [], redirects = [], requests = [];
    const elements = { create: (type, options) => { const target = type === 'payment' ? card : wallet; target.options = options; return target; }, submit: async () => ({}) };
    const stripe = { elements: () => elements, confirmPayment: async options => { calls.push(options); return result; } };
    const document = { getElementById: node, createElement: () => ({}), addEventListener: (_, fn) => fn(), head: { appendChild: script => script.onload() } };
    let script = fs.readFileSync('resources/views/website/booking/checkout.blade.php', 'utf8').split('<script>')[1].split('</script>')[0];
    script = script.replace(/@json\([^\n]+\)/g, '"test-value"');
    vm.runInNewContext(script, { document, window: { Stripe: () => stripe, location: { assign: url => redirects.push(url) } }, fetch: async (url, data) => { requests.push(data); return { ok: true }; } });
    return { node, card, wallet, calls, redirects, requests };
}

test('wallets request broader browser support and explain unavailable methods', () => {
    const page = checkout();
    assert.equal(page.wallet.options.paymentMethods.applePay, 'always');
    assert.equal(page.wallet.options.paymentMethods.googlePay, 'always');
    page.wallet.events.ready({ availablePaymentMethods: { applePay: true } });
    assert.equal(page.node('express-checkout-status').hidden, true);
    page.wallet.events.ready({ availablePaymentMethods: undefined });
    assert.equal(page.node('express-checkout-status').hidden, false);
    assert.match(page.node('express-checkout-status').textContent, /unavailable/);
    assert.match(page.node('express-checkout-status').className, /\btext-danger\b/);
    page.wallet.events.availablepaymentmethodschange({ availablePaymentMethods: { googlePay: true } });
    assert.equal(page.node('express-checkout-status').hidden, true);
});

test('wallet loading failure explains how to continue paying', () => {
    const page = checkout();
    page.wallet.events.loaderror();
    assert.equal(page.node('express-checkout-status').hidden, false);
    assert.match(page.node('express-checkout-status').textContent, /pay by card/);
    assert.match(page.node('express-checkout-status').className, /\btext-danger\b/);
});

test('wallet confirms on site without requiring card form completion', async () => {
    const page = checkout();
    await page.wallet.events.confirm({ paymentFailed: () => assert.fail('Unexpected failure') });
    assert.equal(page.calls.length, 1);
    assert.equal(page.calls[0].redirect, 'if_required');
    assert.deepEqual(JSON.parse(page.requests[0].body), { payment_intent_id: 'pi_wallet' });
    assert.equal(page.redirects.length, 1);
});

test('wallet errors are shown and permit another attempt', async () => {
    const page = checkout({ error: { message: 'Card declined' } });
    let failures = 0;
    await page.wallet.events.confirm({ paymentFailed: () => failures++ });
    assert.equal(page.node('cardErrors').textContent, 'Card declined');
    assert.equal(failures, 1);
    assert.equal(page.redirects.length, 0);
    await page.wallet.events.confirm({ paymentFailed: () => failures++ });
    assert.equal(page.calls.length, 2);
});

test('wallet respects payment authorisation checkbox', async () => {
    const page = checkout();
    page.node('termsCheck').checked = false;
    let failures = 0;
    await page.wallet.events.confirm({ paymentFailed: () => failures++ });
    assert.equal(failures, 1);
    assert.equal(page.calls.length, 0);
    assert.equal(page.node('cardErrors').hidden, false);
});
