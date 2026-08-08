<?php

namespace App\Http\Controllers\CPanel;

use App\Http\Requests\StoreNewsletterSubscriberRequest;
use App\Services\CPanel\CPanelNewsletterService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin newsletter subscriber management. Gated by manage_newsletter (route
 * group middleware). Thin: shaping + delegation to CPanelNewsletterService.
 */
class CPanelNewsletterController extends CPanelBaseController
{
    public function __construct(private CPanelNewsletterService $newsletter)
    {
        parent::__construct();
    }

    public function index(Request $request)
    {
        $status = $request->query('status');
        $search = $request->query('search');

        $subscribers = $this->newsletter->list(
            is_string($status) ? $status : null,
            is_string($search) ? $search : null,
            $this->per_page,
        );

        $subscribers->getCollection()->transform(fn ($s) => [
            'id' => $s->id,
            'email' => $s->email,
            'status' => $s->status,
            'locale' => $s->locale,
            'source' => $s->source,
            'subscribed' => $s->created_at?->format('d.m.Y'),
        ]);

        return Inertia::render('cpanel/newsletter/List', [
            'subscribers' => $subscribers,
            'filters' => [
                'status' => is_string($status) ? $status : null,
                'search' => is_string($search) ? $search : null,
            ],
        ]);
    }

    public function store(StoreNewsletterSubscriberRequest $request)
    {
        $this->newsletter->add($request->validated('email'));

        return back()->with('success', __('cpanel/newsletter.added'));
    }

    public function destroy(int $id)
    {
        $this->newsletter->delete($id);

        return back()->with('success', __('cpanel/newsletter.deleted'));
    }

    public function export(): StreamedResponse
    {
        return $this->newsletter->exportCsv();
    }
}
