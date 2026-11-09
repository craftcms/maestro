@php
  $pages = $craft->entries()->section('pages')->level(1)->all();
  $links = [
    ['url' => '/blog', 'title' => 'Blog', 'current' => $segment1 == 'blog'],
    ['url' => '/guestbook', 'title' => 'Guestbook', 'current' => $segment1 == 'guestbook'],
    ...array_map(fn ($page) => ['url' => $page->url, 'title' => $page->title, 'current' => $segment1 == $page->slug], $pages),
  ];
@endphp
<nav class="sm:tw-basis-2/3 grow-1" aria-label="Primary">
  <ul class="sm:flex">
    @foreach ($links as $link)
      <li><a href="{{ $link['url'] }}" @class(['block p-2 hover:underline hover:text-red-600', 'text-red-600' => $link['current']])@if ($link['current']) aria-current="page"@endif>{{ $link['title'] }}</a></li>
    @endforeach
  </ul>
</nav>
