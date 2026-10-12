// Редактор зон рисованной карты в админке (Alpine-компонент).
// Зоны рисуются в SVG поверх картинки в координатах исходного рисунка (width × height).
// Без выбранной зоны клик по карте выбирает зону; с выбранной — ставит новую точку; точки перетаскиваются, двойной клик удаляет точку;
// маленькие кружки посередине сторон добавляют точку между соседними.
window.mapZonesEditor = function (config) {
  const parse = (points) =>
    String(points || '')
      .trim()
      .split(/\s+/)
      .map((pair) => pair.split(',').map(Number))
      .filter((pair) => pair.length === 2 && pair.every(Number.isFinite));

  const serialize = (zone) => ({
    title: zone.title,
    text: zone.text || null,
    href: zone.href || null,
    shape: zone.shape,
    points: zone.points.map((point) => point.join(',')).join(' '),
  });

  const escape = (text) => String(text).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[c]);

  return {
    state: config.state,
    width: config.width,
    height: config.height,
    nullable: config.nullable,
    defaults: config.defaults || [],
    zones: [],
    selected: null,
    dragging: null,
    lastTap: null,
    lastSaved: null,

    init() {
      this.load(this.state);
      new ResizeObserver(() => this.draw()).observe(this.$refs.svg);
      this.$watch('state', (value) => {
        if (JSON.stringify(value ?? null) !== this.lastSaved) this.load(value);
      });
    },

    load(value) {
      this.zones = (Array.isArray(value) ? value : []).map((zone) => ({
        title: zone.title || '',
        text: zone.text || '',
        href: zone.href || '',
        shape: zone.shape === 'line' ? 'line' : 'polygon',
        points: parse(zone.points),
      }));
      if (this.selected !== null && this.selected >= this.zones.length) this.selected = null;
      this.draw();
    },

    save() {
      const value = this.nullable && !this.zones.length ? null : this.zones.map(serialize);
      this.lastSaved = JSON.stringify(value);
      this.state = value;
      this.draw();
    },

    get zone() {
      return this.selected === null ? null : this.zones[this.selected];
    },

    get usesDefaults() {
      return this.nullable && !this.zones.length;
    },

    select(index) {
      this.selected = index;
      this.draw();
    },

    addZone() {
      this.zones.push({ title: 'Новая зона', text: '', href: '', shape: 'polygon', points: [] });
      this.selected = this.zones.length - 1;
      this.save();
    },

    removeZone() {
      if (this.zone === null || !confirm(`Удалить зону «${this.zone.title}»?`)) return;
      this.zones.splice(this.selected, 1);
      this.selected = this.zones.length ? Math.min(this.selected, this.zones.length - 1) : null;
      this.save();
    },

    move(index, step) {
      const target = index + step;
      if (target < 0 || target >= this.zones.length) return;
      [this.zones[index], this.zones[target]] = [this.zones[target], this.zones[index]];
      this.selected = target;
      this.save();
    },

    undoPoint() {
      if (this.zone?.points.length) {
        this.zone.points.pop();
        this.save();
      }
    },

    clearPoints() {
      if (this.zone && confirm('Стереть все точки этой зоны и нарисовать заново?')) {
        this.zone.points = [];
        this.save();
      }
    },

    copyDefaults() {
      this.load(this.defaults);
      this.save();
    },

    resetToDefaults() {
      if (confirm('Убрать свои контуры этой темы? Будут использоваться общие зоны со страницы «Карта».')) {
        this.zones = [];
        this.selected = null;
        this.save();
      }
    },

    pointFromEvent(event) {
      const svg = this.$refs.svg;
      const point = svg.createSVGPoint();
      point.x = event.clientX;
      point.y = event.clientY;
      const local = point.matrixTransform(svg.getScreenCTM().inverse());
      return [
        Math.min(this.width, Math.max(0, Math.round(local.x))),
        Math.min(this.height, Math.max(0, Math.round(local.y))),
      ];
    },

    onPointerDown(event) {
      const target = event.target.closest('[data-point], [data-mid], [data-zone]');

      // Ничего не выбрано: клик выбирает зону под курсором
      if (!this.zone) {
        if (target?.dataset.zone !== undefined) this.select(Number(target.dataset.zone));
        return;
      }
      if (target?.dataset.point !== undefined) {
        const index = Number(target.dataset.point);
        // Двойное нажатие на ту же точку (мышью или пальцем) — удалить её
        if (this.lastTap && this.lastTap.index === index && event.timeStamp - this.lastTap.time < 400) {
          this.lastTap = null;
          this.zone.points.splice(index, 1);
          this.save();
          return;
        }
        this.lastTap = { index, time: event.timeStamp };
        this.startDrag(index, event);
        return;
      }
      if (target?.dataset.mid !== undefined) {
        const index = Number(target.dataset.mid);
        this.zone.points.splice(index + 1, 0, this.pointFromEvent(event));
        this.startDrag(index + 1, event);
        return;
      }
      // Зона выбрана: любой клик по карте (в том числе над соседними зонами) ставит точку
      this.zone.points.push(this.pointFromEvent(event));
      this.save();
    },

    done() {
      this.selected = null;
      this.draw();
    },

    startDrag(index, event) {
      event.preventDefault();
      this.dragging = index;
      this.$refs.svg.setPointerCapture(event.pointerId);
      this.draw();
    },

    onPointerMove(event) {
      if (this.dragging === null || !this.zone) return;
      this.zone.points[this.dragging] = this.pointFromEvent(event);
      this.draw();
    },

    onPointerUp() {
      if (this.dragging === null) return;
      this.dragging = null;
      this.save();
    },

    // SVG собирается строкой: x-for внутри <svg> в Alpine не работает
    draw() {
      const svg = this.$refs.layer;
      if (!svg) return;
      let html = '';

      this.zones.forEach((zone, index) => {
        if (index === this.selected || !zone.points.length) return;
        const tag = zone.shape === 'line' ? 'polyline' : 'polygon';
        html += `<${tag} data-zone="${index}" points="${zone.points.join(' ')}" class="mze-zone mze-zone--${zone.shape}"><title>${escape(zone.title)}</title></${tag}>`;
      });

      // Размер кружков — в экранных пикселях, независимо от масштаба картинки
      const scale = this.width / (this.$refs.svg.getBoundingClientRect().width || this.width);
      const zone = this.zone;
      if (zone) {
        const points = zone.points.join(' ');
        if (zone.points.length > 1) {
          const tag = zone.shape === 'line' ? 'polyline' : 'polygon';
          html += `<${tag} points="${points}" class="mze-zone mze-zone--${zone.shape} is-selected"></${tag}>`;
        }
        const pairs = zone.points.map((point, i) => [point, zone.points[i + 1] ?? (zone.shape === 'polygon' && zone.points.length > 2 ? zone.points[0] : null)]);
        pairs.forEach(([a, b], i) => {
          if (b) html += `<circle data-mid="${i}" cx="${(a[0] + b[0]) / 2}" cy="${(a[1] + b[1]) / 2}" r="${5 * scale}" class="mze-mid"><title>Добавить точку</title></circle>`;
        });
        zone.points.forEach((point, i) => {
          html += `<circle data-point="${i}" cx="${point[0]}" cy="${point[1]}" r="${(i === this.dragging ? 10 : 8) * scale}" class="mze-point${i === 0 ? ' is-first' : ''}"><title>Тяните, чтобы сдвинуть. Двойной клик — удалить</title></circle>`;
        });
      }

      svg.innerHTML = html;
    },
  };
};

