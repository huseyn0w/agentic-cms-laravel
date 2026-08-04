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

    $translation = ServiceTranslation::where('slug', 'seo-audit')->first();
    expect($translation)->not->toBeNull()
        ->and($translation->title)->toBe('SEO Audit')
        ->and($translation->locale)->toBe('en');
});

it('updates a service via the admin endpoint', function () {
    $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/services/new', servicePayload());
    $translation = ServiceTranslation::where('slug', 'seo-audit')->firstOrFail();

    $response = $this->actingAs($this->admin)
        ->put('/agentic-cms-laravel-admin/services/'.$translation->service_id.'/update', servicePayload([
            'content' => '<p>edited body</p>',
        ]));

    $response->assertSessionHasNoErrors();

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
        ->assertOk();
});

it('renders the edit-service form', function () {
    $this->actingAs($this->admin)->post('/agentic-cms-laravel-admin/services/new', servicePayload());
    $serviceId = ServiceTranslation::where('slug', 'seo-audit')->firstOrFail()->service_id;

    $this->actingAs($this->admin)
        ->get(route('cpanel_edit_service', ['id' => $serviceId, 'lang' => 'en']))
        ->assertOk()
        ->assertSee('SEO Audit');
});
