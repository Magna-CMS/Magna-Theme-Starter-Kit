{{--
    Starter override — the features block.

    WHY OVERRIDE? Core renders each item's title as an <h3> no matter where
    the block sits. Starter keeps the <h3> but wraps the text in a body
    element so the icon can sit beside it in the "horizontal-list" layout,
    and skips items that are completely empty (a half-filled JSON array
    otherwise renders blank cards).

    LIST DATA. `items` is a `json` field: the editor stores a JSON STRING,
    e.g. [{"icon":"⚡","title":"Fast","description":"…"}]. Decode it, and
    accept an already-decoded array too — a site kit or an older document
    may hold either. Treat every key as optional.

    Same rules as every override: one root element first, core's classes
    kept, everything escaped, no script, no queries.
--}}
@php
    $raw = $block['data']['items'] ?? [];
    $items = is_array($raw) ? $raw : (json_decode(is_string($raw) ? $raw : '[]', true) ?: []);
    $layout = in_array($block['data']['layout'] ?? 'icon-grid', ['icon-grid', 'horizontal-list'], true)
        ? ($block['data']['layout'] ?? 'icon-grid')
        : 'icon-grid';
@endphp
<div class="magna-block magna-block--features magna-features magna-features--{{ $layout }}">
    @foreach($items as $item)
        @continue(! is_array($item) || (empty($item['title']) && empty($item['description'])))
        <div class="magna-features__item">
            @if(!empty($item['icon']))
                <span class="magna-features__icon" aria-hidden="true">{{ $item['icon'] }}</span>
            @endif
            <div class="starter-features__body">
                @if(!empty($item['title']))
                    <h3 class="magna-features__title">{{ $item['title'] }}</h3>
                @endif
                @if(!empty($item['description']))
                    <p class="magna-features__description">{{ $item['description'] }}</p>
                @endif
            </div>
        </div>
    @endforeach
</div>
