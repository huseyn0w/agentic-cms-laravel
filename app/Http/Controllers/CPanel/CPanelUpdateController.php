<?php

namespace App\Http\Controllers\CPanel;

use App\Support\Updater\UpdateException;
use App\Support\Updater\UpdateService;
use Inertia\Inertia;

/**
 * Admin core-update screen: shows the current version, whether an update is
 * available (from the background check), and the update history; lets an admin
 * re-check the feed and run the update. Gated by manage_updates on the route
 * group. Thin — all work lives in UpdateService.
 */
class CPanelUpdateController extends CPanelBaseController
{
    public function __construct(UpdateService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function index()
    {
        return Inertia::render('cpanel/updates/Index', [
            'current_version' => cms_version(),
            'available' => $this->service->cachedAvailable(),
            'history' => $this->service->history()->map(fn ($u) => [
                'id' => $u->id,
                'from_version' => $u->from_version,
                'to_version' => $u->to_version,
                'status' => $u->status,
                'message' => $u->message,
                'finished_at' => optional($u->finished_at)->toDateTimeString(),
            ]),
        ]);
    }

    /**
     * Re-check the feed now and cache the result.
     */
    public function check()
    {
        $release = $this->service->refreshAvailability();

        $message = $release === null
            ? __('cpanel/updates.up_to_date')
            : __('cpanel/updates.update_available', ['version' => $release['version']]);

        return back()->with('success', $message);
    }

    /**
     * Run the update to the latest available release.
     */
    public function run()
    {
        $release = $this->service->cachedAvailable() ?? $this->service->refreshAvailability();

        if ($release === null) {
            return back()->with('success', __('cpanel/updates.up_to_date'));
        }

        try {
            $audit = $this->service->update($release);
            $this->service->refreshAvailability();

            return back()->with('success', __('cpanel/updates.updated', ['version' => $audit->to_version]));
        } catch (UpdateException $e) {
            return back()->with('error', __('cpanel/updates.failed', ['error' => $e->getMessage()]));
        }
    }
}
