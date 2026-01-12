// assets/js/includes.js (fixed, full)
// Inject header/footer partials, initialize mobile menu, highlight active links, set footer year, then load main.js
async function injectPartials(){
  const zones = document.querySelectorAll('[data-include]');
  await Promise.all([...zones].map(async el => {
    const url = el.getAttribute('data-include');
    try{
      const res = await fetch(url, { cache: 'no-cache' });
      if(!res.ok) throw new Error('HTTP '+res.status);
      const html = await res.text();
      const box = document.createElement('div');
      box.innerHTML = html.trim();
      el.replaceWith(...box.childNodes);
    }catch(err){
      console.error('Include failed:', url, err);
      el.outerHTML = '<!-- include fail: '+url+' -->';
    }
  }));
}

// Delegated handlers so it works even after injection
function initNav(){
  document.addEventListener('click', (e)=>{
    const toggle   = e.target.closest('.nav__toggle');
    const expander = e.target.closest('.nav__expander');

    if(toggle){
      const links = document.getElementById('navLinks');
      if(!links) return;
      const open = !links.classList.contains('open');
      links.classList.toggle('open', open);
      toggle.setAttribute('aria-expanded', String(open));
      return;
    }

    if(expander){
      e.preventDefault();
      const li = expander.closest('.nav__item');
      if(!li) return;
      const open = !li.classList.contains('open');
      li.classList.toggle('open', open);
      expander.setAttribute('aria-expanded', String(open));
      return;
    }

    // Close if clicking outside nav
    const nav = document.querySelector('header .nav');
    if(nav && !nav.contains(e.target)){
      document.getElementById('navLinks')?.classList.remove('open');
      document.querySelectorAll('.nav__item.open').forEach(li=>li.classList.remove('open'));
      document.querySelector('.nav__toggle')?.setAttribute('aria-expanded','false');
    }
  });

  // Close on breakpoint change
  let last = window.innerWidth;
  window.addEventListener('resize', ()=>{
    const w = window.innerWidth;
    if((last <= 900 && w > 900) || (last > 900 && w <= 900)){
      document.getElementById('navLinks')?.classList.remove('open');
      document.querySelectorAll('.nav__item.open').forEach(li=>li.classList.remove('open'));
      document.querySelector('.nav__toggle')?.setAttribute('aria-expanded','false');
    }
    last = w;
  });

  // ESC to close
  document.addEventListener('keydown', (e)=>{
    if(e.key === 'Escape'){
      document.getElementById('navLinks')?.classList.remove('open');
      document.querySelectorAll('.nav__item.open').forEach(li=>li.classList.remove('open'));
      document.querySelector('.nav__toggle')?.setAttribute('aria-expanded','false');
    }
  });
}

function highlightActive(){
  const file = (location.pathname.split('/').pop() || 'accueil.html').toLowerCase();
  document.querySelectorAll('header .nav__links > .nav__item > a.nav__top').forEach(a=>{
    const href = (a.getAttribute('href') || '').toLowerCase();
    if(href === file){
      a.classList.add('is-active');
      a.closest('.nav__item')?.classList.add('active');
    }
  });
  if(location.hash){
    const sub = document.querySelector('header .nav__submenu a[href$="'+location.hash+'"]');
    if(sub){
      sub.classList.add('is-active');
      sub.closest('.nav__item')?.classList.add('active');
    }
  }
}

function setFooterYear(){
  const y = document.getElementById('year-footer');
  if(y) y.textContent = new Date().getFullYear();
}

function loadMainJs(){
  const src = 'assets/js/main.js';
  if(document.querySelector('script[src="'+src+'"]')) return;
  const s = document.createElement('script');
  s.src = src;
  s.async = true;
  document.body.appendChild(s);
}

document.addEventListener('DOMContentLoaded', async ()=>{
  await injectPartials();
  initNav();
  highlightActive();
  setFooterYear();
  loadMainJs();
});