{{--
  AgenticCms-Laravel — Phase 7 (SEO/GEO)
  File: partials/seo-meta.blade.php

  Single, clean <head> meta partial. Driven by whatever the controllers already
  pass into the view ($data for posts/pages/categories, $user for profiles) with
  global fallbacks from the SEO settings singleton (get_seo_settings()).

  Outputs: <title>, description, canonical, robots, Open Graph, Twitter Card,
  hreflang alternates (+ x-default), verification tags, and schema.org JSON-LD
  (WebSite + Organization on home, Article + BreadcrumbList on posts,
  CollectionPage/BreadcrumbList on categories, ProfilePage/Person on profiles).
--}}
@php
    $seo            = get_seo_settings();
    $separator      = $seo->title_separator ?? '—';
    $siteName       = $seo->og_site_name ?? get_general_settings('website_name');
    $appUrl         = rtrim(config('app.url'), '/');
    $routeName      = request()->route() ? request()->route()->getName() : null;
    $currentUrl     = url()->current();
    $locale         = get_current_lang();
    $ogLocale       = $locale === 'ru' ? 'ru_RU' : 'en_US';

    // --- Context detection -------------------------------------------------
    $entity      = $data ?? null;                 // post / page / category row
    $profileUser = (isset($user) && $routeName === 'show_user') ? $user : null;
    $isPost      = in_array($routeName, ['posts', 'posts_localized'], true);
    $isCategory  = in_array($routeName, ['categories_first_page', 'categories_display_pages', 'categories_localized'], true);
    $isHome      = (isset($entity) && isset($entity->slug) && $entity->slug === '/') || $routeName === null;

    // --- Title -------------------------------------------------------------
    if (is_search_page()) {
        $entityTitle = trans('default/header.searchpage_title');
    } elseif ($profileUser) {
        $entityTitle = trim($profileUser->name . ' ' . $profileUser->surname) ?: $profileUser->username;
    } elseif (isset($entity->title)) {
        $entityTitle = $entity->title;
    } else {
        $entityTitle = $siteName;
    }

    $pageTitle = ($entityTitle && $entityTitle !== $siteName)
        ? $entityTitle . ' ' . $separator . ' ' . $siteName
        : $siteName;

    // --- Description -------------------------------------------------------
    $metaDescription = null;
    if ($profileUser) {
        $metaDescription = $profileUser->about_me ?? null;
    } elseif (isset($entity->meta_description)) {
        $metaDescription = $entity->meta_description;
    }
    if (empty($metaDescription)) {
        $metaDescription = $seo->default_meta_description ?? get_general_settings('tagline');
    }
    $metaDescription = trim((string) $metaDescription);

    // --- Keywords (legacy, low priority) -----------------------------------
    $metaKeywords = $entity->meta_keywords ?? null;

    // --- Canonical ---------------------------------------------------------
    $canonical = $entity->canonical_url ?? null;
    if (empty($canonical)) {
        $canonical = $currentUrl;
    }

    // --- Robots ------------------------------------------------------------
    $discourage  = (bool) ($seo->discourage_search_engines ?? false);
    $entityNoindex = (bool) ($entity->meta_noindex ?? false);
    $robots = ($discourage || $entityNoindex) ? 'noindex, nofollow' : 'index, follow';

    // --- Open Graph image --------------------------------------------------
    $ogImage = $entity->thumbnail ?? null;
    if (empty($ogImage) && $profileUser) {
        $ogImage = $profileUser->avatar ?? null;
    }
    if (empty($ogImage)) {
        $ogImage = $seo->default_og_image ?? null;
    }

    // --- OG type -----------------------------------------------------------
    $ogType = $isPost ? 'article' : ($profileUser ? 'profile' : 'website');

    // --- hreflang alternates ----------------------------------------------
    $alternates = [];
    try {
        $links = get_translation_links();
        foreach ($links as $code => $info) {
            $url = $info['url'] ?? null;
            // get_translation_links() returns null url for the current locale.
            $alternates[$code] = $url ?: $currentUrl;
        }
    } catch (\Throwable $e) {
        $alternates = [];
    }

    // --- Twitter handle ----------------------------------------------------
    $twitter = $seo->twitter_handle ?? null;

    // --- OG article meta (§8: articles add published_time + author) --------
    $articlePublishedTime = null;
    $articleAuthor        = null;
    if ($isPost && isset($entity)) {
        if (!empty($entity->created_at)) {
            $articlePublishedTime = \Carbon\Carbon::parse($entity->created_at)->toIso8601String();
        }
        if (isset($entity->author)) {
            $articleAuthor = trim(($entity->author->name ?? '') . ' ' . ($entity->author->surname ?? '')) ?: null;
        }
    }

    // --- OG image dimensions (§8: emit width/height when known) ------------
    // Only resolvable for images stored locally under the public disk; remote
    // URLs are left without dimensions rather than guessing.
    $ogImageWidth  = null;
    $ogImageHeight = null;
    if (!empty($ogImage)) {
        try {
            $appHost   = parse_url($appUrl, PHP_URL_HOST);
            $imageHost = parse_url($ogImage, PHP_URL_HOST);
            $localPath = null;
            if (empty($imageHost) || $imageHost === $appHost) {
                $relative  = ltrim(parse_url($ogImage, PHP_URL_PATH) ?? $ogImage, '/');
                $candidate = public_path($relative);
                if (is_file($candidate)) {
                    $localPath = $candidate;
                }
            }
            if ($localPath) {
                $size = @getimagesize($localPath);
                if ($size !== false) {
                    $ogImageWidth  = $size[0];
                    $ogImageHeight = $size[1];
                }
            }
        } catch (\Throwable $e) {
            $ogImageWidth = $ogImageHeight = null;
        }
    }
