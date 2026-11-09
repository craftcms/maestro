<figure class="image-block my-12">
  @include('_utilities.image', ['photos' => $entry->image->eagerly()->all()])
</figure>
