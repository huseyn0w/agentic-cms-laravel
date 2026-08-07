<?php

namespace App\Repositories;

use App\Http\Models\NewsletterSubscriber;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * All ORM access for newsletter subscribers. The service/controller layers call
 * only these methods (arch LayeringTest keeps Eloquent out of them).
 */
class NewsletterSubscriberRepository extends BaseRepository
{
    public function __construct(NewsletterSubscriber $model)
    {
        parent::__construct();
        $this->model = $model;
    }

    public function findByEmail(string $email): ?NewsletterSubscriber
    {
        return $this->model::query()->where('email', $email)->first();
    }

    public function findByToken(string $token): ?NewsletterSubscriber
    {
        return $this->model::query()->where('token', $token)->first();
    }

    /**
     * Insert a subscriber. Untyped param keeps the signature compatible with
     * BaseRepository::create while bypassing its translatable machinery (this
     * model is not translatable).
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create($attributes): NewsletterSubscriber
    {
        return $this->model::query()->create($attributes);
    }

    public function save(NewsletterSubscriber $subscriber): void
    {
        $subscriber->save();
    }

    /**
     * Admin list: optionally narrowed by status and an email LIKE search,
     * newest first.
     */
    public function paginateFiltered(?string $status, ?string $search, int $perPage): LengthAwarePaginator
    {
        return $this->model::query()
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($search, fn ($q) => $q->where('email', 'like', '%'.$search.'%'))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Confirmed subscribers shaped for CSV export.
     *
     * @return Collection<int, NewsletterSubscriber>
     */
    public function confirmedEmails(): Collection
    {
        return $this->model::query()
            ->where('status', NewsletterSubscriber::STATUS_CONFIRMED)
            ->orderBy('email')
            ->get(['email', 'locale', 'source', 'confirmed_at']);
    }
}
