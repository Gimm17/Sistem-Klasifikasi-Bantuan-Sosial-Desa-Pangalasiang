<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lihat jejak audit (superadmin). Fitur TAMBAHAN.
 */
class ActivityLogController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return JsonResource::collection(
            ActivityLog::with('user')->orderByDesc('id')->paginate(30)
        );
    }
}
