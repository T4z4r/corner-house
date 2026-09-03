
/* =========================================================
   CONFIG - injected from admin settings via window.__SITE__
   ========================================================= */
const CONFIG = Object.assign({
  enquiryEmail: "bookings@example.com",      // where enquiries go if no endpoint is set
  bookingEndpoint: "",                        // e.g. "/booking/enquiry" - POSTs JSON
  availabilityUrl: "",                        // e.g. "/availability.json" - returns [{start:"YYYY-MM-DD", end:"YYYY-MM-DD"}] (end exclusive)
  nightlyRate: 950,                           // per night, whole house - placeholder
  securityDeposit: 950,                       // refundable security deposit (direct bookings)
  cleaningFee: 250,                           // - placeholder
  minNights: 2,
  monthsAhead: 18,
  sampleBlocked: []                           // only used when availabilityUrl is empty
}, window.__SITE__ || {});

/* ---------- Routing ---------- */
const pages = [...document.querySelectorAll(".page")];
const navLinks = [...document.querySelectorAll("[data-nav]")];
const SUBSECTIONS = {platforms:"book", availability:"book", rules:"book", "house-rules":"book", bedrooms:"rooms", inside:"rooms", outside:"rooms"};
function route(){
  const hash = (location.hash || "#home").slice(1);
  const name = pages.some(p=>p.dataset.page===hash) ? hash : (SUBSECTIONS[hash] || "home");
  pages.forEach(p=>p.classList.toggle("active", p.dataset.page===name));
  navLinks.forEach(a=>a.classList.toggle("active", a.dataset.nav===name && !a.classList.contains("btn")));
  document.getElementById("nav").classList.remove("open");
  document.querySelector(".nav-toggle").setAttribute("aria-expanded","false");
  const target = SUBSECTIONS[hash] ? document.getElementById(hash) : null;
  if(target) target.scrollIntoView();
  else window.scrollTo({top:0, behavior:"instant"});
}
window.addEventListener("hashchange", route);
route();
document.querySelector(".nav-toggle").addEventListener("click", e=>{
  const nav = document.getElementById("nav");
  const open = nav.classList.toggle("open");
  e.currentTarget.setAttribute("aria-expanded", open);
});
document.getElementById("year").textContent = new Date().getFullYear();
document.querySelectorAll(".rev-date").forEach(el=>el.textContent = new Date().toLocaleDateString("en-GB",{day:"numeric",month:"long",year:"numeric"}));
document.getElementById("footer-email").href = "mailto:"+CONFIG.enquiryEmail;
document.getElementById("footer-email").textContent = CONFIG.enquiryEmail;

/* ---------- Places tabs ---------- */
document.querySelectorAll(".tab").forEach(btn=>{
  btn.addEventListener("click", ()=>{
    document.querySelectorAll(".tab").forEach(b=>b.setAttribute("aria-selected", b===btn));
    document.querySelectorAll(".panel").forEach(p=>p.classList.toggle("active", p.id==="panel-"+btn.dataset.tab));
  });
});

/* ---------- Availability calendar ---------- */
const fmtISO = d => d.toISOString().slice(0,10);
const parseISO = s => { const [y,m,dd]=s.split("-").map(Number); return new Date(Date.UTC(y,m-1,dd)); };
const addDays = (d,n) => new Date(d.getTime()+n*86400000);
const fmtLong = d => d.toLocaleDateString("en-GB",{weekday:"short", day:"numeric", month:"short", year:"numeric", timeZone:"UTC"});
const gbp = n => "£"+n.toLocaleString("en-GB");

const today = parseISO(new Date().toISOString().slice(0,10));
let blocked = new Set();          // ISO strings of nights that are booked
let view = new Date(Date.UTC(today.getUTCFullYear(), today.getUTCMonth(), 1));
let checkIn = null, checkOut = null;

