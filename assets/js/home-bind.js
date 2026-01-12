// assets/js/home-bind.js
document.addEventListener('DOMContentLoaded', async () => {
  const news = document.getElementById('news');
  if (!news) return;

  const esc = s => String(s ?? '');
  const imgTag = src => src
    ? `<div class="card__media"><img src="${esc(src)}" alt="" loading="lazy" decoding="async"></div>`
    : '';
  const linkTag = link => (link && link.href)
    ? `<p><a class="btn btn--slim" href="${esc(link.href)}" target="_blank" rel="noopener">${esc(link.label || 'Ouvrir')}</a></p>`
    : '';

  try {
    const r = await fetch('/api/home', { cache: 'no-cache' });
    if (!r.ok) return; // on garde le HTML statique
    const data = await r.json();

    // Construit les cartes dans l'ordre : Important -> Événements -> Infos
    const parts = [];

    (data.important || []).forEach(card => {
      parts.push(`
        <article class="card">
          ${card.badge ? `<div class="badge">${esc(card.badge)}</div>` : ''}
          <h3>${esc(card.title)}</h3>
          <p>${esc(card.text)}</p>
          ${imgTag(card.image)}
          ${linkTag(card.link)}
        </article>
      `);
    });

    (data.events || []).forEach(ev => {
      parts.push(`
        <article class="card">
          <div class="badge">Événements à venir</div>
          <h3>${esc(ev.title)}</h3>
          <p class="small">${esc(ev.date)}${ev.location ? ' — ' + esc(ev.location) : ''}</p>
          <p>${esc(ev.text)}</p>
          ${linkTag(ev.link)}
        </article>
      `);
    });

    (data.infos || []).forEach(card => {
      parts.push(`
        <article class="card">
          ${card.badge ? `<div class="badge">${esc(card.badge)}</div>` : '<div class="badge">Infos</div>'}
          <h3>${esc(card.title)}</h3>
          <p>${esc(card.text)}</p>
          ${imgTag(card.image)}
          ${linkTag(card.link)}
        </article>
      `);
    });

    if (parts.length) news.innerHTML = parts.join('');
  } catch (e) {
    // silence: on garde le HTML statique
  }
});