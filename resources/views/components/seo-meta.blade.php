@php
    $appName = config('app.name', 'The Daily Cuts');
    $siteUrl = rtrim(config('app.url'), '/');

    $title = ($seo['title'] ?? null)
        ? (str_contains($seo['title'], $appName) ? $seo['title'] : $seo['title'] . ' | ' . $appName)
        : $appName . ' | Online Butchery & Fresh Meat Delivery';

    $description = $seo['description'] ?? 'The Daily Cuts is a fresh artisanal meat shop delivering quality cuts of beef, pork and chicken right to your door. Order online for temperature-controlled same-day delivery.';

    $type = $seo['type'] ?? 'website';

    $image = $seo['image'] ?? ($type === 'product' ? null : $siteUrl . '/frontend/images/hero-main.png');
    if ($image && !preg_match('#^https?://#', $image)) {
        $image = $siteUrl . '/' . ltrim($image, '/');
    }

    $url = url()->current();
    $robots = $seo['robots'] ?? 'index, follow';
@endphp

<title>{{ $title }}</title>
<meta name="description" content="{{ $description }}">
<meta name="robots" content="{{ $robots }}">
<link rel="canonical" href="{{ $url }}">

<meta property="og:type" content="{{ $type }}">
<meta property="og:site_name" content="{{ $appName }}">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ $url }}">
@if ($image)
    <meta property="og:image" content="{{ $image }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="{{ $image }}">
@else
    <meta name="twitter:card" content="summary">
@endif
<meta name="twitter:title" content="{{ $title }}">
<meta name="twitter:description" content="{{ $description }}">
