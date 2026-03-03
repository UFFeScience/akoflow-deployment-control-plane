<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProviderVariableSchemaResource;
use App\Services\ListProviderVariableSchemasService;

class ProviderVariableSchemaController extends Controller
{
    public function __construct(
        protected ListProviderVariableSchemasService $service,
    ) {}

    public function index(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $schemas = $this->service->handleAll();
        return ProviderVariableSchemaResource::collection($schemas);
    }

    public function show(string $slug): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $schemas = $this->service->handle($slug);
        return ProviderVariableSchemaResource::collection($schemas);
    }
}
