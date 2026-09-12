@php $metadata = app(\App\Services\SeoMetadata::class)->forPage($content ?? null, $page ?? 'home', $siteName ?? config('app.name')); @endphp
<title>{{ $metadata['title'] }}</title>
<meta name="description" content="{{ $metadata['description'] }}">
<meta name="robots" content="{{ $metadata['robots'] }}">
<link rel="canonical" href="{{ $metadata['canonical'] }}">
<meta property="og:title" content="{{ $metadata['title'] }}">
<meta property="og:description" content="{{ $metadata['description'] }}">
<meta property="og:url" content="{{ $metadata['canonical'] }}">
<meta property="og:type" content="{{ $metadata['type'] }}">
@if($metadata['image'])<meta property="og:image" content="{{ $metadata['image'] }}">@endif
