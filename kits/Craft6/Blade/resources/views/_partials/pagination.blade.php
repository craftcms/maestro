@if ($pageInfo->getPrevUrl() || $pageInfo->getNextUrl())
<nav class="pt-6 text-sm" role="navigation" aria-label="Entry pagination">
  @php
    $prevLinkSummary = ($pageInfo->currentPage - 1) . " of {$pageInfo->totalPages}";
    $nextLinkSummary = ($pageInfo->currentPage + 1) . " of {$pageInfo->totalPages}";
  @endphp
  <ul class="flex justify-between">
  @if ($pageInfo->getPrevUrl())
    <li><a href="{{ $pageInfo->getPrevUrl() }}" class="text-red-600 cursor-pointer font-bold hover:underline focus:underline">← Previous Page ({{ $prevLinkSummary }})</a></li>
  @endif
  @if ($pageInfo->getNextUrl())
    <li class="justify-self-end grow text-right"><a href="{{ $pageInfo->getNextUrl() }}" class="text-red-600 cursor-pointer font-bold hover:underline focus:underline">Next Page ({{ $nextLinkSummary }}) →</a></li>
  @endif
  </ul>
</nav>
@endif
