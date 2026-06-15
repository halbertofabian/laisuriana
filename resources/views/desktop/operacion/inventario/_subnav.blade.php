<div class="desktop-pivot" role="tablist" aria-label="Submódulos de inventario">
    @foreach($submenus as $submenu)
        <a
            href="{{ $submenu['route'] }}"
            class="desktop-btn {{ $activeSubmenu === $submenu['key'] ? 'desktop-btn--active' : '' }}"
            @if($activeSubmenu === $submenu['key']) aria-current="page" @endif
        >
            {{ $submenu['label'] }}
        </a>
    @endforeach
</div>
