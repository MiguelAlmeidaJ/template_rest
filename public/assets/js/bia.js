(() => {
  'use strict';
  const photos = {
    hero: 'photo-1555396273-367ea4eb4db5',
    buffet: 'photo-1547592180-85f173990554',
    food: 'photo-1504674900247-0877df9cc836',
    interior: 'photo-1517248135467-4c7edcad34c4',
    table: 'photo-1414235077428-338989a2e8c0',
    salad: 'photo-1512621776951-a57141f2eefd',
    meat: 'photo-1544025162-d76694265947',
    pasta: 'photo-1473093226795-af9932fe5856',
    dessert: 'photo-1488477181946-6428a0291777',
    gallery1: 'photo-1552566626-52f8b828add9',
    gallery2: 'photo-1508424757105-b6d5ad9329d0',
    gallery3: 'photo-1559339352-11d035aa65de',
    gallery4: 'photo-1414235077428-338989a2e8c0',
    gallery5: 'photo-1517248135467-4c7edcad34c4',
    gallery6: 'photo-1547592180-85f173990554',
  };
  document.querySelectorAll('img[data-photo]').forEach((img) => {
    img.addEventListener(
      'error',
      () => {
        if (img.dataset.fallbackDone) return;
        img.dataset.fallbackDone = '1';
        const id = photos[img.dataset.photo] || photos.food;
        img.src = 'https://images.unsplash.com/' + id + '?auto=format&fit=crop&w=1300&q=82';
      },
      { once: true },
    );
  });
  const toggle = document.querySelector('#menu-toggle');
  const nav = document.querySelector('#main-nav');
  if (toggle && nav) {
    toggle.addEventListener('click', () => {
      const open = toggle.getAttribute('aria-expanded') === 'true';
      toggle.setAttribute('aria-expanded', String(!open));
      nav.classList.toggle('is-open', !open);
    });
    nav.querySelectorAll('a').forEach((a) =>
      a.addEventListener('click', () => {
        toggle.setAttribute('aria-expanded', 'false');
        nav.classList.remove('is-open');
      }),
    );
  }
})();
