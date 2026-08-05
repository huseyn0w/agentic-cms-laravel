<?php

use App\Http\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seoService(string $slug, int $status = 1): Service
{
    $s = Service::create(['sort_order' => 0]);
    $t = $s->translateOrNew('en');
    $t->title = ucfirst(str_replace('-', ' ', $slug));
    $t->slug = $slug;
    $t->excerpt = 'We provide '.$slug.'.';
    $t->status = $status;
    $s->save();

    return $s;
}

it('lists published services in sitemap.xml', function () {
    seoService('managed-hosting', 1);
    seoService('draft-service', 0);

    $body = $this->get('/sitemap.xml')->assertOk()->getContent();

    expect($body)->toContain('services/managed-hosting')
        ->and($body)->not->toContain('services/draft-service');
});

it('links published services in llms.txt', function () {
    seoService('managed-hosting', 1);

    $this->get('/llms.txt')
        ->assertOk()
        ->assertSee('/services/managed-hosting', false);
});

it('excludes draft services from llms.txt', function () {
    seoService('managed-hosting', 1);
    seoService('secret-service', 0);

    $body = $this->get('/llms.txt')->assertOk()->getContent();

    expect($body)->toContain('/services/managed-hosting')
        ->and($body)->not->toContain('/services/secret-service');
});

it('emits Service JSON-LD on the services index', function () {
    seoService('managed-hosting', 1);

    // The ItemList is server-rendered via json_ld() now (pretty-printed, so a
    // space after the colon); the services index body itself is React.
    $this->get('/services')
        ->assertOk()
        ->assertSee('"@type": "Service"', false)
        ->assertSee('Managed hosting', false);
});
