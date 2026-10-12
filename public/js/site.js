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
    const { phones } = await response.json();
    // Каждый номер — отдельная кнопка-ссылка tel:, с подписью, чей он
    const list = document.createElement('div');
    list.className = 'phone-list';
    phones.forEach(({ number, person, tel }) => {
      const link = document.createElement('a');
      link.className = button.className + ' phone-link';
      link.href = tel;
      const digits = document.createElement('b');
      digits.textContent = number;
      link.append(digits);
      if (person) {
        const who = document.createElement('span');
        who.textContent = person;
        link.append(who);
      }
      list.append(link);
    });
    button.replaceWith(list);
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

// «Шаманка в фото»: нажатие на снимок открывает просмотр на весь экран.
// Листание — свайпом (scroll-snap), стрелками или клавишами; закрыть — Esc, крестик или нажатие мимо фото.
document.querySelectorAll('[data-lightbox]').forEach((gallery) => {
  const slides = [...gallery.querySelectorAll('.photo-slide')];
  if (!slides.length || typeof HTMLDialogElement !== 'function') return;

  const dialog = document.createElement('dialog');
  dialog.className = 'lightbox';
  dialog.setAttribute('closedby', 'any');
  dialog.tabIndex = -1;
  dialog.setAttribute('aria-label', 'Фото Шаманки');
  dialog.innerHTML = `
    <div class="lightbox-count" aria-live="polite"></div>
    <button type="button" class="lightbox-btn lightbox-close" aria-label="Закрыть">✕</button>
    <button type="button" class="lightbox-btn lightbox-prev" aria-label="Предыдущее фото">←</button>
    <button type="button" class="lightbox-btn lightbox-next" aria-label="Следующее фото">→</button>
    <div class="lightbox-track"></div>`;
  const track = dialog.querySelector('.lightbox-track');
  const count = dialog.querySelector('.lightbox-count');
  const prev = dialog.querySelector('.lightbox-prev');
  const next = dialog.querySelector('.lightbox-next');

  slides.forEach((slide) => {
    const img = slide.querySelector('img');
    const credit = slide.querySelector('figcaption');
    const figure = document.createElement('figure');
    figure.className = 'lightbox-slide';
    const big = document.createElement('img');
    big.src = img.src;
    big.alt = img.alt;
    big.loading = 'lazy';
    const caption = document.createElement('figcaption');
    caption.textContent = img.alt;
    if (credit) {
      caption.append(document.createElement('br'));
      [...credit.childNodes].forEach((node) => caption.append(node.cloneNode(true)));
    }
    figure.append(big, caption);
    track.append(figure);
  });
  document.body.append(dialog);

  const current = () => Math.round(track.scrollLeft / track.clientWidth);
  const go = (index, smooth = true) => track.scrollTo({ left: index * track.clientWidth, behavior: smooth ? 'smooth' : 'instant' });
  const update = () => {
    const index = current();
    count.textContent = `${index + 1} / ${slides.length}`;
    prev.disabled = index <= 0;
    next.disabled = index >= slides.length - 1;
  };

  slides.forEach((slide, index) => {
    slide.querySelector('img').addEventListener('click', () => {
      dialog.showModal();
      dialog.focus(); // фокус на самом окне: стрелки листают, крестик не подсвечен
      go(index, false);
      update();
    });
  });
  track.addEventListener('scroll', update, { passive: true });
  prev.addEventListener('click', () => go(current() - 1));
  next.addEventListener('click', () => go(current() + 1));
  dialog.querySelector('.lightbox-close').addEventListener('click', () => dialog.close());
  dialog.addEventListener('keydown', (event) => {
    if (event.key === 'ArrowLeft') go(current() - 1);
    if (event.key === 'ArrowRight') go(current() + 1);
  });
  // Нажатие мимо фото, подписи и кнопок закрывает просмотр (Safari пока не знает closedby)
  dialog.addEventListener('click', (event) => {
    if (!event.target.closest('img, figcaption, button')) dialog.close();
  });
});
