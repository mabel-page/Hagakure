// Lightbox
(function(){
  const images = Array.from(document.querySelectorAll('.gallery img'));
  if (!images.length) return;
  const backdrop = document.createElement('div');
  backdrop.className = 'lb-backdrop';
  const img = document.createElement('img');
  img.className = 'lb-img';
  const btnClose = document.createElement('button');
  btnClose.className = 'lb-close'; btnClose.textContent = 'Fermer';
  const btnPrev = document.createElement('button');
  btnPrev.className = 'lb-prev'; btnPrev.textContent = '←';
  const btnNext = document.createElement('button');
  btnNext.className = 'lb-next'; btnNext.textContent = '→';
  backdrop.append(img, btnClose, btnPrev, btnNext);
  document.body.appendChild(backdrop);
  let index = 0;
  const open = (i) => { index = i; img.src = images[index].src; backdrop.classList.add('open'); document.body.style.overflow='hidden'; };
  const close = () => { backdrop.classList.remove('open'); document.body.style.overflow=''; };
  const prev = () => open((index - 1 + images.length) % images.length);
  const next = () => open((index + 1) % images.length);
  images.forEach((im, i) => im.addEventListener('click', () => open(i)));
  btnClose.addEventListener('click', close);
  btnPrev.addEventListener('click', prev);
  btnNext.addEventListener('click', next);
  document.addEventListener('keydown', (e) => {
    if (!backdrop.classList.contains('open')) return;
    if (e.key === 'Escape') close();
    if (e.key === 'ArrowLeft') prev();
    if (e.key === 'ArrowRight') next();
  });
})();