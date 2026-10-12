{{-- Привязка карты к местности. Переменные: $image, $width, $height. Логика — public/js/map-editor.js --}}
<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <script src="/js/map-editor.js?v={{ filemtime(public_path('js/map-editor.js')) }}"></script>
    <style>
        .mza { display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 16px; align-items: start; }
        @media (max-width: 1100px) { .mza { grid-template-columns: 1fr; } }
        .mza-canvas { position: relative; border-radius: 12px; overflow: hidden; user-select: none; }
        .mza-canvas img { display: block; width: 100%; height: auto; pointer-events: none; }
        .mza-canvas svg { position: absolute; inset: 0; width: 100%; height: 100%; touch-action: none; cursor: crosshair; }
        .mza-pin circle { fill: #fff; stroke: #1d4ed8; stroke-width: 3px; vector-effect: non-scaling-stroke; cursor: grab; }
        .mza-pin.is-active circle { fill: #1d4ed8; stroke: #fff; }
        .mza-pin text { text-anchor: middle; font-weight: 700; fill: #1d4ed8; pointer-events: none; font-family: sans-serif; }
        .mza-pin.is-active text { fill: #fff; }
        .mza-side { display: flex; flex-direction: column; gap: 10px; font-size: 14px; }
        .mza-item { display: grid; grid-template-columns: 28px 1fr auto; gap: 6px 8px; align-items: center; padding: 8px; border-radius: 10px; border: 1px solid rgba(128, 128, 128, .25); }
        .mza-item.is-active { border-color: #1d4ed8; background: rgba(37, 99, 235, .08); }
        .mza-num { width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; background: rgba(37, 99, 235, .12); color: #1d4ed8; cursor: pointer; }
        .mza-input { width: 100%; border-radius: 8px; border: 1px solid rgba(128, 128, 128, .35); background: transparent; padding: 5px 8px; font-size: 13px; }
        .mza-input.is-bad { border-color: #dc2626; }
        .mza-meta { grid-column: 2 / -1; font-size: 12px; opacity: .7; }
        .mza-meta.is-warn { color: #dc2626; opacity: 1; }
        .mza-btn { border-radius: 8px; border: 1px solid rgba(128, 128, 128, .35); padding: 5px 10px; font-size: 13px; }
        .mza-btn--primary { background: #f59e0b; border-color: #f59e0b; color: #fff; font-weight: 600; }
        .mza-hint { font-size: 12px; opacity: .75; line-height: 1.55; }
        .mza-hint ol { padding-left: 18px; list-style: decimal; }
    </style>

    <div class="mza" wire:ignore
         x-data="mapAnchorsEditor({ state: $wire.$entangle(@js($getStatePath())), width: {{ (int) $width }}, height: {{ (int) $height }} })">
        <div class="mza-canvas">
            <img src="{{ $image }}" alt="Карта" draggable="false">
            <svg x-ref="svg" viewBox="0 0 {{ (int) $width }} {{ (int) $height }}"
                 x-on:pointerdown="onPointerDown($event)" x-on:pointermove="onPointerMove($event)"
                 x-on:pointerup="onPointerUp()" x-on:pointercancel="onPointerUp()">
                <g x-ref="layer"></g>
            </svg>
        </div>

        <div class="mza-side">
            <div class="mza-hint">
                Чтобы гость увидел себя на карте («Где я?»), привяжите рисунок к местности:
                <ol>
                    <li>«+ Точка», затем нажмите на карте узнаваемое место: край моста, причал парома, устье Шаманки, поворот реки.</li>
                    <li>В Яндекс Картах нажмите на то же место правой кнопкой (на телефоне — долгим нажатием), скопируйте координаты и вставьте в поле.</li>
                    <li>Нужно минимум 4 точки, лучше 5–6, по разным краям карты. После сохранения у каждой появится расхождение: до 20–30 px — отлично.</li>
                </ol>
            </div>

            <template x-for="(anchor, index) in anchors" :key="index">
                <div class="mza-item" :class="{ 'is-active': index === selected }" x-on:click="selected = index; draw()">
                    <span class="mza-num" x-text="index + 1"></span>
                    <input class="mza-input" type="text" maxlength="100" placeholder="Что это за место" x-model="anchor.label" x-on:input="save()">
                    <button type="button" class="mza-btn" x-on:click.stop="remove(index)" title="Удалить точку">✕</button>
                    <span></span>
                    <input class="mza-input" type="text" placeholder="52.0791, 104.0352" x-model="anchor.coords" x-on:input="save()"
                           :class="{ 'is-bad': anchor.coords && !validCoords(anchor.coords) }" style="grid-column: 2 / -1">
                    <div class="mza-meta" :class="{ 'is-warn': anchor.x === null || (anchor.error !== null && anchor.error > 40) }"
                         x-text="anchor.x === null ? 'Нажмите на карте, где это место' : (anchor.error !== null ? `Расхождение с расчётом: ${anchor.error} px` : `На карте: ${anchor.x}, ${anchor.y}`)"></div>
                </div>
            </template>

            <div><button type="button" class="mza-btn mza-btn--primary" x-on:click="add()">+ Точка</button></div>
        </div>
    </div>
</x-dynamic-component>