function loadBlocked(ranges){
  blocked = new Set();
  ranges.forEach(r=>{
    for(let d=parseISO(r.start); d<parseISO(r.end); d=addDays(d,1)) blocked.add(fmtISO(d));
  });
}
async function loadAvailability(){
  if(!CONFIG.availabilityUrl){ loadBlocked(CONFIG.sampleBlocked); return; }
  try{
    const res = await fetch(CONFIG.availabilityUrl, {cache:"no-store"});
    loadBlocked(await res.json());
    document.getElementById("demo-notice").hidden = true;
  }catch(e){
    loadBlocked([]);
    document.getElementById("demo-notice").textContent = "Live availability could not be loaded. Send an enquiry and we will confirm dates by email.";
  }
  renderMonths();
}

function nightBlocked(d){ return blocked.has(fmtISO(d)); }
function rangeClear(a,b){ for(let d=a; d<b; d=addDays(d,1)) if(nightBlocked(d)) return false; return true; }

function renderMonth(first){
  const y=first.getUTCFullYear(), m=first.getUTCMonth();
  const wrap=document.createElement("div"); wrap.className="month";
  wrap.innerHTML = `<h3>${first.toLocaleDateString("en-GB",{month:"long",year:"numeric",timeZone:"UTC"})}</h3>
    <div class="dow">${["M","T","W","T","F","S","S"].map(d=>`<span>${d}</span>`).join("")}</div>`;
  const days=document.createElement("div"); days.className="days";
  const lead=(first.getUTCDay()+6)%7;
  for(let i=0;i<lead;i++){ const e=document.createElement("span"); e.className="day empty"; days.appendChild(e); }
  const last=new Date(Date.UTC(y,m+1,0)).getUTCDate();
  for(let dd=1; dd<=last; dd++){
    const d=new Date(Date.UTC(y,m,dd));
    const b=document.createElement("button"); b.type="button"; b.className="day"; b.textContent=dd;
    b.dataset.date=fmtISO(d);
    b.setAttribute("aria-label", fmtLong(d));
    if(d<today){ b.classList.add("past"); b.disabled=true; }
    if(fmtISO(d)===fmtISO(today)) b.classList.add("today");
    const isBlockedNight = nightBlocked(d);
    const prevBlocked = nightBlocked(addDays(d,-1));
    // A date is selectable as check-out even if that night is booked (guests leave that morning).
    if(isBlockedNight && prevBlocked){ b.classList.add("blocked"); b.disabled=true; }
    else if(isBlockedNight){ b.classList.add("blocked"); b.dataset.checkoutOnly="1"; }
    if(checkIn && fmtISO(d)===fmtISO(checkIn)) b.classList.add("start");
    if(checkOut && fmtISO(d)===fmtISO(checkOut)) b.classList.add("end");
    if(checkIn && checkOut && d>checkIn && d<checkOut) b.classList.add("in-range");
    b.addEventListener("click", ()=>pick(d, b.dataset.checkoutOnly==="1"));
    days.appendChild(b);
  }
  wrap.appendChild(days);
  return wrap;
}
function renderMonths(){
  const el=document.getElementById("months"); el.innerHTML="";
  el.appendChild(renderMonth(view));
  el.appendChild(renderMonth(new Date(Date.UTC(view.getUTCFullYear(), view.getUTCMonth()+1, 1))));
  const maxView = new Date(Date.UTC(today.getUTCFullYear(), today.getUTCMonth()+CONFIG.monthsAhead-1, 1));
  document.getElementById("prev-month").disabled = view <= new Date(Date.UTC(today.getUTCFullYear(), today.getUTCMonth(), 1));
  document.getElementById("next-month").disabled = view >= maxView;
}
document.getElementById("prev-month").addEventListener("click", ()=>{ view=new Date(Date.UTC(view.getUTCFullYear(), view.getUTCMonth()-1, 1)); renderMonths(); });
document.getElementById("next-month").addEventListener("click", ()=>{ view=new Date(Date.UTC(view.getUTCFullYear(), view.getUTCMonth()+1, 1)); renderMonths(); });
document.getElementById("min-nights-label").textContent = CONFIG.minNights;

