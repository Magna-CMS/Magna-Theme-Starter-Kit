{{--
    Starter override — the callout block.

    WHY OVERRIDE? To add a per-tone icon, which CSS alone cannot do well
    (an icon needs real SVG markup). Everything else about the block is kept.

    HOW AN OVERRIDE WORKS. A file at views/blocks/{handle}.blade.php replaces
    core's view for that block entirely. It receives:

      $block       the block's view payload — $block['data'] holds what the
                   editor typed (fields from the block's schema), and
                   $block['_resolved'] holds anything the server resolved
      $definition  the block's schema (fields, labels, options)

    THE RULES, every one of them kept below:

      • One root element, first in the file. The builder stamps its node
        marker and an editor's style class onto the FIRST element — put a
        <style> or a second sibling first and the wrong element gets them.
      • Keep core's wrapper classes (magna-block, magna-block--callout,
        magna-callout, magna-callout--{tone}). Other themes, addons and
        site CSS target them.
      • Validate choices against the schema's own option list before they
        reach an attribute; a stored value may predate today's schema.
      • Escape everything with double braces. Never print unescaped, except
        the sanitized $block['_resolved'] fields core documents. No script tags.
        No queries.
      • Render nothing when there is nothing to show, like core does.
--}}
@php
    $body = trim((string) ($block['data']['body'] ?? ''));
    $heading = trim((string) ($block['data']['heading'] ?? ''));
    $tone = (string) ($block['data']['tone'] ?? 'info');
    $tone = in_array($tone, ['info', 'success', 'warning', 'danger'], true) ? $tone : 'info';

    // One 24×24 stroke path per tone (Lucide-style, inline so no asset is needed).
    $icons = [
        'info' => 'M12 16v-4M12 8h.01M22 12a10 10 0 1 1-20 0 10 10 0 0 1 20 0z',
        'success' => 'M22 11.1V12a10 10 0 1 1-5.9-9.1M22 4 12 14l-3-3',
        'warning' => 'M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0zM12 9v4M12 17h.01',
        'danger' => 'M12 8v4M12 16h.01M7.9 2h8.2L22 7.9v8.2L16.1 22H7.9L2 16.1V7.9z',
    ];
@endphp
@if($body !== '')
    <aside class="magna-block magna-block--callout magna-callout magna-callout--{{ $tone }}" role="note">
        <svg class="starter-callout__icon" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $icons[$tone] }}"/></svg>
        @if($heading !== '')
            <p class="magna-callout__heading">{{ $heading }}</p>
        @endif
        <div class="magna-callout__body">{{ $body }}</div>
    </aside>
@endif
