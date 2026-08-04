<?php

use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Models\Service;
use App\Http\Models\ServiceTranslation;
use App\Http\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(VerifyCsrfToken::class);
    $this->seed(DatabaseSeeder::class);
    config(['inertia.testing.ensure_pages_exist' => false]);
    $this->admin = User::where('username', 'admin')->firstOrFail();
});

function servicePayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'SEO Audit',
        'slug' => 'seo-audit',
        'excerpt' => 'Full technical SEO audit.',
        'content' => '<p>We audit your site.</p>',
        'meta_keywords' => 'seo, audit',
        'meta_description' => 'SEO audit service',
        'sort_order' => 1,
        'status' => 1,
    ], $overrides);
}

it('creates a service via the admin endpoint', function () {
    $response = $this->actingAs($this->admin)
        ->post('/agentic-cms-laravel-admin/services/new', servicePayload());

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('cpanel_services_list'));
    $response->assertSessionHas('success');

    $translation = ServiceTranslation::where('slug', 'seo-audit')->first();
    expect($translation)->not->toBeNull()
        ->and($translation->title)->toBe('SEO Audit')
        ->and($translation->locale)->toBe('en');
});

it('accepts the JSON payload the Inertia form sends (empty url fields, integer sort_order)', function () {
    // The React form submits JSON with empty strings for optional url fields
    // (thumbnail/canonical_url) and an integer sort_order. Empty strings become
    // null via ConvertEmptyStringsToNull, so nullable|url must pass.
    $this->actingAs($this->admin)
        ->from('/agentic-cms-laravel-admin/services/new')
        ->postJson('/agentic-cms-laravel-admin/services/new', servicePayload([
            'slug' => 'json-service',
            'thumbnail' => '',
            'canonical_url' => '',
            'icon' => '',
            'sort_order' => 3,
        ]))
        ->assertSessionHasNoErrors();

    expect(ServiceTranslation::where('slug', 'json-service')->exists())->toBeTrue();
});

it('updates a service via the admin endpoint', function () {
    $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/services/new', servicePayload());
    $translation = ServiceTranslation::where('slug', 'seo-audit')->firstOrFail();

    $response = $this->actingAs($this->admin)
        ->put('/agentic-cms-laravel-admin/services/'.$translation->service_id.'/update', servicePayload([
            'content' => '<p>edited body</p>',
        ]));

    $response->assertSessionHasNoErrors();
    $response->assertSessionHas('success');

    $fresh = ServiceTranslation::where('slug', 'seo-audit')->firstOrFail();
    expect($fresh->content)->toContain('edited body');
});

it('soft-deletes a service via the admin ajax endpoint', function () {
    $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/services/new', servicePayload());
    $serviceId = ServiceTranslation::where('slug', 'seo-audit')->firstOrFail()->service_id;

    $this->actingAs($this->admin)
        ->delete('/agentic-cms-laravel-admin/services/'.$serviceId.'/delete')
        ->assertOk();

    expect(Service::find($serviceId))->toBeNull()
        ->and(Service::withTrashed()->find($serviceId))->not->toBeNull();
});

it('rejects a service create with a missing required title', function () {
    $response = $this->actingAs($this->admin)
        ->post('/agentic-cms-laravel-admin/services/new', servicePayload(['title' => '']));

    $response->assertSessionHasErrors('title');
    // The seeded sample services remain; the rejected create added nothing.
    expect(ServiceTranslation::where('slug', 'seo-audit')->exists())->toBeFalse();
});

it('renders the admin services list', function () {
    $this->actingAs($this->admin)
        ->post('/agentic-cms-laravel-admin/services/new', servicePayload());

    $this->actingAs($this->admin)
        ->get(route('cpanel_services_list'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('cpanel/services/List')
            ->where('trashed', false)
            ->where('services_list.data', fn ($rows) => collect($rows)->contains('title', 'SEO Audit')));
});

it('renders the new-service form', function () {
    $this->actingAs($this->admin)
        ->get(route('cpanel_add_new_service'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('cpanel/services/Form')
            ->where('entity', null));
});

it('renders the edit-service form', function () {
    $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/services/new', servicePayload());
    $serviceId = ServiceTranslation::where('slug', 'seo-audit')->firstOrFail()->service_id;

    $this->actingAs($this->admin)
        ->get(route('cpanel_edit_service', ['id' => $serviceId, 'lang' => 'en']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('cpanel/services/Form')
            ->where('entity.title', 'SEO Audit')
            ->where('entity.slug', 'seo-audit'));
});
