<?php

namespace App\Repositories;

use App\Http\Models\CPanel\CPanelUpdate;
use Illuminate\Support\Collection;

/**
 * Persistence for the core-update audit log (cms_updates). The updater records
 * each attempt here; the admin screen reads the history.
 */
class CPanelUpdateRepository extends BaseRepository
{
    public function __construct(CPanelUpdate $model)
    {
        parent::__construct();
        $this->model = $model;
    }

    /**
     * Open a new update attempt (status = pending) and return it.
     */
    public function start(?string $fromVersion, ?string $toVersion): CPanelUpdate
    {
        return $this->model::create([
            'from_version' => $fromVersion,
            'to_version' => $toVersion,
            'status' => 'pending',
            'started_at' => now(),
        ]);
    }

    /**
     * Close an attempt with a terminal status and optional message.
     */
    public function finish(CPanelUpdate $update, string $status, ?string $message = null): CPanelUpdate
    {
        $update->update([
            'status' => $status,
            'message' => $message,
            'finished_at' => now(),
        ]);

        return $update;
    }

    /**
     * Most recent attempts, newest first.
     *
     * @return Collection<int, CPanelUpdate>
     */
    public function recent(int $limit = 10)
    {
        return $this->model::orderByDesc('id')->limit($limit)->get();
    }

    public function latest(): ?CPanelUpdate
    {
        return $this->model::orderByDesc('id')->first();
    }
}
