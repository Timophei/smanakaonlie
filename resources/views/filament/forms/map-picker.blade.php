{{-- Выбор точки объекта на рисованной карте: клик по картинке записывает проценты в map_x и map_y --}}
<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{
            x: $wire.$entangle('data.map_x'),
            y: $wire.$entangle('data.map_y'),
            pick(event) {
                const box = event.currentTarget.getBoundingClientRect();
                this.x = Math.round((event.clientX - box.left) / box.width * 10000) / 100;
                this.y = Math.round((event.clientY - box.top) / box.height * 10000) / 100;
            },
        }"
        style="display: flex; flex-direction: column; gap: 8px"
    >
        <div x-on:click="pick($event)" style="position: relative; cursor: crosshair; border-radius: 12px; overflow: hidden; max-width: 900px">
            <img src="{{ config('map.image') }}" alt="Карта Шаманки" style="display: block; width: 100%" draggable="false">
            <span x-show="x !== null && x !== '' && y !== null && y !== ''" x-cloak
                  x-bind:style="`left: ${x}%; top: ${y}%`"
                  style="position: absolute; width: 22px; height: 22px; margin: -11px 0 0 -11px; border-radius: 50%; border: 3px solid #fff; background: #b4560f; box-shadow: 0 1px 4px rgba(0, 0, 0, 0.6); pointer-events: none"></span>
        </div>
        <div style="display: flex; gap: 12px; align-items: center; font-size: 14px">
            <span x-text="x !== null && x !== '' ? 'Точка поставлена' : 'Точка не выбрана'"></span>
            <button type="button" x-show="x !== null && x !== ''" x-on:click="x = null; y = null" style="text-decoration: underline">Убрать с карты</button>
        </div>
    </div>
</x-dynamic-component>
