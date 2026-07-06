<?php

declare(strict_types=1);

namespace App\Domains\Resources\Http\Controllers;

use App\Domains\Resources\Models\ResourceUnlock;
use App\Domains\Resources\Support\ResourceGate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ConfirmUnlockController
{
    /**
     * Confirm an email-gated unlock (double opt-in). Marks the capture
     * confirmed, unlocks the resource for this session, and sends the visitor to
     * the resource page where the download is now available.
     *
     * The link is a signed + expiring URL: the `signed` middleware on the route
     * rejects tampered/expired links with a 403 before we get here. We re-check
     * the signature defensively and keep the DB token lookup as a second factor.
     */
    public function __invoke(Request $request, string $token): RedirectResponse
    {
        abort_unless($request->hasValidSignature(), 403);

        $unlock = ResourceUnlock::where('token', $token)->firstOrFail();

        $resource = $unlock->resource;

        if (! $unlock->isConfirmed()) {
            $unlock->confirmed_at = now();
            $unlock->save();
            // Integration point: add $unlock->email to the mailing list here.

            // Grant session access only on first confirmation. Re-visits (email
            // pre-fetch, archived link) must not append duplicate slugs.
            $this->grantSessionAccess($resource->slug);
        }

        return redirect()
            ->route('resources.show', $resource->slug)
            ->with('justConfirmed', true);
    }

    /** Push the slug into the session gate, de-duplicating on re-entry. */
    private function grantSessionAccess(string $slug): void
    {
        $unlocked = session(ResourceGate::SESSION_KEY, []);

        if (! in_array($slug, $unlocked, true)) {
            session()->push(ResourceGate::SESSION_KEY, $slug);
        }
    }
}
