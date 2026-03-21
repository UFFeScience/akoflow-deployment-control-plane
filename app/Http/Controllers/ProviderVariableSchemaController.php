<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateProviderVariableSchemaRequest;
use App\Http\Resources\ProviderVariableSchemaResource;
use App\Repositories\ProviderRepository;
use App\Repositories\ProviderVariableSchemaRepository;
use App\Services\ListProviderVariableSchemasService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProviderVariableSchemaController extends Controller
{
    public function __construct(
        protected ListProviderVariableSchemasService $service,
        protected ProviderVariableSchemaRepository $schemas,
        protected ProviderRepository $providers,
    ) {}

    public function index(string $organizationId, string $providerId): AnonymousResourceCollection
    {
        $provider = $this->providers->findOrFailById($providerId);
        $schemas = $this->service->handle($provider->slug);
        return ProviderVariableSchemaResource::collection($schemas);
    }

    public function indexAll(): AnonymousResourceCollection
    {
        $schemas = $this->service->handleAll();
        return ProviderVariableSchemaResource::collection($schemas);
    }

    public function indexBySlug(string $slug): AnonymousResourceCollection
    {
        $schemas = $this->service->handle($slug);
        return ProviderVariableSchemaResource::collection($schemas);
    }

    public function store(string $organizationId, string $providerId, CreateProviderVariableSchemaRequest $request): JsonResponse
    {
        $provider = $this->providers->findOrFailById($providerId);
        $data = $request->validated();
        $schema = $this->schemas->createForProvider($provider->slug, [
            'section'       => $data['section'],
            'name'          => $data['name'],
            'label'         => $data['label'],
            'description'   => $data['description'] ?? null,
            'type'          => $data['type'],
            'required'      => $data['required'] ?? false,
            'is_sensitive'  => $data['is_sensitive'] ?? false,
            'position'      => $data['position'] ?? 0,
            'options_json'  => isset($data['options']) ? json_encode($data['options']) : null,
            'default_value' => $data['default_value'] ?? null,
        ]);

        return response()->json(['data' => new ProviderVariableSchemaResource($schema)], 201);
    }
}
