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
    map.querySelectorAll('[data-zone-show]').forEach((item) => item.classList.remove('is-active'));
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

  // Легенда подсвеченных зон: показывает место на карте и подсказку
  map.querySelectorAll('[data-zone-show]').forEach((button) => {
    button.addEventListener('click', (event) => {
      event.stopPropagation();
      const zone = map.querySelector(`[data-zone-id="${button.dataset.zoneShow}"]`);
      if (!zone) return;
      if (map.classList.contains('is-collapsed') && expandButton) setCollapsed(false);
      const zoneBox = zone.getBoundingClientRect();
      const canvasBox = canvas.getBoundingClientRect();
      scroller.scrollTo({ left: zoneBox.left - canvasBox.left + zoneBox.width / 2 - scroller.clientWidth / 2 });
      map.querySelectorAll('[data-zone-show]').forEach((item) => item.classList.toggle('is-active', item === button));
      requestAnimationFrame(() => {
        pin(zone, centerOf(zone));
        canvas.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
      });
    });
  });

  tip.addEventListener('click', (event) => event.stopPropagation());
  document.addEventListener('click', hide);
  document.addEventListener('keydown', (event) => event.key === 'Escape' && hide());
});

// «Где я?»: координаты гостя переводятся в точку на рисунке по привязке к местности
// (та же формула, что в app/Support/MapProjection.php). Координаты никуда не отправляются.
document.querySelectorAll('[data-map][data-projection]').forEach((map) => {
  const projection = JSON.parse(map.dataset.projection);
  const button = map.querySelector('[data-locate]');
  const label = map.querySelector('[data-locate-label]');
  const status = map.querySelector('[data-geo-status]');
  const me = map.querySelector('[data-me]');
  const accuracyRing = map.querySelector('[data-me-accuracy]');
  const scroller = map.querySelector('.imap-scroll');
  let watchId = null;
  let centered = false;
  let hasPosition = false;

  const toLocal = (lat, lng) => [
    (lng - projection.lng0) * 111.32 * Math.cos((projection.lat0 * Math.PI) / 180),
    (lat - projection.lat0) * 110.574,
  ];
  const project = (lat, lng) => {
    const [e, n] = toLocal(lat, lng);
    const h = projection.h;
    const w = h[6] * e + h[7] * n + 1;
    return [(h[0] * e + h[1] * n + h[2]) / w, (h[3] * e + h[4] * n + h[5]) / w];
  };
  const kmFromCenter = (lat, lng) => {
    const [e, n] = toLocal(lat, lng);
    return Math.hypot(e, n);
  };

  const say = (text, link) => {
    status.hidden = false;
    status.textContent = text;
    if (link) {
      const a = document.createElement('a');
      a.href = link.href;
      a.textContent = link.text;
      a.className = 'more';
      status.append(' ', a);
    }
  };

  const stop = () => {
    if (watchId !== null) navigator.geolocation.clearWatch(watchId);
    watchId = null;
    centered = false;
    hasPosition = false;
    me.hidden = true;
    status.hidden = true;
    label.textContent = 'Где я?';
    button.classList.remove('is-on');
  };

  const onPosition = ({ coords }) => {
    hasPosition = true;
    const [x, y] = project(coords.latitude, coords.longitude);
    const inside = x >= 0 && x <= projection.width && y >= 0 && y <= projection.height;

    if (!inside) {
      me.hidden = true;
      const km = kmFromCenter(coords.latitude, coords.longitude);
      say(km < 1 ? 'Вы рядом, но за краем карты.' : `Вы в ${km < 10 ? km.toFixed(1).replace('.', ',') : Math.round(km)} км от Шаманки.`,
        { href: '/stati/transport/', text: 'Как добраться' });
      return;
    }

    // Круг точности: сколько пикселей рисунка занимают coords.accuracy метров к северу
    const [, yNorth] = project(coords.latitude + coords.accuracy / 110574, coords.longitude);
    const radius = Math.min(Math.abs(y - yNorth), projection.width / 4);
    me.style.left = `${(x / projection.width) * 100}%`;
    me.style.top = `${(y / projection.height) * 100}%`;
    accuracyRing.style.width = accuracyRing.style.height = `${(radius * 2 / projection.width) * 100}cqw`;
    me.hidden = false;
    say(`Вы здесь${coords.accuracy ? ` — точность около ${Math.round(coords.accuracy)} м` : ''}.`);

    if (!centered) {
      centered = true;
      const canvas = map.querySelector('.imap-canvas');
      scroller.scrollTo({ left: (x / projection.width) * canvas.offsetWidth - scroller.clientWidth / 2, behavior: 'smooth' });
      me.scrollIntoView({ block: 'center', behavior: 'smooth' });
    }
  };

  const onError = (error) => {
    // Разовая ошибка при слежении (пропал GPS на минуту) — не сбрасываем найденную точку
    if (hasPosition && error.code !== error.PERMISSION_DENIED) return;
    stop();
    say(error.code === error.PERMISSION_DENIED
      ? 'Нет доступа к геопозиции. Разрешите его для сайта в настройках браузера.'
      : 'Не получилось определить, где вы. Попробуйте ещё раз на улице или с включённым GPS.');
  };

  button.addEventListener('click', (event) => {
    event.stopPropagation();
    if (watchId !== null) return stop();
    if (!('geolocation' in navigator)) return say('Этот браузер не умеет определять местоположение.');
    map.querySelector('[data-map-expand]:not([hidden])')?.click();
    label.textContent = 'Скрыть меня';
    button.classList.add('is-on');
    say('Определяем, где вы…');
    watchId = navigator.geolocation.watchPosition(onPosition, onError, { enableHighAccuracy: true, maximumAge: 10000, timeout: 20000 });
  });
});
