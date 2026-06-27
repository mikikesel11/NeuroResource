<?php

namespace App\Domains\Resources\Http\Controllers;

use App\Domains\Resources\Models\ResourceUnlock;
use App\Domains\Resources\Support\ResourceGate;
use Illuminate\Http\RedirectResponse;

class ConfirmUnlockController
{
    /**
     * Confirm an email-gated unlock (double opt-in). Marks the capture
     * confirmed, unlocks the resource for this session, and sends the visitor to
     * the resource page where the download is now available.
     */
    public function __invoke(string $token): RedirectResponse
    {
        $unlock = ResourceUnlock::where('token', $token)->firstOrFail();

        if (! $unlock->isConfirmed()) {
            $unlock->update(['confirmed_at' => now()]);
            // Integration point: add $unlock->email to the mailing list here.
        }

        $resource = $unlock->resource;
        session()->push(ResourceGate::SESSION_KEY, $resource->slug);

        return redirect()
            ->route('resources.show', $resource->slug)
            ->with('justConfirmed', true);
    }
}
