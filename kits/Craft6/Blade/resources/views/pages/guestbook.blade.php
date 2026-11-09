@extends('pages.default')

@php
  $postQuery = $craft->entries()->section('guestbookPosts')->limit(3)->orderBy('postDate DESC');
  $postSection = Sections::getSectionByHandle('guestbookPosts');
@endphp
@craftPaginate($postQuery)
@php([$pageInfo, $posts] = [$paginate, $paginatedItems])

@section('title')
  @if ($pageInfo->getPrevUrl() || $pageInfo->getNextUrl())
  , Page {{ $pageInfo->currentPage }} of {{ $pageInfo->totalPages }}
  @endif
@endsection

@section('landing')
  <div class="container mx-auto px-2 sm:grid gap-6 grid-cols-2">
    <section class="mb-12">
      @if (count($posts))
        <ol class="mb-2 divide-y divide-slate-300">
        @foreach ($posts as $post)
          <li>
            <article class="text-xl py-6">
              {!! Markdown::parse((string) $post->textBlock) !!}
              <p class="text-sm mt-1">
                <time datetime="{{ $post->postDate->format(DATE_ATOM) }}">{{ I18N::getFormatter()->asDatetime($post->postDate) }}</time><br />
                By {{ $post->author->fullName }}
              </p>
            </article>
          </li>
        @endforeach
        </ol>
        @include('_partials.pagination', ['pageInfo' => $pageInfo])
      @else
        <p class="text-2xl">No entries yet. Create one using the form.</p>
      @endif
    </section>

    <section>
      <div class="bg-slate-200 p-6 mb-9 rounded">
        <h2 class="font-bold text-3xl mb-4">Post an entry</h2>
        @if ($currentUser)
          @include('_partials.postForm', ['id' => $postSection->id])
        @else
          <p>You need to be signed in to post an entry in the guestbook.</p>
          @include('_partials.signInForm')
      @endif
      </div>
    </section>

  </div>
@endsection
