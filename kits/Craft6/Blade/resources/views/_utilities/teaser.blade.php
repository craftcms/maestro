  <article class="py-6">
    @if ($featured ?? false)
      @php($hero = $post->image->eagerly()->one())
      <figure class="image-block my-4">
        <a href="{{ $post->url }}">
          @if ($hero)
            <img src="{{ $hero->getUrl('hero') }}" alt="{{ $hero->alt }}" />
          @else
            <div class="bg-slate-200 hover:bg-slate-300 aspect-video"></div>
          @endif
        </a>
      </figure>
    @endif
    <h2 class="text-4xl font-bold"><a href="{{ $post->url }}" class="text-red-600 hover:undeline focus:underline cursor-pointer">{{ $post->title }}</a></h2>
    <p>{{ $post->pageSubheading }}</p>
    <p>
      <time class="text-sm" datetime="{{ $post->postDate->format(DATE_ATOM) }}">{{ I18N::getFormatter()->asDate($post->postDate) }}</time>
    </p>
  </article>