// Привязка карты к местности: точка на рисунке + её координаты (из Яндекс Карт).
window.mapAnchorsEditor = function (config) {
  return {
    state: config.state,
    width: config.width,
    height: config.height,
    anchors: [],
    selected: null,
    dragging: false,
    lastSaved: null,

    init() {
      this.load(this.state);
      this.$watch('state', (value) => {
        if (JSON.stringify(value ?? null) !== this.lastSaved) this.load(value);
      });
      new ResizeObserver(() => this.draw()).observe(this.$refs.svg);
    },

    load(value) {
      this.anchors = (Array.isArray(value) ? value : []).map((a) => ({
        label: a.label || '', x: a.x ?? null, y: a.y ?? null, coords: a.coords || '', error: a.error ?? null,
      }));
      if (this.selected !== null && this.selected >= this.anchors.length) this.selected = null;
      this.draw();
    },

    save() {
      const value = this.anchors.map(({ label, x, y, coords }) => ({ label, x, y, coords }));
      this.lastSaved = JSON.stringify(value);
      this.state = value;
      this.draw();
    },

    add() {
      this.anchors.push({ label: `Точка ${this.anchors.length + 1}`, x: null, y: null, coords: '', error: null });
      this.selected = this.anchors.length - 1;
      this.save();
    },

    remove(index) {
      this.anchors.splice(index, 1);
      this.selected = null;
      this.save();
    },

    validCoords(text) {
      return /-?\d{1,2}(\.\d+)?\s*[,; ]\s*-?\d{1,3}(\.\d+)?/.test(text || '');
    },

    pointFromEvent(event) {
      const svg = this.$refs.svg;
      const point = svg.createSVGPoint();
      point.x = event.clientX;
      point.y = event.clientY;
      const local = point.matrixTransform(svg.getScreenCTM().inverse());
      return [Math.min(this.width, Math.max(0, Math.round(local.x))), Math.min(this.height, Math.max(0, Math.round(local.y)))];
    },

    onPointerDown(event) {
      const pin = event.target.closest('[data-anchor]');
      if (pin) {
        this.selected = Number(pin.dataset.anchor);
        this.dragging = true;
        this.$refs.svg.setPointerCapture(event.pointerId);
        this.draw();
        return;
      }
      if (this.selected === null) return;
      [this.anchors[this.selected].x, this.anchors[this.selected].y] = this.pointFromEvent(event);
      this.anchors[this.selected].error = null;
      this.save();
    },

    onPointerMove(event) {
      if (!this.dragging || this.selected === null) return;
      [this.anchors[this.selected].x, this.anchors[this.selected].y] = this.pointFromEvent(event);
      this.draw();
    },

    onPointerUp() {
      if (!this.dragging) return;
      this.dragging = false;
      this.anchors[this.selected].error = null;
      this.save();
    },

    draw() {
      const layer = this.$refs.layer;
      if (!layer) return;
      const scale = this.width / (this.$refs.svg.getBoundingClientRect().width || this.width);
      layer.innerHTML = this.anchors.map((a, i) => {
        if (a.x === null || a.y === null) return '';
        const active = i === this.selected;
        const r = (active ? 14 : 11) * scale;
        return `<g data-anchor="${i}" class="mza-pin${active ? ' is-active' : ''}">
          <circle cx="${a.x}" cy="${a.y}" r="${r}"></circle>
          <text x="${a.x}" y="${a.y}" dy="0.35em" font-size="${13 * scale}">${i + 1}</text></g>`;
      }).join('');
    },
  };
};
