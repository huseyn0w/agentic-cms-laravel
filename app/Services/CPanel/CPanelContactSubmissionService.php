<?php

namespace App\Services\CPanel;

use App\Http\Models\ContactSubmission;
use App\Repositories\ContactSubmissionRepository;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Admin contact-inbox domain service. All data access is delegated to the
 * repository (arch LayeringTest keeps the ORM out of here).
 */
class CPanelContactSubmissionService
{
    public function __construct(private ContactSubmissionRepository $repo) {}

    public function list(bool $unreadOnly, ?string $search, int $perPage): LengthAwarePaginator
    {
        return $this->repo->paginateFiltered($unreadOnly, $search, $perPage);
    }

    /**
     * Fetch a submission and mark it read (viewing an inbox message reads it).
     */
    public function view(int $id): ?ContactSubmission
    {
        $submission = $this->repo->find($id);

        if ($submission === null) {
            return null;
        }

        if (! $submission->isRead()) {
            $this->repo->markRead($submission);
        }

        return $submission;
    }

    public function delete(int $id): void
    {
        $submission = $this->repo->find($id);

        if ($submission !== null) {
            $this->repo->remove($submission);
        }
    }

    public function unreadCount(): int
    {
        return $this->repo->countUnread();
    }
}
