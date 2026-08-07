<?php

namespace App\Services\CPanel;

use App\Http\Models\NewsletterSubscriber;
use App\Repositories\NewsletterSubscriberRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin-side newsletter management: listing/filtering, manual add (admin vouches,
 * so the row is created already confirmed), delete, and a CSV export of confirmed
 * subscribers. All persistence goes through the repository.
 */
class CPanelNewsletterService
{
    public function __construct(private NewsletterSubscriberRepository $repo) {}

    public function list(?string $status, ?string $search, int $perPage): LengthAwarePaginator
    {
        return $this->repo->paginateFiltered($status, $search, $perPage);
    }

    /**
     * Manually add a subscriber. Admin vouches for consent, so no opt-in email:
     * the row is created already confirmed, source=admin.
     */
    public function add(string $email): NewsletterSubscriber
    {
        return $this->repo->create([
            'email' => mb_strtolower(trim($email)),
            'status' => NewsletterSubscriber::STATUS_CONFIRMED,
            'token' => bin2hex(random_bytes(32)),
            'source' => 'admin',
            'confirmed_at' => now(),
        ]);
    }

    public function delete(int $id): void
    {
        $this->repo->delete($id);
    }

    /**
     * Stream confirmed subscribers as CSV (email,locale,source,confirmed_at).
     */
    public function exportCsv(): StreamedResponse
    {
        $rows = $this->repo->confirmedEmails();
        $filename = 'newsletter-subscribers-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['email', 'locale', 'source', 'confirmed_at']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->email,
                    $row->locale,
                    $row->source,
                    $row->confirmed_at?->toDateTimeString(),
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
