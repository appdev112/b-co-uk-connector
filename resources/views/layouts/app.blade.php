<html lang="en-GB">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $meta_title ?? '' }}</title>
        <link rel="stylesheet" href="https://www.betting.co.uk/wp-content/cache/gl-optimize/www-betting-co-uk/dd0e9e60.css">

        <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
        <script>window.iana = 0;</script>
        <meta name="description" content="{{ $meta_description ?? '' }}">
        <link rel="canonical" href="https://www.betting.co.uk/">
        <meta property="og:locale" content="en_GB">
        <meta property="og:type" content="website">
        <meta property="og:title" content="{{ $meta_title ?? '' }}">
        <meta property="og:description" content="{{ $meta_description ?? '' }}">
        <meta property="og:url" content="https://www.betting.co.uk/">
        <meta property="og:site_name" content="Betting.co.uk">
        <meta property="article:modified_time" content="{{ $modified_time ?? '' }}">
        <meta property="og:image" content="https://www.betting.co.uk/wp-content/uploads/betting-co-uk-no-thumb.jpg.webp">
        <meta property="og:image:width" content="800">
        <meta property="og:image:height" content="450">
        <meta property="og:image:type" content="image/jpeg">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="google-site-verification" content="MmOB59hISh4NHhxCCfttfi2MMY6dwRu2SUugkeLozUo">
        <link rel="alternate" hreflang="en-GB" href="https://www.betting.co.uk/">
        <link rel="alternate" hreflang="en-IE" href="https://www.betting.co.uk/ie/">
        <script>
        function themeSetGlovalJsVar() { if (window.innerWidth
        < 992) { window.anims = 0; } else { window.anims = 250; } } themeSetGlovalJsVar(); window.addEventListener('resize', themeSetGlovalJsVar);
        </script><link rel="preconnect" href="https://in.getclicky.com"><link rel="icon" href="https://www.betting.co.uk/wp-content/uploads/betting-co-uk-favicon-512x512-1-150x150.png.webp" sizes="32x32">
        <link rel="icon" href="https://www.betting.co.uk/wp-content/uploads/betting-co-uk-favicon-512x512-1-300x300.png.webp" sizes="192x192">
        <link rel="apple-touch-icon" href="https://www.betting.co.uk/wp-content/uploads/betting-co-uk-favicon-512x512-1-300x300.png.webp">
        <meta name="msapplication-TileImage" content="https://www.betting.co.uk/wp-content/uploads/betting-co-uk-favicon-512x512-1-300x300.png.webp">

        @if(!empty($base_css))
        <style>
            {!! $base_css !!}
        </style>
        @endif

        @if(!empty($site_theme_css))
        {!! $site_theme_css !!}
        @endif
    </head>
    <body>
        @include('b-co-uk-connector::layouts.header')
        {!! $content ?? '' !!}
        @include('b-co-uk-connector::layouts.footer')
    </body>
</html>