@endphp

<title>{{ $pageTitle }}</title>
<meta name="description" content="{{ $metaDescription }}">
@if(!empty($metaKeywords))
<meta name="keywords" content="{{ $metaKeywords }}">
@endif
<meta name="robots" content="{{ $robots }}">
<link rel="canonical" href="{{ $canonical }}">

{{-- hreflang alternates (en/ru) + x-default --}}
@foreach($alternates as $code => $altUrl)
<link rel="alternate" hreflang="{{ $code }}" href="{{ $altUrl }}">
@endforeach
@if(!empty($alternates))
<link rel="alternate" hreflang="x-default" href="{{ $alternates[config('app.locale')] ?? $canonical }}">
@endif

{{-- Feed autodiscovery (RSS 2.0 / Atom 1.0) --}}
<link rel="alternate" type="application/rss+xml" title="{{ get_general_settings('website_name') ?: config('app.name') }} &raquo; RSS" href="{{ url('/rss.xml') }}">
<link rel="alternate" type="application/atom+xml" title="{{ get_general_settings('website_name') ?: config('app.name') }} &raquo; Atom" href="{{ url('/atom.xml') }}">

{{-- Open Graph --}}
<meta property="og:title" content="{{ $entityTitle }}">
<meta property="og:description" content="{{ $metaDescription }}">
<meta property="og:type" content="{{ $ogType }}">
<meta property="og:url" content="{{ $canonical }}">
@if($siteName)<meta property="og:site_name" content="{{ $siteName }}">@endif
<meta property="og:locale" content="{{ $ogLocale }}">
@foreach($alternates as $code => $altUrl)
@if($code !== $locale)<meta property="og:locale:alternate" content="{{ $code === 'ru' ? 'ru_RU' : 'en_US' }}">@endif
@endforeach
@if(!empty($ogImage))<meta property="og:image" content="{{ $ogImage }}">@endif
@if(!empty($ogImageWidth) && !empty($ogImageHeight))
<meta property="og:image:width" content="{{ $ogImageWidth }}">
<meta property="og:image:height" content="{{ $ogImageHeight }}">
@endif

