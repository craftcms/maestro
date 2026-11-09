@extends('_layouts.default')

@section('content')
  @if ($entry->image)
    <figure>
      @php($assets = $entry->image->eagerly()->one())
      @if ($assets)
        <img src="{{ $assets->getUrl('hero') }}" alt="{{ $assets->alt }}" />
      @endif
    </figure>
  @endif
  <header class="container mx-auto pt-12 pb-6 px-2 text-2xl">
    <h1 class="font-bold text-4xl sm:text-6xl lg:text-9xl">{{ $entry->title }}</h1>
    <p class="mt-4">{{ $entry->pageSubheading }}</p>
    @if ($entry->section->handle == 'article')
    <div class="text-xs mt-4">
      @php($category = $entry->category->eagerly()->one())
      <p>@if ($category)<span class="font-bold">{{ $category->title }}</span> | @endif<time datetime="{{ $entry->postDate->format(DATE_ATOM) }}">{{ I18N::getFormatter()->asDate($entry->postDate) }}</time></p>
      <p class="text-xs">By {{ $entry->author->fullName }}</p>
    </div>
    @endif
  </header>
  <section class="page__content">
    <div class="container mx-auto py-12 px-2 text-balance prose">
      {{ $entry->pageContent }}
    </div>
    @yield('landing')
  </section>
@endsection
