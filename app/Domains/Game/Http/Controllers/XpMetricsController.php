<?php

namespace App\Domains\Game\Http\Controllers;

use App\Domains\Game\Services\XpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class XpMetricsController
{
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json(
            app(XpService::class)->metrics($request->user())
        );
    }
}
