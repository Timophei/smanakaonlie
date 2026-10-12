// «Показать телефон»: нажатие уходит на сервер (там оно считается), в ответ приходит номер.
document.addEventListener('click', async (event) => {
  const button = event.target.closest('[data-phone-url]');
  if (!button || button.dataset.done) return;
  event.preventDefault();
  button.disabled = true;
  try {
    const response = await fetch(button.dataset.phoneUrl, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': button.dataset.token, Accept: 'application/json' },
    });
    if (!response.ok) throw new Error(response.status);
    const { phone } = await response.json();
    const link = document.createElement('a');
    link.className = button.className;
    link.href = 'tel:' + phone.replace(/[^\d+]/g, '');
    link.textContent = phone;
    button.replaceWith(link);
  } catch (error) {
    button.disabled = false;
    button.querySelector('[data-label]').textContent = 'Не получилось, попробуйте ещё раз';
  }
});

// Мобильное меню: кнопка-бургер открывает и закрывает список разделов.
document.querySelectorAll('[data-nav-toggle]').forEach((toggle) => {
  const header = toggle.closest('.site-header');
  const setOpen = (open) => {
    header.classList.toggle('is-open', open);
    toggle.setAttribute('aria-expanded', String(open));
    toggle.setAttribute('aria-label', open ? 'Закрыть меню' : 'Открыть меню');
  };
  toggle.addEventListener('click', () => setOpen(!header.classList.contains('is-open')));
  header.querySelectorAll('.nav a').forEach((link) => link.addEventListener('click', () => setOpen(false)));
  document.addEventListener('keydown', (event) => event.key === 'Escape' && setOpen(false));
  document.addEventListener('click', (event) => !header.contains(event.target) && setOpen(false));
});

// Карусель на телефоне: стрелки листают по одной карточке, у краёв стрелка прячется.
document.querySelectorAll('[data-carousel]').forEach((carousel) => {
  const track = carousel.querySelector('[data-carousel-track]');
  const prev = carousel.querySelector('[data-carousel-prev]');
  const next = carousel.querySelector('[data-carousel-next]');
  const step = () => (track.firstElementChild?.getBoundingClientRect().width ?? 300) + 12;
  const update = () => {
    prev.disabled = track.scrollLeft <= 4;
    next.disabled = track.scrollLeft + track.clientWidth >= track.scrollWidth - 4;
  };
  prev.addEventListener('click', () => track.scrollBy({ left: -step(), behavior: 'smooth' }));
  next.addEventListener('click', () => track.scrollBy({ left: step(), behavior: 'smooth' }));
  track.addEventListener('scroll', update, { passive: true });
  window.addEventListener('resize', update);
  update();
});
