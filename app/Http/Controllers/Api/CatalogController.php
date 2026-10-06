<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;

final class CatalogController extends Controller
{
    public function organizations(): JsonResponse
    {
        return response()->json(Organization::query()->select(['id', 'name', 'slug', 'type', 'description', 'logo', 'verified'])->orderBy('name')->paginate(30));
    }

    public function categories(): JsonResponse
    {
        return response()->json(['data' => Category::query()->orderBy('name')->get()]);
    }
}
