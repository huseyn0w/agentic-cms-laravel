<?php

namespace Tests\Feature\CPanel;

use App\Http\Models\ContactSubmission;
use App\Http\Models\User;
use App\Http\Models\UserRoles;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Admin contact inbox: lists stored submissions, is gated by manage_messages,
 * marks a message read on view, and deletes.
 */
class CPanelContactInboxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['inertia.testing.ensure_pages_exist' => false]);
        $this->seed(DatabaseSeeder::class);
    }

    private function admin(): User
    {
        return User::where('username', 'admin')->firstOrFail();
    }

    private function nonManager(): User
    {
        $role = UserRoles::create([
            'name' => 'role_'.bin2hex(random_bytes(4)),
            'permissions' => json_encode(['see_admin_panel' => 1, 'manage_messages' => 0]),
        ]);

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function submission(array $overrides = []): ContactSubmission
    {
        return ContactSubmission::create(array_merge([
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.com',
            'subject' => 'Enquiry',
            'message' => 'Hello there.',
        ], $overrides));
    }

    public function test_inbox_lists_submissions_with_unread_count(): void
    {
        $this->submission();
        $this->submission(['email' => 'grace@example.com', 'subject' => 'Second']);

        $this->actingAs($this->admin())
            ->get(route('cpanel_contact_list'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/contact/List')
                ->where('unread_count', 2)
                ->has('submissions.data', 2));
    }

    public function test_inbox_requires_manage_messages(): void
    {
        $this->actingAs($this->nonManager())
            ->get(route('cpanel_contact_list'))
            ->assertStatus(401);
    }

    public function test_viewing_a_submission_marks_it_read(): void
    {
        $submission = $this->submission();
        $this->assertNull($submission->read_at);

        $this->actingAs($this->admin())
            ->get(route('cpanel_contact_show', $submission->id))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('cpanel/contact/Show')
                ->where('submission.message', 'Hello there.'));

        $this->assertNotNull($submission->fresh()->read_at);
    }

    public function test_unread_filter_narrows_the_list(): void
    {
        $read = $this->submission(['read_at' => now()]);
        $this->submission(['email' => 'new@example.com']);

        $this->actingAs($this->admin())
            ->get(route('cpanel_contact_list', ['unread' => 1]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('submissions.data', 1)
                ->where('submissions.data.0.email', 'new@example.com'));
    }

    public function test_admin_can_delete_a_submission(): void
    {
        $submission = $this->submission();

        $this->actingAs($this->admin())
            ->delete(route('cpanel_contact_destroy', $submission->id))
            ->assertRedirect(route('cpanel_contact_list'));

        $this->assertDatabaseMissing('contact_submissions', ['id' => $submission->id]);
    }
}
