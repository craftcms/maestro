@extends('pages.default')

@php($postQuery = $craft->entries()->section('blogPosts')->limit(4))
@craftPaginate($postQuery)
@php([$pageInfo, $posts] = [$paginate, $paginatedItems])

@section('title')
  @if ($pageInfo->getPrevUrl() || $pageInfo->getNextUrl())
  , Page {{ $pageInfo->currentPage }} of {{ $pageInfo->totalPages }}
  @endif
@endsection

@section('landing')
  <section class="container mx-auto mb-6 px-2 divide-y divide-slate-300">
    <div class="sm:grid sm:grid-cols-2 sm:gap-6">
      @foreach ($posts as $post)
        @include('_utilities.teaser', ['post' => $post, 'featured' => true])
      @endforeach
    </div>
    @include('_partials.pagination', ['pageInfo' => $pageInfo])
  </section>
@endsection
