@props([
    'title',
    'subtitle' => null,
    'icon'     => 'tabler-layout-dashboard',
    'eyebrow'  => null,
    'variant'  => 'default',
])

<header {{ $attributes->class([
    'app-section-header',
    'app-section-header--compact' => $variant === 'compact',
]) }}>
    {{-- main --}}
    <div class="app-section-header__main">
        <span class="app-section-header__icon" aria-hidden="true">
            <i class="icon-base ti {{ $icon }}"></i>
        </span>
        <div>
            @if($eyebrow)
                <p class="app-section-header__eyebrow">{{ $eyebrow }}</p>
            @endif
            <h1 class="app-section-header__title">{{ $title }}</h1>
            @if($subtitle)
                <p class="app-section-header__subtitle">{{ $subtitle }}</p>
            @endif
        </div>
    </div>

    {{-- actions slot --}}
    @if(isset($actions))
        <div class="app-section-header__actions">
            {{ $actions }}
        </div>
    @endif
</header>
