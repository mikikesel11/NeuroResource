<?php

declare(strict_types=1);

namespace App\Domains\Profile\Http\Controllers;

use App\Domains\Profile\Models\Profile;
use Illuminate\View\View;

class AboutController
{
    /**
     * Show the About page for the single featured person, with their
     * Certifications ordered for display. See docs/system-design.md §3.3.
     */
    public function __invoke(): View
    {
        $profile = Profile::with('certifications')->first();

        return view('about', ['profile' => $profile]);
    }
}
