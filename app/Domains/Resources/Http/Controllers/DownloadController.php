<?php

declare(strict_types=1);

namespace App\Domains\Resources\Http\Controllers;

use App\Domains\Resources\Jobs\RecordResourceDownload;
use App\Domains\Resources\Models\Resource;
use App\Domains\Resources\Support\ResourceGate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class DownloadController
{
    public function __invoke(string $slug): Response
    {
        $resource = Resource::published()->where('slug', $slug)->firstOrFail();

        // Enforce the email gate server-side — never trust the UI alone.
        abort_unless(ResourceGate::unlocked($resource), 403);

        RecordResourceDownload::dispatch($resource->id);

        // "link" resources point elsewhere; everything else is a stored file.
        if ($resource->type === 'link' && $resource->external_url) {
            return redirect()->away($resource->external_url);
        }

        abort_unless(
            $resource->file_path && Storage::disk('public')->exists($resource->file_path),
            404,
        );

        return Storage::disk('public')->download(
            $resource->file_path,
            $resource->getTranslation('title', app()->getLocale()).
                '.'.pathinfo($resource->file_path, PATHINFO_EXTENSION),
        );
    }
}