{{-- Article meta (§8): published time + author on article/post pages --}}
@if($ogType === 'article')
@if(!empty($articlePublishedTime))<meta property="article:published_time" content="{{ $articlePublishedTime }}">@endif
@if(!empty($entity->updated_at))<meta property="article:modified_time" content="{{ \Carbon\Carbon::parse($entity->updated_at)->toIso8601String() }}">@endif
@if(!empty($articleAuthor))<meta property="article:author" content="{{ $articleAuthor }}">@endif
@endif

{{-- Twitter Card --}}
<meta name="twitter:card" content="summary_large_image">
@if(!empty($twitter))
<meta name="twitter:site" content="{{ $twitter }}">
<meta name="twitter:creator" content="{{ $twitter }}">
@endif
<meta name="twitter:title" content="{{ $entityTitle }}">
<meta name="twitter:description" content="{{ $metaDescription }}">
@if(!empty($ogImage))<meta name="twitter:image" content="{{ $ogImage }}">@endif

{{-- Search-engine verification --}}
@if(!empty($seo->google_site_verification))
<meta name="google-site-verification" content="{{ $seo->google_site_verification }}">
@endif
@if(!empty($seo->bing_site_verification))
<meta name="msvalidate.01" content="{{ $seo->bing_site_verification }}">
@endif

