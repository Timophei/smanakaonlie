{{--
    Редактор зон карты. Переменные: $image, $width, $height, $nullable (у темы: пусто = общие зоны), $defaults.
    Логика — public/js/map-editor.js
--}}
<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <script src="/js/map-editor.js?v={{ filemtime(public_path('js/map-editor.js')) }}"></script>
    <style>
        .mze { display: grid; grid-template-columns: minmax(0, 1fr) 300px; gap: 16px; align-items: start; }
        @media (max-width: 1100px) { .mze { grid-template-columns: 1fr; } }
        .mze-canvas { position: relative; border-radius: 12px; overflow: hidden; user-select: none; }
        .mze-canvas img { display: block; width: 100%; height: auto; pointer-events: none; }
        .mze-canvas svg { position: absolute; inset: 0; width: 100%; height: 100%; touch-action: none; cursor: crosshair; }
        .mze-zone { fill: rgba(255, 255, 255, .15); stroke: #fff; stroke-width: 2px; vector-effect: non-scaling-stroke; cursor: pointer; }
        .mze-zone--line { fill: none; stroke-width: 6px; stroke-linecap: round; stroke-linejoin: round; }
        .mze-zone.is-selected { fill: rgba(37, 99, 235, .25); stroke: #1d4ed8; stroke-width: 3px; pointer-events: none; }
        .mze-zone--line.is-selected { fill: none; stroke-width: 6px; }
        .mze-point { fill: #fff; stroke: #1d4ed8; stroke-width: 3px; vector-effect: non-scaling-stroke; cursor: grab; }
        .mze-point.is-first { fill: #1d4ed8; stroke: #fff; }
        .mze-mid { fill: rgba(255, 255, 255, .8); stroke: #1d4ed8; stroke-width: 1.5px; vector-effect: non-scaling-stroke; cursor: copy; }
        .mze-side { display: flex; flex-direction: column; gap: 12px; font-size: 14px; }
        .mze-list { display: flex; flex-direction: column; gap: 4px; }
        .mze-item { display: flex; align-items: center; gap: 6px; padding: 6px 8px; border-radius: 8px; border: 1px solid rgba(128, 128, 128, .25); cursor: pointer; text-align: left; }
        .mze-item.is-active { border-color: #f59e0b; background: rgba(245, 158, 11, .12); }
        .mze-item b { flex: 1; font-weight: 600; }
        .mze-item small { opacity: .6; }
        .mze-item i { font-style: normal; opacity: .55; padding: 0 4px; }
        .mze-field { display: flex; flex-direction: column; gap: 4px; }
        .mze-field span { font-size: 12px; opacity: .7; }
        select.mze-input { appearance: none; background-repeat: no-repeat; background-position: right 8px center; padding-right: 32px; }
        .mze-input { width: 100%; border-radius: 8px; border: 1px solid rgba(128, 128, 128, .35); background: transparent; padding: 6px 10px; font-size: 14px; }
        .mze-buttons { display: flex; flex-wrap: wrap; gap: 6px; }
        .mze-btn { border-radius: 8px; border: 1px solid rgba(128, 128, 128, .35); padding: 5px 10px; font-size: 13px; }
        .mze-btn--primary { background: #f59e0b; border-color: #f59e0b; color: #fff; font-weight: 600; }
        .mze-btn--danger { color: #dc2626; }
        .mze-hint { font-size: 12px; opacity: .7; line-height: 1.5; }
    </style>

    <div
        x-data="mapZonesEditor({
            state: $wire.$entangle(@js($getStatePath())),
            width: {{ (int) $width }},
            height: {{ (int) $height }},
            nullable: @js((bool) ($nullable ?? false)),
            defaults: @js($defaults ?? []),
        })"
        class="mze"
        wire:ignore
        x-on:keydown.escape.window="done()"
    >
        <div>
            <div class="mze-canvas">
                <img src="{{ $image }}" alt="Карта" draggable="false">
                <svg x-ref="svg" viewBox="0 0 {{ (int) $width }} {{ (int) $height }}"
                     x-on:pointerdown="onPointerDown($event)" x-on:pointermove="onPointerMove($event)"
                     x-on:pointerup="onPointerUp()" x-on:pointercancel="onPointerUp()">
                    <g x-ref="layer"></g>
                </svg>
            </div>
            <p class="mze-hint" style="margin-top: 8px">
                Нажмите на зону на карте или в списке справа. У выбранной зоны точки можно перетаскивать,
                двойной клик по точке удаляет её, светлые кружки посередине сторон добавляют точку между соседними,
                а клик по карте ставит новую точку в конец. Синяя точка — первая. «Готово» или Esc — снять выбор.
            </p>
        </div>

        <div class="mze-side">
            <template x-if="usesDefaults">
                <div class="mze-hint">
                    У этой темы общие зоны со страницы «Карта». Чтобы нарисовать для неё свои, возьмите общие за основу.
                    <div class="mze-buttons" style="margin-top: 8px">
                        <button type="button" class="mze-btn mze-btn--primary" x-on:click="copyDefaults()">Взять общие и править</button>
                    </div>
                </div>
            </template>

            <div class="mze-list" x-show="zones.length">
                <template x-for="(item, index) in zones" :key="index">
                    <div class="mze-item" :class="{ 'is-active': index === selected }" x-on:click="select(index)">
                        <b x-text="item.title || 'Без названия'"></b>
                        <small x-text="item.shape === 'line' ? 'линия' : 'область'"></small>
                        <i x-on:click.stop="move(index, -1)" title="Выше">↑</i>
                        <i x-on:click.stop="move(index, 1)" title="Ниже">↓</i>
                    </div>
                </template>
            </div>

            <div class="mze-buttons">
                <button type="button" class="mze-btn mze-btn--primary" x-on:click="addZone()">+ Новая зона</button>
                <button type="button" class="mze-btn" x-show="nullable && zones.length" x-on:click="resetToDefaults()">Вернуть общие зоны</button>
            </div>

            <template x-if="zone">
                <div class="mze-side">
                    <label class="mze-field"><span>Название (заголовок подсказки)</span>
                        <input class="mze-input" type="text" maxlength="255" x-model="zone.title" x-on:input="save()"></label>
                    <label class="mze-field"><span>Текст подсказки</span>
                        <textarea class="mze-input" rows="3" maxlength="500" x-model="zone.text" x-on:input="save()"></textarea></label>
                    <label class="mze-field"><span>Ссылка по нажатию (например /stati/tropa/), пусто — без ссылки</span>
                        <input class="mze-input" type="text" maxlength="255" x-model="zone.href" x-on:input="save()"></label>
                    <label class="mze-field"><span>Форма</span>
                        <select class="mze-input" x-model="zone.shape" x-on:change="save()">
                            <option value="polygon">Область (утёс, посёлок, река)</option>
                            <option value="line">Линия (паром, мост, речка)</option>
                        </select></label>
                    <div class="mze-hint" x-text="`Точек: ${zone.points.length}` + (zone.points.length < (zone.shape === 'line' ? 2 : 3) ? ' — нужно ещё, иначе зона не сохранится' : '')"></div>
                    <div class="mze-buttons">
                        <button type="button" class="mze-btn mze-btn--primary" x-on:click="done()">Готово</button>
                        <button type="button" class="mze-btn" x-on:click="undoPoint()">Убрать последнюю точку</button>
                        <button type="button" class="mze-btn" x-on:click="clearPoints()">Нарисовать заново</button>
                        <button type="button" class="mze-btn mze-btn--danger" x-on:click="removeZone()">Удалить зону</button>
                    </div>
                </div>
            </template>
        </div>
    </div>
</x-dynamic-component>
