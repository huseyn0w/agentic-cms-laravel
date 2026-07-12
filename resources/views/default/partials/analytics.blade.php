{{--
  AgenticCms-Laravel — Phase 7 (SEO/GEO)
  File: partials/analytics.blade.php

  The single, OPTIONAL third-party script the public theme will ever load, and
  only when an ID is configured in the admin SEO settings. Loaded async so it
  never blocks render. Default (empty settings) = zero output, zero requests.
  Explicitly unlike many CMS platforms: no tag managers or trackers by default.
--}}
@php
    $seo = get_seo_settings();
    $ga4 = $seo->ga4_measurement_id ?? null;
    $gtm = $seo->gtm_container_id ?? null;
@endphp

@if(!empty($gtm))
    {{-- Google Tag Manager (async) --}}
    <script async src="https://www.googletagmanager.com/gtm.js?id={{ $gtm }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({ 'gtm.start': new Date().getTime(), event: 'gtm.js' });
    </script>
@elseif(!empty($ga4))
    {{-- Google Analytics 4 (async) --}}
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga4 }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', @json($ga4));
    </script>
@endif
