@extends('pages.default')

@section('landing')
  <section class="bg-slate-100">
    <div class="container mx-auto divide-y divide-slate-300 py-12 px-2">
      <h2 class="font-bold text-4xl mb-2">Other articles</h2>
      @php($posts = $craft->entries()->section('blogPosts')->id('not ' . $entry->id)->limit(3)->all())
      @foreach ($posts as $post)
        @include('_utilities.teaser', ['post' => $post])
      @endforeach
    </div>
  </section>
@endsection
