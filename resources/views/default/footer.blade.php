<?php
/**
 * AgenticCms-Laravel
 * File: footer.blade.php
 * Created by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 19.07.2019
 * Phase 4: rebuilt to DESIGN_SYSTEM §5 — surface-2 bg, top border,
 *   columns layout (wordmark+tagline, nav groups, locale switcher),
 *   bottom row mono caption copyright + stack attribution.
 */
$site_options = get_site_options();

$copyright = $site_options?->copyright;
$linkedin_url = $site_options?->linkedin_url;
$github_url = $site_options?->github_url;

$languages = get_translation_links();

?>
</main>
<!-- start footer Area -->
<footer class="mt-24 border-t border-[var(--border)] bg-[var(--surface-2)]" data-testid="public-footer">
    <div class="mx-auto max-w-7xl px-5 py-14 sm:px-8">

        {{-- Main columns --}}
        <div class="grid grid-cols-1 gap-10 sm:grid-cols-2 lg:grid-cols-3">

            {{-- Column 1: Wordmark + tagline + social links --}}
            <div>
                <a
                    href="{{config('app.url')}}"
                    class="font-serif text-xl font-semibold tracking-tight text-[var(--text)]"
                    data-testid="footer-wordmark"
                >AgenticCms-Laravel</a>
                @if($copyright)
                <div class="mt-3 text-sm leading-relaxed text-[var(--text-muted)]">{!! $copyright !!}</div>
                @endif

                {{-- Social icons --}}
                @if($linkedin_url || $github_url)
                <div class="mt-4 flex items-center gap-2">
                    @if($linkedin_url)
                    <a
                        href="{{$linkedin_url}}"
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label="LinkedIn"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-[var(--border)] text-[var(--text-muted)] transition hover:border-[var(--border-strong)] hover:bg-[var(--surface)] hover:text-[var(--text)] active:scale-95"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4.98 3.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM3 9h4v12H3zM10 9h3.8v1.7h.05c.53-1 1.83-2.05 3.77-2.05C21.4 8.65 22 11 22 14.1V21h-4v-6.1c0-1.45-.03-3.3-2-3.3s-2.3 1.57-2.3 3.2V21h-4z"/></svg>
                    </a>
                    @endif
                    @if($github_url)
                    <a
                        href="{{$github_url}}"
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label="GitHub"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-[var(--border)] text-[var(--text-muted)] transition hover:border-[var(--border-strong)] hover:bg-[var(--surface)] hover:text-[var(--text)] active:scale-95"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2C6.48 2 2 6.58 2 12.25c0 4.53 2.87 8.37 6.84 9.73.5.1.68-.22.68-.49v-1.7c-2.78.62-3.37-1.21-3.37-1.21-.45-1.18-1.11-1.5-1.11-1.5-.91-.64.07-.62.07-.62 1 .07 1.53 1.06 1.53 1.06.9 1.57 2.36 1.12 2.94.86.09-.67.35-1.12.63-1.38-2.22-.26-4.56-1.14-4.56-5.06 0-1.12.39-2.03 1.03-2.75-.1-.26-.45-1.3.1-2.7 0 0 .84-.28 2.75 1.05a9.4 9.4 0 0 1 5 0c1.91-1.33 2.75-1.05 2.75-1.05.55 1.4.2 2.44.1 2.7.64.72 1.03 1.63 1.03 2.75 0 3.93-2.35 4.79-4.58 5.05.36.32.68.94.68 1.9v2.82c0 .27.18.59.69.49A10.26 10.26 0 0 0 22 12.25C22 6.58 17.52 2 12 2z"/></svg>
                    </a>
                    @endif
                </div>
                @endif
            </div>

            {{-- Column 2: Navigation group --}}
            <div>
                <x-eyebrow class="mb-4">Navigation</x-eyebrow>
                <nav aria-label="Footer navigation">
                    <ul class="flex flex-col gap-2">
                        <li><a href="{{config('app.url')}}" class="text-sm text-[var(--text-muted)] transition-colors hover:text-[var(--text)]">@lang('default/header.homepage_title')</a></li>
                        <li><a href="{{route('get_search_page')}}" class="text-sm text-[var(--text-muted)] transition-colors hover:text-[var(--text)]">@lang('default/header.search')</a></li>
                        @guest
                        <li><a href="{{route('login')}}" class="text-sm text-[var(--text-muted)] transition-colors hover:text-[var(--text)]">@lang('default/header.login')</a></li>
                        @if(get_general_settings('membership'))
                        <li><a href="{{route('register')}}" class="text-sm text-[var(--text-muted)] transition-colors hover:text-[var(--text)]">@lang('default/header.register')</a></li>
                        @endif
                        @endguest
                    </ul>
                </nav>
            </div>

            {{-- Column 3: Locale / language switcher --}}
            @if(!empty($languages))
            <div data-testid="footer-locale-switcher">
                <x-eyebrow class="mb-4">Language</x-eyebrow>
                <ul class="flex flex-col gap-2">
                    @foreach($languages as $code => $language)
                        <li>
                            @if($code === get_current_lang())
                                <span class="inline-flex items-center gap-2 font-mono text-xs uppercase tracking-[0.06em] text-[var(--primary)]">
                                    <img src="{{$language['icon']}}" alt="" width="16" height="16" decoding="async" class="h-4 w-4 rounded-sm">
                                    {{ strtoupper($code) }} — {{$language['title']}}
                                </span>
                            @else
                                <a
                                    href="{{$language['url']}}"
                                    class="inline-flex items-center gap-2 font-mono text-xs uppercase tracking-[0.06em] text-[var(--text-muted)] transition-colors hover:text-[var(--text)]"
                                    data-testid="lang-{{ $code }}"
                                >
                                    <img src="{{$language['icon']}}" alt="{{$language['title']}}" width="16" height="16" decoding="async" class="h-4 w-4 rounded-sm">
                                    {{ strtoupper($code) }} — {{$language['title']}}
                                </a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>

        {{-- Bottom row: mono caption copyright + stack attribution --}}
        <div class="mt-12 flex flex-col gap-1 border-t border-[var(--border)] pt-6 sm:flex-row sm:items-center sm:justify-between">
            <p class="font-mono text-xs text-[var(--text-subtle)]">
                © {{ date('Y') }} AgenticCms-Laravel. All rights reserved.
            </p>
            <p class="font-mono text-xs text-[var(--text-subtle)]">
                Built with
                <a href="https://elman.group" target="_blank" rel="noopener noreferrer"
                   class="text-[var(--text-muted)] underline-offset-2 transition hover:text-[var(--text)] hover:underline">Elman Group</a>
            </p>
        </div>
    </div>

    {{-- Plugin render-region hook (DESIGN_SYSTEM §5 footer spec) --}}
    @hook('footer')
</footer>
<!-- End footer Area -->

@stack('extrascripts')
</body>
</html>
