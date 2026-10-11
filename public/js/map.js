// Интерактивная карта: подсветка зон, подсказка при наведении, закреплённая карточка по нажатию.
// На телефоне наведения нет: первое касание показывает карточку со ссылкой «Подробнее».
document.querySelectorAll('[data-map]').forEach((map) => {
  const canvas = map.querySelector('.imap-canvas');
  const tip = map.querySelector('.imap-tip');
  const title = tip.querySelector('[data-tip-title]');
  const text = tip.querySelector('[data-tip-text]');
  const link = tip.querySelector('[data-tip-link]');
  const targets = map.querySelectorAll('.imap-zone, .imap-pin');
  let pinned = null;

  // Свёрнутая карта на главной: кнопка «Развернуть» открывает её целиком,
  // «Свернуть» остаётся на виду при прокрутке, пока карта раскрыта.
  const expandButton = map.querySelector('[data-map-expand]');
  const collapseButton = map.querySelector('[data-map-collapse]');
  const setCollapsed = (collapsed) => {
    map.classList.toggle('is-collapsed', collapsed);
    expandButton.hidden = !collapsed;
    collapseButton.hidden = collapsed;
    expandButton.setAttribute('aria-expanded', String(!collapsed));
    if (collapsed) {
      hide();
      map.scrollIntoView({ block: 'nearest' });
      expandButton.focus({ preventScroll: true });
    } else {
      collapseButton.focus({ preventScroll: true });
    }
  };
  if (expandButton && collapseButton) {
    expandButton.addEventListener('click', (event) => { event.stopPropagation(); setCollapsed(false); });
    collapseButton.addEventListener('click', (event) => { event.stopPropagation(); setCollapsed(true); });
  }

  // На узком экране карта шире окна: открываем её по центру, дальше двигают пальцем
  const scroller = map.querySelector('.imap-scroll');
  scroller.scrollLeft = (scroller.scrollWidth - scroller.clientWidth) / 2;

  const show = (target, point) => {
    title.textContent = target.dataset.title;
    text.textContent = target.dataset.text || '';
    text.hidden = !target.dataset.text;
    link.hidden = !target.dataset.href;
    if (target.dataset.href) link.href = target.dataset.href;
    tip.hidden = false;

    // Ставим подсказку рядом с точкой и не даём ей выйти за края карты
    const box = canvas.getBoundingClientRect();
    const left = point.x - box.left - tip.offsetWidth / 2;
    let top = point.y - box.top + 18;
    if (top + tip.offsetHeight > box.height - 8) top = point.y - box.top - tip.offsetHeight - 14;
    tip.style.left = `${Math.min(Math.max(left, 8), box.width - tip.offsetWidth - 8)}px`;
    tip.style.top = `${Math.max(top, 8)}px`;
  };

  const hide = () => {
    tip.hidden = true;
    tip.classList.remove('is-pinned');
    targets.forEach((target) => target.classList.remove('is-active'));
    pinned = null;
  };

  const pin = (target, point) => {
    hide();
    pinned = target;
    target.classList.add('is-active');
    tip.classList.add('is-pinned');
    show(target, point);
  };

  const centerOf = (target) => {
    const box = target.getBoundingClientRect();
    return { x: box.left + box.width / 2, y: box.top + box.height / 2 };
  };

  targets.forEach((target) => {
    target.addEventListener('pointermove', (event) => {
      if (event.pointerType === 'mouse' && !pinned) show(target, { x: event.clientX, y: event.clientY });
    });
    target.addEventListener('pointerleave', (event) => {
      if (event.pointerType === 'mouse' && !pinned) tip.hidden = true;
    });
    target.addEventListener('click', (event) => {
      event.stopPropagation();
      if (pinned === target) return hide();
      pin(target, event.clientX || event.clientY ? { x: event.clientX, y: event.clientY } : centerOf(target));
    });
    target.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        pinned === target ? hide() : pin(target, centerOf(target));
      }
    });
  });

  tip.addEventListener('click', (event) => event.stopPropagation());
  document.addEventListener('click', hide);
  document.addEventListener('keydown', (event) => event.key === 'Escape' && hide());
});