{{-- ---------------------------- JSON-LD ------------------------------- --}}
@php
    $jsonLdBlocks = [];

    // WebSite + Organization on the homepage (GEO / rich results).
    if ($isHome) {
        $jsonLdBlocks[] = [
            '@context'        => 'https://schema.org',
            '@type'           => 'WebSite',
            'name'            => $siteName,
            'url'             => $appUrl,
            'inLanguage'      => $locale,
            'potentialAction' => [
                '@type'       => 'SearchAction',
                'target'      => [
                    '@type'       => 'EntryPoint',
                    'urlTemplate' => $appUrl . '/search?query={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
        // Organization / LocalBusiness — enriched from the admin GEO settings so
        // generative engines (ChatGPT, Perplexity, Gemini, Google AI) can read
        // who you are, what you offer, where, and how to trust/cite you.
        $geo     = get_geo_settings();
        $emitGeo = $geo && $geo->emit_jsonld;

        $org = [
            '@context' => 'https://schema.org',
            '@type'    => ($emitGeo && !empty($geo->business_type)) ? $geo->business_type : 'Organization',
            'name'     => ($emitGeo && !empty($geo->business_name)) ? $geo->business_name : $siteName,
            'url'      => $appUrl,
        ];

        if (!empty($seo->default_og_image)) {
            $org['logo'] = $seo->default_og_image;
        }

        if ($emitGeo) {
            if (!empty($geo->description))   { $org['description'] = $geo->description; }
            if (!empty($geo->service_area))  { $org['areaServed']  = $geo->service_area; }
            if (!empty($geo->address))       { $org['address']     = $geo->address; }
            if (!empty($geo->founder_name))  { $org['founder']     = ['@type' => 'Person', 'name' => $geo->founder_name]; }

            $services = $geo->servicesList();
            if (!empty($services)) {
                $org['knowsAbout'] = $services;
                $org['makesOffer'] = array_map(fn ($s) => [
                    '@type'       => 'Offer',
                    'itemOffered' => ['@type' => 'Service', 'name' => $s],
                ], $services);
            }

            $sameAs = $geo->sameAsList();
            if (!empty($sameAs)) {
                $org['sameAs'] = $sameAs;
            }

            $contact = [];
            if (!empty($geo->contact_email)) { $contact['email']     = $geo->contact_email; }
            if (!empty($geo->contact_phone)) { $contact['telephone'] = $geo->contact_phone; }
            if (!empty($contact)) {
                $org['contactPoint'] = ['@type' => 'ContactPoint', 'contactType' => 'customer support'] + $contact;
            }
        }

        $jsonLdBlocks[] = $org;

        // FAQPage — generative engines quote these Q&A pairs directly.
        if ($emitGeo) {
            $faq = $geo->faqList();
            if (!empty($faq)) {
                $jsonLdBlocks[] = [
                    '@context'   => 'https://schema.org',
                    '@type'      => 'FAQPage',
                    'mainEntity' => array_map(fn ($qa) => [
                        '@type'          => 'Question',
                        'name'           => $qa['question'],
                        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $qa['answer']],
                    ], $faq),
                ];
            }
        }
    }

    // Article / BlogPosting + BreadcrumbList on posts.
    if ($isPost && isset($entity)) {
        $authorName = isset($entity->author)
            ? trim(($entity->author->name ?? '') . ' ' . ($entity->author->surname ?? ''))
            : null;

        $article = [
            '@context'      => 'https://schema.org',
            '@type'         => 'BlogPosting',
            'headline'      => $entity->title ?? $entityTitle,
            'mainEntityOfPage' => $canonical,
            'inLanguage'    => $locale,
        ];
        if (!empty($entity->created_at)) {
            $article['datePublished'] = \Carbon\Carbon::parse($entity->created_at)->toIso8601String();
        }
        if (!empty($entity->updated_at)) {
            $article['dateModified'] = \Carbon\Carbon::parse($entity->updated_at)->toIso8601String();
        }
        if (!empty($authorName)) {
            $article['author'] = ['@type' => 'Person', 'name' => $authorName];
        }
        if (!empty($metaDescription)) {
            $article['description'] = $metaDescription;
        }
        if (!empty($ogImage)) {
            $article['image'] = $ogImage;
        }
        if ($siteName) {
            $article['publisher'] = ['@type' => 'Organization', 'name' => $siteName];
        }
        $jsonLdBlocks[] = $article;

        $crumbs = [['name' => $siteName, 'item' => $appUrl]];
        if (isset($entity->categories[0])) {
            $crumbs[] = [
                'name' => $entity->categories[0]->title,
                'item' => $appUrl . '/category/' . $entity->categories[0]->slug,
            ];
        }
        $crumbs[] = ['name' => $entity->title ?? $entityTitle, 'item' => $canonical];

        $jsonLdBlocks[] = [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => collect($crumbs)->map(fn ($c, $i) => [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'name'     => $c['name'],
                'item'     => $c['item'],
            ])->values()->all(),
        ];
    }

    // CollectionPage + BreadcrumbList on category archives.
    if ($isCategory && isset($entity)) {
        $jsonLdBlocks[] = [
            '@context'   => 'https://schema.org',
            '@type'      => 'CollectionPage',
            'name'       => $entity->title ?? $entityTitle,
            'url'        => $canonical,
            'inLanguage' => $locale,
        ];
        $jsonLdBlocks[] = [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => $siteName, 'item' => $appUrl],
                ['@type' => 'ListItem', 'position' => 2, 'name' => $entity->title ?? $entityTitle, 'item' => $canonical],
            ],
        ];
    }

    // ProfilePage + Person on public user profiles.
    if ($profileUser) {
        $person = [
            '@type' => 'Person',
            'name'  => trim($profileUser->name . ' ' . $profileUser->surname) ?: $profileUser->username,
        ];
        if (!empty($profileUser->avatar)) {
            $person['image'] = $profileUser->avatar;
        }
        $sameAs = array_values(array_filter([
            $profileUser->facebook_url ?? null,
            $profileUser->twitter_url ?? null,
            $profileUser->linkedin_url ?? null,
            $profileUser->instagram_url ?? null,
        ]));
        if (!empty($sameAs)) {
            $person['sameAs'] = $sameAs;
        }
        $jsonLdBlocks[] = [
            '@context'   => 'https://schema.org',
            '@type'      => 'ProfilePage',
            'url'        => $canonical,
            'mainEntity' => $person,
        ];
    }
@endphp
@foreach($jsonLdBlocks as $block)
{!! json_ld($block) !!}
@endforeach

{{-- Plugin render-region: head (e.g. analytics/meta injected by plugins) --}}
@hook('head')
