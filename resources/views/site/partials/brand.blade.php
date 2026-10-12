{{-- Логотип в шапке. На компьютере — логотип целиком, на телефоне — знак слева, справа надпись и подпись. --}}
<a class="brand @if($big ?? false) brand--big @endif" href="/" aria-label="Открой Шаманку — на главную">
    <img class="brand-logo" src="{{ $theme?->logoUrl() ?? '/img/logos/autumn-logo.png' }}" alt="" width="864" height="170">
    <img class="brand-icon" src="{{ $theme?->iconUrl() ?? '/img/logos/autumn-icon.png' }}" alt="" width="187" height="182">
    <span class="brand-text">
        <img class="brand-word" src="{{ $theme?->wordmarkUrl() ?? '/img/logos/autumn-wordmark.png' }}" alt="" width="656" height="67">
        <small class="brand-tagline">Тропы, утёс и жизнь на берегу Иркута.</small>
    </span>
</a>
