<?php

namespace App\Repositories;

use App\Http\Models\ContactSubmission;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * All ORM access for contact submissions. Service/controller layers call only
 * these methods (arch LayeringTest keeps Eloquent out of them). Mirrors
 * NewsletterSubscriberRepository.
 */
class ContactSubmissionRepository extends BaseRepository
{
    public function __construct(ContactSubmission $model)
    {
        parent::__construct();
        $this->model = $model;
    }

    /**
     * Insert a submission. Untyped param keeps the signature compatible with
     * BaseRepository::create while bypassing its translatable machinery.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create($attributes): ContactSubmission
    {
        return $this->model::query()->create($attributes);
    }

    public function find(int $id): ?ContactSubmission
    {
        return $this->model::query()->find($id);
    }

    /**
     * Admin inbox: optional "unread only" filter and an email/subject/name LIKE
     * search, newest first.
     */
    public function paginateFiltered(bool $unreadOnly, ?string $search, int $perPage): LengthAwarePaginator
    {
        return $this->model::query()
            ->when($unreadOnly, fn ($q) => $q->whereNull('read_at'))
            ->when($search, fn ($q) => $q->where(function ($sub) use ($search) {
                $sub->where('email', 'like', '%'.$search.'%')
                    ->orWhere('subject', 'like', '%'.$search.'%')
                    ->orWhere('first_name', 'like', '%'.$search.'%')
                    ->orWhere('last_name', 'like', '%'.$search.'%');
            }))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function countUnread(): int
    {
        return $this->model::query()->whereNull('read_at')->count();
    }

    public function markRead(ContactSubmission $submission): void
    {
        $submission->forceFill(['read_at' => now()])->save();
    }

    public function remove(ContactSubmission $submission): void
    {
        $submission->delete();
    }
}