function setError(msg){ const e=document.getElementById("q-err"); e.textContent=msg||""; e.hidden=!msg; }

function pick(d, checkoutOnly){
  setError("");
  if(!checkIn || (checkIn && checkOut)){
    if(checkoutOnly){ setError("That date is a change-over day. It can only be a check-out date."); return; }
    checkIn=d; checkOut=null;
  } else if(d<=checkIn){
    if(checkoutOnly){ setError("Pick a check-in date first."); return; }
    checkIn=d;
  } else {
    const nights=Math.round((d-checkIn)/86400000);
    if(nights<CONFIG.minNights){ setError(`Minimum stay is ${CONFIG.minNights} nights.`); return; }
    if(!rangeClear(checkIn,d)){ setError("Those dates include a night that is already booked. Please choose a shorter stay or different dates."); return; }
    checkOut=d;
  }
  renderMonths(); renderQuote();
}
function renderQuote(){
  document.getElementById("q-in").textContent = checkIn ? fmtLong(checkIn) : "Select a date";
  document.getElementById("q-out").textContent = checkOut ? fmtLong(checkOut) : (checkIn ? "Select a date" : "—");
  const lines=document.getElementById("q-lines");
  if(checkIn && checkOut){
    const n=Math.round((checkOut-checkIn)/86400000);
    document.getElementById("q-nights").textContent = `${n} night${n>1?"s":""} × ${gbp(CONFIG.nightlyRate)}`;
    document.getElementById("q-accom").textContent = gbp(n*CONFIG.nightlyRate);
    document.getElementById("q-clean").textContent = gbp(CONFIG.cleaningFee);
    document.getElementById("q-total").textContent = gbp(n*CONFIG.nightlyRate+CONFIG.cleaningFee);
    document.getElementById("q-dep").textContent = gbp(CONFIG.securityDeposit);
    lines.hidden=false;
  } else lines.hidden=true;
}

/* ---------- Enquiry form ---------- */
document.getElementById("enquiry").addEventListener("submit", async e=>{
  e.preventDefault();
  if(!checkIn || !checkOut){ setError("Choose your check-in and check-out dates on the calendar first."); return; }
  const f=new FormData(e.target);
  const payload = {
    checkIn: fmtISO(checkIn), checkOut: fmtISO(checkOut),
    nights: Math.round((checkOut-checkIn)/86400000),
    name:f.get("name"), email:f.get("email"), phone:f.get("phone"),
    guests:f.get("guests"), message:f.get("message"), drinksPackage: !!f.get("drinks"),
    acceptedTerms: !!f.get("agree"), acceptedAt: new Date().toISOString()
  };
  if(CONFIG.bookingEndpoint){
    try{
      const r=await fetch(CONFIG.bookingEndpoint,{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify(payload)});
      if(!r.ok) throw new Error();
      e.target.innerHTML = `<p><strong>Enquiry sent.</strong> We will reply to ${payload.email} within 24 hours to confirm availability and price.</p>`;
    }catch(err){ setError("The enquiry could not be sent. Please email us directly at "+CONFIG.enquiryEmail+"."); }
    return;
  }
  const body = [
    `Booking enquiry for Corner House, Braunston`, ``,
    `Check in:  ${fmtLong(checkIn)}`, `Check out: ${fmtLong(checkOut)} (${payload.nights} nights)`,
    `Guests: ${payload.guests}`, `Drinks package: ${payload.drinksPackage?"yes":"no"}`, ``,
    `Name: ${payload.name}`, `Email: ${payload.email}`, `Phone: ${payload.phone||"-"}`, ``,
    payload.message||""
  ].join("\n");
  location.href = `mailto:${CONFIG.enquiryEmail}?subject=${encodeURIComponent("Booking enquiry "+payload.checkIn+" to "+payload.checkOut)}&body=${encodeURIComponent(body)}`;
});

