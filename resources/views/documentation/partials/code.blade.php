{{--
    One code block on the technical documentation page.

    Pass either:
      excerpt  App\Support\CodeExcerpt  real lines read from the codebase
               (file path and line numbers are shown automatically), or
      code     string                   literal code, e.g. an example payload.
    Optional:
      lang     highlight.js language (default php)
      title    header text (default: the excerpt's file path)
      meta     right-hand header text (default: the excerpt's line range)
      markers  [line number => label] shown in the gutter instead of the number
      example  data-example name; tests read the block back by this name
--}}
@php
    $source = isset($excerpt) ? $excerpt->code : $code;
    $title ??= isset($excerpt) ? $excerpt->path : null;
    $meta ??= isset($excerpt) && $excerpt->found ? "lines {$excerpt->startLine}–{$excerpt->endLine}" : null;
    $markers ??= [];
    $gutter = isset($excerpt) && $excerpt->found
        ? collect(range($excerpt->startLine, $excerpt->endLine))
            ->map(fn (int $n) => isset($markers[$n]) ? '<b>'.e($markers[$n]).'</b>' : $n)
            ->implode("\n")
        : null;
@endphp
<figure class="code{{ isset($excerpt) && ! $excerpt->found ? ' is-missing' : '' }}">
    @if ($title || $meta)
        <figcaption class="code-head">
            @if ($title)
                <span class="code-file">{{ $title }}</span>
            @endif
            @if ($meta)
                <span class="code-meta">{{ $meta }}</span>
            @endif
        </figcaption>
    @endif
    <div class="code-body">
        @if ($gutter !== null)
            <pre class="code-gutter" aria-hidden="true">{!! $gutter !!}</pre>
        @endif
        <pre class="code-pre"><code class="language-{{ $lang ?? 'php' }}"@isset($example) data-example="{{ $example }}"@endisset>{{ $source }}</code></pre>
    </div>
</figure>
