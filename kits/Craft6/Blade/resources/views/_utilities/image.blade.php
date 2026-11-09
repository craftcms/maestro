@use('CraftCms\Cms\Support\Html')
@foreach ($photos as $photo)
  @if ($photo->extension == 'svg')
    {!! Html::modifyTagAttributes(Html::svg($photo), [
      'role' => ($decorative ?? false) ? null : 'img',
      'aria-label' => ($decorative ?? false) ? null : $photo->alt,
    ]) !!}
  @else
    @php
      $srcset = collect([640, 1024, 1920])
        ->filter(fn (int $outputWidth) => $outputWidth <= $photo->width)
        ->map(fn (int $outputWidth) => $photo->getUrl(['width' => $outputWidth]) . " {$outputWidth}w")
        ->join(', ');
    @endphp
    <picture>
      <source
        data-sizes="100vw"
        type="image/webp" />
      <img class="lazyload"
        src="{{ $photo->url }}"
        srcset="{{ $srcset }}"
        @if ($decorative ?? false)alt=""@elseif ($photo->alt)alt="{{ $photo->alt }}"@endif
        sizes="100vw"
        loading="lazy"
        class="aspect-ratio-wide" />
    </picture>
  @endif
@endforeach
