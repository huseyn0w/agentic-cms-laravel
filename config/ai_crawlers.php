<?php

/**
 * AEO — catalog of AI/LLM crawlers that can be allowed or blocked from the
 * admin SEO settings. Keyed by a stable slug; `ua` is the exact robots.txt
 * User-agent token, `label` is shown in the settings UI. Every bot is ALLOWED
 * by default — a bot only gets a `Disallow: /` stanza when explicitly turned
 * off (seo_settings.ai_crawlers[key] === false). Extend this list to expose
 * more bots; no migration needed.
 */
return [
    'gptbot' => ['label' => 'GPTBot — OpenAI training', 'ua' => 'GPTBot'],
    'oai_searchbot' => ['label' => 'OAI-SearchBot — OpenAI search', 'ua' => 'OAI-SearchBot'],
    'chatgpt_user' => ['label' => 'ChatGPT-User — user-opened links', 'ua' => 'ChatGPT-User'],
    'claudebot' => ['label' => 'ClaudeBot — Anthropic training', 'ua' => 'ClaudeBot'],
    'claude_searchbot' => ['label' => 'Claude-SearchBot — Anthropic search', 'ua' => 'Claude-SearchBot'],
    'claude_user' => ['label' => 'Claude-User — user-opened links', 'ua' => 'Claude-User'],
    'perplexitybot' => ['label' => 'PerplexityBot — Perplexity', 'ua' => 'PerplexityBot'],
    'google_extended' => ['label' => 'Google-Extended — Gemini / AI Overviews', 'ua' => 'Google-Extended'],
    'applebot_extended' => ['label' => 'Applebot-Extended — Apple Intelligence', 'ua' => 'Applebot-Extended'],
    'ccbot' => ['label' => 'CCBot — Common Crawl', 'ua' => 'CCBot'],
    'meta_externalagent' => ['label' => 'Meta-ExternalAgent — Meta AI', 'ua' => 'meta-externalagent'],
];