loadAvailability().then(renderMonths);
renderMonths();

/* ---------- Floating chat widget ---------- */
(function initChat(){
  const root = document.querySelector("[data-chat-widget]");
  if(!root || root.dataset.bound === "1") return;
  root.dataset.bound = "1";

  const toggle = root.querySelectorAll("[data-chat-toggle]");
  const panel = root.querySelector("[data-chat-panel]");
  const modeBtns = [...root.querySelectorAll("[data-chat-mode]")];
  const form = root.querySelector("[data-chat-form]");
  const messageForm = root.querySelector("[data-message-form]");
  const input = root.querySelector("[data-chat-input]");
  const body = root.querySelector("[data-chat-body]");
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || "";
  const storageKey = "cornerhouse.chatSession.website";
  let sessionId = null;
  try{ sessionId = localStorage.getItem(storageKey); }catch(e){ sessionId = null; }
  if(!sessionId){
    sessionId = (window.crypto && crypto.randomUUID) ? crypto.randomUUID() : String(Date.now());
    try{ localStorage.setItem(storageKey, sessionId); }catch(e){/* storage unavailable */}
  }

  const esc = v => String(v).replace(/</g, "&lt;");
  const addMsg = (text, cls) => { body.insertAdjacentHTML("beforeend", `<div class="chat-msg ${cls}">${esc(text)}</div>`); body.scrollTop = body.scrollHeight; };

  const setOpen = open => {
    if(!panel) return;
    panel.hidden = !open;
    root.classList.toggle("is-open", open);
    toggle.forEach(b => b.setAttribute("aria-expanded", open ? "true" : "false"));
    if(open) input?.focus();
  };
  toggle.forEach(b => b.addEventListener("click", () => setOpen(panel.hidden)));

  const setMode = mode => {
    const isAsk = mode === "ask";
    [form, messageForm].forEach(f => { if(f) f.hidden = !(f.dataset.modePanel === mode); });
    modeBtns.forEach(btn => btn.setAttribute("aria-selected", btn.dataset.chatMode === mode ? "true" : "false"));
  };
  modeBtns.forEach(btn => btn.addEventListener("click", () => setMode(btn.dataset.chatMode)));

  form?.addEventListener("submit", async e => {
    e.preventDefault();
    const message = input?.value.trim();
    if(!message) return;
    addMsg(message, "guest");
    input.value = "";
    addMsg("Thinking…", "ai pending");
    try{
      const r = await fetch("/api/v1/chat", {
        method:"POST",
        headers:{"Content-Type":"application/json",Accept:"application/json","X-CSRF-TOKEN":csrf},
        body: JSON.stringify({message, session_id: sessionId, source:"website"})
      });
      const data = await r.json();
      root.querySelector(".chat-msg.pending")?.remove();
      addMsg(data.reply || "Sorry, I could not answer that.", "ai");
    }catch(err){
      root.querySelector(".chat-msg.pending")?.remove();
      addMsg("The assistant is unavailable right now.", "ai");
    }
  });

  messageForm?.addEventListener("submit", async e => {
    e.preventDefault();
    const payload = {
      name: root.querySelector("[data-msg-name]")?.value.trim(),
      email: root.querySelector("[data-msg-email]")?.value.trim(),
      message: root.querySelector("[data-msg-body]")?.value.trim(),
      session_id: sessionId
    };
    addMsg(payload.message, "guest");
    try{
      const r = await fetch("/api/v1/messages", {
        method:"POST",
        headers:{"Content-Type":"application/json",Accept:"application/json","X-CSRF-TOKEN":csrf},
        body: JSON.stringify(payload)
      });
      const data = await r.json();
      addMsg(data.reply || "Thanks — we have received your message and will reply by email.", "ai");
      const bodyField = root.querySelector("[data-msg-body]");
      if(bodyField) bodyField.value = "";
    }catch(err){ addMsg("We could not send that message just now.", "ai"); }
  });
})();
