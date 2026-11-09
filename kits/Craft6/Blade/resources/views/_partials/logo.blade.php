@php($hasLogo = $global->logo->count() > 0)
<a href="/" @class(['text-red-600', 'block w-12 h-12' => $hasLogo])>
  @if ($hasLogo)
    <span class="sr-only">{{ $siteName }}</span>
    @include('_utilities.image', ['photos' => $global->logo->eagerly()->all(), 'decorative' => true])
  @else
    {{ $siteName }}
  @endif
</a>
