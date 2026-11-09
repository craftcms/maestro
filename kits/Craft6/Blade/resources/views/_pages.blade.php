@php($segment = request()->segment(1))
@includeFirst([
    "pages.{$segment}.{$entry->slug}",
    "pages.{$segment}.{$entry->type->handle}",
    "pages.{$segment}.{$entry->section->handle}",
    "pages.{$segment}.default",
    "pages.{$entry->section->handle}.{$entry->slug}",
    "pages.{$entry->section->handle}.{$entry->type->handle}",
    "pages.{$entry->section->handle}.default",
    "pages.{$entry->slug}",
    "pages.{$entry->type->handle}",
    "pages.{$entry->section->handle}",
    'pages.default',
])
