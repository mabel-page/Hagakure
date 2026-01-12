(() => {
  const CHAT_URL = "./chat-proxy.php";

  const css = `
  .hkc-chat-fab{
    position:fixed; right:18px; bottom:18px; z-index:9999;
    display:flex; align-items:center; gap:10px;
    background:#2563eb; color:#fff; border:none; border-radius:999px;
    padding:12px 14px; box-shadow:0 10px 20px rgba(0,0,0,.18);
    cursor:pointer; font:600 14px/1 system-ui,Segoe UI,Roboto,Arial;
  }
  .hkc-chat-fab .dot{width:10px;height:10px;border-radius:50%;background:#22c55e;box-shadow:0 0 0 3px rgba(34,197,94,.2)}
  .hkc-chat-fab:hover{filter:brightness(1.05)}
  .hkc-chat-panel{
    position:fixed; right:18px; bottom:78px; z-index:9999; width:360px; height:520px;
    background:#fff; border:1px solid #e6e8eb; border-radius:16px; overflow:hidden;
    box-shadow:0 24px 60px rgba(0,0,0,.25); transform:translateY(10px); opacity:0; pointer-events:none;
    transition:opacity .18s ease, transform .18s ease;
  }
  .hkc-chat-panel.open{opacity:1; transform:none; pointer-events:auto}
  .hkc-chat-head{
    display:flex; align-items:center; justify-content:space-between; padding:8px 10px;
    background:#1f4fd8; color:#fff; font-weight:600;
  }
  .hkc-chat-close{
    border:none;background:transparent;color:#fff;font-size:20px;cursor:pointer;padding:6px 8px;
  }
  .hkc-chat-iframe{width:100%; height:calc(100% - 42px); border:0; display:block; background:#fff}
  .hkc-chat-fallback{display:flex;align-items:center;justify-content:center;height:calc(100% - 42px);padding:16px}
  .hkc-chat-fallback .box{max-width:92%; text-align:center; color:#374151}
  .hkc-chat-fallback .btn{margin-top:10px; padding:10px 14px; border-radius:10px; border:1px solid #e5e7eb; background:#2563eb; color:#fff; font-weight:600; cursor:pointer}
  @media (max-width: 560px){
    .hkc-chat-panel{right:10px; left:10px; bottom:10px; top:10px; width:auto; height:auto; border-radius:18px}
  }`;
  const style = document.createElement('style');
  style.textContent = css; document.head.appendChild(style);

  const fab = document.createElement('button');
  fab.className = 'hkc-chat-fab';
  fab.setAttribute('aria-expanded','false');
  fab.innerHTML = `<span class="dot" aria-hidden="true"></span>
                   <span>Je suis l’assistant IA d’Hagakure,<br>comment puis-je vous aider&nbsp;?</span>`;
  document.body.appendChild(fab);

  const panel = document.createElement('div');
  panel.className = 'hkc-chat-panel';
  panel.innerHTML = `
    <div class="hkc-chat-head">
      <span>Assistant IA • Hagakure KC</span>
      <button class="hkc-chat-close" aria-label="Fermer">✕</button>
    </div>
    <iframe class="hkc-chat-iframe" src="${CHAT_URL}" referrerpolicy="no-referrer" loading="lazy"></iframe>`;
  document.body.appendChild(panel);

  const closeBtn = panel.querySelector('.hkc-chat-close');
  const iframe   = panel.querySelector('iframe');

  function open() {
    panel.classList.add('open');
    fab.setAttribute('aria-expanded','true');
    setTimeout(()=>iframe?.focus(), 150);
    sessionStorage.setItem('hkcChatOpen','1');
  }
  function close() {
    panel.classList.remove('open');
    fab.setAttribute('aria-expanded','false');
    sessionStorage.removeItem('hkcChatOpen');
  }

  fab.addEventListener('click', () => {
    panel.classList.contains('open') ? close() : open();
  });
  closeBtn.addEventListener('click', close);
  document.addEventListener('keydown', (e)=>{ if(e.key==='Escape' && panel.classList.contains('open')) close(); });

  if (sessionStorage.getItem('hkcChatOpen') === '1') open();

  // ---------- NEW: Fallback si l'iframe est bloquée ----------
  // Si X-Frame-Options bloque, on n'a pas d'event 'error'. On met une
  // sécurité: si au bout de 1,2s on ne capte pas 'load', on propose un bouton.
  let loaded = false;
  iframe.addEventListener('load', ()=>{ loaded = true; }, {once:true});
  setTimeout(()=>{
    if (loaded) return;
    // Remplace l'iframe par un fallback propre
    const fb = document.createElement('div');
    fb.className = 'hkc-chat-fallback';
    fb.innerHTML = `
      <div class="box">
        <p>Le chat ne peut pas s’ouvrir ici.</p>
        <p><small>(Le fournisseur bloque l’intégration en iframe)</small></p>
        <button class="btn" type="button">Ouvrir le chat dans un nouvel onglet</button>
      </div>`;
    const btn = fb.querySelector('.btn');
    btn.addEventListener('click', ()=> window.open(CHAT_URL, '_blank', 'noopener'));
    iframe.replaceWith(fb);
  }, 1200);
})();