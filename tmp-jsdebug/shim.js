// Minimal DOM shim to detect at which point the module throws
const SCRIPT = process.argv[2];
const fs = require('fs');
const src = fs.readFileSync(SCRIPT, 'utf8');

// Primitive document shim
const listeners = {};
global.window = global;
global.document = {
    addEventListener: (ev, fn) => { listeners[ev] = fn; },
    querySelector: () => null,
    querySelectorAll: () => [],
    getElementById: () => null,
    createElement: () => ({ className:'', classList:{add(){},remove(){},toggle(){}}, appendChild(){}, setAttribute(){} }),
    body: { classList:{add(){},remove(){},toggle(){}}, appendChild(){}, querySelector(){return null}, querySelectorAll(){return []} },
};
global.localStorage = { getItem:()=>null, setItem(){} };
global.location = { href:'http://localhost/' };
global.URL = function(){};
global.fetch = async () => ({ ok:true, json:async()=>({}) });
global.setInterval = () => 0;
global.clearInterval = () => {};
global.ResizeObserver = class { constructor(){} observe(){} disconnect(){} };
global.AudioContext = class { constructor(){} };
global.navigator = {};

global.self = global;

console.log('shim ready, executing...');
try {
    const fn = new Function('require', 'module', 'exports', 'window', 'document', 'localStorage', 'location', 'fetch', 'setInterval', 'ResizeObserver', 'AudioContext', src);
    fn.call({}, require, {exports:{}}, {}, global, global.document, global.localStorage, global.location, global.fetch, global.setInterval, global.ResizeObserver, global.AudioContext);
    console.log('EXECUTION COMPLETED - no fatal error');
} catch (e) {
    console.error('FATAL ERROR DURING MODULE EVAL:');
    console.error(e && e.stack ? e.stack : e);
}
