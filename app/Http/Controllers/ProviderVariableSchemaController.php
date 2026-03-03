<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProviderVariableSchemaResource;
use App\Services\ListProviderVariableSchemasService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProviderVariableSchemaController extends Controller
{
    public function __construct(
        protected ListProviderVariableSchemasService $service,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $schemas = $this->service->handleAll();
        return ProviderVariableSchemaResource::collection($schemas);
    }

    public function show(string $slug): AnonymousResourceCollection
    {
        $schemas = $this->service->handle($slug);
        return ProviderVariableSchemaResource::collection($schemas);
    }
}
