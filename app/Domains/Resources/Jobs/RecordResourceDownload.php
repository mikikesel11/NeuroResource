<?php

declare(strict_types=1);

namespace App\Domains\Resources\Jobs;

use App\Domains\Resources\Models\Resource;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Increments a Resource's download counter off the request path so downloads
 * stay fast. See docs/system-design.md §3.2.
 */
class RecordResourceDownload implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $resourceId) {}

    public function handle(): void
    {
        Resource::whereKey($this->resourceId)->increment('download_count');
    }
}
