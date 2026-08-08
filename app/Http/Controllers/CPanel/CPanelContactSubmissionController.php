<?php

namespace App\Http\Controllers\CPanel;

use App\Services\CPanel\CPanelContactSubmissionService;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Admin contact-form inbox. Gated by manage_messages (route group middleware).
 * Thin: shaping + delegation to CPanelContactSubmissionService. Mirrors the
 * newsletter admin slice.
 */
class CPanelContactSubmissionController extends CPanelBaseController
{
    public function __construct(private CPanelContactSubmissionService $inbox)
    {
        parent::__construct();
    }

    public function index(Request $request)
    {
        $unread = $request->boolean('unread');
        $search = $request->query('search');
        $search = is_string($search) ? $search : null;

        $submissions = $this->inbox->list($unread, $search, $this->per_page);

        $submissions->getCollection()->transform(fn ($s) => [
            'id' => $s->id,
            'name' => trim($s->first_name.' '.$s->last_name),
            'email' => $s->email,
            'subject' => $s->subject,
            'read' => $s->isRead(),
            'received' => $s->created_at?->format('d.m.Y H:i'),
        ]);

        return Inertia::render('cpanel/contact/List', [
            'submissions' => $submissions,
            'filters' => ['unread' => $unread, 'search' => $search],
            'unread_count' => $this->inbox->unreadCount(),
        ]);
    }

    public function show(int $id)
    {
        $submission = $this->inbox->view($id);

        abort_if($submission === null, 404);

        return Inertia::render('cpanel/contact/Show', [
            'submission' => [
                'id' => $submission->id,
                'first_name' => $submission->first_name,
                'last_name' => $submission->last_name,
                'email' => $submission->email,
                'subject' => $submission->subject,
                'message' => $submission->message,
                'ip' => $submission->ip,
                'received' => $submission->created_at?->format('d.m.Y H:i'),
            ],
        ]);
    }

    public function destroy(int $id)
    {
        $this->inbox->delete($id);

        return redirect()
            ->route('cpanel_contact_list')
            ->with('success', __('cpanel/contact.deleted'));
    }
}
